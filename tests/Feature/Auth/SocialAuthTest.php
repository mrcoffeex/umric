<?php

use App\Models\User;
use App\Models\UserProfile;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

function fakeGoogleUser(string $email = 'google@example.com', string $id = 'google-123'): SocialiteUser
{
    return (new SocialiteUser)->map([
        'id' => $id,
        'nickname' => 'google-user',
        'name' => 'Google User',
        'email' => $email,
        'avatar' => null,
        'avatar_original' => null,
    ]);
}

test('google login redirect stores login intent', function () {
    Socialite::fake('google');

    $this->get(route('auth.google'))
        ->assertRedirect();

    expect(session('oauth_intent'))->toBe('login')
        ->and(session()->has('oauth_role'))->toBeFalse();
});

test('google register redirect stores register intent and role', function () {
    Socialite::fake('google');

    $this->get(route('auth.google', ['role' => 'faculty']))
        ->assertRedirect();

    expect(session('oauth_intent'))->toBe('register')
        ->and(session('oauth_role'))->toBe('faculty');
});

test('google login rejects unregistered emails', function () {
    Socialite::fake('google', fakeGoogleUser());

    $this->withSession(['oauth_intent' => 'login'])
        ->get(route('auth.google.callback'))
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
    expect(User::where('email', 'google@example.com')->exists())->toBeFalse();
});

test('google login authenticates existing registered users', function () {
    $user = User::factory()->create(['email' => 'google@example.com']);
    UserProfile::factory()->student()->create(['user_id' => $user->id]);

    Socialite::fake('google', fakeGoogleUser($user->email));

    $this->withSession(['oauth_intent' => 'login'])
        ->get(route('auth.google.callback'))
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user->fresh());
    expect($user->fresh()->google_id)->toBe('google-123');
});

test('google login does not restore soft-deleted accounts', function () {
    $user = User::factory()->create(['email' => 'deleted@example.com']);
    UserProfile::factory()->student()->create(['user_id' => $user->id]);
    $user->delete();

    Socialite::fake('google', fakeGoogleUser('deleted@example.com'));

    $this->withSession(['oauth_intent' => 'login'])
        ->get(route('auth.google.callback'))
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('google registration creates a new student account', function () {
    Socialite::fake('google', fakeGoogleUser('new-student@example.com'));

    $this->withSession([
        'oauth_intent' => 'register',
        'oauth_role' => 'student',
    ])
        ->get(route('auth.google.callback'))
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();

    $user = User::where('email', 'new-student@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->role())->toBe('student')
        ->and($user->google_id)->toBe('google-123');
});

test('google registration creates a faculty account pending approval', function () {
    Socialite::fake('google', fakeGoogleUser('new-faculty@example.com'));

    $this->withSession([
        'oauth_intent' => 'register',
        'oauth_role' => 'faculty',
    ])
        ->get(route('auth.google.callback'))
        ->assertRedirect(route('registration.pending', absolute: false));

    $this->assertGuest();

    $user = User::where('email', 'new-faculty@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->role())->toBe('faculty')
        ->and($user->isApproved())->toBeFalse();
});

test('google registration does not change an existing user role', function () {
    $user = User::factory()->create(['email' => 'existing@example.com']);
    UserProfile::factory()->student()->create(['user_id' => $user->id]);

    Socialite::fake('google', fakeGoogleUser($user->email));

    $this->withSession([
        'oauth_intent' => 'register',
        'oauth_role' => 'faculty',
    ])
        ->get(route('auth.google.callback'))
        ->assertRedirect(route('dashboard', absolute: false));

    expect($user->fresh()->role())->toBe('student');
});
