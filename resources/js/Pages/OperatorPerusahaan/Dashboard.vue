<script setup>
import OperatorPerusahaanLayout from '@/Layouts/OperatorPerusahaanLayout.vue';
import { Head } from '@inertiajs/vue3';
defineOptions({ layout: OperatorPerusahaanLayout });
</script>

<template>
  <div>
    <Head title="Dashboard | Perusahaan" />
    <div class="space-y-6">
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h2>

      <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-sky-500 to-blue-600 p-8 md:p-10 shadow-lg">
        <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-16 -left-16 w-48 h-48 bg-white/10 rounded-full blur-2xl"></div>
        <div class="relative flex flex-col md:flex-row items-start md:items-center gap-6">
          <div class="w-16 h-16 rounded-2xl bg-white/15 backdrop-blur-sm flex items-center justify-center shrink-0"><i class="fas fa-building text-white text-2xl"></i></div>
          <div class="text-white"><h3 class="text-2xl font-bold mb-1">Selamat datang, PT Net Sejahtera!</h3><p class="text-sky-100 text-sm md:text-base">Dashboard ini menampilkan ringkasan bisnis RT/RW Net Anda.</p></div>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-6 shadow-sm hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between mb-3"><div class="w-10 h-10 rounded-lg bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center"><i class="fas fa-users text-sky-600 dark:text-sky-400"></i></div></div>
          <div class="text-2xl font-bold text-gray-900 dark:text-white">0</div><div class="text-sm text-gray-600 dark:text-gray-400">Total Customer</div>
        </div>
        <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-6 shadow-sm hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between mb-3"><div class="w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center"><i class="fas fa-file-invoice text-emerald-600 dark:text-emerald-400"></i></div></div>
          <div class="text-2xl font-bold text-gray-900 dark:text-white">0</div><div class="text-sm text-gray-600 dark:text-gray-400">Tagihan Bulan Ini</div>
        </div>
        <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-6 shadow-sm hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between mb-3"><div class="w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center"><i class="fas fa-hand-holding-usd text-amber-600 dark:text-amber-400"></i></div></div>
          <div class="text-2xl font-bold text-gray-900 dark:text-white">Rp 0</div><div class="text-sm text-gray-600 dark:text-gray-400">Pembayaran Masuk</div>
        </div>
        <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-6 shadow-sm hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between mb-3"><div class="w-10 h-10 rounded-lg bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center"><i class="fas fa-box text-violet-600 dark:text-violet-400"></i></div></div>
          <div class="text-2xl font-bold text-gray-900 dark:text-white">0</div><div class="text-sm text-gray-600 dark:text-gray-400">Paket Aktif</div>
        </div>
      </div>
    </div>
  </div>
</template>
