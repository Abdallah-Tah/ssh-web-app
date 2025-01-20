<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Livewire\SSHApp;
use Livewire\Livewire;
use App\Models\User;
use App\Models\ServerConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

class SSHTest extends TestCase
{
    use RefreshDatabase;

    private string $hostname;
    private string $username;
    private string $password;
    private int $port;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hostname = env('SSH_TEST_HOST', '');
        $this->username = env('SSH_TEST_USER', '');
        $this->password = env('SSH_TEST_PASSWORD', '');
        $this->port = (int) env('SSH_TEST_PORT', 22);

        if (!$this->hostname || !$this->username || !$this->password) {
            $this->markTestSkipped('SSH test credentials not configured.');
        }

        Log::info('Running SSH test with configuration', [
            'hostname' => $this->hostname,
            'username' => $this->username,
            'password' => '********',
            'port' => $this->port,
            'os' => PHP_OS,
            'php_version' => PHP_VERSION
        ]);
    }

    public function test_can_connect_to_ssh_server(): void
    {
        try {
            $component = Livewire::test(SSHApp::class)
                ->set('hostname', $this->hostname)
                ->set('username', $this->username)
                ->set('password', $this->password)
                ->set('port', $this->port);

            Log::debug('Testing SSH connection', [
                'hostname' => $this->hostname,
                'username' => $this->username,
                'port' => $this->port
            ]);

            $component->call('connect')
                ->assertSet('isConnected', true)
                ->assertSet('errorMessage', null)
                ->assertHasNoErrors();

            // Cleanup
            $component->call('disconnect');
        } catch (\Exception $e) {
            Log::error('SSH connection test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function test_can_save_server_config(): void
    {
        $user = User::factory()->create();

        try {
            Livewire::actingAs($user)
                ->test(SSHApp::class)
                ->set('hostname', $this->hostname)
                ->set('username', $this->username)
                ->set('password', $this->password)
                ->set('port', $this->port)
                ->set('configName', 'Test Server')
                ->call('saveServerConfig')
                ->assertHasNoErrors()
                ->assertDispatchedBrowserEvent('server-config-saved');

            $this->assertDatabaseHas('server_configs', [
                'name' => 'Test Server',
                'hostname' => $this->hostname,
                'user_id' => $user->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Save server config test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function test_can_execute_command(): void
    {
        try {
            $component = Livewire::test(SSHApp::class)
                ->set('hostname', $this->hostname)
                ->set('username', $this->username)
                ->set('password', $this->password)
                ->set('port', $this->port)
                ->call('connect');

            $component->assertSet('isConnected', true)
                ->assertSet('errorMessage', null)
                ->set('command', 'pwd')
                ->call('executeCommand')
                ->assertSet('errorMessage', null)
                ->assertNotSet('output', '');

            // Cleanup
            $component->call('disconnect');
        } catch (\Exception $e) {
            Log::error('Command execution test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
