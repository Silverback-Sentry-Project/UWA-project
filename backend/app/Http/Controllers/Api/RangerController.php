<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RangerController extends Controller
{
    protected $firebase;

    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    public function index()
    {
        $rangers = User::whereHas('roles', fn ($q) => $q->where('role_name', 'Ranger'))
            ->withCount([
                'incidentAssignments as active_assignments_count' => fn ($q) => $q->whereIn('assignment_status', ['Assigned', 'Accepted']),
            ])
            ->get(['user_id', 'first_name', 'last_name', 'phone_number', 'email', 'account_status', 'firebase_uid']);

        return response()->json($rangers);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone_number' => ['nullable', 'string', 'unique:users,phone_number'],
            'password' => ['required', 'string', 'min:8'],
            'park_id' => ['required', 'string'], // Firebase park ID or name
            'mysql_park_id' => ['nullable', 'exists:parks,park_id'], // If we want to link to MySQL park table
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        return DB::transaction(function () use ($request) {
            // 1. Create MySQL User
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone_number' => $request->phone_number,
                'password_hash' => Hash::make($request->password),
                'account_status' => 'Active',
            ]);

            // 2. Attach Ranger Role
            $rangerRole = Role::where('role_name', 'Ranger')->first();
            if ($rangerRole) {
                $user->roles()->attach($rangerRole->role_id);
            }

            try {
                // 3. Sync to Firebase
                $firebaseUid = $this->firebase->createRangerAccount(
                    $request->email,
                    $request->password,
                    $user->full_name,
                    $request->park_id
                );

                // 4. Update MySQL User with Firebase UID
                $user->update(['firebase_uid' => $firebaseUid]);

            } catch (\Exception $e) {
                // If Firebase fails, the DB transaction will rollback automatically
                // but we should probably log the error and return a useful response.
                throw new \Exception("Firebase synchronization failed: " . $e->getMessage());
            }

            return response()->json($user->load('roles'), 201);
        });
    }
}
