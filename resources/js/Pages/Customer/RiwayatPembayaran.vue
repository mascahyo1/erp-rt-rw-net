<script setup>
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import { Head } from '@inertiajs/vue3';
defineOptions({ layout: CustomerLayout });

const pembayarans = [
  { id: 1, kode: 'INV-202505-001', periode: 'Mei 2025', jumlah: 250000, metode: 'Transfer', tgl_bayar: '2025-05-08' },
  { id: 2, kode: 'INV-202504-001', periode: 'April 2025', jumlah: 250000, metode: 'Tunai', tgl_bayar: '2025-04-10' },
  { id: 3, kode: 'INV-202503-001', periode: 'Maret 2025', jumlah: 250000, metode: 'QRIS', tgl_bayar: '2025-03-12' },
];
function metodeBadge(m) { if (m === 'Tunai') return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'; if (m === 'Transfer') return 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400'; return 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400'; }
</script>

<template>
  <div>
    <Head title="Riwayat Pembayaran | Pelanggan" />
    <slot name="header">Riwayat Pembayaran</slot>
    <div class="space-y-6">
      <p class="text-sm text-gray-500 dark:text-gray-400">Riwayat pembayaran yang pernah Anda lakukan.</p>
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden"><div class="overflow-x-auto"><table class="w-full text-sm min-w-[600px]"><thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700"><tr><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Tanggal</th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Kode</th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Periode</th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Jumlah</th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Metode</th></tr></thead><tbody class="divide-y divide-gray-200 dark:divide-gray-700"><tr v-for="p in pembayarans" :key="p.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"><td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap">{{ p.tgl_bayar }}</td><td class="px-4 py-3"><code class="text-sm font-mono text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">{{ p.kode }}</code></td><td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap">{{ p.periode }}</td><td class="px-4 py-3 text-gray-900 dark:text-white font-medium">Rp {{ p.jumlah.toLocaleString('id-ID') }}</td><td class="px-4 py-3 whitespace-nowrap"><span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium', metodeBadge(p.metode)]">{{ p.metode }}</span></td></tr></tbody></table></div></div>
    </div>
  </div>
</template>
