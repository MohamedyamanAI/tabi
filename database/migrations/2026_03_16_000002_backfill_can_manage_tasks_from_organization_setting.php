<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Backfill can_manage_tasks for existing employees when their organization had employees_can_manage_tasks enabled.
     */
    public function up(): void
    {
        DB::table('members')
            ->where('role', 'employee')
            ->whereIn('organization_id', function ($query): void {
                $query->select('id')
                    ->from('organizations')
                    ->where('employees_can_manage_tasks', true);
            })
            ->update(['can_manage_tasks' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverting would set everyone back to false; we don't have a way to know who was backfilled.
        // No-op.
    }
};
