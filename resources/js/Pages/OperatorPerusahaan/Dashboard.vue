<!--
  Operator Perusahaan Dashboard
  Hero sky-blue + 4 stat cards real (semua dari query DB, no dummy).
-->
<script setup>
import OperatorPerusahaanLayout from '@/Layouts/OperatorPerusahaanLayout.vue';
import DashboardStatCard from '@/Components/DashboardStatCard.vue';
import DashboardHero from '@/Components/DashboardHero.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: OperatorPerusahaanLayout });

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
});

const page = usePage();
const companyName = computed(() => page.props.auth?.user?.company?.name ?? 'Perusahaan');

// Format nominal ke Rupiah singkat (mis. 1500000 → "Rp 1,5jt")
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
        <Head title="Dashboard | Perusahaan" />

        <div class="space-y-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h2>

            <DashboardHero
                :title="`Selamat datang, ${companyName}!`"
                subtitle="Dashboard ini menampilkan ringkasan bisnis RT/RW Net Anda."
                icon="fa-building"
                gradient="sky"
            />

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <DashboardStatCard
                    icon="fa-users"
                    label="Total Customer"
                    :value="stats?.total_customer ?? 0"
                    color="sky"
                    href="/operator-perusahaan/customer"
                />
                <DashboardStatCard
                    icon="fa-file-invoice"
                    label="Tagihan Bulan Ini"
                    :value="stats?.tagihan_bulan_ini ?? 0"
                    color="emerald"
                    sublabel="Jumlah invoice dibuat"
                    href="/operator-perusahaan/tagihan"
                />
                <DashboardStatCard
                    icon="fa-hand-holding-usd"
                    label="Pembayaran Masuk"
                    :value="formatRupiah(stats?.pembayaran_masuk ?? 0)"
                    color="amber"
                    sublabel="Bulan ini · status paid"
                    href="/operator-perusahaan/riwayat-pembayaran"
                />
                <DashboardStatCard
                    icon="fa-box"
                    label="Paket Aktif"
                    :value="stats?.langganan_aktif ?? 0"
                    color="violet"
                    sublabel="Customer dengan internet aktif"
                    href="/operator-perusahaan/langganan-customer"
                />
            </div>
        </div>
    </div>
</template>
