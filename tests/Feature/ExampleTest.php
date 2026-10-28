<?php

use Inertia\Testing\AssertableInertia as Assert;

test('the application returns a successful response', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Login')
        ->has('laravelVersion')
        ->has('phpVersion')
    );
});
