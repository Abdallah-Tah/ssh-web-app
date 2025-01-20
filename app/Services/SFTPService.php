<?php

declare(strict_types=1);

namespace App\Services;

use phpseclib3\Net\SFTP;
use App\Exceptions\SFTPException;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;

class SFTPService
{
    private ?SFTP $connection = null;

    public function connect(string $hostname, string $username, string $password): void
    {
        try {
            $this->connection = new SFTP($hostname);

            if (!$this->connection->login($username, $password)) {
                throw new SFTPException('Failed to authenticate SFTP connection');
            }
        } catch (\Exception $e) {
            throw new SFTPException('Failed to establish SFTP connection: ' . $e->getMessage());
        }
    }

    public function disconnect(): void
    {
        if ($this->connection) {
            $this->connection->disconnect();
            $this->connection = null;
        }
    }

    public function listDirectory(string $path = '.'): array
    {
        if (!$this->connection) {
            throw new SFTPException('No active SFTP connection');
        }

        $list = $this->connection->nlist($path);
        if ($list === false) {
            throw new SFTPException('Failed to list directory');
        }

        return array_filter($list, fn($item) => $item !== '.' && $item !== '..');
    }

    public function downloadFile(string $path): string
    {
        if (!$this->connection) {
            throw new SFTPException('No active SFTP connection');
        }

        $content = $this->connection->get($path);
        if ($content === false) {
            throw new SFTPException('Failed to download file');
        }

        return $content;
    }

    public function uploadFile(string $remotePath, UploadedFile $file): void
    {
        if (!$this->connection) {
            throw new SFTPException('No active SFTP connection');
        }

        $success = $this->connection->put($remotePath, $file->get(), SFTP::SOURCE_STRING);
        if (!$success) {
            throw new SFTPException('Failed to upload file');
        }
    }

    public function deleteFile(string $path): void
    {
        if (!$this->connection) {
            throw new SFTPException('No active SFTP connection');
        }

        if (!$this->connection->delete($path)) {
            throw new SFTPException('Failed to delete file');
        }
    }

    private function formatPermissions(int $mode): string
    {
        $permissions = '';
        $types = ['r', 'w', 'x'];

        for ($i = 0; $i < 3; $i++) {
            for ($j = 0; $j < 3; $j++) {
                $permissions .= ($mode & (1 << (8 - $i * 3 - $j))) ? $types[$j] : '-';
            }
        }

        return $permissions;
    }
}
