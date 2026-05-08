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

    public function createPackage(User $user, Repository $repository): bool
    {
        if (! $this->isPackageableRepository($repository)) {
            return false;
        }

        if ($this->ownsRepository($user, $repository)) {
            return true;
        }

        return $repository->members()
            ->whereKey($user->id)
            ->whereIn('repository_user.role', Repository::PACKAGE_CREATOR_ROLES)
            ->exists();
    }

    public function deployPackage(User $user, Repository $repository): bool
    {
        if ($this->ownsRepository($user, $repository)) {
            return true;
        }

        return $repository->members()
            ->whereKey($user->id)
            ->whereIn('repository_user.role', Repository::PACKAGE_DEPLOYER_ROLES)
            ->exists();
    }

    protected function ownsRepository(User $user, Repository $repository): bool
    {
        return $user->id === $repository->user_id;
    }

    protected function isPackageableRepository(Repository $repository): bool
    {
        if (in_array($repository->provider, ['github', 'gitlab'], true)) {
            return true;
        }

        return $repository->provider === 'local-pc'
            && in_array($repository->type, ['ssh-mirror', 'uploaded'], true)
            && filled($repository->storage_path);
    }
}
