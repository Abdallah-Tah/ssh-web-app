<?php

return [
    'connection' => [
        'success' => 'Successfully connected to :hostname',
        'failed' => 'Failed to connect: :error',
        'rate_limited' => 'Too many connection attempts. Please try again in :seconds seconds.',
    ],
    'command' => [
        'executed' => 'Command executed successfully',
        'failed' => 'Command execution failed: :error',
        'queued' => 'Long-running command has been queued for execution',
    ],
    'metrics' => [
        'unavailable' => 'System metrics are currently unavailable',
    ],
];
