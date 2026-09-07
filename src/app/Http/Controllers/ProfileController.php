<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    /**
     * Show the authenticated user's self-service profile page.
     *
     * @return \Illuminate\View\View
     */
    public function show()
    {
        $user = auth()->user()->load(['department', 'role']);

        return view('profile', compact('user'));
    }

    /**
     * Upload and persist a new profile picture for the authenticated user.
     *
     * Validates the actual image content (not the client-declared extension),
     * stores it on the public disk, removes any previous file, and updates
     * the user's avatar_path.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
                'dimensions:max_width=2048,max_height=2048',
            ],
        ]);

        $user = auth()->user();
        $file = $request->file('avatar');

        $filename = Str::uuid() . '.' . $file->extension();
        $path = $file->storeAs('avatars', $filename, 'public');

        $previousAvatarPath = $user->avatar_path;
        $user->avatar_path = $path;
        $user->save();

        if ($previousAvatarPath) {
            Storage::disk('public')->delete($previousAvatarPath);
        }

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Profile picture updated.',
                'avatar_url' => $user->avatarUrl(),
            ]);
        }

        return redirect()->route('profile')->with('success', 'Profile picture updated.');
    }

    /**
     * Return paginated documents uploaded by the authenticated user.
     *
     * Filters strictly by uploaded_by_user_id (the individual uploader), not by
     * sender department, mirroring the outbox column set and paginator envelope.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function documentsData(Request $request): JsonResponse
    {
        $user = auth()->user();

        $query = DB::table('documents')
            ->join('document_types', 'documents.document_type_id', '=', 'document_types.id')
            ->join('departments as current_dept', 'documents.current_department_id', '=', 'current_dept.id')
            ->select(
                'documents.id as doc_id',
                'documents.document_number',
                'documents.title',
                'document_types.name as document_type_name',
                'current_dept.name as current_department',
                'documents.status as status',
                'documents.created_at as date_uploaded'
            )
            ->where('documents.uploaded_by_user_id', $user->id);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('documents.title', 'like', '%' . $search . '%')
                  ->orWhere('documents.document_number', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('type')) {
            $query->where('documents.document_type_id', $request->type);
        }

        if ($request->filled('date')) {
            $query->whereDate('documents.created_at', $request->date);
        }

        if ($request->filled('status')) {
            $statusValue = str_replace(' ', '_', strtolower($request->status));
            $query->where('documents.status', $statusValue);
        }

        $paginatedData = $query->orderBy('documents.created_at', 'desc')
            ->paginate(10);

        return response()->json($paginatedData);
    }
}
