<script setup>
import KaryawanLayout from '@/Layouts/KaryawanLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
defineOptions({ layout: KaryawanLayout });

const pembayarans = [
  { id: 1, kode_tagihan: 'INV-202505-001', customer: 'Pak Sugeng', jumlah: 250000, metode: 'Transfer', tgl_bayar: '2025-05-08' },
  { id: 2, kode_tagihan: 'INV-202504-001', customer: 'Pak Herman', jumlah: 150000, metode: 'Tunai', tgl_bayar: '2025-04-10' },
  { id: 3, kode_tagihan: 'INV-202505-003', customer: 'Mbak Dewi', jumlah: 750000, metode: 'QRIS', tgl_bayar: '2025-05-01' },
  { id: 4, kode_tagihan: 'INV-202505-004', customer: 'Pak Slamet', jumlah: 150000, metode: 'Tunai', tgl_bayar: '2025-04-20' },
];
function metodeBadge(m) { if (m === 'Tunai') return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'; if (m === 'Transfer') return 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400'; return 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400'; }
function formatRupiah(n) { return 'Rp ' + Number(n).toLocaleString('id-ID'); }
</script>
<template>
  <div><Head title="Riwayat Pembayaran | Karyawan" /><slot name="header">Riwayat Pembayaran</slot>
    <div class="space-y-6">
      <nav class="flex items-center gap-1.5 text-sm"><Link href="/karyawan/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-amber-600 transition-colors"><i class="fas fa-home"></i></Link><i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i><Link href="/karyawan/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-amber-600 transition-colors">Dashboard</Link><i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i><span class="text-gray-900 dark:text-white font-medium">Riwayat Pembayaran</span></nav>
      <p class="text-sm text-gray-500 dark:text-gray-400">Riwayat pembayaran yang Anda collection.</p>
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden"><div class="overflow-x-auto"><table class="w-full text-sm min-w-[600px]"><thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700"><tr><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Tanggal</th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Kode</th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Customer</th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Jumlah</th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Metode</th></tr></thead><tbody class="divide-y divide-gray-200 dark:divide-gray-700"><tr v-for="p in pembayarans" :key="p.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"><td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap">{{ p.tgl_bayar }}</td><td class="px-4 py-3"><code class="text-sm font-mono text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">{{ p.kode_tagihan }}</code></td><td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ p.customer }}</td><td class="px-4 py-3 text-gray-900 dark:text-white font-medium">{{ formatRupiah(p.jumlah) }}</td><td class="px-4 py-3"><span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium', metodeBadge(p.metode)]">{{ p.metode }}</span></td></tr></tbody></table></div></div>
    </div>
  </div>
</template>
