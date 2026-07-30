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
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid input', 'errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password_hash)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if (! $user->isAdmin()) {
            return response()->json([
                'message' => 'This account does not have System Administrator access to the admin portal.',
            ], 403);
        }

        if ($user->account_status !== 'Active') {
            return response()->json(['message' => 'Account is not active.'], 403);
        }

        // Revoke previous admin-portal tokens so a login always starts a fresh session
        $user->tokens()->where('name', 'admin-portal')->delete();

        $token = $user->createToken('admin-portal')->plainTextToken;

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
        ];
    }
}
