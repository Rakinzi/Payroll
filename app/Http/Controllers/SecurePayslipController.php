<?php

namespace App\Http\Controllers;

use App\Models\Payslip;
use App\Models\PayslipDownloadLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Inertia\Inertia;

class SecurePayslipController extends Controller
{
    /**
     * Show password verification page for secure download
     */
    public function showPasswordForm(string $token)
    {
        $link = PayslipDownloadLink::findByToken($token);

        if (!$link) {
            return Inertia::render('Payslips/SecureDownload/NotFound', [
                'error' => 'Invalid download link',
            ]);
        }

        if (!$link->isValid()) {
            $reason = $link->isExpired() ? 'expired' : 'already used';
            return Inertia::render('Payslips/SecureDownload/Expired', [
                'error' => "This download link has {$reason}",
                'expiry_display' => $link->getExpiryDisplay(),
            ]);
        }

        $link->load(['payslip:id,payslip_number,period_month,period_year', 'employee:id,firstname,surname']);

        return Inertia::render('Payslips/SecureDownload/PasswordForm', [
            'token' => $token,
            'payslip' => [
                'payslip_number' => $link->payslip->payslip_number,
                'period_display' => $link->payslip->period_display,
            ],
            'employee' => [
                'name' => $link->employee->firstname . ' ' . $link->employee->surname,
            ],
            'expiry_display' => $link->getExpiryDisplay(),
            'access_count' => $link->access_count,
        ]);
    }

    /**
     * Verify password and download payslip
     */
    public function download(Request $request, string $token)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $link = PayslipDownloadLink::findByToken($token);

        if (!$link) {
            return back()->withErrors(['password' => 'Invalid download link']);
        }

        if (!$link->isValid()) {
            return back()->withErrors(['password' => 'This download link has expired or been used']);
        }

        // Load relationships
        $link->load(['payslip', 'employee']);

        // Verify payslip password
        if (!Hash::check($request->password, $link->employee->payslip_password)) {
            // Log failed attempt
            Log::warning('Failed payslip download attempt', [
                'employee_id' => $link->employee_id,
                'payslip_id' => $link->payslip_id,
                'ip' => $request->ip(),
            ]);

            return back()->withErrors(['password' => 'Incorrect password']);
        }

        // Mark link as accessed
        $link->markAccessed($request->ip(), $request->userAgent());

        // Generate and return PDF
        return $this->generatePDF($link->payslip);
    }

    /**
     * Download without password (for admin preview)
     */
    public function directDownload(Request $request, string $token)
    {
        // Only allow if user is authenticated and is admin
        if (!auth()->check() || !auth()->user()->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        $link = PayslipDownloadLink::findByToken($token);

        if (!$link) {
            abort(404, 'Link not found');
        }

        $link->load('payslip');

        return $this->generatePDF($link->payslip);
    }

    /**
     * Generate PDF for payslip
     */
    protected function generatePDF(Payslip $payslip)
    {
        $payslip->load([
            'employee:id,emp_system_id,firstname,surname,othername,position_id,department_id',
            'employee.position:id,position_name',
            'employee.department:id,department_name',
            'payroll:id,payroll_name',
            'transactions',
        ]);

        $pdf = Pdf::loadView('payslips.pdf', [
            'payslip' => $payslip,
            'employee' => $payslip->employee,
            'earnings' => $payslip->transactions()->earnings()->get(),
            'deductions' => $payslip->transactions()->deductions()->get(),
        ])
        ->setPaper('a4', 'landscape')
        ->setOption('margin-top', 10)
        ->setOption('margin-bottom', 10)
        ->setOption('margin-left', 10)
        ->setOption('margin-right', 10);

        return $pdf->download("{$payslip->payslip_number}.pdf");
    }

    /**
     * Resend download link (for employees who lost the link)
     */
    public function resendLink(Request $request)
    {
        $request->validate([
            'emp_system_id' => 'required|string',
            'payslip_number' => 'required|string',
        ]);

        // Find employee and payslip
        $employee = \App\Models\Employee::where('emp_system_id', $request->emp_system_id)->first();

        if (!$employee) {
            return back()->withErrors(['emp_system_id' => 'Employee not found']);
        }

        $payslip = Payslip::where('payslip_number', $request->payslip_number)
            ->where('employee_id', $employee->id)
            ->first();

        if (!$payslip) {
            return back()->withErrors(['payslip_number' => 'Payslip not found']);
        }

        // Check if there's an existing valid link
        $existingLink = PayslipDownloadLink::forPayslip($payslip->id)
            ->forEmployee($employee->id)
            ->valid()
            ->first();

        if ($existingLink) {
            return back()->with('success', 'A valid download link already exists. Check your messages.');
        }

        // Generate new link
        $link = PayslipDownloadLink::generate($payslip->id, $employee->id, 'resend');

        // Send notification (SMS or email based on preferences)
        $this->sendLinkNotification($employee, $payslip, $link);

        return back()->with('success', 'Download link has been sent to your registered contact details');
    }

    /**
     * Send download link notification
     */
    protected function sendLinkNotification($employee, $payslip, $link)
    {
        // This would integrate with your notification system
        // For now, just log
        Log::info('Payslip download link sent', [
            'employee_id' => $employee->id,
            'payslip_id' => $payslip->id,
            'link_id' => $link->link_id,
        ]);
    }
}
