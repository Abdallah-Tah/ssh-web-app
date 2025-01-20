<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\SSHService;
use App\Services\ServerMonitoringService;
use App\Http\Requests\SSH\ConnectRequest;
use App\Http\Requests\SSH\ExecuteRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use App\Jobs\ExecuteLongRunningCommand;

class SSHController extends Controller
{
    public function __construct(
        private SSHService $sshService,
        private ServerMonitoringService $monitoringService
    ) {}

    public function connect(ConnectRequest $request): JsonResponse
    {
        try {
            $this->sshService->connect(
                $request->hostname,
                $request->username,
                $request->password
            );

            return response()->json([
                'message' => __('ssh.connection.success', ['hostname' => $request->hostname])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => __('ssh.connection.failed', ['error' => $e->getMessage()])
            ], 500);
        }
    }

    public function execute(ExecuteRequest $request): JsonResponse
    {
        try {
            if ($request->is_long_running) {
                ExecuteLongRunningCommand::dispatch(
                    $request->command,
                    $request->hostname,
                    $request->username,
                    $request->password
                );

                return response()->json([
                    'message' => __('ssh.command.queued')
                ]);
            }

            $output = $this->sshService->executeCommand($request->command);
            return response()->json(['output' => $output]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => __('ssh.command.failed', ['error' => $e->getMessage()])
            ], 500);
        }
    }

    public function metrics(): JsonResponse
    {
        return response()->json(
            Cache::remember('system-metrics', 60, function () {
                return $this->monitoringService->getSystemMetrics();
            })
        );
    }
}
