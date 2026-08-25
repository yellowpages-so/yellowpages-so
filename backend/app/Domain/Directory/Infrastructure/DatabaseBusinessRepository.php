<?php

namespace App\Domain\Directory\Infrastructure;

use App\Domain\Directory\Contracts\BusinessRepository;
use App\Domain\Directory\DTO\CreateBusinessData;
use App\Domain\Directory\DTO\UpdateBusinessData;
use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseBusinessRepository implements BusinessRepository
{
    public function create(
        User $owner,
        CreateBusinessData $data
    ): Business {
        $attributes = $data->toArray();

        $business = Business::query()->create([
            ...$attributes,
            'public_id' => (string) Str::ulid(),
            'slug' => $this->uniqueSlug($data->tradingName),
            'status' => 'draft',
            'profile_completeness' => 0,
            'created_by' => $owner->id,
        ]);

        DB::table('directory.business_members')->insert([
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

    public function update(
        Business $business,
        UpdateBusinessData $data
    ): Business {
        $business->fill($data->toArray());
        $business->save();

        return $business->fresh();
    }

    public function archive(
        Business $business
    ): void {
        $business->update([
            'status' => 'archived',
        ]);
    }

    public function find(
        string $publicId
    ): ?Business {
        return Business::query()
            ->where('public_id', $publicId)
            ->first();
    }

    private function uniqueSlug(
        string $tradingName,
        ?string $ignoreId = null
    ): string {
        $base = Str::slug($tradingName)
            ?: 'business';

        $slug = $base;
        $suffix = 2;

        while (
            DB::table('directory.businesses')
                ->when(
                    $ignoreId,
                    fn ($query) => $query
                        ->where('id', '!=', $ignoreId)
                )
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
