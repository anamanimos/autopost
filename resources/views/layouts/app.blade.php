<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Meta Content Scheduler') - Graph API</title>
    
    <!-- Favicon & App Icons -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}?v=1">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}?v=1">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}?v=1">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=1">

    <!-- Theme Initializer (Run immediately to prevent FOUC) -->
    <script>
        if (localStorage.theme === 'light' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: light)').matches)) {
            document.documentElement.classList.remove('dark');
        } else {
            document.documentElement.classList.add('dark');
        }
    </script>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        slate: {
                            850: '#141c2e',
                            950: '#080d1a',
                        }
                    },
                    boxShadow: {
                        'glass': '0 20px 40px -15px rgba(0, 0, 0, 0.15)',
                        'glass-dark': '0 25px 50px -12px rgba(0, 0, 0, 0.5)',
                        'glow': '0 0 25px -5px rgba(99, 102, 241, 0.4)',
                    }
                }
            }
        };
    </script>

    <!-- FontAwesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap');

        :root {
            --bg-page: #f8fafc;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --glass-bg: rgba(255, 255, 255, 0.85);
            --glass-bg-hover: rgba(255, 255, 255, 0.98);
            --glass-border: rgba(226, 232, 240, 0.9);
            --glass-border-hover: rgba(99, 102, 241, 0.4);
            --card-subtle: rgba(241, 245, 249, 0.8);
            --table-header: rgba(241, 245, 249, 0.95);
            --table-row-hover: rgba(241, 245, 249, 0.65);
            --shadow-glass: 0 4px 20px -2px rgba(0, 0, 0, 0.05), 0 2px 6px -1px rgba(0, 0, 0, 0.02);
        }

        html.dark {
            --bg-page: #080d1a;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --glass-bg: rgba(15, 23, 42, 0.65);
            --glass-bg-hover: rgba(15, 23, 42, 0.8);
            --glass-border: rgba(255, 255, 255, 0.08);
            --glass-border-hover: rgba(99, 102, 241, 0.5);
            --card-subtle: rgba(30, 41, 59, 0.5);
            --table-header: rgba(15, 23, 42, 0.85);
            --table-row-hover: rgba(30, 41, 59, 0.4);
            --shadow-glass: 0 20px 40px -15px rgba(0, 0, 0, 0.6);
        }

        body {
            background-color: var(--bg-page);
            color: var(--text-main);
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Strictly cap bubbly rounded corners (Minimalist & Non-bubbly) */
        .rounded-3xl, .rounded-2xl {
            border-radius: 0.75rem !important; /* 12px */
        }
        .rounded-xl {
            border-radius: 0.625rem !important; /* 10px */
        }
        .rounded-lg {
            border-radius: 0.5rem !important; /* 8px */
        }
        .rounded-md {
            border-radius: 0.375rem !important; /* 6px */
        }

        /* Glassmorphic Cards & Surfaces */
        .glass-card, .card-dark {
            background: var(--glass-bg) !important;
            backdrop-filter: blur(18px) !important;
            -webkit-backdrop-filter: blur(18px) !important;
            border: 1px solid var(--glass-border) !important;
            box-shadow: var(--shadow-glass) !important;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .card-dark:hover, .glass-card:hover {
            border-color: var(--glass-border-hover) !important;
        }

        .card-dark-subtle {
            background: var(--card-subtle) !important;
            backdrop-filter: blur(12px) !important;
            -webkit-backdrop-filter: blur(12px) !important;
            border: 1px solid var(--glass-border) !important;
        }

        /* Glass Navigation */
        .glass-nav {
            background: var(--glass-bg) !important;
            backdrop-filter: blur(20px) !important;
            -webkit-backdrop-filter: blur(20px) !important;
            border-bottom: 1px solid var(--glass-border) !important;
        }

        /* Glass Tables */
        table thead {
            background: var(--table-header) !important;
        }
        table tbody tr:hover {
            background: var(--table-row-hover) !important;
        }

        /* Form Inputs & Selects Adaptation */
        html:not(.dark) input[type="text"], 
        html:not(.dark) input[type="password"], 
        html:not(.dark) input[type="time"], 
        html:not(.dark) input[type="date"], 
        html:not(.dark) select, 
        html:not(.dark) textarea {
            background-color: #ffffff !important;
            border-color: #cbd5e1 !important;
            color: #0f172a !important;
        }
        html:not(.dark) input::placeholder,
        html:not(.dark) textarea::placeholder {
            color: #94a3b8 !important;
        }
        html:not(.dark) input:focus, html:not(.dark) select:focus, html:not(.dark) textarea:focus {
            border-color: #6366f1 !important;
            background-color: #ffffff !important;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15) !important;
        }

        /* Light Mode Adaptive Typography & Contrast */
        html:not(.dark) h1.text-white,
        html:not(.dark) h2.text-white,
        html:not(.dark) h3.text-white,
        html:not(.dark) h4.text-white,
        html:not(.dark) .card-dark h1,
        html:not(.dark) .card-dark h2,
        html:not(.dark) .card-dark h3,
        html:not(.dark) .card-dark h4,
        html:not(.dark) a.text-white,
        html:not(.dark) span.font-bold.text-white,
        html:not(.dark) strong.text-white,
        html:not(.dark) .text-white:not([class*="bg-indigo"]):not([class*="bg-blue"]):not([class*="bg-gradient"]):not([class*="bg-emerald"]):not([class*="bg-rose"]):not([class*="bg-amber"]):not([class*="bg-purple"]) {
            color: #0f172a !important;
        }

        /* Force crisp white text on colored solid action buttons & badges */
        html:not(.dark) button.bg-indigo-600,
        html:not(.dark) button.bg-indigo-600 *,
        html:not(.dark) a.bg-indigo-600,
        html:not(.dark) a.bg-indigo-600 *,
        html:not(.dark) button.bg-blue-600,
        html:not(.dark) button.bg-blue-600 *,
        html:not(.dark) a.bg-blue-600,
        html:not(.dark) a.bg-blue-600 *,
        html:not(.dark) button.bg-emerald-600,
        html:not(.dark) button.bg-emerald-600 *,
        html:not(.dark) a.bg-emerald-600,
        html:not(.dark) a.bg-emerald-600 *,
        html:not(.dark) button.bg-rose-600,
        html:not(.dark) button.bg-rose-600 *,
        html:not(.dark) a.bg-rose-600,
        html:not(.dark) a.bg-rose-600 *,
        html:not(.dark) button.bg-amber-600,
        html:not(.dark) button.bg-amber-600 *,
        html:not(.dark) a.bg-amber-600,
        html:not(.dark) a.bg-amber-600 *,
        html:not(.dark) button.bg-purple-600,
        html:not(.dark) button.bg-purple-600 *,
        html:not(.dark) a.bg-purple-600,
        html:not(.dark) a.bg-purple-600 *,
        html:not(.dark) button[class*="bg-gradient"],
        html:not(.dark) button[class*="bg-gradient"] *,
        html:not(.dark) a[class*="bg-gradient"],
        html:not(.dark) a[class*="bg-gradient"] *,
        html:not(.dark) [class*="bg-gradient-to"][class*="from-indigo"] *,
        html:not(.dark) [class*="bg-gradient-to"][class*="from-purple"] *,
        html:not(.dark) .btn-gradient,
        html:not(.dark) .btn-gradient * {
            color: #ffffff !important;
        }

        /* Light Mode Gray Text Adjustments */
        html:not(.dark) .text-gray-200 {
            color: #1e293b !important; /* Slate 800 */
        }
        html:not(.dark) .text-gray-300 {
            color: #334155 !important; /* Slate 700 */
        }
        html:not(.dark) .text-gray-400 {
            color: #64748b !important; /* Slate 500 */
        }
        html:not(.dark) .text-gray-500 {
            color: #94a3b8 !important; /* Slate 400 */
        }

        /* Light Mode Sub-surfaces & Card Footers */
        html:not(.dark) .bg-gray-900\/90,
        html:not(.dark) .bg-gray-900\/80,
        html:not(.dark) .bg-gray-900\/60,
        html:not(.dark) .bg-gray-900\/40,
        html:not(.dark) .bg-gray-900 {
            background-color: rgba(241, 245, 249, 0.85) !important;
            color: #1e293b !important;
        }
        html:not(.dark) .bg-gray-950\/90,
        html:not(.dark) .bg-gray-950\/80,
        html:not(.dark) .bg-gray-950\/60,
        html:not(.dark) .bg-gray-950 {
            background-color: #f1f5f9 !important;
            color: #1e293b !important;
        }
        html:not(.dark) .bg-gray-800 {
            background-color: #e2e8f0 !important;
            color: #1e293b !important;
            border-color: #cbd5e1 !important;
        }
        html:not(.dark) .bg-gray-800:hover {
            background-color: #cbd5e1 !important;
            color: #0f172a !important;
        }

        /* Light Mode Borders */
        html:not(.dark) .border-gray-800,
        html:not(.dark) .border-gray-800\/80,
        html:not(.dark) .border-gray-800\/60,
        html:not(.dark) .border-gray-700 {
            border-color: rgba(226, 232, 240, 0.9) !important;
        }
        html:not(.dark) .divide-gray-800\/80 > :not([hidden]) ~ :not([hidden]),
        html:not(.dark) .divide-gray-800\/60 > :not([hidden]) ~ :not([hidden]) {
            border-color: rgba(226, 232, 240, 0.9) !important;
        }

        /* Light Mode Badge & Banner Refinements */
        html:not(.dark) .bg-pink-900\/70,
        html:not(.dark) .bg-pink-950 {
            background-color: #fdf2f8 !important;
            border-color: #fbcfe8 !important;
            color: #be185d !important;
        }
        html:not(.dark) .bg-blue-950,
        html:not(.dark) .bg-blue-950\/80 {
            background-color: #eff6ff !important;
            border-color: #bfdbfe !important;
            color: #1d4ed8 !important;
        }
        html:not(.dark) .bg-indigo-950,
        html:not(.dark) .bg-indigo-950\/80,
        html:not(.dark) .bg-indigo-950\/60,
        html:not(.dark) .bg-indigo-950\/40,
        html:not(.dark) .bg-indigo-950\/30,
        html:not(.dark) .bg-indigo-950\/20 {
            background-color: #eef2ff !important;
            border-color: #c7d2fe !important;
            color: #4338ca !important;
        }
        html:not(.dark) .bg-amber-950,
        html:not(.dark) .bg-amber-950\/80,
        html:not(.dark) .bg-amber-950\/60,
        html:not(.dark) .bg-amber-950\/50,
        html:not(.dark) .bg-amber-950\/40 {
            background-color: #fffbeb !important;
            border-color: #fde68a !important;
            color: #b45309 !important;
        }
        html:not(.dark) .bg-emerald-950,
        html:not(.dark) .bg-emerald-950\/80,
        html:not(.dark) .bg-emerald-950\/60,
        html:not(.dark) .bg-emerald-950\/40,
        html:not(.dark) .bg-emerald-950\/25 {
            background-color: #ecfdf5 !important;
            border-color: #a7f3d0 !important;
            color: #047857 !important;
        }
        html:not(.dark) .bg-rose-950,
        html:not(.dark) .bg-rose-950\/80,
        html:not(.dark) .bg-rose-950\/60,
        html:not(.dark) .bg-rose-950\/40,
        html:not(.dark) .bg-rose-950\/30 {
            background-color: #fff1f2 !important;
            border-color: #fecdd3 !important;
            color: #be123c !important;
        }
        html:not(.dark) .bg-purple-950,
        html:not(.dark) .bg-purple-950\/80 {
            background-color: #faf5ff !important;
            border-color: #e9d5ff !important;
            color: #7e22ce !important;
        }
        html:not(.dark) .bg-orange-950,
        html:not(.dark) .bg-orange-950\/80 {
            background-color: #fff7ed !important;
            border-color: #fed7aa !important;
            color: #c2410c !important;
        }

        /* SweetAlert Adaptive Glass Theme */
        .swal2-popup-dark {
            background: var(--glass-bg) !important;
            backdrop-filter: blur(24px) !important;
            -webkit-backdrop-filter: blur(24px) !important;
            color: var(--text-main) !important;
            border: 1px solid var(--glass-border) !important;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.2) !important;
            border-radius: 0.75rem !important;
        }
        html.dark .swal2-title-dark { color: #ffffff !important; }
        html:not(.dark) .swal2-title-dark { color: #0f172a !important; }
        html.dark .swal2-html-dark { color: #94a3b8 !important; }
        html:not(.dark) .swal2-html-dark { color: #475569 !important; }
        html:not(.dark) .swal2-popup-dark label.bg-gray-900 {
            background-color: #f8fafc !important;
            border-color: #cbd5e1 !important;
        }
        html:not(.dark) .swal2-popup-dark label.bg-gray-900 strong {
            color: #0f172a !important;
        }
    </style>
</head>
<body class="min-h-screen relative antialiased selection:bg-indigo-500 selection:text-white flex flex-col" x-data="{ sidebarOpen: false }">

    <!-- Ambient Glowing Background Spheres for Glassmorphic Depth -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none -z-10">
        <div class="absolute -top-32 -left-32 w-96 h-96 rounded-full bg-indigo-500/10 dark:bg-indigo-600/15 blur-3xl"></div>
        <div class="absolute top-1/4 -right-32 w-96 h-96 rounded-full bg-purple-500/10 dark:bg-purple-600/15 blur-3xl"></div>
        <div class="absolute -bottom-32 left-1/3 w-96 h-96 rounded-full bg-pink-500/10 dark:bg-pink-600/10 blur-3xl"></div>
    </div>

    @auth
    <!-- Mobile Backdrop Overlay -->
    <div x-show="sidebarOpen" 
         @click="sidebarOpen = false" 
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-sm lg:hidden" 
         style="display: none;"></div>

    <!-- Left Sidebar (Desktop Fixed & Mobile Drawer) -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
           class="fixed inset-y-0 left-0 z-50 w-64 glass-card border-r border-slate-200/90 dark:border-gray-800 transition-transform duration-300 ease-in-out lg:translate-x-0 flex flex-col bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl shadow-xl lg:shadow-none">
        
        <!-- Top Branding Header -->
        <div class="h-16 flex items-center justify-between px-5 border-b border-slate-200/80 dark:border-gray-800/80 flex-shrink-0">
            <a href="{{ route('projects.index') }}" class="flex items-center space-x-3 group min-w-0">
                <img src="{{ asset('favicon.png') }}" alt="Meta Scheduler Logo" class="w-9 h-9 rounded-xl object-cover shadow-md shadow-indigo-500/25 group-hover:scale-105 transition duration-200 flex-shrink-0">
                <div class="min-w-0">
                    <span class="font-bold text-sm text-slate-900 dark:text-white tracking-tight block truncate">Meta Scheduler</span>
                    <span class="text-[9px] text-indigo-600 dark:text-indigo-400 font-bold tracking-wider uppercase block truncate">Graph API v22.0</span>
                </div>
            </a>
            <!-- Mobile Close Button -->
            <button @click="sidebarOpen = false" class="lg:hidden text-slate-400 hover:text-slate-600 dark:hover:text-white p-1.5 rounded-lg transition hover:bg-slate-100 dark:hover:bg-gray-800">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>

        <!-- Navigation Links -->
        <div class="px-3 py-5 space-y-6 overflow-y-auto flex-1">
            <!-- Group 1: Menu Utama -->
            <div class="space-y-1">
                <div class="px-3 pb-1 text-[10px] font-bold text-slate-400 dark:text-gray-500 uppercase tracking-wider">
                    Menu Utama
                </div>

                <a href="{{ route('projects.index') }}" 
                   class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-xs font-semibold transition group {{ request()->routeIs('projects.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
                    <div class="w-5 text-center">
                        <i class="fa-solid fa-layer-group text-sm {{ request()->routeIs('projects.*') ? 'text-white' : 'text-slate-400 dark:text-gray-400 group-hover:text-indigo-600 dark:group-hover:text-indigo-400' }}"></i>
                    </div>
                    <span>Campaigns</span>
                </a>

                <a href="{{ route('schedules.index') }}" 
                   class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-xs font-semibold transition group {{ request()->routeIs('schedules.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
                    <div class="w-5 text-center">
                        <i class="fa-solid fa-calendar-check text-sm {{ request()->routeIs('schedules.*') ? 'text-white' : 'text-slate-400 dark:text-gray-400 group-hover:text-indigo-600 dark:group-hover:text-indigo-400' }}"></i>
                    </div>
                    <span>Antrean Posting</span>
                </a>
            </div>

            <!-- Group 2: Integrasi -->
            <div class="space-y-1">
                <div class="px-3 pb-1 text-[10px] font-bold text-slate-400 dark:text-gray-500 uppercase tracking-wider">
                    Integrasi Meta
                </div>

                <a href="{{ route('meta.index') }}" 
                   class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-xs font-semibold transition group {{ request()->routeIs('meta.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
                    <div class="w-5 text-center">
                        <i class="fa-solid fa-sliders text-sm {{ request()->routeIs('meta.*') ? 'text-white' : 'text-slate-400 dark:text-gray-400 group-hover:text-indigo-600 dark:group-hover:text-indigo-400' }}"></i>
                    </div>
                    <span>Integrasi Meta API</span>
                </a>
            </div>

            <!-- Group 3: Administrasi (Admin Only) -->
            @if(auth()->user()->isAdmin())
            <div class="space-y-1">
                <div class="px-3 pb-1 text-[10px] font-bold text-slate-400 dark:text-gray-500 uppercase tracking-wider">
                    Administrasi
                </div>

                <a href="{{ route('users.index') }}" 
                   class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-xs font-semibold transition group {{ request()->routeIs('users.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
                    <div class="w-5 text-center">
                        <i class="fa-solid fa-users text-sm {{ request()->routeIs('users.*') ? 'text-white' : 'text-slate-400 dark:text-gray-400 group-hover:text-indigo-600 dark:group-hover:text-indigo-400' }}"></i>
                    </div>
                    <span>Manajemen User</span>
                </a>
            </div>
            @endif

            <!-- Group 4: Pengaturan Akun -->
            <div class="space-y-1">
                <div class="px-3 pb-1 text-[10px] font-bold text-slate-400 dark:text-gray-500 uppercase tracking-wider">
                    Akun
                </div>

                <a href="{{ route('profile') }}" 
                   class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-xs font-semibold transition group {{ request()->routeIs('profile*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
                    <div class="w-5 text-center">
                        <i class="fa-solid fa-user-circle text-sm {{ request()->routeIs('profile*') ? 'text-white' : 'text-slate-400 dark:text-gray-400 group-hover:text-indigo-600 dark:group-hover:text-indigo-400' }}"></i>
                    </div>
                    <span>Profil Saya</span>
                </a>
            </div>
        </div>
    </aside>

    <!-- Content Wrapper for Authenticated Users (with left margin for sidebar) -->
    <div class="flex-1 lg:pl-64 flex flex-col min-h-screen">
        <!-- Topbar Header -->
        <header class="glass-nav sticky top-0 z-30 transition-colors duration-300">
            <div class="w-full px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    
                    <!-- Left: Mobile Menu Trigger & Context Title -->
                    <div class="flex items-center space-x-2.5">
                        <button @click="sidebarOpen = true" 
                                class="lg:hidden p-2 rounded-lg border border-slate-300/80 dark:border-white/10 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                                title="Buka Menu Sidebar">
                            <i class="fa-solid fa-bars text-sm"></i>
                        </button>

                        <a href="{{ route('projects.index') }}" class="lg:hidden flex items-center space-x-2">
                            <img src="{{ asset('favicon.png') }}" class="w-7 h-7 rounded-lg object-cover shadow-sm">
                            <span class="font-bold text-xs text-slate-900 dark:text-white tracking-tight">Meta Scheduler</span>
                        </a>
                        
                        <div class="hidden sm:flex items-center space-x-2 text-xs text-slate-500 dark:text-gray-400">
                            <i class="fa-brands fa-meta text-indigo-500"></i>
                            <span>/</span>
                            <span class="font-medium text-slate-800 dark:text-slate-200">
                                @if(request()->routeIs('projects.*'))
                                    Campaigns
                                @elseif(request()->routeIs('schedules.*'))
                                    Antrean Posting
                                @elseif(request()->routeIs('meta.*'))
                                    Integrasi Meta API
                                @elseif(request()->routeIs('users.*'))
                                    Manajemen User
                                @elseif(request()->routeIs('profile*'))
                                    Profil Saya
                                @else
                                    Dashboard
                                @endif
                            </span>
                        </div>
                    </div>

                    <!-- Right: Action Buttons (Meta API Status, Theme Toggle, User Profile) -->
                    <div class="flex items-center space-x-2.5">
                        <!-- Meta API Connection Status Indicator -->
                        <a href="{{ route('meta.index') }}" 
                           class="flex items-center space-x-2 px-2.5 py-1.5 rounded-lg border border-slate-200/90 dark:border-gray-800 bg-white/80 dark:bg-slate-800/60 hover:border-indigo-400 dark:hover:border-indigo-500/60 transition shadow-sm group"
                           title="Integrasi Meta Graph API — Status: Terhubung (Klik untuk Pengaturan)">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                            <span class="text-xs font-semibold text-slate-700 dark:text-gray-300 hidden sm:inline group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition">Meta API</span>
                            <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider px-1.5 py-0.5 rounded bg-emerald-500/10 border border-emerald-500/20">Online</span>
                        </a>

                        <!-- Theme Toggle -->
                        <button onclick="toggleTheme()" id="themeToggleBtn" 
                                class="w-9 h-9 rounded-lg flex items-center justify-center transition border shadow-sm cursor-pointer bg-white/80 hover:bg-white text-slate-700 border-slate-300/80 dark:bg-slate-800/60 dark:hover:bg-slate-800 dark:text-amber-300 dark:border-white/10"
                                title="Beralih Mode Gelap / Terang">
                            <i id="themeToggleIcon" class="fa-solid fa-moon text-xs"></i>
                        </button>

                        <!-- User Profile Dropdown -->
                        <div class="relative" x-data="{ userMenuOpen: false }">
                            <button @click="userMenuOpen = !userMenuOpen" 
                                    @click.away="userMenuOpen = false"
                                    class="flex items-center space-x-2 p-1.5 rounded-lg border border-slate-300/80 dark:border-white/10 bg-white/80 dark:bg-slate-800/60 hover:bg-white dark:hover:bg-slate-800 transition shadow-sm text-left">
                                <div class="w-6 h-6 rounded-md bg-gradient-to-tr from-indigo-500 to-purple-600 text-white font-bold text-[10px] flex items-center justify-center">
                                    {{ auth()->user()->initials }}
                                </div>
                                <div class="hidden xl:block">
                                    <div class="text-xs font-bold text-slate-900 dark:text-white leading-none truncate max-w-[100px]">
                                        {{ auth()->user()->name }}
                                    </div>
                                    <div class="text-[9px] text-slate-500 dark:text-gray-400 font-semibold uppercase tracking-wider mt-0.5">
                                        {{ auth()->user()->role }}
                                    </div>
                                </div>
                                <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 dark:text-gray-500"></i>
                            </button>

                            <!-- Dropdown Panel -->
                            <div x-show="userMenuOpen" 
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 class="absolute right-0 mt-2 w-56 rounded-xl card-dark border border-slate-200 dark:border-gray-800 shadow-xl py-2 z-50 divide-y divide-slate-200/80 dark:divide-gray-800/80"
                                 style="display: none;">
                                <div class="px-4 py-2.5">
                                    <p class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ auth()->user()->name }}</p>
                                    <p class="text-[11px] text-slate-500 dark:text-gray-400 truncate">{{ auth()->user()->email }}</p>
                                    <span class="inline-block mt-1 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase {{ auth()->user()->isAdmin() ? 'bg-indigo-100 dark:bg-indigo-950/80 text-indigo-700 dark:text-indigo-300' : 'bg-blue-100 dark:bg-blue-950/80 text-blue-700 dark:text-blue-300' }}">
                                        {{ auth()->user()->isAdmin() ? 'Administrator' : 'Operator' }}
                                    </span>
                                </div>

                                <div class="py-1">
                                    <a href="{{ route('profile') }}" 
                                       class="flex items-center space-x-2.5 px-4 py-2 text-xs text-slate-700 dark:text-gray-300 hover:bg-slate-100 dark:hover:bg-slate-800/60 transition">
                                        <i class="fa-solid fa-user-circle text-indigo-500 text-xs w-4 text-center"></i>
                                        <span>Profil Saya</span>
                                    </a>

                                    @if(auth()->user()->isAdmin())
                                    <a href="{{ route('users.index') }}" 
                                       class="flex items-center space-x-2.5 px-4 py-2 text-xs text-slate-700 dark:text-gray-300 hover:bg-slate-100 dark:hover:bg-slate-800/60 transition">
                                        <i class="fa-solid fa-users text-purple-500 text-xs w-4 text-center"></i>
                                        <span>Manajemen User</span>
                                    </a>
                                    @endif
                                </div>

                                <div class="py-1">
                                    <form action="{{ route('logout') }}" method="POST">
                                        @csrf
                                        <button type="submit" 
                                                class="w-full flex items-center space-x-2.5 px-4 py-2 text-xs text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition">
                                            <i class="fa-solid fa-right-from-bracket text-xs w-4 text-center"></i>
                                            <span>Keluar (Logout)</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content Area (100% Full Width with Safe Bottom Padding for Mobile Navbar) -->
        <main class="flex-grow w-full px-3.5 sm:px-6 lg:px-8 py-4 sm:py-6 pb-28 lg:pb-8">
            @if(session('success'))
                <div class="mb-6 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-800 dark:text-emerald-300 text-xs flex items-center justify-between backdrop-blur-md">
                    <div class="flex items-center space-x-2">
                        <i class="fa-solid fa-circle-check text-emerald-600 dark:text-emerald-400 text-sm"></i>
                        <span class="font-semibold">{{ session('success') }}</span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-emerald-600 dark:text-emerald-400 hover:opacity-75"><i class="fa-solid fa-xmark"></i></button>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-800 dark:text-rose-300 text-xs flex items-center justify-between backdrop-blur-md">
                    <div class="flex items-center space-x-2">
                        <i class="fa-solid fa-circle-exclamation text-rose-600 dark:text-rose-400 text-sm"></i>
                        <span class="font-semibold">{{ session('error') }}</span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-rose-600 dark:text-rose-400 hover:opacity-75"><i class="fa-solid fa-xmark"></i></button>
                </div>
            @endif

            @if(session('info'))
                <div class="mb-6 p-4 rounded-xl bg-sky-500/10 border border-sky-500/30 text-sky-800 dark:text-sky-300 text-xs flex items-center justify-between backdrop-blur-md">
                    <div class="flex items-center space-x-2">
                        <i class="fa-solid fa-circle-info text-sky-600 dark:text-sky-400 text-sm"></i>
                        <span class="font-semibold">{{ session('info') }}</span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-sky-600 dark:text-sky-400 hover:opacity-75"><i class="fa-solid fa-xmark"></i></button>
                </div>
            @endif

            @yield('content')
        </main>

        <!-- Global Footer (Desktop visible, extra spacing on mobile) -->
        <footer class="glass-nav border-t py-4 text-center text-xs text-slate-500 dark:text-slate-400 hidden lg:block">
            <p>&copy; {{ date('Y') }} Meta Content Scheduler — Official Meta Graph API (Instagram & Facebook Page)</p>
        </footer>

        <!-- Mobile Bottom Navigation Bar (Fixed at bottom for mobile screens < lg) -->
        <nav class="fixed bottom-0 inset-x-0 z-40 lg:hidden bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl border-t border-slate-200/90 dark:border-gray-800/90 shadow-[0_-4px_20px_rgba(0,0,0,0.06)] dark:shadow-[0_-4px_20px_rgba(0,0,0,0.35)] transition-colors duration-300">
            <div class="grid {{ auth()->user()->isAdmin() ? 'grid-cols-5' : 'grid-cols-4' }} h-16 max-w-lg mx-auto px-1">
                <!-- Tab 1: Campaigns -->
                <a href="{{ route('projects.index') }}" 
                   class="flex flex-col items-center justify-center py-1 group transition relative {{ request()->routeIs('projects.*') ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-slate-500 dark:text-gray-400 hover:text-slate-800 dark:hover:text-slate-200' }}">
                    <div class="relative">
                        <i class="fa-solid fa-layer-group text-base transition-transform group-active:scale-90"></i>
                        @if(request()->routeIs('projects.*'))
                            <span class="absolute -top-1 -right-1 w-1.5 h-1.5 rounded-full bg-indigo-600 dark:bg-indigo-400"></span>
                        @endif
                    </div>
                    <span class="text-[10px] mt-1 font-medium tracking-tight">Campaign</span>
                </a>

                <!-- Tab 2: Antrean Posting -->
                <a href="{{ route('schedules.index') }}" 
                   class="flex flex-col items-center justify-center py-1 group transition relative {{ request()->routeIs('schedules.*') ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-slate-500 dark:text-gray-400 hover:text-slate-800 dark:hover:text-slate-200' }}">
                    <div class="relative">
                        <i class="fa-solid fa-calendar-check text-base transition-transform group-active:scale-90"></i>
                        @if(request()->routeIs('schedules.*'))
                            <span class="absolute -top-1 -right-1 w-1.5 h-1.5 rounded-full bg-indigo-600 dark:bg-indigo-400"></span>
                        @endif
                    </div>
                    <span class="text-[10px] mt-1 font-medium tracking-tight">Antrean</span>
                </a>

                <!-- Tab 3: Integrasi Meta API -->
                <a href="{{ route('meta.index') }}" 
                   class="flex flex-col items-center justify-center py-1 group transition relative {{ request()->routeIs('meta.*') ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-slate-500 dark:text-gray-400 hover:text-slate-800 dark:hover:text-slate-200' }}">
                    <div class="relative">
                        <i class="fa-brands fa-meta text-base transition-transform group-active:scale-90"></i>
                        @if(request()->routeIs('meta.*'))
                            <span class="absolute -top-1 -right-1 w-1.5 h-1.5 rounded-full bg-indigo-600 dark:bg-indigo-400"></span>
                        @endif
                    </div>
                    <span class="text-[10px] mt-1 font-medium tracking-tight">Meta API</span>
                </a>

                <!-- Tab 4: Manajemen User (Admin Only) -->
                @if(auth()->user()->isAdmin())
                <a href="{{ route('users.index') }}" 
                   class="flex flex-col items-center justify-center py-1 group transition relative {{ request()->routeIs('users.*') ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-slate-500 dark:text-gray-400 hover:text-slate-800 dark:hover:text-slate-200' }}">
                    <div class="relative">
                        <i class="fa-solid fa-users text-base transition-transform group-active:scale-90"></i>
                        @if(request()->routeIs('users.*'))
                            <span class="absolute -top-1 -right-1 w-1.5 h-1.5 rounded-full bg-indigo-600 dark:bg-indigo-400"></span>
                        @endif
                    </div>
                    <span class="text-[10px] mt-1 font-medium tracking-tight">Users</span>
                </a>
                @endif

                <!-- Tab 5: Profil Saya -->
                <a href="{{ route('profile') }}" 
                   class="flex flex-col items-center justify-center py-1 group transition relative {{ request()->routeIs('profile*') ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-slate-500 dark:text-gray-400 hover:text-slate-800 dark:hover:text-slate-200' }}">
                    <div class="relative">
                        <i class="fa-solid fa-user-circle text-base transition-transform group-active:scale-90"></i>
                        @if(request()->routeIs('profile*'))
                            <span class="absolute -top-1 -right-1 w-1.5 h-1.5 rounded-full bg-indigo-600 dark:bg-indigo-400"></span>
                        @endif
                    </div>
                    <span class="text-[10px] mt-1 font-medium tracking-tight">Profil</span>
                </a>
            </div>
        </nav>
    </div>
    @else
    <!-- Guest Layout (Login Page) -->
    <div class="flex-1 flex flex-col min-h-screen">
        <header class="glass-nav sticky top-0 z-30 py-3.5 px-4 sm:px-6 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <img src="{{ asset('favicon.png') }}" alt="Meta Scheduler Logo" class="w-8 h-8 rounded-xl object-cover shadow-md shadow-indigo-500/25">
                <span class="font-bold text-sm text-slate-900 dark:text-white">Meta Content Scheduler</span>
            </div>
            <button onclick="toggleTheme()" id="themeToggleBtn" 
                    class="w-8 h-8 rounded-lg flex items-center justify-center transition border shadow-sm cursor-pointer bg-white/80 hover:bg-white text-slate-700 border-slate-300/80 dark:bg-slate-800/60 dark:hover:bg-slate-800 dark:text-amber-300 dark:border-white/10"
                    title="Beralih Mode Gelap / Terang">
                <i id="themeToggleIcon" class="fa-solid fa-moon text-xs"></i>
            </button>
        </header>
        <main class="flex-grow flex items-center justify-center p-4">
            @yield('content')
        </main>
        <footer class="glass-nav border-t py-4 text-center text-xs text-slate-500 dark:text-slate-400">
            <p>&copy; {{ date('Y') }} Meta Content Scheduler — Official Meta Graph API</p>
        </footer>
    </div>
    @endauth

    <!-- Global Modal Lightbox Viewer Fullscreen -->
    <div id="lightboxModal" class="fixed inset-0 z-50 hidden bg-black/90 backdrop-blur-md flex items-center justify-center p-4">
        <button onclick="closeLightbox()" class="absolute top-5 right-5 text-gray-400 hover:text-white text-3xl z-50 transition">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <button onclick="prevLightbox()" class="absolute left-4 top-1/2 -translate-y-1/2 text-white/70 hover:text-white text-4xl p-2 z-50 transition">
            <i class="fa-solid fa-chevron-left"></i>
        </button>

        <div class="max-w-4xl max-h-[85vh] flex items-center justify-center relative">
            <img id="lightboxImage" src="" class="max-w-full max-h-[85vh] object-contain rounded-lg shadow-2xl hidden">
            <video id="lightboxVideo" controls src="" class="max-w-full max-h-[85vh] rounded-lg shadow-2xl hidden"></video>
        </div>

        <button onclick="nextLightbox()" class="absolute right-4 top-1/2 -translate-y-1/2 text-white/70 hover:text-white text-4xl p-2 z-50 transition">
            <i class="fa-solid fa-chevron-right"></i>
        </button>

        <div id="lightboxCaption" class="absolute bottom-4 left-1/2 -translate-x-1/2 text-xs text-slate-200 bg-slate-900/90 px-4 py-2 rounded-md border border-slate-700 font-mono"></div>
    </div>

    <!-- Live Interactive Publish Progress Modal (Bagian 2.1 & 11) -->
    <div id="publishProgressModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm hidden px-4 transition-opacity duration-300">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-2xl max-w-xl w-full p-6 space-y-5 text-left relative overflow-hidden">
            <!-- Glowing accent gradient line at top -->
            <div id="progressAccentGlow" class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500"></div>

            <!-- Modal Header -->
            <div class="flex items-start justify-between">
                <div class="flex items-center space-x-3">
                    <div id="progressHeaderIcon" class="w-10 h-10 rounded-lg bg-indigo-50 dark:bg-indigo-600/20 border border-indigo-200 dark:border-indigo-500/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                        <i class="fa-solid fa-satellite-dish text-lg animate-pulse"></i>
                    </div>
                    <div>
                        <h3 id="progressModalTitle" class="text-base font-bold text-slate-900 dark:text-white leading-tight">Mempublikasikan Jadwal ke Meta</h3>
                        <p id="progressModalSubtitle" class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Menghubungkan ke Meta Graph API v22.0...</p>
                    </div>
                </div>
                <button id="progressModalCloseBtn" onclick="closePublishProgressModal()" class="text-slate-400 hover:text-slate-700 dark:text-gray-500 dark:hover:text-white transition text-sm p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-gray-800 hidden">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>

            <!-- Progress Bar & Percentage Indicator -->
            <div class="space-y-1.5">
                <div class="flex items-center justify-between text-xs">
                    <span id="progressStatusLabel" class="font-medium text-slate-700 dark:text-gray-300">Menginisialisasi proses publikasi...</span>
                    <span id="progressPercentage" class="font-bold text-indigo-600 dark:text-indigo-400 font-mono">0%</span>
                </div>
                <div class="w-full h-2.5 bg-slate-100 dark:bg-gray-950 rounded-full overflow-hidden p-0.5 border border-slate-200 dark:border-gray-800">
                    <div id="progressBarFill" class="h-full bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 rounded-full transition-all duration-500 ease-out" style="width: 5%;"></div>
                </div>
            </div>

            <!-- Step-by-Step Tracker Checklist -->
            <div class="space-y-2 text-xs" id="progressStepList">
                <!-- Step 1: Otentikasi & Verifikasi Target -->
                <div id="step-1" class="flex items-center justify-between p-2.5 rounded-lg bg-slate-50 dark:bg-gray-950/60 border border-slate-200 dark:border-gray-800 transition">
                    <div class="flex items-center space-x-2.5">
                        <div class="step-icon w-6 h-6 rounded-md bg-slate-200 dark:bg-gray-800 flex items-center justify-center text-slate-600 dark:text-gray-400 text-xs">
                            <i class="fa-solid fa-key"></i>
                        </div>
                        <div>
                            <div class="font-semibold text-slate-800 dark:text-gray-200">1. Otentikasi & Verifikasi Target</div>
                            <div class="text-[10px] text-slate-500 dark:text-gray-400 step-desc">Memeriksa Page Access Token & IG User ID</div>
                        </div>
                    </div>
                    <span class="step-badge text-[10px] font-mono px-2 py-0.5 rounded-md bg-slate-100 dark:bg-gray-800 text-slate-600 dark:text-gray-400 border border-slate-200 dark:border-gray-700">Menunggu</span>
                </div>

                <!-- Step 2: Pembuatan Media Container -->
                <div id="step-2" class="flex items-center justify-between p-2.5 rounded-lg bg-slate-50 dark:bg-gray-950/60 border border-slate-200 dark:border-gray-800 transition">
                    <div class="flex items-center space-x-2.5">
                        <div class="step-icon w-6 h-6 rounded-md bg-slate-200 dark:bg-gray-800 flex items-center justify-center text-slate-600 dark:text-gray-400 text-xs">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                        </div>
                        <div>
                            <div class="font-semibold text-slate-800 dark:text-gray-200">2. Buat Media Container di Meta</div>
                            <div class="text-[10px] text-slate-500 dark:text-gray-400 step-desc">Mengirim URL media publik ke endpoint Instagram API</div>
                        </div>
                    </div>
                    <span class="step-badge text-[10px] font-mono px-2 py-0.5 rounded-md bg-slate-100 dark:bg-gray-800 text-slate-600 dark:text-gray-400 border border-slate-200 dark:border-gray-700">Menunggu</span>
                </div>

                <!-- Step 3: Validasi Kesiapan Media (Polling) -->
                <div id="step-3" class="flex items-center justify-between p-2.5 rounded-lg bg-slate-50 dark:bg-gray-950/60 border border-slate-200 dark:border-gray-800 transition">
                    <div class="flex items-center space-x-2.5">
                        <div class="step-icon w-6 h-6 rounded-md bg-slate-200 dark:bg-gray-800 flex items-center justify-center text-slate-600 dark:text-gray-400 text-xs">
                            <i class="fa-solid fa-hourglass-half"></i>
                        </div>
                        <div>
                            <div class="font-semibold text-slate-800 dark:text-gray-200">3. Validasi Kesiapan Media di Meta</div>
                            <div class="text-[10px] text-slate-500 dark:text-gray-400 step-desc">Memastikan status media FINISHED di server Meta</div>
                        </div>
                    </div>
                    <span class="step-badge text-[10px] font-mono px-2 py-0.5 rounded-md bg-slate-100 dark:bg-gray-800 text-slate-600 dark:text-gray-400 border border-slate-200 dark:border-gray-700">Menunggu</span>
                </div>

                <!-- Step 4: Publikasi ke Instagram & Facebook -->
                <div id="step-4" class="flex items-center justify-between p-2.5 rounded-lg bg-slate-50 dark:bg-gray-950/60 border border-slate-200 dark:border-gray-800 transition">
                    <div class="flex items-center space-x-2.5">
                        <div class="step-icon w-6 h-6 rounded-md bg-slate-200 dark:bg-gray-800 flex items-center justify-center text-slate-600 dark:text-gray-400 text-xs">
                            <i class="fa-solid fa-paper-plane"></i>
                        </div>
                        <div>
                            <div class="font-semibold text-slate-800 dark:text-gray-200">4. Publikasikan Konten ke Platform</div>
                            <div class="text-[10px] text-slate-500 dark:text-gray-400 step-desc">Menayangkan ke Story/Feed Instagram & Facebook Page</div>
                        </div>
                    </div>
                    <span class="step-badge text-[10px] font-mono px-2 py-0.5 rounded-md bg-slate-100 dark:bg-gray-800 text-slate-600 dark:text-gray-400 border border-slate-200 dark:border-gray-700">Menunggu</span>
                </div>

                <!-- Step 5: Catat Log & Finalisasi -->
                <div id="step-5" class="flex items-center justify-between p-2.5 rounded-lg bg-slate-50 dark:bg-gray-950/60 border border-slate-200 dark:border-gray-800 transition">
                    <div class="flex items-center space-x-2.5">
                        <div class="step-icon w-6 h-6 rounded-md bg-slate-200 dark:bg-gray-800 flex items-center justify-center text-slate-600 dark:text-gray-400 text-xs">
                            <i class="fa-solid fa-database"></i>
                        </div>
                        <div>
                            <div class="font-semibold text-slate-800 dark:text-gray-200">5. Catat Log & Finalisasi Database</div>
                            <div class="text-[10px] text-slate-500 dark:text-gray-400 step-desc">Menyimpan respons Media ID & update status jadwal</div>
                        </div>
                    </div>
                    <span class="step-badge text-[10px] font-mono px-2 py-0.5 rounded-md bg-slate-100 dark:bg-gray-800 text-slate-600 dark:text-gray-400 border border-slate-200 dark:border-gray-700">Menunggu</span>
                </div>
            </div>

            <!-- Live Terminal / Console Box -->
            <div class="space-y-1.5">
                <div class="flex items-center justify-between text-[11px] text-slate-500 dark:text-gray-400">
                    <span class="flex items-center space-x-1.5 font-medium">
                        <i class="fa-solid fa-terminal text-[10px] text-indigo-500 dark:text-indigo-400"></i>
                        <span>Live Execution Console</span>
                    </span>
                    <span class="text-[10px] text-slate-600 dark:text-gray-400 font-mono bg-slate-100 dark:bg-gray-800/80 px-2 py-0.5 rounded border border-slate-200 dark:border-gray-700" id="progressTimer">0.0s</span>
                </div>
                <div id="progressConsole" class="bg-slate-900 dark:bg-gray-950 p-3 rounded-lg border border-slate-800 font-mono text-[10px] leading-relaxed max-h-32 overflow-y-auto space-y-1 text-slate-200 select-text shadow-inner">
                    <!-- Dynamic console log lines injected here -->
                </div>
            </div>

            <!-- Result Breakdown Card (Hidden during loading, shown on completion) -->
            <div id="progressResultCard" class="hidden p-4 rounded-xl border text-xs space-y-2 shadow-lg"></div>

            <!-- Action Footer -->
            <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-200 dark:border-gray-800/80">
                <button id="progressCancelBtn" onclick="closePublishProgressModal()" class="px-4 py-2 text-xs font-semibold rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 dark:bg-gray-800 dark:hover:bg-gray-700 dark:text-gray-300 transition hidden">
                    Tutup
                </button>
                <button id="progressDoneReloadBtn" onclick="window.location.reload()" class="px-4 py-2 text-xs font-semibold rounded-lg bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white shadow-md shadow-indigo-600/30 transition hidden flex items-center space-x-1.5">
                    <i class="fa-solid fa-arrows-rotate text-xs"></i>
                    <span>Muat Ulang Halaman</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Global SweetAlert, Theme & Lightbox Scripts -->
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let lightboxItems = [];
        let currentLightboxIdx = 0;

        let progressIntervalTimer = null;
        let progressStartTimestamp = null;
        let progressPhaseStep = 1;

        function updateThemeIcon() {
            const isDark = document.documentElement.classList.contains('dark');
            const icon = document.getElementById('themeToggleIcon');
            if (icon) {
                if (isDark) {
                    icon.className = 'fa-solid fa-sun text-xs text-amber-400';
                } else {
                    icon.className = 'fa-solid fa-moon text-xs text-indigo-600';
                }
            }
        }

        function toggleTheme() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.theme = 'light';
            } else {
                document.documentElement.classList.add('dark');
                localStorage.theme = 'dark';
            }
            updateThemeIcon();
        }

        document.addEventListener('DOMContentLoaded', updateThemeIcon);

        function showLoading(title, text) {
            Swal.fire({
                title: title || 'Memproses...',
                text: text || 'Mohon tunggu sebentar...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                },
                customClass: {
                    popup: 'swal2-popup-dark',
                    title: 'swal2-title-dark',
                    htmlContainer: 'swal2-html-dark'
                }
            });
        }

        function showAlert(icon, title, text) {
            Swal.fire({
                icon: icon,
                title: title,
                text: text,
                customClass: {
                    popup: 'swal2-popup-dark',
                    title: 'swal2-title-dark',
                    htmlContainer: 'swal2-html-dark'
                }
            });
        }

        function openLightboxDirect(url, isVideo, name) {
            lightboxItems = [{ url: url, isVideo: isVideo, name: name || '' }];
            currentLightboxIdx = 0;
            updateLightboxView();
            document.getElementById('lightboxModal').classList.remove('hidden');
        }

        function closeLightbox() {
            document.getElementById('lightboxModal').classList.add('hidden');
            const vid = document.getElementById('lightboxVideo');
            vid.pause();
            vid.src = '';
        }

        function prevLightbox() {
            if (lightboxItems.length === 0) return;
            currentLightboxIdx = (currentLightboxIdx - 1 + lightboxItems.length) % lightboxItems.length;
            updateLightboxView();
        }

        function nextLightbox() {
            if (lightboxItems.length === 0) return;
            currentLightboxIdx = (currentLightboxIdx + 1) % lightboxItems.length;
            updateLightboxView();
        }

        function updateLightboxView() {
            const item = lightboxItems[currentLightboxIdx];
            if (!item) return;
            const imgEl = document.getElementById('lightboxImage');
            const vidEl = document.getElementById('lightboxVideo');
            const capEl = document.getElementById('lightboxCaption');

            if (item.isVideo) {
                imgEl.classList.add('hidden');
                vidEl.classList.remove('hidden');
                vidEl.src = item.url;
                vidEl.play();
            } else {
                vidEl.classList.add('hidden');
                vidEl.pause();
                imgEl.classList.remove('hidden');
                imgEl.src = item.url;
            }

            capEl.textContent = `${currentLightboxIdx + 1} / ${lightboxItems.length} - ${item.name || 'Media Asset'}`;
        }

        document.addEventListener('keydown', function(e) {
            const modal = document.getElementById('lightboxModal');
            if (!modal.classList.contains('hidden')) {
                if (e.key === 'Escape') closeLightbox();
                if (e.key === 'ArrowLeft') prevLightbox();
                if (e.key === 'ArrowRight') nextLightbox();
            }
        });

        /* =========================================================================
           PROGRESS MODAL CONTROLLER FUNCTIONS (Live Loading & Step Tracker)
           ========================================================================= */

        function openPublishProgressModal(title, subtitle) {
            const modal = document.getElementById('publishProgressModal');
            document.getElementById('progressModalTitle').textContent = title || 'Mempublikasikan Jadwal ke Meta';
            document.getElementById('progressModalSubtitle').textContent = subtitle || 'Menghubungkan ke Meta Graph API v22.0...';
            document.getElementById('progressModalCloseBtn').classList.add('hidden');
            document.getElementById('progressCancelBtn').classList.add('hidden');
            document.getElementById('progressDoneReloadBtn').classList.add('hidden');
            document.getElementById('progressResultCard').classList.add('hidden');
            document.getElementById('progressResultCard').innerHTML = '';

            // Reset console & timer
            document.getElementById('progressConsole').innerHTML = '';
            document.getElementById('progressTimer').textContent = '0.0s';
            setProgressBar(5, 'Menyiapkan proses publikasi...');

            // Reset all 5 steps to waiting
            for (let i = 1; i <= 5; i++) {
                updateProgressStep(i, 'pending', 'Menunggu');
            }

            modal.classList.remove('hidden');

            // Start timer
            progressStartTimestamp = Date.now();
            if (progressIntervalTimer) clearInterval(progressIntervalTimer);
            progressIntervalTimer = setInterval(() => {
                const elapsedSec = ((Date.now() - progressStartTimestamp) / 1000).toFixed(1);
                document.getElementById('progressTimer').textContent = `${elapsedSec}s`;
            }, 100);
        }

        function closePublishProgressModal() {
            if (progressIntervalTimer) clearInterval(progressIntervalTimer);
            document.getElementById('publishProgressModal').classList.add('hidden');
        }

        function setProgressBar(percentage, statusText) {
            const bar = document.getElementById('progressBarFill');
            const percText = document.getElementById('progressPercentage');
            const statText = document.getElementById('progressStatusLabel');

            bar.style.width = `${Math.min(100, Math.max(0, percentage))}%`;
            percText.textContent = `${Math.round(percentage)}%`;
            if (statusText) statText.textContent = statusText;
        }

        function updateProgressStep(stepNum, state, badgeText) {
            const stepEl = document.getElementById(`step-${stepNum}`);
            if (!stepEl) return;

            const iconEl = stepEl.querySelector('.step-icon');
            const badgeEl = stepEl.querySelector('.step-badge');

            // Reset class variations
            stepEl.className = 'flex items-center justify-between p-2.5 rounded-xl transition border';
            iconEl.className = 'step-icon w-6 h-6 rounded-lg flex items-center justify-center text-xs';
            badgeEl.className = 'step-badge text-[10px] font-mono px-2 py-0.5 rounded-md border';

            if (state === 'pending') {
                stepEl.classList.add('bg-gray-950/40', 'border-gray-800/80');
                iconEl.classList.add('bg-gray-800', 'text-gray-500');
                badgeEl.classList.add('bg-gray-800/60', 'text-gray-400', 'border-gray-700/60');
                badgeEl.textContent = badgeText || 'Menunggu';
            } else if (state === 'active') {
                stepEl.classList.add('bg-indigo-950/30', 'border-indigo-800/80');
                iconEl.classList.add('bg-indigo-600/30', 'text-indigo-400', 'animate-spin');
                iconEl.innerHTML = '<i class="fa-solid fa-circle-notch"></i>';
                badgeEl.classList.add('bg-indigo-950', 'text-indigo-400', 'border-indigo-700', 'animate-pulse');
                badgeEl.textContent = badgeText || 'Memproses...';
            } else if (state === 'completed') {
                stepEl.classList.add('bg-emerald-950/25', 'border-emerald-800/60');
                iconEl.classList.add('bg-emerald-950', 'text-emerald-400');
                iconEl.innerHTML = '<i class="fa-solid fa-check"></i>';
                badgeEl.classList.add('bg-emerald-950', 'text-emerald-400', 'border-emerald-700');
                badgeEl.textContent = badgeText || 'Selesai';
            } else if (state === 'failed') {
                stepEl.classList.add('bg-rose-950/30', 'border-rose-800/70');
                iconEl.classList.add('bg-rose-950', 'text-rose-400');
                iconEl.innerHTML = '<i class="fa-solid fa-xmark"></i>';
                badgeEl.classList.add('bg-rose-950', 'text-rose-400', 'border-rose-700');
                badgeEl.textContent = badgeText || 'Gagal';
            }
        }

        function addConsoleLog(message, type = 'info') {
            const consoleBox = document.getElementById('progressConsole');
            const now = new Date();
            const timeStr = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}:${String(now.getSeconds()).padStart(2, '0')}`;
            
            const line = document.createElement('div');
            line.className = 'flex items-start space-x-2';

            let iconHtml = '<i class="fa-solid fa-chevron-right text-gray-500 text-[8px] mt-1"></i>';
            let colorClass = 'text-gray-300';

            if (type === 'success') {
                iconHtml = '<i class="fa-solid fa-circle-check text-emerald-400 text-[9px] mt-1"></i>';
                colorClass = 'text-emerald-300';
            } else if (type === 'error') {
                iconHtml = '<i class="fa-solid fa-circle-xmark text-rose-400 text-[9px] mt-1"></i>';
                colorClass = 'text-rose-300';
            } else if (type === 'warning') {
                iconHtml = '<i class="fa-solid fa-triangle-exclamation text-amber-400 text-[9px] mt-1"></i>';
                colorClass = 'text-amber-300';
            } else if (type === 'step') {
                iconHtml = '<i class="fa-solid fa-circle-notch text-indigo-400 animate-spin text-[9px] mt-1"></i>';
                colorClass = 'text-indigo-300 font-semibold';
            }

            line.innerHTML = `<span class="text-gray-500 select-none">[${timeStr}]</span> ${iconHtml} <span class="${colorClass}">${message}</span>`;
            consoleBox.appendChild(line);
            consoleBox.scrollTop = consoleBox.scrollHeight;
        }

        /**
         * Publish Single Schedule with Live Progress Tracker Modal
         */
        window.publishScheduledWithProgress = function(scheduleId, dateInfo, campaignName, forceRepublish = false) {
            Swal.fire({
                title: forceRepublish ? 'Terbitkan Ulang Jadwal Ini?' : 'Publikasikan Jadwal Ini Sekarang?',
                html: `<div class="text-left text-xs text-gray-300 space-y-2.5">
                    <p>Campaign: <strong class="text-white">${campaignName || 'Campaign'}</strong></p>
                    <p>Jadwal tayang: <strong class="text-white font-mono">${dateInfo || 'Jadwal'}</strong></p>
                    <div class="p-3 bg-indigo-950/40 border border-indigo-800/60 rounded-xl text-indigo-300 text-[11px] leading-relaxed">
                        <i class="fa-solid fa-satellite-dish mr-1"></i>
                        Engine akan menghubungkan <strong>Meta Graph API v22.0</strong>, membuat media container, memverifikasi kesiapan media, dan menayangkan konten secara live dengan progress visual lengkap.
                    </div>
                </div>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#4f46e5',
                cancelButtonColor: '#374151',
                confirmButtonText: '<i class="fa-solid fa-paper-plane mr-1.5"></i>Mulai Publikasi Sekarang',
                cancelButtonText: 'Batal',
                customClass: {
                    popup: 'swal2-popup-dark',
                    title: 'swal2-title-dark',
                    htmlContainer: 'swal2-html-dark'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    executeSingleScheduleProgress(scheduleId, dateInfo, campaignName, forceRepublish);
                }
            });
        };

        function executeSingleScheduleProgress(scheduleId, dateInfo, campaignName, forceRepublish) {
            openPublishProgressModal(
                `Publikasi Jadwal #${scheduleId}`,
                `${campaignName ? campaignName + ' • ' : ''}${dateInfo || 'Jadwal'}`
            );

            addConsoleLog(`Inisialisasi eksekusi publikasi jadwal #${scheduleId}...`, 'info');
            addConsoleLog(`Menghubungi endpoint server lokal sosmedauto...`, 'info');

            // Phase 1: Authentication & Verification
            updateProgressStep(1, 'active', 'Otentikasi');
            setProgressBar(15, '1/5 Otentikasi token & validasi akun target...');
            addConsoleLog(`Memeriksa Page Access Token & Instagram Business ID...`, 'step');

            let phaseTimer1 = setTimeout(() => {
                updateProgressStep(1, 'completed', 'Valid');
                updateProgressStep(2, 'active', 'Upload Media');
                setProgressBar(38, '2/5 Mengunggah & membuat Media Container di Meta...');
                addConsoleLog(`Mengirimkan URL media publik ke Meta Graph API v22.0...`, 'step');
            }, 1200);

            let phaseTimer2 = setTimeout(() => {
                updateProgressStep(2, 'completed', 'Container OK');
                updateProgressStep(3, 'active', 'Validasi Status');
                setProgressBar(62, '3/5 Memeriksa status kesiapan media container di Meta...');
                addConsoleLog(`Menunggu respons verifikasi encoding container (status FINISHED)...`, 'step');
            }, 3000);

            let phaseTimer3 = setTimeout(() => {
                updateProgressStep(3, 'completed', 'Ready');
                updateProgressStep(4, 'active', 'Menerbitkan...');
                setProgressBar(85, '4/5 Menerbitkan ke Instagram & Facebook Page...');
                addConsoleLog(`Mengirim instruksi publikasi container ke Feed/Story & Page Feed...`, 'step');
            }, 5500);

            fetch(`/schedules/${scheduleId}/run`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    force_republish: forceRepublish ? true : false
                })
            })
            .then(res => res.json().then(data => ({ status: res.status, ok: res.ok, data: data })))
            .then(({ status, ok, data }) => {
                clearTimeout(phaseTimer1);
                clearTimeout(phaseTimer2);
                clearTimeout(phaseTimer3);
                if (progressIntervalTimer) clearInterval(progressIntervalTimer);

                const finalTime = document.getElementById('progressTimer').textContent;

                if (ok && data.success) {
                    // Mark all steps as complete
                    for (let i = 1; i <= 5; i++) {
                        updateProgressStep(i, 'completed', 'Selesai');
                    }
                    setProgressBar(100, 'Publikasi selesai dengan sukses!');
                    document.getElementById('progressBarFill').className = 'h-full bg-gradient-to-r from-emerald-500 to-teal-400 rounded-full transition-all duration-500 ease-out';
                    
                    addConsoleLog(`Respons sukses diterima dari Meta Graph API dalam ${finalTime}.`, 'success');
                    addConsoleLog(`Status jadwal sekarang: ${data.status.toUpperCase()}`, 'success');

                    // Render Result Card
                    const resultCard = document.getElementById('progressResultCard');
                    resultCard.className = 'p-4 rounded-2xl bg-emerald-950/40 border border-emerald-800 text-xs space-y-2.5 shadow-lg';

                    let logsHtml = '';
                    if (data.logs && data.logs.length > 0) {
                        logsHtml = `<div class="space-y-1.5 mt-2 border-t border-emerald-800/60 pt-2">
                            <div class="text-[10px] font-semibold text-emerald-300 uppercase tracking-wider">Hasil Per Target Akun:</div>`;
                        data.logs.forEach(l => {
                            const accName = l.connected_account ? l.connected_account.page_name : 'Akun';
                            const isIg = l.platform === 'instagram';
                            const badge = l.action_status === 'success' ? 
                                '<span class="px-1.5 py-0.5 rounded text-[9px] bg-emerald-900 text-emerald-300 font-bold uppercase">Sukses</span>' :
                                (l.action_status === 'skipped' ? '<span class="px-1.5 py-0.5 rounded text-[9px] bg-gray-800 text-gray-400 font-bold uppercase">Skip</span>' : '<span class="px-1.5 py-0.5 rounded text-[9px] bg-rose-900 text-rose-300 font-bold uppercase">Gagal</span>');
                            
                            const detailId = l.media_id ? `<span class="font-mono text-[9px] text-gray-400">ID: ${l.media_id}</span>` : '';
                            const errorMsg = l.error_message ? `<span class="text-[10px] text-rose-300 block">${l.error_message}</span>` : '';

                            logsHtml += `
                                <div class="flex items-center justify-between text-[11px] bg-gray-900/60 p-2 rounded-xl border border-gray-800">
                                    <div class="flex items-center space-x-2">
                                        <i class="${isIg ? 'fa-brands fa-instagram text-pink-400' : 'fa-brands fa-facebook text-blue-400'}"></i>
                                        <span class="font-semibold text-white">${accName}</span>
                                        <span class="text-[10px] text-gray-400">(${l.platform})</span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        ${detailId}
                                        ${badge}
                                    </div>
                                </div>
                                ${errorMsg}
                            `;
                        });
                        logsHtml += `</div>`;
                    }

                    resultCard.innerHTML = `
                        <div class="flex items-center space-x-2 text-emerald-400 font-bold">
                            <i class="fa-solid fa-circle-check text-base"></i>
                            <span>${data.message || 'Konten berhasil dipublikasikan ke Meta!'}</span>
                        </div>
                        <p class="text-gray-300 text-[11px] leading-relaxed">${data.notes || 'Seluruh aksi telah selesai dieksekusi dan log tercatat.'}</p>
                        ${logsHtml}
                    `;
                    resultCard.classList.remove('hidden');

                    document.getElementById('progressModalCloseBtn').classList.remove('hidden');
                    document.getElementById('progressDoneReloadBtn').classList.remove('hidden');
                } else {
                    handlePublishError(data.message || 'Gagal mempublikasikan jadwal');
                }
            })
            .catch(err => {
                clearTimeout(phaseTimer1);
                clearTimeout(phaseTimer2);
                clearTimeout(phaseTimer3);
                if (progressIntervalTimer) clearInterval(progressIntervalTimer);
                handlePublishError(err.message || 'Terjadi kesalahan jaringan');
            });
        }

        function handlePublishError(errorMsg) {
            updateProgressStep(4, 'failed', 'Gagal');
            setProgressBar(100, 'Gagal mempublikasikan konten');
            document.getElementById('progressBarFill').className = 'h-full bg-rose-600 rounded-full transition-all duration-500 ease-out';
            
            addConsoleLog(`Eksekusi gagal: ${errorMsg}`, 'error');

            const resultCard = document.getElementById('progressResultCard');
            resultCard.className = 'p-4 rounded-2xl bg-rose-950/40 border border-rose-800 text-xs space-y-2 shadow-lg';
            resultCard.innerHTML = `
                <div class="flex items-center space-x-2 text-rose-400 font-bold">
                    <i class="fa-solid fa-circle-exclamation text-base"></i>
                    <span>Publikasi Gagal Dieksekusi</span>
                </div>
                <p class="text-gray-300 text-[11px] leading-relaxed">${errorMsg}</p>
                <div class="p-2.5 bg-gray-900/80 rounded-xl border border-gray-800 text-[10px] text-gray-400 font-mono">
                    Silakan periksa izin Page Access Token & koneksi Instagram Business di menu Integrasi Meta API.
                </div>
            `;
            resultCard.classList.remove('hidden');

            document.getElementById('progressModalCloseBtn').classList.remove('hidden');
            document.getElementById('progressCancelBtn').classList.remove('hidden');
        }

        /**
         * Trigger Publish Now (Bulk or Project Due) with Live Loading Progress
         */
        window.triggerPublishNowWithProgress = function(projectId = null, projectName = null, force = false) {
            const title = projectId ? `Publikasikan Jadwal Jatuh Tempo Campaign ${projectName || ''}?` : 'Jalankan Antrean Jatuh Tempo?';
            const desc = projectId ? 
                `Engine hanya akan memeriksa & menerbitkan jadwal campaign <strong>${projectName || 'ini'}</strong> yang <strong>waktunya sudah tiba atau lewat (hari ini/sebelumnya)</strong>.` :
                'Engine hanya akan menerbitkan jadwal yang <strong>waktunya sudah tiba atau lewat (hari ini atau sebelumnya)</strong>.';

            Swal.fire({
                title: title,
                html: `<div class="text-left text-xs text-gray-300 space-y-2.5">
                    <p>${desc}</p>
                    <div class="p-2.5 bg-gray-900 border border-gray-800 rounded-xl text-gray-400 text-[11px] leading-relaxed">
                        <i class="fa-solid fa-shield-halved text-emerald-400 mr-1"></i>
                        <strong>Aman:</strong> Jadwal yang tanggalnya masih di masa depan <em>(misal 17 Sep - Okt 2026)</em> <strong>TIDAK AKAN</strong> terposting oleh tombol ini.
                    </div>
                    <div class="p-2.5 bg-indigo-950/40 border border-indigo-800/60 rounded-xl text-indigo-300 text-[11px] leading-relaxed">
                        <i class="fa-solid fa-lightbulb text-amber-300 mr-1"></i>
                        <em>Tips:</em> Jika ingin menerbitkan jadwal tanggal tertentu sekarang juga untuk testing, klik tombol <strong>ikon pesawat kertas</strong> pada baris tanggal tersebut di tabel.
                    </div>
                </div>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#4f46e5',
                cancelButtonColor: '#374151',
                confirmButtonText: '<i class="fa-solid fa-paper-plane mr-1.5"></i>Ya, Periksa & Eksekusi',
                cancelButtonText: 'Batal',
                customClass: {
                    popup: 'swal2-popup-dark',
                    title: 'swal2-title-dark',
                    htmlContainer: 'swal2-html-dark'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    executeBatchPublishProgress(projectId, projectName, force);
                }
            });
        };

        function executeBatchPublishProgress(projectId, projectName, force) {
            openPublishProgressModal(
                projectId ? `Publikasi Campaign: ${projectName || 'Campaign'}` : 'Publikasi Seluruh Antrean Jatuh Tempo',
                'Memeriksa jadwal yang siap terbit...'
            );

            addConsoleLog(`Menjalankan perintah Artisan meta:publish...`, 'info');
            if (projectId) addConsoleLog(`Filter Project ID: #${projectId}`, 'info');

            updateProgressStep(1, 'active', 'Scanning');
            setProgressBar(25, '1/5 Memeriksa jadwal yang jatuh tempo...');

            let timerA = setTimeout(() => {
                updateProgressStep(1, 'completed', 'OK');
                updateProgressStep(2, 'active', 'Menghubungi Meta');
                setProgressBar(50, '2/5 Menyiapkan aset media dan endpoint Meta...');
                addConsoleLog(`Menghubungkan ke Meta Graph API untuk akun target terkait...`, 'step');
            }, 1000);

            let timerB = setTimeout(() => {
                updateProgressStep(2, 'completed', 'OK');
                updateProgressStep(3, 'active', 'Memproses');
                updateProgressStep(4, 'active', 'Menerbitkan');
                setProgressBar(75, '3/5 Mempublikasikan konten ke Instagram & Facebook...');
                addConsoleLog(`Memproses antrean postingan...`, 'step');
            }, 2500);

            fetch("{{ route('schedules.publishNow') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    project_id: projectId || null,
                    force: force ? true : false
                })
            })
            .then(res => res.json().then(data => ({ ok: res.ok, data: data })))
            .then(({ ok, data }) => {
                clearTimeout(timerA);
                clearTimeout(timerB);
                if (progressIntervalTimer) clearInterval(progressIntervalTimer);

                const finalTime = document.getElementById('progressTimer').textContent;

                if (ok && data.success) {
                    for (let i = 1; i <= 5; i++) {
                        updateProgressStep(i, 'completed', 'Selesai');
                    }
                    setProgressBar(100, 'Seluruh antrean berhasil diproses!');
                    document.getElementById('progressBarFill').className = 'h-full bg-gradient-to-r from-emerald-500 to-teal-400 rounded-full transition-all duration-500 ease-out';
                    
                    addConsoleLog(`Perintah selesai dalam ${finalTime}.`, 'success');
                    if (data.output) {
                        data.output.split('\n').forEach(line => {
                            if (line.trim()) addConsoleLog(line.trim(), 'info');
                        });
                    }

                    const resultCard = document.getElementById('progressResultCard');
                    resultCard.className = 'p-4 rounded-2xl bg-emerald-950/40 border border-emerald-800 text-xs space-y-2 shadow-lg';
                    resultCard.innerHTML = `
                        <div class="flex items-center space-x-2 text-emerald-400 font-bold">
                            <i class="fa-solid fa-circle-check text-base"></i>
                            <span>${data.message || 'Antrean berhasil diproses!'}</span>
                        </div>
                        <div class="bg-gray-950/80 p-2.5 rounded-xl border border-gray-800 font-mono text-[10px] text-gray-300 whitespace-pre-wrap">
                            ${data.output || 'Tidak ada jadwal yang perlu dipublish saat ini atau seluruhnya telah selesai.'}
                        </div>
                    `;
                    resultCard.classList.remove('hidden');

                    document.getElementById('progressModalCloseBtn').classList.remove('hidden');
                    document.getElementById('progressDoneReloadBtn').classList.remove('hidden');
                } else {
                    handlePublishError(data.message || 'Gagal memproses antrean');
                }
            })
            .catch(err => {
                clearTimeout(timerA);
                clearTimeout(timerB);
                if (progressIntervalTimer) clearInterval(progressIntervalTimer);
                handlePublishError(err.message || 'Terjadi kesalahan jaringan');
            });
        }

        // Standard wrapper for header button
        function triggerPublishNow() {
            triggerPublishNowWithProgress();
        }
    </script>

    @yield('scripts')
</body>
</html>