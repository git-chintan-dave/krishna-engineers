<?php

namespace App\Http\Resources;

use App\Models\Salary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'id' => $this->id,
            'uuid' => $this->uuid,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded(
                'user',
                fn (): array => [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'employee_code' => $this->user->employee_code,
                ],
            ),
            'salary_period_start' => $this->salary_period_start?->toDateString(),
            'salary_period_end' => $this->salary_period_end?->toDateString(),
            'salary_period_start_formatted' => $this->salary_period_start?->format('d M Y'),
            'salary_period_end_formatted' => $this->salary_period_end?->format('d M Y'),
            'base_salary' => $this->base_salary,
            'working_days' => $this->working_days,
            'upad' => $this->upad,
            'paid_loan' => $this->paid_loan,
            'incentive_rate' => $this->incentive_rate,
            'incentive' => $this->incentive,
            'deduction_rate' => $this->deduction_rate,
            'deduction' => $this->deduction,
            'adjustment' => $this->adjustment,
            'adjustment_reason' => $this->adjustment_reason,
            'final_payable' => $this->final_payable,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'paid_at' => $this->paid_at?->toISOString(),
            'paid_at_formatted' => $this->paid_at?->format('d M Y h:i A'),
            'created_at' => $this->created_at?->format('d M Y'),
        ];
    }
}
