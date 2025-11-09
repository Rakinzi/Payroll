<?php

namespace App\Http\Controllers;

use App\Services\LeaveCalculationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveCalculationController extends Controller
{
    public function __construct(
        private readonly LeaveCalculationService $leaveCalculationService
    ) {}

    /**
     * Calculate working days between two dates
     */
    public function calculate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'working_days_policy' => 'sometimes|in:5_day,6_day,7_day',
            'exclude_saturdays' => 'sometimes|boolean',
            'exclude_sundays' => 'sometimes|boolean',
            'exclude_public_holidays' => 'sometimes|boolean',
            'custom_holidays' => 'sometimes|array',
            'custom_holidays.*' => 'date_format:Y-m-d',
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);

        // Use company defaults or request values
        $company = $request->user()->employee?->company ?? null;

        $workingDaysPolicy = $validated['working_days_policy'] ?? $company?->working_days_policy ?? '5_day';
        $excludeSaturdays = $validated['exclude_saturdays'] ?? $company?->exclude_saturdays ?? true;
        $excludeSundays = $validated['exclude_sundays'] ?? $company?->exclude_sundays ?? true;
        $excludePublicHolidays = $validated['exclude_public_holidays'] ?? $company?->exclude_public_holidays ?? true;
        $customHolidays = $validated['custom_holidays'] ?? $company?->custom_holidays ?? [];

        $workingDays = $this->leaveCalculationService->calculateWorkingDays(
            $startDate,
            $endDate,
            $workingDaysPolicy,
            $excludeSaturdays,
            $excludeSundays,
            $excludePublicHolidays,
            $customHolidays
        );

        return response()->json([
            'working_days' => $workingDays,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'policy' => [
                'working_days_policy' => $workingDaysPolicy,
                'exclude_saturdays' => $excludeSaturdays,
                'exclude_sundays' => $excludeSundays,
                'exclude_public_holidays' => $excludePublicHolidays,
                'custom_holidays' => $customHolidays,
            ],
        ]);
    }

    /**
     * Get detailed breakdown of leave calculation
     */
    public function breakdown(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'working_days_policy' => 'sometimes|in:5_day,6_day,7_day',
            'exclude_saturdays' => 'sometimes|boolean',
            'exclude_sundays' => 'sometimes|boolean',
            'exclude_public_holidays' => 'sometimes|boolean',
            'custom_holidays' => 'sometimes|array',
            'custom_holidays.*' => 'date_format:Y-m-d',
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);

        // Use company defaults or request values
        $company = $request->user()->employee?->company ?? null;

        $workingDaysPolicy = $validated['working_days_policy'] ?? $company?->working_days_policy ?? '5_day';
        $excludeSaturdays = $validated['exclude_saturdays'] ?? $company?->exclude_saturdays ?? true;
        $excludeSundays = $validated['exclude_sundays'] ?? $company?->exclude_sundays ?? true;
        $excludePublicHolidays = $validated['exclude_public_holidays'] ?? $company?->exclude_public_holidays ?? true;
        $customHolidays = $validated['custom_holidays'] ?? $company?->custom_holidays ?? [];

        $breakdown = $this->leaveCalculationService->getLeaveBreakdown(
            $startDate,
            $endDate,
            $workingDaysPolicy,
            $excludeSaturdays,
            $excludeSundays,
            $excludePublicHolidays,
            $customHolidays
        );

        return response()->json([
            'breakdown' => $breakdown,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'policy' => [
                'working_days_policy' => $workingDaysPolicy,
                'exclude_saturdays' => $excludeSaturdays,
                'exclude_sundays' => $excludeSundays,
                'exclude_public_holidays' => $excludePublicHolidays,
                'custom_holidays' => $customHolidays,
            ],
        ]);
    }
}
