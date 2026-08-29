<?php

namespace App\Services;

use App\Exceptions\InvalidBackupException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Throwable;
use ZipArchive;

class BackupService
{
    public const FORMAT = 'umric.backup.v1';

    public const FILENAME_PATTERN = '/^umric-backup-\d{4}-\d{2}-\d{2}-\d{6}(?:-[a-z0-9]{6})?\.zip$/';

    public const LOCK_KEY = 'umric-system-backup';

    /**
     * @return array{
     *     filename: string,
     *     size: int,
     *     size_label: string,
     *     created_at: string,
     *     created_at_formatted: string,
     *     tables: int,
     *     files: int
     * }
     */
    public function create(): array
    {
        $this->assertZipAvailable();

        $lock = Cache::lock(self::LOCK_KEY, 300);

        if (! $lock->get()) {
            throw new InvalidBackupException('A backup or restore is already in progress. Try again in a few minutes.');
        }

        $tempArchive = null;

        try {
            $filename = $this->newFilename();
            $tables = $this->dumpTables();
            $fileManifest = $this->collectFiles();

            $manifest = [
                'format' => self::FORMAT,
                'app' => (string) config('app.name'),
                'created_at' => now()->toIso8601String(),
                'tables' => array_keys($tables),
                'disks' => array_keys($fileManifest),
                'file_count' => $this->countCollectedFiles($fileManifest),
            ];

            $tempArchive = $this->writeArchive($manifest, $tables, $fileManifest);
            $this->storeArchive($filename, $tempArchive);
            $this->prune();

            return $this->describe($filename);
        } finally {
            if (is_string($tempArchive) && is_file($tempArchive)) {
                @unlink($tempArchive);
            }

            $lock->release();
        }
    }

    /**
     * @return list<array{
     *     filename: string,
     *     size: int,
     *     size_label: string,
     *     created_at: string,
     *     created_at_formatted: string
     * }>
     */
    public function list(): array
    {
        $disk = $this->archiveDisk();
        $directory = $this->archivePath();

        if (! $disk->exists($directory)) {
            return [];
        }

        return collect($disk->files($directory))
            ->map(fn (string $path): string => basename($path))
            ->filter(fn (string $filename): bool => $this->isValidFilename($filename))
            ->map(fn (string $filename): array => $this->describe($filename))
            ->sortByDesc('created_at')
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     filename: string,
     *     size: int,
     *     size_label: string,
     *     created_at: string,
     *     created_at_formatted: string,
     *     tables?: int,
     *     files?: int
     * }
     */
    public function describe(string $filename): array
    {
        $this->assertValidFilename($filename);

        $path = $this->archiveRelativePath($filename);
        $disk = $this->archiveDisk();

        if (! $disk->exists($path)) {
            throw new InvalidBackupException('That backup file was not found.');
        }

        $modified = $disk->lastModified($path);
        $createdAt = now()->setTimestamp($modified);
        $size = (int) $disk->size($path);

        return [
            'filename' => $filename,
            'size' => $size,
            'size_label' => Number::fileSize($size, precision: 1),
            'created_at' => $createdAt->toIso8601String(),
            'created_at_formatted' => $createdAt->timezone((string) config('app.timezone'))->format('M d, Y H:i'),
        ];
    }

    public function archiveAbsolutePath(string $filename): string
    {
        $this->assertValidFilename($filename);

        $relative = $this->archiveRelativePath($filename);
        $disk = $this->archiveDisk();

        if (! $disk->exists($relative)) {
            throw new InvalidBackupException('That backup file was not found.');
        }

        return $disk->path($relative);
    }

    public function restoreFromStored(string $filename): void
    {
        $this->restoreFromAbsolutePath($this->archiveAbsolutePath($filename));
    }

    public function restoreFromAbsolutePath(string $absolutePath): void
    {
        $this->assertZipAvailable();

        if (! is_file($absolutePath)) {
            throw new InvalidBackupException('That backup file was not found.');
        }

        $lock = Cache::lock(self::LOCK_KEY, 600);

        if (! $lock->get()) {
            throw new InvalidBackupException('A backup or restore is already in progress. Try again in a few minutes.');
        }

        $extractDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'umric-restore-'.Str::uuid();

        try {
            $payload = $this->extractAndValidate($absolutePath, $extractDir);

            Schema::disableForeignKeyConstraints();

            try {
                DB::transaction(function () use ($payload): void {
                    $this->restoreTables($payload['tables']);
                });
            } finally {
                Schema::enableForeignKeyConstraints();
            }

            $this->restoreFiles($payload['files']);
            Cache::flush();
        } finally {
            $this->deleteDirectory($extractDir);
            $lock->release();
        }
    }

    public function storeUploadedArchive(string $absolutePath): string
    {
        $this->assertZipAvailable();

        $extractDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'umric-upload-check-'.Str::uuid();

        try {
            $this->extractAndValidate($absolutePath, $extractDir, validateOnly: true);
        } finally {
            $this->deleteDirectory($extractDir);
        }

        $filename = $this->newFilename();
        $this->storeArchive($filename, $absolutePath);
        $this->prune();

        return $filename;
    }

    public function delete(string $filename): void
    {
        $this->assertValidFilename($filename);

        $path = $this->archiveRelativePath($filename);
        $disk = $this->archiveDisk();

        if (! $disk->exists($path)) {
            throw new InvalidBackupException('That backup file was not found.');
        }

        $disk->delete($path);
    }

    public function isValidFilename(string $filename): bool
    {
        return (bool) preg_match(self::FILENAME_PATTERN, $filename);
    }

    public function prune(): void
    {
        $keep = max(1, (int) config('backup.keep', 14));
        $backups = $this->list();

        foreach (array_slice($backups, $keep) as $backup) {
            $this->archiveDisk()->delete($this->archiveRelativePath($backup['filename']));
        }
    }

    public function assertValidFilename(string $filename): void
    {
        if (! $this->isValidFilename($filename)) {
            throw new InvalidBackupException('The backup filename is not valid.');
        }
    }

    private function newFilename(): string
    {
        return 'umric-backup-'.now()->format('Y-m-d-His').'-'.Str::lower(Str::random(6)).'.zip';
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    private function dumpTables(): array
    {
        $dump = [];

        foreach ($this->backupableTables() as $table) {
            $dump[$table] = $this->dumpTable($table);
        }

        return $dump;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function dumpTable(string $table): array
    {
        $rows = [];
        $query = DB::table($table);
        $columns = Schema::getColumnListing($table);

        if (in_array('id', $columns, true)) {
            $query->orderBy('id')->chunk(500, function ($chunk) use (&$rows): void {
                foreach ($chunk as $row) {
                    $rows[] = $this->serializeRow($row);
                }
            });

            return $rows;
        }

        foreach ($query->get() as $row) {
            $rows[] = $this->serializeRow($row);
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeRow(object $row): array
    {
        $data = [];

        foreach ((array) $row as $key => $value) {
            if ($value instanceof \DateTimeInterface) {
                $data[$key] = $value->format('Y-m-d H:i:s');

                continue;
            }

            if (is_resource($value)) {
                continue;
            }

            $data[$key] = $value;
        }

        return $data;
    }

    /**
     * @return list<string>
     */
    private function backupableTables(): array
    {
        $excluded = array_map(
            strtolower(...),
            (array) config('backup.exclude_tables', []),
        );

        return collect(Schema::getTableListing())
            ->reject(fn (string $table): bool => in_array(strtolower($table), $excluded, true))
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @return array<string, list<string>>
     */
    private function collectFiles(): array
    {
        $collected = [];
        $backupDirectory = trim((string) config('backup.path', 'backups'), '/');

        foreach ((array) config('backup.file_disks', []) as $diskName) {
            if (! is_string($diskName) || $diskName === '') {
                continue;
            }

            try {
                $files = collect(Storage::disk($diskName)->allFiles())
                    ->reject(function (string $path) use ($backupDirectory): bool {
                        return str_starts_with($path, $backupDirectory.'/')
                            || str_ends_with($path, '.gitignore');
                    })
                    ->values()
                    ->all();
            } catch (Throwable) {
                continue;
            }

            $collected[$diskName] = $files;
        }

        return $collected;
    }

    /**
     * @param  array<string, list<string>>  $fileManifest
     */
    private function countCollectedFiles(array $fileManifest): int
    {
        return collect($fileManifest)->sum(fn (array $files): int => count($files));
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @param  array<string, list<array<string, mixed>>>  $tables
     * @param  array<string, list<string>>  $fileManifest
     */
    private function writeArchive(array $manifest, array $tables, array $fileManifest): string
    {
        $tempArchive = tempnam(sys_get_temp_dir(), 'umric-backup-');

        if ($tempArchive === false) {
            throw new InvalidBackupException('Unable to create a temporary backup file.');
        }

        $zipPath = $tempArchive.'.zip';
        @unlink($tempArchive);

        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new InvalidBackupException('Unable to create the backup archive.');
        }

        $zip->addFromString('manifest.json', $this->encodeJson($manifest));
        $zip->addFromString('database.json', $this->encodeJson(['tables' => $tables]));

        foreach ($fileManifest as $diskName => $paths) {
            foreach ($paths as $path) {
                $this->addStoredFileToZip($zip, $diskName, $path);
            }
        }

        $zip->close();

        return $zipPath;
    }

    private function addStoredFileToZip(ZipArchive $zip, string $diskName, string $path): void
    {
        $zipPath = 'files/'.$diskName.'/'.$path;

        try {
            $absolute = Storage::disk($diskName)->path($path);

            if (is_file($absolute)) {
                $zip->addFile($absolute, $zipPath);

                return;
            }
        } catch (Throwable) {
            // Fall through to stream contents for remote disks.
        }

        $contents = Storage::disk($diskName)->get($path);

        if ($contents !== null) {
            $zip->addFromString($zipPath, $contents);
        }
    }

    private function storeArchive(string $filename, string $absolutePath): void
    {
        $disk = $this->archiveDisk();
        $directory = $this->archivePath();

        if (! $disk->exists($directory)) {
            $disk->makeDirectory($directory);
        }

        $stream = fopen($absolutePath, 'rb');

        if ($stream === false) {
            throw new InvalidBackupException('Unable to read the backup archive.');
        }

        try {
            $disk->put($this->archiveRelativePath($filename), $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    /**
     * @return array{tables: array<string, list<array<string, mixed>>>, files: array<string, array<string, string>>}
     */
    private function extractAndValidate(string $absolutePath, string $extractDir, bool $validateOnly = false): array
    {
        if (! is_dir($extractDir) && ! mkdir($extractDir, 0700, true) && ! is_dir($extractDir)) {
            throw new InvalidBackupException('Unable to extract the backup archive.');
        }

        $zip = new ZipArchive;

        if ($zip->open($absolutePath) !== true) {
            $this->deleteDirectory($extractDir);
            throw new InvalidBackupException('The uploaded file is not a valid backup archive.');
        }

        try {
            $this->assertSafeZipEntries($zip);

            if (! $zip->extractTo($extractDir)) {
                throw new InvalidBackupException('Unable to extract the backup archive.');
            }
        } finally {
            $zip->close();
        }

        try {
            $manifestPath = $extractDir.DIRECTORY_SEPARATOR.'manifest.json';
            $databasePath = $extractDir.DIRECTORY_SEPARATOR.'database.json';

            if (! is_file($manifestPath) || ! is_file($databasePath)) {
                throw new InvalidBackupException('The archive is missing required backup files.');
            }

            $manifest = $this->decodeJsonFile($manifestPath);

            if (($manifest['format'] ?? null) !== self::FORMAT) {
                throw new InvalidBackupException('This file is not a recognized UMRIC backup.');
            }

            $database = $this->decodeJsonFile($databasePath);
            $tables = $database['tables'] ?? null;

            if (! is_array($tables)) {
                throw new InvalidBackupException('The backup database dump is invalid.');
            }

            if ($validateOnly) {
                $this->deleteDirectory($extractDir);

                return ['tables' => [], 'files' => []];
            }

            return [
                'tables' => $tables,
                'files' => $this->indexExtractedFiles($extractDir.DIRECTORY_SEPARATOR.'files'),
            ];
        } catch (InvalidBackupException $exception) {
            $this->deleteDirectory($extractDir);
            throw $exception;
        }
    }

    /**
     * @param  array<string, list<array<string, mixed>>>  $tables
     */
    private function restoreTables(array $tables): void
    {
        $excluded = array_map(strtolower(...), (array) config('backup.exclude_tables', []));
        $existingTables = collect(Schema::getTableListing())
            ->map(fn (string $table): string => strtolower($table))
            ->all();

        $ordered = $this->tablesInDependencyOrder($tables, $existingTables, $excluded);

        $this->deleteTablesWithRetry(array_reverse($ordered));
        $this->insertTablesWithRetry($ordered, $tables);
    }

    /**
     * @param  list<string>  $ordered
     * @param  array<string, mixed>  $tables
     */
    private function insertTablesWithRetry(array $ordered, array $tables): void
    {
        $pending = [];

        foreach ($ordered as $table) {
            $rows = $tables[$table] ?? [];

            if (! is_array($rows) || $rows === []) {
                $this->resetPostgresSequence($table);

                continue;
            }

            $pending[$table] = $rows;
        }

        $attempts = count($pending) + 2;

        while ($pending !== [] && $attempts-- > 0) {
            foreach ($pending as $table => $rows) {
                try {
                    $this->insertTableRows($table, $rows);
                    $this->resetPostgresSequence($table);
                    unset($pending[$table]);
                } catch (QueryException $exception) {
                    if (! $this->isForeignKeyFailure($exception)) {
                        throw $exception;
                    }
                }
            }
        }

        if ($pending !== []) {
            throw new InvalidBackupException('Unable to restore tables due to foreign key conflicts: '.implode(', ', array_keys($pending)));
        }
    }

    /**
     * @param  list<string>  $tables
     */
    private function deleteTablesWithRetry(array $tables): void
    {
        $remaining = array_values($tables);
        $attempts = count($remaining) + 2;

        while ($remaining !== [] && $attempts-- > 0) {
            $failed = [];

            foreach ($remaining as $table) {
                try {
                    DB::table($table)->delete();
                } catch (QueryException $exception) {
                    if (! $this->isForeignKeyFailure($exception)) {
                        throw $exception;
                    }

                    $failed[] = $table;
                }
            }

            $remaining = $failed;
        }

        if ($remaining !== []) {
            throw new InvalidBackupException('Unable to clear existing data for restore.');
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function insertTableRows(string $table, array $rows): void
    {
        $columns = Schema::getColumnListing($table);
        $chunks = [];

        foreach (array_chunk($rows, 100) as $chunk) {
            $payload = [];

            foreach ($chunk as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $payload[] = array_intersect_key($row, array_flip($columns));
            }

            if ($payload !== []) {
                DB::table($table)->insert($payload);
            }
        }
    }

    private function isForeignKeyFailure(QueryException $exception): bool
    {
        $sqlState = (string) $exception->getCode();
        $message = strtolower($exception->getMessage());

        return $sqlState === '23000'
            || $sqlState === '23503'
            || str_contains($message, 'foreign key')
            || str_contains($message, 'integrity constraint');
    }

    /**
     * @param  array<string, mixed>  $tables
     * @param  list<string>  $existingTables
     * @param  list<string>  $excluded
     * @return list<string>
     */
    private function tablesInDependencyOrder(array $tables, array $existingTables, array $excluded): array
    {
        $names = collect(array_keys($tables))
            ->filter(fn (mixed $table): bool => is_string($table)
                && in_array(strtolower($table), $existingTables, true)
                && ! in_array(strtolower($table), $excluded, true))
            ->values()
            ->all();

        $dependsOn = [];

        foreach ($names as $table) {
            $parents = [];

            foreach (Schema::getForeignKeys($table) as $foreignKey) {
                $parent = $foreignKey['foreign_table'] ?? null;

                if (! is_string($parent) || $parent === $table || ! in_array($parent, $names, true)) {
                    continue;
                }

                $parents[] = $parent;
            }

            $dependsOn[$table] = array_values(array_unique($parents));
        }

        $sorted = [];
        $visited = [];
        $visiting = [];

        $visit = function (string $table) use (&$visit, &$dependsOn, &$sorted, &$visited, &$visiting): void {
            if (isset($visited[$table])) {
                return;
            }

            if (isset($visiting[$table])) {
                return;
            }

            $visiting[$table] = true;

            foreach ($dependsOn[$table] ?? [] as $parent) {
                $visit($parent);
            }

            unset($visiting[$table]);
            $visited[$table] = true;
            $sorted[] = $table;
        };

        foreach ($names as $table) {
            $visit($table);
        }

        return $sorted;
    }

    private function resetPostgresSequence(string $table): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        if (! in_array('id', Schema::getColumnListing($table), true)) {
            return;
        }

        $sequence = DB::selectOne('select pg_get_serial_sequence(?, ?) as seq', [$table, 'id']);

        if ($sequence === null || ! isset($sequence->seq) || $sequence->seq === null) {
            return;
        }

        $wrappedTable = Schema::getConnection()->getQueryGrammar()->wrapTable($table);

        DB::statement(
            "select setval(?, coalesce((select max(id) from {$wrappedTable}), 1), (select max(id) from {$wrappedTable}) is not null)",
            [$sequence->seq]
        );
    }

    private function assertSafeZipEntries(ZipArchive $zip): void
    {
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);

            if (! is_string($name) || $name === '') {
                throw new InvalidBackupException('The backup archive contains invalid file paths.');
            }

            $normalized = str_replace('\\', '/', $name);

            if (
                str_contains($normalized, '..')
                || str_starts_with($normalized, '/')
                || preg_match('/^[a-zA-Z]:/', $normalized) === 1
            ) {
                throw new InvalidBackupException('The backup archive contains invalid file paths.');
            }
        }
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function indexExtractedFiles(string $filesDir): array
    {
        if (! is_dir($filesDir)) {
            return [];
        }

        $indexed = [];
        $allowedDisks = (array) config('backup.file_disks', []);

        foreach ($allowedDisks as $diskName) {
            if (! is_string($diskName) || $diskName === '') {
                continue;
            }

            $diskDir = $filesDir.DIRECTORY_SEPARATOR.$diskName;

            if (! is_dir($diskDir)) {
                continue;
            }

            $indexed[$diskName] = $this->filesInDirectory($diskDir, $diskDir);
        }

        return $indexed;
    }

    /**
     * @return array<string, string>
     */
    private function filesInDirectory(string $root, string $current): array
    {
        $files = [];
        $entries = scandir($current);

        if ($entries === false) {
            return [];
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $absolute = $current.DIRECTORY_SEPARATOR.$entry;

            if (is_dir($absolute)) {
                $files = [...$files, ...$this->filesInDirectory($root, $absolute)];

                continue;
            }

            $relative = ltrim(str_replace('\\', '/', substr($absolute, strlen($root))), '/');
            $files[$relative] = $absolute;
        }

        return $files;
    }

    /**
     * @param  array<string, array<string, string>>  $files
     */
    private function restoreFiles(array $files): void
    {
        foreach ($files as $diskName => $paths) {
            foreach ($paths as $relative => $absolute) {
                $contents = file_get_contents($absolute);

                if ($contents === false) {
                    continue;
                }

                Storage::disk($diskName)->put($relative, $contents);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonFile(string $path): array
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new InvalidBackupException('Unable to read the backup archive.');
        }

        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            throw new InvalidBackupException('The backup archive contains invalid JSON.');
        }

        if (! is_array($decoded)) {
            throw new InvalidBackupException('The backup archive contains invalid JSON.');
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function encodeJson(array $payload): string
    {
        return json_encode(
            $payload,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE,
        );
    }

    private function archiveDisk(): Filesystem
    {
        return Storage::disk((string) config('backup.disk', 'local'));
    }

    private function archivePath(): string
    {
        return trim((string) config('backup.path', 'backups'), '/');
    }

    private function archiveRelativePath(string $filename): string
    {
        return $this->archivePath().'/'.$filename;
    }

    private function assertZipAvailable(): void
    {
        if (! class_exists(ZipArchive::class)) {
            throw new InvalidBackupException('The PHP zip extension is required for backup and restore.');
        }
    }

    private function deleteDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $entries = scandir($directory);

        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory.DIRECTORY_SEPARATOR.$entry;

            if (is_dir($path)) {
                $this->deleteDirectory($path);

                continue;
            }

            @unlink($path);
        }

        @rmdir($directory);
    }
}
