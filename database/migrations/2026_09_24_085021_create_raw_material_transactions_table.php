<?php

use App\Enums\RawMaterialTransactionType;
use App\Models\SectionSize;
use App\Models\User;
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
        Schema::create('raw_material_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignIdFor(SectionSize::class)
                ->constrained()
                ->restrictOnDelete();
            $table->enum(
                'type',
                array_column(RawMaterialTransactionType::cases(), 'value')
            );
            $table->unsignedInteger('quantity');
            $table->foreignIdFor(User::class)
                ->nullable()
                ->nullOnDelete();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['section_size_id', 'type']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raw_material_transactions');
    }
};
