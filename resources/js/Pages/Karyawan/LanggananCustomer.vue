<script setup>
import KaryawanLayout from '@/Layouts/KaryawanLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
defineOptions({ layout: KaryawanLayout });

const langganans = [
  { id: 1, customer: 'Pak Sugeng', paket: 'Silver 20Mbps', alamat: 'Jl. Mawar No. 5', status: 'Aktif' },
  { id: 2, customer: 'Bu Rini', paket: 'Gold 50Mbps', alamat: 'Jl. Melati No. 12', status: 'Aktif' },
  { id: 3, customer: 'Mbak Dewi', paket: 'Platinum 100Mbps', alamat: 'Jl. Kenanga No. 20', status: 'Aktif' },
  { id: 4, customer: 'Pak Slamet', paket: 'Basic 10Mbps', alamat: 'Jl. Dahlia No. 3', status: 'Nonaktif' },
];
function statusBadge(s) { return s === 'Aktif' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'; }
</script>
<template>
  <div><Head title="Langganan Customer | Karyawan" /><slot name="header">Langganan Customer</slot>
    <div class="space-y-6">
      <nav class="flex items-center gap-1.5 text-sm"><Link href="/karyawan/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-amber-600 transition-colors"><i class="fas fa-home"></i></Link><i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i><Link href="/karyawan/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-amber-600 transition-colors">Dashboard</Link><i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i><span class="text-gray-900 dark:text-white font-medium">Langganan Customer</span></nav>
      <p class="text-sm text-gray-500 dark:text-gray-400">Data pemasangan paket customer yang Anda tangani.</p>
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden"><div class="overflow-x-auto"><table class="w-full text-sm min-w-[600px]"><thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700"><tr><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Customer</th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Paket</th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Alamat</th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Status</th></tr></thead><tbody class="divide-y divide-gray-200 dark:divide-gray-700"><tr v-for="l in langganans" :key="l.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"><td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ l.customer }}</td><td class="px-4 py-3"><span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400"><i class="fas fa-wifi text-[10px]"></i> {{ l.paket }}</span></td><td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ l.alamat }}</td><td class="px-4 py-3"><span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium', statusBadge(l.status)]">{{ l.status }}</span></td></tr></tbody></table></div></div>
    </div>
  </div>
</template>
