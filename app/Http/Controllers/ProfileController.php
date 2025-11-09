<?php

namespace App\Http\Controllers;

use App\Models\UserProfile;
use App\Models\Employee;
use App\Models\Department;
use App\Models\WorkPosition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ProfileController extends Controller
{
    /**
     * Display the profile edit form.
     */
    public function edit()
    {
        $user = Auth::user();
        $employee = $user->employee;
        $profile = UserProfile::getForUser($user->id);

        // Get departments and positions for dropdowns
        $departments = Department::where('center_id', $user->center_id)->get();
        $positions = WorkPosition::all();

        return Inertia::render('Settings/Profile/Edit', [
            'profile' => [
                'profile_id' => $profile->profile_id,
                'user_id' => $profile->user_id,
                'avatar_path' => $profile->avatar_path,
                'signature_path' => $profile->signature_path,
                'avatar_url' => $profile->avatar_url,
                'signature_url' => $profile->signature_url,
                'preferences' => $profile->preferences,
            ],
            'employee' => $employee ? [
                'id' => $employee->id,
                'firstname' => $employee->firstname,
                'surname' => $employee->surname,
                'nationality' => $employee->nationality,
                'nat_id' => $employee->nat_id,
                'gender' => $employee->gender,
                'dob' => $employee->dob,
                'marital_status' => $employee->marital_status,
                'home_address' => $employee->home_address,
                'city' => $employee->city,
                'country' => $employee->country,
                'phone_number' => $employee->phone_number,
                'personal_email_address' => $employee->personal_email_address,
                'religion' => $employee->religion,
                'drivers_licence_id' => $employee->drivers_licence_id,
                'drivers_licence_class' => $employee->drivers_licence_class,
                'passport_id' => $employee->passport_id,
                'department_id' => $employee->department_id,
                'position_id' => $employee->position_id,
                'payment_method' => $employee->payment_method,
                'payment_basis' => $employee->payment_basis,
                'title' => $employee->title,
            ] : null,
            'departments' => $departments,
            'positions' => $positions,
        ]);
    }

    /**
     * Update employee personal information.
     */
    public function update(Request $request)
    {
        $request->validate([
            'firstname' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'nationality' => 'nullable|string|max:255',
            'nat_id' => 'required|string|max:255',
            'gender' => 'required|in:Male,Female',
            'dob' => 'required|date',
            'marital_status' => 'nullable|in:Single,Married,Divorced,Widowed',
            'home_address' => 'required|string',
            'city' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'phone_number' => 'required|string|max:255',
            'personal_email_address' => 'nullable|email',
            'religion' => 'nullable|string|max:255',
            'drivers_licence_id' => 'nullable|string|max:255',
            'drivers_licence_class' => 'nullable|integer',
            'passport_id' => 'nullable|string|max:255',
            'department_id' => 'required|exists:departments,dept_id',
            'position_id' => 'required|exists:work_position,position_id',
            'payment_method' => 'required|in:Cash,Cheque,Transfer',
            'payment_basis' => 'required|in:Daily,Weekly,Monthly,Yearly',
            'title' => 'required|in:Hon,Dr,Mr,Mrs,Ms,Sir',
        ]);

        try {
            $employee = Auth::user()->employee;

            if (!$employee) {
                return back()->withErrors(['employee' => 'Employee record not found']);
            }

            $employee->update($request->only([
                'firstname',
                'surname',
                'nationality',
                'nat_id',
                'gender',
                'dob',
                'marital_status',
                'home_address',
                'city',
                'country',
                'phone_number',
                'personal_email_address',
                'religion',
                'drivers_licence_id',
                'drivers_licence_class',
                'passport_id',
                'department_id',
                'position_id',
                'payment_method',
                'payment_basis',
                'title',
            ]));

            Log::info("Profile updated for user " . Auth::id());

            return back()->with('success', 'Profile updated successfully');

        } catch (\Exception $e) {
            Log::error("Profile update failed: " . $e->getMessage());
            return back()->withErrors(['error' => 'An error occurred while updating the profile']);
        }
    }

    /**
     * Update profile avatar.
     */
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,JPG,PNG|max:128', // 128KB limit
        ]);

        try {
            $profile = UserProfile::getForUser(Auth::id());

            if ($profile->updateAvatar($request->file('avatar'))) {
                Log::info("Avatar updated for user " . Auth::id());
                return back()->with('success', 'Avatar updated successfully');
            }

            return back()->withErrors(['avatar' => 'Avatar update failed']);

        } catch (\Exception $e) {
            Log::error("Avatar update failed: " . $e->getMessage());
            return back()->withErrors(['avatar' => $e->getMessage()]);
        }
    }

    /**
     * Update digital signature.
     */
    public function updateSignature(Request $request)
    {
        $request->validate([
            'signature' => 'required|string',
        ]);

        try {
            $profile = UserProfile::getForUser(Auth::id());

            if ($profile->updateSignature($request->signature)) {
                Log::info("Signature updated for user " . Auth::id());
                return back()->with('success', 'Signature updated successfully');
            }

            return back()->withErrors(['signature' => 'Signature update failed']);

        } catch (\Exception $e) {
            Log::error("Signature update failed: " . $e->getMessage());
            return back()->withErrors(['signature' => $e->getMessage()]);
        }
    }

    /**
     * Update system password.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
            'new_password_confirmation' => 'required',
        ]);

        try {
            if (!Hash::check($request->old_password, Auth::user()->password)) {
                return back()->withErrors(['old_password' => 'Current password is incorrect']);
            }

            $profile = UserProfile::getForUser(Auth::id());

            if ($profile->updatePassword($request->new_password)) {
                Log::info("Password updated for user " . Auth::id());
                return back()->with('success', 'Password updated successfully');
            }

            return back()->withErrors(['password' => 'Password update failed']);

        } catch (\Exception $e) {
            Log::error("Password update failed: " . $e->getMessage());
            return back()->withErrors(['password' => 'An error occurred while updating the password']);
        }
    }

    /**
     * Update payslip password.
     */
    public function updatePayslipPassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
            'new_password_confirmation' => 'required',
        ]);

        try {
            $employee = Auth::user()->employee;

            if (!$employee) {
                return back()->withErrors(['employee' => 'Employee record not found']);
            }

            if (!Hash::check($request->old_password, $employee->payslip_password)) {
                return back()->withErrors(['old_password' => 'Current payslip password is incorrect']);
            }

            $profile = UserProfile::getForUser(Auth::id());

            if ($profile->updatePayslipPassword($request->new_password)) {
                Log::info("Payslip password updated for user " . Auth::id());
                return back()->with('success', 'Payslip password updated successfully');
            }

            return back()->withErrors(['password' => 'Payslip password update failed']);

        } catch (\Exception $e) {
            Log::error("Payslip password update failed: " . $e->getMessage());
            return back()->withErrors(['password' => 'An error occurred while updating the payslip password']);
        }
    }

    /**
     * Update bank details.
     */
    public function updateBankDetails(Request $request)
    {
        $request->validate([
            'bank_name' => 'required|string|max:255',
            'bank_account_number' => 'required|string|max:255',
            'bank_branch' => 'required|string|max:255',
            'bank_account_name' => 'required|string|max:255',
        ]);

        try {
            $employee = Auth::user()->employee;

            if (!$employee) {
                return back()->withErrors(['employee' => 'Employee record not found']);
            }

            $employee->update($request->only([
                'bank_name',
                'bank_account_number',
                'bank_branch',
                'bank_account_name',
            ]));

            Log::info("Bank details updated for user " . Auth::id());

            return back()->with('success', 'Bank details updated successfully');

        } catch (\Exception $e) {
            Log::error("Bank details update failed: " . $e->getMessage());
            return back()->withErrors(['error' => 'An error occurred while updating bank details']);
        }
    }
}
