<?php

use App\Enums\SalaryStatus;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('salaries', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();

            $table->foreignIdFor(User::class)
                ->constrained()
                ->restrictOnDelete();

            // Salary Period
            $table->date('salary_period_start');
            $table->date('salary_period_end');

            // Salary Calculation
            $table->decimal('base_salary', 12, 2)->default(0);
            $table->unsignedSmallInteger('working_days')->default(0);

            $table->decimal('upad', 12, 2)->default(0);
            $table->decimal('paid_loan', 12, 2)->default(0);

            // Snapshot of employee salary configuration
            $table->decimal('incentive_rate', 12, 2)->default(0);
            $table->decimal('incentive', 12, 2)->default(0);

            $table->decimal('deduction_rate', 12, 2)->default(0);
            $table->decimal('deduction', 12, 2)->default(0);

            // Manual Adjustment
            $table->decimal('adjustment', 12, 2)->default(0);
            $table->text('adjustment_reason')->nullable();

            // Final Salary
            $table->decimal('final_payable', 12, 2)->default(0);

            // Payment
            $table->enum(
                'status',
                array_column(SalaryStatus::cases(), 'value'),
            )->default(SalaryStatus::DRAFT->value);

            $table->dateTime('paid_at')->nullable();

            $table->timestamps();

            $table->unique([
                'user_id',
                'salary_period_start',
                'salary_period_end',
            ]);

            $table->index([
                'user_id',
                'status',
            ]);

            $table->index([
                'salary_period_start',
                'salary_period_end',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salaries');
    }
};
