<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiRequestLogViewer extends Model
{
    protected $table = 'monitoring.api_request_log_viewer';

    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = null;

    protected $fillable = [
        'api_request_log_id',
        'environment',
        'event_slug',
        'reference_value',
        'created_date',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'api_request_log_id' => 'integer',
            'created_date' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }
}
