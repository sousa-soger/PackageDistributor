<?php

namespace App\Policies;

use App\Models\Repository;
use App\Models\User;

class RepositoryPolicy
{
    public function update(User $user, Repository $repository): bool
    {
        return $user->id === $repository->user_id;
    }
 
    public function delete(User $user, Repository $repository): bool
    {
        return $user->id === $repository->user_id;
    }
}
