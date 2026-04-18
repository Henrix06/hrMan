<?php

namespace App\Http\Middleware;

use App\Contracts\FeatureFlagServiceInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FeatureFlagMiddleware
{
    public function __construct(private readonly FeatureFlagServiceInterface $flags) {}

    public function handle(Request $request, Closure $next, string $flagKey): Response
    {
        if (! $this->flags->isEnabled($flagKey)) {
            abort(403, 'Feature not available.');
        }

        return $next($request);
    }
}
