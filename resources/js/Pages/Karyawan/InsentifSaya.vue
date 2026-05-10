<script setup>
import KaryawanLayout from '@/Layouts/KaryawanLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
defineOptions({ layout: KaryawanLayout });

const insentifs = [
  { id: 1, kode_tagihan: 'INV-202505-001', customer: 'Pak Sugeng', nominal: 12500, status: 'Disetujui', tgl: '2025-05-08' },
  { id: 2, kode_tagihan: 'INV-202505-002', customer: 'Bu Rini', nominal: 12000, status: 'Menunggu', tgl: '2025-05-10' },
  { id: 3, kode_tagihan: 'INV-202505-003', customer: 'Mbak Dewi', nominal: 37500, status: 'Disetujui', tgl: '2025-05-01' },
  { id: 4, kode_tagihan: 'INV-202505-004', customer: 'Pak Slamet', nominal: 7500, status: 'Disetujui', tgl: '2025-04-20' },
];
function statusBadge(s) { if (s === 'Disetujui') return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'; if (s === 'Menunggu') return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'; return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'; }
function formatRupiah(n) { return 'Rp ' + Number(n).toLocaleString('id-ID'); }
</script>
<template>
  <div><Head title="Insentif Saya | Karyawan" /><slot name="header">Insentif Saya</slot>
    <div class="space-y-6">
      <nav class="flex items-center gap-1.5 text-sm"><Link href="/karyawan/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-amber-600 transition-colors"><i class="fas fa-home"></i></Link><i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i><Link href="/karyawan/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-amber-600 transition-colors">Dashboard</Link><i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i><span class="text-gray-900 dark:text-white font-medium">Insentif Saya</span></nav>
      <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4 text-sm text-amber-700 dark:text-amber-400"><i class="fas fa-info-circle mr-1.5"></i> Total insentif Anda: <strong>{{ formatRupiah(insentifs.reduce((a, i) => a + i.nominal, 0)) }}</strong></div>
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden"><div class="overflow-x-auto"><table class="w-full text-sm min-w-[600px]"><thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700"><tr><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Kode Tagihan</th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Customer</th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Nominal</th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Status</th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Tanggal</th></tr></thead><tbody class="divide-y divide-gray-200 dark:divide-gray-700"><tr v-for="i in insentifs" :key="i.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"><td class="px-4 py-3"><code class="text-sm font-mono text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">{{ i.kode_tagihan }}</code></td><td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ i.customer }}</td><td class="px-4 py-3 text-gray-900 dark:text-white font-medium">{{ formatRupiah(i.nominal) }}</td><td class="px-4 py-3"><span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium', statusBadge(i.status)]">{{ i.status }}</span></td><td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap">{{ i.tgl }}</td></tr></tbody></table></div></div>
    </div>
  </div>
</template>
