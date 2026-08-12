<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class ToolsUser extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'tools_user';

    protected $fillable = [
        'nama',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * Auth identifier field used by Laravel (username = nama).
     */
    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }
}
