<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminPlanRequest;
use App\Support\AdminAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminSubscriptionBillingController extends Controller
{
    public function plans(Request $request): JsonResponse
    {
        AdminAccess::authorize($request->user());

        return response()->json([
            'success' => true,
            'data' => DB::table('billing.plans')
                ->orderBy('sort_order')
                ->orderBy('price')
                ->get(),
        ]);
    }

    public function storePlan(
        AdminPlanRequest $request
    ): JsonResponse {
        AdminAccess::authorize($request->user());
        $data = $request->validated();

        $planId = DB::table('billing.plans')
            ->where('code', $data['code'])
            ->value('id') ?? (string) Str::uuid();

        DB::transaction(function () use ($planId, $data): void {
            DB::table('billing.plans')->updateOrInsert(
                ['code' => $data['code']],
                [
                    'id' => $planId,
                    'name' => $data['name'],
                    'description' => $data['description'] ?? null,
                    'billing_interval' => $data['billing_interval'],
                    'price' => $data['price'],
                    'currency' => strtoupper($data['currency']),
                    'trial_days' => $data['trial_days'] ?? 0,
                    'active' => $data['active'] ?? true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            foreach ($data['features'] ?? [] as $featureCode => $value) {
                $featureId = DB::table('billing.features')
                    ->where('code', $featureCode)
                    ->value('id');

                if (! $featureId) {
                    continue;
                }

                DB::table('billing.plan_features')->updateOrInsert(
                    [
                        'plan_id' => $planId,
                        'feature_id' => $featureId,
                    ],
                    [
                        'id' => DB::table('billing.plan_features')
                            ->where('plan_id', $planId)
                            ->where('feature_id', $featureId)
                            ->value('id') ?? (string) Str::uuid(),
                        'value' => (string) $value,
                        'created_at' => now(),
                    ]
                );
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Plan saved successfully.',
            'data' => ['id' => $planId],
        ], 201);
    }

    public function subscriptions(Request $request): JsonResponse
    {
        AdminAccess::authorize($request->user());

        return response()->json([
            'success' => true,
            'data' => DB::table('billing.subscriptions as subscriptions')
                ->join('billing.plans as plans', 'plans.id', '=', 'subscriptions.plan_id')
                ->join('directory.businesses as businesses', 'businesses.id', '=', 'subscriptions.business_id')
                ->select([
                    'subscriptions.*',
                    'plans.name as plan_name',
                    'plans.code as plan_code',
                    'businesses.trading_name',
                ])
                ->orderByDesc('subscriptions.created_at')
                ->paginate(25),
        ]);
    }

    public function revenue(Request $request): JsonResponse
    {
        AdminAccess::authorize($request->user());

        $paid = DB::table('billing.invoices')
            ->where('status', 'paid');

        return response()->json([
            'success' => true,
            'data' => [
                'total_revenue' => (float) (clone $paid)->sum('total_amount'),
                'paid_invoices' => (clone $paid)->count(),
                'open_invoices' => DB::table('billing.invoices')
                    ->where('status', 'open')
                    ->count(),
                'active_subscriptions' => DB::table('billing.subscriptions')
                    ->whereIn('status', ['trialing', 'active'])
                    ->count(),
                'past_due_subscriptions' => DB::table('billing.subscriptions')
                    ->where('status', 'past_due')
                    ->count(),
            ],
        ]);
    }
}
