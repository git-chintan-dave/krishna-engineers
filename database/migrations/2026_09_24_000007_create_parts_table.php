<?php

use App\Enums\Status;
use App\Models\Section;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('parts', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('name');
            $table->string('new_part_name')->nullable()->unique();
            $table->foreignIdFor(Section::class)
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();
            $table->string('image')->nullable();
            $table->string('cutting_size')->nullable();
            $table->string('part_weight')->nullable();
            $table->string('raw_material_weight')->nullable();
            $table->enum('status', array_column(Status::cases(), 'value'))->default(Status::ACTIVE->value);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parts');
    }
};
