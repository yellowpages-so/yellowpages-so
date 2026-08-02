<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessClaim extends Model
{
    protected $table = 'directory.business_claims';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'business_id',
        'claimant_user_id',
        'claim_type',
        'claim_reason',
        'contact_email',
        'contact_phone',
        'status',
        'evidence_summary',
        'assigned_to',
        'reviewed_by',
        'reviewed_at',
        'submitted_at',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'submitted_at' => 'datetime',
            'decided_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
