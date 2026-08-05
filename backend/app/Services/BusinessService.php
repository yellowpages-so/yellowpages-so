<?php

namespace App\Services;

use App\Domain\Directory\DTO\CreateBusinessData;
use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BusinessService
{
    public function create(
        User $owner,
        CreateBusinessData $data
    ): Business {
        return DB::transaction(
            function () use ($owner, $data): Business {
                $attributes = $data->toArray();

                $business = Business::query()->create([
                    ...$attributes,
                    'public_id' => (string) Str::ulid(),
                    'slug' => $this->uniqueSlug(
                        $data->tradingName
                    ),
                    'status' => 'draft',
                    'profile_completeness' => $this->calculateCompleteness(
                        $attributes
                    ),
                    'created_by' => $owner->id,
                ]);

                DB::table(
                    'directory.business_members'
                )->insert([
                    'id' => (string) Str::uuid(),
                    'business_id' => $business->id,
                    'user_id' => $owner->id,
                    'role_code' => 'owner',
                    'status' => 'active',
                    'joined_at' => now(),
                    'created_at' => now(),
                ]);

                return $business->fresh();
            }
        );
    }

    public function update(Business $business, array $data): Business
    {
        if (array_key_exists('trading_name', $data) && $data['trading_name'] !== $business->trading_name) {
            $data['slug'] = $this->uniqueSlug($data['trading_name'], $business->id);
        }

        $business->update($data);

        $business->update([
            'profile_completeness' => $this->calculateCompleteness($business->fresh()->toArray()),
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
