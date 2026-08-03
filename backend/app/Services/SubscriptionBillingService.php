<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class SubscriptionBillingService
{
    public function start(User $user, array $data): array
    {
        $this->assertBusinessManager($user, $data['business_id']);

        $plan = DB::table('billing.plans')
            ->where('code', $data['plan_code'])
            ->where('active', true)
            ->first();

        if (! $plan) {
            throw new RuntimeException('Plan not found.');
        }

        $active = DB::table('billing.subscriptions')
            ->where('business_id', $data['business_id'])
            ->whereIn('status', ['trial', 'active', 'past_due'])
            ->first();

        if ($active) {
            throw new RuntimeException('This business already has an active subscription.');
        }

        return DB::transaction(function () use ($user, $data, $plan): array {
            $now = CarbonImmutable::now();
            $trialDays = property_exists($plan, 'trial_days')
                ? (int) $plan->trial_days
                : 0;

            $trialEnds = $trialDays > 0
                ? $now->addDays($trialDays)
                : null;

            $periodStart = $trialEnds ?? $now;
            $periodEnd = $plan->billing_interval === 'yearly'
                ? $periodStart->addYear()
                : $periodStart->addMonth();

            $subscriptionId = (string) Str::uuid();
            $status = $trialEnds ? 'trial' : 'active';

            DB::table('billing.subscriptions')->insert([
                'id' => $subscriptionId,
                'business_id' => $data['business_id'],
                'plan_id' => $plan->id,
                'status' => $status,
                'starts_at' => $periodStart,
                'ends_at' => $periodEnd,
                'auto_renew' => true,
            ]);

            $invoice = $this->createInvoice(
                $data['business_id'],
                $subscriptionId,
                $plan,
                $data['coupon_code'] ?? null
            );

            $this->event(
                $subscriptionId,
                $user->id,
                'subscription_started',
                [
                    'plan_code' => $plan->code,
                    'invoice_id' => $invoice['id'],
                ]
            );

            return [
                'subscription_id' => $subscriptionId,
                'status' => $status,
                'invoice' => $invoice,
            ];
        });
    }

    public function changePlan(
        User $user,
        string $subscriptionId,
        array $data
    ): void {
        $subscription = DB::table('billing.subscriptions')
            ->where('id', $subscriptionId)
            ->first();

        if (! $subscription) {
            throw new RuntimeException('Subscription not found.');
        }

        $this->assertBusinessManager($user, $subscription->business_id);

        $plan = DB::table('billing.plans')
            ->where('code', $data['plan_code'])
            ->where('active', true)
            ->first();

        if (! $plan) {
            throw new RuntimeException('Plan not found.');
        }

        if ($data['effective'] === 'immediately') {
            DB::table('billing.subscriptions')
                ->where('id', $subscriptionId)
                ->update([
                    'plan_id' => $plan->id,
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('billing.subscriptions')
                ->where('id', $subscriptionId)
                ->update([
                    'metadata' => json_encode([
                        'next_plan_id' => $plan->id,
                        'next_plan_code' => $plan->code,
                    ]),
                    'updated_at' => now(),
                ]);
        }

        $this->event(
            $subscriptionId,
            $user->id,
            'plan_change_requested',
            $data
        );
    }

    public function cancel(
        User $user,
        string $subscriptionId,
        bool $immediately
    ): void {
        $subscription = DB::table('billing.subscriptions')
            ->where('id', $subscriptionId)
            ->first();

        if (! $subscription) {
            throw new RuntimeException('Subscription not found.');
        }

        $this->assertBusinessManager($user, $subscription->business_id);

        DB::table('billing.subscriptions')
            ->where('id', $subscriptionId)
            ->update([
                'status' => $immediately ? 'cancelled' : $subscription->status,
                'cancel_at_period_end' => ! $immediately,
                'cancelled_at' => $immediately ? now() : null,
                'updated_at' => now(),
            ]);

        $this->event(
            $subscriptionId,
            $user->id,
            $immediately ? 'subscription_cancelled' : 'cancellation_scheduled',
            []
        );
    }

    public function entitlements(string $businessId): array
    {
        $subscription = DB::table('billing.subscriptions as subscriptions')
            ->join('billing.plans as plans', 'plans.id', '=', 'subscriptions.plan_id')
            ->where('subscriptions.business_id', $businessId)
            ->whereIn('subscriptions.status', ['trialing', 'active', 'past_due'])
            ->select([
                'subscriptions.id as subscription_id',
                'subscriptions.status',
                'subscriptions.starts_at as current_period_starts_at',
                'subscriptions.ends_at as current_period_ends_at',
                'plans.id as plan_id',
                'plans.code as plan_code',
                'plans.name as plan_name',
            ])
            ->first();

        if (! $subscription) {
            $subscription = DB::table('billing.plans')
                ->where('code', 'free')
                ->selectRaw(
                    "NULL::uuid as subscription_id,
                     'active'::text as status,
                     NULL::timestamptz as current_period_starts_at,
                     NULL::timestamptz as current_period_ends_at,
                     id as plan_id,
                     code as plan_code,
                     name as plan_name"
                )
                ->first();
        }

        $features = DB::table('billing.plan_features as plan_features')
            ->join('billing.features as features', 'features.id', '=', 'plan_features.feature_id')
            ->where('plan_features.plan_id', $subscription->plan_id)
            ->pluck('plan_features.value', 'features.code')
            ->all();

        return [
            'subscription' => $subscription,
            'features' => $features,
        ];
    }

    public function consume(
        string $businessId,
        string $featureCode,
        int $amount = 1
    ): void {
        $entitlements = $this->entitlements($businessId);
        $limit = $entitlements['features'][$featureCode] ?? null;

        if ($limit === null) {
            throw new RuntimeException('Feature is not available on this plan.');
        }

        if (in_array($limit, ['true', 'false'], true)) {
            if ($limit !== 'true') {
                throw new RuntimeException('Feature is not available on this plan.');
            }

            return;
        }

        $subscriptionId = $entitlements['subscription']->subscription_id;

        if (! $subscriptionId) {
            throw new RuntimeException('Usage tracking requires an active paid subscription.');
        }

        $periodStart = $entitlements['subscription']->current_period_starts_at;
        $periodEnd = $entitlements['subscription']->current_period_ends_at;

        $used = (int) DB::table('billing.subscription_usage')
            ->where('subscription_id', $subscriptionId)
            ->where('feature_code', $featureCode)
            ->where('period_starts_at', $periodStart)
            ->value('used_amount');

        if (($used + $amount) > (int) $limit) {
            throw new RuntimeException('Plan usage limit exceeded.');
        }

        DB::table('billing.subscription_usage')->updateOrInsert(
            [
                'subscription_id' => $subscriptionId,
                'feature_code' => $featureCode,
                'period_starts_at' => $periodStart,
            ],
            [
                'id' => DB::table('billing.subscription_usage')
                    ->where('subscription_id', $subscriptionId)
                    ->where('feature_code', $featureCode)
                    ->where('period_starts_at', $periodStart)
                    ->value('id') ?? (string) Str::uuid(),
                'used_amount' => $used + $amount,
                'period_ends_at' => $periodEnd,
                'updated_at' => now(),
            ]
        );
    }

    public function renewDueSubscriptions(): int
    {
        $count = 0;

        DB::table('billing.subscriptions')
            ->whereIn('status', ['trial', 'active', 'past_due'])
            ->where('current_period_ends_at', '<=', now())
            ->orderBy('id')
            ->chunkById(100, function ($subscriptions) use (&$count): void {
                foreach ($subscriptions as $subscription) {
                    $plan = DB::table('billing.plans')
                        ->where('id', $subscription->plan_id)
                        ->first();

                    if (! $plan) {
                        continue;
                    }

                    if ($subscription->cancel_at_period_end) {
                        DB::table('billing.subscriptions')
                            ->where('id', $subscription->id)
                            ->update([
                                'status' => 'cancelled',
                                'cancelled_at' => now(),
                                'updated_at' => now(),
                            ]);

                        continue;
                    }

                    $start = CarbonImmutable::parse(
                        $subscription->current_period_ends_at
                    );

                    $end = $plan->billing_interval === 'yearly'
                        ? $start->addYear()
                        : $start->addMonth();

                    DB::transaction(function () use (
                        $subscription,
                        $plan,
                        $start,
                        $end
                    ): void {
                        DB::table('billing.subscriptions')
                            ->where('id', $subscription->id)
                            ->update([
                                'status' => 'active',
                                'current_period_starts_at' => $start,
                                'current_period_ends_at' => $end,
                                'trial_ends_at' => null,
                                'updated_at' => now(),
                            ]);

                        $this->createInvoice(
                            $subscription->business_id,
                            $subscription->id,
                            $plan,
                            null
                        );

                        $this->event(
                            $subscription->id,
                            null,
                            'subscription_renewed',
                            []
                        );
                    });

                    $count++;
                }
            });

        return $count;
    }

    private function createInvoice(
        string $businessId,
        string $subscriptionId,
        object $plan,
        ?string $couponCode
    ): array {
        $coupon = null;
        $discount = 0.0;

        if ($couponCode) {
            $coupon = DB::table('billing.coupons')
                ->where('code', strtoupper($couponCode))
                ->where('active', true)
                ->first();

            if ($coupon) {
                $discount = $coupon->discount_type === 'percentage'
                    ? ((float) $plan->price * ((float) $coupon->discount_value / 100))
                    : (float) $coupon->discount_value;
            }
        }

        $subtotal = (float) $plan->price;
        $total = max($subtotal - $discount, 0);
        $invoiceId = (string) Str::uuid();
        $invoiceNo = 'INV-'.now()->format('Ymd').'-'.strtoupper(Str::random(8));

        DB::table('billing.invoices')->insert([
            'id' => $invoiceId,
            'invoice_no' => $invoiceNo,
            'business_id' => $businessId,
            'subscription_id' => $subscriptionId,
            'status' => $total <= 0 ? 'paid' : 'issued',
            'currency' => $plan->currency,
            'subtotal' => $subtotal,
            'tax_total' => 0,
            'total' => $total,
            'amount_paid' => $total <= 0 ? $total : 0,
            'issued_at' => now(),
            'due_at' => now()->addDays(7),
        ]);

        DB::table('billing.invoice_items')->insert([
            'id' => (string) Str::uuid(),
            'invoice_id' => $invoiceId,
            'description' => $plan->name.' subscription',
            'quantity' => 1,
            'unit_price' => $subtotal,
            'line_total' => $subtotal,
        ]);

        return [
            'id' => $invoiceId,
            'invoice_no' => $invoiceNo,
            'total_amount' => $total,
            'currency' => $plan->currency,
            'status' => $total <= 0 ? 'paid' : 'issued',
        ];
    }

    private function assertBusinessManager(User $user, string $businessId): void
    {
        $allowed = DB::table('directory.business_members')
            ->where('business_id', $businessId)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();

        if (! $allowed) {
            throw new RuntimeException('You do not manage this business.');
        }
    }

    private function event(
        string $subscriptionId,
        ?string $actorUserId,
        string $eventType,
        array $metadata
    ): void {
        if (! Schema::hasTable(
            'billing.subscription_events'
        )) {
            return;
        }

        DB::table('billing.subscription_events')->insert([
            'id' => (string) Str::uuid(),
            'subscription_id' => $subscriptionId,
            'actor_user_id' => $actorUserId,
            'event_type' => $eventType,
            'metadata' => json_encode($metadata),
            'created_at' => now(),
        ]);
    }
}
