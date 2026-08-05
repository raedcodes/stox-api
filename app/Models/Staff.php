<?php

namespace App\Models;

use App\Enums\UserRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

/**
 * Manager/Cashier/StoreKeeper accounts. Lives on this tenant's own database
 * connection (the default one — no CentralConnection trait), unlike Owner/
 * SuperAdmin (App\Models\User), which lives centrally. See the staff-move
 * migration plan for why: staff never move between businesses and the
 * tenant is already known before login even renders, so there's no need
 * for their identity to be centrally resolvable.
 *
 * Roles are a single plain column, not Spatie permissions — every call
 * site only ever checks one fixed role at a time, and Spatie's installed
 * version binds its Role model's connection globally (not per-instance),
 * which would silently break under multi-tenant connection switching.
 */
class Staff extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    protected $table = 'staff';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Prefixed so the business guard's auth provider can tell a Staff
     * session apart from a central User (Owner) session — two independent
     * auto-increment sequences would otherwise collide on the same id.
     */
    public function getAuthIdentifier(): string
    {
        return 'staff:'.$this->getKey();
    }

    public function hasRole(UserRole|string $role): bool
    {
        return $this->role === ($role instanceof UserRole ? $role->value : $role);
    }

    /**
     * @param  array<UserRole|string>  $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    public function assignRole(UserRole|string $role): static
    {
        $this->role = $role instanceof UserRole ? $role->value : $role;
        $this->save();

        return $this;
    }

    public function syncRoles(UserRole|string $role): static
    {
        return $this->assignRole($role);
    }

    public function getRoleNames(): Collection
    {
        return collect([$this->role]);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && $panel->getId() === 'business';
    }
}
