<?php

use Illuminate\Support\Facades\Route;

it('renders the custom 404 page for missing web routes', function () {
    config(['app.debug' => false]);

    $this->get('/definitely-missing-page')
        ->assertNotFound()
        ->assertSee('Page Not Found')
        ->assertSee('NegosyoHub');
});

it('renders the custom 500 page for unexpected production errors', function () {
    config(['app.debug' => false]);

    Route::middleware('web')->get('/_test-error-page', function () {
        throw new RuntimeException('Boom');
    });

    $this->get('/_test-error-page')
        ->assertStatus(500)
        ->assertSee('Server Error')
        ->assertSee('NegosyoHub');
});
