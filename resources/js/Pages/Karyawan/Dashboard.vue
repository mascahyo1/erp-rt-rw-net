<!--
  Karyawan Dashboard
  Hero amber-orange + 4 stat cards real.
-->
<script setup>
import KaryawanLayout from '@/Layouts/KaryawanLayout.vue';
import DashboardStatCard from '@/Components/DashboardStatCard.vue';
import DashboardHero from '@/Components/DashboardHero.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: KaryawanLayout });

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
});

const page = usePage();
const userName = computed(() => page.props.auth?.user?.name ?? 'Karyawan');

const formatRupiah = (n) => {
    const num = Number(n) || 0;
    if (num >= 1_000_000_000) return `Rp ${(num / 1_000_000_000).toFixed(1).replace(/\.0$/, '')}M`;
    if (num >= 1_000_000) return `Rp ${(num / 1_000_000).toFixed(1).replace(/\.0$/, '')}jt`;
    if (num >= 1_000) return `Rp ${(num / 1_000).toFixed(0)}rb`;
    return `Rp ${num.toLocaleString('id-ID')}`;
};
</script>

<template>
    <div>
        <Head title="Dashboard | Karyawan" />
        <slot name="header">Dashboard</slot>

        <div class="space-y-6">
            <DashboardHero
                :title="`Selamat datang, ${userName}!`"
                subtitle="Pantau tagihan, customer, dan insentif Anda."
                icon="fa-user-tie"
                gradient="amber"
            />

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <DashboardStatCard
                    icon="fa-users"
                    label="Customer Ditagih"
                    :value="stats?.customer_ditagih ?? 0"
                    color="amber"
                    sublabel="Customer dengan internet aktif"
                    href="/karyawan/customer"
                />
                <DashboardStatCard
                    icon="fa-file-invoice"
                    label="Tagihan Bulan Ini"
                    :value="stats?.tagihan_bulan_ini ?? 0"
                    color="sky"
                    sublabel="Invoice dibuat bulan ini"
                    href="/karyawan/tagihan"
                />
                <DashboardStatCard
                    icon="fa-coins"
                    label="Insentif Bulan Ini"
                    :value="formatRupiah(stats?.insentif_bulan_ini ?? 0)"
                    color="emerald"
                    sublabel="Klaim disetujui admin"
                    href="/karyawan/insentif-saya"
                />
                <DashboardStatCard
                    icon="fa-credit-card"
                    label="Pembayaran Collection"
                    :value="stats?.pembayaran_collection ?? 0"
                    color="violet"
                    sublabel="Total entri pembayaran"
                    href="/karyawan/riwayat-pembayaran"
                />
            </div>
        </div>
    </div>
</template>
