<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $this->withoutVite();

    $this->get(route('register'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/Register')
        );
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'student',
        'terms_accepted' => '1',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
    expect(User::where('email', 'test@example.com')->first()?->role())->toBe('student');
});

test('new faculty can register and wait for approval', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Faculty User',
        'email' => 'faculty@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'faculty',
        'terms_accepted' => '1',
    ]);

    $this->assertGuest();
    $response->assertRedirect(route('registration.pending', absolute: false));
    expect(User::where('email', 'faculty@example.com')->first()?->role())->toBe('faculty');
});

test('registration requires terms acceptance', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'nterms@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'student',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors('terms_accepted');
});
