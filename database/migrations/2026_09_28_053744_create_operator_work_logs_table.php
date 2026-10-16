<?php

use App\Enums\UserShift;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('operator_work_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->date('work_date');
            $table->enum('shift', array_column(UserShift::cases(), 'value'))
                ->nullable()
                ->index();
            $table->foreignId('machine_id')
                ->constrained('machines')
                ->restrictOnDelete();
            $table->foreignId('part_id')
                ->constrained('parts')
                ->restrictOnDelete();
            $table->foreignId('setup_id')
                ->constrained('setups')
                ->restrictOnDelete();
            $table->foreignId('part_setup_id')
                ->constrained('part_setups')
                ->restrictOnDelete();
            $table->unsignedInteger('machine_cycle_time');
            $table->unsignedInteger('actual_cycle_time');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('rejection_quantity')
                ->default(0);
            $table->unsignedBigInteger('production_seconds');
            $table->text('remarks')
                ->nullable();
            $table->timestamps();

            $table->index([
                'user_id',
                'work_date',
            ]);

            $table->index([
                'work_date',
                'machine_id',
            ]);

            $table->index([
                'work_date',
                'part_id',
            ]);

            $table->index([
                'work_date',
                'setup_id',
            ]);

            $table->index([
                'work_date',
                'shift',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operator_work_logs');
    }
};
