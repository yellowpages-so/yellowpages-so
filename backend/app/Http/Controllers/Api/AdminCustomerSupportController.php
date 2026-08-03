<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CustomerSupportService;
use App\Support\AdminAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AdminCustomerSupportController extends Controller
{
    public function __construct(
        private readonly CustomerSupportService $service
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        AdminAccess::authorize($request->user());

        return response()->json([
            'success' => true,
            'data' => [
                'open_tickets' => DB::table('support.tickets')
                    ->whereIn('status', [
                        'open',
                        'in_progress',
                        'pending_customer',
                    ])
                    ->count(),
                'urgent_tickets' => DB::table('support.tickets')
                    ->where('priority', 'urgent')
                    ->whereNotIn('status', ['resolved', 'closed'])
                    ->count(),
                'overdue_response' => DB::table('support.tickets')
                    ->whereNull('first_responded_at')
                    ->where('first_response_due_at', '<', now())
                    ->count(),
                'overdue_resolution' => DB::table('support.tickets')
                    ->whereNull('resolved_at')
                    ->where('resolution_due_at', '<', now())
                    ->count(),
                'average_csat' => (float) (
                    DB::table('support.surveys')
                        ->where('survey_type', 'csat')
                        ->avg('score') ?? 0
                ),
            ],
        ]);
    }

    public function tickets(Request $request): JsonResponse
    {
        AdminAccess::authorize($request->user());

        return response()->json([
            'success' => true,
            'data' => DB::table('support.tickets')
                ->orderByRaw("
                    CASE priority
                        WHEN 'urgent' THEN 1
                        WHEN 'high' THEN 2
                        WHEN 'normal' THEN 3
                        ELSE 4
                    END
                ")
                ->orderBy('created_at')
                ->paginate(50),
        ]);
    }

    public function reply(
        Request $request,
        string $ticketId
    ): JsonResponse {
        AdminAccess::authorize($request->user());

        $data = $request->validate([
            'body' => ['required', 'string', 'max:10000'],
            'internal' => ['boolean'],
        ]);

        try {
            $id = $this->service->reply(
                $request->user(),
                $ticketId,
                $data['body'],
                $data['internal'] ?? false
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Reply added.',
            'data' => ['id' => $id],
        ], 201);
    }

    public function update(
        Request $request,
        string $ticketId
    ): JsonResponse {
        AdminAccess::authorize($request->user());

        $data = $request->validate([
            'status' => [
                'nullable',
                'in:open,in_progress,pending_customer,resolved,closed',
            ],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
            'assigned_to' => ['nullable', 'uuid'],
            'queue_id' => ['nullable', 'uuid'],
        ]);

        $this->service->updateTicket(
            $request->user(),
            $ticketId,
            $data
        );

        return response()->json([
            'success' => true,
            'message' => 'Ticket updated.',
        ]);
    }
}
