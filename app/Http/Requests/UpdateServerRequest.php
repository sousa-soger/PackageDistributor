<?php

namespace App\Http\Requests;

use App\Models\Server;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $server = $this->route('server');

        return $server instanceof Server && (bool) $this->user()?->can('update', $server);
    }

    /**
     * @return array<string, list<mixed>|string>
     */
    public function rules(): array
    {
        $server = $this->route('server');
        $serverId = $server instanceof Server ? $server->id : null;

        return [
            'auto_deploy_enabled' => ['sometimes', 'boolean'],
            'auto_deploy_strategy' => [
                Rule::requiredIf(fn (): bool => $this->boolean('auto_deploy_enabled')),
                'nullable',
                'string',
                Rule::in(array_keys(Server::AUTO_DEPLOY_STRATEGIES)),
            ],
            'deploy_path' => ['required', 'string', 'max:500'],
            'environment' => ['required', 'string', Rule::in(array_keys(Server::ENVIRONMENTS))],
            'health_check_url' => ['nullable', 'url', 'max:500'],
            'host' => ['required', 'string', 'max:255'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('servers', 'name')
                    ->where(fn ($query) => $query->where('user_id', $this->user()?->id))
                    ->ignore($serverId),
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'production_approval_required' => ['sometimes', 'boolean'],
            'project_id' => [
                'nullable',
                'integer',
                Rule::exists('projects', 'id')->where(fn ($query) => $query->where('user_id', $this->user()?->id)),
            ],
            'ssh_user' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]+$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'health_check_url.url' => 'Enter a valid health check URL.',
            'host.required' => 'Enter the server hostname or IP address.',
            'name.unique' => 'You already have a server with this name.',
            'ssh_user.regex' => 'The SSH user may only contain letters, numbers, dots, underscores, and dashes.',
        ];
    }
}
