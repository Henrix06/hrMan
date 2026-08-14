<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class PricingController extends Controller
{
    public function __invoke(Request $request): View
    {
        $billing = $request->string('billing')->toString() === 'yearly' ? 'yearly' : 'monthly';
        $plans = config('pricing.plans');
        $modules = config('pricing.modules');

        return view('pricing', [
            'billing' => $billing,
            'plans' => $plans,
            'modules' => $modules,
        ]);
    }
}
