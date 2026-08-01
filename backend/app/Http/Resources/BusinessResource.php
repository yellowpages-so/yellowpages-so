<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'legal_name' => $this->legal_name,
            'trading_name' => $this->trading_name,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'registration_number' => $this->registration_number,
            'tax_number' => $this->tax_number,
            'established_year' => $this->established_year,
            'status' => $this->status,
            'verification_level_id' => $this->verification_level_id,
            'primary_city_id' => $this->primary_city_id,
            'logo_url' => $this->logo_url,
            'cover_url' => $this->cover_url,
            'website_url' => $this->website_url,
            'profile_completeness' => $this->profile_completeness,
            'average_rating' => $this->average_rating,
            'review_count' => $this->review_count,
            'published_at' => $this->published_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
