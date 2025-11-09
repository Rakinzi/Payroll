<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class UserProfile extends Model
{
    protected $table = 'user_profiles';
    protected $primaryKey = 'profile_id';

    protected $fillable = [
        'user_id',
        'avatar_path',
        'signature_path',
        'preferences',
    ];

    protected $casts = [
        'preferences' => 'array',
    ];

    protected $appends = [
        'avatar_url',
        'signature_url',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    // Accessors
    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar_path ? Storage::url('avatars/' . $this->avatar_path) : null;
    }

    public function getSignatureUrlAttribute(): ?string
    {
        return $this->signature_path ? Storage::url('signatures/' . $this->signature_path) : null;
    }

    // Business Methods
    public function updateAvatar($file): bool
    {
        // Delete old avatar
        if ($this->avatar_path) {
            Storage::disk('public')->delete('avatars/' . $this->avatar_path);
        }

        // Generate new filename
        $filename = $this->generateFileName($file, 'avatar');

        // Validate file size (max 128KB)
        if ($file->getSize() > 128000) {
            throw new \InvalidArgumentException('Avatar file size must be less than 128KB');
        }

        // Store new avatar
        $path = $file->storeAs('avatars', $filename, 'public');

        if ($path) {
            $this->update(['avatar_path' => $filename]);
            return true;
        }

        return false;
    }

    public function updateSignature(string $signatureData): bool
    {
        // Delete old signature
        if ($this->signature_path) {
            Storage::disk('public')->delete('signatures/' . $this->signature_path);
        }

        // Generate new filename
        $filename = 'signature_' . $this->user_id . '_' . time() . '.png';

        // Decode and store base64 signature
        $signatureData = str_replace('data:image/png;base64,', '', $signatureData);
        $signatureData = str_replace(' ', '+', $signatureData);
        $signatureBlob = base64_decode($signatureData);

        if ($signatureBlob === false) {
            throw new \InvalidArgumentException('Invalid signature data');
        }

        $success = Storage::disk('public')->put('signatures/' . $filename, $signatureBlob);

        if ($success) {
            $this->update(['signature_path' => $filename]);
            return true;
        }

        return false;
    }

    public function updatePassword(string $newPassword): bool
    {
        return $this->user->update(['password' => Hash::make($newPassword)]);
    }

    public function updatePayslipPassword(string $newPassword): bool
    {
        return $this->user->employee->update(['payslip_password' => Hash::make($newPassword)]);
    }

    public function updatePreference(string $key, $value): void
    {
        $preferences = $this->preferences ?? [];
        $preferences[$key] = $value;
        $this->update(['preferences' => $preferences]);
    }

    public function getPreference(string $key, $default = null)
    {
        return $this->preferences[$key] ?? $default;
    }

    private function generateFileName($file, string $prefix): string
    {
        $extension = $file->getClientOriginalExtension();
        return $prefix . '_' . $this->user_id . '_' . time() . '.' . $extension;
    }

    // Static Methods
    public static function getForUser(int $userId): self
    {
        return self::firstOrCreate(['user_id' => $userId]);
    }

    public static function validateAvatarFile($file): array
    {
        $errors = [];

        // Check file size (max 128KB)
        if ($file->getSize() > 128000) {
            $errors[] = 'Avatar file size must be less than 128KB';
        }

        // Check file type
        $allowedTypes = ['jpg', 'jpeg', 'png', 'JPG', 'PNG'];
        $extension = $file->getClientOriginalExtension();

        if (!in_array($extension, $allowedTypes)) {
            $errors[] = 'Avatar must be a JPG or PNG file';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
