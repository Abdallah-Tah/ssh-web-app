<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\SSHService;
use phpseclib3\Net\SSH2;
use App\Exceptions\SSHConnectionException;
use Mockery;
use Illuminate\Support\Facades\Log;

class SSHServiceMockTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_successful_connection_with_mock(): void
    {
        // Create a mock SSH2 instance
        $mockSSH2 = Mockery::mock(SSH2::class);
        $mockSSH2->shouldReceive('isConnected')->andReturn(true);
        $mockSSH2->shouldReceive('login')->with('testuser', 'testpass')->andReturn(true);
        $mockSSH2->shouldReceive('exec')->with('echo "Connection test successful"')->andReturn("Connection test successful\n");
        $mockSSH2->shouldReceive('getErrors')->andReturn([]);

        // Create a mock SSHService that uses our mock SSH2
        $sshService = $this->getMockBuilder(SSHService::class)
            ->onlyMethods(['createSSH2Instance'])
            ->getMock();

        $sshService->expects($this->once())
            ->method('createSSH2Instance')
            ->willReturn($mockSSH2);

        // Test the connection
        try {
            $sshService->connect('localhost', 'testuser', 'testpass');
            $this->assertTrue(true, 'Connection successful');
        } catch (SSHConnectionException $e) {
            $this->fail('Connection should have succeeded: ' . $e->getMessage());
        }
    }

    public function test_failed_connection_with_mock(): void
    {
        // Create a mock SSH2 instance that fails to connect
        $mockSSH2 = Mockery::mock(SSH2::class);
        $mockSSH2->shouldReceive('isConnected')->andReturn(false);
        $mockSSH2->shouldReceive('getErrors')->andReturn(['Connection refused']);
        $mockSSH2->shouldReceive('getLog')->andReturn(['Failed to establish connection']);

        // Create a mock SSHService
        $sshService = $this->getMockBuilder(SSHService::class)
            ->onlyMethods(['createSSH2Instance'])
            ->getMock();

        $sshService->expects($this->once())
            ->method('createSSH2Instance')
            ->willReturn($mockSSH2);

        $this->expectException(SSHConnectionException::class);
        $this->expectExceptionMessage('Connection failed: Connection refused');

        $sshService->connect('localhost', 'testuser', 'testpass');
    }

    public function test_failed_authentication_with_mock(): void
    {
        // Create a mock SSH2 instance that fails authentication
        $mockSSH2 = Mockery::mock(SSH2::class);
        $mockSSH2->shouldReceive('isConnected')->andReturn(true);
        $mockSSH2->shouldReceive('login')->andReturn(false);
        $mockSSH2->shouldReceive('getErrors')->andReturn(['Authentication failed']);
        $mockSSH2->shouldReceive('getLog')->andReturn(['Authentication attempt failed']);

        // Create a mock SSHService
        $sshService = $this->getMockBuilder(SSHService::class)
            ->onlyMethods(['createSSH2Instance'])
            ->getMock();

        $sshService->expects($this->once())
            ->method('createSSH2Instance')
            ->willReturn($mockSSH2);

        $this->expectException(SSHConnectionException::class);
        $this->expectExceptionMessage('Authentication failed: Authentication failed');

        $sshService->connect('localhost', 'testuser', 'testpass');
    }

    public function test_keyboard_interactive_authentication_with_mock(): void
    {
        // Create a mock SSH2 instance that fails password auth but succeeds with keyboard-interactive
        $mockSSH2 = Mockery::mock(SSH2::class);
        $mockSSH2->shouldReceive('isConnected')->andReturn(true);
        $mockSSH2->shouldReceive('login')
            ->with('testuser', 'testpass')
            ->once()
            ->andReturn(false);
        $mockSSH2->shouldReceive('login')
            ->with('testuser', 'testpass', 'keyboard-interactive')
            ->once()
            ->andReturn(true);
        $mockSSH2->shouldReceive('exec')
            ->with('echo "Connection test successful"')
            ->andReturn("Connection test successful\n");
        $mockSSH2->shouldReceive('getErrors')->andReturn([]);

        // Create a mock SSHService
        $sshService = $this->getMockBuilder(SSHService::class)
            ->onlyMethods(['createSSH2Instance'])
            ->getMock();

        $sshService->expects($this->once())
            ->method('createSSH2Instance')
            ->willReturn($mockSSH2);

        try {
            $sshService->connect('localhost', 'testuser', 'testpass');
            $this->assertTrue(true, 'Connection successful with keyboard-interactive authentication');
        } catch (SSHConnectionException $e) {
            $this->fail('Connection should have succeeded with keyboard-interactive: ' . $e->getMessage());
        }
    }

    public function test_successful_command_execution_with_mock(): void
    {
        // Create a mock SSH2 instance
        $mockSSH2 = Mockery::mock(SSH2::class);
        $mockSSH2->shouldReceive('isConnected')->andReturn(true);
        $mockSSH2->shouldReceive('login')->with('testuser', 'testpass')->andReturn(true);
        $mockSSH2->shouldReceive('exec')
            ->with('echo "Connection test successful"')
            ->andReturn("Connection test successful\n");
        $mockSSH2->shouldReceive('exec')
            ->with('ls -la')
            ->andReturn("total 123\ndrwxr-xr-x 2 user user 4096 Mar 19 10:00 .\n");
        $mockSSH2->shouldReceive('getErrors')->andReturn([]);

        // Create a mock SSHService
        $sshService = $this->getMockBuilder(SSHService::class)
            ->onlyMethods(['createSSH2Instance'])
            ->getMock();

        $sshService->expects($this->once())
            ->method('createSSH2Instance')
            ->willReturn($mockSSH2);

        // Connect and execute command
        $sshService->connect('localhost', 'testuser', 'testpass');
        $output = $sshService->executeCommand('ls -la');

        $this->assertNotEmpty($output);
        $this->assertStringContainsString('total', $output);
        $this->assertStringContainsString('drwxr-xr-x', $output);
    }

    public function test_failed_command_execution_with_mock(): void
    {
        // Create a mock SSH2 instance
        $mockSSH2 = Mockery::mock(SSH2::class);
        $mockSSH2->shouldReceive('isConnected')->andReturn(true);
        $mockSSH2->shouldReceive('login')->with('testuser', 'testpass')->andReturn(true);
        $mockSSH2->shouldReceive('exec')
            ->with('echo "Connection test successful"')
            ->andReturn("Connection test successful\n");
        $mockSSH2->shouldReceive('exec')
            ->with('invalid_command')
            ->andReturn(false);
        $mockSSH2->shouldReceive('getErrors')
            ->andReturn(['Command not found']);

        // Create a mock SSHService
        $sshService = $this->getMockBuilder(SSHService::class)
            ->onlyMethods(['createSSH2Instance'])
            ->getMock();

        $sshService->expects($this->once())
            ->method('createSSH2Instance')
            ->willReturn($mockSSH2);

        // Connect and attempt to execute invalid command
        $sshService->connect('localhost', 'testuser', 'testpass');

        $this->expectException(SSHConnectionException::class);
        $this->expectExceptionMessage('Command execution failed: Command not found');

        $sshService->executeCommand('invalid_command');
    }

    public function test_connection_timeout_with_mock(): void
    {
        // Create a mock SSH2 instance that simulates a timeout
        $mockSSH2 = Mockery::mock(SSH2::class);
        $mockSSH2->shouldReceive('isConnected')->andReturn(false);
        $mockSSH2->shouldReceive('getErrors')->andReturn(['Connection timed out']);
        $mockSSH2->shouldReceive('getLog')->andReturn(['Connection attempt timed out after 30 seconds']);

        // Create a mock SSHService
        $sshService = $this->getMockBuilder(SSHService::class)
            ->onlyMethods(['createSSH2Instance'])
            ->getMock();

        $sshService->expects($this->once())
            ->method('createSSH2Instance')
            ->willReturn($mockSSH2);

        $this->expectException(SSHConnectionException::class);
        $this->expectExceptionMessage('Connection failed: Connection timed out');

        $sshService->connect('localhost', 'testuser', 'testpass');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
