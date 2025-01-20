<?php

declare(strict_types=1);

namespace App\Livewire;

use Livewire\Component;
use App\Models\ServerConfig;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ServerConfigForm extends Component
{
    public ?ServerConfig $editing = null;

    public string $name = '';
    public string $hostname = '';
    public string $username = '';
    public string $password = '';
    public int $port = 22;
    public ?string $private_key_path = null;

    protected $rules = [
        'name' => 'required|string|max:255',
        'hostname' => 'required|string|max:255',
        'username' => 'required|string|max:255',
        'password' => 'required_without:private_key_path|string|nullable',
        'port' => 'required|integer|min:1|max:65535',
        'private_key_path' => 'nullable|string|max:255',
    ];

    public function load(ServerConfig $serverConfig): void
    {
        $this->editing = $serverConfig;
        $this->name = $serverConfig->name;
        $this->hostname = $serverConfig->hostname;
        $this->username = $serverConfig->username;
        $this->password = ''; // Do not load sensitive data
        $this->port = $serverConfig->port;
        $this->private_key_path = $serverConfig->private_key_path;

        Log::info('Loaded server configuration for editing', ['id' => $serverConfig->id]);
    }

    public function save(): void
    {
        $this->validate();

        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            $data = [
                'name' => $this->name,
                'hostname' => $this->hostname,
                'username' => $this->username,
                'password' => $this->password ?: null, // Handle empty password
                'port' => $this->port,
                'private_key_path' => $this->private_key_path,
                'user_id' => $user->id,
            ];

            if ($this->editing) {
                if (empty($this->password)) {
                    unset($data['password']);
                }
                $this->editing->update($data);
                Log::info('Server configuration updated', ['id' => $this->editing->id]);
            } else {
                $serverConfig = $user->serverConfigs()->create($data);
                Log::info('New server configuration created', ['id' => $serverConfig->id]);
            }

            $this->dispatch('server-config-saved');
            $this->reset(['editing', 'name', 'hostname', 'username', 'password', 'port', 'private_key_path']);
        } catch (\Exception $e) {
            Log::error('Failed to save server configuration', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Failed to save server configuration: ' . $e->getMessage());
            $this->addError('save', 'Failed to save server configuration. Please try again.');
        }
    }

    public function render()
    {
        return view('livewire.server-config-form');
    }
}
