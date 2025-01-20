<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ServerConfig;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Rule;

class ServerConfigs extends Component
{
    #[Rule('required|string|max:255')]
    public string $name = '';

    #[Rule('required|string|max:255')]
    public string $hostname = '';

    #[Rule('required|string|max:255')]
    public string $username = '';

    #[Rule('required|string')]
    public string $password = '';

    public ?ServerConfig $editing = null;
    public bool $isModalOpen = false;
    public string $modalMode = 'create'; // 'create' or 'edit'

    public function openModal(string $mode = 'create', ?ServerConfig $serverConfig = null): void
    {
        $this->modalMode = $mode;
        $this->isModalOpen = true;

        if ($mode === 'edit' && $serverConfig) {
            $this->editing = $serverConfig;
            $this->name = $serverConfig->name;
            $this->hostname = $serverConfig->hostname;
            $this->username = $serverConfig->username;
            $this->password = $serverConfig->password;
        } else {
            $this->resetForm();
        }
    }

    public function closeModal(): void
    {
        $this->isModalOpen = false;
        $this->resetForm();
    }

    public function save(): void
    {
        $this->validate();

        if ($this->modalMode === 'edit' && $this->editing) {
            $this->editing->update([
                'name' => $this->name,
                'hostname' => $this->hostname,
                'username' => $this->username,
                'password' => $this->password,
            ]);
            $this->dispatch('server-config-updated');
        } else {
            Auth::user()->serverConfigs()->create([
                'name' => $this->name,
                'hostname' => $this->hostname,
                'username' => $this->username,
                'password' => $this->password,
            ]);
            $this->dispatch('server-config-saved');
        }

        $this->closeModal();
    }

    public function delete(ServerConfig $serverConfig): void
    {
        $serverConfig->delete();
        $this->dispatch('server-config-deleted');
    }

    private function resetForm(): void
    {
        $this->reset(['name', 'hostname', 'username', 'password', 'editing']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.server-configs', [
            'configs' => Auth::user()->serverConfigs()->latest()->get()
        ]);
    }
}
