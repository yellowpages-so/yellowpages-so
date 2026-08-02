<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\AdminAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminLeadMarketplaceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        AdminAccess::authorize($request->user());

        $requests = DB::table('leads.quote_requests as requests')
            ->leftJoin('directory.categories as categories', 'categories.id', '=', 'requests.category_id')
            ->leftJoin('directory.cities as cities', 'cities.id', '=', 'requests.city_id')
            ->select([
                'requests.*',
                'categories.name as category_name',
                'cities.name as city_name',
            ])
            ->orderByDesc('requests.created_at')
            ->paginate(25);

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }
}
