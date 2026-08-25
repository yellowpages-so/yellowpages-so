<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LocationController extends Controller
{
    public function regions(): JsonResponse
    {
        $rows = DB::table('directory.administrative_areas')
            ->where('area_type', 'region')
            ->where('active', true)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'name_so',
                'code',
                'slug',
            ]);

        return response()->json([
            'success' => true,
            'data' => $rows,
        ]);
    }

    public function cities(Request $request): JsonResponse
    {
        $query = DB::table('directory.cities')
            ->where('active', true);

        if ($request->filled('region_id')) {
            $query->where(
                'administrative_area_id',
                $request->string('region_id')->toString()
            );
        }

        $rows = $query
            ->orderByDesc('is_capital')
            ->orderBy('name')
            ->get([
                'id',
                'administrative_area_id',
                'name',
                'name_so',
                'code',
                'slug',
                'is_capital',
            ]);

        return response()->json([
            'success' => true,
            'data' => $rows,
        ]);
    }

    public function districts(Request $request): JsonResponse
    {
        $query = DB::table('directory.districts')
            ->where('active', true);

        if ($request->filled('city_id')) {
            $query->where(
                'city_id',
                $request->string('city_id')->toString()
            );
        }

        if ($request->filled('region_id')) {
            $query->where(
                'administrative_area_id',
                $request->string('region_id')->toString()
            );
        }

        $rows = $query
            ->orderBy('name')
            ->get([
                'id',
                'city_id',
                'administrative_area_id',
                'name',
                'name_so',
                'code',
                'slug',
            ]);

        return response()->json([
            'success' => true,
            'data' => $rows,
        ]);
    }
}
