<script setup>
import { Link } from '@inertiajs/vue3';
import LandingLayout from '@/Layouts/LandingLayout.vue';

defineOptions({ layout: LandingLayout });
</script>

<template>
  <div>
    <!-- Hero -->
    <section class="relative overflow-hidden bg-gradient-to-br from-sky-50 via-white to-indigo-50 dark:from-gray-950 dark:via-gray-950 dark:to-indigo-950/30 transition-colors">
      <!-- Background blobs -->
      <div class="absolute -top-40 -right-40 w-96 h-96 bg-sky-300/20 dark:bg-sky-500/10 rounded-full blur-3xl"></div>
      <div class="absolute -bottom-32 -left-32 w-80 h-80 bg-indigo-300/20 dark:bg-indigo-500/10 rounded-full blur-3xl"></div>
      <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-60 h-60 bg-violet-300/10 dark:bg-violet-500/5 rounded-full blur-2xl"></div>

      <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 md:py-28">
        <div class="text-center max-w-3xl mx-auto">
          <!-- Icon -->
          <div class="w-20 h-20 mx-auto rounded-2xl bg-gradient-to-br from-sky-500 to-indigo-600 flex items-center justify-center mb-6 shadow-lg shadow-sky-500/25">
            <i class="fas fa-users text-white text-3xl"></i>
          </div>
          <h1 class="text-4xl md:text-5xl font-extrabold text-gray-900 dark:text-white mb-4">Tentang Kami</h1>
          <p class="text-lg text-gray-600 dark:text-gray-400 leading-relaxed">
            Kami hadir untuk membantu bisnis RT/RW Net di seluruh Indonesia mengelola operasional mereka dengan lebih efisien, transparan, dan modern.
          </p>
        </div>
      </div>
    </section>

    <!-- Breadcrumbs -->
    <div class="bg-white dark:bg-gray-950  transition-colors pt-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-8">
        <nav class="flex items-center gap-1.5 text-sm">
          <Link href="/" class="text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
            <i class="fas fa-home"></i>
          </Link>
          <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
          <Link href="/" class="text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Home</Link>
          <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
          <span class="text-gray-900 dark:text-white font-medium">Tentang Kami</span>
        </nav>
      </div>
    </div>
    
    <!-- Visi & Misi -->
    <section class="pb-16 bg-white dark:bg-gray-950 transition-colors">

      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid md:grid-cols-2 gap-12">
          <!-- Visi -->
          <div class="relative overflow-hidden p-8 rounded-2xl bg-gradient-to-br from-sky-50 to-indigo-50 dark:from-gray-900 dark:to-gray-900 border border-gray-200 dark:border-gray-800 group hover:shadow-lg transition-shadow">
            <div class="absolute -top-6 -right-6 w-24 h-24 bg-sky-300/20 dark:bg-sky-500/10 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative">
              <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-sky-500 to-indigo-600 flex items-center justify-center mb-5">
                <i class="fas fa-eye text-white text-xl"></i>
              </div>
              <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">Visi</h2>
              <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                Menjadi platform ERP nomor satu untuk bisnis RT/RW Net di Indonesia, memberdayakan pemilik usaha dengan teknologi yang mudah digunakan dan terjangkau.
              </p>
            </div>
          </div>

          <!-- Misi -->
          <div class="relative overflow-hidden p-8 rounded-2xl bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-gray-900 dark:to-gray-900 border border-gray-200 dark:border-gray-800 group hover:shadow-lg transition-shadow">
            <div class="absolute -top-6 -right-6 w-24 h-24 bg-emerald-300/20 dark:bg-emerald-500/10 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative">
              <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center mb-5">
                <i class="fas fa-bullseye text-white text-xl"></i>
              </div>
              <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">Misi</h2>
              <ul class="space-y-2 text-gray-600 dark:text-gray-400">
                <li class="flex items-start gap-2">
                  <i class="fas fa-check-circle text-emerald-500 mt-1 shrink-0"></i>
                  <span>Mendigitalisasi pencatatan operasional RT/RW Net dari manual menjadi otomatis.</span>
                </li>
                <li class="flex items-start gap-2">
                  <i class="fas fa-check-circle text-emerald-500 mt-1 shrink-0"></i>
                  <span>Menyediakan laporan keuangan dan performa bisnis secara real-time.</span>
                </li>
                <li class="flex items-start gap-2">
                  <i class="fas fa-check-circle text-emerald-500 mt-1 shrink-0"></i>
                  <span>Memberikan transparansi penuh dalam penagihan, pembayaran, dan insentif karyawan.</span>
                </li>
                <li class="flex items-start gap-2">
                  <i class="fas fa-check-circle text-emerald-500 mt-1 shrink-0"></i>
                  <span>Mendukung pertumbuhan bisnis multi-cabang dan multi-perusahaan.</span>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Kenapa Kami -->
    <section class="py-16 bg-gray-50 dark:bg-gray-900 transition-colors">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Mengapa Memilih Kami?</h2>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <div class="text-center p-6 group">
            <div class="w-16 h-16 mx-auto rounded-full bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
              <i class="fas fa-bolt text-sky-600 dark:text-sky-400 text-2xl"></i>
            </div>
            <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Cepat & Ringan</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">Dibangun dengan teknologi modern: Vue 3, Inertia, Tailwind. Tanpa reload halaman.</p>
          </div>
          <div class="text-center p-6 group">
            <div class="w-16 h-16 mx-auto rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
              <i class="fas fa-shield-halved text-emerald-600 dark:text-emerald-400 text-2xl"></i>
            </div>
            <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Keamanan Terjamin</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">Data Anda aman dengan enkripsi, signed URL, dan RBAC granular per tenant.</p>
          </div>
          <div class="text-center p-6 group">
            <div class="w-16 h-16 mx-auto rounded-full bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
              <i class="fas fa-mobile-screen text-violet-600 dark:text-violet-400 text-2xl"></i>
            </div>
            <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Responsif</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">Akses dari mana saja: desktop, tablet, maupun smartphone. Mobile-first design.</p>
          </div>
          <div class="text-center p-6 group">
            <div class="w-16 h-16 mx-auto rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
              <i class="fas fa-headset text-amber-600 dark:text-amber-400 text-2xl"></i>
            </div>
            <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Dukungan 24/7</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">Tim support kami siap membantu Anda kapan saja melalui chat, telepon, atau email.</p>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>
