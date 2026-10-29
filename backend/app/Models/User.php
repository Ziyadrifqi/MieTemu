<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use App\Enums\StaffRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;


#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

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
            'is_super_admin' => 'boolean',
        ];
    }

    /** Halaman awal setelah login. */
    public function homeUrl(): string
    {
        return $this->isSuperAdmin() && ! $this->accessibleBranches()->exists()
            ? route('super.businesses.index')
            : route('dashboard');
    }

    public function businessMemberships(): HasMany
    {
        return $this->hasMany(BusinessMember::class);
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    /**
     * Role yang dimiliki user di cabang tertentu.
     * Keanggotaan dengan branch_id kosong berlaku untuk semua cabang di bisnis itu.
     *
     * @return Collection<int, StaffRole>
     */
    public function rolesForBranch(Branch $branch): Collection
    {
        return $this->businessMemberships
            ->filter(fn(BusinessMember $m) => (int) $m->business_id === (int) $branch->business_id
                && ($m->branch_id === null || (int) $m->branch_id === (int) $branch->id))
            ->pluck('role')
            ->unique()
            ->values();
    }

    public function hasBranchRole(Branch $branch, StaffRole ...$roles): bool
    {
        return $this->rolesForBranch($branch)
            ->contains(fn(StaffRole $role) => in_array($role, $roles, true));
    }

    /**
     * Cabang (di bisnis yang aktif) yang boleh diakses user sebagai staf.
     */
    public function accessibleBranches(): Builder
    {
        $memberships = $this->businessMemberships;

        // Penting: group where yang kosong akan meloloskan SEMUA cabang,
        // jadi user tanpa keanggotaan harus dikunci secara eksplisit.
        if ($memberships->isEmpty()) {
            return Branch::query()->whereRaw('1 = 0');
        }

        return Branch::query()
            ->where('is_active', true)
            ->whereHas('business', fn($q) => $q->where('status', 'active'))
            ->where(function (Builder $query) use ($memberships) {
                foreach ($memberships as $membership) {
                    $query->orWhere(function (Builder $q) use ($membership) {
                        $q->where('business_id', $membership->business_id);

                        if ($membership->branch_id !== null) {
                            $q->where('id', $membership->branch_id);
                        }
                    });
                }
            });
    }
}
