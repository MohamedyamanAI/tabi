<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            $table->boolean('can_manage_tasks')->default(false)->after('billable_rate');
            $table->boolean('can_manage_projects')->default(false)->after('can_manage_tasks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            $table->dropColumn(['can_manage_tasks', 'can_manage_projects']);
        });
    }
};
