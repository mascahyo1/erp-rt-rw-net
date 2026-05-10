<script setup>
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
defineOptions({ layout: CustomerLayout });
const paket = { id: 1, nama_paket: 'Silver 20Mbps', kecepatan: 20, harga: 250000, fup: '500 GB', status: 'Aktif', tgl_mulai: '2025-01-20', tgl_akhir: null };
</script>
<template>
  <div><Head title="Detail Paket | Pelanggan" /><slot name="header">Detail Paket</slot>
    <div class="space-y-6">
      <nav class="flex items-center gap-1.5 text-sm"><Link href="/customer/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-emerald-600 transition-colors"><i class="fas fa-home"></i></Link><i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i><Link href="/customer/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-emerald-600 transition-colors">Dashboard</Link><i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i><Link href="/customer/paket-saya" class="text-gray-500 dark:text-gray-400 hover:text-emerald-600 transition-colors">Paket Saya</Link><i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i><span class="text-gray-900 dark:text-white font-medium">Detail</span></nav>
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 sm:p-8 max-w-lg">
        <div class="flex items-center gap-4 pb-6 border-b border-gray-100 dark:border-gray-700"><div class="w-16 h-16 rounded-lg bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white text-xl shrink-0"><i class="fas fa-wifi"></i></div><div><h4 class="text-xl font-bold text-gray-900 dark:text-white">{{ paket.nama_paket }}</h4><span class="inline-flex mt-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">{{ paket.status }}</span></div></div>
        <div class="grid grid-cols-2 gap-4 mt-6"><div><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kecepatan</label><p class="text-sm text-gray-900 dark:text-white mt-1 font-medium">{{ paket.kecepatan }} Mbps</p></div><div><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Harga</label><p class="text-sm text-gray-900 dark:text-white mt-1 font-medium">Rp {{ paket.harga.toLocaleString('id-ID') }}/bln</p></div><div><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">FUP</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ paket.fup || '—' }}</p></div><div><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tanggal Mulai</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ paket.tgl_mulai }}</p></div><div><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tanggal Akhir</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ paket.tgl_akhir || '— (Aktif)' }}</p></div></div>
      </div>
    </div>
  </div>
</template>
