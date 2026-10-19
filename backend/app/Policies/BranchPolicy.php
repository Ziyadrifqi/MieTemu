<?php

namespace App\Policies;

use App\Enums\StaffRole;
use App\Models\Branch;
use App\Models\User;

class BranchPolicy
{
    /** Masuk ke area staf cabang (semua role). */
    public function viewStaffArea(User $user, Branch $branch): bool
    {
        return $this->hasRole($user, $branch, StaffRole::Admin, StaffRole::Host, StaffRole::Kitchen);
    }

    /** Kelola antrean dan booking: admin dan host. */
    public function manageQueue(User $user, Branch $branch): bool
    {
        return $this->hasRole($user, $branch, StaffRole::Admin, StaffRole::Host);
    }

    /** Kelola pesanan dapur: admin dan dapur. */
    public function manageKitchen(User $user, Branch $branch): bool
    {
        return $this->hasRole($user, $branch, StaffRole::Admin, StaffRole::Kitchen);
    }

    /** Kelola pengaturan cabang (meja, menu, jam, staf): admin saja. */
    public function manageBranch(User $user, Branch $branch): bool
    {
        return $this->hasRole($user, $branch, StaffRole::Admin);
    }

    private function hasRole(User $user, Branch $branch, StaffRole ...$roles): bool
    {
        $branch->loadMissing('business');

        // Bisnis yang disuspend tidak boleh diakses stafnya.
        if ($branch->business->status !== 'active') {
            return false;
        }

        return $user->hasBranchRole($branch, ...$roles);
    }
}
