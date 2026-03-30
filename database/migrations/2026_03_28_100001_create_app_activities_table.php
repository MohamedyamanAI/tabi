<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_activities', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->timestampTz('timestamp');
            $table->string('app_name', 255);
            $table->text('window_title');
            $table->text('url')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->uuid('time_entry_id');
            $table->uuid('member_id');
            $table->uuid('organization_id');
            $table->timestampsTz();

            $table->foreign('time_entry_id')
                ->references('id')
                ->on('time_entries')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreign('member_id')
                ->references('id')
                ->on('members')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->unique(['time_entry_id', 'timestamp', 'app_name'], 'app_activities_time_entry_timestamp_app_unique');
            $table->index(['organization_id', 'member_id']);
            $table->index('timestamp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_activities');
    }
};
