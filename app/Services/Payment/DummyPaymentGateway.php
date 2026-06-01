<?php

namespace App\Services\Payment;

class DummyPaymentGateway
{
    /**
     * Process a dummy payment
     *
     * @param array $paymentData
     * @return array
     */
    public function processPayment(array $paymentData): array
    {
        // Simulate payment processing delay
        sleep(1);

        // Generate a dummy transaction ID
        $transactionId = 'TXN-' . strtoupper(uniqid());

        // Always return success for dummy gateway
        return [
            'success' => true,
            'transaction_id' => $transactionId,
            'amount' => $paymentData['amount'] ?? 0,
            'currency' => $paymentData['currency'] ?? 'USD',
            'payment_method' => $paymentData['payment_method'] ?? 'card',
            'processed_at' => now()->toISOString(),
            'message' => 'Payment processed successfully (dummy gateway)',
        ];
    }

    /**
     * Verify a dummy payment
     *
     * @param string $transactionId
     * @return array
     */
    public function verifyPayment(string $transactionId): array
    {
        // Simulate verification delay
        sleep(0.5);

        return [
            'verified' => true,
            'transaction_id' => $transactionId,
            'status' => 'completed',
            'message' => 'Payment verified successfully (dummy gateway)',
        ];
    }
}
