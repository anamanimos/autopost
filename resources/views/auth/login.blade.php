@extends('layouts.app')

@section('title', 'Masuk ke Sistem')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-xl w-full space-y-7">
        
        <!-- Top Branding Header -->
        <div class="text-center space-y-2.5">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl shadow-xl shadow-indigo-500/30 mb-2 transform hover:scale-105 transition duration-200 overflow-hidden border border-slate-200/60 dark:border-gray-800">
                <img src="{{ asset('favicon.png') }}" alt="Meta Scheduler Logo" class="w-full h-full object-cover">
            </div>
            <h2 class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                Masuk ke Sistem
            </h2>
            <p class="text-sm text-slate-500 dark:text-gray-400 max-w-sm mx-auto">
                Meta Content Scheduler &bull; Official Graph API (IG & FB)
            </p>
        </div>

        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-800 dark:text-emerald-300 text-sm flex items-center justify-between backdrop-blur-md">
                <div class="flex items-center space-x-2.5">
                    <i class="fa-solid fa-circle-check text-emerald-600 dark:text-emerald-400 text-base flex-shrink-0"></i>
                    <span class="font-medium">{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-600 dark:text-emerald-400 hover:opacity-75"><i class="fa-solid fa-xmark"></i></button>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-800 dark:text-rose-300 text-sm flex items-center justify-between backdrop-blur-md">
                <div class="flex items-center space-x-2.5">
                    <i class="fa-solid fa-circle-exclamation text-rose-600 dark:text-rose-400 text-base flex-shrink-0"></i>
                    <span class="font-medium">{{ session('error') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-rose-600 dark:text-rose-400 hover:opacity-75"><i class="fa-solid fa-xmark"></i></button>
            </div>
        @endif

        @if(session('info'))
            <div class="p-4 rounded-xl bg-sky-500/10 border border-sky-500/30 text-sky-800 dark:text-sky-300 text-sm flex items-center justify-between backdrop-blur-md">
                <div class="flex items-center space-x-2.5">
                    <i class="fa-solid fa-circle-info text-sky-600 dark:text-sky-400 text-base flex-shrink-0"></i>
                    <span class="font-medium">{{ session('info') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-sky-600 dark:text-sky-400 hover:opacity-75"><i class="fa-solid fa-xmark"></i></button>
            </div>
        @endif

        <!-- Login Card -->
        <div class="card-dark rounded-2xl border border-slate-200/90 dark:border-gray-800 p-8 sm:p-10 shadow-2xl relative overflow-hidden backdrop-blur-2xl">
            
            <!-- Single Sign-On (SSO) ERP Damai Jaya Button -->
            <div class="mb-6">
                <a href="{{ route('sso.redirect') }}" 
                   class="w-full py-3.5 px-5 rounded-xl bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 hover:opacity-95 text-white text-sm font-bold shadow-lg shadow-indigo-500/25 transition duration-200 flex items-center justify-center space-x-3 group">
                    <div class="w-7 h-7 rounded-lg bg-white/20 flex items-center justify-center text-xs group-hover:scale-110 transition">
                        <i class="fa-solid fa-building-shield"></i>
                    </div>
                    <span>Masuk via SSO Damai Jaya (ERP)</span>
                </a>

                <div class="relative flex py-5 items-center">
                    <div class="flex-grow border-t border-slate-200 dark:border-gray-800"></div>
                    <span class="flex-shrink mx-4 text-xs uppercase tracking-wider text-slate-400 dark:text-gray-500 font-bold">atau masuk dengan email</span>
                    <div class="flex-grow border-t border-slate-200 dark:border-gray-800"></div>
                </div>
            </div>

            <form action="{{ route('login') }}" method="POST" class="space-y-5" x-data="{ showPassword: false }">
                @csrf

                <!-- Email Input -->
                <div class="space-y-2">
                    <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-gray-300">
                        Alamat Email
                    </label>
                    <div class="relative rounded-xl shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 dark:text-gray-500">
                            <i class="fa-solid fa-envelope text-sm"></i>
                        </div>
                        <input type="email" 
                               name="email" 
                               id="email" 
                               value="{{ old('email') }}" 
                               required 
                               autofocus
                               autocomplete="email"
                               placeholder="admin@sosmedauto.com"
                               class="block w-full pl-11 pr-4 py-3 text-sm rounded-xl border border-slate-300 dark:border-gray-700 bg-white/80 dark:bg-gray-900/60 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    </div>
                    @error('email')
                        <p class="text-xs text-rose-500 mt-1 flex items-center space-x-1.5">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>

                <!-- Password Input -->
                <div class="space-y-2">
                    <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-gray-300">
                        Kata Sandi
                    </label>
                    <div class="relative rounded-xl shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 dark:text-gray-500">
                            <i class="fa-solid fa-lock text-sm"></i>
                        </div>
                        <input :type="showPassword ? 'text' : 'password'" 
                               name="password" 
                               id="password" 
                               required 
                               autocomplete="current-password"
                               placeholder="••••••••"
                               class="block w-full pl-11 pr-11 py-3 text-sm rounded-xl border border-slate-300 dark:border-gray-700 bg-white/80 dark:bg-gray-900/60 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        <button type="button" 
                                @click="showPassword = !showPassword" 
                                class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 dark:text-gray-500 hover:text-slate-600 dark:hover:text-gray-300 focus:outline-none">
                            <i class="fa-solid text-sm" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                    @error('password')
                        <p class="text-xs text-rose-500 mt-1 flex items-center space-x-1.5">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>

                <!-- Remember Me & Forgot Hint -->
                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center space-x-2.5 cursor-pointer select-none">
                        <input type="checkbox" 
                               name="remember" 
                               id="remember"
                               class="w-4 h-4 rounded border-slate-300 dark:border-gray-700 text-indigo-600 focus:ring-indigo-500 bg-white dark:bg-gray-900">
                        <span class="text-xs sm:text-sm text-slate-600 dark:text-gray-400 font-medium">Ingat Saya di Perangkat Ini</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button type="submit" 
                            class="w-full py-3.5 px-5 rounded-xl bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 hover:opacity-95 text-white text-sm font-bold shadow-lg shadow-indigo-600/30 transition duration-200 flex items-center justify-center space-x-2.5">
                        <i class="fa-solid fa-right-to-bracket text-sm"></i>
                        <span>Masuk ke Dashboard</span>
                    </button>
                </div>
            </form>

            <!-- Default Credentials Tip -->
            <div class="mt-8 pt-6 border-t border-slate-200/80 dark:border-gray-800 text-center">
                <p class="text-xs text-slate-500 dark:text-gray-400">
                    Akun default Administrator:<br>
                    <span class="font-mono text-slate-800 dark:text-gray-200 font-semibold">admin@sosmedauto.com</span> &bull; 
                    <span class="font-mono text-slate-800 dark:text-gray-200 font-semibold">password</span>
                </p>
            </div>
        </div>

    </div>
</div>
@endsection
