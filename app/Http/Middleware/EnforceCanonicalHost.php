<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 301-redirects to the scheme and host of APP_URL (https, www or non-www),
 * so the canonical domain is configured in .env instead of .htaccess.
 * Only active in production.
 */
class EnforceCanonicalHost
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->isProduction()) {
            return $next($request);
        }

        $canonical = parse_url((string) config('app.url'));
        $scheme = $canonical['scheme'] ?? 'https';
        $host = $canonical['host'] ?? null;

        if ($host === null) {
            return $next($request);
        }

        if ($request->getHost() !== $host || $request->getScheme() !== $scheme) {
            return redirect()->to($scheme.'://'.$host.$request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
