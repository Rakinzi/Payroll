<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CompanyController extends Controller
{
    /**
     * Display the company details.
     */
    public function show()
    {
        // Get the user's center_id to find their company
        $centerId = auth()->user()->center_id;

        // Find or create company for this center
        $company = Company::where('id', $centerId)->first();

        if (!$company) {
            // Create default company if it doesn't exist
            $company = Company::create([
                'id' => $centerId,
                'company_name' => 'Company Name',
                'company_email_address' => 'info@company.com',
                'phone_number' => '+263',
                'physical_address' => 'Physical Address',
                'is_active' => true,
            ]);
        }

        // Determine if user can edit
        $canEdit = auth()->user()->hasPermissionTo('access all centers');

        return Inertia::render('companies/show', [
            'company' => [
                'id' => $company->id,
                'company_name' => $company->company_name,
                'company_email_address' => $company->company_email_address,
                'phone_number' => $company->phone_number,
                'telephone_number' => $company->telephone_number,
                'physical_address' => $company->physical_address,
                'registration_number' => $company->registration_number,
                'tax_number' => $company->tax_number,
                'industry' => $company->industry,
                'website' => $company->website,
                'description' => $company->description,
                'logo' => $company->logo,
                'logo_url' => $company->logo_url,
                'is_active' => $company->is_active,
                'created_at' => $company->created_at,
                'updated_at' => $company->updated_at,
            ],
            'canEdit' => $canEdit,
        ]);
    }

    /**
     * Update the company details.
     */
    public function update(Request $request, Company $company)
    {
        // Check permissions
        if (!auth()->user()->hasPermissionTo('access all centers')) {
            abort(403, 'Access denied');
        }

        // Ensure user can only update their company
        if ($company->id !== auth()->user()->center_id) {
            abort(403, 'Access denied');
        }

        $rules = Company::rules(true);
        unset($rules['logo']); // Handle logo separately

        $validated = $request->validate($rules);

        // Handle logo upload if provided
        if ($request->hasFile('logo')) {
            $request->validate([
                'logo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            // Delete old logo if exists
            if ($company->logo) {
                Storage::disk('public')->delete($company->logo);
            }

            $logoPath = $request->file('logo')->store('company-logos', 'public');
            $validated['logo'] = $logoPath;
        }

        $company->update($validated);

        // Log the change
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => ActivityLog::ACTION_UPDATE,
            'description' => "Updated company details for {$company->company_name}",
            'model_type' => 'Company',
            'model_id' => $company->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('companies.show')
            ->with('success', 'Company details updated successfully');
    }

    /**
     * Upload company logo.
     */
    public function uploadLogo(Request $request, Company $company)
    {
        // Check permissions
        if (!auth()->user()->hasPermissionTo('access all centers')) {
            abort(403, 'Access denied');
        }

        // Ensure user can only update their company
        if ($company->id !== auth()->user()->center_id) {
            abort(403, 'Access denied');
        }

        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Delete old logo if exists
        if ($company->logo) {
            Storage::disk('public')->delete($company->logo);
        }

        $logoPath = $request->file('logo')->store('company-logos', 'public');

        $company->update(['logo' => $logoPath]);

        // Log the change
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => ActivityLog::ACTION_UPDATE,
            'description' => "Updated company logo for {$company->company_name}",
            'model_type' => 'Company',
            'model_id' => $company->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'logo_url' => $company->logo_url,
        ]);
    }

    /**
     * Delete company logo.
     */
    public function deleteLogo(Company $company)
    {
        // Check permissions
        if (!auth()->user()->hasPermissionTo('access all centers')) {
            abort(403, 'Access denied');
        }

        // Ensure user can only update their company
        if ($company->id !== auth()->user()->center_id) {
            abort(403, 'Access denied');
        }

        // Delete logo file
        if ($company->logo) {
            Storage::disk('public')->delete($company->logo);
        }

        $company->update(['logo' => null]);

        // Log the change
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => ActivityLog::ACTION_DELETE,
            'description' => "Deleted company logo for {$company->company_name}",
            'model_type' => 'Company',
            'model_id' => $company->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return response()->json([
            'success' => true,
        ]);
    }
}
