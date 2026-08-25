<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DirectorySearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicDirectoryController extends Controller
{
    public function __construct(
        private readonly DirectorySearchService $searchService
    ) {}

    public function search(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->searchService->search($filters),
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $business = DB::table('directory.businesses as b')
            ->where('b.slug', $slug)
            ->whereNull('b.deleted_at')
            ->select('b.*')
            ->first();

        abort_if(! $business, 404, 'Business not found.');

        $business->is_verified = filled($business->verification_level_id);

        $business->categories = DB::table(
            'directory.business_categories as bc'
        )
            ->join(
                'directory.categories as c',
                'c.id',
                '=',
                'bc.category_id'
            )
            ->where('bc.business_id', $business->id)
            ->get([
                'c.name',
                'c.name_so',
                'c.slug',
                'bc.is_primary',
            ]);

        $business->services = DB::table(
            'directory.business_services as bs'
        )
            ->leftJoin(
                'directory.services as s',
                's.id',
                '=',
                'bs.service_id'
            )
            ->where('bs.business_id', $business->id)
            ->where('bs.active', true)
            ->orderByRaw(
                'COALESCE(s.name, bs.custom_name) ASC'
            )
            ->get([
                'bs.id',
                's.name',
                's.name_so',
                's.slug',
                'bs.custom_name',
                'bs.description',
                'bs.price_from',
                'bs.currency',
            ]);

        $business->contacts = DB::table(
            'directory.business_contacts'
        )
            ->where('business_id', $business->id)
            ->where('is_public', true)
            ->orderByDesc('is_primary')
            ->orderBy('contact_type')
            ->get([
                'contact_type',
                'label',
                'value',
                'is_primary',
            ]);

        $branch = DB::table(
            'directory.business_branches as br'
        )
            ->leftJoin(
                'directory.addresses as a',
                'a.id',
                '=',
                'br.address_id'
            )
            ->leftJoin(
                'directory.administrative_areas as r',
                'r.id',
                '=',
                'a.administrative_area_id'
            )
            ->leftJoin(
                'directory.cities as c',
                'c.id',
                '=',
                'a.city_id'
            )
            ->leftJoin(
                'directory.districts as d',
                'd.id',
                '=',
                'a.district_id'
            )
            ->where('br.business_id', $business->id)
            ->where('br.status', 'active')
            ->orderByRaw(
                'CASE WHEN br.address_id IS NOT NULL THEN 0 ELSE 1 END'
            )
            ->orderByDesc('br.is_head_office')
            ->orderBy('br.created_at')
            ->select([
                'br.id',
                'br.name',
                'br.phone',
                'br.email',
                'br.is_head_office',
                'br.status',
                'br.address_id',
                'a.address_line1',
                'a.address_line2',
                'a.landmark',
                'a.postal_code',
                'r.id as region_id',
                'r.name as region_name',
                'r.name_so as region_name_so',
                'c.id as city_id',
                'c.name as city_name',
                'c.name_so as city_name_so',
                'd.id as district_id',
                'd.name as district_name',
                'd.name_so as district_name_so',
            ])
            ->first();

        $business->branch = $branch;

        $hoursBranchId = null;

        if ($branch) {
            $hasHours = DB::table(
                'directory.business_opening_hours'
            )
                ->where('branch_id', $branch->id)
                ->exists();

            if ($hasHours) {
                $hoursBranchId = $branch->id;
            }
        }

        if (! $hoursBranchId) {
            $hoursBranchId = DB::table(
                'directory.business_branches as br'
            )
                ->join(
                    'directory.business_opening_hours as h',
                    'h.branch_id',
                    '=',
                    'br.id'
                )
                ->where('br.business_id', $business->id)
                ->where('br.status', 'active')
                ->orderByDesc('br.is_head_office')
                ->value('br.id');
        }

        $business->opening_hours = $hoursBranchId
            ? DB::table('directory.business_opening_hours')
                ->where('branch_id', $hoursBranchId)
                ->orderBy('weekday')
                ->get([
                    'weekday',
                    'opens_at',
                    'closes_at',
                    'is_closed',
                ])
            : collect();

        $business->city = $branch?->city_name;
        $business->district = $branch?->district_name;
        $business->region = $branch?->region_name;

        return response()->json([
            'success' => true,
            'data' => $business,
        ]);
    }
}
