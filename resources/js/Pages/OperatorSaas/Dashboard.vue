<script setup>
import OperatorSaasLayout from '@/Layouts/OperatorSaasLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineOptions({ layout: OperatorSaasLayout });

defineProps({
  stats: Object,
});

const cards = [
  {
    key: 'perusahaan_aktif', label: 'Perusahaan Aktif', icon: 'fa-building',
    color: 'from-violet-500 to-purple-600', bg: 'bg-violet-100 dark:bg-violet-900/30',
    iconColor: 'text-violet-600 dark:text-violet-400', hover: 'hover:border-violet-300 dark:hover:border-violet-700',
    link: '/operator-saas/perusahaan',
  },
  {
    key: 'admin_perusahaan_aktif', label: 'Admin Perusahaan', icon: 'fa-user-tie',
    color: 'from-sky-500 to-blue-600', bg: 'bg-sky-100 dark:bg-sky-900/30',
    iconColor: 'text-sky-600 dark:text-sky-400', hover: 'hover:border-sky-300 dark:hover:border-sky-700',
    link: '/operator-saas/admin-perusahaan',
  },
  {
    key: 'admin_saas', label: 'Admin SaaS', icon: 'fa-user-shield',
    color: 'from-emerald-500 to-teal-600', bg: 'bg-emerald-100 dark:bg-emerald-900/30',
    iconColor: 'text-emerald-600 dark:text-emerald-400', hover: 'hover:border-emerald-300 dark:hover:border-emerald-700',
    link: '/operator-saas/admin-saas',
  },
  {
    key: 'pelanggan_aktif', label: 'Pelanggan Aktif', icon: 'fa-users',
    color: 'from-fuchsia-500 to-pink-600', bg: 'bg-fuchsia-100 dark:bg-fuchsia-900/30',
    iconColor: 'text-fuchsia-600 dark:text-fuchsia-400', hover: 'hover:border-fuchsia-300 dark:hover:border-fuchsia-700',
  },
  {
    key: 'karyawan_aktif', label: 'Karyawan Aktif', icon: 'fa-user-gear',
    color: 'from-amber-500 to-orange-600', bg: 'bg-amber-100 dark:bg-amber-900/30',
    iconColor: 'text-amber-600 dark:text-amber-400', hover: 'hover:border-amber-300 dark:hover:border-amber-700',
  },
  {
    key: 'langganan_aktif', label: 'Langganan Aktif', icon: 'fa-wifi',
    color: 'from-rose-500 to-red-600', bg: 'bg-rose-100 dark:bg-rose-900/30',
    iconColor: 'text-rose-600 dark:text-rose-400', hover: 'hover:border-rose-300 dark:hover:border-rose-700',
  },
];
</script>

<template>
  <div>
    <Head title="Dashboard | Operator SaaS" />

    <div class="space-y-8">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Ringkasan data platform ERP RT RW Net</p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        <Link
          v-for="card in cards"
          :key="card.key"
          :href="card.link || '#'"
          :class="[
            'group rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-6 shadow-sm transition-all duration-300',
            'hover:shadow-lg hover:-translate-y-1',
            card.link ? 'cursor-pointer' : 'cursor-default',
            card.hover,
          ]"
          @click="!card.link && $event.preventDefault()"
        >
          <div class="flex items-start justify-between mb-5">
            <div :class="['w-12 h-12 rounded-xl flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform duration-300', card.bg]">
              <i :class="['fas', card.icon, card.iconColor, 'text-lg']"></i>
            </div>
            <div :class="['w-2 h-2 rounded-full bg-gradient-to-br', card.color, 'opacity-60 group-hover:opacity-100 transition-opacity']"></div>
          </div>
          <div :class="['text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-br', card.color]">
            {{ stats?.[card.key] ?? 0 }}
          </div>
          <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">{{ card.label }}</div>
          <div v-if="card.link" class="flex items-center gap-1 mt-3 text-xs text-gray-400 dark:text-gray-500 group-hover:text-indigo-500 dark:group-hover:text-indigo-400 transition-colors">
            <span>Lihat detail</span>
            <i class="fas fa-arrow-right text-[9px] group-hover:translate-x-0.5 transition-transform"></i>
          </div>
        </Link>
      </div>
    </div>
  </div>
</template>
