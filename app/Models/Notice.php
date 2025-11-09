<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Notice extends Model
{
    protected $table = 'notices';
    protected $primaryKey = 'notice_id';

    protected $fillable = [
        'employee_id',
        'notice_title',
        'file_name',
        'file_size',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    protected $appends = [
        'file_size_formatted',
        'file_extension',
        'is_image',
        'is_document',
        'is_spreadsheet',
    ];

    // Relationships
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    // Accessors
    public function getFileSizeFormattedAttribute(): string
    {
        return $this->formatBytes($this->file_size);
    }

    public function getFileUrlAttribute(): string
    {
        return Storage::url('notices/' . $this->file_name);
    }

    public function getFileExtensionAttribute(): string
    {
        return strtolower(pathinfo($this->file_name, PATHINFO_EXTENSION));
    }

    public function getIsImageAttribute(): bool
    {
        return in_array($this->file_extension, ['jpg', 'jpeg', 'png', 'gif']);
    }

    public function getIsDocumentAttribute(): bool
    {
        return in_array($this->file_extension, ['pdf', 'doc', 'docx']);
    }

    public function getIsSpreadsheetAttribute(): bool
    {
        return in_array($this->file_extension, ['xls', 'xlsx', 'csv']);
    }

    // Scopes
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeByUploader($query, string $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where('notice_title', 'like', "%{$search}%")
            ->orWhere('file_name', 'like', "%{$search}%");
    }

    // Business Methods
    public function canBeModifiedBy(User $user): bool
    {
        // Allow modification by uploader or admin
        return $this->employee_id === $user->employee_id || $user->hasRole('admin');
    }

    public function deleteWithFile(): bool
    {
        // Delete physical file
        if (Storage::disk('public')->exists('notices/' . $this->file_name)) {
            Storage::disk('public')->delete('notices/' . $this->file_name);
        }

        // Delete database record
        return $this->delete();
    }

    public function replaceFile($newFile): bool
    {
        // Delete old file
        if (Storage::disk('public')->exists('notices/' . $this->file_name)) {
            Storage::disk('public')->delete('notices/' . $this->file_name);
        }

        // Generate new filename
        $newFileName = $this->generateUniqueFileName($newFile->getClientOriginalName());

        // Store new file
        $path = $newFile->storeAs('notices', $newFileName, 'public');

        if ($path) {
            $this->update([
                'file_name' => $newFileName,
                'file_size' => $newFile->getSize(),
            ]);

            return true;
        }

        return false;
    }

    private function generateUniqueFileName(string $originalName): string
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $basename = pathinfo($originalName, PATHINFO_FILENAME);

        // Sanitize basename (remove special characters)
        $basename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $basename);

        return $basename . '_' . round(microtime(true) * 1000) . '.' . $extension;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    // Static Methods
    public static function getMaxFileSize(): int
    {
        return 10240000; // 10MB
    }

    public static function getAllowedExtensions(): array
    {
        return ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'jpg', 'jpeg', 'png', 'gif'];
    }

    public static function validateFile($file): array
    {
        $errors = [];

        // Check file size
        if ($file->getSize() > self::getMaxFileSize()) {
            $errors[] = 'File size exceeds the maximum limit of 10MB';
        }

        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::getAllowedExtensions())) {
            $errors[] = 'File type not allowed. Allowed types: ' . implode(', ', self::getAllowedExtensions());
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    public static function generateUniqueFileNameStatic(string $originalName): string
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $basename = pathinfo($originalName, PATHINFO_FILENAME);

        // Sanitize basename (remove special characters)
        $basename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $basename);

        return $basename . '_' . round(microtime(true) * 1000) . '.' . $extension;
    }
}
