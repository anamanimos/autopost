@extends('layouts.app')

@section('title', 'Manajemen User')

@section('content')
<div class="space-y-6">

    <!-- Top Action & Title Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 dark:border-gray-800 pb-5">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center space-x-3">
                <div class="p-2 bg-gradient-to-tr from-indigo-600 to-purple-600 rounded-lg text-white shadow-md">
                    <i class="fa-solid fa-users text-base"></i>
                </div>
                <span>Manajemen User</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-gray-400 mt-1">
                Kelola hak akses pengguna, persetujuan akun SSO ERP Damai Jaya, dan status aktifasi akun.
            </p>
        </div>

        <div class="flex items-center space-x-3">
            <a href="{{ route('users.create') }}" 
               class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-lg text-xs transition shadow-sm flex items-center space-x-2">
                <i class="fa-solid fa-user-plus text-xs"></i>
                <span>Tambah Pengguna Baru</span>
            </a>
        </div>
    </div>

    <!-- Summary Metrics Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        <!-- Total Users -->
        <div class="card-dark rounded-xl border border-slate-200/90 dark:border-gray-800 p-4 shadow-sm flex items-center space-x-3">
            <div class="w-10 h-10 rounded-lg bg-indigo-50 dark:bg-indigo-950/80 border border-indigo-200 dark:border-indigo-800 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-lg">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <span class="text-[11px] font-semibold text-slate-500 dark:text-gray-400 block uppercase tracking-wider">Total User</span>
                <span class="text-xl font-bold text-slate-900 dark:text-white">{{ $totalUsers }}</span>
            </div>
        </div>

        <!-- Total Admins -->
        <div class="card-dark rounded-xl border border-slate-200/90 dark:border-gray-800 p-4 shadow-sm flex items-center space-x-3">
            <div class="w-10 h-10 rounded-lg bg-purple-50 dark:bg-purple-950/80 border border-purple-200 dark:border-purple-800 text-purple-600 dark:text-purple-400 flex items-center justify-center text-lg">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div>
                <span class="text-[11px] font-semibold text-slate-500 dark:text-gray-400 block uppercase tracking-wider">Administrator</span>
                <span class="text-xl font-bold text-slate-900 dark:text-white">{{ $totalAdmins }}</span>
            </div>
        </div>

        <!-- Total Operators -->
        <div class="card-dark rounded-xl border border-slate-200/90 dark:border-gray-800 p-4 shadow-sm flex items-center space-x-3">
            <div class="w-10 h-10 rounded-lg bg-blue-50 dark:bg-blue-950/80 border border-blue-200 dark:border-blue-800 text-blue-600 dark:text-blue-400 flex items-center justify-center text-lg">
                <i class="fa-solid fa-user-gear"></i>
            </div>
            <div>
                <span class="text-[11px] font-semibold text-slate-500 dark:text-gray-400 block uppercase tracking-wider">Operator</span>
                <span class="text-xl font-bold text-slate-900 dark:text-white">{{ $totalOperators }}</span>
            </div>
        </div>

        <!-- Active Users -->
        <div class="card-dark rounded-xl border border-slate-200/90 dark:border-gray-800 p-4 shadow-sm flex items-center space-x-3">
            <div class="w-10 h-10 rounded-lg bg-emerald-50 dark:bg-emerald-950/80 border border-emerald-200 dark:border-emerald-800 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-lg">
                <i class="fa-solid fa-user-check"></i>
            </div>
            <div>
                <span class="text-[11px] font-semibold text-slate-500 dark:text-gray-400 block uppercase tracking-wider">User Aktif</span>
                <span class="text-xl font-bold text-slate-900 dark:text-white">{{ $activeUsers }}</span>
            </div>
        </div>

        <!-- Pending Approval Users -->
        <div class="card-dark rounded-xl border border-slate-200/90 dark:border-gray-800 p-4 shadow-sm flex items-center space-x-3 col-span-2 sm:col-span-1 {{ $pendingUsers > 0 ? 'border-amber-400/80 bg-amber-50/20 dark:bg-amber-950/20' : '' }}">
            <div class="w-10 h-10 rounded-lg bg-amber-50 dark:bg-amber-950/80 border border-amber-200 dark:border-amber-800 text-amber-600 dark:text-amber-400 flex items-center justify-center text-lg relative">
                <i class="fa-solid fa-user-clock"></i>
                @if($pendingUsers > 0)
                    <span class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-amber-500 rounded-full animate-ping"></span>
                @endif
            </div>
            <div>
                <span class="text-[11px] font-semibold text-slate-500 dark:text-gray-400 block uppercase tracking-wider">Menunggu ACC</span>
                <span class="text-xl font-bold {{ $pendingUsers > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-900 dark:text-white' }}">{{ $pendingUsers }}</span>
            </div>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="card-dark rounded-xl border border-slate-200/90 dark:border-gray-800 p-4 shadow-sm">
        <form action="{{ route('users.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
            <!-- Search Input -->
            <div class="sm:col-span-6 relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-gray-500">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </div>
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}" 
                       placeholder="Cari nama, email, atau no handphone..." 
                       class="block w-full pl-10 pr-3.5 py-2 text-xs rounded-lg border border-slate-300 dark:border-gray-700 bg-white/80 dark:bg-gray-900/60 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <!-- Role Filter -->
            <div class="sm:col-span-3">
                <select name="role" 
                        class="block w-full px-3 py-2 text-xs rounded-lg border border-slate-300 dark:border-gray-700 bg-white/80 dark:bg-gray-900/60 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">Semua Peran</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Administrator</option>
                    <option value="operator" {{ request('role') === 'operator' ? 'selected' : '' }}>Operator</option>
                </select>
            </div>

            <!-- Status Filter -->
            <div class="sm:col-span-2">
                <select name="status" 
                        class="block w-full px-3 py-2 text-xs rounded-lg border border-slate-300 dark:border-gray-700 bg-white/80 dark:bg-gray-900/60 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">Semua Status</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Menunggu ACC (Pending)</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                </select>
            </div>

            <!-- Filter Buttons -->
            <div class="sm:col-span-1 flex items-center space-x-1">
                <button type="submit" 
                        class="w-full py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-semibold transition flex items-center justify-center"
                        title="Terapkan Filter">
                    <i class="fa-solid fa-filter text-xs"></i>
                </button>
                @if(request()->hasAny(['search', 'role', 'status']))
                    <a href="{{ route('users.index') }}" 
                       class="py-2 px-2.5 bg-slate-200 dark:bg-gray-800 hover:bg-slate-300 dark:hover:bg-gray-700 text-slate-700 dark:text-gray-300 rounded-lg text-xs transition flex items-center justify-center"
                       title="Reset Filter">
                        <i class="fa-solid fa-rotate-left text-xs"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Users Table Card -->
    <div class="card-dark rounded-xl border border-slate-200/90 dark:border-gray-800 shadow-sm overflow-hidden">
        @if($users->isEmpty())
            <div class="text-center py-16 space-y-3">
                <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-xl flex items-center justify-center mx-auto text-xl">
                    <i class="fa-solid fa-user-slash"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Tidak Ada Pengguna Ditemukan</h3>
                <p class="text-xs text-slate-500 dark:text-gray-400 max-w-sm mx-auto">
                    Kriteria pencarian atau filter yang Anda masukkan tidak menghasilkan data apapun.
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-gray-800 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">
                            <th class="py-3.5 px-4">Pengguna</th>
                            <th class="py-3.5 px-4">Peran</th>
                            <th class="py-3.5 px-4">Status Akun</th>
                            <th class="py-3.5 px-4">Login Terakhir</th>
                            <th class="py-3.5 px-4">Dibuat Pada</th>
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/80 dark:divide-gray-800/80 text-xs">
                        @foreach($users as $user)
                            <tr class="transition hover:bg-slate-50/50 dark:hover:bg-slate-800/40 {{ $user->isPending() ? 'bg-amber-50/10 dark:bg-amber-950/10' : '' }}">
                                <!-- User Info -->
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-9 h-9 rounded-lg bg-gradient-to-tr from-indigo-500 to-purple-600 text-white font-bold flex items-center justify-center text-xs shadow-sm flex-shrink-0">
                                            {{ $user->initials }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-semibold text-slate-900 dark:text-white flex items-center space-x-1.5 truncate">
                                                <span>{{ $user->name }}</span>
                                                @if($user->id === auth()->id())
                                                    <span class="px-1.5 py-0.2 text-[9px] font-bold rounded bg-indigo-100 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300">Anda</span>
                                                @endif
                                                @if($user->isSsoUser())
                                                    <span class="px-1.5 py-0.2 text-[9px] font-bold rounded bg-blue-100 dark:bg-blue-900/60 text-blue-700 dark:text-blue-300 flex items-center space-x-1" title="Terdaftar via SSO ERP Damai Jaya">
                                                        <i class="fa-solid fa-building-shield text-[8px]"></i>
                                                        <span>SSO ERP</span>
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="text-[11px] text-slate-500 dark:text-gray-400 truncate">{{ $user->email }}</div>
                                            @if($user->phone)
                                                <div class="text-[10px] text-slate-400 dark:text-gray-500 flex items-center space-x-1 mt-0.5">
                                                    <i class="fa-solid fa-phone text-[9px]"></i>
                                                    <span>{{ $user->phone }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <!-- Role Badge -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if($user->isAdmin())
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-50 dark:bg-indigo-950/80 border border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-300 uppercase">
                                            <i class="fa-solid fa-shield-halved text-[9px] mr-1"></i> Admin
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-50 dark:bg-blue-950/80 border border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300 uppercase">
                                            <i class="fa-solid fa-user-gear text-[9px] mr-1"></i> Operator
                                        </span>
                                    @endif
                                </td>

                                <!-- Status Toggle / Badge -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if($user->id === auth()->id())
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/80 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400 uppercase cursor-not-allowed" title="Anda tidak dapat mengubah status akun sendiri">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span> Aktif
                                        </span>
                                    @elseif($user->isPending())
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-amber-50 dark:bg-amber-950/80 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5 animate-pulse"></span>
                                            <span>Menunggu ACC</span>
                                        </span>
                                    @else
                                        <button type="button" 
                                                onclick="toggleUserStatus({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ $user->status }}')"
                                                class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase transition {{ $user->status === 'active' ? 'bg-emerald-50 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 dark:hover:bg-emerald-900/60' : 'bg-rose-50 dark:bg-rose-950/80 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-800 hover:bg-rose-100 dark:hover:bg-rose-900/60' }}">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $user->status === 'active' ? 'bg-emerald-500' : 'bg-rose-500' }} mr-1.5"></span>
                                            <span>{{ $user->status === 'active' ? 'Aktif' : 'Nonaktif' }}</span>
                                        </button>
                                    @endif
                                </td>

                                <!-- Last Login -->
                                <td class="py-3.5 px-4 whitespace-nowrap text-slate-500 dark:text-gray-400">
                                    @if($user->last_login_at)
                                        <div class="font-medium text-slate-700 dark:text-gray-300">
                                            {{ $user->last_login_at->translatedFormat('d M Y, H:i') }}
                                        </div>
                                        <div class="text-[10px] text-slate-400 dark:text-gray-500 font-mono">
                                            IP: {{ $user->last_login_ip ?: '-' }}
                                        </div>
                                    @else
                                        <span class="text-slate-400 dark:text-gray-500 italic">Belum pernah</span>
                                    @endif
                                </td>

                                <!-- Created At -->
                                <td class="py-3.5 px-4 whitespace-nowrap text-slate-500 dark:text-gray-400">
                                    {{ $user->created_at->translatedFormat('d M Y') }}
                                </td>

                                <!-- Action Buttons -->
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center space-x-1.5">
                                        <!-- ACC / Approve Button (for pending users) -->
                                        @if($user->isPending())
                                            <button type="button" 
                                                    onclick="confirmApproveUser({{ $user->id }}, '{{ addslashes($user->name) }}')"
                                                    class="px-2.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-sm flex items-center space-x-1 transition"
                                                    title="Setujui dan Aktifkan Akun (ACC)">
                                                <i class="fa-solid fa-check text-xs"></i>
                                                <span>Setujui (ACC)</span>
                                            </button>
                                        @endif

                                        <a href="{{ route('users.edit', $user->id) }}" 
                                           class="p-1.5 rounded-lg bg-slate-100 dark:bg-gray-800 hover:bg-indigo-50 dark:hover:bg-indigo-950/80 text-slate-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 border border-slate-200 dark:border-gray-700 transition"
                                           title="Edit Pengguna">
                                            <i class="fa-solid fa-pen-to-square text-xs"></i>
                                        </a>

                                        @if($user->id !== auth()->id())
                                            <button type="button" 
                                                    onclick="confirmDeleteUser({{ $user->id }}, '{{ addslashes($user->name) }}')"
                                                    class="p-1.5 rounded-lg bg-slate-100 dark:bg-gray-800 hover:bg-rose-50 dark:hover:bg-rose-950/80 text-slate-600 dark:text-gray-400 hover:text-rose-600 dark:hover:text-rose-400 border border-slate-200 dark:border-gray-700 transition"
                                                    title="Hapus Pengguna">
                                                <i class="fa-solid fa-trash text-xs"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination Bar -->
            @if($users->hasPages())
                <div class="p-4 border-t border-slate-200 dark:border-gray-800">
                    {{ $users->links() }}
                </div>
            @endif
        @endif
    </div>

</div>

<!-- Hidden Forms for Toggle Status, Approve, & Delete -->
<form id="toggleStatusForm" method="POST" class="hidden">
    @csrf
</form>

<form id="approveUserForm" method="POST" class="hidden">
    @csrf
</form>

<form id="deleteUserForm" method="POST" class="hidden">
    @csrf
    @method('DELETE')
</form>

<script>
function confirmApproveUser(userId, userName) {
    Swal.fire({
        title: 'Setujui Akun (ACC)?',
        text: `Apakah Anda yakin ingin menyetujui akun "${userName}"? Pengguna akan langsung diaktifkan dan dapat login ke dalam sistem.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa-solid fa-check mr-1"></i> Ya, Setujui Akun',
        cancelButtonText: 'Batal',
        customClass: {
            popup: 'swal2-popup-dark',
            title: 'swal2-title-dark',
            htmlContainer: 'swal2-html-dark'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.getElementById('approveUserForm');
            form.action = `/users/${userId}/approve`;
            form.submit();
        }
    });
}

function toggleUserStatus(userId, userName, currentStatus) {
    const nextStatus = currentStatus === 'active' ? 'nonaktifkan' : 'aktifkan';
    
    Swal.fire({
        title: `Konfirmasi Status`,
        text: `Apakah Anda yakin ingin ${nextStatus} pengguna "${userName}"?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#64748b',
        confirmButtonText: `Ya, ${nextStatus.charAt(0).toUpperCase() + nextStatus.slice(1)}`,
        cancelButtonText: 'Batal',
        customClass: {
            popup: 'swal2-popup-dark',
            title: 'swal2-title-dark',
            htmlContainer: 'swal2-html-dark'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.getElementById('toggleStatusForm');
            form.action = `/users/${userId}/toggle-status`;
            form.submit();
        }
    });
}

function confirmDeleteUser(userId, userName) {
    Swal.fire({
        title: 'Hapus Pengguna?',
        text: `Akun "${userName}" akan dihapus secara permanen dari sistem. Tindakan ini tidak dapat dibatalkan!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus Akun',
        cancelButtonText: 'Batal',
        customClass: {
            popup: 'swal2-popup-dark',
            title: 'swal2-title-dark',
            htmlContainer: 'swal2-html-dark'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.getElementById('deleteUserForm');
            form.action = `/users/${userId}`;
            form.submit();
        }
    });
}
</script>
@endsection
