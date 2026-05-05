<?php

namespace App\Policies;

use App\Models\Repository;
use App\Models\User;

class RepositoryPolicy
{
    public function view(User $user, Repository $repository): bool
    {
        if ($this->ownsRepository($user, $repository)) {
            return true;
        }

        return $repository->members()->whereKey($user->id)->exists();
    }

    public function update(User $user, Repository $repository): bool
    {
        return $this->ownsRepository($user, $repository);
    }

    public function delete(User $user, Repository $repository): bool
    {
        return $this->ownsRepository($user, $repository);
    }

    public function manageMembers(User $user, Repository $repository): bool
    {
        return $this->ownsRepository($user, $repository);
    }

    protected function ownsRepository(User $user, Repository $repository): bool
    {
        return $user->id === $repository->user_id;
    }
}
