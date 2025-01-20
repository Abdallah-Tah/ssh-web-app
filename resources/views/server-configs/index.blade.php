<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Server Configurations') }}
            </h2>
            <a href="{{ route('server-configs.create') }}" class="btn btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Add New Server
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="alert alert-success mb-4">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    @if($configs->isEmpty())
                        <div class="text-center py-8">
                            <h3 class="text-lg font-medium text-gray-500">No server configurations yet</h3>
                            <p class="mt-2 text-sm text-gray-400">Get started by adding your first server configuration.</p>
                        </div>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($configs as $config)
                                <div class="card bg-base-100 shadow-xl">
                                    <div class="card-body">
                                        <h2 class="card-title">{{ $config->name }}</h2>
                                        <p class="text-sm text-gray-600">{{ $config->hostname }}</p>
                                        <p class="text-sm text-gray-500">{{ $config->username }}</p>
                                        <div class="card-actions justify-end mt-4">
                                            <a href="{{ route('ssh.index', ['config' => $config->id]) }}" class="btn btn-primary btn-sm">
                                                Connect
                                            </a>
                                            <a href="{{ route('server-configs.edit', $config) }}" class="btn btn-ghost btn-sm">
                                                Edit
                                            </a>
                                            <form action="{{ route('server-configs.destroy', $config) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-error btn-sm" onclick="return confirm('Are you sure you want to delete this server?')">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
