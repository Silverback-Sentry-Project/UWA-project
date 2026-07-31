<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'users';
    protected $primaryKey = 'user_id';
    public $timestamps = false;

    protected $fillable = [
        'first_name', 'last_name', 'phone_number', 'email',
        'password_hash', 'preferred_language', 'account_status',
        'email_verified', 'phone_verified', 'park_id',
    ];

    protected $hidden = ['password_hash'];

    protected $casts = [
        'email_verified' => 'boolean',
        'phone_verified' => 'boolean',
        'created_at' => 'datetime',
    ];

    // Laravel's auth internals look for "password" by default — WildWatch's
    // schema calls the column password_hash, so we point Auth at it here.
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id')
            ->withPivot('assigned_at');
    }

    public function incidentAssignments()
    {
        return $this->hasMany(IncidentAssignment::class, 'ranger_id', 'user_id');
    }

    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('role_name', $roleName)->exists();
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('System Administrator');
    }

    public function isGamepark(): bool
    {
        return $this->hasRole('Gamepark Officer') && $this->park_id !== null;
    }

    public function park()
    {
        return $this->belongsTo(Park::class, 'park_id', 'park_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
