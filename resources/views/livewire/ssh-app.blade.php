<div class="min-h-screen bg-base-200">
    <!-- Navigation Menu -->
    <div class="navbar bg-base-100 shadow-lg mb-4">
        <div class="navbar-start">
            <div class="dropdown">
                <label tabindex="0" class="btn btn-ghost lg:hidden">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16" />
                    </svg>
                </label>
                <ul tabindex="0" class="menu menu-sm dropdown-content mt-3 z-[1] p-2 shadow bg-base-100 rounded-box w-52">
                    <li><button wire:click="$toggle('showMonitoring')" class="{{$showMonitoring ? 'active' : ''}}">System Monitor</button></li>
                    <li><button wire:click="$toggle('showFileManager')" class="{{$showFileManager ? 'active' : ''}}">File Manager</button></li>
                    <li><button wire:click="$toggle('showSaveForm')" class="{{$showSaveForm ? 'active' : ''}}">Save Config</button></li>
                </ul>
            </div>
            <a class="btn btn-ghost normal-case text-xl">SSH App</a>
        </div>

        <div class="navbar-center hidden lg:flex">
            <ul class="menu menu-horizontal px-1">
                <li>
                    <x-modal name="server-config-form" :show="$showSaveForm" focusable>
                        <div class="p-6">
                            <h2 class="text-lg font-medium text-gray-900">
                                {{ $selectedConfigId ? 'Edit Server Configuration' : 'Add New Server Configuration' }}
                            </h2>
                            <div class="mt-4">
                                <livewire:server-config-form :server-config="$selectedConfigId ? $savedConfigs->firstWhere('id', $selectedConfigId) : null" />
                            </div>
                        </div>
                    </x-modal>
                </li>
                <li>
                    <button wire:click="$toggle('showFileManager')"
                            class="btn btn-ghost {{$showFileManager ? 'btn-active' : ''}}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                        </svg>
                        File Manager
                    </button>
                </li>
                <li>
                    <button wire:click="$toggle('showSaveForm')"
                            class="btn btn-ghost {{$showSaveForm ? 'btn-active' : ''}}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                        </svg>
                        Save Config
                    </button>
                </li>
            </ul>
        </div>

        <div class="navbar-end">
            @if($isConnected)
                <button wire:click="disconnect" class="btn btn-error btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Disconnect
                </button>
            @endif
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4">
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            @if ($errorMessage)
                <div class="alert alert-error mb-4">
                    {{ $errorMessage }}
                </div>
            @endif

            @if (!$isConnected)
                <!-- Server Configuration Modal -->
                <x-modal name="server-config-form" :show="$showSaveForm" focusable>
                    <div class="p-6">
                        <h2 class="text-lg font-medium text-gray-900">
                            {{ $selectedConfigId ? 'Edit Server Configuration' : 'Add New Server Configuration' }}
                        </h2>
                        <div class="mt-4">
                            <livewire:server-config-form :server-config="$selectedConfigId ? $savedConfigs->firstWhere('id', $selectedConfigId) : null" />
                        </div>
                    </div>
                </x-modal>

                <!-- Server List -->
                <div class="mt-4">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Saved Servers</h3>
                        <button wire:click="$set('showSaveForm', true)" class="btn btn-primary">
                            Add New Server
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($savedConfigs as $config)
                            <div class="bg-white rounded-lg shadow p-4">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="font-medium text-gray-900">{{ $config->name }}</h4>
                                        <p class="text-sm text-gray-500">{{ $config->hostname }}</p>
                                        <p class="text-sm text-gray-500">{{ $config->username }}</p>
                                    </div>
                                    <div class="flex space-x-2">
                                        <button wire:click="loadServerConfig({{ $config->id }})" class="text-blue-600 hover:text-blue-800">
                                            <x-icon name="arrow-path" class="w-5 h-5" />
                                        </button>
                                        <button wire:click="deleteServerConfig({{ $config->id }})" class="text-red-600 hover:text-red-800">
                                            <x-icon name="trash" class="w-5 h-5" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                    <form wire:submit.prevent="connect" class="space-y-4">
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Hostname</span>
                        </label>
                        <input type="text" wire:model="hostname" class="input input-bordered" placeholder="example.com" />
                        @error('hostname') <span class="text-error text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Username</span>
                        </label>
                        <input type="text" wire:model="username" class="input input-bordered" placeholder="username" />
                        @error('username') <span class="text-error text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Password</span>
                        </label>
                        <input type="password" wire:model="password" class="input input-bordered" />
                        @error('password') <span class="text-error text-sm">{{ $message }}</span> @enderror
                    </div>

                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                        <span wire:loading wire:target="connect">Connecting...</span>
                        <span wire:loading.remove wire:target="connect">Connect</span>
                    </button>
                </form>

                @if ($showSaveForm)
                    <div class="mt-4">
                        <form wire:submit="saveServerConfig" class="flex gap-2">
                            <input type="text" wire:model="configName"
                                   class="input input-bordered flex-1"
                                   placeholder="Enter a name for this server" />
                            <button type="submit" class="btn btn-primary">Save</button>
                            <button type="button" class="btn" wire:click="$set('showSaveForm', false)">Cancel</button>
                        </form>
                    </div>
                @else
                    <button class="btn btn-ghost mt-4" wire:click="$set('showSaveForm', true)">
                        Save This Configuration
                    </button>
                @endif
            @else
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <h2 class="text-xl font-bold">Connected to: {{ $hostname }}</h2>
                        <button class="btn btn-error btn-sm" wire:click="disconnect">Disconnect</button>
                    </div>

                    <div class="bg-base-300 p-4 rounded-lg min-h-[200px] font-mono whitespace-pre-wrap">
                        {{ $output }}
                    </div>

                    @if ($aiSuggestion)
                        <div class="bg-primary/10 p-4 rounded-lg">
                            <h3 class="font-semibold mb-2">AI Suggestion:</h3>
                            <div class="prose">
                                {{ $aiSuggestion }}
                            </div>
                        </div>
                    @endif

                    <form wire:submit="executeCommand" class="flex gap-2 relative">
                        <div class="flex-1 relative">
                            <input type="text" wire:model.live.debounce.300ms="command"
                                   class="input input-bordered w-full"
                                   placeholder="Enter command..."
                                   wire:keydown.enter="executeCommand"
                                   wire:keydown.escape="$set('showAutocomplete', false)"
                                   wire:keydown.arrow-down="$dispatch('focus-suggestion', { index: 0 })"
                                   autocomplete="off" />

                            @if ($showAutocomplete && !empty($commandSuggestions))
                                <div class="absolute top-full left-0 right-0 mt-1 bg-base-200 rounded-lg shadow-lg border border-base-300 z-10">
                                    <ul class="p-2 space-y-1">
                                        @foreach ($commandSuggestions as $index => $suggestion)
                                            <li>
                                                <button type="button"
                                                        wire:click="selectSuggestion('{{ $suggestion }}')"
                                                        x-data="{ focused: false }"
                                                        x-on:focus-suggestion.window="focused = $event.detail.index === {{ $index }}"
                                                        x-on:keydown.arrow-down.prevent="$dispatch('focus-suggestion', { index: {{ $index + 1 }} })"
                                                        x-on:keydown.arrow-up.prevent="$dispatch('focus-suggestion', { index: {{ $index - 1 }} })"
                                                        x-on:keydown.enter.prevent="$wire.selectSuggestion('{{ $suggestion }}')"
                                                        :class="{ 'bg-primary/10': focused }"
                                                        class="w-full text-left px-3 py-2 rounded hover:bg-primary/10 focus:outline-none focus:bg-primary/10">
                                                    {{ $suggestion }}
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>

                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                            <span wire:loading wire:target="executeCommand">Executing...</span>
                            <span wire:loading.remove wire:target="executeCommand">Execute</span>
                        </button>
                    </form>

                    @if ($isLoadingSuggestion)
                        <div class="text-center text-sm text-base-content/70">
                            Generating AI suggestion...
                        </div>
                    @endif

                    <div class="flex justify-between items-center">
                        <button class="btn btn-ghost" wire:click="toggleMonitoring">
                            {{ $showMonitoring ? 'Hide Monitoring' : 'Show Monitoring' }}
                        </button>
                        @if ($showMonitoring)
                            <button class="btn btn-ghost btn-sm" wire:click="refreshMetrics">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                            </button>
                        @endif
                    </div>

                    @if ($showMonitoring)
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4" wire:poll.10s="refreshMetrics">
                            <!-- CPU Usage -->
                            <div class="card bg-base-200">
                                <div class="card-body">
                                    <h3 class="card-title">CPU Usage</h3>
                                    <div class="radial-progress text-primary" style="--value:{{ $systemMetrics['cpu'] ?? 0 }};">
                                        {{ $systemMetrics['cpu'] ?? 0 }}%
                                    </div>
                                </div>
                            </div>

                            <!-- Memory Usage -->
                            <div class="card bg-base-200">
                                <div class="card-body">
                                    <h3 class="card-title">Memory Usage</h3>
                                    <div class="radial-progress text-primary" style="--value:{{ $systemMetrics['memory'] ?? 0 }};">
                                        {{ $systemMetrics['memory'] ?? 0 }}%
                                    </div>
                                </div>
                            </div>

                            <!-- Disk Usage -->
                            <div class="card bg-base-200">
                                <div class="card-body">
                                    <h3 class="card-title">Disk Usage</h3>
                                    <div class="radial-progress text-primary" style="--value:{{ $systemMetrics['disk'] ?? 0 }};">
                                        {{ $systemMetrics['disk'] ?? 0 }}%
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Process List -->
                        <div class="card bg-base-200">
                            <div class="card-body">
                                <h3 class="card-title">Top Processes</h3>
                                <div class="overflow-x-auto">
                                    <table class="table table-compact w-full">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>PID</th>
                                                <th>CPU %</th>
                                                <th>Memory %</th>
                                                <th>Command</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($processList as $process)
                                                <tr>
                                                    <td>{{ $process['user'] }}</td>
                                                    <td>{{ $process['pid'] }}</td>
                                                    <td>{{ $process['cpu'] }}</td>
                                                    <td>{{ $process['memory'] }}</td>
                                                    <td>{{ $process['command'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif
            </div>
        </div>
    </div>
</div>
