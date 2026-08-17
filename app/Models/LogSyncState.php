<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogSyncState extends Model
{
    protected $table = 'monitoring.log_sync_state';

    protected $fillable = [
        'environment',
        'last_synced_created_date',
        'last_synced_id',
        'last_sync_started_at',
        'last_sync_finished_at',
        'status',
        'last_sync_records',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'last_synced_created_date' => 'datetime',
            'last_synced_id' => 'integer',
            'last_sync_started_at' => 'datetime',
            'last_sync_finished_at' => 'datetime',
            'last_sync_records' => 'integer',
        ];
    }
}
