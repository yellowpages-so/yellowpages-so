<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class VerificationRequest extends Model
{
    use HasUuids;

    protected $table = 'verification.verification_requests';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'business_id',
        'claim_id',
        'reference_no',
        'requested_level_id',
        'status',
        'current_step',
        'risk_score',
        'submitted_by',
        'assigned_to',
        'submitted_at',
        'decided_at',
        'expires_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'risk_score' => 'integer',
            'submitted_at' => 'datetime',
            'decided_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
