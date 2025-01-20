<div>
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold text-gray-800">Server Configurations</h2>
            <button wire:click="openModal('create')" class="btn btn-primary">
                Add New Server
            </button>
        </div>

        <!-- Success Messages -->
        <div>
            @if (session()->has('message'))
                <div class="alert alert-success mb-4">
                    {{ session('message') }}
                </div>
            @endif
        </div>

        <!-- Server Configs Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($configs as $config)
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h3 class="card-title">{{ $config->name }}</h3>
                        <p class="text-gray-600">{{ $config->hostname }}</p>
                        <p class="text-gray-600">{{ $config->username }}</p>
                        <div class="card-actions justify-end mt-4">
                            <button wire:click="openModal('edit', {{ $config }})" class="btn btn-sm">
                                Edit
                            </button>
                            <button wire:click="delete({{ $config->id }})"
                                    wire:confirm="Are you sure you want to delete this server configuration?"
                                    class="btn btn-sm btn-error">
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Modal -->
    @if($isModalOpen)
        <div class="modal modal-open">
            <div class="modal-box">
                <h3 class="font-bold text-lg mb-4">
                    {{ $modalMode === 'create' ? 'Add New Server Configuration' : 'Edit Server Configuration' }}
                </h3>
                <form wire:submit="save">
                    <div class="space-y-4">
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Server Name</span>
                            </label>
                            <input type="text" wire:model="name" class="input input-bordered" required>
                            @error('name') <span class="text-error text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Hostname</span>
                            </label>
                            <input type="text" wire:model="hostname" class="input input-bordered" required>
                            @error('hostname') <span class="text-error text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Username</span>
                            </label>
                            <input type="text" wire:model="username" class="input input-bordered" required>
                            @error('username') <span class="text-error text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Password</span>
                            </label>
                            <input type="password" wire:model="password" class="input input-bordered" required>
                            @error('password') <span class="text-error text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="modal-action">
                        <button type="button" class="btn" wire:click="closeModal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            {{ $modalMode === 'create' ? 'Save' : 'Update' }}
                        </button>
                    </div>
                </form>
            </div>
            <div class="modal-backdrop" wire:click="closeModal"></div>
        </div>
    @endif
</div>
