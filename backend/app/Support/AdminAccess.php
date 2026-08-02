<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdminAccess
{
    public static function authorize(User $user): void
    {
        $allowed = DB::table('iam.user_roles as user_roles')
            ->join('iam.roles as roles', 'roles.id', '=', 'user_roles.role_id')
            ->where('user_roles.user_id', $user->id)
            ->whereIn('roles.code', [
                'moderator',
                'verifier',
                'administrator',
                'super_administrator',
            ])
            ->exists();

        abort_unless($allowed, 403, 'Administrator access required.');
    }
}
