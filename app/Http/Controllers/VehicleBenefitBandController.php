<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\VehicleBenefitBand;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VehicleBenefitBandController extends Controller
{
    /**
     * Display the vehicle benefit bands.
     */
    public function index(Request $request)
    {
        $query = VehicleBenefitBand::query();

        // Filter by currency
        if ($request->filled('currency')) {
            $query->currency($request->currency);
        }

        // Filter by period
        if ($request->filled('period')) {
            $query->period($request->period);
        }

        // Filter by active status
        if ($request->filled('active_only') && $request->active_only) {
            $query->active();
        }

        $benefitBands = $query->ordered()->get();

        return Inertia::render('vehicle-benefits/index', [
            'benefitBands' => $benefitBands->map(function ($band) {
                return [
                    'id' => $band->id,
                    'engine_capacity_min' => $band->engine_capacity_min,
                    'engine_capacity_max' => $band->engine_capacity_max,
                    'benefit_amount' => $band->benefit_amount,
                    'currency' => $band->currency,
                    'period' => $band->period,
                    'description' => $band->description,
                    'is_active' => $band->is_active,
                    'capacity_range' => $band->capacity_range,
                    'formatted_benefit_amount' => $band->formatted_benefit_amount,
                    'created_at' => $band->created_at,
                    'updated_at' => $band->updated_at,
                ];
            }),
            'supportedCurrencies' => VehicleBenefitBand::getSupportedCurrencies(),
            'supportedPeriods' => VehicleBenefitBand::getSupportedPeriods(),
            'filters' => $request->only(['currency', 'period', 'active_only']),
        ]);
    }

    /**
     * Store a new vehicle benefit band.
     */
    public function store(Request $request)
    {
        $validated = $request->validate(VehicleBenefitBand::rules());

        try {
            $benefitBand = VehicleBenefitBand::create($validated);

            // Log the creation
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => ActivityLog::ACTION_CREATE,
                'description' => "Created vehicle benefit band: {$benefitBand->capacity_range} ({$benefitBand->currency}, {$benefitBand->period})",
                'model_type' => 'VehicleBenefitBand',
                'model_id' => $benefitBand->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return redirect()->route('vehicle-benefits.index')
                ->with('success', 'Vehicle benefit band created successfully');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Update a vehicle benefit band.
     */
    public function update(Request $request, VehicleBenefitBand $vehicleBenefit)
    {
        $validated = $request->validate(VehicleBenefitBand::rules(true));

        try {
            $vehicleBenefit->update($validated);

            // Log the update
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => ActivityLog::ACTION_UPDATE,
                'description' => "Updated vehicle benefit band: {$vehicleBenefit->capacity_range} ({$vehicleBenefit->currency}, {$vehicleBenefit->period})",
                'model_type' => 'VehicleBenefitBand',
                'model_id' => $vehicleBenefit->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return redirect()->route('vehicle-benefits.index')
                ->with('success', 'Vehicle benefit band updated successfully');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Delete a vehicle benefit band.
     */
    public function destroy(VehicleBenefitBand $vehicleBenefit)
    {
        $capacityRange = $vehicleBenefit->capacity_range;
        $currency = $vehicleBenefit->currency;
        $period = $vehicleBenefit->period;

        $vehicleBenefit->delete();

        // Log the deletion
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => ActivityLog::ACTION_DELETE,
            'description' => "Deleted vehicle benefit band: {$capacityRange} ({$currency}, {$period})",
            'model_type' => 'VehicleBenefitBand',
            'model_id' => $vehicleBenefit->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return redirect()->route('vehicle-benefits.index')
            ->with('success', 'Vehicle benefit band deleted successfully');
    }

    /**
     * Calculate benefit for specific engine capacity.
     */
    public function calculateBenefit(Request $request)
    {
        $request->validate([
            'engine_capacity' => 'required|integer|min:0',
            'currency' => 'required|in:USD,ZWG',
            'period' => 'required|in:monthly,annual',
        ]);

        $band = VehicleBenefitBand::findBandForCapacity(
            $request->engine_capacity,
            $request->currency,
            $request->period
        );

        if (!$band) {
            return response()->json([
                'success' => false,
                'message' => 'No benefit band found for the specified engine capacity',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'benefit_amount' => $band->benefit_amount,
            'capacity_range' => $band->capacity_range,
            'currency' => $band->currency,
            'period' => $band->period,
            'band_id' => $band->id,
        ]);
    }
}
