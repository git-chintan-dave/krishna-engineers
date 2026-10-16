<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bandsaw_work_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->date('work_date');
            $table->foreignId('part_id')
                ->constrained('parts')
                ->restrictOnDelete();
            $table->foreignId('section_size_id')
                ->constrained('section_sizes')
                ->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->text('remarks')
                ->nullable();
            $table->timestamps();

            $table->index([
                'user_id',
                'work_date',
            ]);

            $table->index([
                'work_date',
                'part_id',
            ]);

            $table->index([
                'work_date',
                'section_size_id',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bandsaw_work_logs');
    }
};
