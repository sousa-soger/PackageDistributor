<?php

namespace App\Http\Requests;

use App\Models\Repository;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QueueDeploymentPackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $repository = $this->unvalidatedPackageRepository();

        if (! $repository) {
            return true;
        }

        return (bool) $this->user()?->can('createPackage', $repository);
    }

    /**
     * @return array<string, list<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'base_version' => ['required', 'string', 'max:100'],
            'environment' => ['required', 'string', 'max:20'],
            'head_version' => ['required', 'string', 'max:100'],
            'package_name' => ['nullable', 'string', 'max:255'],
            'project_name' => [Rule::requiredIf(fn () => blank($this->input('repository_id'))), 'nullable', 'string', 'max:100'],
            'repo' => [Rule::requiredIf(fn () => blank($this->input('repository_id'))), 'nullable', 'string', 'max:255'],
            'repository_id' => ['nullable', 'integer', Rule::exists('repositories', 'id')],
            'vcs_provider' => ['nullable', 'string', 'in:github,gitlab'],
        ];
    }

    public function packageRepository(): ?Repository
    {
        $repositoryId = $this->validated('repository_id');

        if (blank($repositoryId)) {
            return null;
        }

        return Repository::find($repositoryId);
    }

    private function unvalidatedPackageRepository(): ?Repository
    {
        $repositoryId = $this->input('repository_id');

        if (blank($repositoryId)) {
            return null;
        }

        return Repository::find($repositoryId);
    }
}
