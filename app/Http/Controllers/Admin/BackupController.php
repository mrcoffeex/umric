<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\InvalidBackupException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\LogsAdminActions;
use App\Http\Requests\Admin\DeleteBackupRequest;
use App\Http\Requests\Admin\RestoreBackupRequest;
use App\Http\Requests\Admin\RestoreUploadedBackupRequest;
use App\Http\Requests\Admin\StoreBackupRequest;
use App\Http\Requests\Admin\UpdateBackupScheduleRequest;
use App\Services\BackupScheduleService;
use App\Services\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class BackupController extends Controller
{
    use LogsAdminActions;

    public function __construct(
        private BackupService $backups,
        private BackupScheduleService $schedule,
    ) {}

    public function index(): Response
    {
        return Inertia::render('admin/Backups/Index', [
            'backups' => $this->backups->list(),
            'max_upload_megabytes' => (int) ceil(((int) config('backup.max_upload_kilobytes', 262144)) / 1024),
            'retention' => (int) config('backup.keep', 14),
            'schedule' => $this->schedule->inertiaProps(),
        ]);
    }

    public function store(StoreBackupRequest $request): RedirectResponse
    {
        try {
            $backup = $this->backups->create();
        } catch (InvalidBackupException $exception) {
            return back()->withErrors(['backup' => $exception->getMessage()]);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['backup' => 'The backup could not be created. Check the logs and try again.']);
        }

        $this->logAdminAction()
            ->withDescription('Created backup '.$backup['filename'])
            ->withProperties(['filename' => $backup['filename'], 'size' => $backup['size']])
            ->action('backed_up');

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Backup created: '.$backup['filename'],
        ]);

        return redirect()->route('admin.backups.index');
    }

    public function updateSchedule(UpdateBackupScheduleRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->schedule->update([
            'enabled' => (bool) $validated['enabled'],
            'frequency' => $validated['frequency'],
        ]);

        $this->logAdminAction()
            ->withDescription('Updated automatic backup schedule')
            ->withProperties($validated)
            ->action('updated');

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $validated['enabled']
                ? 'Automatic backups will run '.$this->frequencyLabel($validated['frequency']).'. Archives appear in the list below for download.'
                : 'Automatic backups are turned off.',
        ]);

        return redirect()->route('admin.backups.index');
    }

    public function download(string $backup): StreamedResponse
    {
        if (! $this->backups->isValidFilename($backup)) {
            abort(404);
        }

        $disk = Storage::disk((string) config('backup.disk', 'local'));
        $relative = trim((string) config('backup.path', 'backups'), '/').'/'.$backup;

        if (! $disk->exists($relative)) {
            abort(404);
        }

        return $disk->download($relative, $backup);
    }

    public function restore(RestoreBackupRequest $request, string $backup): RedirectResponse
    {
        try {
            $this->backups->restoreFromStored($backup);
        } catch (InvalidBackupException $exception) {
            return back()->withErrors(['backup' => $exception->getMessage()]);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['backup' => 'The restore failed. The current data may be incomplete. Check the logs.']);
        }

        $this->logAdminAction()
            ->withDescription('Restored backup '.$backup)
            ->withProperties(['filename' => $backup])
            ->action('restored');

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'System restored from '.$backup.'.',
        ]);

        return redirect()->route('admin.backups.index');
    }

    public function restoreUpload(RestoreUploadedBackupRequest $request): RedirectResponse
    {
        $file = $request->file('file');
        $path = $file?->getRealPath();

        if (! is_string($path) || $path === '') {
            return back()->withErrors(['file' => 'Choose a backup archive to restore.']);
        }

        try {
            $filename = $this->backups->storeUploadedArchive($path);
            $this->backups->restoreFromStored($filename);
        } catch (InvalidBackupException $exception) {
            return back()->withErrors(['file' => $exception->getMessage()]);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['file' => 'The restore failed. The current data may be incomplete. Check the logs.']);
        }

        $this->logAdminAction()
            ->withDescription('Restored uploaded backup '.$filename)
            ->withProperties(['filename' => $filename])
            ->action('restored');

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'System restored from the uploaded backup.',
        ]);

        return redirect()->route('admin.backups.index');
    }

    public function destroy(DeleteBackupRequest $request, string $backup): RedirectResponse
    {
        try {
            $this->backups->delete($backup);
        } catch (InvalidBackupException $exception) {
            return back()->withErrors(['backup' => $exception->getMessage()]);
        }

        $this->logAdminAction()
            ->withDescription('Deleted backup '.$backup)
            ->withProperties(['filename' => $backup])
            ->action('deleted');

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Backup deleted.',
        ]);

        return redirect()->route('admin.backups.index');
    }

    private function frequencyLabel(string $frequency): string
    {
        return match ($frequency) {
            BackupScheduleService::FREQUENCY_HOURLY => 'every hour',
            BackupScheduleService::FREQUENCY_WEEKLY => 'every week',
            BackupScheduleService::FREQUENCY_MONTHLY => 'every month',
            default => 'every day',
        };
    }
}
