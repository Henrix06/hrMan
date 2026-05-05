<?php

namespace App\Livewire\Auth;

use App\Services\TenantProvisioner;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.signup')]
class Signup extends Component
{
    // ── URL params passed from pricing page ───────────────────────────────────
    #[Url]
    public string $plan = 'free';

    #[Url]
    public string $billing = 'monthly';

    // ── Wizard state ──────────────────────────────────────────────────────────
    public int $step = 1; // 1 = credentials, 2 = payment, 3 = provisioning

    // ── Step 1: Credentials ───────────────────────────────────────────────────
    public string $name         = '';
    public string $email        = '';
    public string $password     = '';
    public string $password_confirmation = '';
    public string $company_name = '';
    public string $subdomain    = '';
    public bool   $subdomain_available = true;

    // ── Step 2: Payment ───────────────────────────────────────────────────────
    public string $payment_method = 'card'; // card | eft
    public string $card_number    = '';
    public string $card_expiry    = '';
    public string $card_cvc       = '';
    public string $card_name      = '';
    public string $eft_reference  = '';

    // ── Step 3: Result ────────────────────────────────────────────────────────
    public bool   $provisioning   = false;
    public bool   $done           = false;
    public string $tenant_url     = '';
    public string $tenant_login   = '';  // subdomain login URL to redirect to
    public string $error_message  = '';

    // ── Computed ──────────────────────────────────────────────────────────────

    #[Computed]
    public function planConfig(): array
    {
        return config("pricing.plans.{$this->plan}", config('pricing.plans.free'));
    }

    #[Computed]
    public function planPrice(): string
    {
        $plan = $this->planConfig;

        if ($plan['monthly_price'] === 0) {
            return 'Free';
        }

        $amount = $this->billing === 'yearly'
            ? $plan['yearly_price']
            : $plan['monthly_price'];

        return '$' . number_format($amount, 0) . ($this->billing === 'yearly' ? '/yr' : '/mo');
    }

    #[Computed]
    public function isFree(): bool
    {
        return ($this->planConfig['monthly_price'] ?? 0) === 0;
    }

    // ── Lifecycle ─────────────────────────────────────────────────────────────

    public function mount(): void
    {
        // Validate the plan key; fall back to free if unknown
        if (! array_key_exists($this->plan, config('pricing.plans', []))) {
            $this->plan = 'free';
        }

        // Free plan skips payment step
        if ($this->isFree) {
            $this->billing = 'monthly';
        }
    }

    // ── Step 1 ────────────────────────────────────────────────────────────────

    public function updatedCompanyName(string $value): void
    {
        if (strlen($value) >= 2) {
            $provisioner    = app(TenantProvisioner::class);
            $this->subdomain = $provisioner->suggestSubdomain($value);
            $this->subdomain_available = true;
        }
    }

    public function updatedSubdomain(string $value): void
    {
        $slug = Str::slug($value, '-');
        $this->subdomain = $slug;

        if ($slug !== '') {
            $provisioner = app(TenantProvisioner::class);
            $this->subdomain_available = $provisioner->isSubdomainAvailable($slug);
        }
    }

    public function nextToPayment(): void
    {
        $this->validate([
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'     => ['required', 'string', 'confirmed', Password::defaults()],
            'company_name' => ['required', 'string', 'max:255'],
            'subdomain'    => ['required', 'string', 'min:3', 'max:50', 'regex:/^[a-z0-9-]+$/', 'unique:tenants,subdomain'],
        ], [
            'subdomain.unique' => 'That subdomain is already taken. Please choose another.',
            'subdomain.regex'  => 'Subdomain may only contain lowercase letters, numbers, and hyphens.',
        ]);

        // Free plan skips payment
        if ($this->isFree) {
            $this->runProvisioning();
            return;
        }

        $this->step = 2;
    }

    // ── Step 2 ────────────────────────────────────────────────────────────────

    public function backToCredentials(): void
    {
        $this->step = 1;
    }

    public function submitPayment(): void
    {
        if ($this->payment_method === 'card') {
            $this->validate([
                'card_number' => ['required', 'string'],
                'card_expiry' => ['required', 'string', 'regex:/^\d{2}\/\d{2}$/'],
                'card_cvc'    => ['required', 'string', 'min:3', 'max:4'],
                'card_name'   => ['required', 'string', 'max:255'],
            ], [
                'card_expiry.regex' => 'Expiry must be in MM/YY format.',
            ]);
        } else {
            $this->validate([
                'eft_reference' => ['required', 'string', 'max:255'],
            ]);
        }

        $this->runProvisioning();
    }

    // ── Provisioning ──────────────────────────────────────────────────────────

    private function runProvisioning(): void
    {
        $this->step        = 3;
        $this->provisioning = true;
        $this->error_message = '';

        try {
            $paymentMethod = $this->buildPaymentMethodPayload();

            $provisioner = app(TenantProvisioner::class);
            $result      = $provisioner->provision([
                'name'           => $this->name,
                'email'          => $this->email,
                'password'       => $this->password,
                'company_name'   => $this->company_name,
                'subdomain'      => $this->subdomain,
                'plan'           => $this->plan,
                'billing_cycle'  => $this->billing,
                'payment_method' => $paymentMethod,
            ]);

            Auth::login($result['user']);

            $this->tenant_url   = $result['tenant']->url();
            $this->provisioning = false;
            $this->done         = true;

        } catch (\Throwable $e) {
            $this->provisioning  = false;
            $this->error_message = 'Something went wrong while setting up your workspace. Please try again.';
            $this->step          = 1;

            report($e);
        }
    }

    private function buildPaymentMethodPayload(): array
    {
        if ($this->isFree) {
            return ['type' => 'none'];
        }

        if ($this->payment_method === 'card') {
            // Store only the last 4 digits — never the full number
            $last4 = substr(preg_replace('/\s+/', '', $this->card_number), -4);
            return [
                'type'       => 'card',
                'last4'      => $last4,
                'expiry'     => $this->card_expiry,
                'cardholder' => $this->card_name,
            ];
        }

        return [
            'type'      => 'eft',
            'reference' => $this->eft_reference,
        ];
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render()
    {
        return view('livewire.auth.signup');
    }
}
