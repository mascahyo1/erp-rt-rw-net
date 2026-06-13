<script setup>
import { Head, Link } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import ToastContainer from '@/Components/ToastContainer.vue';

defineOptions({ layout: CustomerLayout });

defineProps({ paket: { type: Object, default: () => ({}) } });

function formatRupiah(n) {
    if (n === null || n === undefined) return '—';
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
    <Head title="Detail Paket | Pelanggan" />
    <ToastContainer />
    <div class="space-y-6">
      <nav class="flex items-center gap-1.5 text-sm">
        <Link href="/customer/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-emerald-600 transition-colors"><i class="fas fa-home"></i></Link>
        <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
        <Link href="/customer/daftar-paket" class="text-gray-500 dark:text-gray-400 hover:text-emerald-600 transition-colors">Daftar Paket</Link>
        <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
        <span class="text-gray-900 dark:text-white font-medium">Detail</span>
      </nav>

      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-br from-emerald-500 to-teal-600 p-6 text-white">
          <h2 class="text-3xl font-bold">{{ paket.nama }}</h2>
          <p v-if="paket.kode" class="text-sm font-mono opacity-80 mt-1">{{ paket.kode }}</p>
          <div class="mt-4 text-4xl font-bold">
            {{ formatRupiah(paket.harga) }}
            <span v-if="paket.billing_cycle" class="text-base font-normal opacity-80">/ {{ paket.billing_cycle }}</span>
          </div>
        </div>

        <!-- Specs -->
        <div class="p-6 space-y-6">
          <div v-if="paket.deskripsi">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-2 uppercase tracking-wider">Deskripsi</h3>
            <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">{{ paket.deskripsi }}</p>
          </div>

          <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 uppercase tracking-wider">Spesifikasi</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-lg">
                <div class="w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                  <i class="fas fa-tachometer-alt"></i>
                </div>
                <div>
                  <p class="text-xs text-gray-500 dark:text-gray-400">Kecepatan</p>
                  <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatSpeed(paket.kecepatan_down_kbps, paket.kecepatan_up_kbps) }}</p>
                </div>
              </div>

              <div v-if="paket.unlimited" class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-lg">
                <div class="w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                  <i class="fas fa-infinity"></i>
                </div>
                <div>
                  <p class="text-xs text-gray-500 dark:text-gray-400">Kuota</p>
                  <p class="text-sm font-semibold text-gray-900 dark:text-white">Unlimited</p>
                </div>
              </div>
              <div v-else-if="paket.kuota_gb" class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-lg">
                <div class="w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                  <i class="fas fa-database"></i>
                </div>
                <div>
                  <p class="text-xs text-gray-500 dark:text-gray-400">Kuota</p>
                  <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ paket.kuota_gb }} GB</p>
                </div>
              </div>

              <div v-if="paket.max_devices" class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-lg">
                <div class="w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                  <i class="fas fa-devices"></i>
                </div>
                <div>
                  <p class="text-xs text-gray-500 dark:text-gray-400">Maks. Perangkat</p>
                  <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ paket.max_devices }} perangkat</p>
                </div>
              </div>

              <div v-if="paket.billing_cycle" class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-lg">
                <div class="w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                  <i class="fas fa-calendar"></i>
                </div>
                <div>
                  <p class="text-xs text-gray-500 dark:text-gray-400">Siklus Tagihan</p>
                  <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ paket.billing_cycle }}</p>
                </div>
              </div>
            </div>
          </div>

          <div v-if="paket.fup_quota_down">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 uppercase tracking-wider">Kebijakan FUP</h3>
            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
              <div class="flex items-start gap-3">
                <i class="fas fa-exclamation-triangle text-amber-600 dark:text-amber-400 mt-0.5"></i>
                <div class="text-sm text-amber-900 dark:text-amber-200 space-y-1">
                  <p>Setelah pemakaian mencapai <strong>{{ paket.fup_quota_down }} GB</strong>, kecepatan akan diturunkan menjadi <strong>{{ formatSpeed(paket.fup_speed_down_kbps, paket.fup_speed_up_kbps) }}</strong> hingga siklus tagihan berikutnya.</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-4 border-t border-gray-100 dark:border-gray-700">
            <Link href="/customer/daftar-paket" class="inline-flex items-center justify-center px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
              <i class="fas fa-arrow-left mr-1.5"></i>Kembali
            </Link>
            <Link :href="`/customer/paket-tambah?id_paket=${paket.id}`" class="inline-flex items-center justify-center px-6 py-2.5 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors shadow-sm">
              <i class="fas fa-plus mr-1.5"></i>Berlangganan Sekarang
            </Link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
