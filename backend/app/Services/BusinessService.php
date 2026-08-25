<?php

namespace App\Services;

use App\Domain\Directory\Contracts\BusinessRepository;
use App\Domain\Directory\DTO\CreateBusinessData;
use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Str;

class BusinessService
{
    public function __construct(
        private readonly BusinessRepository $businesses,
    ) {}

    public function create(
        User $owner,
        CreateBusinessData $data
    ): Business {
        return $this->businesses->create(
            $owner,
            $data
        );
    }

    public function update(
        Business $business,
        array $data
    ): Business {
        if (
            array_key_exists('trading_name', $data)
            && $data['trading_name'] !== $business->trading_name
        ) {
            $data['slug'] = $this->uniqueSlug(
                $data['trading_name'],
                $business->id
            );
        }

        $business->update($data);

        $business->update([
            'profile_completeness' => $this->calculateCompleteness(
                $business->fresh()->toArray()
            ),
        ]);

        return $business->fresh();
    }

    private function uniqueSlug(string $tradingName, ?string $ignoreId = null): string
    {
        $base = Str::slug($tradingName) ?: 'business';
        $slug = $base;
        $suffix = 2;

        while (
            Business::withTrashed()
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function calculateCompleteness(array $data): int
    {
        $fields = [
            'legal_name',
            'trading_name',
            'short_description',
            'description',
            'website_url',
            'primary_city_id',
        ];

        $completed = collect($fields)
            ->filter(fn (string $field) => filled($data[$field] ?? null))
            ->count();

        return (int) round(($completed / count($fields)) * 100);
    }
}
