<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Business extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $table = 'directory.businesses';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'public_id',
        'legal_name',
        'trading_name',
        'slug',
        'description',
        'short_description',
        'registration_number',
        'tax_number',
        'established_year',
        'status',
        'verification_level_id',
        'primary_city_id',
        'primary_address_id',
        'logo_url',
        'cover_url',
        'website_url',
        'profile_completeness',
        'created_by',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'established_year' => 'integer',
            'profile_completeness' => 'integer',
            'average_rating' => 'decimal:2',
            'review_count' => 'integer',
            'published_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'directory.business_members',
            'business_id',
            'user_id'
        )->withPivot(['role_code', 'status', 'joined_at']);
    }
}
