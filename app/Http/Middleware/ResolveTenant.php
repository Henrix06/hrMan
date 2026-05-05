<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->detectTenant($request);

        if ($tenant) {
            // Bind to the container so anything can resolve it via app(Tenant::class)
            app()->instance(Tenant::class, $tenant);

            // Share with all Blade views
            View::share('currentTenant', $tenant);

            // Tag the request itself for convenience
            $request->attributes->set('tenant', $tenant);
        }

        return $next($request);
    }

    private function detectTenant(Request $request): ?Tenant
    {
        $host     = $request->getHost();                    // e.g. acme.localhost or acme.umsizi.co
        $appHost  = parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';

        // Strip port if present (e.g. localhost:8000 → localhost)
        $appHost = explode(':', $appHost)[0];

        // Only proceed if the request host is a subdomain of the app host
        if (! str_ends_with($host, '.' . $appHost)) {
            return null;
        }

        // Extract the leftmost label as the subdomain
        $subdomain = substr($host, 0, strlen($host) - strlen('.' . $appHost));

        // Reject multi-level subdomains (e.g. a.b.localhost)
        if (str_contains($subdomain, '.') || $subdomain === '') {
            return null;
        }

        return Tenant::where('subdomain', $subdomain)
            ->where('status', 'active')
            ->first();
    }
}
