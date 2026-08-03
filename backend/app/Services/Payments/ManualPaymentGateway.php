<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use Illuminate\Support\Str;

class ManualPaymentGateway implements PaymentGateway
{
    public function createIntent(array $payment): array
    {
        return [
            'success' => true,
            'status' => 'requires_action',
            'provider_reference' => 'manual_'.Str::lower(Str::random(20)),
            'instructions' => 'Complete payment using the selected manual or mobile-money channel.',
        ];
    }

    public function capture(array $payment): array
    {
        return [
            'success' => true,
            'status' => 'succeeded',
            'provider_transaction_id' => 'txn_'.Str::lower(Str::random(24)),
        ];
    }

    public function refund(array $payment, float $amount): array
    {
        return [
            'success' => true,
            'status' => 'succeeded',
            'provider_refund_id' => 'ref_'.Str::lower(Str::random(24)),
            'amount' => $amount,
        ];
    }
}
