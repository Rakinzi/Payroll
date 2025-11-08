<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessSpreadsheetImport;
use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\ImportSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SpreadsheetImportController extends Controller
{
    /**
     * Display import sessions list.
     */
    public function index(Request $request)
    {
        $query = ImportSession::with('importedBy:id,name,email')
            ->where('imported_by', auth()->id());

        // Filter by import type
        if ($request->filled('import_type')) {
            $query->byType($request->import_type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $sessions = $query->latest()
            ->paginate(15)
            ->through(function ($session) {
                return [
                    'id' => $session->id,
                    'import_type' => $session->import_type,
                    'file_name' => $session->file_name,
                    'total_rows' => $session->total_rows,
                    'processed_rows' => $session->processed_rows,
                    'error_rows' => $session->error_rows,
                    'status' => $session->status,
                    'progress_percentage' => $session->progress_percentage,
                    'success_percentage' => $session->success_percentage,
                    'has_errors' => $session->has_errors,
                    'imported_by' => $session->importedBy ? [
                        'name' => $session->importedBy->name,
                        'email' => $session->importedBy->email,
                    ] : null,
                    'created_at' => $session->created_at->toISOString(),
                    'updated_at' => $session->updated_at->toISOString(),
                ];
            });

        return Inertia::render('spreadsheet-import/index', [
            'sessions' => $sessions,
            'supportedTypes' => ImportSession::getSupportedTypes(),
            'filters' => $request->only(['import_type', 'status']),
        ]);
    }

    /**
     * Upload and process CSV file.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:51200', // 50MB max
            'import_type' => 'required|in:employees,salaries,transactions,banking',
        ]);

        $file = $request->file('file');
        $importType = $request->import_type;

        // Store file temporarily
        $filePath = $file->store('temp-imports');

        // Create import session
        $session = ImportSession::create([
            'import_type' => $importType,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'status' => 'uploaded',
            'imported_by' => auth()->id(),
        ]);

        // Log upload
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => ActivityLog::ACTION_CREATE,
            'description' => "Uploaded {$importType} import file: {$file->getClientOriginalName()}",
            'model_type' => 'ImportSession',
            'model_id' => $session->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Dispatch processing job
        ProcessSpreadsheetImport::dispatch($session);

        return redirect()->route('spreadsheet-import.preview', $session->id)
            ->with('success', 'File uploaded successfully. Processing started.');
    }

    /**
     * Display import preview with errors.
     */
    public function preview(ImportSession $session)
    {
        // Check permissions
        if ($session->imported_by !== auth()->id() && !auth()->user()->hasPermissionTo('access all centers')) {
            abort(403);
        }

        // Load errors grouped by row
        $session->load(['errors' => function ($query) {
            $query->orderBy('row_number');
        }]);

        // Get preview data from cache
        $previewData = Cache::get("import_preview_{$session->id}", []);

        // Group errors by row number
        $errorsByRow = $session->errors->groupBy('row_number')->map(function ($errors) {
            return $errors->map(function ($error) {
                return [
                    'id' => $error->id,
                    'column_name' => $error->column_name,
                    'error_message' => $error->error_message,
                    'raw_value' => $error->raw_value,
                    'error_location' => $error->error_location,
                ];
            });
        });

        return Inertia::render('import-preview/index', [
            'session' => [
                'id' => $session->id,
                'import_type' => $session->import_type,
                'file_name' => $session->file_name,
                'total_rows' => $session->total_rows,
                'processed_rows' => $session->processed_rows,
                'error_rows' => $session->error_rows,
                'status' => $session->status,
                'progress_percentage' => $session->progress_percentage,
                'success_percentage' => $session->success_percentage,
                'has_errors' => $session->has_errors,
                'can_be_processed' => $session->canBeProcessed(),
                'created_at' => $session->created_at->toISOString(),
            ],
            'previewData' => $previewData,
            'errorsByRow' => $errorsByRow,
        ]);
    }

    /**
     * Process validated data from import.
     */
    public function process(Request $request, ImportSession $session)
    {
        // Check permissions
        if ($session->imported_by !== auth()->id() && !auth()->user()->hasPermissionTo('access all centers')) {
            abort(403);
        }

        // Validate session can be processed
        if (!$session->canBeProcessed()) {
            return back()->with('error', 'Session is not ready for processing or has too many errors');
        }

        // Mark as processing
        $session->markProcessing();

        // Get preview data
        $previewData = Cache::get("import_preview_{$session->id}", []);

        // Process valid rows
        $successCount = 0;
        $errorCount = 0;

        foreach ($previewData as $index => $row) {
            // Skip rows with errors
            if ($session->errors->where('row_number', $index + 1)->count() > 0) {
                $errorCount++;
                continue;
            }

            try {
                // Import based on type
                $this->importRow($session->import_type, $row);
                $successCount++;
            } catch (\Exception $e) {
                $errorCount++;
            }
        }

        // Mark as completed
        $session->markCompleted();

        // Log completion
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => ActivityLog::ACTION_UPDATE,
            'description' => "Completed {$session->import_type} import: {$successCount} rows imported, {$errorCount} rows failed",
            'model_type' => 'ImportSession',
            'model_id' => $session->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Clear cache
        Cache::forget("import_preview_{$session->id}");

        return redirect()->route('spreadsheet-import.index')
            ->with('success', "{$successCount} rows imported successfully. {$errorCount} rows had errors.");
    }

    /**
     * Get import status (for polling).
     */
    public function status(ImportSession $session)
    {
        return response()->json([
            'status' => $session->status,
            'progress' => $session->progress_percentage,
            'processed_rows' => $session->processed_rows,
            'error_rows' => $session->error_rows,
            'total_rows' => $session->total_rows,
        ]);
    }

    /**
     * Delete import session.
     */
    public function destroy(ImportSession $session)
    {
        // Check permissions
        if ($session->imported_by !== auth()->id() && !auth()->user()->hasPermissionTo('access all centers')) {
            abort(403);
        }

        // Delete file if exists
        if ($session->file_path && Storage::exists($session->file_path)) {
            Storage::delete($session->file_path);
        }

        // Clear cache
        Cache::forget("import_preview_{$session->id}");

        // Delete session (cascade deletes errors)
        $session->delete();

        return back()->with('success', 'Import session deleted successfully');
    }

    /**
     * Export data to CSV.
     */
    public function export(Request $request)
    {
        $request->validate([
            'export_type' => 'required|in:employees,payroll,transactions',
            'filters' => 'nullable|array',
        ]);

        $exportType = $request->export_type;
        $filters = $request->filters ?? [];

        // Generate export data
        $data = $this->generateExportData($exportType, $filters);

        // Create CSV
        $csv = $this->arrayToCsv($data);
        $filename = $exportType . '_export_' . now()->format('Y-m-d_H-i-s') . '.csv';

        // Log export
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => ActivityLog::ACTION_READ,
            'description' => "Exported {$exportType} data to CSV",
            'model_type' => 'Export',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Import a single row based on type.
     */
    private function importRow(string $type, array $row): void
    {
        switch ($type) {
            case 'employees':
                $this->importEmployeeRow($row);
                break;
            case 'salaries':
                $this->importSalaryRow($row);
                break;
            case 'transactions':
                $this->importTransactionRow($row);
                break;
            case 'banking':
                $this->importBankingRow($row);
                break;
        }
    }

    /**
     * Import employee row.
     */
    private function importEmployeeRow(array $row): void
    {
        Employee::create([
            'firstname' => $row['firstname'] ?? '',
            'surname' => $row['surname'] ?? '',
            'othername' => $row['othername'] ?? null,
            'emp_email' => $row['email'] ?? '',
            'phone' => $row['phone'] ?? null,
            'date_of_birth' => $row['date_of_birth'] ?? null,
            'hire_date' => $row['hire_date'] ?? now(),
            'gender' => $row['gender'] ?? null,
            'nationality' => $row['nationality'] ?? null,
            'nat_id' => $row['nat_id'] ?? null,
            'basic_salary' => $row['basic_salary'] ?? 0,
            'is_active' => true,
        ]);
    }

    /**
     * Import salary row.
     */
    private function importSalaryRow(array $row): void
    {
        // Implementation for salary import
        // This would update employee salaries
    }

    /**
     * Import transaction row.
     */
    private function importTransactionRow(array $row): void
    {
        // Implementation for transaction import
    }

    /**
     * Import banking row.
     */
    private function importBankingRow(array $row): void
    {
        // Implementation for banking information import
    }

    /**
     * Generate export data based on type.
     */
    private function generateExportData(string $type, array $filters): array
    {
        switch ($type) {
            case 'employees':
                return $this->exportEmployees($filters);
            case 'payroll':
                return $this->exportPayroll($filters);
            case 'transactions':
                return $this->exportTransactions($filters);
            default:
                return [];
        }
    }

    /**
     * Export employees data.
     */
    private function exportEmployees(array $filters): array
    {
        $query = Employee::query();

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['center_id'])) {
            $query->where('center_id', $filters['center_id']);
        }

        return $query->get()->map(function ($employee) {
            return [
                'Employee ID' => $employee->emp_system_id,
                'First Name' => $employee->firstname,
                'Surname' => $employee->surname,
                'Other Name' => $employee->othername,
                'Email' => $employee->emp_email,
                'Phone' => $employee->phone,
                'Date of Birth' => $employee->date_of_birth?->format('Y-m-d'),
                'Hire Date' => $employee->hire_date?->format('Y-m-d'),
                'Gender' => $employee->gender,
                'Nationality' => $employee->nationality,
                'National ID' => $employee->nat_id,
                'Basic Salary' => $employee->basic_salary,
                'Status' => $employee->is_active ? 'Active' : 'Inactive',
            ];
        })->toArray();
    }

    /**
     * Export payroll data.
     */
    private function exportPayroll(array $filters): array
    {
        // Implementation for payroll export
        return [];
    }

    /**
     * Export transactions data.
     */
    private function exportTransactions(array $filters): array
    {
        // Implementation for transactions export
        return [];
    }

    /**
     * Convert array to CSV string.
     */
    private function arrayToCsv(array $data): string
    {
        if (empty($data)) {
            return '';
        }

        $csv = '';
        $headers = array_keys($data[0]);
        $csv .= implode(',', array_map(fn($h) => "\"$h\"", $headers)) . "\n";

        foreach ($data as $row) {
            $csv .= implode(',', array_map(function ($value) {
                return '"' . str_replace('"', '""', $value ?? '') . '"';
            }, $row)) . "\n";
        }

        return $csv;
    }
}
