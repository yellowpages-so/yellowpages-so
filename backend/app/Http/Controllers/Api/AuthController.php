<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The supplied information is invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $email = mb_strtolower(trim($data['email']));

        $exists = DB::table('iam.user_emails')
            ->whereRaw('LOWER(email::text) = ?', [$email])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'This email address is already registered.',
            ], 422);
        }

        try {
            $result = DB::transaction(function () use ($data, $email): array {
                $user = User::query()->create([
                    'status' => 'active',
                    'password_hash' => Hash::make($data['password']),
                ]);

                DB::table('iam.user_profiles')->insert([
                    'user_id' => $user->id,
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'display_name' => trim(
                        $data['first_name'].' '.$data['last_name']
                    ),
                    'locale' => 'en',
                    'timezone' => 'Africa/Mogadishu',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('iam.user_emails')->insert([
                    'id' => (string) str()->uuid(),
                    'user_id' => $user->id,
                    'email' => $email,
                    'is_primary' => true,
                    'verified_at' => null,
                    'created_at' => now(),
                ]);

                $token = $user->createToken(
                    $data['device_name'] ?? 'yellowpages-web'
                )->plainTextToken;

                return [
                    'user' => $this->formatUser($user),
                    'token' => $token,
                ];
            });

            return response()->json([
                'message' => 'Account created successfully.',
                ...$result,
            ], 201);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Account creation failed.',
                'error' => app()->isLocal()
                    ? $exception->getMessage()
                    : null,
            ], 500);
        }
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $email = mb_strtolower(trim($data['email']));

        $record = DB::table('iam.user_emails as emails')
            ->join('iam.users as users', 'users.id', '=', 'emails.user_id')
            ->whereRaw('LOWER(emails.email::text) = ?', [$email])
            ->whereNull('users.deleted_at')
            ->select('users.*')
            ->first();

        if (
            ! $record ||
            ! Hash::check($data['password'], $record->password_hash)
        ) {
            throw ValidationException::withMessages([
                'email' => ['The email address or password is incorrect.'],
            ]);
        }

        if ($record->status !== 'active') {
            return response()->json([
                'message' => 'This account is not active.',
            ], 403);
        }

        $user = User::query()->findOrFail($record->id);

        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        $token = $user->createToken(
            $data['device_name'] ?? 'yellowpages-web'
        )->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'user' => $this->formatUser($user),
            'token' => $token,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->formatUser($request->user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    private function formatUser(User $user): array
    {
        $profile = DB::table('iam.user_profiles')
            ->where('user_id', $user->id)
            ->first();

        $email = DB::table('iam.user_emails')
            ->where('user_id', $user->id)
            ->where('is_primary', true)
            ->value('email');

        return [
            'id' => $user->public_id,
            'first_name' => $profile?->first_name,
            'last_name' => $profile?->last_name,
            'display_name' => $profile?->display_name,
            'email' => $email,
            'status' => $user->status,
            'email_verified_at' => $user->email_verified_at,
            'created_at' => $user->created_at,
        ];
    }
}
