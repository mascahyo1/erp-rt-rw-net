<script setup>
import KaryawanLayout from '@/Layouts/KaryawanLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
defineOptions({ layout: KaryawanLayout });

const tagihans = [
  { id: 1, kode: 'INV-202505-001', customer: 'Pak Sugeng', nominal: 250000, status: 'Lunas', tgl_jatuh_tempo: '2025-05-10' },
  { id: 2, kode: 'INV-202505-002', customer: 'Bu Rini', nominal: 400000, status: 'Belum Bayar', tgl_jatuh_tempo: '2025-06-10' },
  { id: 3, kode: 'INV-202504-001', customer: 'Pak Herman', nominal: 150000, status: 'Kadaluarsa', tgl_jatuh_tempo: '2025-04-10' },
  { id: 4, kode: 'INV-202505-003', customer: 'Mbak Dewi', nominal: 750000, status: 'Lunas', tgl_jatuh_tempo: '2025-05-10' },
  { id: 5, kode: 'INV-202505-004', customer: 'Pak Slamet', nominal: 150000, status: 'Belum Bayar', tgl_jatuh_tempo: '2025-05-10' },
];
function statusBadge(s) { if (s === 'Lunas') return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'; if (s === 'Belum Bayar') return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'; return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'; }
function formatRupiah(n) { return 'Rp ' + Number(n).toLocaleString('id-ID'); }
</script>
<template>
  <div><Head title="Tagihan | Karyawan" /><slot name="header">Tagihan</slot>
    <div class="space-y-6">
      <nav class="flex items-center gap-1.5 text-sm"><Link href="/karyawan/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-amber-600 transition-colors"><i class="fas fa-home"></i></Link><i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i><Link href="/karyawan/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-amber-600 transition-colors">Dashboard</Link><i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i><span class="text-gray-900 dark:text-white font-medium">Tagihan</span></nav>
      <p class="text-sm text-gray-500 dark:text-gray-400">Tagihan yang perlu ditagihkan ke customer.</p>
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden"><div class="overflow-x-auto"><table class="w-full text-sm min-w-[600px]"><thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700"><tr><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Kode</th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Customer</th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Nominal</th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Jatuh Tempo</th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Status</th></tr></thead><tbody class="divide-y divide-gray-200 dark:divide-gray-700"><tr v-for="t in tagihans" :key="t.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"><td class="px-4 py-3"><code class="text-sm font-mono text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">{{ t.kode }}</code></td><td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ t.customer }}</td><td class="px-4 py-3 text-gray-900 dark:text-white font-medium">{{ formatRupiah(t.nominal) }}</td><td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap">{{ t.tgl_jatuh_tempo }}</td><td class="px-4 py-3"><span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium', statusBadge(t.status)]">{{ t.status }}</span></td></tr></tbody></table></div></div>
    </div>
  </div>
</template>
