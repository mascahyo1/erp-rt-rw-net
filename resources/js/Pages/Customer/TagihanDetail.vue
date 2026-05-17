<script setup>
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineOptions({ layout: CustomerLayout });

const props = defineProps({ tagihan: Object });

function statusBadge(s) { if (s === 'Lunas') return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'; if (s === 'Belum Bayar') return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'; return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'; }
</script>

<template>
  <div>
    <Head title="Detail Tagihan | Pelanggan" />
    <div class="space-y-6">
      <nav class="flex items-center gap-1.5 text-sm"><Link href="/customer/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-emerald-600 transition-colors"><i class="fas fa-home"></i></Link><i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i><Link href="/customer/tagihan-saya" class="text-gray-500 dark:text-gray-400 hover:text-emerald-600 transition-colors">Tagihan Saya</Link><i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i><span class="text-gray-900 dark:text-white font-medium">Detail</span></nav>
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 sm:p-8">
        <div class="flex items-center gap-4 pb-6 border-b border-gray-100 dark:border-gray-700"><div class="w-16 h-16 rounded-lg bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white text-xl shrink-0"><i class="fas fa-file-invoice"></i></div><div><h4 class="text-xl font-bold text-gray-900 dark:text-white">{{ tagihan?.kode }}</h4><span :class="['inline-flex mt-1 px-2.5 py-0.5 rounded-full text-xs font-medium', statusBadge(tagihan?.status)]">{{ tagihan?.status }}</span></div></div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6"><div><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Paket</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ tagihan?.paket || '—' }}</p></div><div><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nominal</label><p class="text-sm text-gray-900 dark:text-white mt-1 font-medium">Rp {{ Number(tagihan?.nominal || 0).toLocaleString('id') }}</p></div><div><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Jatuh Tempo</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ tagihan?.tgl_jatuh_tempo || '—' }}</p></div><div><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tanggal Bayar</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ tagihan?.tgl_bayar || '—' }}</p></div><div><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Metode</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ tagihan?.metode || '—' }}</p></div></div>
      </div>
    </div>
  </div>
</template>
