<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['tenant_id', 'role_id', 'name', 'email', 'phone', 'department', 'password', 'role', 'is_active', 'last_login_at', 'password_changed_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'password_changed_at' => 'datetime',
        ];
    }

    public function assignedRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function hasPermission(string $permission): bool
    {
        $normalizedRole = strtolower(trim((string) $this->role));
        $normalizedRole = str_replace([' ', '-'], '_', $normalizedRole);

        if (in_array($normalizedRole, ['super_admin', 'superadmin', 'company_admin', 'admin', 'administrator', 'owner'], true)) {
            return true;
        }

        return (bool) $this->assignedRole?->hasPermission($permission);
    }

    public function isAdministrator(): bool
    {
        return $this->hasPermission('users.manage')
            || $this->hasPermission('roles.manage')
            || $this->hasPermission('security.manage');
    }
}
