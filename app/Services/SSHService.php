<?php

namespace App\Services;

use phpseclib3\Net\SSH2;
use phpseclib3\Net\SFTP;
use App\Exceptions\SSHConnectionException;
use Illuminate\Support\Facades\Log;
use Spatie\Ssh\Ssh;

class SSHService
{
    private ?SSH2 $ssh = null;
    private ?SFTP $sftp = null;
    private string $lastHost = '';
    private string $lastUser = '';

    protected function checkServerConnectivity(string $hostname, int $port, int $timeout = 5): bool
    {
        $hostname = trim($hostname);

        Log::debug('Checking server connectivity', [
            'hostname' => $hostname,
            'port' => $port,
            'timeout' => $timeout,
            'php_version' => PHP_VERSION,
            'os' => PHP_OS,
            'env' => app()->environment()
        ]);

        try {
            for ($attempt = 1; $attempt <= 3; $attempt++) {
                $currentTimeout = $timeout * $attempt;
                Log::debug("Connectivity attempt {$attempt} with timeout {$currentTimeout}s");

                $socket = @fsockopen($hostname, $port, $errno, $errstr, $currentTimeout);
                if ($socket) {
                    fclose($socket);
                    Log::debug('Server is reachable', [
                        'attempt' => $attempt,
                        'timeout_used' => $currentTimeout
                    ]);
                    return true;
                }

                Log::warning("Attempt {$attempt} failed", [
                    'error_number' => $errno,
                    'error_message' => $errstr,
                    'timeout_used' => $currentTimeout
                ]);

                usleep(500000);
            }

            Log::error('All connectivity check attempts failed', [
                'last_error_number' => $errno ?? null,
                'last_error_message' => $errstr ?? null
            ]);

            if (PHP_OS_FAMILY === 'Windows') {
                $pingCommand = sprintf('ping -n 1 %s', escapeshellarg($hostname));
            } else {
                $pingCommand = sprintf('ping -c 1 %s', escapeshellarg($hostname));
            }

            $pingResult = shell_exec($pingCommand);

            if ($pingResult && (
                str_contains($pingResult, 'TTL=') ||
                str_contains($pingResult, ' 0% packet loss')
            )) {
                Log::info('Server reachable via ping, proceeding with connection attempt');
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Server connectivity check exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    public function connect(
        string $hostname,
        string $username,
        int $port = 22,
        ?string $password = null,
        ?string $privateKeyPath = null
    ): void {
        $hostname = trim($hostname);

        Log::info('Starting SSH connection attempt', [
            'hostname' => $hostname,
            'username' => $username,
            'port' => $port,
            'auth_type' => $password ? 'password' : 'private_key',
            'os' => PHP_OS,
            'php_version' => PHP_VERSION,
            'env' => app()->environment()
        ]);

        if (!$this->checkServerConnectivity($hostname, $port)) {
            $error = "Unable to reach server {$hostname} on port {$port}. Please check your firewall settings and ensure the host is accessible.";
            Log::error($error);
            throw new SSHConnectionException($error);
        }

        try {
            $this->ssh = new SSH2($hostname, $port);
            if ($privateKeyPath) {
                if (!file_exists($privateKeyPath)) {
                    throw new SSHConnectionException("Private key file not found at: {$privateKeyPath}");
                }
                $key = file_get_contents($privateKeyPath);
                if (!$this->ssh->login($username, $key)) {
                    throw new SSHConnectionException('SSH login with private key failed.');
                }
            } else {
                if (!$password) {
                    throw new SSHConnectionException('No authentication method provided. Provide a password or private key.');
                }
                if (!$this->ssh->login($username, $password)) {
                    throw new SSHConnectionException('SSH login with username/password failed.');
                }
            }

            $this->lastHost = $hostname;
            $this->lastUser = $username;
            $this->sftp = new SFTP($hostname, $port);
            if ($privateKeyPath) {
                $key = file_get_contents($privateKeyPath);
                if (!$this->sftp->login($username, $key)) {
                    throw new SSHConnectionException('SFTP login with private key failed.');
                }
            } else {
                if (!$this->sftp->login($username, $password)) {
                    throw new SSHConnectionException('SFTP login with username/password failed.');
                }
            }

            Log::info('SSH connection established successfully', [
                'host' => $hostname,
                'user' => $username
            ]);
        } catch (\Exception $e) {
            $message = $this->getUserFriendlyErrorMessage($e->getMessage());
            Log::error('Unexpected SSH connection error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'host' => $hostname,
                'user' => $username,
                'port' => $port
            ]);
            throw new SSHConnectionException($message);
        }
    }

    private function getUserFriendlyErrorMessage(string $error): string
    {
        $lowercaseError = strtolower($error);

        if (str_contains($lowercaseError, 'permission denied')) {
            return 'Authentication failed. Please check your credentials and try again.';
        }

        if (str_contains($lowercaseError, 'connection refused')) {
            return 'Connection refused. Please check if the SSH service is running and the port is correct.';
        }

        if (str_contains($lowercaseError, 'timed out')) {
            return 'Connection timed out. Please check your network connection and firewall settings.';
        }

        if (str_contains($lowercaseError, 'no route to host')) {
            return 'Could not reach the host. Check if the hostname is correct and the server is online.';
        }

        return 'SSH connection failed: ' . $error;
    }

    public function executeCommand(string $command): string
    {
        if (!$this->ssh) {
            throw new SSHConnectionException('No active SSH connection');
        }

        Log::info('Executing SSH command', [
            'command' => $command,
            'host' => $this->lastHost,
            'user' => $this->lastUser
        ]);

        try {
            // Use phpseclib's exec method directly
            $result = $this->ssh->exec($command);

            Log::debug('Command execution details', [
                'command' => $command,
                'output' => $result,
                'exit_status' => $this->ssh->getExitStatus()
            ]);

            if ($result === false || $this->ssh->getExitStatus() !== 0) {
                $error = $this->ssh->getStdError() ?: 'No error output available';
                Log::error('Command execution failed', [
                    'command' => $command,
                    'exit_status' => $this->ssh->getExitStatus(),
                    'error' => $error
                ]);
                throw new SSHConnectionException("Command execution failed: {$error}");
            }

            return trim($result);
        } catch (\Exception $e) {
            Log::error('Command execution error', [
                'error' => $e->getMessage(),
                'command' => $command,
                'host' => $this->lastHost,
                'user' => $this->lastUser,
                'trace' => $e->getTraceAsString()
            ]);
            throw new SSHConnectionException('Command execution failed: ' . $e->getMessage());
        }
    }

    public function disconnect(): void
    {
        $this->ssh = null;
        $this->sftp = null;
        $this->lastHost = '';
        $this->lastUser = '';
        Log::info('SSH connection cleared');
    }

    public function listDirectory(string $path = '.'): array
    {
        if (!$this->sftp) {
            throw new SSHConnectionException('No active SFTP connection');
        }

        $files = $this->sftp->rawlist($path);
        if ($files === false) {
            throw new SSHConnectionException("Unable to list directory: {$path}");
        }

        $result = [];
        foreach ($files as $file => $info) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $result[] = [
                'type' => ($info['type'] === 2 ? 'directory' : 'file'),
                'permissions' => $info['permissions'],
                'owner' => $info['uid'],
                'group' => $info['gid'],
                'size' => $info['size'],
                'modified' => date('Y-m-d H:i:s', $info['mtime']),
                'name' => $file,
            ];
        }
        return $result;
    }

    public function getSystemMetrics(): array
    {
        $cpuCommand = "top -bn1 | grep 'Cpu(s)' | awk '{print $2}'";
        $memoryCommand = "free | grep Mem | awk '{print ($3/$2) * 100}'";
        $diskCommand = "df / | tail -1 | awk '{print $5}' | sed 's/%//'";

        $cpu = (float) $this->executeCommand($cpuCommand);
        $memory = (float) $this->executeCommand($memoryCommand);
        $disk = (float) $this->executeCommand($diskCommand);

        return [
            'cpu' => round($cpu, 2),
            'memory' => round($memory, 2),
            'disk' => round($disk, 2),
        ];
    }

    public function readFile(string $path): string
    {
        if (!$this->sftp) {
            throw new SSHConnectionException('No active SFTP connection');
        }

        $content = $this->sftp->get($path);
        if ($content === false) {
            throw new SSHConnectionException("Unable to read file: {$path}");
        }

        return $content;
    }

    public function writeFile(string $path, string $contents): void
    {
        if (!$this->sftp) {
            throw new SSHConnectionException('No active SFTP connection');
        }

        if (!$this->sftp->put($path, $contents)) {
            throw new SSHConnectionException("Unable to write to file: {$path}");
        }
    }
}
