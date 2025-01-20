<?php

namespace App\Http\Controllers;

use App\Models\ServerConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ServerConfigController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $configs = Auth::user()->serverConfigs()->latest()->get();
        return view('server-configs.index', compact('configs'));
    }

    public function create()
    {
        return view('server-configs.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'hostname' => 'required|string|max:255',
            'username' => 'required|string|max:255',
            'password' => 'required|string',
        ]);

        Auth::user()->serverConfigs()->create($validated);

        return redirect()->route('server-configs.index')
            ->with('success', 'Server configuration saved successfully.');
    }

    public function edit(ServerConfig $serverConfig)
    {
        $this->authorize('update', $serverConfig);
        return view('server-configs.edit', compact('serverConfig'));
    }

    public function update(Request $request, ServerConfig $serverConfig)
    {
        $this->authorize('update', $serverConfig);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'hostname' => 'required|string|max:255',
            'username' => 'required|string|max:255',
            'password' => 'required|string',
        ]);

        $serverConfig->update($validated);

        return redirect()->route('server-configs.index')
            ->with('success', 'Server configuration updated successfully.');
    }

    public function destroy(ServerConfig $serverConfig)
    {
        $this->authorize('delete', $serverConfig);

        $serverConfig->delete();

        return redirect()->route('server-configs.index')
            ->with('success', 'Server configuration deleted successfully.');
    }
}
