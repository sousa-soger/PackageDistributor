<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $projects = $user->projects()
            ->with('repositories')
            ->orderBy('name')
            ->get()
            ->map(fn (Project $project) => [
                'id' => $project->id,
                'name' => $project->name,
                'slug' => $project->slug,
                'description' => $project->description ?: 'No description added yet.',
                'color' => $project->color ?: Project::DEFAULT_COLOR,
                'repoCount' => $project->repositories->count(),
                'lastDeployedAt' => $project->last_deployed_at?->diffForHumans() ?? '—',
                'repositories' => $project->repositories->map(fn ($r) => [
                    'id' => $r->id,
                    'name' => $r->display_name ?? $r->name,
                    'provider' => $r->provider,
                    'defaultBranch' => $r->default_branch ?? 'main',
                    'branchCount' => count($r->branches ?? []),
                    'tagCount' => count($r->tags ?? []),
                    'status' => $r->status ?? 'connected',
                ])->values(),
            ])->values();

        $teamIds = $user->teams()->pluck('teams.id');

        $teamMemberIds = $teamIds->isEmpty()
            ? collect([$user->id])
            : User::whereHas('teams', fn ($query) => $query->whereIn('teams.id', $teamIds))
                ->pluck('users.id')
                ->push($user->id)
                ->unique()
                ->values();

        $teamMembers = User::whereIn('id', $teamMemberIds)
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'role' => $u->id === $user->id ? 'owner' : 'member',
                'status' => 'active',
                'initials' => collect(explode(' ', $u->name))
                    ->map(fn ($w) => strtoupper($w[0] ?? ''))
                    ->take(2)
                    ->implode(''),
            ])->values();

        return view('projects', [
            'projects' => $projects,
            'teamMembers' => $teamMembers,
            'colorOptions' => Project::COLOR_OPTIONS,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('projects', 'name')->where(fn ($query) => $query->where('user_id', $user->id)),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['required', 'string', Rule::in(Project::COLOR_OPTIONS)],
        ]);

        $slug = $this->uniqueSlug($user, $validated['name']);

        $user->projects()->create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?: null,
            'color' => $validated['color'],
        ]);

        return redirect()
            ->route('projects')
            ->with('success', 'Project created successfully.');
    }

    public function update(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $user = $request->user();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('projects', 'name')
                    ->where(fn ($query) => $query->where('user_id', $user->id))
                    ->ignore($project->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['required', 'string', Rule::in(Project::COLOR_OPTIONS)],
        ]);

        $project->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?: null,
            'color' => $validated['color'],
        ]);

        return redirect()
            ->route('projects')
            ->with('success', 'Project updated successfully.');
    }

    public function destroy(Project $project)
    {
        $this->authorize('delete', $project);

        $project->delete();

        return redirect()
            ->route('projects')
            ->with('success', 'Project deleted.');
    }

    private function uniqueSlug($user, string $name): string
    {
        $baseSlug = Str::slug($name);
        $seed = $baseSlug !== '' ? $baseSlug : 'project';
        $slug = $seed;
        $counter = 2;

        while ($user->projects()->where('slug', $slug)->exists()) {
            $slug = $seed.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
