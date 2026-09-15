@extends('layouts.app')

@section('title', 'Manajemen User')

@section('content')
<div class="space-y-6" x-data="userManager()">

    <!-- Top Action & Title Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 dark:border-gray-800 pb-5">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white flex items-center space-x-3">
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
               class="w-full sm:w-auto justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-lg text-xs transition shadow-sm flex items-center space-x-2">
                <i class="fa-solid fa-user-plus text-xs"></i>
                <span>Tambah Pengguna Baru</span>
            </a>
        </div>
    </div>

    <!-- Summary Metrics Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4">
        <!-- Total Users -->
        <div class="card-dark rounded-xl border border-slate-200/90 dark:border-gray-800 p-3 sm:p-4 shadow-sm flex items-center space-x-3">
            <div class="w-10 h-10 rounded-lg bg-indigo-50 dark:bg-indigo-950/80 border border-indigo-200 dark:border-indigo-800 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-lg flex-shrink-0">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <span class="text-[10px] sm:text-[11px] font-semibold text-slate-500 dark:text-gray-400 block uppercase tracking-wider">Total User</span>
                <span class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white">{{ $totalUsers }}</span>
            </div>
        </div>

        <!-- Total Admins -->
        <div class="card-dark rounded-xl border border-slate-200/90 dark:border-gray-800 p-3 sm:p-4 shadow-sm flex items-center space-x-3">
            <div class="w-10 h-10 rounded-lg bg-purple-50 dark:bg-purple-950/80 border border-purple-200 dark:border-purple-800 text-purple-600 dark:text-purple-400 flex items-center justify-center text-lg flex-shrink-0">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div>
                <span class="text-[10px] sm:text-[11px] font-semibold text-slate-500 dark:text-gray-400 block uppercase tracking-wider">Administrator</span>
                <span class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white">{{ $totalAdmins }}</span>
            </div>
        </div>

        <!-- Total Operators -->
        <div class="card-dark rounded-xl border border-slate-200/90 dark:border-gray-800 p-3 sm:p-4 shadow-sm flex items-center space-x-3">
            <div class="w-10 h-10 rounded-lg bg-blue-50 dark:bg-blue-950/80 border border-blue-200 dark:border-blue-800 text-blue-600 dark:text-blue-400 flex items-center justify-center text-lg flex-shrink-0">
                <i class="fa-solid fa-user-gear"></i>
            </div>
            <div>
                <span class="text-[10px] sm:text-[11px] font-semibold text-slate-500 dark:text-gray-400 block uppercase tracking-wider">Operator</span>
                <span class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white">{{ $totalOperators }}</span>
            </div>
        </div>

        <!-- Active Users -->
        <div class="card-dark rounded-xl border border-slate-200/90 dark:border-gray-800 p-3 sm:p-4 shadow-sm flex items-center space-x-3">
            <div class="w-10 h-10 rounded-lg bg-emerald-50 dark:bg-emerald-950/80 border border-emerald-200 dark:border-emerald-800 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-lg flex-shrink-0">
                <i class="fa-solid fa-user-check"></i>
            </div>
            <div>
                <span class="text-[10px] sm:text-[11px] font-semibold text-slate-500 dark:text-gray-400 block uppercase tracking-wider">User Aktif</span>
                <span class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white">{{ $activeUsers }}</span>
            </div>
        </div>

        <!-- Pending Approval Users -->
        <div class="card-dark rounded-xl border border-slate-200/90 dark:border-gray-800 p-3 sm:p-4 shadow-sm flex items-center space-x-3 col-span-2 sm:col-span-1 {{ $pendingUsers > 0 ? 'border-amber-400/80 bg-amber-50/20 dark:bg-amber-950/20' : '' }}">
            <div class="w-10 h-10 rounded-lg bg-amber-50 dark:bg-amber-950/80 border border-amber-200 dark:border-amber-800 text-amber-600 dark:text-amber-400 flex items-center justify-center text-lg relative flex-shrink-0">
                <i class="fa-solid fa-user-clock"></i>
                @if($pendingUsers > 0)
                    <span class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-amber-500 rounded-full animate-ping"></span>
                @endif
            </div>
            <div>
                <span class="text-[10px] sm:text-[11px] font-semibold text-slate-500 dark:text-gray-400 block uppercase tracking-wider">Menunggu ACC</span>
                <span class="text-lg sm:text-xl font-bold {{ $pendingUsers > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-900 dark:text-white' }}">{{ $pendingUsers }}</span>
            </div>
        </div>
    </div>

    <!-- Desktop Search & Filter Bar (hidden on mobile) -->
    <div class="hidden sm:block card-dark rounded-xl border border-slate-200/90 dark:border-gray-800 p-4 shadow-sm">
        <form action="{{ route('users.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
            <!-- Search Input -->
            <div class="sm:col-span-6 relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-gray-500">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </div>
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}" 
                       placeholder="Cari nama, email, atau no. telepon pengguna..." 
                       class="w-full pl-9 pr-4 py-2 bg-white dark:bg-gray-900 border border-slate-300 dark:border-gray-700 rounded-lg text-xs text-slate-800 dark:text-white placeholder-slate-400 dark:placeholder-gray-500 focus:outline-none focus:border-indigo-500 transition">
            </div>

            <!-- Filter Role -->
            <div class="sm:col-span-3">
                <select name="role" 
                        onchange="this.form.submit()" 
                        class="w-full px-3 py-2 bg-white dark:bg-gray-900 border border-slate-300 dark:border-gray-700 rounded-lg text-xs text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 transition">
                    <option value="">Semua Peran (Role)</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Administrator</option>
                    <option value="operator" {{ request('role') === 'operator' ? 'selected' : '' }}>Operator</option>
                </select>
            </div>

            <!-- Filter Status -->
            <div class="sm:col-span-3 flex space-x-2">
                <select name="status" 
                        onchange="this.form.submit()" 
                        class="w-full px-3 py-2 bg-white dark:bg-gray-900 border border-slate-300 dark:border-gray-700 rounded-lg text-xs text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 transition">
                    <option value="">Semua Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Menunggu ACC</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                </select>

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

    <!-- Users Table Card (Desktop) & Cards (Mobile) -->
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
            <!-- ========== DESKTOP TABLE VIEW (hidden on mobile) ========== -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-gray-800 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">
                            <x-sort-th field="name" label="Pengguna" :currentSort="$currentSort ?? request('sort')" :currentDirection="$currentDirection ?? request('direction')" defaultDirection="asc" class="py-3.5 px-4" />
                            <x-sort-th field="role" label="Peran" :currentSort="$currentSort ?? request('sort')" :currentDirection="$currentDirection ?? request('direction')" defaultDirection="asc" class="py-3.5 px-4" />
                            <x-sort-th field="status" label="Status Akun" :currentSort="$currentSort ?? request('sort')" :currentDirection="$currentDirection ?? request('direction')" defaultDirection="asc" class="py-3.5 px-4" />
                            <x-sort-th field="last_login_at" label="Login Terakhir" :currentSort="$currentSort ?? request('sort')" :currentDirection="$currentDirection ?? request('direction')" defaultDirection="desc" class="py-3.5 px-4" />
                            <x-sort-th field="created_at" label="Dibuat Pada" :currentSort="$currentSort ?? request('sort')" :currentDirection="$currentDirection ?? request('direction')" defaultDirection="desc" class="py-3.5 px-4" />
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

            <!-- ========== MOBILE CARD VIEW (visible only on mobile screens < md) ========== -->
            <div class="block md:hidden divide-y divide-slate-200/80 dark:divide-gray-800/80">
                <!-- Mobile Sort Selector -->
                <div class="p-3 bg-slate-50/80 dark:bg-gray-900/60 flex items-center justify-between border-b border-slate-200/80 dark:border-gray-800/80">
                    <label for="mobile-user-sort" class="text-[11px] font-semibold text-slate-500 dark:text-gray-400 flex items-center space-x-1.5">
                        <i class="fa-solid fa-arrow-down-short-wide text-indigo-500"></i>
                        <span>Urutan Pengguna:</span>
                    </label>
                    <select id="mobile-user-sort" onchange="window.location.href=this.value" 
                            class="text-xs bg-white dark:bg-gray-800 border border-slate-300 dark:border-gray-700 rounded-lg px-2.5 py-1.5 font-medium text-slate-700 dark:text-gray-200 focus:ring-1 focus:ring-indigo-500">
                        <option value="{{ request()->fullUrlWithQuery(['sort' => null, 'direction' => null, 'page' => 1]) }}" {{ empty($currentSort ?? request('sort')) ? 'selected' : '' }}>Default (Pending & Role)</option>
                        <option value="{{ request()->fullUrlWithQuery(['sort' => 'name', 'direction' => 'asc', 'page' => 1]) }}" {{ (($currentSort ?? request('sort')) === 'name' && ($currentDirection ?? request('direction')) === 'asc') ? 'selected' : '' }}>Nama A-Z</option>
                        <option value="{{ request()->fullUrlWithQuery(['sort' => 'name', 'direction' => 'desc', 'page' => 1]) }}" {{ (($currentSort ?? request('sort')) === 'name' && ($currentDirection ?? request('direction')) === 'desc') ? 'selected' : '' }}>Nama Z-A</option>
                        <option value="{{ request()->fullUrlWithQuery(['sort' => 'role', 'direction' => 'asc', 'page' => 1]) }}" {{ (($currentSort ?? request('sort')) === 'role' && ($currentDirection ?? request('direction')) === 'asc') ? 'selected' : '' }}>Peran (Admin dulu)</option>
                        <option value="{{ request()->fullUrlWithQuery(['sort' => 'status', 'direction' => 'asc', 'page' => 1]) }}" {{ (($currentSort ?? request('sort')) === 'status' && ($currentDirection ?? request('direction')) === 'asc') ? 'selected' : '' }}>Status Akun</option>
                        <option value="{{ request()->fullUrlWithQuery(['sort' => 'last_login_at', 'direction' => 'desc', 'page' => 1]) }}" {{ (($currentSort ?? request('sort')) === 'last_login_at' && ($currentDirection ?? request('direction')) === 'desc') ? 'selected' : '' }}>Login Terbaru</option>
                        <option value="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'direction' => 'desc', 'page' => 1]) }}" {{ (($currentSort ?? request('sort')) === 'created_at' && ($currentDirection ?? request('direction')) === 'desc') ? 'selected' : '' }}>Terbaru Didaftarkan</option>
                    </select>
                </div>
                @foreach($users as $user)
                    <div class="p-4 space-y-3"
                         x-show="matchesSearch('{{ strtolower(addslashes($user->name . ' ' . $user->email . ' ' . ($user->phone ?? ''))) }}')">
                        <!-- Top Row: Avatar + Name + Badges -->
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center space-x-3 min-w-0">
                                <div class="w-11 h-11 rounded-xl bg-gradient-to-tr from-indigo-500 to-purple-600 text-white font-bold flex items-center justify-center text-sm shadow-sm flex-shrink-0">
                                    {{ $user->initials }}
                                </div>
                                <div class="min-w-0">
                                    <div class="font-bold text-sm text-slate-900 dark:text-white flex items-center space-x-1.5 truncate">
                                        <span class="truncate">{{ $user->name }}</span>
                                        @if($user->id === auth()->id())
                                            <span class="px-1.5 py-0.2 text-[9px] font-bold rounded bg-indigo-100 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 flex-shrink-0">Anda</span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-slate-500 dark:text-gray-400 truncate mt-0.5">{{ $user->email }}</div>
                                    @if($user->phone)
                                        <div class="text-[11px] text-slate-400 dark:text-gray-500 flex items-center space-x-1 mt-0.5">
                                            <i class="fa-solid fa-phone text-[9px]"></i>
                                            <span>{{ $user->phone }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Role and Status Row -->
                        <div class="flex items-center justify-between pt-1 border-t border-slate-100 dark:border-gray-800/60">
                            <div class="flex items-center space-x-2">
                                @if($user->isAdmin())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-50 dark:bg-indigo-950/80 border border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-300 uppercase">
                                        <i class="fa-solid fa-shield-halved text-[9px] mr-1"></i> Admin
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-50 dark:bg-blue-950/80 border border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300 uppercase">
                                        <i class="fa-solid fa-user-gear text-[9px] mr-1"></i> Operator
                                    </span>
                                @endif

                                @if($user->isSsoUser())
                                    <span class="px-1.5 py-0.5 text-[9px] font-bold rounded bg-blue-100 dark:bg-blue-900/60 text-blue-700 dark:text-blue-300 flex items-center space-x-1">
                                        <i class="fa-solid fa-building-shield text-[8px]"></i>
                                        <span>SSO ERP</span>
                                    </span>
                                @endif
                            </div>

                            <!-- Status Badge / Toggle Button -->
                            <div>
                                @if($user->id === auth()->id())
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/80 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400 uppercase">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span> Aktif
                                    </span>
                                @elseif($user->isPending())
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase bg-amber-50 dark:bg-amber-950/80 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5 animate-pulse"></span>
                                        <span>Menunggu ACC</span>
                                    </span>
                                @else
                                    <button type="button" 
                                            onclick="toggleUserStatus({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ $user->status }}')"
                                            class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase transition {{ $user->status === 'active' ? 'bg-emerald-50 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800' : 'bg-rose-50 dark:bg-rose-950/80 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-800' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $user->status === 'active' ? 'bg-emerald-500' : 'bg-rose-500' }} mr-1.5"></span>
                                        <span>{{ $user->status === 'active' ? 'Aktif' : 'Nonaktif' }}</span>
                                    </button>
                                @endif
                            </div>
                        </div>

                        <!-- Details (Login & Created) -->
                        <div class="grid grid-cols-2 gap-2 text-[11px] text-slate-500 dark:text-gray-400 bg-slate-50 dark:bg-gray-800/40 p-2.5 rounded-lg border border-slate-100 dark:border-gray-800">
                            <div>
                                <span class="text-[9px] uppercase tracking-wider block text-slate-400 dark:text-gray-500">Login Terakhir</span>
                                <span class="font-medium text-slate-700 dark:text-gray-300">
                                    {{ $user->last_login_at ? $user->last_login_at->translatedFormat('d M Y, H:i') : 'Belum pernah' }}
                                </span>
                            </div>
                            <div>
                                <span class="text-[9px] uppercase tracking-wider block text-slate-400 dark:text-gray-500">Dibuat Pada</span>
                                <span class="font-medium text-slate-700 dark:text-gray-300">
                                    {{ $user->created_at->translatedFormat('d M Y') }}
                                </span>
                            </div>
                        </div>

                        <!-- Actions Bar -->
                        <div class="flex items-center justify-end space-x-2 pt-1">
                            @if($user->isPending())
                                <button type="button" 
                                        onclick="confirmApproveUser({{ $user->id }}, '{{ addslashes($user->name) }}')"
                                        class="flex-1 py-2 px-3 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-sm flex items-center justify-center space-x-1.5 transition">
                                    <i class="fa-solid fa-check text-xs"></i>
                                    <span>Setujui (ACC)</span>
                                </button>
                            @endif

                            <a href="{{ route('users.edit', $user->id) }}" 
                               class="{{ $user->isPending() ? '' : 'flex-1 justify-center' }} py-2 px-3.5 rounded-lg bg-slate-100 dark:bg-gray-800 hover:bg-indigo-50 dark:hover:bg-indigo-950/80 text-slate-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 border border-slate-200 dark:border-gray-700 transition text-xs font-semibold flex items-center space-x-1.5">
                                <i class="fa-solid fa-pen-to-square text-xs"></i>
                                <span>Edit Akun</span>
                            </a>

                            @if($user->id !== auth()->id())
                                <button type="button" 
                                        onclick="confirmDeleteUser({{ $user->id }}, '{{ addslashes($user->name) }}')"
                                        class="p-2 rounded-lg bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800/80 transition"
                                        title="Hapus Pengguna">
                                    <i class="fa-solid fa-trash text-xs"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination Bar -->
            @if($users->hasPages())
                <div class="p-4 border-t border-slate-200 dark:border-gray-800">
                    {{ $users->links() }}
                </div>
            @endif
        @endif
    </div>

    <!-- ================================================================= -->
    <!-- MOBILE FLOATING ACTION BUTTONS (SEARCH & FILTER)                   -->
    <!-- Positioned above Mobile Bottom Navbar (bottom-20 right-4)         -->
    <!-- ================================================================= -->
    <div class="fixed bottom-20 right-4 z-40 lg:hidden flex items-center space-x-2.5" x-show="!searchOpen">
        <!-- Floating Filter Icon (Opens Bottom Sheet Modal) -->
        <button type="button" @click="mobileFilterOpen = true" 
                class="w-12 h-12 rounded-full bg-slate-900/90 dark:bg-slate-800/95 border border-slate-700/80 text-white shadow-xl flex items-center justify-center transition-all duration-200 active:scale-90 relative"
                title="Buka Filter Pengguna">
            <i class="fa-solid fa-filter text-sm text-indigo-400"></i>
            @if(request()->hasAny(['role', 'status']))
                <span class="absolute -top-1 -right-1 w-3 h-3 bg-indigo-600 rounded-full border-2 border-white dark:border-gray-900 shadow"></span>
            @endif
        </button>

        <!-- Floating Search Icon (Expands to 100% Bar) -->
        <button type="button" @click="searchOpen = true; $nextTick(() => $refs.userSearchInput.focus())" 
                class="w-12 h-12 rounded-full bg-gradient-to-tr from-indigo-600 to-purple-600 text-white shadow-xl flex items-center justify-center transition-all duration-300 active:scale-90"
                title="Cari Pengguna">
            <i class="fa-solid fa-magnifying-glass text-sm"></i>
        </button>
    </div>

    <!-- EXPANDABLE 100% SEARCH BAR (Mobile Full Width on Click) -->
    <div x-show="searchOpen" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-6 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-6 scale-95"
         class="fixed bottom-20 inset-x-3 z-40 lg:hidden bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl p-2 rounded-2xl shadow-2xl border border-indigo-500/50 flex items-center space-x-2">
        <div class="p-2 text-indigo-600 dark:text-indigo-400">
            <i class="fa-solid fa-magnifying-glass text-sm"></i>
        </div>
        <input type="text" x-ref="userSearchInput" x-model="searchQuery" 
               placeholder="Cari nama, email, atau no. telp..." 
               class="flex-1 bg-transparent text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-gray-500 focus:outline-none py-1.5 font-medium">
        <button type="button" x-show="searchQuery" @click="searchQuery = ''" class="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-gray-300 text-xs">
            <i class="fa-solid fa-circle-xmark"></i>
        </button>
        <button type="button" @click="searchOpen = false" class="px-3 py-1.5 bg-slate-100 dark:bg-gray-800 text-slate-700 dark:text-gray-300 rounded-lg text-xs font-semibold hover:bg-slate-200 dark:hover:bg-gray-700">
            Tutup
        </button>
    </div>

    <!-- MOBILE FILTER BOTTOM SHEET MODAL -->
    <div x-show="mobileFilterOpen" class="fixed inset-0 z-50 lg:hidden flex items-end justify-center" style="display: none;">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm" @click="mobileFilterOpen = false"
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"></div>

        <!-- Dialog Sheet -->
        <div class="relative w-full max-h-[85vh] bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-gray-800 rounded-t-2xl shadow-2xl overflow-y-auto p-5 space-y-5 z-10"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-y-full"
             x-transition:enter-end="translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-y-0"
             x-transition:leave-end="translate-y-full">
            
            <!-- Drag Handle Bar -->
            <div class="w-12 h-1.5 rounded-full bg-slate-300 dark:bg-gray-700 mx-auto -mt-1"></div>

            <div class="flex items-center justify-between border-b border-slate-200 dark:border-gray-800 pb-3">
                <div class="flex items-center space-x-2">
                    <div class="p-1.5 bg-indigo-50 dark:bg-indigo-950/80 rounded-lg text-indigo-600 dark:text-indigo-400">
                        <i class="fa-solid fa-filter text-xs"></i>
                    </div>
                    <h3 class="font-bold text-sm text-slate-900 dark:text-white">Filter Pengguna</h3>
                </div>
                <button type="button" @click="mobileFilterOpen = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-white text-base">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form action="{{ route('users.index') }}" method="GET" class="space-y-4">
                @if(request('search'))
                    <input type="hidden" name="search" value="{{ request('search') }}">
                @endif

                <!-- Peran (Role) Filter -->
                <div class="space-y-1.5">
                    <label class="text-[10px] font-semibold text-slate-500 dark:text-gray-400 uppercase tracking-wider block">Peran / Role</label>
                    <select name="role" class="w-full bg-slate-100 dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-xl p-3 text-xs text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500">
                        <option value="">Semua Peran (Role)</option>
                        <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Administrator</option>
                        <option value="operator" {{ request('role') === 'operator' ? 'selected' : '' }}>Operator</option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="space-y-1.5">
                    <label class="text-[10px] font-semibold text-slate-500 dark:text-gray-400 uppercase tracking-wider block">Status Akun</label>
                    <select name="status" class="w-full bg-slate-100 dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-xl p-3 text-xs text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500">
                        <option value="">Semua Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Menunggu ACC</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>

                <!-- Buttons -->
                <div class="pt-3 border-t border-slate-200 dark:border-gray-800 flex items-center space-x-2">
                    <a href="{{ route('users.index') }}" 
                       class="flex-1 py-2.5 px-4 rounded-xl border border-slate-300 dark:border-gray-700 text-slate-700 dark:text-gray-300 font-semibold text-xs text-center hover:bg-slate-100 dark:hover:bg-gray-800 transition">
                        Reset Filter
                    </a>
                    <button type="submit" 
                            class="flex-1 py-2.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 transition text-center">
                        Terapkan Filter
                    </button>
                </div>
            </form>
        </div>
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
function userManager() {
    return {
        searchOpen: false,
        searchQuery: '',
        mobileFilterOpen: false,
        matchesSearch(text) {
            if (!this.searchQuery || this.searchQuery.trim() === '') return true;
            return text.toLowerCase().includes(this.searchQuery.trim().toLowerCase());
        }
    };
}

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
