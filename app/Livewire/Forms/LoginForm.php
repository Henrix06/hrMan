<?php

namespace App\Livewire\Forms;

use App\Models\Tenant;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Form;

class LoginForm extends Form
{
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    #[Validate('boolean')]
    public bool $remember = false;

    /**
     * Attempt to authenticate the request's credentials.
     *
     * Scopes the attempt to the current tenant when running on a subdomain,
     * so users from different organisations cannot log in to each other's sites.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $tenant = $this->resolvedTenant();

        // When on a tenant subdomain, scope the auth attempt to that tenant's users only.
        if ($tenant) {
            $credentials = [
                'email'     => $this->email,
                'password'  => $this->password,
                'tenant_id' => $tenant->id,   // Laravel's Eloquent provider uses extra fields as WHERE clauses
            ];

            if (! Auth::attempt($credentials, $this->remember)) {
                RateLimiter::hit($this->throttleKey());

                throw ValidationException::withMessages([
                    'form.email' => trans('auth.failed'),
                ]);
            }
        } else {
            // Main domain — standard auth (demo accounts, legacy users without tenant)
            if (! Auth::attempt($this->only(['email', 'password']), $this->remember)) {
                RateLimiter::hit($this->throttleKey());

                throw ValidationException::withMessages([
                    'form.email' => trans('auth.failed'),
                ]);
            }
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Return the tenant bound to the current request, if any.
     * Returns null if no tenant was resolved by ResolveTenant middleware.
     */
    private function resolvedTenant(): ?Tenant
    {
        // Check if the middleware explicitly bound a Tenant instance.
        // We cannot use app(Tenant::class) directly because Laravel's container
        // will auto-instantiate an empty Tenant model if nothing is bound.
        if (! app()->bound(Tenant::class)) {
            return null;
        }

        $tenant = app(Tenant::class);

        // Extra guard: only return it if it's a persisted record
        return ($tenant instanceof Tenant && $tenant->exists) ? $tenant : null;
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'form.email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email) . '|' . request()->ip());
    }
}
