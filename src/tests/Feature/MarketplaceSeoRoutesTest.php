<?php

use App\Models\GlobalSeoSetting;
use App\Models\LegalPage;
use App\Models\Store;

it('serves a marketplace sitemap with public routes and content', function () {
    LegalPage::factory()->published()->create([
        'slug' => 'terms-of-service',
    ]);

    Store::factory()->create([
        'slug' => 'trusted-seller',
    ]);

    $this->get(route('sitemap'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml')
        ->assertSee(route('home'), false)
        ->assertSee(route('register.sector'), false)
        ->assertSee(route('legal.show', 'terms-of-service'), false)
        ->assertSee(route('suppliers.show', 'trusted-seller'), false);
});

it('adds a sitemap line to the marketplace robots response', function () {
    GlobalSeoSetting::current()->update([
        'robots_txt_content' => "User-agent: *\nAllow: /\nDisallow: /moon",
    ]);

    $this->get(route('robots'))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSee('Disallow: /moon')
        ->assertSee('Sitemap: '.route('sitemap'));
});

it('omits the sitemap line from robots when the sitemap is disabled', function () {
    GlobalSeoSetting::current()->update([
        'robots_txt_content' => "User-agent: *\nAllow: /\nDisallow: /moon",
        'sitemap_enabled' => false,
    ]);

    $this->get(route('robots'))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSee('Disallow: /moon')
        ->assertDontSee('Sitemap:');
});
