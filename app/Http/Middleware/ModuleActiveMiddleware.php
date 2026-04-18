<?php

namespace App\Http\Middleware;

use App\Contracts\ModuleRegistryInterface;
use App\Exceptions\InvalidModuleKeyException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ModuleActiveMiddleware
{
    public function __construct(private readonly ModuleRegistryInterface $registry) {}

    public function handle(Request $request, Closure $next, string $moduleKey): Response
    {
        try {
            $active = $this->registry->isActive($moduleKey);
        } catch (InvalidModuleKeyException) {
            abort(404);
        }

        if (! $active) {
            return response()->view('errors.module-disabled', ['moduleKey' => $moduleKey], 403);
        }

        return $next($request);
    }
}
