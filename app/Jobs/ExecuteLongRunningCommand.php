<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\SSHService;

class ExecuteLongRunningCommand implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private string $command,
        private string $hostname,
        private string $username,
        private string $password
    ) {}

    public function handle(SSHService $sshService): void
    {
        try {
            $sshService->connect($this->hostname, $this->username, $this->password);
            $output = $sshService->executeCommand($this->command);

            // Store the result or dispatch an event
            event(new CommandExecuted($output));
        } catch (\Exception $e) {
            logger()->error('Long running command failed', [
                'command' => $this->command,
                'error' => $e->getMessage()
            ]);
            throw $e;
        } finally {
            $sshService->disconnect();
        }
    }
}
