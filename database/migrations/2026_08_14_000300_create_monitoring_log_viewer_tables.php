<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS monitoring');

        if (! Schema::hasTable('monitoring.api_request_log_viewer')) {
            Schema::create('monitoring.api_request_log_viewer', function (Blueprint $table) {
                $table->unsignedBigInteger('api_request_log_id');
                $table->string('environment', 20);
                $table->string('event_slug', 100);
                $table->string('reference_value', 500)->nullable();
                $table->timestamp('created_date');
                $table->timestamp('synced_at')->useCurrent();

                $table->primary(['environment', 'api_request_log_id']);
                $table->index(
                    ['environment', 'event_slug', 'reference_value', 'created_date'],
                    'idx_log_viewer_env_event_ref_date'
                );
                $table->index(
                    ['environment', 'created_date'],
                    'idx_log_viewer_env_created_date'
                );
            });
        }

        if (! Schema::hasTable('monitoring.log_sync_state')) {
            Schema::create('monitoring.log_sync_state', function (Blueprint $table) {
                $table->id();
                $table->string('environment', 20)->unique();
                $table->timestamp('last_synced_created_date')->nullable();
                $table->unsignedBigInteger('last_synced_id')->nullable();
                $table->timestamp('last_sync_started_at')->nullable();
                $table->timestamp('last_sync_finished_at')->nullable();
                $table->string('status', 20)->default('idle');
                $table->unsignedInteger('last_sync_records')->default(0);
                $table->text('last_error')->nullable();
                $table->timestamps();
            });

            DB::table('monitoring.log_sync_state')->insert([
                [
                    'environment' => 'prod',
                    'status' => 'idle',
                    'last_sync_records' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'environment' => 'preprod',
                    'status' => 'idle',
                    'last_sync_records' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring.api_request_log_viewer');
        Schema::dropIfExists('monitoring.log_sync_state');
    }
};
