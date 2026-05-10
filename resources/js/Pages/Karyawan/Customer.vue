<script setup>
import KaryawanLayout from '@/Layouts/KaryawanLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
defineOptions({ layout: KaryawanLayout });

const customers = [
  { id: 1, nama: 'Pak Sugeng', alamat: 'Jl. Mawar No. 5, RT 02/RW 01', telepon: '+62 81234567890', status: 'Aktif' },
  { id: 2, nama: 'Bu Rini', alamat: 'Jl. Melati No. 12, RT 03/RW 01', telepon: '+62 81345678901', status: 'Aktif' },
  { id: 3, nama: 'Pak Herman', alamat: 'Jl. Anggrek No. 8, RT 01/RW 02', telepon: '+62 85789012345', status: 'Nonaktif' },
  { id: 4, nama: 'Mbak Dewi', alamat: 'Jl. Kenanga No. 20, RT 04/RW 01', telepon: '+62 82123456789', status: 'Aktif' },
  { id: 5, nama: 'Pak Slamet', alamat: 'Jl. Dahlia No. 3, RT 02/RW 03', telepon: '+62 85678901234', status: 'Aktif' },
];
function statusBadge(s) { return s === 'Aktif' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'; }
</script>
<template>
  <div><Head title="Customer | Karyawan" /><slot name="header">Customer</slot>
    <div class="space-y-6">
      <nav class="flex items-center gap-1.5 text-sm"><Link href="/karyawan/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-amber-600 transition-colors"><i class="fas fa-home"></i></Link><i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i><Link href="/karyawan/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-amber-600 transition-colors">Dashboard</Link><i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i><span class="text-gray-900 dark:text-white font-medium">Customer</span></nav>
      <p class="text-sm text-gray-500 dark:text-gray-400">Daftar customer yang menjadi tanggung jawab Anda.</p>
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden"><div class="overflow-x-auto"><table class="w-full text-sm min-w-[600px]"><thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700"><tr><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Nama</th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Alamat</th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Telepon</th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Status</th></tr></thead><tbody class="divide-y divide-gray-200 dark:divide-gray-700"><tr v-for="c in customers" :key="c.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"><td class="px-4 py-3"><div class="flex items-center gap-2.5"><div class="w-8 h-8 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white text-xs font-bold shrink-0">{{ c.nama.charAt(0) }}</div><span class="font-medium text-gray-900 dark:text-white">{{ c.nama }}</span></div></td><td class="px-4 py-3 text-gray-600 dark:text-gray-400 max-w-48 truncate">{{ c.alamat }}</td><td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap">{{ c.telepon }}</td><td class="px-4 py-3"><span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium', statusBadge(c.status)]">{{ c.status }}</span></td></tr></tbody></table></div></div>
    </div>
  </div>
</template>
