<?php

beforeEach(function () {
    seedSite();
    app()['env'] = 'production';
});

it('redirects www to the non-www host from APP_URL with a 301', function () {
    config(['app.url' => 'https://example.co.id']);

    $this->get('https://www.example.co.id/?utm=1')
        ->assertStatus(301)
        ->assertRedirect('https://example.co.id/?utm=1');
});

it('redirects http to https', function () {
    config(['app.url' => 'https://example.co.id']);

    $this->get('http://example.co.id/robots.txt')
        ->assertStatus(301)
        ->assertRedirect('https://example.co.id/robots.txt');
});

it('follows APP_URL when the canonical host is www', function () {
    config(['app.url' => 'https://www.example.co.id']);

    $this->get('https://example.co.id/')->assertRedirect('https://www.example.co.id/');
    $this->get('https://www.example.co.id/')->assertOk();
});

it('does not redirect outside production', function () {
    app()['env'] = 'local';
    config(['app.url' => 'https://example.co.id']);

    $this->get('http://www.example.co.id/')->assertOk();
});
