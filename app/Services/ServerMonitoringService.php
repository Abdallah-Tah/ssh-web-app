<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\SSHConnectionException;

class ServerMonitoringService
{
    public function __construct(
        private SSHService $sshService
    ) {}

    public function getSystemMetrics(): array
    {
        $metrics = [];

        // CPU Usage
        $cpuCommand = "top -bn1 | grep 'Cpu(s)' | awk '{print $2}'";
        $metrics['cpu'] = (float) $this->sshService->executeCommand($cpuCommand);

        // Memory Usage
        $memCommand = "free | grep Mem | awk '{print ($3/$2) * 100}'";
        $metrics['memory'] = (float) $this->sshService->executeCommand($memCommand);

        // Disk Usage
        $diskCommand = "df / | tail -1 | awk '{print $5}' | sed 's/%//'";
        $metrics['disk'] = (float) $this->sshService->executeCommand($diskCommand);

        return $metrics;
    }

    public function getProcessList(): array
    {
        $command = "ps aux | head -n 11"; // Get top 10 processes (excluding header)
        $output = $this->sshService->executeCommand($command);

        $lines = array_filter(explode("\n", $output));
        $header = array_shift($lines); // Remove header line

        $processes = [];
        foreach ($lines as $line) {
            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) >= 11) {
                $processes[] = [
                    'user' => $parts[0],
                    'pid' => (int) $parts[1],
                    'cpu' => (float) $parts[2],
                    'memory' => (float) $parts[3],
                    'command' => implode(' ', array_slice($parts, 10))
                ];
            }
        }

        return $processes;
    }

    public function getSystemInfo(): array
    {
        $info = [];

        // OS Information
        $info['os'] = trim($this->sshService->executeCommand('cat /etc/os-release | grep PRETTY_NAME | cut -d= -f2'));

        // Kernel Version
        $info['kernel'] = trim($this->sshService->executeCommand('uname -r'));

        // Uptime
        $info['uptime'] = trim($this->sshService->executeCommand('uptime -p'));

        // Load Average
        $loadAvg = explode(' ', trim($this->sshService->executeCommand('cat /proc/loadavg')));
        $info['load_average'] = [
            '1min' => $loadAvg[0] ?? 0,
            '5min' => $loadAvg[1] ?? 0,
            '15min' => $loadAvg[2] ?? 0
        ];

        return $info;
    }
}
