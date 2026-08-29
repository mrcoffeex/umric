<?php

use App\Models\Department;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\BackupScheduleService;
use App\Services\BackupService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    Storage::fake('local');
    Storage::fake('public');
});

function makeBackupUser(string $role): User
{
    $user = User::factory()->create();
    UserProfile::factory()->{$role}()->create(['user_id' => $user->id]);

    return $user;
}

it('redirects guests from the backup index', function () {
    $this->get(route('admin.backups.index'))
        ->assertRedirect(route('login'));
});

it('denies students access to backups', function () {
    $this->actingAs(makeBackupUser('student'))
        ->get(route('admin.backups.index'))
        ->assertForbidden();
});

it('denies staff access to backups', function () {
    $this->actingAs(makeBackupUser('staff'))
        ->get(route('admin.backups.index'))
        ->assertForbidden();
});

it('allows admins to view the backup index', function () {
    $this->actingAs(makeBackupUser('admin'))
        ->get(route('admin.backups.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Backups/Index')
            ->has('backups')
            ->has('retention')
            ->has('max_upload_megabytes')
            ->has('schedule.frequency')
            ->has('schedule.frequencies')
        );
});

it('allows an admin to create a backup that includes data and files', function () {
    $admin = makeBackupUser('admin');
    $department = Department::factory()->create(['name' => 'College of Science']);
    Storage::disk('public')->put('brand/logo-test.png', 'logo-bytes');

    $this->actingAs($admin)
        ->post(route('admin.backups.store'))
        ->assertRedirect(route('admin.backups.index'));

    $backups = app(BackupService::class)->list();
    expect($backups)->toHaveCount(1);

    $absolute = app(BackupService::class)->archiveAbsolutePath($backups[0]['filename']);
    $zip = new ZipArchive;
    expect($zip->open($absolute))->toBeTrue();
    expect($zip->getFromName('manifest.json'))->toContain(BackupService::FORMAT);
    expect($zip->getFromName('database.json'))->toContain($department->name);
    expect($zip->getFromName('files/public/brand/logo-test.png'))->toBe('logo-bytes');
    $zip->close();
});

it('restores a stored backup and its files', function () {
    $admin = makeBackupUser('admin');
    $department = Department::factory()->create(['name' => 'Original Name']);
    Storage::disk('public')->put('papers/sample.pdf', 'pdf-bytes');

    $this->actingAs($admin)->post(route('admin.backups.store'));

    $filename = app(BackupService::class)->list()[0]['filename'];

    $department->update(['name' => 'Changed Name']);
    Storage::disk('public')->delete('papers/sample.pdf');

    $this->actingAs($admin)
        ->post(route('admin.backups.restore', $filename), [
            'password' => 'password',
            'confirmation' => '1',
        ])
        ->assertRedirect(route('admin.backups.index'));

    expect($department->fresh()->name)->toBe('Original Name');
    Storage::disk('public')->assertExists('papers/sample.pdf');
    expect(Storage::disk('public')->get('papers/sample.pdf'))->toBe('pdf-bytes');
});

it('restores from an uploaded backup archive', function () {
    $admin = makeBackupUser('admin');
    $department = Department::factory()->create(['name' => 'Uploaded Original']);

    $this->actingAs($admin)->post(route('admin.backups.store'));
    $filename = app(BackupService::class)->list()[0]['filename'];
    $absolute = app(BackupService::class)->archiveAbsolutePath($filename);

    $department->update(['name' => 'Should Be Reverted']);

    $upload = new UploadedFile($absolute, $filename, 'application/zip', test: true);

    $this->actingAs($admin)
        ->post(route('admin.backups.restore-upload'), [
            'password' => 'password',
            'confirmation' => '1',
            'file' => $upload,
        ])
        ->assertRedirect(route('admin.backups.index'));

    expect($department->fresh()->name)->toBe('Uploaded Original');
});

it('requires the current password and confirmation to restore', function () {
    $admin = makeBackupUser('admin');

    $this->actingAs($admin)->post(route('admin.backups.store'));
    $filename = app(BackupService::class)->list()[0]['filename'];

    $this->actingAs($admin)
        ->post(route('admin.backups.restore', $filename), [
            'password' => 'wrong-password',
            'confirmation' => '1',
        ])
        ->assertSessionHasErrors('password');

    $this->actingAs($admin)
        ->post(route('admin.backups.restore', $filename), [
            'password' => 'password',
        ])
        ->assertSessionHasErrors('confirmation');
});

it('rejects an invalid uploaded archive', function () {
    $admin = makeBackupUser('admin');
    $file = UploadedFile::fake()->create('not-a-backup.zip', 12, 'application/zip');

    $this->actingAs($admin)
        ->post(route('admin.backups.restore-upload'), [
            'password' => 'password',
            'confirmation' => '1',
            'file' => $file,
        ])
        ->assertSessionHasErrors('file');
});

it('lets an admin download and delete a backup', function () {
    $admin = makeBackupUser('admin');

    $this->actingAs($admin)->post(route('admin.backups.store'));
    $filename = app(BackupService::class)->list()[0]['filename'];

    $this->actingAs($admin)
        ->get(route('admin.backups.download', $filename))
        ->assertSuccessful()
        ->assertHeader('content-disposition');

    $this->actingAs($admin)
        ->delete(route('admin.backups.destroy', $filename))
        ->assertRedirect(route('admin.backups.index'));

    expect(app(BackupService::class)->list())->toHaveCount(0);
});

it('returns 404 when downloading a missing backup', function () {
    $this->actingAs(makeBackupUser('admin'))
        ->get(route('admin.backups.download', 'umric-backup-2026-01-01-000000.zip'))
        ->assertNotFound();
});

it('creates a backup from the artisan command', function () {
    makeBackupUser('admin');

    $this->artisan('backup:create')
        ->assertSuccessful();

    expect(app(BackupService::class)->list())->toHaveCount(1);
    expect(app(BackupScheduleService::class)->lastRanAt())->not->toBeNull();
});

it('allows an admin to update the automatic backup schedule', function () {
    $admin = makeBackupUser('admin');

    $this->actingAs($admin)
        ->put(route('admin.backups.schedule'), [
            'enabled' => true,
            'frequency' => 'weekly',
        ])
        ->assertRedirect(route('admin.backups.index'));

    $schedule = app(BackupScheduleService::class);
    expect($schedule->enabled())->toBeTrue();
    expect($schedule->frequency())->toBe('weekly');
});

it('rejects an invalid backup frequency', function () {
    $this->actingAs(makeBackupUser('admin'))
        ->put(route('admin.backups.schedule'), [
            'enabled' => true,
            'frequency' => 'yearly',
        ])
        ->assertSessionHasErrors('frequency');
});

it('denies staff from updating the backup schedule', function () {
    $this->actingAs(makeBackupUser('staff'))
        ->put(route('admin.backups.schedule'), [
            'enabled' => true,
            'frequency' => 'daily',
        ])
        ->assertForbidden();
});

it('does not run scheduled backups when they are disabled', function () {
    app(BackupScheduleService::class)->update([
        'enabled' => false,
        'frequency' => 'hourly',
    ]);

    expect(app(BackupScheduleService::class)->shouldRun(now()))->toBeFalse();
});

it('runs a daily scheduled backup at 2am only', function () {
    $schedule = app(BackupScheduleService::class);
    $schedule->update([
        'enabled' => true,
        'frequency' => 'daily',
    ]);

    expect($schedule->shouldRun(Carbon::parse('2026-08-29 02:15:00')))->toBeTrue();
    expect($schedule->shouldRun(Carbon::parse('2026-08-29 14:00:00')))->toBeFalse();

    $schedule->markRan(Carbon::parse('2026-08-29 02:00:00'));

    expect($schedule->shouldRun(Carbon::parse('2026-08-29 02:30:00')))->toBeFalse();
    expect($schedule->shouldRun(Carbon::parse('2026-08-30 02:00:00')))->toBeTrue();
});

it('runs an hourly scheduled backup after an hour has passed', function () {
    $schedule = app(BackupScheduleService::class);
    $schedule->update([
        'enabled' => true,
        'frequency' => 'hourly',
    ]);

    expect($schedule->shouldRun(Carbon::parse('2026-08-29 10:00:00')))->toBeTrue();

    $schedule->markRan(Carbon::parse('2026-08-29 10:00:00'));

    expect($schedule->shouldRun(Carbon::parse('2026-08-29 10:30:00')))->toBeFalse();
    expect($schedule->shouldRun(Carbon::parse('2026-08-29 11:00:00')))->toBeTrue();
});

it('runs a weekly scheduled backup on sunday at 2am', function () {
    $schedule = app(BackupScheduleService::class);
    $schedule->update([
        'enabled' => true,
        'frequency' => 'weekly',
    ]);

    expect($schedule->shouldRun(Carbon::parse('2026-08-30 02:00:00')))->toBeTrue();
    expect($schedule->shouldRun(Carbon::parse('2026-08-29 02:00:00')))->toBeFalse();

    $schedule->markRan(Carbon::parse('2026-08-30 02:00:00'));

    expect($schedule->shouldRun(Carbon::parse('2026-08-30 02:15:00')))->toBeFalse();
    expect($schedule->shouldRun(Carbon::parse('2026-09-06 02:00:00')))->toBeTrue();
});

it('runs a monthly scheduled backup on the first at 2am', function () {
    $schedule = app(BackupScheduleService::class);
    $schedule->update([
        'enabled' => true,
        'frequency' => 'monthly',
    ]);

    expect($schedule->shouldRun(Carbon::parse('2026-09-01 02:00:00')))->toBeTrue();
    expect($schedule->shouldRun(Carbon::parse('2026-09-02 02:00:00')))->toBeFalse();

    $schedule->markRan(Carbon::parse('2026-09-01 02:00:00'));

    expect($schedule->shouldRun(Carbon::parse('2026-09-01 02:30:00')))->toBeFalse();
    expect($schedule->shouldRun(Carbon::parse('2026-10-01 02:00:00')))->toBeTrue();
});
