<?php

namespace App\Jobs;

use App\Models\ImportError;
use App\Models\ImportMapping;
use App\Models\ImportSession;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessSpreadsheetImport implements ShouldQueue
{
    use Queueable;

    protected ImportSession $session;

    /**
     * Create a new job instance.
     */
    public function __construct(ImportSession $session)
    {
        $this->session = $session;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Mark as processing
            $this->session->markProcessing();

            // Read CSV file
            $data = $this->readCsvFile($this->session->file_path);

            if (empty($data)) {
                $this->session->markFailed();
                return;
            }

            // Update total rows
            $this->session->update([
                'total_rows' => count($data),
                'processed_rows' => 0,
                'error_rows' => 0,
            ]);

            // Process each row
            $errors = [];
            $validatedData = [];

            foreach ($data as $rowIndex => $row) {
                try {
                    $validated = $this->validateAndTransformRow($row, $rowIndex);
                    $validatedData[] = $validated;
                    $this->session->incrementProcessed();
                } catch (ValidationException $e) {
                    $this->session->incrementErrors();
                    $errors[] = [
                        'session_id' => $this->session->id,
                        'row_number' => $rowIndex + 1,
                        'column_name' => $e->getField(),
                        'error_message' => $e->getMessage(),
                        'raw_value' => $e->getValue(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                } catch (Exception $e) {
                    $this->session->incrementErrors();
                    $errors[] = [
                        'session_id' => $this->session->id,
                        'row_number' => $rowIndex + 1,
                        'column_name' => null,
                        'error_message' => 'Unexpected error: ' . $e->getMessage(),
                        'raw_value' => json_encode($row),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            // Save errors
            if (!empty($errors)) {
                ImportError::insert($errors);
            }

            // Store preview data in cache (24 hours)
            Cache::put("import_preview_{$this->session->id}", $validatedData, now()->addHours(24));

            // Mark as preview ready
            $this->session->markPreview();

            // Delete temp file
            if (Storage::exists($this->session->file_path)) {
                Storage::delete($this->session->file_path);
            }
        } catch (Exception $e) {
            $this->session->markFailed();
            Log::error('Spreadsheet import processing failed', [
                'session_id' => $this->session->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Read CSV file and return data array.
     */
    private function readCsvFile(string $filePath): array
    {
        $data = [];
        $fullPath = Storage::path($filePath);

        if (!file_exists($fullPath)) {
            throw new Exception("File not found: {$filePath}");
        }

        $handle = fopen($fullPath, 'r');

        if ($handle === false) {
            throw new Exception("Cannot open file: {$filePath}");
        }

        // Read header row
        $headers = fgetcsv($handle);

        if ($headers === false || empty($headers)) {
            fclose($handle);
            throw new Exception('CSV file is empty or invalid');
        }

        // Clean headers
        $headers = array_map('trim', $headers);

        // Read data rows
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === 1 && empty($row[0])) {
                continue; // Skip empty rows
            }

            // Create associative array with headers as keys
            $rowData = [];
            foreach ($headers as $index => $header) {
                $rowData[$header] = $row[$index] ?? null;
            }

            $data[] = $rowData;
        }

        fclose($handle);
        return $data;
    }

    /**
     * Validate and transform a single row.
     */
    private function validateAndTransformRow(array $row, int $rowIndex): array
    {
        $mappings = ImportMapping::active()
            ->forType($this->session->import_type)
            ->orderBy('display_order')
            ->get();

        if ($mappings->isEmpty()) {
            // No mappings defined, return row as-is
            return $row;
        }

        $validatedData = [];

        foreach ($mappings as $mapping) {
            $columnName = $mapping->spreadsheet_column;
            $value = $row[$columnName] ?? null;

            // Check required fields
            if ($mapping->is_required && ($value === null || $value === '')) {
                throw new ValidationException(
                    $columnName,
                    "Required field '{$columnName}' is missing or empty",
                    $value
                );
            }

            // Skip validation if value is empty and not required
            if (($value === null || $value === '') && !$mapping->is_required) {
                $validatedData[$mapping->database_field] = null;
                continue;
            }

            // Apply validation
            $validatedValue = $this->validateField($value, $mapping);

            // Apply transformations
            $transformedValue = $this->transformValue($validatedValue, $mapping);

            $validatedData[$mapping->database_field] = $transformedValue;
        }

        return $validatedData;
    }

    /**
     * Validate a field value.
     */
    private function validateField($value, ImportMapping $mapping)
    {
        $rules = $mapping->validation_rules_array;

        foreach ($rules as $rule => $parameters) {
            switch ($rule) {
                case 'email':
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        throw new ValidationException(
                            $mapping->spreadsheet_column,
                            "Invalid email format",
                            $value
                        );
                    }
                    break;

                case 'numeric':
                    if (!is_numeric($value)) {
                        throw new ValidationException(
                            $mapping->spreadsheet_column,
                            "Field must be numeric",
                            $value
                        );
                    }
                    break;

                case 'integer':
                    if (!is_numeric($value) || (int) $value != $value) {
                        throw new ValidationException(
                            $mapping->spreadsheet_column,
                            "Field must be an integer",
                            $value
                        );
                    }
                    break;

                case 'min':
                    if (is_numeric($value) && $value < $parameters) {
                        throw new ValidationException(
                            $mapping->spreadsheet_column,
                            "Value must be at least {$parameters}",
                            $value
                        );
                    }
                    break;

                case 'max':
                    if (is_numeric($value) && $value > $parameters) {
                        throw new ValidationException(
                            $mapping->spreadsheet_column,
                            "Value must not exceed {$parameters}",
                            $value
                        );
                    }
                    break;

                case 'date':
                    if (!strtotime($value)) {
                        throw new ValidationException(
                            $mapping->spreadsheet_column,
                            "Invalid date format",
                            $value
                        );
                    }
                    break;

                case 'in':
                    if (!in_array($value, $parameters)) {
                        throw new ValidationException(
                            $mapping->spreadsheet_column,
                            "Value must be one of: " . implode(', ', $parameters),
                            $value
                        );
                    }
                    break;
            }
        }

        return $value;
    }

    /**
     * Transform a field value.
     */
    private function transformValue($value, ImportMapping $mapping)
    {
        if ($value === null || $value === '') {
            return null;
        }

        $transformations = $mapping->transformation_rules_array;

        foreach ($transformations as $transformation => $parameters) {
            switch ($transformation) {
                case 'uppercase':
                    $value = strtoupper($value);
                    break;

                case 'lowercase':
                    $value = strtolower($value);
                    break;

                case 'trim':
                    $value = trim($value);
                    break;

                case 'date_format':
                    try {
                        $from = $parameters['from'] ?? 'Y-m-d';
                        $to = $parameters['to'] ?? 'Y-m-d';
                        $date = \DateTime::createFromFormat($from, $value);

                        if (!$date) {
                            throw new ValidationException(
                                $mapping->spreadsheet_column,
                                "Invalid date format. Expected: {$from}",
                                $value
                            );
                        }

                        $value = $date->format($to);
                    } catch (Exception $e) {
                        throw new ValidationException(
                            $mapping->spreadsheet_column,
                            "Date transformation failed: " . $e->getMessage(),
                            $value
                        );
                    }
                    break;

                case 'boolean':
                    $value = in_array(strtolower($value), ['1', 'true', 'yes', 'y']) ? 1 : 0;
                    break;

                case 'decimal':
                    $places = $parameters['places'] ?? 2;
                    $value = number_format((float) $value, $places, '.', '');
                    break;
            }
        }

        return $value;
    }
}

/**
 * Custom validation exception for import processing.
 */
class ValidationException extends Exception
{
    private string $field;
    private $rawValue;

    public function __construct(string $field, string $message, $rawValue)
    {
        parent::__construct($message);
        $this->field = $field;
        $this->rawValue = $rawValue;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getValue()
    {
        return $this->rawValue;
    }
}
