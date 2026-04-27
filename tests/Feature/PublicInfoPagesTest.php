<?php

test('public info pages return ok', function (string $routeName, string $component) {
    $response = $this->get(route($routeName));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component($component));
})->with([
    ['documentation', 'Documentation'],
    ['faq', 'Faq'],
    ['terms', 'Terms'],
    ['privacy', 'PrivacyPolicy'],
]);
