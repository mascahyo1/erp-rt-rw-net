<script setup>
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineOptions({ layout: CustomerLayout });

const props = defineProps({ pembayaran: Object });

function metodeBadge(m) { if (m === 'Tunai' || m === 'cash') return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'; if (m === 'Transfer' || m === 'transfer') return 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400'; return 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400'; }
</script>

<template>
  <div>
    <Head title="Detail Pembayaran | Pelanggan" />
    <div class="space-y-6">
      <nav class="flex items-center gap-1.5 text-sm"><Link href="/customer/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-emerald-600 transition-colors"><i class="fas fa-home"></i></Link><i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i><Link href="/customer/riwayat-pembayaran" class="text-gray-500 dark:text-gray-400 hover:text-emerald-600 transition-colors">Riwayat Pembayaran</Link><i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i><span class="text-gray-900 dark:text-white font-medium">Detail</span></nav>
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 sm:p-8">
        <div class="flex items-center gap-4 pb-6 border-b border-gray-100 dark:border-gray-700"><div class="w-16 h-16 rounded-lg bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white text-xl shrink-0"><i class="fas fa-check-circle"></i></div><div><h4 class="text-xl font-bold text-gray-900 dark:text-white">{{ pembayaran?.kode }}</h4><span :class="['inline-flex mt-1 px-2.5 py-0.5 rounded-full text-xs font-medium', metodeBadge(pembayaran?.metode)]">{{ pembayaran?.metode }}</span></div></div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6"><div><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Jumlah</label><p class="text-sm text-gray-900 dark:text-white mt-1 font-medium">Rp {{ Number(pembayaran?.jumlah || 0).toLocaleString('id') }}</p></div><div><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tanggal Bayar</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ pembayaran?.tgl_bayar || '—' }}</p></div><div><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Metode</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ pembayaran?.metode || '—' }}</p></div></div>
        <div class="mt-4"><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Keterangan</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ pembayaran?.keterangan || '—' }}</p></div>
      </div>
    </div>
  </div>
</template>
