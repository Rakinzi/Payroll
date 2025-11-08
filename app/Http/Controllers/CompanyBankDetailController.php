<?php

namespace App\Http\Controllers;

use App\Models\CompanyBankDetail;
use App\Models\CostCenter;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CompanyBankDetailController extends Controller
{
    /**
     * Display a listing of company bank details.
     */
    public function index()
    {
        $centerId = auth()->user()->center_id;

        $bankDetails = CompanyBankDetail::where('center_id', $centerId)
            ->with('costCenter')
            ->orderBy('is_default', 'desc')
            ->orderBy('bank_name')
            ->get();

        $costCenter = CostCenter::find($centerId);

        return Inertia::render('company-bank-details/index', [
            'bankDetails' => $bankDetails,
            'costCenter' => $costCenter,
            'accountTypes' => CompanyBankDetail::getAccountTypes(),
            'currencies' => CompanyBankDetail::getCurrencies(),
        ]);
    }

    /**
     * Store a newly created bank detail.
     */
    public function store(Request $request)
    {
        $centerId = auth()->user()->center_id;

        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'branch_name' => 'required|string|max:255',
            'branch_code' => 'required|string|max:10',
            'account_number' => 'required|string|min:10|max:20',
            'account_type' => 'required|in:Current,Nostro,FCA',
            'account_currency' => 'required|in:RTGS,ZWL,USD',
            'is_default' => 'boolean',
        ]);

        $validated['center_id'] = $centerId;
        $validated['is_active'] = true;

        CompanyBankDetail::create($validated);

        return redirect()->route('company-bank-details.index')
            ->with('success', 'Bank account added successfully');
    }

    /**
     * Update the specified bank detail.
     */
    public function update(Request $request, CompanyBankDetail $companyBankDetail)
    {
        // Ensure user can only edit their cost center's bank details
        if ($companyBankDetail->center_id !== auth()->user()->center_id) {
            abort(403, 'Unauthorized access to this bank detail');
        }

        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'branch_name' => 'required|string|max:255',
            'branch_code' => 'required|string|max:10',
            'account_number' => 'required|string|min:10|max:20',
            'account_type' => 'required|in:Current,Nostro,FCA',
            'account_currency' => 'required|in:RTGS,ZWL,USD',
            'is_default' => 'boolean',
        ]);

        $companyBankDetail->update($validated);

        return redirect()->route('company-bank-details.index')
            ->with('success', 'Bank account updated successfully');
    }

    /**
     * Remove the specified bank detail.
     */
    public function destroy(CompanyBankDetail $companyBankDetail)
    {
        // Ensure user can only delete their cost center's bank details
        if ($companyBankDetail->center_id !== auth()->user()->center_id) {
            abort(403, 'Unauthorized access to this bank detail');
        }

        // Prevent deletion of default account
        if ($companyBankDetail->is_default) {
            return redirect()->back()
                ->with('error', 'Cannot delete the default bank account. Please set another account as default first.');
        }

        $companyBankDetail->delete();

        return redirect()->route('company-bank-details.index')
            ->with('success', 'Bank account deleted successfully');
    }

    /**
     * Set the specified bank detail as default.
     */
    public function setDefault(CompanyBankDetail $companyBankDetail)
    {
        // Ensure user can only modify their cost center's bank details
        if ($companyBankDetail->center_id !== auth()->user()->center_id) {
            abort(403, 'Unauthorized access to this bank detail');
        }

        // The model's boot method will handle removing default from others
        $companyBankDetail->update(['is_default' => true]);

        return redirect()->route('company-bank-details.index')
            ->with('success', 'Default bank account updated successfully');
    }
}
