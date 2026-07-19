<?php

namespace App\Livewire\Auth;

use App\Models\Tenant;
use App\Services\Payment\DummyPaymentGateway;
use App\Services\TenantProvisioner;
use Illuminate\Support\Facades\Auth;
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

    public int $step = 1;

    // Step 1: Credentials
    public string $name         = '';
    public string $email        = '';
    public string $password     = '';
    public string $password_confirmation = '';
    public string $company_name = '';
    public ?string $organisation_id = '1';

    // Step 2: Payment
    public string $payment_method = 'card';
    public string $card_number    = '';
    public string $card_expiry    = '';
    public string $card_cvc       = '';
    public string $card_name      = '';
    public string $eft_reference  = '';

    // Step 3: Result
    public bool   $provisioning   = false;
    public bool   $done           = false;
    public string $error_message  = '';

    #[Computed]
    public function planConfig(): array
    {
        return config("pricing.plans.{$this->plan}", config('pricing.plans.free'));
    }

    #[Computed]
    public function planPrice(): string
    {
        $plan = $this->planConfig;

        if ($plan['monthly_price'] === null && $plan['yearly_price'] === null) {
            return 'Contact us';
        }

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
        return ($this->planConfig['monthly_price'] ?? 0) === 0 || ($this->planConfig['monthly_price'] === null && $this->planConfig['yearly_price'] === null);
    }

    public function mount(): void
    {
        if (! array_key_exists($this->plan, config('pricing.plans', []))) {
            $this->plan = 'free';
        }

        if ($this->isFree) {
            $this->billing = 'monthly';
        }
    }

    public function nextToPayment(): void
    {
        // Validate base fields
        $this->validate([
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'     => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        // If joining an existing org, validate organisation_id exists
        if ($this->organisation_id) {
            $this->validate([
                'organisation_id' => ['required', 'string', 'exists:tenants,id'],
            ]);
            // Clear company_name since we're joining an existing org
            $this->company_name = '';
        } else {
            // Creating a new org, company_name is required
            $this->validate([
                'company_name' => ['required', 'string', 'max:255'],
            ]);
        }

        if ($this->isFree) {
            $this->runProvisioning();
            return;
        }

        $this->step = 2;
    }

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

        $paymentGateway = app(DummyPaymentGateway::class);
        $planConfig = $this->planConfig;
        $amount = $this->billing === 'yearly' ? $planConfig['yearly_price'] : $planConfig['monthly_price'];

        $paymentResult = $paymentGateway->processPayment([
            'amount' => $amount,
            'currency' => 'USD',
            'payment_method' => $this->payment_method,
            'card_details' => $this->payment_method === 'card' ? [
                'last4' => substr(preg_replace('/\s+/', '', $this->card_number), -4),
                'expiry' => $this->card_expiry,
                'cardholder' => $this->card_name,
            ] : null,
            'eft_reference' => $this->payment_method === 'eft' ? $this->eft_reference : null,
        ]);

        if (!$paymentResult['success']) {
            $this->error_message = $paymentResult['message'] ?? 'Payment failed. Please try again.';
            return;
        }

        $this->runProvisioning($paymentResult);
    }

    private function runProvisioning(?array $paymentResult = null): void
    {
        $this->step        = 3;
        $this->provisioning = true;
        $this->error_message = '';

        try {
            $paymentMethod = $this->buildPaymentMethodPayload($paymentResult);

            $provisioner = app(TenantProvisioner::class);
            $result      = $provisioner->provision([
                'name'            => $this->name,
                'email'           => $this->email,
                'password'        => $this->password,
                'company_name'    => $this->company_name,
                'organisation_id' => $this->organisation_id,
                'plan'            => $this->plan,
                'billing_cycle'   => $this->billing,
                'payment_method'  => $paymentMethod,
            ]);

            Auth::login($result['user']);

            $this->provisioning = false;
            $this->done         = true;

            // Auto-redirect to dashboard after a short delay
            $this->redirect(route('dashboard'), navigate: true);

        } catch (\Throwable $e) {
            $this->provisioning  = false;
            $this->error_message = 'Something went wrong while setting up your workspace. Please try again.';
            $this->step          = 1;

            report($e);
        }
    }

    private function buildPaymentMethodPayload(?array $paymentResult = null): array
    {
        if ($this->isFree) {
            return ['type' => 'none'];
        }

        $payload = [];

        if ($this->payment_method === 'card') {
            $last4 = substr(preg_replace('/\s+/', '', $this->card_number), -4);
            $payload = [
                'type'       => 'card',
                'last4'      => $last4,
                'expiry'     => $this->card_expiry,
                'cardholder' => $this->card_name,
            ];
        } else {
            $payload = [
                'type'      => 'eft',
                'reference' => $this->eft_reference,
            ];
        }

        if ($paymentResult) {
            $payload['transaction_id'] = $paymentResult['transaction_id'] ?? null;
            $payload['gateway'] = 'dummy';
            $payload['processed_at'] = $paymentResult['processed_at'] ?? null;
        }

        return $payload;
    }

    public function render()
    {
        return view('livewire.auth.signup');
    }
}
