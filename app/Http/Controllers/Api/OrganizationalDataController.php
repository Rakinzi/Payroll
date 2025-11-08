<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyBankDetail;
use App\Models\Department;
use App\Models\Industry;
use App\Models\NECGrade;
use App\Models\Occupation;
use App\Models\Paypoint;
use App\Models\Position;
use App\Models\TaxBand;
use App\Models\TaxCredit;
use App\Models\TransactionCode;
use App\Models\VehicleBenefitBand;
use Illuminate\Http\Request;

class OrganizationalDataController extends Controller
{
    /**
     * Get all organizational data
     */
    public function index()
    {
        return response()->json([
            'company' => Company::active()->first(),
            'departments' => Department::active()->orderBy('dept_name')->get(),
            'positions' => Position::active()->orderBy('position_name')->get(),
            'transaction_codes' => TransactionCode::active()->orderBy('code_number')->get(),
            'tax_bands' => TaxBand::active()->orderBy('currency')->orderBy('period')->orderBy('min_salary')->get(),
            'tax_credits' => TaxCredit::active()->orderBy('currency')->orderBy('period')->get(),
            'nec_grades' => NECGrade::with('transactionCode')->active()->orderBy('grade_name')->get(),
            'vehicle_benefit_bands' => VehicleBenefitBand::active()->orderBy('engine_capacity_min')->get(),
            'company_bank_details' => CompanyBankDetail::active()->orderBy('is_primary', 'desc')->orderBy('bank_name')->get(),
            'industries' => Industry::active()->orderBy('industry_name')->get(),
            'occupations' => Occupation::active()->orderBy('occupation_name')->get(),
            'paypoints' => Paypoint::active()->orderBy('paypoint_name')->get(),
        ]);
    }

    // ===========================
    // Company CRUD Methods
    // ===========================

    public function storeCompany(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'company_email_address' => 'nullable|email|max:255',
            'phone_number' => 'nullable|string|max:255',
            'telephone_number' => 'nullable|string|max:255',
            'physical_address' => 'nullable|string',
            'description' => 'nullable|string',
            'logo' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $company = Company::create($validated);

        return response()->json(['company' => $company], 201);
    }

    public function updateCompany(Request $request, string $id)
    {
        $company = Company::findOrFail($id);

        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'company_email_address' => 'nullable|email|max:255',
            'phone_number' => 'nullable|string|max:255',
            'telephone_number' => 'nullable|string|max:255',
            'physical_address' => 'nullable|string',
            'description' => 'nullable|string',
            'logo' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $company->update($validated);

        return response()->json(['company' => $company]);
    }

    public function destroyCompany(string $id)
    {
        $company = Company::findOrFail($id);
        $company->delete();

        return response()->json(['message' => 'Company deleted successfully']);
    }

    // ===========================
    // Tax Credit CRUD Methods
    // ===========================

    public function storeTaxCredit(Request $request)
    {
        $validated = $request->validate([
            'credit_name' => 'required|string|max:255',
            'credit_amount' => 'required|numeric|min:0',
            'currency' => 'required|in:USD,ZWG',
            'period' => 'required|in:monthly,annual',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $taxCredit = TaxCredit::create($validated);

        return response()->json(['tax_credit' => $taxCredit], 201);
    }

    public function updateTaxCredit(Request $request, string $id)
    {
        $taxCredit = TaxCredit::findOrFail($id);

        $validated = $request->validate([
            'credit_name' => 'required|string|max:255',
            'credit_amount' => 'required|numeric|min:0',
            'currency' => 'required|in:USD,ZWG',
            'period' => 'required|in:monthly,annual',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $taxCredit->update($validated);

        return response()->json(['tax_credit' => $taxCredit]);
    }

    public function destroyTaxCredit(string $id)
    {
        $taxCredit = TaxCredit::findOrFail($id);
        $taxCredit->delete();

        return response()->json(['message' => 'Tax credit deleted successfully']);
    }

    // ===========================
    // Vehicle Benefit Band CRUD Methods
    // ===========================

    public function storeVehicleBenefitBand(Request $request)
    {
        $validated = $request->validate([
            'engine_capacity_min' => 'required|integer|min:0',
            'engine_capacity_max' => 'nullable|integer|gt:engine_capacity_min',
            'benefit_amount' => 'required|numeric|min:0',
            'currency' => 'required|in:USD,ZWG',
            'period' => 'required|in:monthly,annual',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $vehicleBenefitBand = VehicleBenefitBand::create($validated);

        return response()->json(['vehicle_benefit_band' => $vehicleBenefitBand], 201);
    }

    public function updateVehicleBenefitBand(Request $request, string $id)
    {
        $vehicleBenefitBand = VehicleBenefitBand::findOrFail($id);

        $validated = $request->validate([
            'engine_capacity_min' => 'required|integer|min:0',
            'engine_capacity_max' => 'nullable|integer|gt:engine_capacity_min',
            'benefit_amount' => 'required|numeric|min:0',
            'currency' => 'required|in:USD,ZWG',
            'period' => 'required|in:monthly,annual',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $vehicleBenefitBand->update($validated);

        return response()->json(['vehicle_benefit_band' => $vehicleBenefitBand]);
    }

    public function destroyVehicleBenefitBand(string $id)
    {
        $vehicleBenefitBand = VehicleBenefitBand::findOrFail($id);
        $vehicleBenefitBand->delete();

        return response()->json(['message' => 'Vehicle benefit band deleted successfully']);
    }

    // ===========================
    // Company Bank Detail CRUD Methods
    // ===========================

    public function storeCompanyBankDetail(Request $request)
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'branch_name' => 'nullable|string|max:255',
            'branch_code' => 'nullable|string|max:255',
            'swift_code' => 'nullable|string|max:255',
            'currency' => 'required|in:USD,ZWG',
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        $companyBankDetail = CompanyBankDetail::create($validated);

        return response()->json(['company_bank_detail' => $companyBankDetail], 201);
    }

    public function updateCompanyBankDetail(Request $request, string $id)
    {
        $companyBankDetail = CompanyBankDetail::findOrFail($id);

        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'branch_name' => 'nullable|string|max:255',
            'branch_code' => 'nullable|string|max:255',
            'swift_code' => 'nullable|string|max:255',
            'currency' => 'required|in:USD,ZWG',
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        $companyBankDetail->update($validated);

        return response()->json(['company_bank_detail' => $companyBankDetail]);
    }

    public function destroyCompanyBankDetail(string $id)
    {
        $companyBankDetail = CompanyBankDetail::findOrFail($id);
        $companyBankDetail->delete();

        return response()->json(['message' => 'Company bank detail deleted successfully']);
    }
}
