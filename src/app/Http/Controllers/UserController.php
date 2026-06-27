<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $roles = DB::table('roles')->select('id', 'name')->get();
        $departments = DB::table('departments')->where('is_active', 1)->select('id', 'name')->get();
        return view('users', compact('roles', 'departments'));
    }

    public function getUsersData(Request $request)
    {
        $query = User::with(['role', 'department'])
            ->select('id', 'name', 'email', 'department_id', 'role_id', 'status', 'created_at');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->role_id);
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('status')) {
            $query->where('status', strtolower($request->status));
        }

        $paginatedData = $query->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($paginatedData);
    }

    public function toggleStatus(User $user)
    {
        $user->status = $user->status === 'active' ? 'inactive' : 'active';
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User status updated successfully.',
            'status' => $user->status
        ]);
    }

    public function stats()
    {
        $total = User::count();
        $active = User::where('status', 'active')->count();
        $admins = User::where('role_id', 1)->count();
        $departments = DB::table('departments')->where('is_active', 1)->count();

        return response()->json([
            'total_users' => $total,
            'active_users' => $active,
            'admin_users' => $admins,
            'department_count' => $departments
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'department_id' => 'required|exists:departments,id',
            'role_id' => 'required|exists:roles,id',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'department_id' => $validated['department_id'],
            'role_id' => $validated['role_id'],
            'password' => Hash::make($validated['password']),
            'status' => 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => "User {$user->name} created successfully.",
            'user' => $user->load(['role', 'department']),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role_id' => 'required|exists:roles,id',
            'department_id' => 'required|exists:departments,id',
        ]);

        $user->update($request->only(['name', 'email', 'department_id', 'role_id']));

        if ($request->filled('password')) {
            $request->validate(['password' => 'string|min:8']);
            $user->password = Hash::make($request->password);
            $user->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Account details updated successfully.',
        ]);
    }
}
