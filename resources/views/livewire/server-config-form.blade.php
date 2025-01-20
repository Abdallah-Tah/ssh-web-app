<div>
    <form wire:submit.prevent="save" class="space-y-4">
        {{-- Global Errors --}}
        @if($errors->has('save'))
            <div class="alert alert-error">
                {{ $errors->first('save') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-error">
                {{ session('error') }}
            </div>
        @endif

        {{-- Name Field --}}
        <div>
            <label for="name" class="block text-sm font-medium">Name</label>
            <input
                type="text"
                id="name"
                wire:model="name"
                class="input input-bordered w-full @error('name') input-error @enderror">
            @error('name')
                <span class="text-error text-sm">{{ $message }}</span>
            @enderror
        </div>

        {{-- Hostname Field --}}
        <div>
            <label for="hostname" class="block text-sm font-medium">Hostname</label>
            <input
                type="text"
                id="hostname"
                wire:model="hostname"
                class="input input-bordered w-full @error('hostname') input-error @enderror">
            @error('hostname')
                <span class="text-error text-sm">{{ $message }}</span>
            @enderror
        </div>

        {{-- Username Field --}}
        <div>
            <label for="username" class="block text-sm font-medium">Username</label>
            <input
                type="text"
                id="username"
                wire:model="username"
                class="input input-bordered w-full @error('username') input-error @enderror">
            @error('username')
                <span class="text-error text-sm">{{ $message }}</span>
            @enderror
        </div>

        {{-- Password Field --}}
        <div>
            <label for="password" class="block text-sm font-medium">Password</label>
            <input
                type="password"
                id="password"
                wire:model="password"
                class="input input-bordered w-full @error('password') input-error @enderror">
            @error('password')
                <span class="text-error text-sm">{{ $message }}</span>
            @enderror
        </div>

        {{-- Port Field --}}
        <div>
            <label for="port" class="block text-sm font-medium">Port</label>
            <input
                type="number"
                id="port"
                wire:model="port"
                class="input input-bordered w-full @error('port') input-error @enderror">
            @error('port')
                <span class="text-error text-sm">{{ $message }}</span>
            @enderror
        </div>

        {{-- Private Key Path Field --}}
        <div>
            <label for="private_key_path" class="block text-sm font-medium">Private Key Path (Optional)</label>
            <input
                type="text"
                id="private_key_path"
                wire:model="private_key_path"
                class="input input-bordered w-full @error('private_key_path') input-error @enderror">
            @error('private_key_path')
                <span class="text-error text-sm">{{ $message }}</span>
            @enderror
        </div>

        {{-- Submit Button --}}
        <div class="flex justify-end space-x-2">
            <button type="submit" class="btn btn-primary">
                {{ $editing ? 'Update' : 'Save' }}
            </button>
        </div>
    </form>
</div>
