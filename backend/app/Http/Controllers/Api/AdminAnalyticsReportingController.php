<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\AdminAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAnalyticsReportingController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        AdminAccess::authorize($request->user());

        return response()->json([
            'success' => true,
            'data' => [
                'businesses' => DB::table('directory.businesses')->whereNull('deleted_at')->count(),
                'users' => DB::table('iam.users')->count(),
                'events' => DB::table('reporting.events')->count(),
                'saved_reports' => DB::table('reporting.saved_reports')->count(),
            ],
        ]);
    }
}
