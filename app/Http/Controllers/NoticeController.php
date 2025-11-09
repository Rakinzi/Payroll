<?php

namespace App\Http\Controllers;

use App\Models\Notice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class NoticeController extends Controller
{
    /**
     * Display a listing of notices.
     */
    public function index(Request $request)
    {
        $query = Notice::with('uploader')
            ->orderBy('created_at', 'desc');

        // Apply search filter
        if ($request->has('search') && !empty($request->search)) {
            $query->search($request->search);
        }

        $notices = $query->paginate(15)->through(function ($notice) {
            return [
                'notice_id' => $notice->notice_id,
                'employee_id' => $notice->employee_id,
                'notice_title' => $notice->notice_title,
                'file_name' => $notice->file_name,
                'file_size' => $notice->file_size,
                'file_size_formatted' => $notice->file_size_formatted,
                'file_extension' => $notice->file_extension,
                'is_image' => $notice->is_image,
                'is_document' => $notice->is_document,
                'is_spreadsheet' => $notice->is_spreadsheet,
                'created_at' => $notice->created_at,
                'updated_at' => $notice->updated_at,
                'uploader' => $notice->uploader ? [
                    'id' => $notice->uploader->id,
                    'firstname' => $notice->uploader->firstname,
                    'surname' => $notice->uploader->surname,
                ] : null,
                'can_modify' => $notice->canBeModifiedBy(Auth::user()),
            ];
        });

        return Inertia::render('notices/index', [
            'notices' => $notices,
            'filters' => $request->only(['search']),
            'maxFileSize' => Notice::getMaxFileSize(),
            'allowedExtensions' => Notice::getAllowedExtensions(),
        ]);
    }

    /**
     * Store a newly created notice.
     */
    public function store(Request $request)
    {
        $request->validate([
            'notice_title' => 'required|string|max:255',
            'attach_file' => 'required|file|max:' . (Notice::getMaxFileSize() / 1024), // Convert to KB
        ]);

        $file = $request->file('attach_file');

        // Validate file
        $validation = Notice::validateFile($file);
        if (!$validation['valid']) {
            return back()->withErrors(['attach_file' => $validation['errors'][0]]);
        }

        try {
            // Generate unique filename
            $newFileName = Notice::generateUniqueFileNameStatic($file->getClientOriginalName());

            // Store file
            $path = $file->storeAs('notices', $newFileName, 'public');

            if (!$path) {
                return back()->withErrors(['attach_file' => 'File upload failed']);
            }

            // Create notice record
            Notice::create([
                'employee_id' => Auth::user()->employee_id,
                'notice_title' => $request->notice_title,
                'file_name' => $newFileName,
                'file_size' => $file->getSize(),
            ]);

            Log::info("Notice created by user {$request->user()->id}: {$request->notice_title}");

            return back()->with('success', 'Notice uploaded successfully');

        } catch (\Exception $e) {
            Log::error("Notice creation failed: " . $e->getMessage());
            return back()->withErrors(['attach_file' => 'An error occurred while uploading the notice']);
        }
    }

    /**
     * Display the specified notice.
     */
    public function show(Notice $notice)
    {
        return response()->json([
            'notice' => [
                'notice_id' => $notice->notice_id,
                'employee_id' => $notice->employee_id,
                'notice_title' => $notice->notice_title,
                'file_name' => $notice->file_name,
                'file_size' => $notice->file_size,
                'file_size_formatted' => $notice->file_size_formatted,
                'file_extension' => $notice->file_extension,
                'created_at' => $notice->created_at,
                'updated_at' => $notice->updated_at,
                'uploader' => $notice->uploader ? [
                    'id' => $notice->uploader->id,
                    'firstname' => $notice->uploader->firstname,
                    'surname' => $notice->uploader->surname,
                ] : null,
            ],
            'can_modify' => $notice->canBeModifiedBy(Auth::user()),
        ]);
    }

    /**
     * Update the specified notice.
     */
    public function update(Request $request, Notice $notice)
    {
        $this->authorize('update', $notice);

        $request->validate([
            'notice_title' => 'required|string|max:255',
            'attach_file' => 'nullable|file|max:' . (Notice::getMaxFileSize() / 1024),
        ]);

        try {
            $updateData = ['notice_title' => $request->notice_title];

            // Handle file replacement
            if ($request->hasFile('attach_file')) {
                $file = $request->file('attach_file');
                $validation = Notice::validateFile($file);

                if (!$validation['valid']) {
                    return back()->withErrors(['attach_file' => $validation['errors'][0]]);
                }

                if (!$notice->replaceFile($file)) {
                    return back()->withErrors(['attach_file' => 'File replacement failed']);
                }
            } else {
                // Update only the title
                $notice->update($updateData);
            }

            Log::info("Notice updated by user {$request->user()->id}: Notice ID {$notice->notice_id}");

            return back()->with('success', 'Notice updated successfully');

        } catch (\Exception $e) {
            Log::error("Notice update failed: " . $e->getMessage());
            return back()->withErrors(['notice' => 'An error occurred while updating the notice']);
        }
    }

    /**
     * Remove the specified notice.
     */
    public function destroy(Notice $notice)
    {
        $this->authorize('delete', $notice);

        try {
            $noticeId = $notice->notice_id;
            $noticeTitle = $notice->notice_title;

            if ($notice->deleteWithFile()) {
                Log::info("Notice deleted by user " . Auth::id() . ": Notice ID {$noticeId} - {$noticeTitle}");
                return back()->with('success', 'Notice deleted successfully');
            }

            return back()->withErrors(['notice' => 'Notice deletion failed']);

        } catch (\Exception $e) {
            Log::error("Notice deletion failed: " . $e->getMessage());
            return back()->withErrors(['notice' => 'An error occurred while deleting the notice']);
        }
    }

    /**
     * Download the notice file.
     */
    public function download(Notice $notice)
    {
        try {
            $filePath = 'notices/' . $notice->file_name;

            if (!Storage::disk('public')->exists($filePath)) {
                return back()->withErrors(['notice' => 'File not found']);
            }

            // Log download activity
            Log::info("Notice downloaded by user " . Auth::id() . ": Notice ID {$notice->notice_id}");

            return Storage::disk('public')->download($filePath, $notice->notice_title . '.' . $notice->file_extension);

        } catch (\Exception $e) {
            Log::error("Notice download failed: " . $e->getMessage());
            return back()->withErrors(['notice' => 'An error occurred while downloading the notice']);
        }
    }

    /**
     * Get latest notices for dashboard.
     */
    public function latest(Request $request)
    {
        $limit = $request->get('limit', 5);

        $notices = Notice::with('uploader')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($notice) {
                return [
                    'notice_id' => $notice->notice_id,
                    'notice_title' => $notice->notice_title,
                    'file_name' => $notice->file_name,
                    'file_size_formatted' => $notice->file_size_formatted,
                    'file_extension' => $notice->file_extension,
                    'created_at' => $notice->created_at,
                    'uploader' => $notice->uploader ? [
                        'firstname' => $notice->uploader->firstname,
                        'surname' => $notice->uploader->surname,
                    ] : null,
                ];
            });

        return response()->json(['notices' => $notices]);
    }
}
