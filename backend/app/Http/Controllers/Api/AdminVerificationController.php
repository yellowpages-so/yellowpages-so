<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminVerificationDecisionRequest;
use App\Models\VerificationRequest;
use App\Services\VerificationDocumentSecurityService;
use App\Services\VerificationService;
use App\Support\PlatformRoles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminVerificationController extends Controller
{
    public function __construct(
        private readonly VerificationService $verificationService,
        private readonly VerificationDocumentSecurityService $securityService
    ) {}

    private function authorizeReviewer(
        Request $request
    ): void {
        abort_unless(
            PlatformRoles::hasAny(
                $request->user(),
                [
                    'verifier',
                    'administrator',
                    'super_administrator',
                ]
            ),
            403,
            'You are not authorised to review verification requests.'
        );
    }

    public function index(
        Request $request
    ): JsonResponse {
        $this->authorizeReviewer($request);

        $requests = DB::table(
            'verification.verification_requests as requests'
        )
            ->join(
                'directory.businesses as businesses',
                'businesses.id',
                '=',
                'requests.business_id'
            )
            ->leftJoin(
                'iam.user_profiles as profiles',
                'profiles.user_id',
                '=',
                'requests.submitted_by'
            )
            ->leftJoin(
                'verification.verification_levels as levels',
                'levels.id',
                '=',
                'requests.requested_level_id'
            )
            ->select([
                'requests.*',
                'businesses.public_id as business_public_id',
                'businesses.trading_name',
                'profiles.display_name as submitted_by_name',
                'levels.code as requested_level_code',
                'levels.name as requested_level_name',
            ])
            ->orderByDesc(
                'requests.submitted_at'
            )
            ->paginate(25);

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }

    public function show(
        Request $request,
        VerificationRequest $verificationRequest
    ): JsonResponse {
        $this->authorizeReviewer($request);

        $business = DB::table(
            'directory.businesses'
        )
            ->where(
                'id',
                $verificationRequest->business_id
            )
            ->first([
                'id',
                'public_id',
                'trading_name',
                'legal_name',
                'slug',
                'status',
                'verification_level_id',
            ]);

        $requestedLevel = DB::table(
            'verification.verification_levels'
        )
            ->where(
                'id',
                $verificationRequest
                    ->requested_level_id
            )
            ->first([
                'id',
                'code',
                'name',
                'rank',
                'description',
            ]);

        $documents = DB::table(
            'verification.verification_documents'
        )
            ->where(
                'request_id',
                $verificationRequest->id
            )
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
                'checksum_sha256',
                'virus_scan_passed',
                'virus_scan_status',
                'virus_scanned_at',
                'reviewed_at',
                'reviewed_by',
                'review_notes',
                'created_at',
            ]);

        $checks = DB::table(
            'verification.verification_checks'
        )
            ->where(
                'request_id',
                $verificationRequest->id
            )
            ->orderBy('check_type')
            ->get();

        $decision = DB::table(
            'verification.verification_decisions'
        )
            ->where(
                'request_id',
                $verificationRequest->id
            )
            ->first();

        $history = DB::table(
            'verification.verification_history'
        )
            ->where(
                'request_id',
                $verificationRequest->id
            )
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'request' =>
                    $verificationRequest,
                'business' => $business,
                'requested_level' =>
                    $requestedLevel,
                'documents' => $documents,
                'checks' => $checks,
                'decision' => $decision,
                'history' => $history,
            ],
        ]);
    }

    public function downloadDocument(
        Request $request,
        VerificationRequest $verificationRequest,
        string $documentId
    ): StreamedResponse {
        $this->authorizeReviewer($request);

        $document = DB::table(
            'verification.verification_documents'
        )
            ->where('id', $documentId)
            ->where(
                'request_id',
                $verificationRequest->id
            )
            ->first();

        abort_unless(
            $document,
            404,
            'Verification document not found.'
        );

        abort_unless(
            Storage::disk('local')->exists(
                $document->storage_key
            ),
            404,
            'Verification document file not found.'
        );

        $this->audit(
            $request,
            'verification.document_downloaded',
            'verification_document',
            $document->id,
            [
                'request_id' =>
                    $verificationRequest->id,
            ]
        );

        return Storage::disk('local')->download(
            $document->storage_key,
            $document->original_name
                ?: 'verification-document'
        );
    }

    public function scanDocument(
        Request $request,
        VerificationRequest $verificationRequest,
        string $documentId
    ): JsonResponse {
        $this->authorizeReviewer($request);

        $document = DB::table(
            'verification.verification_documents'
        )
            ->where('id', $documentId)
            ->where(
                'request_id',
                $verificationRequest->id
            )
            ->first();

        abort_unless(
            $document,
            404,
            'Verification document not found.'
        );

        try {
            $result = $this->securityService
                ->scan($document->storage_key);
        } catch (\Throwable $exception) {
            DB::table(
                'verification.verification_documents'
            )
                ->where('id', $document->id)
                ->update([
                    'virus_scan_status' => 'error',
                    'virus_scan_passed' => false,
                    'virus_scanned_at' => now(),
                ]);

            return response()->json([
                'success' => false,
                'message' =>
                    $exception->getMessage(),
            ], 422);
        }

        DB::table(
            'verification.verification_documents'
        )
            ->where('id', $document->id)
            ->update([
                'virus_scan_status' =>
                    $result['status'],
                'virus_scan_passed' =>
                    $result['clean'],
                'virus_scanned_at' => now(),
                'status' => $result['clean']
                    ? $document->status
                    : 'rejected',
                'review_notes' =>
                    $result['clean']
                    ? $document->review_notes
                    : 'Security scan detected a threat.',
            ]);

        $this->recordHistory(
            $verificationRequest,
            $request->user()->id,
            'document_security_scan',
            null,
            $result['status'],
            [
                'document_id' =>
                    $document->id,
                'scan_status' =>
                    $result['status'],
            ]
        );

        $this->audit(
            $request,
            'verification.document_scanned',
            'verification_document',
            $document->id,
            [
                'request_id' =>
                    $verificationRequest->id,
                'scan_status' =>
                    $result['status'],
            ]
        );

        return response()->json([
            'success' => true,
            'message' => $result['clean']
                ? 'Document security scan passed.'
                : 'Document security scan detected a threat.',
            'data' => [
                'status' =>
                    $result['status'],
            ],
        ]);
    }

    public function reviewDocument(
        Request $request,
        VerificationRequest $verificationRequest,
        string $documentId
    ): JsonResponse {
        $this->authorizeReviewer($request);

        $validated = $request->validate([
            'status' => [
                'required',
                'in:accepted,rejected,information_requested',
            ],
            'review_notes' => [
                'nullable',
                'string',
                'max:3000',
            ],
        ]);

        $document = DB::table(
            'verification.verification_documents'
        )
            ->where('id', $documentId)
            ->where(
                'request_id',
                $verificationRequest->id
            )
            ->first();

        abort_unless(
            $document,
            404,
            'Verification document not found.'
        );

        if (
            $validated['status'] === 'accepted'
            && $document->virus_scan_status
                !== 'clean'
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Run and pass the document security scan before accepting this document.',
            ], 422);
        }

        DB::table(
            'verification.verification_documents'
        )
            ->where('id', $document->id)
            ->update([
                'status' =>
                    $validated['status'],
                'reviewed_at' => now(),
                'reviewed_by' =>
                    $request->user()->id,
                'review_notes' =>
                    $validated['review_notes']
                    ?? null,
            ]);

        if (
            $validated['status']
            === 'information_requested'
        ) {
            $verificationRequest->update([
                'status' =>
                    'information_requested',
                'current_step' =>
                    'information_requested',
            ]);
        }

        $this->recordHistory(
            $verificationRequest,
            $request->user()->id,
            'document_reviewed',
            $document->status,
            $validated['status'],
            [
                'document_id' =>
                    $document->id,
                'review_notes' =>
                    $validated['review_notes']
                    ?? null,
            ]
        );

        $this->audit(
            $request,
            'verification.document_reviewed',
            'verification_document',
            $document->id,
            [
                'request_id' =>
                    $verificationRequest->id,
                'status' =>
                    $validated['status'],
            ]
        );

        return response()->json([
            'success' => true,
            'message' =>
                'Document review recorded.',
        ]);
    }

    public function updateCheck(
        Request $request,
        VerificationRequest $verificationRequest,
        string $checkId
    ): JsonResponse {
        $this->authorizeReviewer($request);

        $validated = $request->validate([
            'status' => [
                'required',
                'in:pending,passed,failed,information_requested',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:3000',
            ],
        ]);

        $check = DB::table(
            'verification.verification_checks'
        )
            ->where('id', $checkId)
            ->where(
                'request_id',
                $verificationRequest->id
            )
            ->first();

        abort_unless(
            $check,
            404,
            'Verification check not found.'
        );

        DB::table(
            'verification.verification_checks'
        )
            ->where('id', $check->id)
            ->update([
                'status' =>
                    $validated['status'],
                'checked_by' =>
                    $request->user()->id,
                'checked_at' => now(),
                'notes' =>
                    $validated['notes']
                    ?? null,
            ]);

        if (
            $validated['status']
            === 'information_requested'
        ) {
            $verificationRequest->update([
                'status' =>
                    'information_requested',
                'current_step' =>
                    'information_requested',
            ]);
        }

        $this->recordHistory(
            $verificationRequest,
            $request->user()->id,
            'verification_check_updated',
            $check->status,
            $validated['status'],
            [
                'check_id' => $check->id,
                'check_type' =>
                    $check->check_type,
                'notes' =>
                    $validated['notes']
                    ?? null,
            ]
        );

        $this->audit(
            $request,
            'verification.check_updated',
            'verification_check',
            $check->id,
            [
                'request_id' =>
                    $verificationRequest->id,
                'check_type' =>
                    $check->check_type,
                'status' =>
                    $validated['status'],
            ]
        );

        return response()->json([
            'success' => true,
            'message' =>
                'Verification check updated.',
        ]);
    }

    public function decide(
        AdminVerificationDecisionRequest $request,
        VerificationRequest $verificationRequest
    ): JsonResponse {
        $this->authorizeReviewer($request);

        $validated = $request->validated();

        if (
            $validated['decision']
            === 'approved'
        ) {
            $gate = $this->verificationService
                ->approvalGate(
                    $verificationRequest,
                    $validated[
                        'approved_level_code'
                    ]
                );

            if (! $gate['allowed']) {
                return response()->json([
                    'success' => false,
                    'message' =>
                        'Verification approval requirements are not complete.',
                    'data' => $gate,
                ], 422);
            }
        }

        $verification =
            $this->verificationService->decide(
                $verificationRequest,
                $request->user(),
                $validated
            );

        return response()->json([
            'success' => true,
            'message' =>
                'Verification decision recorded.',
            'data' => $verification,
        ]);
    }

    private function recordHistory(
        VerificationRequest $request,
        ?string $actorUserId,
        string $eventType,
        ?string $oldStatus,
        ?string $newStatus,
        array $metadata
    ): void {
        DB::table(
            'verification.verification_history'
        )->insert([
            'id' => (string) Str::uuid(),
            'business_id' =>
                $request->business_id,
            'request_id' => $request->id,
            'actor_user_id' =>
                $actorUserId,
            'event_type' => $eventType,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'metadata' =>
                json_encode($metadata),
            'created_at' => now(),
        ]);
    }

    private function audit(
        Request $request,
        string $action,
        string $entityType,
        string $entityId,
        array $data
    ): void {
        DB::table('system.audit_logs')->insert([
            'actor_user_id' =>
                $request->user()->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'after_data' => json_encode($data),
            'created_at' => now(),
        ]);
    }
}
