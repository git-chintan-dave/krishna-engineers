<?php

use App\Enums\Status;
use App\Enums\UserRole;
use App\Enums\UserShift;
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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();

            // Employee identity
            $table->string('name');
            $table->string('employee_code')->unique();
            $table->string('phone_number', 10)->unique()->nullable();
            $table->string('alternate_phone_number', 10)->nullable();
            $table->string('email')->nullable();
            $table->date('joining_date')->nullable();

            // Authentication
            $table->string('password');

            // Employee configuration
            $table->enum('role', array_column(UserRole::cases(), 'value'))->index();

            $table->enum('shift', array_column(UserShift::cases(), 'value'))
                ->nullable()
                ->index();

            // Salary configuration
            $table->decimal('salary', 12, 2)->default(0);
            $table->decimal('incentive_per_day', 12, 2)->default(0);
            $table->decimal('deduction_per_day', 12, 2)->default(0);

            // Employee identification
            $table->string('aadhar_number', 12)->nullable();

            // Account status
            $table->enum('status', array_column(Status::cases(), 'value'))
                ->default(Status::ACTIVE->value)
                ->index();

            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
