<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->boolean('activity_tracking_enabled')->default(false);
            $table->boolean('app_activity_sync_enabled')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->dropColumn(['activity_tracking_enabled', 'app_activity_sync_enabled']);
        });
    }
};
