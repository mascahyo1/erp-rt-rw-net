<script setup>
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import { Head } from '@inertiajs/vue3';
defineOptions({ layout: CustomerLayout });

const pakets = [
  { id: 1, nama_paket: 'Silver 20Mbps', kecepatan: 20, harga: 250000, fup: '500 GB', status: 'Aktif', tgl_mulai: '2025-01-20', tgl_akhir: null },
  { id: 2, nama_paket: 'Basic 10Mbps', kecepatan: 10, harga: 150000, fup: '300 GB', status: 'Nonaktif', tgl_mulai: '2024-06-01', tgl_akhir: '2025-01-19' },
];
</script>

<template>
  <div>
    <Head title="Paket Saya | Pelanggan" />
    <slot name="header">Paket Saya</slot>
    <div class="space-y-6">
      <p class="text-sm text-gray-500 dark:text-gray-400">Daftar paket internet yang Anda langgani.</p>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div v-for="p in pakets" :key="p.id" :class="['rounded-xl border shadow-sm overflow-hidden', p.status === 'Aktif' ? 'bg-white dark:bg-gray-800 border-emerald-200 dark:border-emerald-800' : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 opacity-70']">
          <div class="p-6">
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center gap-3"><div class="w-12 h-12 rounded-lg bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white shrink-0"><i class="fas fa-wifi text-lg"></i></div><div><h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ p.nama_paket }}</h3><span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium', p.status === 'Aktif' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400']">{{ p.status }}</span></div></div>
              <div class="text-right"><div class="text-xl font-bold text-gray-900 dark:text-white">Rp {{ p.harga.toLocaleString('id-ID') }}</div><div class="text-xs text-gray-500 dark:text-gray-400">/bulan</div></div>
            </div>
            <div class="grid grid-cols-2 gap-3 text-sm"><div><span class="text-gray-500 dark:text-gray-400">Kecepatan</span><p class="font-medium text-gray-900 dark:text-white">{{ p.kecepatan }} Mbps</p></div><div><span class="text-gray-500 dark:text-gray-400">FUP</span><p class="font-medium text-gray-900 dark:text-white">{{ p.fup || '—' }}</p></div><div><span class="text-gray-500 dark:text-gray-400">Mulai</span><p class="font-medium text-gray-900 dark:text-white">{{ p.tgl_mulai }}</p></div><div><span class="text-gray-500 dark:text-gray-400">Akhir</span><p class="font-medium text-gray-900 dark:text-white">{{ p.tgl_akhir || '—' }}</p></div></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
