<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class ServerConfig extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'hostname',
        'username',
        'password',
        'port',
        'private_key_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'private_key_path',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'port' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if ($model->isDirty('password') && $model->password !== null) {
                $model->encrypted_password = Crypt::encryptString($model->password);
                $model->password = null; // Clear the unencrypted password
            }
        });
    }

    /**
     * Get the user that owns the server configuration.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the decrypted password.
     */
    public function getPasswordAttribute($value): ?string
    {
        if ($this->encrypted_password) {
            try {
                return Crypt::decryptString($this->encrypted_password);
            } catch (\Exception $e) {
                Log::error('Failed to decrypt password', ['error' => $e->getMessage()]);
                return null;
            }
        }
        return $value;
    }
}
