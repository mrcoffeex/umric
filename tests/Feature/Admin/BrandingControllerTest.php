<?php

use App\Models\AppBranding;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
});

function makeBrandingUser(string $role): User
{
    $user = User::factory()->create();
    UserProfile::factory()->{$role}()->create(['user_id' => $user->id]);

    return $user;
}

it('redirects guests from branding index', function () {
    $this->get(route('admin.branding.index'))
        ->assertRedirect(route('login'));
});

it('denies students access to branding index', function () {
    $student = makeBrandingUser('student');

    $this->actingAs($student)
        ->get(route('admin.branding.index'))
        ->assertForbidden();
});

it('allows staff to view branding index', function () {
    $staff = makeBrandingUser('staff');

    $this->actingAs($staff)
        ->get(route('admin.branding.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Branding/Index')
            ->has('branding.site_name')
        );
});

it('rejects whitespace-only site name', function () {
    $admin = makeBrandingUser('admin');

    $this->actingAs($admin)
        ->put(route('admin.branding.update'), [
            'site_name' => '   ',
            'tagline' => 'ok',
            'remove_logo' => false,
        ])
        ->assertSessionHasErrors('site_name');
});

it('rejects site name over 100 characters', function () {
    $admin = makeBrandingUser('admin');
    $long = str_repeat('a', 101);

    $this->actingAs($admin)
        ->put(route('admin.branding.update'), [
            'site_name' => $long,
            'tagline' => null,
            'remove_logo' => false,
        ])
        ->assertSessionHasErrors('site_name');
});

it('trims site name and tagline on update', function () {
    $admin = makeBrandingUser('admin');

    $this->actingAs($admin)
        ->put(route('admin.branding.update'), [
            'site_name' => '  My Site  ',
            'tagline' => '  A tagline  ',
            'remove_logo' => false,
        ])
        ->assertRedirect(route('admin.branding.index'));

    $row = AppBranding::query()->first();
    expect($row->site_name)->toBe('My Site');
    expect($row->tagline)->toBe('A tagline');
});

it('allows admin to update branding text', function () {
    $admin = makeBrandingUser('admin');

    $this->actingAs($admin)
        ->put(route('admin.branding.update'), [
            'site_name' => 'Test Institute',
            'tagline' => 'Research first',
            'remove_logo' => false,
        ])
        ->assertRedirect(route('admin.branding.index'));

    expect(AppBranding::query()->first()->site_name)->toBe('Test Institute');
    expect(AppBranding::query()->first()->tagline)->toBe('Research first');
});

it('allows admin to upload and clear logo', function () {
    Storage::fake('public');
    $admin = makeBrandingUser('admin');

    $file = UploadedFile::fake()->image('logo.png', 64, 64);

    $this->actingAs($admin)
        ->put(route('admin.branding.update'), [
            'site_name' => 'With Logo',
            'tagline' => null,
            'logo' => $file,
        ]);

    $path = AppBranding::query()->first()->logo_path;
    expect($path)->not->toBeNull();
    Storage::disk('public')->assertExists($path);

    $this->actingAs($admin)
        ->put(route('admin.branding.update'), [
            'site_name' => 'With Logo',
            'tagline' => null,
            'remove_logo' => true,
        ])
        ->assertRedirect(route('admin.branding.index'));

    expect(AppBranding::query()->first()->logo_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});
