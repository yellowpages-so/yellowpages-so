<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsReportingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AnalyticsReportingController extends Controller
{
    public function __construct(private readonly AnalyticsReportingService $service) {}

    public function track(Request $request): JsonResponse
    {
        $data = $request->validate([
            'business_id' => ['nullable', 'uuid'],
            'event_type' => ['required', 'string', 'max:100'],
            'source' => ['nullable', 'string', 'max:50'],
            'session_id' => ['nullable', 'string', 'max:255'],
            'entity_type' => ['nullable', 'string', 'max:100'],
            'entity_id' => ['nullable', 'uuid'],
            'value' => ['nullable', 'numeric'],
            'currency' => ['nullable', 'string', 'size:3'],
            'dimensions' => ['nullable', 'array'],
        ]);

        $this->service->track($data, $request->user());

        return response()->json(['success' => true, 'message' => 'Event recorded.'], 201);
    }

    public function dashboard(Request $request, string $businessId): JsonResponse
    {
        try {
            $data = $this->service->dashboard(
                $request->user(),
                $businessId,
                (int) $request->integer('days', 30)
            );
        } catch (RuntimeException $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 403);
        }

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function reports(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => DB::table('reporting.saved_reports')
                ->where('user_id', $request->user()->id)
                ->orderByDesc('created_at')
                ->get(),
        ]);
    }

    public function saveReport(Request $request): JsonResponse
    {
        $data = $request->validate([
            'business_id' => ['nullable', 'uuid'],
            'name' => ['required', 'string', 'max:255'],
            'report_type' => ['required', 'string', 'max:100'],
            'filters' => ['nullable', 'array'],
            'columns' => ['nullable', 'array'],
            'visualization' => ['nullable', 'in:table,line,bar,pie,kpi'],
            'shared' => ['boolean'],
        ]);

        return response()->json([
            'success' => true,
            'data' => ['id' => $this->service->saveReport($request->user(), $data)],
        ], 201);
    }
}
