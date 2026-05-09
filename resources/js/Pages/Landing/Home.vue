<script setup>
import { Link } from '@inertiajs/vue3';
import LandingLayout from '@/Layouts/LandingLayout.vue';

defineOptions({ layout: LandingLayout });
</script>

<template>
  <div>
    <!-- HERO -->
    <section class="relative overflow-hidden bg-gradient-to-br from-sky-50 via-white to-indigo-50 dark:from-gray-950 dark:via-gray-950 dark:to-indigo-950/30 transition-colors">
      <!-- Background blob -->
      <div class="absolute -top-40 -right-40 w-96 h-96 bg-sky-300/20 dark:bg-sky-500/10 rounded-full blur-3xl"></div>
      <div class="absolute -bottom-32 -left-32 w-80 h-80 bg-indigo-300/20 dark:bg-indigo-500/10 rounded-full blur-3xl"></div>

      <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 md:py-36">
        <div class="text-center max-w-3xl mx-auto">
          <h1 class="text-4xl sm:text-5xl md:text-6xl font-extrabold text-gray-900 dark:text-white leading-tight tracking-tight">
            ERP Modern untuk
            <span class="bg-gradient-to-r from-sky-500 to-indigo-600 bg-clip-text text-transparent">
              RT/RW Net
            </span>
          </h1>
          <p class="mt-6 text-lg text-gray-600 dark:text-gray-400 leading-relaxed max-w-2xl mx-auto">
            Kelola pelanggan, tagihan, pembayaran, dan insentif karyawan dalam satu platform.
            Dibangun khusus untuk bisnis RT/RW Net skala kecil hingga besar.
          </p>
          <div class="mt-10 flex flex-col sm:flex-row gap-3 justify-center">
            <Link href="/login-perusahaan" class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-sky-500 to-indigo-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl hover:scale-[1.02] active:scale-[0.98] transition-all">
              <i class="fas fa-rocket mr-2"></i> Mulai Sekarang
            </Link>
            <Link href="/tentang-kami" class="inline-flex items-center justify-center px-6 py-3 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-semibold rounded-xl border border-gray-300 dark:border-gray-700 hover:border-sky-500 dark:hover:border-sky-500 hover:text-sky-600 dark:hover:text-sky-400 transition-colors">
              <i class="fas fa-info-circle mr-2"></i> Pelajari Lebih Lanjut
            </Link>
          </div>
        </div>
      </div>
    </section>

    <!-- FEATURES -->
    <section class="py-20 md:py-28 bg-white dark:bg-gray-950 transition-colors">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white">Fitur Unggulan</h2>
          <p class="mt-3 text-gray-600 dark:text-gray-400 max-w-xl mx-auto">Semua yang Anda butuhkan untuk mengelola bisnis RT/RW Net dalam satu dashboard.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <!-- Card 1 -->
          <div class="group p-6 rounded-2xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:border-sky-300 dark:hover:border-sky-700 hover:shadow-lg hover:scale-[1.02] transition-all duration-300">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-sky-500 to-indigo-600 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
              <i class="fas fa-users text-white text-lg"></i>
            </div>
            <h3 class="font-semibold text-lg text-gray-900 dark:text-white mb-2">Manajemen Pelanggan</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">CRUD pelanggan lengkap, status aktif/nonaktif, riwayat paket, dan koordinat GPS.</p>
          </div>

          <!-- Card 2 -->
          <div class="group p-6 rounded-2xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:border-sky-300 dark:hover:border-sky-700 hover:shadow-lg hover:scale-[1.02] transition-all duration-300">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
              <i class="fas fa-file-invoice text-white text-lg"></i>
            </div>
            <h3 class="font-semibold text-lg text-gray-900 dark:text-white mb-2">Penagihan Otomatis</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">Generate invoice otomatis tiap bulan. Tracking status: belum bayar, menunggu konfirmasi, lunas, kadaluarsa.</p>
          </div>

          <!-- Card 3 -->
          <div class="group p-6 rounded-2xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:border-sky-300 dark:hover:border-sky-700 hover:shadow-lg hover:scale-[1.02] transition-all duration-300">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
              <i class="fas fa-hand-holding-usd text-white text-lg"></i>
            </div>
            <h3 class="font-semibold text-lg text-gray-900 dark:text-white mb-2">Pembayaran Tunai & Non-Tunai</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">Input pembayaran tunai oleh kolektor dan upload bukti transfer non-tunai. Semua tercatat rapi.</p>
          </div>

          <!-- Card 4 -->
          <div class="group p-6 rounded-2xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:border-sky-300 dark:hover:border-sky-700 hover:shadow-lg hover:scale-[1.02] transition-all duration-300">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-pink-500 to-rose-600 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
              <i class="fas fa-coins text-white text-lg"></i>
            </div>
            <h3 class="font-semibold text-lg text-gray-900 dark:text-white mb-2">Insentif Karyawan</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">Kalkulasi otomatis insentif kolektor dari pembayaran tunai & non-tunai. Riwayat lengkap & approval.</p>
          </div>

          <!-- Card 5 -->
          <div class="group p-6 rounded-2xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:border-sky-300 dark:hover:border-sky-700 hover:shadow-lg hover:scale-[1.02] transition-all duration-300">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
              <i class="fas fa-chart-line text-white text-lg"></i>
            </div>
            <h3 class="font-semibold text-lg text-gray-900 dark:text-white mb-2">Laporan & Dashboard</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">Laporan harian, mingguan, bulanan, tahunan. Dashboard real-time dengan notifikasi via WebSocket.</p>
          </div>

          <!-- Card 6 -->
          <div class="group p-6 rounded-2xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:border-sky-300 dark:hover:border-sky-700 hover:shadow-lg hover:scale-[1.02] transition-all duration-300">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
              <i class="fas fa-building-shield text-white text-lg"></i>
            </div>
            <h3 class="font-semibold text-lg text-gray-900 dark:text-white mb-2">Multi-Perusahaan</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">Kelola banyak perusahaan RT/RW Net dalam satu sistem. Data terisolasi, branding per tenant.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- STATS -->
    <section class="py-16 bg-gradient-to-r from-sky-500 to-indigo-600">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center text-white">
          <div>
            <div class="text-3xl md:text-4xl font-extrabold mb-1">500+</div>
            <div class="text-sm opacity-80">Perusahaan RT/RW Net</div>
          </div>
          <div>
            <div class="text-3xl md:text-4xl font-extrabold mb-1">50,000+</div>
            <div class="text-sm opacity-80">Pelanggan Terkelola</div>
          </div>
          <div>
            <div class="text-3xl md:text-4xl font-extrabold mb-1">99,9%</div>
            <div class="text-sm opacity-80">Uptime Server</div>
          </div>
          <div>
            <div class="text-3xl md:text-4xl font-extrabold mb-1">24/7</div>
            <div class="text-sm opacity-80">Dukungan</div>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA -->
    <section class="py-20 bg-gray-50 dark:bg-gray-900 transition-colors">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">Siap Mengelola Bisnis RT/RW Net Anda?</h2>
        <p class="text-lg text-gray-600 dark:text-gray-400 mb-8">Daftar sekarang dan dapatkan free trial 30 hari.</p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
          <Link href="/login-pelanggan" class="px-8 py-3 bg-gradient-to-r from-sky-500 to-indigo-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl hover:scale-[1.02] active:scale-[0.98] transition-all">
            <i class="fas fa-user-plus mr-2"></i> Daftar / Login Pelanggan
          </Link>
          <Link href="/hubungi-kami" class="px-8 py-3 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-semibold rounded-xl border border-gray-300 dark:border-gray-700 hover:border-sky-500 dark:hover:border-sky-500 transition-colors">
            <i class="fas fa-envelope mr-2"></i> Hubungi Kami
          </Link>
        </div>
      </div>
    </section>
  </div>
</template>