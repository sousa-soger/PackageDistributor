<?php

namespace App\Policies;

use App\Models\DeploymentJob;
use App\Models\User;

class DeploymentJobPolicy
{
    public function deploy(User $user, DeploymentJob $deploymentJob): bool
    {
        $deploymentJob->loadMissing('repository');

        if (! $deploymentJob->repository) {
            return $deploymentJob->user_id === $user->id;
        }

        return $user->can('deployPackage', $deploymentJob->repository);
    }

    public function delete(User $user, DeploymentJob $deploymentJob): bool
    {
        $deploymentJob->loadMissing('repository.members');

        if (! $deploymentJob->repository) {
            return $deploymentJob->user_id === $user->id;
        }

        if ($deploymentJob->repository->user_id === $user->id) {
            return true;
        }

        $role = $deploymentJob->repository->members
            ->firstWhere('id', $user->id)
            ?->pivot
            ?->role;

        if ($role === 'maintainer') {
            return true;
        }

        return $role === 'creator' && $deploymentJob->user_id === $user->id;
    }
}
