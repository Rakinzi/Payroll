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
}
