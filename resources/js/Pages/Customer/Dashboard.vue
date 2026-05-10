<script setup>
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
defineOptions({ layout: CustomerLayout });
</script>

<template>
  <div>
    <Head title="Dashboard | Pelanggan" />
    <div class="space-y-6">
      <nav class="flex items-center gap-1.5 text-sm"><Link href="/customer/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-emerald-600 transition-colors"><i class="fas fa-home"></i></Link><i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i><span class="text-gray-900 dark:text-white font-medium">Dashboard</span></nav>
      <slot name="header">Dashboard</slot>
      <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 p-8 md:p-10 shadow-lg">
        <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-16 -left-16 w-48 h-48 bg-white/10 rounded-full blur-2xl"></div>
        <div class="relative flex flex-col md:flex-row items-start md:items-center gap-6">
          <div class="w-16 h-16 rounded-2xl bg-white/15 backdrop-blur-sm flex items-center justify-center shrink-0"><i class="fas fa-user text-white text-2xl"></i></div>
          <div class="text-white"><h3 class="text-2xl font-bold mb-1">Selamat datang!</h3><p class="text-emerald-100 text-sm md:text-base">Dashboard pelanggan RT/RW Net. Pantau tagihan dan paket Anda.</p></div>
        </div>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-6 shadow-sm"><div class="w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center mb-3"><i class="fas fa-box text-emerald-600 dark:text-emerald-400"></i></div><div class="text-2xl font-bold text-gray-900 dark:text-white">—</div><div class="text-sm text-gray-600 dark:text-gray-400">Paket Aktif</div></div>
        <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-6 shadow-sm"><div class="w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center mb-3"><i class="fas fa-file-invoice text-amber-600 dark:text-amber-400"></i></div><div class="text-2xl font-bold text-gray-900 dark:text-white">—</div><div class="text-sm text-gray-600 dark:text-gray-400">Tagihan Bulan Ini</div></div>
        <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-6 shadow-sm"><div class="w-10 h-10 rounded-lg bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center mb-3"><i class="fas fa-credit-card text-sky-600 dark:text-sky-400"></i></div><div class="text-2xl font-bold text-gray-900 dark:text-white">—</div><div class="text-sm text-gray-600 dark:text-gray-400">Status Pembayaran</div></div>
        <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-6 shadow-sm"><div class="w-10 h-10 rounded-lg bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center mb-3"><i class="fas fa-history text-violet-600 dark:text-violet-400"></i></div><div class="text-2xl font-bold text-gray-900 dark:text-white">0</div><div class="text-sm text-gray-600 dark:text-gray-400">Riwayat Pembayaran</div></div>
      </div>
    </div>
  </div>
</template>
