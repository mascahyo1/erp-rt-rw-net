<script setup>
import KaryawanLayout from '@/Layouts/KaryawanLayout.vue';
import { Head } from '@inertiajs/vue3';
defineOptions({ layout: KaryawanLayout });
</script>
<template>
  <div><Head title="Dashboard | Karyawan" /><slot name="header">Dashboard</slot>
    <div class="space-y-6">
      <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 p-8 md:p-10 shadow-lg">
        <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-16 -left-16 w-48 h-48 bg-white/10 rounded-full blur-2xl"></div>
        <div class="relative flex flex-col md:flex-row items-start md:items-center gap-6">
          <div class="w-16 h-16 rounded-2xl bg-white/15 backdrop-blur-sm flex items-center justify-center shrink-0"><i class="fas fa-user-tie text-white text-2xl"></i></div>
          <div class="text-white"><h3 class="text-2xl font-bold mb-1">Selamat datang, Karyawan!</h3><p class="text-amber-100 text-sm md:text-base">Pantau tagihan, customer, dan insentif Anda.</p></div>
        </div>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-6 shadow-sm"><div class="w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center mb-3"><i class="fas fa-users text-amber-600 dark:text-amber-400"></i></div><div class="text-2xl font-bold text-gray-900 dark:text-white">—</div><div class="text-sm text-gray-600 dark:text-gray-400">Customer Ditagih</div></div>
        <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-6 shadow-sm"><div class="w-10 h-10 rounded-lg bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center mb-3"><i class="fas fa-file-invoice text-sky-600 dark:text-sky-400"></i></div><div class="text-2xl font-bold text-gray-900 dark:text-white">—</div><div class="text-sm text-gray-600 dark:text-gray-400">Tagihan Bulan Ini</div></div>
        <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-6 shadow-sm"><div class="w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center mb-3"><i class="fas fa-coins text-emerald-600 dark:text-emerald-400"></i></div><div class="text-2xl font-bold text-gray-900 dark:text-white">—</div><div class="text-sm text-gray-600 dark:text-gray-400">Insentif Bulan Ini</div></div>
        <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-6 shadow-sm"><div class="w-10 h-10 rounded-lg bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center mb-3"><i class="fas fa-credit-card text-violet-600 dark:text-violet-400"></i></div><div class="text-2xl font-bold text-gray-900 dark:text-white">0</div><div class="text-sm text-gray-600 dark:text-gray-400">Pembayaran Collection</div></div>
      </div>
    </div>
  </div>
</template>
