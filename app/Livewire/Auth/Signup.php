<?php

namespace App\Livewire\Auth;

use App\Services\TenantProvisioner;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.signup')]
class Signup extends Component
{
    #[Url]
    public string $plan = 'free';

    #[Url]
    public string $billing = 'monthly';

    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $company_name = '';

    public string $error_message = '';

    #[Computed]
    public function planConfig(): array
    {
        return config("pricing.plans.{$this->plan}", config('pricing.plans.free'));
    }

    #[Computed]
    public function planName(): string
    {
        return $this->planConfig['name'] ?? 'Free';
    }

    #[Computed]
    public function planPrice(): string
    {
        $plan = $this->planConfig;

        if (($plan['monthly_price'] ?? 0) === 0) {
            return 'Free';
        }

        if (($plan['monthly_price'] ?? null) === null) {
            return 'Contact us';
        }

        return '$' . number_format($plan['monthly_price'], 0) . '/mo';
    }

    public function mount(): void
    {
        if (! array_key_exists($this->plan, config('pricing.plans', []))) {
            $this->plan = 'free';
        }

        if (! in_array($this->billing, ['monthly', 'yearly'], true)) {
            $this->billing = 'monthly';
        }

        // The marketing site can pre-fill the work email.
        $this->email = (string) request()->query('email', $this->email);
    }

    public function signup(): void
    {
        if ($this->ensureNotRateLimited()) {
            return;
        }

        $this->validate([
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'     => ['required', 'string', 'confirmed', Password::defaults()],
            'company_name' => ['required', 'string', 'max:255'],
        ]);

        RateLimiter::hit($this->rateLimitKey(), 3600);

        $this->error_message = '';

        try {
            $result = app(TenantProvisioner::class)->provision([
                'name'           => $this->name,
                'email'          => $this->email,
                'password'       => $this->password,
                'company_name'   => $this->company_name,
                'plan'           => $this->plan,
                'billing_cycle'  => $this->billing,
                'payment_method' => ['type' => 'none'],
            ]);
        } catch (\Throwable $e) {
            report($e);

            $this->error_message = 'We could not set up your workspace. Please try again.';

            return;
        }

        Auth::login($result['user']);

        $this->redirectRoute('dashboard');
    }

    private function rateLimitKey(): string
    {
        return 'signup:' . Str::lower($this->email ?: 'anon');
    }

    private function ensureNotRateLimited(): bool
    {
        $key = $this->rateLimitKey();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            $this->error_message = 'Too many attempts. Please try again in ' . $seconds . ' seconds.';

            return true;
        }

        return false;
    }

    public function render()
    {
        return view('livewire.auth.signup');
    }
}
