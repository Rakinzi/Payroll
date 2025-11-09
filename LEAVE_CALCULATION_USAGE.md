# Leave Calculation System - Usage Guide

This document explains how to use the flexible leave calculation system that supports different company working day policies.

## Overview

The system supports three working day policies:
- **5_day**: Monday-Friday (22 working days/month typically)
- **6_day**: Monday-Saturday (26 working days/month typically)
- **7_day**: Everyday (30/31 working days/month)

## Features

✅ Excludes weekends based on company policy
✅ Excludes Zimbabwe official public holidays (NOT observances like Father's/Mother's Day)
✅ Handles holiday rollover when they fall on weekends
✅ Supports custom company holidays
✅ Configurable per company/tenant
✅ Available in both backend (PHP) and frontend (TypeScript)

## Backend Usage

### 1. Company Configuration

First, run the migration to add working days policy fields:

```bash
php artisan migrate
```

Then configure company policy:

```php
use App\Models\Company;

$company = Company::first();
$company->update([
    'working_days_policy' => '5_day', // '5_day', '6_day', or '7_day'
    'standard_working_days_per_month' => 22,
    'exclude_saturdays' => true,
    'exclude_sundays' => true,
    'exclude_public_holidays' => true,
    'custom_holidays' => ['2025-12-31'], // Company-specific holidays
]);
```

### 2. Calculate Working Days (Service)

```php
use App\Services\LeaveCalculationService;
use Carbon\Carbon;

$service = new LeaveCalculationService();

$workingDays = $service->calculateWorkingDays(
    startDate: Carbon::parse('2025-01-01'),
    endDate: Carbon::parse('2025-01-31'),
    workingDaysPolicy: '5_day',
    excludeSaturdays: true,
    excludeSundays: true,
    excludePublicHolidays: true,
    customHolidays: ['2025-01-15'] // Optional
);

echo "Working days: $workingDays";
```

### 3. Get Detailed Breakdown

```php
$breakdown = $service->getLeaveBreakdown(
    startDate: Carbon::parse('2025-01-01'),
    endDate: Carbon::parse('2025-01-31'),
    workingDaysPolicy: '5_day',
    excludeSaturdays: true,
    excludeSundays: true,
    excludePublicHolidays: true
);

/*
Returns:
[
    'total_days' => 31,
    'working_days' => 21,
    'weekend_days' => 9,
    'public_holidays' => 1,
    'custom_holidays' => 0,
    'excluded_dates' => [
        ['date' => '2025-01-01', 'type' => 'public_holiday', 'name' => 'Public Holiday'],
        ['date' => '2025-01-04', 'type' => 'weekend', 'name' => 'Saturday'],
        // ... more dates
    ]
]
*/
```

### 4. API Usage

Use the API endpoints:

```bash
# Calculate working days
curl -X POST /leave-calculation/calculate \
  -H "Content-Type: application/json" \
  -d '{
    "start_date": "2025-01-01",
    "end_date": "2025-01-31",
    "working_days_policy": "5_day"
  }'

# Get breakdown
curl -X POST /leave-calculation/breakdown \
  -H "Content-Type: application/json" \
  -d '{
    "start_date": "2025-01-01",
    "end_date": "2025-01-31"
  }'
```

## Frontend Usage (TypeScript/React)

### 1. Local Calculation (No API call)

```typescript
import { calculateWorkingDays, getLeaveBreakdown, DEFAULT_WORKING_POLICY } from '@/utils/leave-calculation';
import type { CompanyWorkingPolicy } from '@/utils/leave-calculation';

// Use default 5-day policy
const workingDays = calculateWorkingDays(
    '2025-01-01',
    '2025-01-31',
    DEFAULT_WORKING_POLICY
);

// Custom policy
const customPolicy: CompanyWorkingPolicy = {
    working_days_policy: '6_day', // Work Monday-Saturday
    standard_working_days_per_month: 26,
    exclude_saturdays: false, // Include Saturdays
    exclude_sundays: true,
    exclude_public_holidays: true,
    custom_holidays: [],
};

const workingDays = calculateWorkingDays('2025-01-01', '2025-01-31', customPolicy);

// Get detailed breakdown
const breakdown = getLeaveBreakdown('2025-01-01', '2025-01-31', customPolicy);
console.log(breakdown);
/*
{
    total_days: 31,
    working_days: 26,
    weekend_days: 4,
    public_holidays: 1,
    custom_holidays: 0,
    excluded_dates: [...]
}
*/
```

### 2. API-based Calculation (React Hook)

```typescript
import { useCalculateWorkingDays, useLeaveBreakdown } from '@/hooks/use-leave-calculation';

function LeaveApplicationForm() {
    const calculateMutation = useCalculateWorkingDays();
    const breakdownMutation = useLeaveBreakdown();

    const handleCalculate = async () => {
        const result = await calculateMutation.mutateAsync({
            start_date: '2025-01-01',
            end_date: '2025-01-31',
            // Optional overrides (uses company defaults if not provided)
            working_days_policy: '5_day',
        });

        console.log('Working days:', result.working_days);
    };

    const handleGetBreakdown = async () => {
        const result = await breakdownMutation.mutateAsync({
            start_date: '2025-01-01',
            end_date: '2025-01-31',
        });

        console.log('Breakdown:', result.breakdown);
    };

    return (
        <div>
            <button onClick={handleCalculate}>Calculate</button>
            <button onClick={handleGetBreakdown}>Get Breakdown</button>

            {calculateMutation.data && (
                <p>Working days: {calculateMutation.data.working_days}</p>
            )}

            {breakdownMutation.data && (
                <div>
                    <p>Total: {breakdownMutation.data.breakdown.total_days}</p>
                    <p>Working: {breakdownMutation.data.breakdown.working_days}</p>
                    <p>Weekends: {breakdownMutation.data.breakdown.weekend_days}</p>
                    <p>Holidays: {breakdownMutation.data.breakdown.public_holidays}</p>
                </div>
            )}
        </div>
    );
}
```

## Zimbabwe Public Holidays

The system includes these **official** Zimbabwe public holidays (observances excluded):

- New Year's Day (January 1)
- Robert Gabriel Mugabe National Youth Day (February 21)
- Good Friday (moveable)
- Easter Saturday (moveable)
- Easter Monday (moveable)
- Independence Day (April 18)
- Workers' Day (May 1)
- Africa Day (May 25)
- Heroes' Day (2nd Monday in August)
- Defence Forces Day (Day after Heroes' Day)
- Unity Day (December 22)
- Christmas Day (December 25)
- Boxing Day (December 26)

**NOT included** (observances):
- Father's Day
- Mother's Day
- Valentine's Day
- Any other non-gazetted observances

## Holiday Rollover Rules

When a public holiday falls on a weekend:

- **Sunday**: Holiday observed on Monday
- **Saturday** (when Saturdays excluded): Holiday observed on Monday
- **Saturday** (when Saturdays NOT excluded): Holiday observed on Saturday

## Examples by Work Schedule

### 5-Day Work Week (Mon-Fri)

```php
// January 1-31, 2025
// Excludes: Saturdays (4), Sundays (4), New Year's Day (1)
// Working days: 31 - 4 - 4 - 1 = 22 days
$service->calculateWorkingDays(
    Carbon::parse('2025-01-01'),
    Carbon::parse('2025-01-31'),
    '5_day',
    true,
    true,
    true
); // Returns: 22
```

### 6-Day Work Week (Mon-Sat)

```php
// January 1-31, 2025
// Excludes: Sundays (4), New Year's Day (1)
// Working days: 31 - 4 - 1 = 26 days
$service->calculateWorkingDays(
    Carbon::parse('2025-01-01'),
    Carbon::parse('2025-01-31'),
    '6_day',
    false, // Don't exclude Saturdays
    true,  // Exclude Sundays
    true
); // Returns: 26
```

### 7-Day Work Week (Everyday)

```php
// January 1-31, 2025
// No exclusions
// Working days: 31 days
$service->calculateWorkingDays(
    Carbon::parse('2025-01-01'),
    Carbon::parse('2025-01-31'),
    '7_day'
); // Returns: 31
```

## Testing

Run tests:

```bash
# Backend
php artisan test --filter=LeaveCalculationTest

# Frontend
npm run test -- leave-calculation.test.ts
```

## Migration

To update an existing system:

1. Run migration: `php artisan migrate`
2. Update company records with default policy
3. Update leave application forms to use new calculation
4. Test with different policies

## Support

For issues or questions:
- Check the service class: `app/Services/LeaveCalculationService.php`
- Check the utility: `resources/js/utils/leave-calculation.ts`
- Review this documentation
