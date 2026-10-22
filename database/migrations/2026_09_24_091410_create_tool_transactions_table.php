<?php

use App\Enums\ToolTransactionType;
use App\Models\Part;
use App\Models\Setup;
use App\Models\Tool;
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
        Schema::create('tool_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignIdFor(Tool::class)
                ->constrained()
                ->restrictOnDelete();
            $table->enum(
                'type',
                array_column(ToolTransactionType::cases(), 'value')
            );
            $table->integer('quantity');
            $table->foreignId('from_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('to_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignIdFor(Part::class)
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->foreignIdFor(Setup::class)
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->boolean('return_scrap')->nullable();
            $table->foreignId('created_by_user_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['tool_id', 'type']);
            $table->index(['from_user_id', 'to_user_id']);
            $table->index(['part_id', 'setup_id']);
            $table->index('created_by_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tool_transactions');
    }
};
