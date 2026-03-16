<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds task status: active, for_review, for_later, cancelled, done.
     * Backfills status = 'done' where done_at is set.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->string('status', 32)->default('active')->after('assignee_id');
        });

        DB::table('tasks')
            ->whereNotNull('done_at')
            ->update(['status' => 'done']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropColumn('status');
        });
    }
};
