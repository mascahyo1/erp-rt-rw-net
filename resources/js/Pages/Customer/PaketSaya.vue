<script setup>
import { computed } from 'vue';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineOptions({ layout: CustomerLayout });

const props = defineProps({ pakets: Array });

function statusBadgeClass(s) {
  return s === 'Aktif' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400';
}
</script>

<template>
  <div>
    <Head title="Paket Saya | Pelanggan" />
    <div class="space-y-6">
      <nav class="flex items-center gap-1.5 text-sm"><Link href="/customer/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-emerald-600 transition-colors"><i class="fas fa-home"></i></Link><i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i><span class="text-gray-900 dark:text-white font-medium">Paket Saya</span></nav>
      <div class="flex items-center justify-between"><div><h2 class="text-2xl font-bold text-gray-900 dark:text-white">Paket Saya</h2><p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Daftar paket internet yang Anda langgani.</p></div></div>

      <div v-if="!pakets || pakets.length === 0" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-12 text-center text-gray-400 dark:text-gray-500"><i class="fas fa-box-open text-4xl mb-3 block"></i><span class="text-sm">Belum ada paket yang dilanggani.</span></div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div v-for="p in pakets" :key="p.id" :class="['rounded-xl border shadow-sm overflow-hidden relative', p.status === 'Aktif' ? 'bg-white dark:bg-gray-800 border-emerald-200 dark:border-emerald-800' : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 opacity-70']">
          <div class="p-6">
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center gap-3"><div class="w-12 h-12 rounded-lg bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white shrink-0"><i class="fas fa-wifi text-lg"></i></div><div><h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ p.nama_paket }}</h3><span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium', statusBadgeClass(p.status)]">{{ p.status }}</span></div></div>
              <div class="text-right"><div class="text-xl font-bold text-gray-900 dark:text-white">Rp {{ Number(p.harga || 0).toLocaleString('id') }}</div><div class="text-xs text-gray-500 dark:text-gray-400">/bulan</div></div>
            </div>
            <div class="grid grid-cols-2 gap-3 text-sm"><div><span class="text-gray-500 dark:text-gray-400">Kecepatan</span><p class="font-medium text-gray-900 dark:text-white">{{ p.kecepatan || 0 }} Mbps</p></div><div><span class="text-gray-500 dark:text-gray-400">FUP</span><p class="font-medium text-gray-900 dark:text-white">{{ p.fup || '—' }}</p></div><div><span class="text-gray-500 dark:text-gray-400">Mulai</span><p class="font-medium text-gray-900 dark:text-white">{{ p.tgl_mulai || '—' }}</p></div><div><span class="text-gray-500 dark:text-gray-400">Akhir</span><p class="font-medium text-gray-900 dark:text-white">{{ p.tgl_akhir || '—' }}</p></div></div>
            <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700 text-right"><Link :href="`/customer/paket-saya/detail?id=${p.id}`" class="inline-flex items-center gap-1 text-sm text-emerald-600 dark:text-emerald-400 hover:underline"><i class="fas fa-eye"></i> Detail</Link></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
