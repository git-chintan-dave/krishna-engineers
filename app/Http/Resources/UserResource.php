<?php

namespace App\Http\Resources;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\OperatorProductionEfficiencyService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $includeProductionEfficiency = $request->routeIs('employees.index');

        $productionEfficiency = null;

        if ($includeProductionEfficiency && $this->role === UserRole::OPERATOR) {
            $productionEfficiency = app(OperatorProductionEfficiencyService::class)
                ->calculateForPeriod(
                    userId: $this->id,
                    startDate: Carbon::parse('2026-01-01'),
                    endDate: now(),
                );
        }

        return [
            // Employee Information
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'employee_code' => $this->employee_code,
            'phone_number' => $this->phone_number,
            'alternate_phone_number' => $this->alternate_phone_number,
            'email' => $this->email,
            'joining_date' => $this->joining_date?->format('d M Y'),

            // Employee Configuration
            'role' => $this->role?->value,
            'role_label' => $this->role?->label(),
            'shift' => $this->shift?->value,
            'shift_label' => $this->shift?->label(),

            // Salary Configuration
            'salary' => $this->salary,
            'incentive_per_day' => $this->incentive_per_day,
            'deduction_per_day' => $this->deduction_per_day,

            // Account Status
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),

            // Timestamps
            'created_at' => $this->created_at?->format('d M Y'),
            'updated_at' => $this->updated_at?->format('d M Y'),

            // Production Efficiency (Index only)
            'production_efficiency' => $this->when(
                $includeProductionEfficiency && $this->role === UserRole::OPERATOR,
                fn () => $productionEfficiency,
            ),
        ];
    }
}
