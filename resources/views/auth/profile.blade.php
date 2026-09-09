@extends('layouts.app')

@section('title', 'Profil Saya')

@section('content')
<div class="w-full space-y-6">

    <!-- Page Header -->
    <div class="flex items-center justify-between border-b border-slate-200 dark:border-gray-800 pb-5">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center space-x-3">
                <div class="p-2 bg-gradient-to-tr from-indigo-600 to-purple-600 rounded-lg text-white shadow-md">
                    <i class="fa-solid fa-user-circle text-base"></i>
                </div>
                <span>Profil Saya</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-gray-400 mt-1">
                Kelola informasi akun pribadi dan perbarui kata sandi keamanan Anda.
            </p>
        </div>

        <a href="{{ route('projects.index') }}" 
           class="px-3.5 py-2 bg-slate-200/80 hover:bg-slate-300/80 dark:bg-gray-800 dark:hover:bg-gray-700 text-slate-700 dark:text-gray-300 font-semibold rounded-lg text-xs transition flex items-center space-x-2">
            <i class="fa-solid fa-arrow-left text-xs"></i>
            <span>Kembali</span>
        </a>
    </div>

    <!-- User Information Summary Card -->
    <div class="card-dark rounded-xl border border-slate-200/90 dark:border-gray-800 p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center space-x-4">
                <div class="w-16 h-16 rounded-xl bg-gradient-to-tr from-indigo-500 via-purple-600 to-pink-500 flex items-center justify-center text-white text-xl font-bold shadow-lg shadow-indigo-500/25">
                    {{ $user->initials }}
                </div>
                <div>
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ $user->name }}</h2>
                    <p class="text-xs text-slate-500 dark:text-gray-400">{{ $user->email }}</p>
                    <div class="flex items-center space-x-2 mt-2">
                        @if($user->isAdmin())
                            <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-md bg-indigo-50 dark:bg-indigo-950/80 border border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-300 uppercase">
                                <i class="fa-solid fa-shield-halved text-[9px] mr-1"></i> Administrator
                            </span>
                        @else
                            <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-md bg-blue-50 dark:bg-blue-950/80 border border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300 uppercase">
                                <i class="fa-solid fa-user-gear text-[9px] mr-1"></i> Operator
                            </span>
                        @endif

                        <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-md bg-emerald-50 dark:bg-emerald-950/80 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400 uppercase">
                            <i class="fa-solid fa-circle-check text-[9px] mr-1"></i> Aktif
                        </span>
                    </div>
                </div>
            </div>

            <div class="text-xs text-slate-500 dark:text-gray-400 space-y-1 bg-slate-50 dark:bg-gray-900/40 p-3 rounded-xl border border-slate-200/60 dark:border-gray-800">
                <div><span class="font-medium text-slate-700 dark:text-gray-300">Login Terakhir:</span> {{ $user->last_login_at ? $user->last_login_at->translatedFormat('d M Y H:i') : 'Belum pernah' }}</div>
                <div><span class="font-medium text-slate-700 dark:text-gray-300">IP Login:</span> {{ $user->last_login_ip ?: '-' }}</div>
                <div><span class="font-medium text-slate-700 dark:text-gray-300">Bergabung:</span> {{ $user->created_at->translatedFormat('d F Y') }}</div>
            </div>
        </div>
    </div>

    <!-- Profile Edit Form -->
    <div class="card-dark rounded-xl border border-slate-200/90 dark:border-gray-800 p-6 sm:p-8 shadow-sm">
        <form action="{{ route('profile.update') }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-900 dark:text-white border-b border-slate-200 dark:border-gray-800 pb-3 flex items-center space-x-2">
                <i class="fa-solid fa-id-card text-indigo-500"></i>
                <span>Informasi Data Diri</span>
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Name -->
                <div class="space-y-1.5">
                    <label for="name" class="block text-xs font-semibold text-slate-700 dark:text-gray-300">
                        Nama Lengkap <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" 
                           name="name" 
                           id="name" 
                           value="{{ old('name', $user->name) }}" 
                           required 
                           class="block w-full px-3.5 py-2.5 text-xs rounded-lg border border-slate-300 dark:border-gray-700 bg-white/80 dark:bg-gray-900/60 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('name')
                        <p class="text-[11px] text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div class="space-y-1.5">
                    <label for="email" class="block text-xs font-semibold text-slate-700 dark:text-gray-300">
                        Alamat Email <span class="text-rose-500">*</span>
                    </label>
                    <input type="email" 
                           name="email" 
                           id="email" 
                           value="{{ old('email', $user->email) }}" 
                           required 
                           class="block w-full px-3.5 py-2.5 text-xs rounded-lg border border-slate-300 dark:border-gray-700 bg-white/80 dark:bg-gray-900/60 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('email')
                        <p class="text-[11px] text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Phone -->
                <div class="space-y-1.5 sm:col-span-2">
                    <label for="phone" class="block text-xs font-semibold text-slate-700 dark:text-gray-300">
                        Nomor Handphone / WhatsApp (Opsional)
                    </label>
                    <input type="text" 
                           name="phone" 
                           id="phone" 
                           value="{{ old('phone', $user->phone) }}" 
                           placeholder="081234567890"
                           class="block w-full px-3.5 py-2.5 text-xs rounded-lg border border-slate-300 dark:border-gray-700 bg-white/80 dark:bg-gray-900/60 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('phone')
                        <p class="text-[11px] text-rose-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Password Update Section -->
            <div class="pt-4 border-t border-slate-200 dark:border-gray-800 space-y-4">
                <div>
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-900 dark:text-white flex items-center space-x-2">
                        <i class="fa-solid fa-key text-indigo-500"></i>
                        <span>Ubah Kata Sandi</span>
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-gray-400 mt-1">
                        Kosongkan bagian ini jika Anda tidak ingin memperbarui kata sandi.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Current Password -->
                    <div class="space-y-1.5">
                        <label for="current_password" class="block text-xs font-semibold text-slate-700 dark:text-gray-300">
                            Kata Sandi Saat Ini
                        </label>
                        <input type="password" 
                               name="current_password" 
                               id="current_password" 
                               autocomplete="current-password"
                               placeholder="••••••••"
                               class="block w-full px-3.5 py-2.5 text-xs rounded-lg border border-slate-300 dark:border-gray-700 bg-white/80 dark:bg-gray-900/60 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @error('current_password')
                            <p class="text-[11px] text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- New Password -->
                    <div class="space-y-1.5">
                        <label for="password" class="block text-xs font-semibold text-slate-700 dark:text-gray-300">
                            Kata Sandi Baru
                        </label>
                        <input type="password" 
                               name="password" 
                               id="password" 
                               autocomplete="new-password"
                               placeholder="Min. 8 karakter"
                               class="block w-full px-3.5 py-2.5 text-xs rounded-lg border border-slate-300 dark:border-gray-700 bg-white/80 dark:bg-gray-900/60 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @error('password')
                            <p class="text-[11px] text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password Confirmation -->
                    <div class="space-y-1.5">
                        <label for="password_confirmation" class="block text-xs font-semibold text-slate-700 dark:text-gray-300">
                            Ulangi Kata Sandi Baru
                        </label>
                        <input type="password" 
                               name="password_confirmation" 
                               id="password_confirmation" 
                               autocomplete="new-password"
                               placeholder="Ulangi kata sandi baru"
                               class="block w-full px-3.5 py-2.5 text-xs rounded-lg border border-slate-300 dark:border-gray-700 bg-white/80 dark:bg-gray-900/60 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-4 flex items-center justify-end space-x-3">
                <button type="submit" 
                        class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-lg text-xs transition shadow-sm flex items-center space-x-2">
                    <i class="fa-solid fa-floppy-disk text-xs"></i>
                    <span>Simpan Perubahan</span>
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
