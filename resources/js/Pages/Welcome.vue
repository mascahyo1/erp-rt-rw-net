<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String,
});
</script>

<template>
    <Head title="Selamat Datang" />

    <div class="min-h-screen bg-white dark:bg-gray-900">
        <!-- ===== NAVBAR ===== -->
        <nav class="fixed top-0 z-50 w-full border-b border-gray-200 bg-white/80 backdrop-blur-md dark:border-gray-700 dark:bg-gray-900/80">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 lg:px-8">
                <!-- Logo -->
                <Link href="/" class="flex items-center gap-2">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-sky-500 to-indigo-600">
                        <i class="fas fa-wifi text-lg text-white"></i>
                    </div>
                    <span class="text-xl font-bold text-gray-900 dark:text-white">
                        RT<span class="text-sky-500">/</span>RW Net
                    </span>
                </Link>

                <!-- Desktop Menu -->
                <div class="hidden items-center gap-8 lg:flex">
                    <a href="#fitur" class="text-sm font-medium text-gray-600 transition hover:text-sky-600 dark:text-gray-300 dark:hover:text-sky-400">Fitur</a>
                    <a href="#cara-kerja" class="text-sm font-medium text-gray-600 transition hover:text-sky-600 dark:text-gray-300 dark:hover:text-sky-400">Cara Kerja</a>
                    <a href="#harga" class="text-sm font-medium text-gray-600 transition hover:text-sky-600 dark:text-gray-300 dark:hover:text-sky-400">Harga</a>
                    <a href="#kontak" class="text-sm font-medium text-gray-600 transition hover:text-sky-600 dark:text-gray-300 dark:hover:text-sky-400">Kontak</a>
                </div>

                <!-- CTA Buttons -->
                <div class="hidden items-center gap-3 lg:flex">
                    <Link
                        v-if="canLogin"
                        :href="route('login')"
                        class="rounded-lg px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800"
                    >
                        Masuk
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="rounded-lg bg-gradient-to-r from-sky-500 to-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-sky-500/25 transition hover:shadow-lg hover:shadow-sky-500/30 hover:from-sky-600 hover:to-indigo-700"
                    >
                        Mulai Gratis
                    </Link>
                </div>

                <!-- Mobile Menu Button -->
                <button
                    data-collapse-toggle="mobile-menu"
                    type="button"
                    class="inline-flex items-center justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600 lg:hidden"
                    aria-controls="mobile-menu"
                    aria-expanded="false"
                >
                    <span class="sr-only">Buka menu</span>
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>

            <!-- Mobile Menu -->
            <div id="mobile-menu" class="hidden border-t border-gray-200 px-4 pb-4 dark:border-gray-700 lg:hidden">
                <a href="#fitur" class="block rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">Fitur</a>
                <a href="#cara-kerja" class="block rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">Cara Kerja</a>
                <a href="#harga" class="block rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">Harga</a>
                <a href="#kontak" class="block rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">Kontak</a>
                <div class="mt-3 flex flex-col gap-2 border-t border-gray-200 pt-3 dark:border-gray-700">
                    <Link
                        v-if="canLogin"
                        :href="route('login')"
                        class="rounded-lg px-3 py-2 text-center text-sm font-semibold text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800"
                    >
                        Masuk
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="rounded-lg bg-gradient-to-r from-sky-500 to-indigo-600 px-3 py-2.5 text-center text-sm font-semibold text-white"
                    >
                        Mulai Gratis
                    </Link>
                </div>
            </div>
        </nav>

        <!-- ===== HERO SECTION ===== -->
        <section class="relative overflow-hidden pt-24 lg:pt-32">
            <!-- Background decoration -->
            <div class="absolute inset-0 -z-10">
                <div class="absolute -left-40 -top-40 h-[500px] w-[500px] rounded-full bg-sky-400/20 blur-[100px] dark:bg-sky-600/10"></div>
                <div class="absolute -right-40 -bottom-40 h-[500px] w-[500px] rounded-full bg-indigo-400/20 blur-[100px] dark:bg-indigo-600/10"></div>
            </div>

            <div class="mx-auto max-w-7xl px-4 pb-20 lg:flex lg:items-center lg:gap-12 lg:px-8">
                <!-- Left: Text -->
                <div class="max-w-2xl lg:flex-1">
                    <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-4 py-1.5 text-sm font-medium text-sky-700 dark:border-sky-800 dark:bg-sky-900/50 dark:text-sky-300">
                        <i class="fas fa-rocket text-xs"></i>
                        ERP Modern untuk Bisnis Internet Anda
                    </div>

                    <h1 class="text-4xl font-extrabold tracking-tight text-gray-900 dark:text-white sm:text-5xl lg:text-6xl">
                        Kelola Bisnis
                        <span class="bg-gradient-to-r from-sky-500 to-indigo-600 bg-clip-text text-transparent">RT/RW Net</span>
                        Jadi Lebih Mudah
                    </h1>

                    <p class="mt-6 text-lg leading-relaxed text-gray-600 dark:text-gray-400">
                        Dari pencatatan manual ke digital. Kelola pelanggan, tagihan, pembayaran, insentif karyawan,
                        dan laporan keuangan — semua dalam satu dashboard. Dukung multi-perusahaan dengan akses yang fleksibel.
                    </p>

                    <div class="mt-8 flex flex-wrap gap-4">
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-sky-500 to-indigo-600 px-7 py-3.5 text-base font-semibold text-white shadow-lg shadow-sky-500/25 transition hover:shadow-xl hover:shadow-sky-500/30 hover:from-sky-600 hover:to-indigo-700"
                        >
                            <i class="fas fa-play-circle"></i>
                            Mulai Sekarang Gratis
                        </Link>
                        <a
                            href="#fitur"
                            class="inline-flex items-center gap-2 rounded-xl border border-gray-300 px-7 py-3.5 text-base font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                        >
                            <i class="fas fa-circle-info"></i>
                            Pelajari Fitur
                        </a>
                    </div>

                    <!-- Stats -->
                    <div class="mt-10 flex flex-wrap gap-8">
                        <div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">100%</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Digital</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">24/7</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Akses Realtime</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">Multi</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Perusahaan</div>
                        </div>
                    </div>
                </div>

                <!-- Right: Illustration -->
                <div class="mt-12 hidden lg:mt-0 lg:flex lg:flex-1 lg:justify-end">
                    <div class="relative">
                        <!-- Dashboard Mockup -->
                        <div class="w-[500px] rounded-2xl border border-gray-200 bg-white p-3 shadow-2xl dark:border-gray-700 dark:bg-gray-800">
                            <!-- Browser Bar -->
                            <div class="mb-3 flex items-center gap-2 px-1">
                                <div class="h-3 w-3 rounded-full bg-red-400"></div>
                                <div class="h-3 w-3 rounded-full bg-yellow-400"></div>
                                <div class="h-3 w-3 rounded-full bg-green-400"></div>
                                <div class="ml-3 flex-1 rounded-full bg-gray-100 px-3 py-1 text-xs text-gray-400 dark:bg-gray-700">app.rt-rw-net.id</div>
                            </div>
                            <!-- Content -->
                            <div class="space-y-3">
                                <!-- Stats Row -->
                                <div class="grid grid-cols-3 gap-2">
                                    <div class="rounded-lg bg-gradient-to-br from-sky-50 to-sky-100 p-3 dark:from-sky-900/30 dark:to-sky-800/30">
                                        <div class="text-xs font-medium text-sky-600 dark:text-sky-400"><i class="fas fa-users mr-1"></i> Pelanggan</div>
                                        <div class="mt-1 text-xl font-bold text-gray-900 dark:text-white">1,248</div>
                                    </div>
                                    <div class="rounded-lg bg-gradient-to-br from-emerald-50 to-emerald-100 p-3 dark:from-emerald-900/30 dark:to-emerald-800/30">
                                        <div class="text-xs font-medium text-emerald-600 dark:text-emerald-400"><i class="fas fa-money-bill mr-1"></i> Pendapatan</div>
                                        <div class="mt-1 text-xl font-bold text-gray-900 dark:text-white">Rp 42M</div>
                                    </div>
                                    <div class="rounded-lg bg-gradient-to-br from-amber-50 to-amber-100 p-3 dark:from-amber-900/30 dark:to-amber-800/30">
                                        <div class="text-xs font-medium text-amber-600 dark:text-amber-400"><i class="fas fa-clock mr-1"></i> Piutang</div>
                                        <div class="mt-1 text-xl font-bold text-gray-900 dark:text-white">Rp 5.2M</div>
                                    </div>
                                </div>
                                <!-- Table -->
                                <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-900">
                                    <div class="mb-2 text-xs font-semibold text-gray-500">Tagihan Terbaru</div>
                                    <div class="space-y-1.5">
                                        <div v-for="i in 4" :key="i" class="flex items-center justify-between rounded bg-white px-3 py-2 text-xs shadow-sm dark:bg-gray-800">
                                            <div class="flex items-center gap-2">
                                                <div class="h-6 w-6 rounded-full bg-sky-100 dark:bg-sky-900"></div>
                                                <span class="font-medium text-gray-700 dark:text-gray-300">Pelanggan {{ i }}</span>
                                            </div>
                                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400">Lunas</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Floating element -->
                        <div class="absolute -bottom-6 -left-6 rounded-xl border border-gray-200 bg-white p-3 shadow-lg dark:border-gray-700 dark:bg-gray-800">
                            <div class="flex items-center gap-2">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/50">
                                    <i class="fas fa-check text-emerald-600 dark:text-emerald-400 text-sm"></i>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold text-gray-900 dark:text-white">Insentif Bulan Ini</div>
                                    <div class="text-sm font-bold text-emerald-600 dark:text-emerald-400">Rp 2.450.000</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== FEATURES SECTION ===== -->
        <section id="fitur" class="bg-gray-50 py-20 dark:bg-gray-800/50">
            <div class="mx-auto max-w-7xl px-4 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white sm:text-4xl">
                        Semua yang Anda Butuhkan dalam
                        <span class="bg-gradient-to-r from-sky-500 to-indigo-600 bg-clip-text text-transparent">Satu Platform</span>
                    </h2>
                    <p class="mt-4 text-lg text-gray-600 dark:text-gray-400">
                        Fitur lengkap untuk mengelola bisnis RT/RW Net dari hulu ke hilir.
                    </p>
                </div>

                <div class="mt-16 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <!-- Feature 1 -->
                    <div class="group rounded-2xl border border-gray-200 bg-white p-6 transition hover:border-sky-200 hover:shadow-lg hover:shadow-sky-500/5 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-sky-800">
                        <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-sky-500 to-sky-600 shadow-lg shadow-sky-500/25">
                            <i class="fas fa-user-group text-xl text-white"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Manajemen Pelanggan</h3>
                        <p class="mt-2 text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                            Data pelanggan lengkap, histori paket, status aktif/nonaktif, dan pencarian cepat.
                        </p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="group rounded-2xl border border-gray-200 bg-white p-6 transition hover:border-sky-200 hover:shadow-lg hover:shadow-sky-500/5 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-sky-800">
                        <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 shadow-lg shadow-emerald-500/25">
                            <i class="fas fa-file-invoice text-xl text-white"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Tagihan Otomatis</h3>
                        <p class="mt-2 text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                            Generate invoice otomatis tiap bulan. Tracking status: belum bayar, menunggu konfirmasi, lunas, kadaluarsa.
                        </p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="group rounded-2xl border border-gray-200 bg-white p-6 transition hover:border-sky-200 hover:shadow-lg hover:shadow-sky-500/5 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-sky-800">
                        <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-amber-500 to-amber-600 shadow-lg shadow-amber-500/25">
                            <i class="fas fa-wallet text-xl text-white"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Pembayaran Tunai & Non-Tunai</h3>
                        <p class="mt-2 text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                            Input pembayaran cash, upload bukti transfer, verifikasi otomatis. Semua tercatat rapi.
                        </p>
                    </div>

                    <!-- Feature 4 -->
                    <div class="group rounded-2xl border border-gray-200 bg-white p-6 transition hover:border-sky-200 hover:shadow-lg hover:shadow-sky-500/5 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-sky-800">
                        <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 shadow-lg shadow-purple-500/25">
                            <i class="fas fa-hand-holding-dollar text-xl text-white"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Insentif Karyawan</h3>
                        <p class="mt-2 text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                            Hitung otomatis insentif penagih (flat / persentase). Approval & pencairan transparan.
                        </p>
                    </div>

                    <!-- Feature 5 -->
                    <div class="group rounded-2xl border border-gray-200 bg-white p-6 transition hover:border-sky-200 hover:shadow-lg hover:shadow-sky-500/5 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-sky-800">
                        <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-rose-500 to-rose-600 shadow-lg shadow-rose-500/25">
                            <i class="fas fa-chart-pie text-xl text-white"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Laporan Lengkap</h3>
                        <p class="mt-2 text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                            Laporan harian, mingguan, bulanan, tahunan. Export PDF & Excel. Dashboard realtime.
                        </p>
                    </div>

                    <!-- Feature 6 -->
                    <div class="group rounded-2xl border border-gray-200 bg-white p-6 transition hover:border-sky-200 hover:shadow-lg hover:shadow-sky-500/5 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-sky-800">
                        <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-600 shadow-lg shadow-indigo-500/25">
                            <i class="fas fa-building text-xl text-white"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Multi-Perusahaan</h3>
                        <p class="mt-2 text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                            Kelola banyak perusahaan dalam satu akun. RBAC granular dengan permission dinamis.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== HOW IT WORKS ===== -->
        <section id="cara-kerja" class="py-20">
            <div class="mx-auto max-w-7xl px-4 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white sm:text-4xl">
                        Bagaimana Cara Kerjanya?
                    </h2>
                    <p class="mt-4 text-lg text-gray-600 dark:text-gray-400">
                        Empat langkah mudah untuk memulai digitalisasi bisnis RT/RW Net Anda.
                    </p>
                </div>

                <div class="mt-16 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Step 1 -->
                    <div class="relative text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-500 to-indigo-600 text-2xl font-bold text-white shadow-lg shadow-sky-500/25">
                            1
                        </div>
                        <h3 class="mt-5 text-lg font-bold text-gray-900 dark:text-white">Daftar Akun</h3>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Buat akun perusahaan Anda dalam 2 menit. Gratis, tanpa kartu kredit.</p>
                    </div>

                    <!-- Step 2 -->
                    <div class="relative text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-500 to-indigo-600 text-2xl font-bold text-white shadow-lg shadow-sky-500/25">
                            2
                        </div>
                        <h3 class="mt-5 text-lg font-bold text-gray-900 dark:text-white">Input Data</h3>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Masukkan data pelanggan, paket, dan karyawan. Impor dari Excel juga bisa.</p>
                    </div>

                    <!-- Step 3 -->
                    <div class="relative text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-500 to-indigo-600 text-2xl font-bold text-white shadow-lg shadow-sky-500/25">
                            3
                        </div>
                        <h3 class="mt-5 text-lg font-bold text-gray-900 dark:text-white">Jalankan Operasional</h3>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Generate tagihan, catat pembayaran, track status, hitung insentif — otomatis.</p>
                    </div>

                    <!-- Step 4 -->
                    <div class="relative text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-500 to-indigo-600 text-2xl font-bold text-white shadow-lg shadow-sky-500/25">
                            4
                        </div>
                        <h3 class="mt-5 text-lg font-bold text-gray-900 dark:text-white">Pantau & Analisa</h3>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Dashboard realtime & laporan lengkap untuk keputusan bisnis yang lebih baik.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== CTA SECTION ===== -->
        <section class="relative overflow-hidden bg-gradient-to-br from-sky-600 to-indigo-700 py-20">
            <div class="absolute inset-0 -z-10">
                <div class="absolute -left-20 -top-20 h-[300px] w-[300px] rounded-full bg-white/10 blur-[80px]"></div>
                <div class="absolute -right-20 -bottom-20 h-[300px] w-[300px] rounded-full bg-white/10 blur-[80px]"></div>
            </div>

            <div class="mx-auto max-w-4xl px-4 text-center lg:px-8">
                <h2 class="text-3xl font-extrabold text-white sm:text-4xl">
                    Siap Digitalisasi Bisnis RT/RW Net Anda?
                </h2>
                <p class="mx-auto mt-4 max-w-2xl text-lg text-sky-100">
                    Bergabunglah dengan ratusan penyedia RT/RW Net yang sudah beralih dari pencatatan manual ke sistem digital. Mulai gratis, upgrade sesuai kebutuhan.
                </p>
                <div class="mt-8 flex flex-wrap justify-center gap-4">
                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="inline-flex items-center gap-2 rounded-xl bg-white px-8 py-4 text-base font-bold text-indigo-700 shadow-lg transition hover:bg-gray-100"
                    >
                        <i class="fas fa-rocket"></i>
                        Mulai Gratis Sekarang
                    </Link>
                    <a
                        href="#kontak"
                        class="inline-flex items-center gap-2 rounded-xl border-2 border-white/30 px-8 py-4 text-base font-bold text-white transition hover:bg-white/10"
                    >
                        <i class="fas fa-headset"></i>
                        Hubungi Kami
                    </a>
                </div>
            </div>
        </section>

        <!-- ===== FOOTER ===== -->
        <footer id="kontak" class="border-t border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <div class="mx-auto max-w-7xl px-4 py-12 lg:px-8">
                <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Brand -->
                    <div>
                        <div class="flex items-center gap-2">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-sky-500 to-indigo-600">
                                <i class="fas fa-wifi text-lg text-white"></i>
                            </div>
                            <span class="text-xl font-bold text-gray-900 dark:text-white">
                                RT<span class="text-sky-500">/</span>RW Net
                            </span>
                        </div>
                        <p class="mt-3 text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                            Platform ERP modern untuk penyedia layanan internet RT/RW. Dari pencatatan manual ke digital.
                        </p>
                    </div>

                    <!-- Fitur Links -->
                    <div>
                        <h4 class="text-sm font-bold uppercase tracking-wider text-gray-900 dark:text-white">Fitur</h4>
                        <ul class="mt-4 space-y-2">
                            <li><a href="#" class="text-sm text-gray-600 transition hover:text-sky-600 dark:text-gray-400 dark:hover:text-sky-400">Manajemen Pelanggan</a></li>
                            <li><a href="#" class="text-sm text-gray-600 transition hover:text-sky-600 dark:text-gray-400 dark:hover:text-sky-400">Penagihan Otomatis</a></li>
                            <li><a href="#" class="text-sm text-gray-600 transition hover:text-sky-600 dark:text-gray-400 dark:hover:text-sky-400">Insentif Karyawan</a></li>
                            <li><a href="#" class="text-sm text-gray-600 transition hover:text-sky-600 dark:text-gray-400 dark:hover:text-sky-400">Laporan & Dashboard</a></li>
                        </ul>
                    </div>

                    <!-- Perusahaan Links -->
                    <div>
                        <h4 class="text-sm font-bold uppercase tracking-wider text-gray-900 dark:text-white">Perusahaan</h4>
                        <ul class="mt-4 space-y-2">
                            <li><a href="#" class="text-sm text-gray-600 transition hover:text-sky-600 dark:text-gray-400 dark:hover:text-sky-400">Tentang Kami</a></li>
                            <li><a href="#" class="text-sm text-gray-600 transition hover:text-sky-600 dark:text-gray-400 dark:hover:text-sky-400">Harga</a></li>
                            <li><a href="#" class="text-sm text-gray-600 transition hover:text-sky-600 dark:text-gray-400 dark:hover:text-sky-400">Blog</a></li>
                            <li><a href="#" class="text-sm text-gray-600 transition hover:text-sky-600 dark:text-gray-400 dark:hover:text-sky-400">Karir</a></li>
                        </ul>
                    </div>

                    <!-- Kontak -->
                    <div>
                        <h4 class="text-sm font-bold uppercase tracking-wider text-gray-900 dark:text-white">Kontak</h4>
                        <ul class="mt-4 space-y-2">
                            <li class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                <i class="fas fa-envelope w-4 text-sky-500"></i> support@rt-rw-net.id
                            </li>
                            <li class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                <i class="fas fa-phone w-4 text-sky-500"></i> (021) 1234-5678
                            </li>
                            <li class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                <i class="fas fa-map-marker-alt w-4 text-sky-500"></i> Jakarta, Indonesia
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Bottom -->
                <div class="mt-10 flex flex-col items-center justify-between gap-4 border-t border-gray-200 pt-6 dark:border-gray-700 sm:flex-row">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        &copy; {{ new Date().getFullYear() }} RT/RW Net. All rights reserved.
                    </p>
                    <div class="flex gap-4">
                        <a href="#" class="text-gray-400 transition hover:text-sky-500 dark:hover:text-sky-400">
                            <i class="fab fa-facebook text-lg"></i>
                        </a>
                        <a href="#" class="text-gray-400 transition hover:text-sky-500 dark:hover:text-sky-400">
                            <i class="fab fa-twitter text-lg"></i>
                        </a>
                        <a href="#" class="text-gray-400 transition hover:text-sky-500 dark:hover:text-sky-400">
                            <i class="fab fa-instagram text-lg"></i>
                        </a>
                        <a href="#" class="text-gray-400 transition hover:text-sky-500 dark:hover:text-sky-400">
                            <i class="fab fa-youtube text-lg"></i>
                        </a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

                            <path
                                d="M61.8548 14.6253C61.8778 14.7102 61.8895 14.7978 61.8897 14.8858V28.5615C61.8898 28.737 61.8434 28.9095 61.7554 29.0614C61.6675 29.2132 61.5409 29.3392 61.3887 29.4265L49.9104 36.0351V49.1337C49.9104 49.4902 49.7209 49.8192 49.4118 49.9987L25.4519 63.7916C25.3971 63.8227 25.3372 63.8427 25.2774 63.8639C25.255 63.8714 25.2338 63.8851 25.2101 63.8913C25.0426 63.9354 24.8666 63.9354 24.6991 63.8913C24.6716 63.8838 24.6467 63.8689 24.6205 63.8589C24.5657 63.8389 24.5084 63.8215 24.456 63.7916L0.501061 49.9987C0.348882 49.9113 0.222437 49.7853 0.134469 49.6334C0.0465019 49.4816 0.000120578 49.3092 0 49.1337L0 8.10652C0 8.01678 0.0124642 7.92953 0.0348998 7.84477C0.0423783 7.8161 0.0598282 7.78993 0.0697995 7.76126C0.0884958 7.70891 0.105946 7.65531 0.133367 7.6067C0.152063 7.5743 0.179485 7.54812 0.20192 7.51821C0.230588 7.47832 0.256763 7.43719 0.290416 7.40229C0.319084 7.37362 0.356476 7.35243 0.388883 7.32751C0.425029 7.29759 0.457436 7.26518 0.498568 7.2415L12.4779 0.345059C12.6296 0.257786 12.8015 0.211853 12.9765 0.211853C13.1515 0.211853 13.3234 0.257786 13.475 0.345059L25.4531 7.2415H25.4556C25.4955 7.26643 25.5292 7.29759 25.5653 7.32626C25.5977 7.35119 25.6339 7.37362 25.6625 7.40104C25.6974 7.43719 25.7224 7.47832 25.7523 7.51821C25.7735 7.54812 25.8021 7.5743 25.8196 7.6067C25.8483 7.65656 25.8645 7.70891 25.8844 7.76126C25.8944 7.78993 25.9118 7.8161 25.9193 7.84602C25.9423 7.93096 25.954 8.01853 25.9542 8.10652V33.7317L35.9355 27.9844V14.8846C35.9355 14.7973 35.948 14.7088 35.9704 14.6253C35.9792 14.5954 35.9954 14.5692 36.0053 14.5405C36.0253 14.4882 36.0427 14.4346 36.0702 14.386C36.0888 14.3536 36.1163 14.3274 36.1375 14.2975C36.1674 14.2576 36.1923 14.2165 36.2272 14.1816C36.2559 14.1529 36.292 14.1317 36.3244 14.1068C36.3618 14.0769 36.3942 14.0445 36.4341 14.0208L48.4147 7.12434C48.5663 7.03694 48.7383 6.99094 48.9133 6.99094C49.0883 6.99094 49.2602 7.03694 49.4118 7.12434L61.3899 14.0208C61.4323 14.0457 61.4647 14.0769 61.5021 14.1055C61.5333 14.1305 61.5694 14.1529 61.5981 14.1803C61.633 14.2165 61.6579 14.2576 61.6878 14.2975C61.7103 14.3274 61.7377 14.3536 61.7551 14.386C61.7838 14.4346 61.8 14.4882 61.8199 14.5405C61.8312 14.5692 61.8474 14.5954 61.8548 14.6253ZM59.893 27.9844V16.6121L55.7013 19.0252L49.9104 22.3593V33.7317L59.8942 27.9844H59.893ZM47.9149 48.5566V37.1768L42.2187 40.4299L25.953 49.7133V61.2003L47.9149 48.5566ZM1.99677 9.83281V48.5566L23.9562 61.199V49.7145L12.4841 43.2219L12.4804 43.2194L12.4754 43.2169C12.4368 43.1945 12.4044 43.1621 12.3682 43.1347C12.3371 43.1097 12.3009 43.0898 12.2735 43.0624L12.271 43.0586C12.2386 43.0275 12.2162 42.9888 12.1887 42.9539C12.1638 42.9203 12.1339 42.8916 12.114 42.8567L12.1127 42.853C12.0903 42.8156 12.0766 42.7707 12.0604 42.7283C12.0442 42.6909 12.023 42.656 12.013 42.6161C12.0005 42.5688 11.998 42.5177 11.9931 42.4691C11.9881 42.4317 11.9781 42.3943 11.9781 42.3569V15.5801L6.18848 12.2446L1.99677 9.83281ZM12.9777 2.36177L2.99764 8.10652L12.9752 13.8513L22.9541 8.10527L12.9752 2.36177H12.9777ZM18.1678 38.2138L23.9574 34.8809V9.83281L19.7657 12.2459L13.9749 15.5801V40.6281L18.1678 38.2138ZM48.9133 9.14105L38.9344 14.8858L48.9133 20.6305L58.8909 14.8846L48.9133 9.14105ZM47.9149 22.3593L42.124 19.0252L37.9323 16.6121V27.9844L43.7219 31.3174L47.9149 33.7317V22.3593ZM24.9533 47.987L39.59 39.631L46.9065 35.4555L36.9352 29.7145L25.4544 36.3242L14.9907 42.3482L24.9533 47.987Z"
                                fill="currentColor"
                            />
                        </svg>
                    </div>
                    <nav v-if="canLogin" class="-mx-3 flex flex-1 justify-end">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="route('dashboard')"
                            class="rounded-md px-3 py-2 text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white"
                        >
                            Dashboard
                        </Link>

                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="rounded-md px-3 py-2 text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white"
                            >
                                Log in
                            </Link>

                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="rounded-md px-3 py-2 text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white"
                            >
                                Register
                            </Link>
                        </template>
                    </nav>
                </header>

                <main class="mt-6">
                    <div class="grid gap-6 lg:grid-cols-2 lg:gap-8">
                        <a
                            href="https://laravel.com/docs"
                            id="docs-card"
                            class="flex flex-col items-start gap-6 overflow-hidden rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] transition duration-300 hover:text-black/70 hover:ring-black/20 focus:outline-none focus-visible:ring-[#FF2D20] md:row-span-3 lg:p-10 lg:pb-10 dark:bg-zinc-900 dark:ring-zinc-800 dark:hover:text-white/70 dark:hover:ring-zinc-700 dark:focus-visible:ring-[#FF2D20]"
                        >
                            <div
                                id="screenshot-container"
                                class="relative flex w-full flex-1 items-stretch"
                            >
                                <img
                                    src="https://laravel.com/assets/img/welcome/docs-light.svg"
                                    alt="Laravel documentation screenshot"
                                    class="aspect-video h-full w-full flex-1 rounded-[10px] object-cover object-top drop-shadow-[0px_4px_34px_rgba(0,0,0,0.06)] dark:hidden"
                                    @error="handleImageError"
                                />
                                <img
                                    src="https://laravel.com/assets/img/welcome/docs-dark.svg"
                                    alt="Laravel documentation screenshot"
                                    class="hidden aspect-video h-full w-full flex-1 rounded-[10px] object-cover object-top drop-shadow-[0px_4px_34px_rgba(0,0,0,0.25)] dark:block"
                                />
                                <div
                                    class="absolute -bottom-16 -left-16 h-40 w-[calc(100%+8rem)] bg-gradient-to-b from-transparent via-white to-white dark:via-zinc-900 dark:to-zinc-900"
                                ></div>
                            </div>

                            <div
                                class="relative flex items-center gap-6 lg:items-end"
                            >
                                <div
                                    id="docs-card-content"
                                    class="flex items-start gap-6 lg:flex-col"
                                >
                                    <div
                                        class="flex size-12 shrink-0 items-center justify-center rounded-full bg-[#FF2D20]/10 sm:size-16"
                                    >
                                        <svg
                                            class="size-5 sm:size-6"
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                fill="#FF2D20"
                                                d="M23 4a1 1 0 0 0-1.447-.894L12.224 7.77a.5.5 0 0 1-.448 0L2.447 3.106A1 1 0 0 0 1 4v13.382a1.99 1.99 0 0 0 1.105 1.79l9.448 4.728c.14.065.293.1.447.1.154-.005.306-.04.447-.105l9.453-4.724a1.99 1.99 0 0 0 1.1-1.789V4ZM3 6.023a.25.25 0 0 1 .362-.223l7.5 3.75a.251.251 0 0 1 .138.223v11.2a.25.25 0 0 1-.362.224l-7.5-3.75a.25.25 0 0 1-.138-.22V6.023Zm18 11.2a.25.25 0 0 1-.138.224l-7.5 3.75a.249.249 0 0 1-.329-.099.249.249 0 0 1-.033-.12V9.772a.251.251 0 0 1 .138-.224l7.5-3.75a.25.25 0 0 1 .362.224v11.2Z"
                                            />
                                            <path
                                                fill="#FF2D20"
                                                d="m3.55 1.893 8 4.048a1.008 1.008 0 0 0 .9 0l8-4.048a1 1 0 0 0-.9-1.785l-7.322 3.706a.506.506 0 0 1-.452 0L4.454.108a1 1 0 0 0-.9 1.785H3.55Z"
                                            />
                                        </svg>
                                    </div>

                                    <div class="pt-3 sm:pt-5 lg:pt-0">
                                        <h2
                                            class="text-xl font-semibold text-black dark:text-white"
                                        >
                                            Documentation
                                        </h2>

                                        <p class="mt-4 text-sm/relaxed">
                                            Laravel has wonderful documentation
                                            covering every aspect of the
                                            framework. Whether you are a
                                            newcomer or have prior experience
                                            with Laravel, we recommend reading
                                            our documentation from beginning to
                                            end.
                                        </p>
                                    </div>
                                </div>

                                <svg
                                    class="size-6 shrink-0 stroke-[#FF2D20]"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.5"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M4.5 12h15m0 0l-6.75-6.75M19.5 12l-6.75 6.75"
                                    />
                                </svg>
                            </div>
                        </a>

                        <a
                            href="https://laracasts.com"
                            class="flex items-start gap-4 rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] transition duration-300 hover:text-black/70 hover:ring-black/20 focus:outline-none focus-visible:ring-[#FF2D20] lg:pb-10 dark:bg-zinc-900 dark:ring-zinc-800 dark:hover:text-white/70 dark:hover:ring-zinc-700 dark:focus-visible:ring-[#FF2D20]"
                        >
                            <div
                                class="flex size-12 shrink-0 items-center justify-center rounded-full bg-[#FF2D20]/10 sm:size-16"
                            >
                                <svg
                                    class="size-5 sm:size-6"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <g fill="#FF2D20">
                                        <path
                                            d="M24 8.25a.5.5 0 0 0-.5-.5H.5a.5.5 0 0 0-.5.5v12a2.5 2.5 0 0 0 2.5 2.5h19a2.5 2.5 0 0 0 2.5-2.5v-12Zm-7.765 5.868a1.221 1.221 0 0 1 0 2.264l-6.626 2.776A1.153 1.153 0 0 1 8 18.123v-5.746a1.151 1.151 0 0 1 1.609-1.035l6.626 2.776ZM19.564 1.677a.25.25 0 0 0-.177-.427H15.6a.106.106 0 0 0-.072.03l-4.54 4.543a.25.25 0 0 0 .177.427h3.783c.027 0 .054-.01.073-.03l4.543-4.543ZM22.071 1.318a.047.047 0 0 0-.045.013l-4.492 4.492a.249.249 0 0 0 .038.385.25.25 0 0 0 .14.042h5.784a.5.5 0 0 0 .5-.5v-2a2.5 2.5 0 0 0-1.925-2.432ZM13.014 1.677a.25.25 0 0 0-.178-.427H9.101a.106.106 0 0 0-.073.03l-4.54 4.543a.25.25 0 0 0 .177.427H8.4a.106.106 0 0 0 .073-.03l4.54-4.543ZM6.513 1.677a.25.25 0 0 0-.177-.427H2.5A2.5 2.5 0 0 0 0 3.75v2a.5.5 0 0 0 .5.5h1.4a.106.106 0 0 0 .073-.03l4.54-4.543Z"
                                        />
                                    </g>
                                </svg>
                            </div>

                            <div class="pt-3 sm:pt-5">
                                <h2
                                    class="text-xl font-semibold text-black dark:text-white"
                                >
                                    Laracasts
                                </h2>

                                <p class="mt-4 text-sm/relaxed">
                                    Laracasts offers thousands of video
                                    tutorials on Laravel, PHP, and JavaScript
                                    development. Check them out, see for
                                    yourself, and massively level up your
                                    development skills in the process.
                                </p>
                            </div>

                            <svg
                                class="size-6 shrink-0 self-center stroke-[#FF2D20]"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M4.5 12h15m0 0l-6.75-6.75M19.5 12l-6.75 6.75"
                                />
                            </svg>
                        </a>

                        <a
                            href="https://laravel-news.com"
                            class="flex items-start gap-4 rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] transition duration-300 hover:text-black/70 hover:ring-black/20 focus:outline-none focus-visible:ring-[#FF2D20] lg:pb-10 dark:bg-zinc-900 dark:ring-zinc-800 dark:hover:text-white/70 dark:hover:ring-zinc-700 dark:focus-visible:ring-[#FF2D20]"
                        >
                            <div
                                class="flex size-12 shrink-0 items-center justify-center rounded-full bg-[#FF2D20]/10 sm:size-16"
                            >
                                <svg
                                    class="size-5 sm:size-6"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <g fill="#FF2D20">
                                        <path
                                            d="M8.75 4.5H5.5c-.69 0-1.25.56-1.25 1.25v4.75c0 .69.56 1.25 1.25 1.25h3.25c.69 0 1.25-.56 1.25-1.25V5.75c0-.69-.56-1.25-1.25-1.25Z"
                                        />
                                        <path
                                            d="M24 10a3 3 0 0 0-3-3h-2V2.5a2 2 0 0 0-2-2H2a2 2 0 0 0-2 2V20a3.5 3.5 0 0 0 3.5 3.5h17A3.5 3.5 0 0 0 24 20V10ZM3.5 21.5A1.5 1.5 0 0 1 2 20V3a.5.5 0 0 1 .5-.5h14a.5.5 0 0 1 .5.5v17c0 .295.037.588.11.874a.5.5 0 0 1-.484.625L3.5 21.5ZM22 20a1.5 1.5 0 1 1-3 0V9.5a.5.5 0 0 1 .5-.5H21a1 1 0 0 1 1 1v10Z"
                                        />
                                        <path
                                            d="M12.751 6.047h2a.75.75 0 0 1 .75.75v.5a.75.75 0 0 1-.75.75h-2A.75.75 0 0 1 12 7.3v-.5a.75.75 0 0 1 .751-.753ZM12.751 10.047h2a.75.75 0 0 1 .75.75v.5a.75.75 0 0 1-.75.75h-2A.75.75 0 0 1 12 11.3v-.5a.75.75 0 0 1 .751-.753ZM4.751 14.047h10a.75.75 0 0 1 .75.75v.5a.75.75 0 0 1-.75.75h-10A.75.75 0 0 1 4 15.3v-.5a.75.75 0 0 1 .751-.753ZM4.75 18.047h7.5a.75.75 0 0 1 .75.75v.5a.75.75 0 0 1-.75.75h-7.5A.75.75 0 0 1 4 19.3v-.5a.75.75 0 0 1 .75-.753Z"
                                        />
                                    </g>
                                </svg>
                            </div>

                            <div class="pt-3 sm:pt-5">
                                <h2
                                    class="text-xl font-semibold text-black dark:text-white"
                                >
                                    Laravel News
                                </h2>

                                <p class="mt-4 text-sm/relaxed">
                                    Laravel News is a community driven portal
                                    and newsletter aggregating all of the latest
                                    and most important news in the Laravel
                                    ecosystem, including new package releases
                                    and tutorials.
                                </p>
                            </div>

                            <svg
                                class="size-6 shrink-0 self-center stroke-[#FF2D20]"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M4.5 12h15m0 0l-6.75-6.75M19.5 12l-6.75 6.75"
                                />
                            </svg>
                        </a>

                        <div
                            class="flex items-start gap-4 rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] lg:pb-10 dark:bg-zinc-900 dark:ring-zinc-800"
                        >
                            <div
                                class="flex size-12 shrink-0 items-center justify-center rounded-full bg-[#FF2D20]/10 sm:size-16"
                            >
                                <svg
                                    class="size-5 sm:size-6"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <g fill="#FF2D20">
                                        <path
                                            d="M16.597 12.635a.247.247 0 0 0-.08-.237 2.234 2.234 0 0 1-.769-1.68c.001-.195.03-.39.084-.578a.25.25 0 0 0-.09-.267 8.8 8.8 0 0 0-4.826-1.66.25.25 0 0 0-.268.181 2.5 2.5 0 0 1-2.4 1.824.045.045 0 0 0-.045.037 12.255 12.255 0 0 0-.093 3.86.251.251 0 0 0 .208.214c2.22.366 4.367 1.08 6.362 2.118a.252.252 0 0 0 .32-.079 10.09 10.09 0 0 0 1.597-3.733ZM13.616 17.968a.25.25 0 0 0-.063-.407A19.697 19.697 0 0 0 8.91 15.98a.25.25 0 0 0-.287.325c.151.455.334.898.548 1.328.437.827.981 1.594 1.619 2.28a.249.249 0 0 0 .32.044 29.13 29.13 0 0 0 2.506-1.99ZM6.303 14.105a.25.25 0 0 0 .265-.274 13.048 13.048 0 0 1 .205-4.045.062.062 0 0 0-.022-.07 2.5 2.5 0 0 1-.777-.982.25.25 0 0 0-.271-.149 11 11 0 0 0-5.6 2.815.255.255 0 0 0-.075.163c-.008.135-.02.27-.02.406.002.8.084 1.598.246 2.381a.25.25 0 0 0 .303.193 19.924 19.924 0 0 1 5.746-.438ZM9.228 20.914a.25.25 0 0 0 .1-.393 11.53 11.53 0 0 1-1.5-2.22 12.238 12.238 0 0 1-.91-2.465.248.248 0 0 0-.22-.187 18.876 18.876 0 0 0-5.69.33.249.249 0 0 0-.179.336c.838 2.142 2.272 4 4.132 5.353a.254.254 0 0 0 .15.048c1.41-.01 2.807-.282 4.117-.802ZM18.93 12.957l-.005-.008a.25.25 0 0 0-.268-.082 2.21 2.21 0 0 1-.41.081.25.25 0 0 0-.217.2c-.582 2.66-2.127 5.35-5.75 7.843a.248.248 0 0 0-.09.299.25.25 0 0 0 .065.091 28.703 28.703 0 0 0 2.662 2.12.246.246 0 0 0 .209.037c2.579-.701 4.85-2.242 6.456-4.378a.25.25 0 0 0 .048-.189 13.51 13.51 0 0 0-2.7-6.014ZM5.702 7.058a.254.254 0 0 0 .2-.165A2.488 2.488 0 0 1 7.98 5.245a.093.093 0 0 0 .078-.062 19.734 19.734 0 0 1 3.055-4.74.25.25 0 0 0-.21-.41 12.009 12.009 0 0 0-10.4 8.558.25.25 0 0 0 .373.281 12.912 12.912 0 0 1 4.826-1.814ZM10.773 22.052a.25.25 0 0 0-.28-.046c-.758.356-1.55.635-2.365.833a.25.25 0 0 0-.022.48c1.252.43 2.568.65 3.893.65.1 0 .2 0 .3-.008a.25.25 0 0 0 .147-.444c-.526-.424-1.1-.917-1.673-1.465ZM18.744 8.436a.249.249 0 0 0 .15.228 2.246 2.246 0 0 1 1.352 2.054c0 .337-.08.67-.23.972a.25.25 0 0 0 .042.28l.007.009a15.016 15.016 0 0 1 2.52 4.6.25.25 0 0 0 .37.132.25.25 0 0 0 .096-.114c.623-1.464.944-3.039.945-4.63a12.005 12.005 0 0 0-5.78-10.258.25.25 0 0 0-.373.274c.547 2.109.85 4.274.901 6.453ZM9.61 5.38a.25.25 0 0 0 .08.31c.34.24.616.561.8.935a.25.25 0 0 0 .3.127.631.631 0 0 1 .206-.034c2.054.078 4.036.772 5.69 1.991a.251.251 0 0 0 .267.024c.046-.024.093-.047.141-.067a.25.25 0 0 0 .151-.23A29.98 29.98 0 0 0 15.957.764a.25.25 0 0 0-.16-.164 11.924 11.924 0 0 0-2.21-.518.252.252 0 0 0-.215.076A22.456 22.456 0 0 0 9.61 5.38Z"
                                        />
                                    </g>
                                </svg>
                            </div>

                            <div class="pt-3 sm:pt-5">
                                <h2
                                    class="text-xl font-semibold text-black dark:text-white"
                                >
                                    Vibrant Ecosystem
                                </h2>

                                <p class="mt-4 text-sm/relaxed">
                                    Laravel's robust library of first-party
                                    tools and libraries, such as
                                    <a
                                        href="https://forge.laravel.com"
                                        class="rounded-sm underline hover:text-black focus:outline-none focus-visible:ring-1 focus-visible:ring-[#FF2D20] dark:hover:text-white dark:focus-visible:ring-[#FF2D20]"
                                        >Forge</a
                                    >,
                                    <a
                                        href="https://vapor.laravel.com"
                                        class="rounded-sm underline hover:text-black focus:outline-none focus-visible:ring-1 focus-visible:ring-[#FF2D20] dark:hover:text-white"
                                        >Vapor</a
                                    >,
                                    <a
                                        href="https://nova.laravel.com"
                                        class="rounded-sm underline hover:text-black focus:outline-none focus-visible:ring-1 focus-visible:ring-[#FF2D20] dark:hover:text-white"
                                        >Nova</a
                                    >,
                                    <a
                                        href="https://envoyer.io"
                                        class="rounded-sm underline hover:text-black focus:outline-none focus-visible:ring-1 focus-visible:ring-[#FF2D20] dark:hover:text-white"
                                        >Envoyer</a
                                    >, and
                                    <a
                                        href="https://herd.laravel.com"
                                        class="rounded-sm underline hover:text-black focus:outline-none focus-visible:ring-1 focus-visible:ring-[#FF2D20] dark:hover:text-white"
                                        >Herd</a
                                    >
                                    help you take your projects to the next
                                    level. Pair them with powerful open source
                                    libraries like
                                    <a
                                        href="https://laravel.com/docs/billing"
                                        class="rounded-sm underline hover:text-black focus:outline-none focus-visible:ring-1 focus-visible:ring-[#FF2D20] dark:hover:text-white"
                                        >Cashier</a
                                    >,
                                    <a
                                        href="https://laravel.com/docs/dusk"
                                        class="rounded-sm underline hover:text-black focus:outline-none focus-visible:ring-1 focus-visible:ring-[#FF2D20] dark:hover:text-white"
                                        >Dusk</a
                                    >,
                                    <a
                                        href="https://laravel.com/docs/broadcasting"
                                        class="rounded-sm underline hover:text-black focus:outline-none focus-visible:ring-1 focus-visible:ring-[#FF2D20] dark:hover:text-white"
                                        >Echo</a
                                    >,
                                    <a
                                        href="https://laravel.com/docs/horizon"
                                        class="rounded-sm underline hover:text-black focus:outline-none focus-visible:ring-1 focus-visible:ring-[#FF2D20] dark:hover:text-white"
                                        >Horizon</a
                                    >,
                                    <a
                                        href="https://laravel.com/docs/sanctum"
                                        class="rounded-sm underline hover:text-black focus:outline-none focus-visible:ring-1 focus-visible:ring-[#FF2D20] dark:hover:text-white"
                                        >Sanctum</a
                                    >,
                                    <a
                                        href="https://laravel.com/docs/telescope"
                                        class="rounded-sm underline hover:text-black focus:outline-none focus-visible:ring-1 focus-visible:ring-[#FF2D20] dark:hover:text-white"
                                        >Telescope</a
                                    >, and more.
                                </p>
                            </div>
                        </div>
                    </div>
                </main>

                <footer
                    class="py-16 text-center text-sm text-black dark:text-white/70"
                >
                    Laravel v{{ laravelVersion }} (PHP v{{ phpVersion }})
                </footer>
            </div>
        </div>
    </div>
</template>
