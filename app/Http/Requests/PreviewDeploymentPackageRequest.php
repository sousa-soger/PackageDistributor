<?php

namespace App\Http\Requests;

use App\Models\Repository;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PreviewDeploymentPackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $repository = $this->unvalidatedPackageRepository();

        if (! $repository) {
            return (bool) $this->user();
        }

        return (bool) $this->user()?->can('createPackage', $repository);
    }

    /**
     * @return array<string, list<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'base_version' => ['required', 'string', 'max:100', 'different:head_version'],
            'head_version' => ['required', 'string', 'max:100'],
            'repository_id' => ['required', 'integer', Rule::exists('repositories', 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'base_version.different' => 'Choose two different versions to preview changes.',
            'repository_id.required' => 'Choose a repository before previewing changes.',
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
