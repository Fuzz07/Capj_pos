<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CAPTAiN J POS SYSTEM - Point of Sale & Management</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('images/capj.jpg') }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #1e293b;
            background-color: #ffffff;
        }

        .brand-red {
            color: #dc2626;
        }

        .bg-brand-red {
            background-color: #dc2626;
        }

        .bg-brand-red-hover:hover {
            background-color: #b91c1c;
        }

        .bg-soft-red {
            background-color: #fef2f2;
        }

        .border-brand-red {
            border-color: #dc2626;
        }

        .hero-gradient {
            background: linear-gradient(135deg, #ffffff 0%, #fff5f5 100%);
        }

        .card-shadow {
            box-shadow: 0 10px 30px -5px rgba(220, 38, 38, 0.05), 0 4px 12px -2px rgba(0, 0, 0, 0.03);
        }

        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 35px -10px rgba(220, 38, 38, 0.12);
        }

        /* Modal styling */
        .modal-backdrop {
            background-color: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
        }
    </style>
</head>
<body class="antialiased selection:bg-red-500 selection:text-white">

    <!-- NAVIGATION HEADER -->
    <header class="sticky top-0 z-40 w-full bg-white/90 backdrop-blur-md border-b border-slate-100 shadow-sm transition-all duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <!-- Brand Logo -->
            <a href="{{ route('landing') }}" class="flex items-center gap-3 group">
                <div class="w-11 h-11 rounded-full overflow-hidden border-2 border-red-500 p-0.5 shadow-sm group-hover:scale-105 transition-transform duration-200">
                    <img src="{{ asset('images/capj.jpg') }}" alt="CAPTAiN J Logo" class="w-full h-full object-cover rounded-full">
                </div>
                <div>
                    <span class="text-xl font-extrabold tracking-tight text-slate-900 block leading-none">CAPTAiN J</span>
                    <span class="text-xs font-semibold text-red-600 tracking-wider">POS SYSTEM</span>
                </div>
            </a>

            <!-- Navigation Links -->
            <nav class="hidden md:flex items-center gap-8 text-sm font-semibold text-slate-600">
                <a href="#hero" class="hover:text-red-600 transition-colors py-1 border-b-2 border-transparent hover:border-red-600 text-red-600 border-red-600">Home</a>
                <a href="#features" class="hover:text-red-600 transition-colors py-1 border-b-2 border-transparent hover:border-red-600">Features</a>
                <a href="#how-it-works" class="hover:text-red-600 transition-colors py-1 border-b-2 border-transparent hover:border-red-600">How It Works</a>
                <a href="#download" class="hover:text-red-600 transition-colors py-1 border-b-2 border-transparent hover:border-red-600">Download</a>
                <a href="#faq" class="hover:text-red-600 transition-colors py-1 border-b-2 border-transparent hover:border-red-600">FAQ</a>
            </nav>

            <!-- Action Button -->
            <div class="flex items-center gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-bold text-white bg-red-600 hover:bg-red-700 rounded-lg shadow-md hover:shadow-red-200 transition-all duration-200">
                        Go to POS Dashboard
                        <i class="fa-solid font-bold fa-arrow-right"></i>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-bold text-white bg-red-600 hover:bg-red-700 rounded-lg shadow-md hover:shadow-red-200 transition-all duration-200">
                        Log in
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- HERO SECTION -->
    <section id="hero" class="hero-gradient pt-12 pb-20 md:pt-20 md:pb-28 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                
                <!-- Left Text Content -->
                <div class="lg:col-span-6 space-y-6 text-center lg:text-left">
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-red-50 border border-red-100 text-red-600 font-bold text-xs uppercase tracking-wider">
                        <span class="w-2 h-2 rounded-full bg-red-600 animate-pulse"></span>
                        CAPTAiN J POS SYSTEM
                    </div>

                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-slate-900 tracking-tight leading-none sm:leading-tight">
                        A simple and reliable point-of-sale system for your business.
                    </h1>

                    <p class="text-lg text-slate-600 max-w-2xl mx-auto lg:mx-0 leading-relaxed font-normal">
                        Manage sales, products, inventory, transactions, and daily business operations in one place.
                    </p>
                </div>

                <!-- Right Device Mockup -->
                <div class="lg:col-span-6 relative">
                    <div class="relative mx-auto max-w-lg lg:max-w-none">
                        <!-- Glass background highlight -->
                        <div class="absolute -inset-4 bg-gradient-to-r from-red-500/10 to-orange-500/10 rounded-3xl blur-2xl -z-10"></div>
                        <img src="{{ asset('images/pos_hero_mockup.jpg') }}" alt="CAPTAiN J POS Dashboard & Mobile Interface" class="w-full h-auto rounded-2xl shadow-2xl border border-slate-200/80">
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- FEATURES SECTION -->
    <section id="features" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16 space-y-3">
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900">
                    Everything you need to run your business
                </h2>
                <p class="text-base sm:text-lg text-slate-600">
                    Powerful features designed to make your daily operations easier and more efficient.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                
                <!-- Card 1: Sales & Transactions -->
                <div class="bg-white p-8 rounded-2xl border border-slate-100 card-shadow card-hover space-y-4">
                    <div class="w-14 h-14 rounded-xl bg-red-50 text-red-600 flex items-center justify-center text-2xl font-bold">
                        <i class="fa-solid fa-cart-shopping"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900">Sales & Transactions</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        Process orders quickly, and keep your transactions organized.
                    </p>
                </div>

                <!-- Card 2: Inventory Management -->
                <div class="bg-white p-8 rounded-2xl border border-slate-100 card-shadow card-hover space-y-4">
                    <div class="w-14 h-14 rounded-xl bg-red-50 text-red-600 flex items-center justify-center text-2xl font-bold">
                        <i class="fa-solid fa-boxes-stacked"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900">Inventory Management</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        Monitor products, stock levels, and available items.
                    </p>
                </div>

                <!-- Card 3: Business Reports -->
                <div class="bg-white p-8 rounded-2xl border border-slate-100 card-shadow card-hover space-y-4">
                    <div class="w-14 h-14 rounded-xl bg-red-50 text-red-600 flex items-center justify-center text-2xl font-bold">
                        <i class="fa-solid fa-chart-pie"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900">Business Reports</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        View sales information and track business activity.
                    </p>
                </div>

                <!-- Card 4: Staff & Accounts -->
                <div class="bg-white p-8 rounded-2xl border border-slate-100 card-shadow card-hover space-y-4">
                    <div class="w-14 h-14 rounded-xl bg-red-50 text-red-600 flex items-center justify-center text-2xl font-bold">
                        <i class="fa-solid fa-users-gear"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900">Staff & Accounts</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        Manage authorized users and their access to the system.
                    </p>
                </div>

            </div>
        </div>
    </section>

    <!-- ANDROID APP DOWNLOAD SECTION -->
    <section id="download" class="py-16 bg-gradient-to-b from-red-50/50 to-white border-y border-red-100/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-3xl p-8 sm:p-12 border border-red-100 shadow-xl grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                
                <!-- Mobile Phone Mockup -->
                <div class="lg:col-span-4 flex justify-center">
                    <div class="relative max-w-[280px]">
                        <img src="{{ asset('images/pos_mobile_preview.jpg') }}" alt="CAPTAiN J Mobile POS App" class="w-full h-auto rounded-3xl shadow-xl border-4 border-slate-800">
                    </div>
                </div>

                <!-- App Details & QR Action -->
                <div class="lg:col-span-5 space-y-6 text-center lg:text-left">
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900">
                        CAPTAiN J POS on Android
                    </h2>
                    <p class="text-slate-600 text-sm sm:text-base leading-relaxed">
                        Take your POS system with you. Download the official CAPTAiN J Android application and access your business tools from your mobile device.
                    </p>

                    <div class="flex flex-wrap items-center justify-center lg:justify-start gap-4 pt-2">
                        <a href="https://median.co/share/krkwrwz#apk" target="_blank" class="inline-flex items-center gap-2 px-6 py-3 text-sm font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl shadow-md transition-all">
                            <i class="fa-solid fa-download"></i>
                            Download APK
                        </a>

                        <button onclick="openQrModal()" class="inline-flex items-center gap-3 p-2 pr-4 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl transition-all group text-left cursor-pointer">
                            <div class="w-12 h-12 bg-white rounded-lg border border-slate-200 p-1 flex items-center justify-center">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=https://median.co/share/krkwrwz%23apk" alt="QR Code" class="w-full h-full object-contain">
                            </div>
                            <span class="text-xs font-semibold text-slate-700 group-hover:text-red-600">Scan to Download</span>
                        </button>
                    </div>
                </div>

                <!-- Android Compatibility Checklist -->
                <div class="lg:col-span-3 bg-red-50/60 p-6 rounded-2xl border border-red-100 space-y-4">
                    <div class="flex items-center gap-3 text-emerald-600 font-bold text-sm">
                        <i class="fa-brands fa-android text-2xl text-emerald-500"></i>
                        <span class="text-slate-900">Android</span>
                    </div>
                    <ul class="space-y-3 text-xs sm:text-sm text-slate-700 font-medium">
                        <li class="flex items-start gap-2.5">
                            <i class="fa-solid fa-check text-red-600 mt-1"></i>
                            <span>Compatible with supported Android devices</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <i class="fa-solid fa-check text-red-600 mt-1"></i>
                            <span>Download the latest CAPTAiN J APK</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <i class="fa-solid fa-check text-red-600 mt-1"></i>
                            <span>Install and sign in with your account</span>
                        </li>
                    </ul>
                </div>

            </div>
        </div>
    </section>

    <!-- HOW TO USE SECTION -->
    <section id="how-it-works" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16 space-y-3">
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900">
                    How to use CAPTAiN J
                </h2>
                <p class="text-slate-600">
                    Get started in just a few simple steps.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 relative">
                
                <!-- Step 01 -->
                <div class="bg-slate-50 p-8 rounded-2xl border border-slate-100 text-center space-y-4 relative">
                    <div class="w-12 h-12 mx-auto rounded-full bg-red-100 text-red-600 flex items-center justify-center font-black text-sm">
                        01
                    </div>
                    <h3 class="text-lg font-bold text-slate-900">Sign in</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        Log in using your CAPTAiN J account.
                    </p>
                </div>

                <!-- Step 02 -->
                <div class="bg-slate-50 p-8 rounded-2xl border border-slate-100 text-center space-y-4 relative">
                    <div class="w-12 h-12 mx-auto rounded-full bg-red-100 text-red-600 flex items-center justify-center font-black text-sm">
                        02
                    </div>
                    <h3 class="text-lg font-bold text-slate-900">Manage your business</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        Access products, sales, inventory, transactions, and other POS functions.
                    </p>
                </div>

                <!-- Step 03 -->
                <div class="bg-slate-50 p-8 rounded-2xl border border-slate-100 text-center space-y-4 relative">
                    <div class="w-12 h-12 mx-auto rounded-full bg-red-100 text-red-600 flex items-center justify-center font-black text-sm">
                        03
                    </div>
                    <h3 class="text-lg font-bold text-slate-900">Track your operations</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        Review your sales and business metrics through the system.
                    </p>
                </div>

            </div>
        </div>
    </section>

    <!-- FAQ SECTION -->
    <section id="faq" class="py-20 bg-slate-50 border-t border-slate-100">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            
            <div class="text-center space-y-2">
                <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900">
                    Frequently Asked Questions
                </h2>
                <p class="text-slate-600 text-sm">
                    Find answers to common questions about CAPTAiN J POS.
                </p>
            </div>

            <div class="space-y-3">
                <!-- FAQ 1 -->
                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                    <button onclick="toggleFaq(this)" class="w-full px-6 py-4 text-left font-bold text-slate-800 flex justify-between items-center hover:text-red-600 transition-colors">
                        <span>How do I access CAPTAiN J POS?</span>
                        <i class="fa-solid fa-chevron-down text-slate-400 text-xs transition-transform duration-200"></i>
                    </button>
                    <div class="px-6 pb-4 text-sm text-slate-600 hidden">
                        You can access CAPTAiN J POS directly from any web browser by clicking the 'Log in to POS' button or by downloading the official Android application.
                    </div>
                </div>

                <!-- FAQ 2 -->
                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                    <button onclick="toggleFaq(this)" class="w-full px-6 py-4 text-left font-bold text-slate-800 flex justify-between items-center hover:text-red-600 transition-colors">
                        <span>How do I download the Android app?</span>
                        <i class="fa-solid fa-chevron-down text-slate-400 text-xs transition-transform duration-200"></i>
                    </button>
                    <div class="px-6 pb-4 text-sm text-slate-600 hidden">
                        Click the 'Download APK' button on this landing page or scan the provided QR code with your smartphone camera to launch the direct download.
                    </div>
                </div>

                <!-- FAQ 3 -->
                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                    <button onclick="toggleFaq(this)" class="w-full px-6 py-4 text-left font-bold text-slate-800 flex justify-between items-center hover:text-red-600 transition-colors">
                        <span>How do I install the APK?</span>
                        <i class="fa-solid fa-chevron-down text-slate-400 text-xs transition-transform duration-200"></i>
                    </button>
                    <div class="px-6 pb-4 text-sm text-slate-600 hidden">
                        Open the downloaded file on your Android device. If prompted, allow installation from unknown sources in your browser settings to complete setup.
                    </div>
                </div>

                <!-- FAQ 4 -->
                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                    <button onclick="toggleFaq(this)" class="w-full px-6 py-4 text-left font-bold text-slate-800 flex justify-between items-center hover:text-red-600 transition-colors">
                        <span>Can I use CAPTAiN J on a computer?</span>
                        <i class="fa-solid fa-chevron-down text-slate-400 text-xs transition-transform duration-200"></i>
                    </button>
                    <div class="px-6 pb-4 text-sm text-slate-600 hidden">
                        Yes! CAPTAiN J POS works on desktops, laptops, tablets, and mobile devices through any modern browser.
                    </div>
                </div>

                <!-- FAQ 5 -->
                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                    <button onclick="toggleFaq(this)" class="w-full px-6 py-4 text-left font-bold text-slate-800 flex justify-between items-center hover:text-red-600 transition-colors">
                        <span>What should I do if I forget my password?</span>
                        <i class="fa-solid fa-chevron-down text-slate-400 text-xs transition-transform duration-200"></i>
                    </button>
                    <div class="px-6 pb-4 text-sm text-slate-600 hidden">
                        Click 'Forgot Password' on the login screen and enter your registered email address to receive password reset instructions.
                    </div>
                </div>

                <!-- FAQ 6 -->
                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                    <button onclick="toggleFaq(this)" class="w-full px-6 py-4 text-left font-bold text-slate-800 flex justify-between items-center hover:text-red-600 transition-colors">
                        <span>Is my business information secure?</span>
                        <i class="fa-solid fa-chevron-down text-slate-400 text-xs transition-transform duration-200"></i>
                    </button>
                    <div class="px-6 pb-4 text-sm text-slate-600 hidden">
                        Yes, CAPTAiN J POS ensures your data is protected through encrypted sessions, single-tab security checks, and strict access controls.
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- FOOTER -->
    <footer class="bg-white border-t border-slate-100 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-xs text-slate-500 font-medium">
            <p>© 2026 CAPTAiN J POS SYSTEM. All rights reserved.</p>
        </div>
    </footer>

    <!-- QR CODE DOWNLOAD MODAL -->
    <div id="qrModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop hidden transition-opacity duration-300">
        <div class="bg-white rounded-3xl max-w-xs w-full p-6 shadow-2xl relative text-center transform transition-transform scale-95 duration-300" id="modalCard">
            
            <!-- Close Button -->
            <button onclick="closeQrModal()" class="absolute top-3 right-3 text-slate-400 hover:text-slate-700 w-8 h-8 rounded-full flex items-center justify-center transition-colors">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>

            <!-- QR Code Graphic Only -->
            <div class="p-2 inline-block">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=https://median.co/share/krkwrwz%23apk" alt="QR Code" class="w-56 h-56 mx-auto object-contain rounded-xl">
            </div>

        </div>
    </div>

    <!-- JAVASCRIPT FOR INTERACTION -->
    <script>
        function openQrModal() {
            const modal = document.getElementById('qrModal');
            const card = document.getElementById('modalCard');
            modal.classList.remove('hidden');
            setTimeout(() => {
                card.classList.remove('scale-95');
                card.classList.add('scale-100');
            }, 10);
        }

        function closeQrModal() {
            const modal = document.getElementById('qrModal');
            const card = document.getElementById('modalCard');
            card.classList.remove('scale-100');
            card.classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 200);
        }

        function toggleFaq(btn) {
            const content = btn.nextElementSibling;
            const icon = btn.querySelector('i');
            
            if (content.classList.contains('hidden')) {
                content.classList.remove('hidden');
                icon.classList.add('rotate-180');
            } else {
                content.classList.add('hidden');
                icon.classList.remove('rotate-180');
            }
        }

        // Close modal on backdrop click
        document.getElementById('qrModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeQrModal();
            }
        });
    </script>
</body>
</html>
