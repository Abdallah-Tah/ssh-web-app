<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\SSHService;
use App\Exceptions\SSHConnectionException;
use Illuminate\Support\Facades\Log;

class SSHServiceTest extends TestCase
{
    private SSHService $sshService;
    private string $hostname;
    private string $username;
    private string $password;
    private ?string $privateKeyPath;
    private int $port;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sshService = new SSHService();
        $this->hostname = env('SSH_TEST_HOST', '');
        $this->username = env('SSH_TEST_USER', '');
        $this->password = env('SSH_TEST_PASSWORD', '');
        $this->privateKeyPath = env('SSH_TEST_PRIVATE_KEY_PATH', null);
        $this->port = (int) env('SSH_TEST_PORT', 22);

        Log::info('Running SSH test with configuration', [
            'config' => [
                'hostname' => $this->hostname,
                'username' => $this->username,
                'password' => '********',
                'private_key' => $this->privateKeyPath ? 'provided' : 'not provided',
                'port' => $this->port
            ],
            'php_version' => PHP_VERSION,
            'os' => PHP_OS,
            'env' => app()->environment()
        ]);
    }

    public function test_ssh_connection_successful(): void
    {
        $this->sshService->connect(
            $this->hostname,
            $this->username,
            $this->port,
            $this->password,
            $this->privateKeyPath
        );
        $this->assertTrue(true, 'SSH connection established successfully.');
    }

    public function test_execute_command_successful(): void
    {
        $this->sshService->connect(
            $this->hostname,
            $this->username,
            $this->port,
            $this->password,
            $this->privateKeyPath
        );
        $output = $this->sshService->executeCommand('whoami');
        $this->assertNotEmpty($output, 'Command output should not be empty');
        $this->assertStringContainsString($this->username, $output, 'Output should contain username');
    }

    public function test_ssh_connection_failure(): void
    {
        $this->expectException(SSHConnectionException::class);
        $this->sshService->connect(
            $this->hostname,
            'invalid_user',
            $this->port,
            'invalid_password'
        );
    }

    public function test_connection_invalid_port(): void
    {
        $this->expectException(SSHConnectionException::class);
        $this->sshService->connect(
            $this->hostname,
            $this->username,
            9999,
            $this->password
        );
    }

    public function test_execute_command_without_connection(): void
    {
        $this->expectException(SSHConnectionException::class);
        $this->sshService->executeCommand('id');
    }

    protected function tearDown(): void
    {
        $this->sshService->disconnect();
        parent::tearDown();
    }
}
