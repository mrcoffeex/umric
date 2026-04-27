<?php

use App\Models\User;
use App\Models\UserProfile;

test('user profile stores role correctly', function () {
    $user = User::factory()->create();
    UserProfile::factory()->faculty()->create(['user_id' => $user->id]);

    expect($user->fresh()->role())->toBe('faculty');
});

test('user defaults to student role when no profile', function () {
    $user = User::factory()->create();

    expect($user->role())->toBe('student');
});

test('user role helper methods work', function () {
    $user = User::factory()->create();
    UserProfile::factory()->admin()->create(['user_id' => $user->id]);

    expect($user->fresh()->isAdmin())->toBeTrue()
        ->and($user->fresh()->isStudent())->toBeFalse()
        ->and($user->fresh()->isFaculty())->toBeFalse()
        ->and($user->fresh()->isStaff())->toBeFalse();
});

test('user hasRole method works with multiple roles', function () {
    $user = User::factory()->create();
    UserProfile::factory()->staff()->create(['user_id' => $user->id]);

    expect($user->fresh()->hasRole('staff', 'admin'))->toBeTrue()
        ->and($user->fresh()->hasRole('student', 'faculty'))->toBeFalse();
});

test('role middleware blocks unauthorized roles', function () {
    $user = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('student.home'));
});

test('registration creates user profile with role', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Faculty User',
        'email' => 'faculty@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'faculty',
        'terms_accepted' => '1',
    ]);

    // Faculty users are logged out after registration and sent to the pending page
    $this->assertGuest();
    $response->assertRedirect(route('registration.pending'));

    $user = User::where('email', 'faculty@example.com')->first();
    expect($user->profile)->not->toBeNull()
        ->and($user->role())->toBe('faculty');
});

test('registration defaults to student role when no role provided', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Student User',
        'email' => 'student@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms_accepted' => '1',
    ]);

    $this->assertAuthenticated();

    $user = User::where('email', 'student@example.com')->first();
    expect($user->profile)->not->toBeNull()
        ->and($user->role())->toBe('student');
});

test('registration rejects invalid role', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Bad User',
        'email' => 'bad@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'admin',
        'terms_accepted' => '1',
    ]);

    $response->assertSessionHasErrors('role');
    $this->assertGuest();
});
