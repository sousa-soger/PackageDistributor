<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProjectInvolvementService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function index(Request $request, ProjectInvolvementService $involvement)
    {
        $user = $request->user();
        $teamOptions = $involvement->teamOptionsForUser($user);

        $projects = $involvement->visibleProjectsFor($user)
            ->with([
                'repositories',
                'teams' => fn ($query) => $query->withCount('members')->orderBy('name'),
                'involvedUsers' => fn ($query) => $query->orderBy('name'),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Project $project) => $involvement->projectCardPayload($project, $user, $teamOptions))
            ->values();

        $repositoryProjectOptions = $user->projects()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Project $project) => [
                'id' => $project->id,
                'name' => $project->name,
            ])
            ->values();

        return view('projects', [
            'projects' => $projects,
            'oauthConnections' => [
                'github' => (bool) $user->github_token,
                'gitlab' => (bool) $user->gitlab_token,
            ],
            'repositoryProjectOptions' => $repositoryProjectOptions,
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
