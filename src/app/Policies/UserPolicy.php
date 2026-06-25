<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('users.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('users.manage');
    }

    public function update(User $user, User $target): bool
    {
        if (!$user->hasPermission('users.manage')) {
            return false;
        }

        if ((int) $user->id === (int) $target->id) {
            return true;
        }

        return true;
    }

    public function toggleStatus(User $user, User $target): bool
    {
        if (!$user->hasPermission('users.manage')) {
            return false;
        }

        if ((int) $user->id === (int) $target->id) {
            return false;
        }

        return true;
    }
}
