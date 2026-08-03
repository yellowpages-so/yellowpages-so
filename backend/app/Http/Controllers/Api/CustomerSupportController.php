<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CustomerSupportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CustomerSupportController extends Controller
{
    public function __construct(
        private readonly CustomerSupportService $service
    ) {}

    public function createTicket(Request $request): JsonResponse
    {
        $data = $request->validate([
            'business_id' => ['nullable', 'uuid'],
            'queue_code' => ['nullable', 'string', 'max:100'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'channel' => ['nullable', 'in:web,email,phone,chat,whatsapp'],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
            'requester_name' => ['nullable', 'string', 'max:255'],
            'requester_email' => ['nullable', 'email', 'max:255'],
            'requester_phone' => ['nullable', 'string', 'max:50'],
            'metadata' => ['nullable', 'array'],
        ]);

        try {
            $ticket = $this->service->createTicket(
                $request->user(),
                $data
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Support ticket created.',
            'data' => $ticket,
        ], 201);
    }

    public function myTickets(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => DB::table('support.tickets')
                ->where('requester_user_id', $request->user()->id)
                ->orderByDesc('created_at')
                ->paginate(25),
        ]);
    }

    public function ticket(
        Request $request,
        string $ticketId
    ): JsonResponse {
        $ticket = DB::table('support.tickets')
            ->where('id', $ticketId)
            ->where('requester_user_id', $request->user()->id)
            ->first();

        abort_unless($ticket, 404);

        return response()->json([
            'success' => true,
            'data' => [
                'ticket' => $ticket,
                'messages' => DB::table('support.ticket_messages')
                    ->where('ticket_id', $ticketId)
                    ->where('internal', false)
                    ->orderBy('created_at')
                    ->get(),
            ],
        ]);
    }

    public function articles(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->publicArticles(
                $request->string('q')->toString() ?: null
            ),
        ]);
    }

    public function faqs(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => DB::table('support.faqs')
                ->where('active', true)
                ->orderByDesc('featured')
                ->orderBy('sort_order')
                ->get(),
        ]);
    }
}
