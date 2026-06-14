<!--
  Operator SaaS Dashboard
  Tampil 6 stat cards multi-warna untuk ringkasan platform ERP RT/RW Net.
  3 cards punya link interaktif ke halaman resource (perusahaan, admin-perusahaan, admin-saas).
-->
<script setup>
import OperatorSaasLayout from '@/Layouts/OperatorSaasLayout.vue';
import DashboardStatCard from '@/Components/DashboardStatCard.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: OperatorSaasLayout });

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
});

// Tambahan: card kecil "System Status" untuk kesan premium SaaS
const systemOnline = computed(() => true);
</script>

<template>
    <div>
        <Head title="Dashboard | Operator SaaS" />

        <div class="space-y-8">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Ringkasan data platform ERP RT RW Net</p>
                </div>
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-xs font-medium text-emerald-700 dark:text-emerald-300 w-fit">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    System Online
                </div>
            </div>

            <!-- 6 Stat Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                <DashboardStatCard
                    icon="fa-building"
                    label="Perusahaan Aktif"
                    :value="stats?.perusahaan_aktif ?? 0"
                    color="violet"
                    href="/operator-saas/perusahaan"
                />
                <DashboardStatCard
                    icon="fa-user-tie"
                    label="Admin Perusahaan"
                    :value="stats?.admin_perusahaan_aktif ?? 0"
                    color="sky"
                    href="/operator-saas/admin-perusahaan"
                />
                <DashboardStatCard
                    icon="fa-user-shield"
                    label="Admin SaaS"
                    :value="stats?.admin_saas ?? 0"
                    color="emerald"
                    href="/operator-saas/admin-saas"
                />
                <DashboardStatCard
                    icon="fa-users"
                    label="Pelanggan Aktif"
                    :value="stats?.pelanggan_aktif ?? 0"
                    color="fuchsia"
                />
                <DashboardStatCard
                    icon="fa-user-gear"
                    label="Karyawan Aktif"
                    :value="stats?.karyawan_aktif ?? 0"
                    color="amber"
                />
                <DashboardStatCard
                    icon="fa-wifi"
                    label="Langganan Aktif"
                    :value="stats?.langganan_aktif ?? 0"
                    color="rose"
                />
            </div>
        </div>
    </div>
</template>
