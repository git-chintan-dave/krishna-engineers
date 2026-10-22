<?php

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
        Schema::create('part_setups', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('part_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('setup_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('image')->nullable();
            $table->unsignedInteger('machine_cycle_time_seconds')->default(0);
            $table->unsignedInteger('actual_cycle_time_seconds')->default(0);
            $table->timestamps();

            $table->unique(['part_id', 'setup_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('part_setups');
    }
};
