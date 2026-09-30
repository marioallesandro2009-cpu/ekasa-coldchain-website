<?php

use App\Support\CloudflareIps;
use App\Support\TrustedProxies;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

beforeEach(fn () => seedSite());

afterEach(function () {
    TrustProxies::flushState();
    @unlink(CloudflareIps::path());
});

it('sends the security headers on every public page (G-1)', function () {
    $response = $this->get('/')->assertOk();

    expect($response->headers->get('X-Frame-Options'))->toBe('SAMEORIGIN')
        ->and($response->headers->get('X-Content-Type-Options'))->toBe('nosniff')
        ->and($response->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin')
        ->and($response->headers->get('Permissions-Policy'))->toContain('camera=()')->toContain('geolocation=()')
        ->and($response->headers->get('Cross-Origin-Opener-Policy'))->toBe('same-origin')
        ->and($response->headers->has('X-Powered-By'))->toBeFalse();
});

it('sends the CSP as report-only by default, with a per-request nonce that the scripts carry', function () {
    $response = $this->get('/')->assertOk();

    $csp = (string) $response->headers->get('Content-Security-Policy-Report-Only');
    preg_match("/'nonce-([A-Za-z0-9+\/=_-]+)'/", $csp, $header);
    $nonce = $header[1] ?? '';

    expect($response->headers->has('Content-Security-Policy'))->toBeFalse()
        ->and($csp)->toContain("default-src 'self'")->toContain("object-src 'none'")->toContain("frame-ancestors 'self'")
        ->toContain("'unsafe-eval'")->toContain('https://tile.openstreetmap.org')
        ->not->toContain("script-src 'self' 'unsafe-inline'")
        ->and($nonce)->not->toBe('');

    // The inline "js" flag script and the Vite bundle both carry this request's nonce.
    $html = (string) $response->getContent();
    expect(substr_count($html, 'nonce="'.$nonce.'"'))->toBeGreaterThanOrEqual(2);

    // A new nonce on the next request.
    preg_match("/'nonce-([A-Za-z0-9+\/=_-]+)'/", (string) $this->get('/')->headers->get('Content-Security-Policy-Report-Only'), $next);
    expect($next[1])->not->toBe($nonce);
});

it('can enforce or switch off the CSP from .env, and leaves the admin out', function () {
    config(['security.csp.mode' => 'enforce']);
    $enforced = $this->get('/');
    expect($enforced->headers->has('Content-Security-Policy'))->toBeTrue()
        ->and($enforced->headers->has('Content-Security-Policy-Report-Only'))->toBeFalse();

    config(['security.csp.mode' => 'off']);
    $off = $this->get('/');
    expect($off->headers->has('Content-Security-Policy'))->toBeFalse()
        ->and($off->headers->has('Content-Security-Policy-Report-Only'))->toBeFalse();

    config(['security.csp.mode' => 'enforce']);
    $admin = $this->get('/admin/login')->assertOk();
    expect($admin->headers->has('Content-Security-Policy'))->toBeFalse()
        ->and($admin->headers->get('X-Frame-Options'))->toBe('SAMEORIGIN');
});

it('allows Turnstile in the CSP only when it is switched on', function () {
    expect((string) $this->get('/')->headers->get('Content-Security-Policy-Report-Only'))->not->toContain('challenges.cloudflare.com');

    config(['security.turnstile.enabled' => true]);
    $csp = (string) $this->get('/')->headers->get('Content-Security-Policy-Report-Only');

    expect($csp)->toContain('script-src')->toContain('https://challenges.cloudflare.com')
        ->toContain('frame-src https://challenges.cloudflare.com');
});

it('sends HSTS only over https and only when HSTS_MAX_AGE is above zero, never with preload (G-1)', function () {
    config(['security.hsts.max_age' => 0]);
    expect($this->get('https://example.test/')->headers->has('Strict-Transport-Security'))->toBeFalse();

    config(['security.hsts.max_age' => 300]);
    expect($this->get('http://example.test/')->headers->has('Strict-Transport-Security'))->toBeFalse()
        ->and($this->get('https://example.test/')->headers->get('Strict-Transport-Security'))->toBe('max-age=300');

    config(['security.hsts.include_subdomains' => true]);
    $value = (string) $this->get('https://example.test/')->headers->get('Strict-Transport-Security');
    expect($value)->toBe('max-age=300; includeSubDomains')->not->toContain('preload');
});

it('does not force https unless FORCE_HTTPS is on, but always keeps the canonical host (G-2)', function () {
    app()['env'] = 'production';
    config(['app.url' => 'https://example.co.id', 'security.force_https' => false]);

    // SSL not installed yet: plain http keeps working...
    $this->get('http://example.co.id/')->assertOk();
    // ...and a wrong host still goes to the canonical one, on the same scheme the visitor used.
    $this->get('http://www.example.co.id/x')->assertStatus(301)->assertRedirect('http://example.co.id/x');

    config(['security.force_https' => true]);
    $this->get('http://example.co.id/x')->assertStatus(301)->assertRedirect('https://example.co.id/x');
    expect((string) $this->get('https://example.co.id/')->headers->get('Content-Security-Policy-Report-Only'))->toContain('upgrade-insecure-requests');
});

it('has no fixed https redirect left in .htaccess, and blocks hidden and sensitive files (G-2, G-4)', function () {
    $htaccess = (string) file_get_contents(public_path('.htaccess'));

    expect($htaccess)->not->toMatch('/RewriteRule\s+\^\s+https:/')
        ->toContain('Require all denied')
        ->toContain('composer')
        ->toContain('well-known')
        ->toContain('Options -MultiViews -Indexes');
});

it('resolves TRUSTED_PROXIES: nobody by default, Cloudflare ranges, a list, or everyone (G-3)', function () {
    expect(TrustedProxies::resolve(''))->toBeNull()
        ->and(TrustedProxies::resolve(null))->toBeNull()
        ->and(TrustedProxies::resolve('*'))->toBe('*')
        ->and(TrustedProxies::resolve('10.0.0.5, 192.168.0.0/16'))->toBe(['10.0.0.5', '192.168.0.0/16'])
        ->and(TrustedProxies::resolve('cloudflare'))->toContain('173.245.48.0/20')->toContain('2606:4700::/32');

    $both = TrustedProxies::resolve('cloudflare,10.0.0.5');
    expect($both)->toContain('10.0.0.5')->toContain('104.16.0.0/13');
});

it('reads the visitor IP from X-Forwarded-For only for trusted proxies (G-3)', function () {
    Route::middleware('web')->get('/_ip', fn (Request $request) => $request->ip());

    $call = fn () => $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
        ->withHeaders(['X-Forwarded-For' => '198.51.100.9'])
        ->get('/_ip')->getContent();

    // Nothing trusted (default): the proxy's address, so a forged header changes nothing.
    expect($call())->toBe('203.0.113.7');

    TrustProxies::at(TrustedProxies::resolve('203.0.113.0/24'));
    expect($call())->toBe('198.51.100.9');
});

it('refreshes the Cloudflare ranges from Cloudflare', function () {
    Http::fake([
        'www.cloudflare.com/ips-v4' => Http::response("173.245.48.0/20\n103.21.244.0/22\n103.22.200.0/22\n103.31.4.0/22\n141.101.64.0/18\n108.162.192.0/18\n", 200),
        'www.cloudflare.com/ips-v6' => Http::response("2400:cb00::/32\n2606:4700::/32\n2803:f800::/32\n2405:b500::/32\n", 200),
    ]);

    $this->artisan('security:cloudflare-ips')->assertSuccessful();

    expect(CloudflareIps::ranges())->toContain('2803:f800::/32')->toHaveCount(10);
});

it('refuses a Cloudflare answer that does not look like the IP list and keeps the bundled ranges', function () {
    Http::fake(['*' => Http::response('<html>blocked</html>', 200)]);

    $this->artisan('security:cloudflare-ips')->assertFailed();

    expect(is_file(CloudflareIps::path()))->toBeFalse()->and(CloudflareIps::ranges())->toBe(CloudflareIps::BUNDLED);
});

it('keeps CORS switched off: no path is open to other origins', function () {
    expect(config('cors.paths'))->toBe([]);

    $response = $this->call('OPTIONS', '/contact', server: ['HTTP_ORIGIN' => 'https://evil.test', 'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST']);

    expect($response->headers->has('Access-Control-Allow-Origin'))->toBeFalse();
});
