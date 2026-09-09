@extends('layouts.app')

@section('title', 'Edit Pengguna - ' . $user->name)

@section('content')
<div class="w-full space-y-6">

    <!-- Page Header -->
    <div class="flex items-center justify-between border-b border-slate-200 dark:border-gray-800 pb-5">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center space-x-3">
                <div class="p-2 bg-gradient-to-tr from-indigo-600 to-purple-600 rounded-lg text-white shadow-md">
                    <i class="fa-solid fa-user-pen text-base"></i>
                </div>
                <span>Edit Pengguna</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-gray-400 mt-1">
                Perbarui data akun, hak akses peran, atau reset kata sandi pengguna.
            </p>
        </div>

        <a href="{{ route('users.index') }}" 
           class="px-3.5 py-2 bg-slate-200/80 hover:bg-slate-300/80 dark:bg-gray-800 dark:hover:bg-gray-700 text-slate-700 dark:text-gray-300 font-semibold rounded-lg text-xs transition flex items-center space-x-2">
            <i class="fa-solid fa-arrow-left text-xs"></i>
            <span>Kembali</span>
        </a>
    </div>

    <!-- Edit Form Card -->
    <div class="card-dark rounded-xl border border-slate-200/90 dark:border-gray-800 p-6 sm:p-8 shadow-sm">
        <form action="{{ route('users.update', $user->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Section 1: Identitas Akun -->
            <div class="space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-white border-b border-slate-200 dark:border-gray-800 pb-2 flex items-center space-x-2">
                    <i class="fa-solid fa-id-card text-indigo-500"></i>
                    <span>Informasi Identitas</span>
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Nama Lengkap -->
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
            </div>

            <!-- Section 2: Peran & Status -->
            <div class="space-y-4 pt-2">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-white border-b border-slate-200 dark:border-gray-800 pb-2 flex items-center space-x-2">
                    <i class="fa-solid fa-user-shield text-indigo-500"></i>
                    <span>Hak Akses & Status</span>
                </h3>

                @if($user->id === auth()->id())
                    <div class="p-3 rounded-lg bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/80 text-amber-800 dark:text-amber-300 text-xs flex items-center space-x-2">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span>Anda sedang mengedit akun Anda sendiri. Peran dan status akun tidak dapat diubah dari sini.</span>
                    </div>
                    <input type="hidden" name="role" value="{{ $user->role }}">
                    <input type="hidden" name="status" value="{{ $user->status }}">
                @else
                    <!-- Role Selection Cards -->
                    <div class="space-y-2">
                        <label class="block text-xs font-semibold text-slate-700 dark:text-gray-300">
                            Pilih Peran Akun <span class="text-rose-500">*</span>
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" x-data="{ selectedRole: '{{ old('role', $user->role) }}' }">
                            <!-- Role Operator -->
                            <label class="relative flex flex-col p-4 rounded-xl border cursor-pointer transition select-none"
                                   :class="selectedRole === 'operator' ? 'border-indigo-600 bg-indigo-50/50 dark:bg-indigo-950/30 shadow-sm' : 'border-slate-200 dark:border-gray-800 bg-white/50 dark:bg-gray-900/40 hover:border-slate-300 dark:hover:border-gray-700'">
                                <div class="flex items-center justify-between mb-1.5">
                                    <div class="flex items-center space-x-2">
                                        <span class="w-7 h-7 rounded-lg bg-blue-100 dark:bg-blue-950/80 text-blue-600 dark:text-blue-400 flex items-center justify-center text-xs">
                                            <i class="fa-solid fa-user-gear"></i>
                                        </span>
                                        <span class="text-xs font-bold text-slate-900 dark:text-white">Operator</span>
                                    </div>
                                    <input type="radio" name="role" value="operator" x-model="selectedRole" class="text-indigo-600 focus:ring-indigo-500">
                                </div>
                                <p class="text-[11px] text-slate-500 dark:text-gray-400 leading-relaxed">
                                    Dapat mengelola Project Campaign, media pool, dan memantau antrean posting.
                                </p>
                            </label>

                            <!-- Role Admin -->
                            <label class="relative flex flex-col p-4 rounded-xl border cursor-pointer transition select-none"
                                   :class="selectedRole === 'admin' ? 'border-indigo-600 bg-indigo-50/50 dark:bg-indigo-950/30 shadow-sm' : 'border-slate-200 dark:border-gray-800 bg-white/50 dark:bg-gray-900/40 hover:border-slate-300 dark:hover:border-gray-700'">
                                <div class="flex items-center justify-between mb-1.5">
                                    <div class="flex items-center space-x-2">
                                        <span class="w-7 h-7 rounded-lg bg-indigo-100 dark:bg-indigo-950/80 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xs">
                                            <i class="fa-solid fa-shield-halved"></i>
                                        </span>
                                        <span class="text-xs font-bold text-slate-900 dark:text-white">Administrator</span>
                                    </div>
                                    <input type="radio" name="role" value="admin" x-model="selectedRole" class="text-indigo-600 focus:ring-indigo-500">
                                </div>
                                <p class="text-[11px] text-slate-500 dark:text-gray-400 leading-relaxed">
                                    Akses menyeluruh: manajemen user, konfigurasi Meta App API, campaign, dan penjadwalan.
                                </p>
                            </label>
                        </div>
                        @error('role')
                            <p class="text-[11px] text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Status Selection -->
                    <div class="space-y-1.5 pt-2">
                        <label for="status" class="block text-xs font-semibold text-slate-700 dark:text-gray-300">
                            Status Akun <span class="text-rose-500">*</span>
                        </label>
                        <select name="status" 
                                id="status" 
                                required 
                                class="block w-full px-3.5 py-2.5 text-xs rounded-lg border border-slate-300 dark:border-gray-700 bg-white/80 dark:bg-gray-900/60 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="active" {{ old('status', $user->status) === 'active' ? 'selected' : '' }}>Aktif (Dapat Login)</option>
                            <option value="inactive" {{ old('status', $user->status) === 'inactive' ? 'selected' : '' }}>Nonaktif (Diblokir)</option>
                        </select>
                        @error('status')
                            <p class="text-[11px] text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>
                @endif
            </div>

            <!-- Section 3: Reset Kata Sandi -->
            <div class="space-y-4 pt-2">
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-white border-b border-slate-200 dark:border-gray-800 pb-2 flex items-center space-x-2">
                        <i class="fa-solid fa-lock text-indigo-500"></i>
                        <span>Ubah / Reset Kata Sandi</span>
                    </h3>
                    <p class="text-[11px] text-slate-500 dark:text-gray-400 mt-1">
                        Kosongkan bagian ini jika tidak ingin mengubah kata sandi pengguna.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Kata Sandi Baru -->
                    <div class="space-y-1.5">
                        <label for="password" class="block text-xs font-semibold text-slate-700 dark:text-gray-300">
                            Kata Sandi Baru
                        </label>
                        <input type="password" 
                               name="password" 
                               id="password" 
                               autocomplete="new-password"
                               placeholder="Kosongkan jika tidak diubah"
                               class="block w-full px-3.5 py-2.5 text-xs rounded-lg border border-slate-300 dark:border-gray-700 bg-white/80 dark:bg-gray-900/60 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @error('password')
                            <p class="text-[11px] text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Konfirmasi Kata Sandi -->
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

            <!-- Action Buttons -->
            <div class="pt-4 border-t border-slate-200 dark:border-gray-800 flex items-center justify-end space-x-3">
                <a href="{{ route('users.index') }}" 
                   class="px-4 py-2.5 bg-slate-200/80 hover:bg-slate-300/80 dark:bg-gray-800 dark:hover:bg-gray-700 text-slate-700 dark:text-gray-300 font-semibold rounded-lg text-xs transition">
                    Batal
                </a>
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
