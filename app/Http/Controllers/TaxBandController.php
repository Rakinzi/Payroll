<?php

namespace App\Http\Controllers;

use App\Models\TaxBand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TaxBandController extends Controller
{
    private $bandTypes = [
        'annual_zwl' => 'tax_bands_annual_zwl',
        'annual_usd' => 'tax_bands_annual_usd',
        'monthly_zwl' => 'tax_bands_monthly_zwl',
        'monthly_usd' => 'tax_bands_monthly_usd',
    ];

    private $bandTypeLabels = [
        'annual_zwl' => 'ZWG - Annual Table',
        'annual_usd' => 'USD - Annual Table',
        'monthly_zwl' => 'ZWG - Monthly Table',
        'monthly_usd' => 'USD - Monthly Table',
    ];

    /**
     * Display all tax bands grouped by type.
     */
    public function index()
    {
        $taxBands = [];

        foreach ($this->bandTypes as $type => $table) {
            $taxBands[$type] = TaxBand::from($table)
                ->orderBy('min_salary')
                ->get()
                ->map(function ($band) {
                    return [
                        'id' => $band->id,
                        'min_salary' => (float) $band->min_salary,
                        'max_salary' => $band->max_salary ? (float) $band->max_salary : null,
                        'tax_rate' => (float) $band->tax_rate,
                        'tax_amount' => (float) $band->tax_amount,
                        'formatted_min_salary' => $band->formatted_min_salary,
                        'formatted_max_salary' => $band->formatted_max_salary,
                        'formatted_rate' => $band->formatted_rate,
                        'created_at' => $band->created_at->toISOString(),
                        'updated_at' => $band->updated_at->toISOString(),
                    ];
                });
        }

        return Inertia::render('tax-bands/index', [
            'taxBands' => $taxBands,
            'bandTypes' => array_keys($this->bandTypes),
            'bandTypeLabels' => $this->bandTypeLabels,
        ]);
    }

    /**
     * Update the specified tax band.
     */
    public function update(Request $request, string $bandType, int $id)
    {
        if (!isset($this->bandTypes[$bandType])) {
            abort(404, 'Invalid band type');
        }

        $table = $this->bandTypes[$bandType];

        $validated = $request->validate(TaxBand::rules());

        // Validate band ranges don't overlap
        if (!$this->validateBandRanges($table, $validated, $id)) {
            return redirect()->back()
                ->with('error', 'Tax band ranges cannot overlap with existing bands');
        }

        // Find and update the tax band
        $taxBand = TaxBand::from($table)->findOrFail($id);
        $taxBand->update($validated);

        return redirect()->route('tax-bands.index')
            ->with('success', 'Tax band updated successfully');
    }

    /**
     * Store a newly created tax band.
     */
    public function store(Request $request, string $bandType)
    {
        if (!isset($this->bandTypes[$bandType])) {
            abort(404, 'Invalid band type');
        }

        $table = $this->bandTypes[$bandType];

        $validated = $request->validate(TaxBand::rules());

        // Validate band ranges don't overlap
        if (!$this->validateBandRanges($table, $validated)) {
            return redirect()->back()
                ->with('error', 'Tax band ranges cannot overlap with existing bands');
        }

        // Create the tax band
        DB::table($table)->insert(array_merge($validated, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        return redirect()->route('tax-bands.index')
            ->with('success', 'Tax band created successfully');
    }

    /**
     * Remove the specified tax band.
     */
    public function destroy(string $bandType, int $id)
    {
        if (!isset($this->bandTypes[$bandType])) {
            abort(404, 'Invalid band type');
        }

        $table = $this->bandTypes[$bandType];

        $taxBand = TaxBand::from($table)->findOrFail($id);
        $taxBand->delete();

        return redirect()->route('tax-bands.index')
            ->with('success', 'Tax band deleted successfully');
    }

    /**
     * Validate that tax band ranges don't overlap.
     */
    private function validateBandRanges(string $table, array $data, ?int $excludeId = null): bool
    {
        $query = DB::table($table);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $existingBands = $query->get();

        $newMin = $data['min_salary'];
        $newMax = $data['max_salary'] ?? PHP_FLOAT_MAX;

        foreach ($existingBands as $band) {
            $existingMin = $band->min_salary;
            $existingMax = $band->max_salary ?? PHP_FLOAT_MAX;

            // Check for overlap: two ranges overlap if one starts before the other ends
            if ($newMin < $existingMax && $existingMin < $newMax) {
                return false;
            }
        }

        return true;
    }
}
