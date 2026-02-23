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
        Schema::create('screenshots', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('storage_path');
            $table->dateTime('captured_at');
            $table->uuid('time_entry_id');
            $table->uuid('member_id');
            $table->uuid('organization_id');
            $table->timestamps();

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

            $table->index('captured_at');
            $table->index(['organization_id', 'member_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('screenshots');
    }
};
