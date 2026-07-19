<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->detectTenant($request);

        if ($tenant) {
            app()->instance(Tenant::class, $tenant);
            View::share('currentTenant', $tenant);
            $request->attributes->set('tenant', $tenant);
        } elseif (Auth::check() && Auth::user()->tenant_id) {
            // Single-domain: resolve tenant from the authenticated user
            $tenant = Tenant::find(Auth::user()->tenant_id);

            if ($tenant && $tenant->status === 'active') {
                app()->instance(Tenant::class, $tenant);
                View::share('currentTenant', $tenant);
                $request->attributes->set('tenant', $tenant);
            }
        }

        return $next($request);
    }

    private function detectTenant(Request $request): ?Tenant
    {
        $host     = $request->getHost();
        $appHost  = parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';
        $appHost  = explode(':', $appHost)[0];

        if (! str_ends_with($host, '.' . $appHost)) {
            return null;
        }

        $subdomain = substr($host, 0, strlen($host) - strlen('.' . $appHost));

        if (str_contains($subdomain, '.') || $subdomain === '') {
            return null;
        }

        return Tenant::where('subdomain', $subdomain)
            ->where('status', 'active')
            ->first();
    }
}
