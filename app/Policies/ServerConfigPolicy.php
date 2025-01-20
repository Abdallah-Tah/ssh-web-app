<?php

namespace App\Policies;

use App\Models\ServerConfig;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServerConfigPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ServerConfig $serverConfig): bool
    {
        return $user->id === $serverConfig->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ServerConfig $serverConfig): bool
    {
        return $user->id === $serverConfig->user_id;
    }

    public function delete(User $user, ServerConfig $serverConfig): bool
    {
        return $user->id === $serverConfig->user_id;
    }
}
