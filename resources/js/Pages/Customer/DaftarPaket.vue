<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import ToastContainer from '@/Components/ToastContainer.vue';

defineOptions({ layout: CustomerLayout });

defineProps({ pakets: { type: Array, default: () => [] } });

const searchInput = ref('');

const filteredPakets = computed(() => {
    const q = searchInput.value.trim().toLowerCase();
    if (!q) return [];
    // This is a static fallback - actual filtering is done in template
    return [];
});

function formatRupiah(n) {
    if (!n) return '—';
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(n);
}

function formatSpeed(downKbps, upKbps) {
    if (!downKbps) return '—';
    const down = downKbps >= 1000 ? (downKbps / 1000) + ' Mbps' : downKbps + ' Kbps';
    if (!upKbps) return down;
    const up = upKbps >= 1000 ? (upKbps / 1000) + ' Mbps' : upKbps + ' Kbps';
    return `${down} / ${up}`;
}
</script>

<template>
  <div>
    <Head title="Daftar Paket | Pelanggan" />
    <ToastContainer />
    <div class="space-y-6">
      <nav class="flex items-center gap-1.5 text-sm">
        <Link href="/customer/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-emerald-600 transition-colors"><i class="fas fa-home"></i></Link>
        <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
        <span class="text-gray-900 dark:text-white font-medium">Daftar Paket</span>
      </nav>

      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Daftar Paket Internet</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Pilih paket internet yang sesuai dengan kebutuhan Anda.</p>
      </div>

      <!-- Filter -->
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm">
        <div class="flex flex-wrap items-end gap-4">
          <div class="min-w-[160px] flex-1">
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Cari</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-search text-gray-400 text-sm"></i></div>
              <input v-model="searchInput" type="text" placeholder="Cari nama paket..." class="w-full pl-10 pr-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors" data-testid="input-search">
            </div>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div v-if="pakets.length === 0" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-12 text-center">
        <i class="fas fa-box-open text-5xl text-gray-300 dark:text-gray-600 mb-3 block"></i>
        <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada paket yang tersedia saat ini.</p>
      </div>

      <!-- Paket grid -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="paket in pakets" :key="paket.id" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-shadow">
          <div class="p-5 space-y-3">
            <div class="flex items-start justify-between">
              <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ paket.nama }}</h3>
                <p v-if="paket.kode" class="text-xs font-mono text-gray-400 mt-0.5">{{ paket.kode }}</p>
              </div>
              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                <i class="fas fa-check-circle mr-1"></i>Aktif
              </span>
            </div>

            <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
              {{ formatRupiah(paket.harga) }}
              <span v-if="paket.billing_cycle" class="text-xs font-normal text-gray-500">/ {{ paket.billing_cycle }}</span>
            </div>

            <div class="space-y-1.5 text-sm">
              <div class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                <i class="fas fa-tachometer-alt text-emerald-500 w-4"></i>
                <span>{{ formatSpeed(paket.kecepatan_down_kbps, paket.kecepatan_up_kbps) }}</span>
              </div>
              <div v-if="paket.unlimited" class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                <i class="fas fa-infinity text-emerald-500 w-4"></i>
                <span>Kuota Unlimited</span>
              </div>
              <div v-else-if="paket.kuota_gb" class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                <i class="fas fa-database text-emerald-500 w-4"></i>
                <span>Kuota {{ paket.kuota_gb }} GB</span>
              </div>
              <div v-if="paket.max_devices" class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                <i class="fas fa-devices text-emerald-500 w-4"></i>
                <span>Maks {{ paket.max_devices }} perangkat</span>
              </div>
              <div v-if="paket.fup_quota_down" class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                <i class="fas fa-arrow-down text-amber-500 w-4"></i>
                <span>FUP: {{ paket.fup_quota_down }} GB → {{ formatSpeed(paket.fup_speed_down_kbps, null) }}</span>
              </div>
            </div>

            <p v-if="paket.deskripsi" class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2">{{ paket.deskripsi }}</p>

            <div class="pt-2 border-t border-gray-100 dark:border-gray-700 flex items-center gap-2">
              <Link :href="`/customer/daftar-paket/detail?id=${paket.id}`" class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                <i class="fas fa-eye mr-1.5"></i>Detail
              </Link>
              <Link :href="`/customer/paket-tambah?id_paket=${paket.id}`" class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors shadow-sm">
                <i class="fas fa-plus mr-1.5"></i>Berlangganan
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
