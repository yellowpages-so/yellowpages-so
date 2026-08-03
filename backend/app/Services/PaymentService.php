<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Models\User;
use App\Services\Payments\ManualPaymentGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PaymentService
{
    public function createIntent(
        ?User $user,
        array $data
    ): array {
        $provider = DB::table('payments.providers')
            ->where('code', $data['provider_code'])
            ->where('active', true)
            ->first();

        if (! $provider) {
            throw new RuntimeException('Payment provider is unavailable.');
        }

        $id = (string) Str::uuid();
        $reference = 'PAY-'.now()->format('Ymd').'-'.strtoupper(Str::random(10));

        $gatewayResult = $this->gateway($provider->code)
            ->createIntent($data);

        DB::table('payments.payment_intents')->insert([
            'id' => $id,
            'business_id' => $data['business_id'],
            'user_id' => $user?->id,
            'order_id' => $data['order_id'] ?? null,
            'invoice_id' => $data['invoice_id'] ?? null,
            'provider_id' => $provider->id,
            'reference' => $reference,
            'status' => $gatewayResult['status'] ?? 'requires_action',
            'currency' => strtoupper($data['currency']),
            'amount' => $data['amount'],
            'captured_amount' => 0,
            'refunded_amount' => 0,
            'provider_reference' => $gatewayResult['provider_reference'] ?? null,
            'customer_phone' => $data['customer_phone'] ?? null,
            'customer_email' => $data['customer_email'] ?? null,
            'metadata' => json_encode($data['metadata'] ?? []),
            'expires_at' => now()->addMinutes(
                config('payments.intent_expiry_minutes')
            ),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'id' => $id,
            'reference' => $reference,
            'status' => $gatewayResult['status'] ?? 'requires_action',
            'instructions' => $gatewayResult['instructions'] ?? null,
        ];
    }

    public function capture(
        string $intentId
    ): array {
        $intent = DB::table('payments.payment_intents')
            ->where('id', $intentId)
            ->first();

        if (! $intent) {
            throw new RuntimeException('Payment intent not found.');
        }

        if ($intent->status === 'succeeded') {
            return [
                'status' => 'succeeded',
                'captured_amount' => (float) $intent->captured_amount,
            ];
        }

        $provider = DB::table('payments.providers')
            ->where('id', $intent->provider_id)
            ->first();

        $result = $this->gateway($provider->code)
            ->capture((array) $intent);

        DB::transaction(function () use ($intent, $result): void {
            DB::table('payments.transactions')->insert([
                'id' => (string) Str::uuid(),
                'payment_intent_id' => $intent->id,
                'type' => 'capture',
                'status' => $result['status'],
                'amount' => $intent->amount,
                'currency' => $intent->currency,
                'provider_transaction_id' => $result['provider_transaction_id'] ?? null,
                'provider_response' => json_encode($result),
                'processed_at' => now(),
                'created_at' => now(),
            ]);

            DB::table('payments.payment_intents')
                ->where('id', $intent->id)
                ->update([
                    'status' => 'succeeded',
                    'captured_amount' => $intent->amount,
                    'paid_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($intent->order_id) {
                DB::table('commerce.orders')
                    ->where('id', $intent->order_id)
                    ->update([
                        'payment_status' => 'paid',
                        'status' => 'confirmed',
                        'updated_at' => now(),
                    ]);
            }
        });

        return [
            'status' => 'succeeded',
            'captured_amount' => (float) $intent->amount,
        ];
    }

    public function refund(
        User $user,
        string $intentId,
        float $amount,
        ?string $reason
    ): array {
        $intent = DB::table('payments.payment_intents')
            ->where('id', $intentId)
            ->first();

        if (! $intent || $intent->status !== 'succeeded') {
            throw new RuntimeException('Payment is not refundable.');
        }

        $remaining = (float) $intent->captured_amount
            - (float) $intent->refunded_amount;

        if ($amount <= 0 || $amount > $remaining) {
            throw new RuntimeException('Refund amount is invalid.');
        }

        $provider = DB::table('payments.providers')
            ->where('id', $intent->provider_id)
            ->first();

        $result = $this->gateway($provider->code)
            ->refund((array) $intent, $amount);

        $refundId = (string) Str::uuid();

        DB::transaction(function () use (
            $user,
            $intent,
            $amount,
            $reason,
            $result,
            $refundId
        ): void {
            DB::table('payments.refunds')->insert([
                'id' => $refundId,
                'payment_intent_id' => $intent->id,
                'requested_by' => $user->id,
                'amount' => $amount,
                'currency' => $intent->currency,
                'reason' => $reason,
                'status' => $result['status'],
                'provider_refund_id' => $result['provider_refund_id'] ?? null,
                'processed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('payments.payment_intents')
                ->where('id', $intent->id)
                ->increment('refunded_amount', $amount);
        });

        return [
            'id' => $refundId,
            'status' => $result['status'],
            'amount' => $amount,
        ];
    }

    public function createEscrow(
        string $intentId,
        string $businessId,
        float $amount,
        ?string $condition,
        ?string $releaseDueAt
    ): string {
        $id = (string) Str::uuid();

        DB::table('payments.escrows')->insert([
            'id' => $id,
            'payment_intent_id' => $intentId,
            'business_id' => $businessId,
            'amount' => $amount,
            'currency' => config('payments.currency'),
            'status' => 'held',
            'release_condition' => $condition,
            'release_due_at' => $releaseDueAt,
            'created_at' => now(),
        ]);

        return $id;
    }

    public function releaseEscrow(
        string $escrowId
    ): void {
        DB::table('payments.escrows')
            ->where('id', $escrowId)
            ->where('status', 'held')
            ->update([
                'status' => 'released',
                'released_at' => now(),
            ]);
    }

    private function gateway(
        string $providerCode
    ): PaymentGateway {
        return new ManualPaymentGateway;
    }
}
