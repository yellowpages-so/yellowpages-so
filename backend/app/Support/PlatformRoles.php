<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class PlatformRoles
{
    public static function hasAny(User $user, array $roles): bool
    {
        return DB::table('iam.user_roles as user_roles')
            ->join('iam.roles as roles', 'roles.id', '=', 'user_roles.role_id')
            ->where('user_roles.user_id', $user->id)
            ->whereIn('roles.code', $roles)
            ->exists();
    }
}
