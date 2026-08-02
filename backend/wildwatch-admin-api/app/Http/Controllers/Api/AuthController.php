<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $accountType = $request->input('account_type', 'uwa');

        $validator = Validator::make($request->all(), [
            'email' => [$accountType === 'gamepark' ? 'nullable' : 'required', 'email'],
            'password' => ['required', 'string'],
            'account_type' => ['nullable', 'in:uwa,gamepark'],
            'park_id' => [$accountType === 'gamepark' ? 'required' : 'nullable', 'exists:parks,park_id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid input', 'errors' => $validator->errors()], 422);
        }

        if ($accountType === 'gamepark') {
            $user = User::where('park_id', $request->park_id)->first();
        } else {
            $user = User::where('email', $request->email)->first();
        }

        if (! $user || ! Hash::check($request->password, $user->password_hash)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if ($accountType === 'gamepark') {
            if (! $user->isGamepark()) {
                return response()->json([
                    'message' => 'This account is not registered as a Gamepark account.',
                ], 403);
            }
        } elseif (! $user->isAdmin()) {
            return response()->json([
                'message' => 'This account does not have System Administrator access to the admin portal.',
            ], 403);
        }

        if ($user->account_status !== 'Active') {
            return response()->json(['message' => 'Account is not active.'], 403);
        }

        // Revoke previous tokens for this portal so a login always starts a fresh session.
        // No refresh/remember-me is issued anywhere, so logging out always requires a fresh sign-in.
        $tokenName = $accountType === 'gamepark' ? 'gamepark-portal' : 'admin-portal';
        $user->tokens()->where('name', $tokenName)->delete();

        $token = $user->createToken($tokenName)->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->formatUser($user),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request)
    {
        return response()->json($this->formatUser($request->user()));
    }

    private function formatUser(User $user): array
    {
        return [
            'user_id' => $user->user_id,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'roles' => $user->roles()->pluck('role_name'),
            'account_type' => $user->isGamepark() ? 'gamepark' : 'uwa',
            'park' => $user->isGamepark() ? [
                'park_id' => $user->park?->park_id,
                'park_name' => $user->park?->park_name,
            ] : null,
        ];
    }
}
