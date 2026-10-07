<?php

namespace App\Policies;

use App\Models\User;

/**
 * "Akun Admin" in the panel: only a working administrator sees the list and limits or revokes
 * access. Accounts are created with `php artisan admin:create` and never deleted from the panel
 * (revoking keeps the record of who had access).
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAdminAccess();
    }

    public function view(User $user, User $target): bool
    {
        return $user->hasAdminAccess();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, User $target): bool
    {
        return $user->hasAdminAccess();
    }

    public function delete(User $user, User $target): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
