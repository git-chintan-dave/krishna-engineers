<?php

namespace App\Http\Resources;

use App\Models\Salary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Number;

/**
 * @mixin Salary
 */
class SalaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // Salary Information
            'id' => $this->id,
            'uuid' => $this->uuid,

            // Employee
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded(
                'user',
                fn(): array => [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'employee_code' => $this->user->employee_code,
                ],
            ),

            // Salary Period
            'salary_period_start' => $this->salary_period_start?->toDateString(),
            'salary_period_end' => $this->salary_period_end?->toDateString(),
            'salary_period_start_formatted' => $this->salary_period_start?->format('d M Y'),
            'salary_period_end_formatted' => $this->salary_period_end?->format('d M Y'),

            // Salary Calculation
            'base_salary' => $this->base_salary,
            'base_salary_formatted' => Number::currency($this->base_salary, in: 'INR'),
            'working_days' => $this->working_days,
            'incentive_rate' => $this->incentive_rate,
            'incentive_rate_formatted' => Number::currency($this->incentive_rate, in: 'INR'),
            'incentive' => $this->incentive,
            'incentive_formatted' => Number::currency($this->incentive, in: 'INR'),
            'deduction_rate' => $this->deduction_rate,
            'deduction_rate_formatted' => Number::currency($this->deduction_rate, in: 'INR'),
            'deduction' => $this->deduction,
            'deduction_formatted' => Number::currency($this->deduction, in: 'INR'),

            // Payment Adjustments
            'upad' => $this->upad,
            'upad_formatted' => Number::currency($this->upad, in: 'INR'),
            'paid_loan' => $this->paid_loan,
            'paid_loan_formatted' => Number::currency($this->paid_loan, in: 'INR'),
            'adjustment' => $this->adjustment,
            'adjustment_formatted' => Number::currency($this->adjustment, in: 'INR'),
            'adjustment_reason' => $this->adjustment_reason,

            // Final Payable
            'final_payable' => $this->final_payable,
            'final_payable_formatted' => Number::currency($this->final_payable, in: 'INR'),

            // Payment Status
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'paid_at' => $this->paid_at?->toISOString(),
            'paid_at_formatted' => $this->paid_at?->format('d M Y h:i A'),

            // Timestamps
            'created_at' => $this->created_at?->format('d M Y'),
        ];
    }
}
