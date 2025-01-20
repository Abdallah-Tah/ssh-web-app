<?php

declare(strict_types=1);

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\SSHService;
use App\Services\SFTPService;
use App\Services\ServerMonitoringService;
use App\Models\ServerConfig;
use App\Exceptions\{SSHConnectionException, SFTPException};
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Prism\Facades\Prism;
use Illuminate\Http\UploadedFile;

class SSHApp extends Component
{
    use WithFileUploads;

    public string $hostname = '';
    public string $username = '';
    public string $password = '';
    public int $port = 22;
    public string $command = '';
    public string $output = '';
    public bool $isConnected = false;
    public ?string $errorMessage = null;
    public string $aiSuggestion = '';
    public bool $isLoadingSuggestion = false;
    public array $commandSuggestions = [];
    public bool $showAutocomplete = false;
    public bool $showSaveForm = false;
    public string $configName = '';
    public ?int $selectedConfigId = null;
    public array $systemMetrics = [];
    public array $processList = [];
    public bool $showMonitoring = false;
    public bool $showFileManager = false;
    public string $currentPath = '.';
    public array $fileList = [];
    public ?UploadedFile $uploadFile = null;
    public string $selectedFile = '';
    public bool $showUploadModal = false;

    protected SSHService $sshService;
    protected ServerMonitoringService $monitoringService;
    protected SFTPService $sftpService;

    protected $rules = [
        'hostname' => 'required|string',
        'username' => 'required|string',
        'password' => 'required|string',
        'port' => 'required|integer|min:1|max:65535',
    ];

    public function __construct()
    {
        $this->sshService = app(SSHService::class);
        $this->monitoringService = new ServerMonitoringService($this->sshService);
        $this->sftpService = app(SFTPService::class);
    }

    public function mount(): void
    {

    }

    public function connect(): void
    {
        try {
            Log::info('Starting SSH connection attempt', [
                'hostname' => $this->hostname,
                'username' => $this->username,
                'port' => $this->port
            ]);

            $this->validate();

            Log::info('Validation passed, attempting SSH connection');
            $this->sshService->connect(
                $this->hostname,
                $this->username,
                $this->port,
                $this->password
            );
            $this->isConnected = true;
            $this->errorMessage = null;

            // Initialize system metrics
            try {
                $this->updateSystemMetrics();
            } catch (\Exception $e) {
                Log::warning('Failed to update system metrics', ['error' => $e->getMessage()]);
                // Don't fail the connection if metrics update fails
            }

            Log::info('SSH connection successful, attempting SFTP connection');
            $this->sftpService->connect($this->hostname, $this->username, $this->password, $this->port);

            Log::info('All connections successful');

            $this->dispatch('connection-established');
        } catch (SSHConnectionException $e) {
            $this->isConnected = false;
            $this->errorMessage = $e->getMessage();
            Log::error('SSH Connection failed', ['error' => $e->getMessage()]);
        } catch (SFTPException $e) {
            $this->isConnected = false;
            $this->errorMessage = $e->getMessage();
            Log::error('SFTP Connection failed', ['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            $this->isConnected = false;
            $this->errorMessage = 'Failed to connect: ' . $e->getMessage();
            Log::error('Connection failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
    }

    public function updateSystemMetrics(): void
    {
        if (!$this->isConnected) {
            return;
        }

        try {
            $metrics = $this->sshService->getSystemMetrics();
            $this->systemMetrics = [
                'cpu' => $metrics['cpu'] ?? 0,
                'memory' => $metrics['memory'] ?? 0,
                'disk' => $metrics['disk'] ?? 0
            ];
        } catch (\Exception $e) {
            Log::error('Failed to update system metrics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Don't throw the error to the UI, just log it
        }
    }

    public function getCommandSuggestion(): void
    {
        $this->isLoadingSuggestion = true;

        try {
            // Basic command suggestions without AI
            $this->aiSuggestion = 'Command suggestions are currently unavailable.';
        } catch (\Exception $e) {
            Log::error('Command suggestion failed', ['error' => $e->getMessage()]);
            $this->aiSuggestion = 'Unable to generate suggestion at this time.';
        } finally {
            $this->isLoadingSuggestion = false;
        }
    }

    public function executeCommand(): void
    {
        try {
            $this->validate([
                'command' => 'required',
            ]);

            $this->output = $this->sshService->executeCommand($this->command);
            $this->getCommandSuggestion();
            $this->command = '';
        } catch (SSHConnectionException $e) {
            $this->errorMessage = $e->getMessage();
            Log::error('SSH Command execution failed', ['error' => $e->getMessage()]);
        }
    }

    public function disconnect(): void
    {
        $this->sshService->disconnect();
        $this->sftpService->disconnect();
        $this->isConnected = false;
        $this->output = '';
        $this->errorMessage = null;
    }

    public function updatedCommand(): void
    {
        if (strlen($this->command) < 2) {
            $this->commandSuggestions = [];
            $this->showAutocomplete = false;
            return;
        }

        try {
            // Basic command suggestions without AI
            $commonCommands = [
                'ls', 'cd', 'pwd', 'mkdir', 'rm', 'cp', 'mv',
                'cat', 'grep', 'find', 'ps', 'top', 'df', 'du'
            ];

            $this->commandSuggestions = array_filter($commonCommands, function($cmd) {
                return str_starts_with($cmd, $this->command);
            });

            $this->showAutocomplete = !empty($this->commandSuggestions);
        } catch (\Exception $e) {
            Log::error('Command autocomplete failed', ['error' => $e->getMessage()]);
            $this->commandSuggestions = [];
            $this->showAutocomplete = false;
        }
    }

    public function selectSuggestion(string $suggestion): void
    {
        $this->command = $suggestion;
        $this->showAutocomplete = false;
    }

    public function saveServerConfig(): void
    {
        $this->validate([
            'configName' => 'required|max:255',
            'hostname' => 'required',
            'username' => 'required',
            'password' => 'required',
            'port' => 'required|integer|min:1|max:65535',
        ]);

        Auth::user()->serverConfigs()->create([
            'name' => $this->configName,
            'hostname' => $this->hostname,
            'username' => $this->username,
            'password' => $this->password,
            'port' => $this->port,
        ]);

        $this->showSaveForm = false;
        $this->configName = '';
        $this->dispatch('server-config-saved');
    }

    public function loadServerConfig(int $configId): void
    {
        $config = Auth::user()->serverConfigs()->findOrFail($configId);

        $this->selectedConfigId = $configId;
        $this->hostname = $config->hostname;
        $this->username = $config->username;
        $this->password = $config->password;
        $this->port = $config->port ?? 22;
    }

    public function deleteServerConfig(int $configId): void
    {
        Auth::user()->serverConfigs()->findOrFail($configId)->delete();

        if ($this->selectedConfigId === $configId) {
            $this->selectedConfigId = null;
            $this->hostname = '';
            $this->username = '';
            $this->password = '';
        }

        $this->dispatch('server-config-deleted');
    }

    public function toggleMonitoring(): void
    {
        $this->showMonitoring = !$this->showMonitoring;
        if ($this->showMonitoring) {
            $this->refreshMetrics();
        }
    }

    public function refreshMetrics(): void
    {
        try {
            if (!$this->isConnected || !$this->showMonitoring) {
                return;
            }

            $this->systemMetrics = $this->monitoringService->getSystemMetrics();
            $this->processList = $this->monitoringService->getProcessList();
        } catch (SSHConnectionException $e) {
            $this->errorMessage = $e->getMessage();
            Log::error('Failed to refresh metrics', ['error' => $e->getMessage()]);
        }
    }

    public function toggleFileManager(): void
    {
        $this->showFileManager = !$this->showFileManager;
        if ($this->showFileManager) {
            $this->refreshFileList();
        }
    }

    public function refreshFileList(): void
    {
        try {
            $this->fileList = $this->sftpService->listDirectory($this->currentPath);
        } catch (SFTPException $e) {
            $this->errorMessage = $e->getMessage();
            Log::error('Failed to list directory', ['error' => $e->getMessage()]);
        }
    }

    public function changeDirectory(string $path): void
    {
        $this->currentPath = $path;
        $this->refreshFileList();
    }

    public function downloadFile(string $path): void
    {
        try {
            $content = $this->sftpService->downloadFile($path);
            $filename = basename($path);

            // Emit a browser event with the file content and filename
            $this->dispatchBrowserEvent('file-download', [
                'content' => $content,
                'filename' => $filename,
            ]);
        } catch (SFTPException $e) {
            $this->errorMessage = $e->getMessage();
            Log::error('Failed to download file', ['error' => $e->getMessage()]);
        }
    }

    public function uploadFile(): void
    {
        $this->validate([
            'uploadFile' => 'required|file|max:10240', // 10MB max
        ]);

        try {
            $filename = $this->uploadFile->getClientOriginalName();
            $remotePath = rtrim($this->currentPath, '/') . '/' . $filename;

            $this->sftpService->uploadFile($remotePath, $this->uploadFile);
            $this->uploadFile = null;
            $this->showUploadModal = false;
            $this->refreshFileList();
        } catch (SFTPException $e) {
            $this->errorMessage = $e->getMessage();
            Log::error('Failed to upload file', ['error' => $e->getMessage()]);
        }
    }

    public function deleteFile(string $path): void
    {
        try {
            $this->sftpService->deleteFile($path);
            $this->refreshFileList();
        } catch (SFTPException $e) {
            $this->errorMessage = $e->getMessage();
            Log::error('Failed to delete file', ['error' => $e->getMessage()]);
        }
    }

    public function getPollingListeners(): array
    {
        return [
            'refresh-metrics' => '$refresh',
        ];
    }

    public function render()
    {
        return view('livewire.ssh-app', [
            'savedConfigs' => Auth::user()->serverConfigs()->get()
        ]);
    }
}
