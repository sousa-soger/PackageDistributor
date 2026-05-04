<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        if ($this->ownsProject($user, $project)) {
            return true;
        }

        if ($project->involvedUsers()->whereKey($user->id)->exists()) {
            return true;
        }

        return $project->teams()
            ->whereHas('members', fn ($query) => $query->whereKey($user->id))
            ->exists();
    }

    public function update(User $user, Project $project): bool
    {
        return $this->ownsProject($user, $project);
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->ownsProject($user, $project);
    }

    public function manageMembers(User $user, Project $project): bool
    {
        return $this->ownsProject($user, $project);
    }

    protected function ownsProject(User $user, Project $project): bool
    {
        return $user->id === $project->user_id;
    }
}
