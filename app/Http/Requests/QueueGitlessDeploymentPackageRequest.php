<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QueueGitlessDeploymentPackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    /**
     * @return array<string, list<string>|string>
     */
    public function rules(): array
    {
        return [
            'base_archive' => ['required', 'file', 'max:512000'],
            'environment' => ['required', 'string', 'max:20'],
            'head_archive' => ['required', 'file', 'max:512000'],
            'package_name' => ['nullable', 'string', 'max:255'],
            'project_name' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'base_archive.required' => 'Choose a base folder or ZIP archive.',
            'head_archive.required' => 'Choose a target folder or ZIP archive.',
        ];
    }
}
