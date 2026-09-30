<?php

use Illuminate\Support\Env;

beforeEach(fn () => seedSite());

it('sends the session cookie HttpOnly, SameSite=Lax, and Secure when the site is on https (E-2)', function () {
    config(['session.secure' => true]);

    $response = $this->get('https://example.test/')->assertOk();
    $cookie = collect($response->headers->getCookies())->first(fn ($c) => $c->getName() === config('session.cookie'));

    expect($cookie)->not->toBeNull()
        ->and($cookie->isHttpOnly())->toBeTrue()
        ->and($cookie->getSameSite())->toBe('lax')
        ->and($cookie->isSecure())->toBeTrue();

    expect(config('session.http_only'))->toBeTrue()->and(config('session.same_site'))->toBe('lax');
});

it('makes the session cookie https-only with the single FORCE_HTTPS switch, unless told otherwise (E-2)', function () {
    $secure = function (?string $force, ?string $cookie): mixed {
        foreach (['FORCE_HTTPS' => $force, 'SESSION_SECURE_COOKIE' => $cookie] as $name => $value) {
            Env::getRepository()->clear($name);

            if ($value !== null) {
                Env::getRepository()->set($name, $value);
            }
        }

        $config = require base_path('config/session.php');

        Env::getRepository()->clear('FORCE_HTTPS');
        Env::getRepository()->clear('SESSION_SECURE_COOKIE');

        return $config['secure'];
    };

    expect($secure(null, null))->toBeFalse()          // before SSL exists: a secure cookie would never be stored
        ->and($secure('true', null))->toBeTrue()      // after SSL: one switch
        ->and($secure('true', 'false'))->toBeFalse()  // explicit setting wins
        ->and($secure('false', 'true'))->toBeTrue();
});

it('keeps the production template on shared-hosting-safe drivers with safe defaults', function () {
    $env = (string) file_get_contents(base_path('.env.production.example'));
    $line = fn (string $key): ?string => preg_match('/^'.preg_quote($key, '/').'=(.*)$/m', $env, $m) ? trim($m[1]) : null;

    expect($line('APP_ENV'))->toBe('production')
        ->and($line('APP_DEBUG'))->toBe('false')
        ->and($line('APP_URL'))->toStartWith('https://')
        ->and($line('LOG_LEVEL'))->toBe('error')
        ->and($line('SESSION_DRIVER'))->toBe('database')
        ->and($line('CACHE_STORE'))->toBe('database')
        ->and($line('QUEUE_CONNECTION'))->toBe('sync')
        ->and($line('MAIL_MAILER'))->toBe('smtp')
        ->and($line('SESSION_LIFETIME'))->toBe('60')
        ->and($line('SESSION_HTTP_ONLY'))->toBe('true')
        ->and($line('SESSION_SAME_SITE'))->toBe('lax')
        // Off until SSL exists; switched on deliberately afterwards (DEPLOY.md).
        ->and($line('FORCE_HTTPS'))->toBe('false')
        ->and($line('TURNSTILE_ENABLED'))->toBe('false')
        ->and($line('TRUSTED_PROXIES'))->toBe('')
        ->and($line('SESSION_SECURE_COOKIE'))->toBeNull()
        ->and($env)->toContain('# SESSION_SECURE_COOKIE=')
        // The first administrator is created on the server, never from a password in .env.
        ->and($env)->not->toContain('ADMIN_PASSWORD')
        ->and($env)->toContain('admin:create');
});

it('ships no development-only tooling to the server: tinker is a dev dependency (I-2)', function () {
    $composer = json_decode((string) file_get_contents(base_path('composer.json')), true);
    $lock = json_decode((string) file_get_contents(base_path('composer.lock')), true);
    $names = fn (array $packages): array => array_column($packages, 'name');

    expect($composer['require'])->not->toHaveKey('laravel/tinker')
        ->and($composer['require-dev'])->toHaveKey('laravel/tinker')
        ->and($names($lock['packages']))->not->toContain('laravel/tinker')
        ->and($names($lock['packages-dev']))->toContain('laravel/tinker');
});

it('has composer.lock and package-lock.json committed and in step with their manifests', function () {
    expect(file_exists(base_path('composer.lock')))->toBeTrue()
        ->and(file_exists(base_path('package-lock.json')))->toBeTrue();

    $composer = json_decode((string) file_get_contents(base_path('composer.json')), true);
    $lock = json_decode((string) file_get_contents(base_path('composer.lock')), true);

    // The lock's hash is of composer.json's relevant keys: a stale lock would be reported by `composer validate`.
    expect($lock['content-hash'])->toMatch('/^[a-f0-9]{32}$/')->and($composer['name'])->toBe('ekasa/website');
});
