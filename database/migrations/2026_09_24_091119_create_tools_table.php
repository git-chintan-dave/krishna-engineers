<?php

use App\Enums\Status;
use App\Enums\ToolType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tools', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->enum('type', array_column(ToolType::cases(), 'value'));
            $table->string('name');
            $table->string('image')->nullable();
            $table->unsignedInteger('quantity')->default(0)->comment('stock quantity');
            $table->enum('status', array_column(Status::cases(), 'value'))->default(Status::ACTIVE->value);
            $table->timestamps();

            $table->index('type');
            $table->index('status');
            $table->unique(['type', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tools');
    }
};
