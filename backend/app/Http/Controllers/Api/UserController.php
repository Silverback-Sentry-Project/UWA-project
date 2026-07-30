<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('roles');

        if ($request->filled('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('role_name', $request->role));
        }

        return response()->json(
            $query->latest('created_at')->paginate($request->integer('per_page', 25))
        );
    }

    public function show(User $user)
    {
        return response()->json($user->load('roles'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'unique:users,email'],
            'phone_number' => ['nullable', 'string', 'unique:users,phone_number'],
            'password' => ['required', 'string', 'min:8'],
            'role_id' => ['required', 'exists:roles,role_id'],
            'account_status' => ['nullable', 'in:Pending,Active,Suspended'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (! $request->filled('email') && ! $request->filled('phone_number')) {
            return response()->json(['errors' => ['email' => ['Either email or phone_number is required.']]], 422);
        }

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'password_hash' => Hash::make($request->password),
            'account_status' => $request->account_status ?? 'Active',
        ]);

        $user->roles()->attach($request->role_id);

        return response()->json($user->load('roles'), 201);
    }

    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['sometimes', 'string', 'max:100'],
            'account_status' => ['sometimes', 'in:Pending,Active,Suspended'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user->update($validator->validated());

        return response()->json($user->fresh('roles'));
    }
}
