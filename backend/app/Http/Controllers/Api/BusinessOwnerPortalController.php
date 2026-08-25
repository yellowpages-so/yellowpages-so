<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBusinessAddressRequest;
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
use Illuminate\Support\Facades\Schema;
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

    public function storeAddress(
        StoreBusinessAddressRequest $request,
        Business $business
    ): JsonResponse {
        $this->service->assertManager($request->user(), $business);

        $data = $request->validated();

        $countryId = DB::table('directory.countries')
            ->where('iso2', 'SO')
            ->value('id');

        if (! $countryId) {
            return response()->json([
                'success' => false,
                'message' => 'Somalia country record was not found.',
            ], 422);
        }

        $city = DB::table('directory.cities')
            ->where('id', $data['city_id'])
            ->where('administrative_area_id', $data['administrative_area_id'])
            ->exists();

        if (! $city) {
            return response()->json([
                'success' => false,
                'message' => 'The selected city does not belong to the selected region.',
            ], 422);
        }

        if (! empty($data['district_id'])) {
            $district = DB::table('directory.districts')
                ->where('id', $data['district_id'])
                ->where('administrative_area_id', $data['administrative_area_id'])
                ->where('city_id', $data['city_id'])
                ->exists();

            if (! $district) {
                return response()->json([
                    'success' => false,
                    'message' => 'The selected district does not belong to the selected city.',
                ], 422);
            }
        }

        $id = (string) Str::uuid();

        DB::table('directory.addresses')->insert([
            'id' => $id,
            'country_id' => $countryId,
            'administrative_area_id' => $data['administrative_area_id'],
            'city_id' => $data['city_id'],
            'district_id' => $data['district_id'] ?? null,
            'neighbourhood_id' => null,
            'address_line1' => $data['address_line1'],
            'address_line2' => $data['address_line2'] ?? null,
            'landmark' => $data['landmark'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'location' => null,
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Address created successfully.',
            'data' => [
                'id' => $id,
            ],
        ], 201);
    }

    public function categories(
        Request $request,
        Business $business
    ): JsonResponse {
        $this->service->assertManager($request->user(), $business);

        $rows = DB::table('directory.business_categories as bc')
            ->join('directory.categories as c', 'c.id', '=', 'bc.category_id')
            ->where('bc.business_id', $business->id)
            ->orderByDesc('bc.is_primary')
            ->orderBy('c.name')
            ->get([
                'c.id',
                'c.name',
                'c.name_so',
                'c.slug',
                'bc.is_primary',
            ]);

        return response()->json([
            'success' => true,
            'data' => $rows,
        ]);
    }

    public function updateCategories(
        Request $request,
        Business $business
    ): JsonResponse {
        $this->service->assertManager($request->user(), $business);

        $validated = $request->validate([
            'primary_category_id' => ['required', 'uuid'],
            'secondary_category_ids' => ['nullable', 'array', 'max:10'],
            'secondary_category_ids.*' => ['uuid', 'distinct'],
        ]);

        $ids = collect([
            $validated['primary_category_id'],
            ...($validated['secondary_category_ids'] ?? []),
        ])->unique()->values();

        $existing = DB::table('directory.categories')
            ->whereIn('id', $ids)
            ->pluck('id');

        if ($existing->count() !== $ids->count()) {
            return response()->json([
                'success' => false,
                'message' => 'One or more selected categories are invalid.',
            ], 422);
        }

        DB::transaction(function () use (
            $business,
            $validated,
            $ids
        ): void {
            DB::table('directory.business_categories')
                ->where('business_id', $business->id)
                ->delete();

            foreach ($ids as $categoryId) {
                $row = [
                    'business_id' => $business->id,
                    'category_id' => $categoryId,
                    'is_primary' => $categoryId === $validated['primary_category_id'],
                ];

                if (
                    Schema::hasColumn(
                        'directory.business_categories',
                        'id'
                    )
                ) {
                    $row['id'] = (string) Str::uuid();
                }

                if (
                    Schema::hasColumn(
                        'directory.business_categories',
                        'created_at'
                    )
                ) {
                    $row['created_at'] = now();
                }

                if (
                    Schema::hasColumn(
                        'directory.business_categories',
                        'updated_at'
                    )
                ) {
                    $row['updated_at'] = now();
                }

                DB::table('directory.business_categories')
                    ->insert($row);
            }
        });

        $this->service->recalculateProgress($business);

        return response()->json([
            'success' => true,
            'message' => 'Business categories updated successfully.',
        ]);
    }

    public function verificationStatus(
        Request $request,
        Business $business
    ): JsonResponse {
        $this->service->assertManager($request->user(), $business);

        $latest = \App\Models\VerificationRequest::query()
            ->where('business_id', $business->id)
            ->orderByDesc('submitted_at')
            ->first();

        $requestData = null;

        if ($latest) {
            $level = DB::table('verification.verification_levels')
                ->where('id', $latest->requested_level_id)
                ->first([
                    'id',
                    'code',
                    'name',
                    'rank',
                    'description',
                ]);

            $documents = DB::table('verification.verification_documents')
                ->where('request_id', $latest->id)
                ->orderByDesc('created_at')
                ->get([
                    'id',
                    'document_type',
                    'document_number',
                    'issued_at',
                    'expires_at',
                    'status',
                    'original_name',
                    'mime_type',
                    'file_size',
                    'virus_scan_passed',
                    'reviewed_at',
                    'review_notes',
                    'created_at',
                ]);

            $requestData = array_merge(
                $latest->toArray(),
                [
                    'requested_level' => $level,
                    'documents' => $documents,
                ]
            );
        }

        $currentLevel = null;

        if (filled($business->verification_level_id)) {
            $currentLevel = DB::table('verification.verification_levels')
                ->where('id', $business->verification_level_id)
                ->first([
                    'id',
                    'code',
                    'name',
                    'rank',
                    'description',
                ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'is_verified' => filled($business->verification_level_id),
                'verification_level_id' => $business->verification_level_id,
                'current_level' => $currentLevel,
                'latest_request' => $requestData,
            ],
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
        $this->service->assertManager(
            $request->user(),
            $business
        );

        $branchId = DB::table(
            'directory.business_branches'
        )
            ->where('business_id', $business->id)
            ->orderByDesc('is_head_office')
            ->value('id');

        if (! $branchId) {
            return response()->json([
                'success' => false,
                'message' => 'Add a business branch before saving opening hours.',
            ], 422);
        }

        DB::transaction(function () use (
            $business,
            $branchId,
            $request
        ): void {
            $query = DB::table(
                'directory.business_opening_hours'
            );

            if (
                Schema::hasColumn(
                    'directory.business_opening_hours',
                    'business_id'
                )
            ) {
                $query->where(
                    'business_id',
                    $business->id
                );
            } else {
                $query->where('branch_id', $branchId);
            }

            $query->delete();

            foreach (
                $request->validated()['hours'] as $row
            ) {
                $record = [
                    'id' => (string) Str::uuid(),
                    'weekday' => $row['day_of_week'],
                    'is_closed' => $row['is_closed'] ?? false,
                    'opens_at' => $row['open_time'] ?? null,
                    'closes_at' => $row['close_time'] ?? null,
                ];

                if (
                    Schema::hasColumn(
                        'directory.business_opening_hours',
                        'business_id'
                    )
                ) {
                    $record['business_id'] = $business->id;
                } else {
                    $record['branch_id'] = $branchId;
                }

                DB::table(
                    'directory.business_opening_hours'
                )->insert($record);
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
