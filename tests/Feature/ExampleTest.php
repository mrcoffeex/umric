<?php

test('returns a successful response', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});

test('the home page renders the welcome landing', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Welcome')
            ->has('canRegister')
            ->has('stats')
            ->where('branding.logoUrl', '/images/um-digos-college-logo.png'));
});
