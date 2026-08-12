<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\PersonnelInviteMail;
use App\Models\Park;
use App\Models\Role;
use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserController extends Controller
{
    // Roles a Gamepark account is allowed to invite — park field staff only,
    // never UWA-level or system roles.
    private const GAMEPARK_INVITABLE_ROLES = ['Ranger', 'Community Wildlife Officer', 'Park Warden'];

    // Portal roles that have a corresponding role in the mobile app's Firebase custom
    // claims (see android-native-master-branch's UserRole.kt / AuthRepositoryImpl.mapRole)
    // - an invited user in one of these roles also gets a Firebase Auth account
    // provisioned so they can sign in to the mobile app with the same email.
    // Community Wildlife Officer/System Administrator/Gamepark Officer have no mobile
    // counterpart and are deliberately left out.
    private const MOBILE_ROLE_CLAIMS = [
        'Ranger' => 'ranger',
        'Park Warden' => 'warden',
        'UWA Official' => 'uwa_official',
    ];

    public function __construct(private FirebaseService $firebase) {}

    public function index(Request $request)
    {
        $query = User::with('roles');

        if ($request->filled('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('role_name', $request->role));
        }
        if ($request->filled('park_id')) {
            $query->where('park_id', $request->park_id);
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

    /**
     * Invite a new UWA staff member by email. UWA admins may invite for any
     * role and optionally assign a park (e.g. a Ranger stationed at a
     * specific park). See gameparkInvite() for the park-restricted version
     * used by the Gamepark portal.
     */
    public function invite(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'role_id' => ['required', 'exists:roles,role_id'],
            'park_id' => ['nullable', 'exists:parks,park_id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        return $this->sendInvite(
            firstName: $request->first_name,
            lastName: $request->last_name,
            email: $request->email,
            roleId: (int) $request->role_id,
            parkId: $request->park_id,
        );
    }

    // Gamepark personnel for the signed-in account's own park.
    public function gameparkIndex(Request $request)
    {
        $users = User::with('roles')
            ->where('park_id', $request->user()->park_id)
            ->latest('created_at')
            ->get(['user_id', 'first_name', 'last_name', 'email', 'phone_number', 'account_status', 'park_id']);

        return response()->json($users);
    }

    /**
     * A gamepark invites its own field staff. The park is always the
     * inviter's own park (never client-supplied), and only field-level
     * roles are allowed — no UWA-level or system roles from this portal.
     */
    public function gameparkInvite(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'role_id' => ['required', 'exists:roles,role_id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $role = Role::find($request->role_id);
        if (! $role || ! in_array($role->role_name, self::GAMEPARK_INVITABLE_ROLES, true)) {
            return response()->json([
                'message' => 'Gamepark accounts can only invite park field staff (Ranger, Community Wildlife Officer, or Park Warden).',
            ], 422);
        }

        return $this->sendInvite(
            firstName: $request->first_name,
            lastName: $request->last_name,
            email: $request->email,
            roleId: $role->role_id,
            parkId: $request->user()->park_id,
        );
    }

    private function sendInvite(string $firstName, string $lastName, string $email, int $roleId, ?int $parkId): \Illuminate\Http\JsonResponse
    {
        $temporaryPassword = Str::password(12);
        $roleName = Role::find($roleId)?->role_name ?? 'Personnel';
        $mobileRole = self::MOBILE_ROLE_CLAIMS[$roleName] ?? null;

        $user = DB::transaction(function () use ($firstName, $lastName, $email, $temporaryPassword, $roleId, $parkId, $mobileRole) {
            $user = User::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'password_hash' => Hash::make($temporaryPassword),
                'park_id' => $parkId,
                'account_status' => 'Active',
            ]);

            $user->roles()->attach($roleId);

            if ($mobileRole !== null) {
                $parkFirestoreId = $parkId !== null ? Park::find($parkId)?->firestore_id : null;
                $firebaseUid = null;
                try {
                    $firebaseUid = $this->firebase->provisionMobileAccount(
                        $email,
                        $user->full_name,
                        $mobileRole,
                        $parkFirestoreId,
                    );
                    $user->update(['firebase_uid' => $firebaseUid]);
                } catch (\Throwable $e) {
                    if ($firebaseUid !== null) {
                        try {
                            $this->firebase->auth()->deleteUser($firebaseUid);
                        } catch (\Throwable) {
                            // Best-effort cleanup; the MySQL transaction still rolls back.
                        }
                    }
                    throw new \RuntimeException('Firebase synchronization failed: ' . $e->getMessage(), 0, $e);
                }
            }

            return $user;
        });

        $user->load(['roles', 'park']);
        $portalUrl = rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/') . '/portal';

        $mailSent = true;
        $mailError = null;
        try {
            Mail::to($user->email)->send(new PersonnelInviteMail(
                recipientName: $user->first_name,
                recipientEmail: $user->email,
                temporaryPassword: $temporaryPassword,
                roleName: $roleName,
                portalUrl: $portalUrl,
                parkName: $user->park?->park_name,
                hasMobileAccount: $mobileRole !== null,
            ));
        } catch (\Throwable $e) {
            $mailSent = false;
            $mailError = $e->getMessage();
            Log::warning('Personnel invite email failed to send: ' . $mailError);
        }

        return response()->json([
            'user' => $user,
            'mail_sent' => $mailSent,
            'message' => $mailSent
                ? ($mobileRole !== null
                    ? 'Invitation email sent — they can sign in to the WildWatch mobile app with this email.'
                    : 'Invitation email sent.')
                : 'Account created, but the invite email could not be sent — check the mail configuration.',
            // Only surfaced when APP_DEBUG=true, to help diagnose a broken mail gateway without leaking details in production.
            'mail_error' => (! $mailSent && config('app.debug')) ? $mailError : null,
        ], 201);
    }
}
