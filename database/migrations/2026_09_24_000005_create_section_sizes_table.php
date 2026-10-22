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
        Schema::create('section_sizes', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('section_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('size');
            $table->string('weight')->nullable();
            $table->unsignedInteger('quantity')->default(0)->comment('stock quantity');
            $table->timestamps();

            $table->unique(['section_id', 'size']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('section_sizes');
    }
};
