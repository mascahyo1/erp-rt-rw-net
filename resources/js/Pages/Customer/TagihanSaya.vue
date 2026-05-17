<script setup>
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineOptions({ layout: CustomerLayout });

const props = defineProps({ tagihans: Array });

function statusBadge(s) {
  if (s === 'Lunas') return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
  if (s === 'Belum Bayar') return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
  return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
}
</script>

<template>
  <div>
    <Head title="Tagihan Saya | Pelanggan" />
    <div class="space-y-6">
      <nav class="flex items-center gap-1.5 text-sm"><Link href="/customer/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-emerald-600 transition-colors"><i class="fas fa-home"></i></Link><i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i><span class="text-gray-900 dark:text-white font-medium">Tagihan Saya</span></nav>
      <div><h2 class="text-2xl font-bold text-gray-900 dark:text-white">Tagihan Saya</h2><p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Daftar tagihan dan status pembayaran Anda.</p></div>

      <div v-if="!tagihans || tagihans.length === 0" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-12 text-center text-gray-400 dark:text-gray-500"><i class="fas fa-file-invoice text-4xl mb-3 block"></i><span class="text-sm">Belum ada tagihan.</span></div>

      <div v-else class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto"><table class="w-full text-sm min-w-[650px]"><thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700"><tr><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Kode</th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Nominal</th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Jatuh Tempo</th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Status</th><th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-400 w-24">Aksi</th></tr></thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700"><tr v-for="t in tagihans" :key="t.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"><td class="px-4 py-3"><code class="text-sm font-mono text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">{{ t.kode }}</code></td><td class="px-4 py-3 text-gray-900 dark:text-white font-medium">Rp {{ Number(t.nominal || 0).toLocaleString('id') }}</td><td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap">{{ t.tgl_jatuh_tempo || '—' }}</td><td class="px-4 py-3 whitespace-nowrap"><span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium', statusBadge(t.status)]">{{ t.status }}</span></td><td class="px-4 py-3 whitespace-nowrap text-right"><Link :href="`/customer/tagihan-saya/detail?id=${t.id}`" class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors inline-block" title="Detail"><i class="fas fa-eye text-sm"></i></Link></td></tr></tbody></table></div>
      </div>
    </div>
  </div>
</template>
