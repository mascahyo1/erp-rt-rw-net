<!--
  Customer (Pelanggan) Dashboard
  Breadcrumb + Hero emerald-teal + 4 stat cards real.
-->
<script setup>
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import DashboardStatCard from '@/Components/DashboardStatCard.vue';
import DashboardHero from '@/Components/DashboardHero.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: CustomerLayout });

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
});

const page = usePage();
const userName = computed(() => page.props.auth?.user?.name ?? 'Pelanggan');

// Pilih color untuk status_pembayaran berdasarkan label
const statusColor = computed(() => {
    const s = props.stats?.status_pembayaran;
    if (s === 'Lunas') return 'emerald';
    if (s === 'Ada Tunggakan') return 'rose';
    if (s === 'Belum Bayar') return 'amber';
    return 'slate'; // Belum Ada Tagihan / default
});
</script>

<template>
    <div>
        <Head title="Dashboard | Pelanggan" />

        <div class="space-y-6">
            <nav class="flex items-center gap-1.5 text-sm">
                <Link href="/customer/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-emerald-600 transition-colors">
                    <i class="fas fa-home"></i>
                </Link>
                <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
                <span class="text-gray-900 dark:text-white font-medium">Dashboard</span>
            </nav>

            <slot name="header">Dashboard</slot>

            <DashboardHero
                :title="`Selamat datang, ${userName}!`"
                subtitle="Dashboard pelanggan RT/RW Net. Pantau tagihan dan paket Anda."
                icon="fa-user"
                gradient="emerald"
            />

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <DashboardStatCard
                    icon="fa-box"
                    label="Paket Aktif"
                    :value="stats?.paket_aktif ?? 0"
                    color="emerald"
                    sublabel="Langganan internet aktif"
                    href="/customer/paket-saya"
                />
                <DashboardStatCard
                    icon="fa-file-invoice"
                    label="Tagihan Bulan Ini"
                    :value="stats?.tagihan_bulan_ini ?? 0"
                    color="amber"
                    sublabel="Invoice dibuat bulan ini"
                    href="/customer/tagihan-saya"
                />
                <DashboardStatCard
                    icon="fa-credit-card"
                    label="Status Pembayaran"
                    :value="stats?.status_pembayaran ?? '—'"
                    :color="statusColor"
                    :gradient-text="false"
                    sublabel="Status invoice terbaru"
                    href="/customer/tagihan-saya"
                />
                <DashboardStatCard
                    icon="fa-history"
                    label="Riwayat Pembayaran"
                    :value="stats?.riwayat_pembayaran ?? 0"
                    color="violet"
                    sublabel="Total transaksi tercatat"
                    href="/customer/riwayat-pembayaran"
                />
            </div>
        </div>
    </div>
</template>
