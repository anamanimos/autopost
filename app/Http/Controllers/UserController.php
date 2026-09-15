<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of users with stats, search and filters.
     */
    public function index(Request $request)
    {
        $totalUsers = User::count();
        $totalAdmins = User::where('role', 'admin')->count();
        $totalOperators = User::where('role', 'operator')->count();
        $activeUsers = User::where('status', 'active')->count();
        $pendingUsers = User::where('status', 'pending')->count();

        $query = User::query();

        // Search by name, email, or phone
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Sorting
        $allowedSorts = ['name', 'role', 'status', 'last_login_at', 'created_at'];
        $sort = $request->get('sort');
        $direction = strtolower($request->get('direction', ''));

        if ($sort && in_array($sort, $allowedSorts)) {
            if (!in_array($direction, ['asc', 'desc'])) {
                $direction = match($sort) {
                    'last_login_at', 'created_at' => 'desc',
                    default => 'asc',
                };
            }
            $query->orderBy($sort, $direction);
            if ($sort !== 'id') {
                $query->orderBy('id', 'desc');
            }
        } else {
            $sort = null;
            $direction = null;
            $query->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
                ->orderBy('role', 'asc')
                ->orderBy('name', 'asc');
        }

        $users = $query->paginate(12)->withQueryString();

        $currentSort = $sort;
        $currentDirection = $direction;

        return view('users.index', compact(
            'users',
            'totalUsers',
            'totalAdmins',
            'totalOperators',
            'activeUsers',
            'pendingUsers',
            'currentSort',
            'currentDirection'
        ));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        return view('users.create');
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in(['admin', 'operator'])],
            'status' => ['required', Rule::in(['active', 'inactive', 'pending'])],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Alamat email sudah terdaftar.',
            'role.required' => 'Peran pengguna wajib dipilih.',
            'role.in' => 'Peran yang dipilih tidak valid.',
            'status.required' => 'Status akun wajib dipilih.',
            'status.in' => 'Status akun tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak sesuai.',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'status' => $validated['status'],
            'approved_at' => $validated['status'] === 'active' ? now() : null,
            'approved_by' => $validated['status'] === 'active' ? Auth::id() : null,
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('users.index')->with('success', "Pengguna {$validated['name']} berhasil ditambahkan!");
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in(['admin', 'operator'])],
            'status' => ['required', Rule::in(['active', 'inactive', 'pending'])],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Alamat email sudah terdaftar pada pengguna lain.',
            'role.required' => 'Peran pengguna wajib dipilih.',
            'role.in' => 'Peran yang dipilih tidak valid.',
            'status.required' => 'Status akun wajib dipilih.',
            'status.in' => 'Status akun tidak valid.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak sesuai.',
        ]);

        // Security Check 1: Cannot demote or deactivate self
        if ($user->id === Auth::id()) {
            if ($validated['role'] !== 'admin') {
                return back()->withInput()->with('error', 'Anda tidak dapat menurunkan peran akun Anda sendiri.');
            }
            if ($validated['status'] !== 'active') {
                return back()->withInput()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
            }
        }

        // Security Check 2: Cannot demote the last admin
        if ($user->role === 'admin' && $validated['role'] !== 'admin') {
            $otherAdminsCount = User::where('role', 'admin')->where('id', '!=', $user->id)->count();
            if ($otherAdminsCount === 0) {
                return back()->withInput()->with('error', 'Tidak dapat mengubah peran: Harus ada minimal 1 Administrator di dalam sistem.');
            }
        }

        // Security Check 3: Cannot deactivate the last active admin
        if ($user->role === 'admin' && $validated['status'] !== 'active') {
            $activeAdminsCount = User::where('role', 'admin')
                ->where('status', 'active')
                ->where('id', '!=', $user->id)
                ->count();
            if ($activeAdminsCount === 0) {
                return back()->withInput()->with('error', 'Tidak dapat menonaktifkan satu-satunya Administrator yang aktif.');
            }
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? null;
        $user->role = $validated['role'];
        $user->status = $validated['status'];

        if ($request->filled('password')) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('users.index')->with('success', "Data pengguna {$user->name} berhasil diperbarui!");
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        // Security Check 1: Cannot delete self
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        // Security Check 2: Cannot delete the last admin
        if ($user->role === 'admin') {
            $otherAdminsCount = User::where('role', 'admin')->where('id', '!=', $user->id)->count();
            if ($otherAdminsCount === 0) {
                return back()->with('error', 'Tidak dapat menghapus: Harus ada minimal 1 Administrator yang tersisa.');
            }
        }

        $userName = $user->name;
        $user->delete();

        return redirect()->route('users.index')->with('success', "Pengguna {$userName} berhasil dihapus.");
    }

    /**
     * Approve (ACC) a pending or inactive user.
     */
    public function approve(User $user)
    {
        if ($user->status === 'active') {
            return back()->with('info', "Akun {$user->name} sudah dalam keadaan aktif.");
        }

        $user->status = 'active';
        $user->approved_at = now();
        $user->approved_by = Auth::id();
        $user->save();

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Akun {$user->name} berhasil disetujui (di-ACC) dan kini aktif!",
            ]);
        }

        return back()->with('success', "Akun {$user->name} berhasil disetujui (di-ACC) dan kini aktif!");
    }

    /**
     * Toggle active/inactive status.
     */
    public function toggleStatus(User $user)
    {
        // Security Check: Cannot toggle self
        if ($user->id === Auth::id()) {
            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Anda tidak dapat menonaktifkan akun Anda sendiri.'], 422);
            }
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        // Security Check: Cannot deactivate last active admin
        if ($user->role === 'admin' && $user->status === 'active') {
            $activeAdminsCount = User::where('role', 'admin')
                ->where('status', 'active')
                ->where('id', '!=', $user->id)
                ->count();
            if ($activeAdminsCount === 0) {
                if (request()->expectsJson()) {
                    return response()->json(['success' => false, 'message' => 'Tidak dapat menonaktifkan satu-satunya Administrator yang aktif.'], 422);
                }
                return back()->with('error', 'Tidak dapat menonaktifkan satu-satunya Administrator yang aktif.');
            }
        }

        if ($user->status === 'pending') {
            $user->status = 'active';
            $user->approved_at = now();
            $user->approved_by = Auth::id();
        } else {
            $user->status = ($user->status === 'active') ? 'inactive' : 'active';
            if ($user->status === 'active' && ! $user->approved_at) {
                $user->approved_at = now();
                $user->approved_by = Auth::id();
            }
        }

        $user->save();

        $statusLabel = $user->status === 'active' ? 'diaktifkan' : 'dinonaktifkan';

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'status' => $user->status,
                'message' => "Pengguna {$user->name} berhasil {$statusLabel}.",
            ]);
        }

        return back()->with('success', "Status pengguna {$user->name} berhasil {$statusLabel}.");
    }
}
