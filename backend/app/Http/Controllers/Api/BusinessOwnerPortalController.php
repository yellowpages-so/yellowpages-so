<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBusinessBranchRequest;
use App\Http\Requests\StoreBusinessContactRequest;
use App\Http\Requests\StoreBusinessHoursRequest;
use App\Http\Requests\StoreBusinessServiceRequest;
use App\Http\Requests\StoreBusinessSocialLinkRequest;
use App\Http\Requests\StoreBusinessTeamMemberRequest;
use App\Models\Business;
use App\Services\BusinessOwnerPortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class BusinessOwnerPortalController extends Controller
{
    public function __construct(
        private readonly BusinessOwnerPortalService $service
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->dashboard($request->user()),
        ]);
    }

    public function progress(
        Request $request,
        Business $business
    ): JsonResponse {
        $this->service->assertManager($request->user(), $business);

        return response()->json([
            'success' => true,
            'data' => $this->service->recalculateProgress($business),
        ]);
    }

    public function branches(
        Request $request,
        Business $business
    ): JsonResponse {
        $this->service->assertManager($request->user(), $business);

        return response()->json([
            'success' => true,
            'data' => DB::table('directory.business_branches')
                ->where('business_id', $business->id)
                ->orderByDesc('is_head_office')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function storeBranch(
        StoreBusinessBranchRequest $request,
        Business $business
    ): JsonResponse {
        $this->service->assertManager($request->user(), $business);

        $id = $this->service->addBranch(
            $business,
            $request->validated()
        );

        $this->service->recalculateProgress($business);

        return response()->json([
            'success' => true,
            'message' => 'Branch added successfully.',
            'data' => ['id' => $id],
        ], 201);
    }

    public function contacts(
        Request $request,
        Business $business
    ): JsonResponse {
        $this->service->assertManager($request->user(), $business);

        return response()->json([
            'success' => true,
            'data' => DB::table('directory.business_contacts')
                ->where('business_id', $business->id)
                ->orderByDesc('is_primary')
                ->get(),
        ]);
    }

    public function storeContact(
        StoreBusinessContactRequest $request,
        Business $business
    ): JsonResponse {
        $this->service->assertManager($request->user(), $business);
        $data = $request->validated();

        if ($data['is_primary'] ?? false) {
            DB::table('directory.business_contacts')
                ->where('business_id', $business->id)
                ->where('contact_type', $data['contact_type'])
                ->update(['is_primary' => false]);
        }

        $id = (string) Str::uuid();

        DB::table('directory.business_contacts')->insert([
            'id' => $id,
            'business_id' => $business->id,
            'contact_type' => $data['contact_type'],
            'label' => $data['label'] ?? null,
            'value' => $data['value'],
            'is_primary' => $data['is_primary'] ?? false,
            'is_public' => $data['is_public'] ?? true,
            'created_at' => now(),
        ]);

        $this->service->recalculateProgress($business);

        return response()->json([
            'success' => true,
            'message' => 'Contact added successfully.',
            'data' => ['id' => $id],
        ], 201);
    }

    public function socialLinks(
        Request $request,
        Business $business
    ): JsonResponse {
        $this->service->assertManager($request->user(), $business);

        return response()->json([
            'success' => true,
            'data' => DB::table('directory.business_social_links')
                ->where('business_id', $business->id)
                ->orderBy('platform')
                ->get(),
        ]);
    }

    public function storeSocialLink(
        StoreBusinessSocialLinkRequest $request,
        Business $business
    ): JsonResponse {
        $this->service->assertManager($request->user(), $business);
        $data = $request->validated();

        DB::table('directory.business_social_links')->updateOrInsert(
            [
                'business_id' => $business->id,
                'platform' => $data['platform'],
            ],
            [
                'id' => DB::table('directory.business_social_links')
                    ->where('business_id', $business->id)
                    ->where('platform', $data['platform'])
                    ->value('id') ?? (string) Str::uuid(),
                'url' => $data['url'],
                'created_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Social link saved successfully.',
        ]);
    }

    public function storeHours(
        StoreBusinessHoursRequest $request,
        Business $business
    ): JsonResponse {
        $this->service->assertManager($request->user(), $business);

        DB::transaction(function () use ($business, $request): void {
            DB::table('directory.business_opening_hours')
                ->where('business_id', $business->id)
                ->delete();

            foreach ($request->validated()['hours'] as $row) {
                DB::table('directory.business_opening_hours')->insert([
                    'id' => (string) Str::uuid(),
                    'business_id' => $business->id,
                    'day_of_week' => $row['day_of_week'],
                    'is_closed' => $row['is_closed'] ?? false,
                    'open_time' => $row['open_time'] ?? null,
                    'close_time' => $row['close_time'] ?? null,
                ]);
            }
        });

        $this->service->recalculateProgress($business);

        return response()->json([
            'success' => true,
            'message' => 'Opening hours saved successfully.',
        ]);
    }

    public function services(
        Request $request,
        Business $business
    ): JsonResponse {
        $this->service->assertManager($request->user(), $business);

        return response()->json([
            'success' => true,
            'data' => DB::table('directory.business_services as business_services')
                ->leftJoin('directory.services as services', 'services.id', '=', 'business_services.service_id')
                ->where('business_services.business_id', $business->id)
                ->select([
                    'business_services.*',
                    'services.name as service_name',
                    'services.name_so as service_name_so',
                ])
                ->orderBy('service_name')
                ->get(),
        ]);
    }

    public function storeService(
        StoreBusinessServiceRequest $request,
        Business $business
    ): JsonResponse {
        $this->service->assertManager($request->user(), $business);
        $data = $request->validated();

        $id = (string) Str::uuid();

        DB::table('directory.business_services')->insert([
            'id' => $id,
            'business_id' => $business->id,
            'service_id' => $data['service_id'] ?? null,
            'custom_name' => $data['custom_name'] ?? null,
            'description' => $data['description'] ?? null,
            'price_from' => $data['price_from'] ?? null,
            'currency' => $data['currency'] ?? null,
            'active' => true,
            'created_at' => now(),
        ]);

        $this->service->recalculateProgress($business);

        return response()->json([
            'success' => true,
            'message' => 'Business service added successfully.',
            'data' => ['id' => $id],
        ], 201);
    }

    public function team(
        Request $request,
        Business $business
    ): JsonResponse {
        $this->service->assertManager($request->user(), $business);

        return response()->json([
            'success' => true,
            'data' => DB::table('directory.business_members as members')
                ->join('iam.users as users', 'users.id', '=', 'members.user_id')
                ->leftJoin('iam.user_profiles as profiles', 'profiles.user_id', '=', 'users.id')
                ->where('members.business_id', $business->id)
                ->select([
                    'members.id',
                    'members.user_id',
                    'members.role_code',
                    'members.status',
                    'members.joined_at',
                    'profiles.display_name',
                ])
                ->orderBy('profiles.display_name')
                ->get(),
        ]);
    }

    public function storeTeamMember(
        StoreBusinessTeamMemberRequest $request,
        Business $business
    ): JsonResponse {
        $this->service->assertManager($request->user(), $business);

        $owner = DB::table('directory.business_members')
            ->where('business_id', $business->id)
            ->where('user_id', $request->user()->id)
            ->where('role_code', 'owner')
            ->where('status', 'active')
            ->exists();

        if (! $owner) {
            throw new RuntimeException('Only an owner can add team members.');
        }

        $data = $request->validated();

        DB::table('directory.business_members')->updateOrInsert(
            [
                'business_id' => $business->id,
                'user_id' => $data['user_id'],
            ],
            [
                'id' => DB::table('directory.business_members')
                    ->where('business_id', $business->id)
                    ->where('user_id', $data['user_id'])
                    ->value('id') ?? (string) Str::uuid(),
                'role_code' => $data['role_code'],
                'status' => 'active',
                'joined_at' => now(),
                'created_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Team member added successfully.',
        ], 201);
    }

    public function analytics(
        Request $request,
        Business $business
    ): JsonResponse {
        $this->service->assertManager($request->user(), $business);

        $days = min(max((int) $request->query('days', 30), 1), 365);

        $metrics = DB::table('analytics.business_daily_metrics')
            ->where('business_id', $business->id)
            ->whereDate('metric_date', '>=', now()->subDays($days - 1)->toDateString())
            ->orderBy('metric_date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'days' => $days,
                'series' => $metrics,
                'totals' => [
                    'profile_views' => $metrics->sum('profile_views'),
                    'search_impressions' => $metrics->sum('search_impressions'),
                    'website_clicks' => $metrics->sum('website_clicks'),
                    'phone_clicks' => $metrics->sum('phone_clicks'),
                    'whatsapp_clicks' => $metrics->sum('whatsapp_clicks'),
                    'direction_clicks' => $metrics->sum('direction_clicks'),
                    'lead_count' => $metrics->sum('lead_count'),
                ],
            ],
        ]);
    }
}
