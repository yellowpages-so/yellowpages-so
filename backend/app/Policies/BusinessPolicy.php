<?php

namespace App\Policies;

use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BusinessPolicy
{
    public function view(User $user, Business $business): bool
    {
        return $this->isActiveMember($user, $business);
    }

    public function update(User $user, Business $business): bool
    {
        return $this->isActiveMember($user, $business);
    }

    public function delete(User $user, Business $business): bool
    {
        return DB::table('directory.business_members')
            ->where('business_id', $business->id)
            ->where('user_id', $user->id)
            ->where('role_code', 'owner')
            ->where('status', 'active')
            ->exists();
    }

    private function isActiveMember(User $user, Business $business): bool
    {
        return DB::table('directory.business_members')
            ->where('business_id', $business->id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();
    }
}
