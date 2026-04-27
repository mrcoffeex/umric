<?php

use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms_accepted' => '1',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('registration requires terms acceptance', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'nterms@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors('terms_accepted');
});
