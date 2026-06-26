<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import OperatorPerusahaanLayout from '@/Layouts/OperatorPerusahaanLayout.vue';
import { useToast } from '@/Composables/useToast';
import ToastContainer from '@/Components/ToastContainer.vue';

defineOptions({ layout: OperatorPerusahaanLayout });

const props = defineProps({ performa: Array, filters: Object });
const toast = useToast();

const dariTgl = ref(props.filters?.dari_tgl || new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0, 10));
const sampaiTgl = ref(props.filters?.sampai_tgl || new Date().toISOString().slice(0, 10));

const items = computed(() => props.performa || []);

function applyFilter() {
  router.get('/operator-perusahaan/performa-admin', { dari_tgl: dariTgl.value, sampai_tgl: sampaiTgl.value }, { preserveState: true, preserveScroll: true });
}

function resetFilter() {
  dariTgl.value = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0, 10);
  sampaiTgl.value = new Date().toISOString().slice(0, 10);
  applyFilter();
}

function exportExcel() {
  const url = `/operator-perusahaan/performa-admin/export?dari_tgl=${dariTgl.value}&sampai_tgl=${sampaiTgl.value}`;
  window.open(url, '_blank');
  toast.success('Export Excel sedang diproses.');
}

function formatRupiah(n) {
  if (n === null || n === undefined) return 'Rp 0';
  return 'Rp ' + new Intl.NumberFormat('id-ID').format(n);
}
</script>

<template>
  <Head title="Performa Admin" />
  <ToastContainer />

  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Performa Admin</h1>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Produktivitas admin: insentif yang di-review + tiket yang di-verify.</p>
      </div>
      <div class="flex items-center gap-2">
        <button data-testid="btn-export-excel" @click="exportExcel" class="inline-flex items-center px-4 py-2.5 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors shadow-sm">
          <i class="fas fa-file-excel mr-1.5"></i>Export Excel
        </button>
      </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
      <div class="flex flex-wrap items-end gap-3">
        <div>
          <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Dari Tanggal</label>
          <input data-testid="input-dari-tgl" v-model="dariTgl" type="date" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 outline-none" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Sampai Tanggal</label>
          <input data-testid="input-sampai-tgl" v-model="sampaiTgl" type="date" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 outline-none" />
        </div>
        <button data-testid="btn-apply-filter" @click="applyFilter" class="px-4 py-2 bg-emerald-600 text-white text-sm rounded-lg hover:bg-emerald-700"><i class="fas fa-filter mr-1"></i>Filter</button>
        <button data-testid="btn-reset-filter" @click="resetFilter" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">Reset</button>
      </div>
    </div>

    <div v-if="items.length > 0" class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Insentif Disetujui</div>
        <div class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400" data-testid="summary-insentif-setuju-count">{{ items.reduce((a, b) => a + b.insentif_disetujui_count, 0) }} insentif</div>
        <div class="text-xs text-gray-500 dark:text-gray-400">{{ formatRupiah(items.reduce((a, b) => a + b.insentif_disetujui_total, 0)) }}</div>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Insentif Ditolak</div>
        <div class="mt-1 text-2xl font-bold text-red-600 dark:text-red-400" data-testid="summary-insentif-tolak-count">{{ items.reduce((a, b) => a + b.insentif_ditolak_count, 0) }} insentif</div>
        <div class="text-xs text-gray-500 dark:text-gray-400">{{ formatRupiah(items.reduce((a, b) => a + b.insentif_ditolak_total, 0)) }}</div>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tiket Disetujui</div>
        <div class="mt-1 text-2xl font-bold text-sky-600 dark:text-sky-400" data-testid="summary-tiket-setuju">{{ items.reduce((a, b) => a + b.tiket_disetujui, 0) }} tiket</div>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tiket Ditolak</div>
        <div class="mt-1 text-2xl font-bold text-orange-600 dark:text-orange-400" data-testid="summary-tiket-tolak">{{ items.reduce((a, b) => a + b.tiket_ditolak, 0) }} tiket</div>
      </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
            <tr>
              <th class="px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 text-xs">Nama Admin</th>
              <th class="px-3 py-3 text-right font-semibold text-emerald-600 dark:text-emerald-400 text-xs">Insentif Disetujui<br><span class="text-[10px] text-gray-500 font-normal">(jumlah)</span></th>
              <th class="px-3 py-3 text-right font-semibold text-emerald-600 dark:text-emerald-400 text-xs">Nominal Disetujui</th>
              <th class="px-3 py-3 text-right font-semibold text-red-600 dark:text-red-400 text-xs">Insentif Ditolak<br><span class="text-[10px] text-gray-500 font-normal">(jumlah)</span></th>
              <th class="px-3 py-3 text-right font-semibold text-red-600 dark:text-red-400 text-xs">Nominal Ditolak</th>
              <th class="px-3 py-3 text-right font-semibold text-sky-600 dark:text-sky-400 text-xs">Tiket Disetujui</th>
              <th class="px-3 py-3 text-right font-semibold text-orange-600 dark:text-orange-400 text-xs">Tiket Ditolak</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in items" :key="p.id" :class="['border-b border-gray-100 dark:border-gray-700', (p.insentif_disetujui_count + p.insentif_ditolak_count + p.tiket_disetujui + p.tiket_ditolak === 0) ? 'opacity-60' : '']" data-testid="row-admin">
              <td class="px-3 py-3 text-gray-900 dark:text-white">
                <div>{{ p.name }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">{{ p.email }}</div>
              </td>
              <td class="px-3 py-3 text-right text-gray-700 dark:text-gray-300">{{ p.insentif_disetujui_count }}</td>
              <td class="px-3 py-3 text-right text-emerald-600 dark:text-emerald-400 font-medium">{{ formatRupiah(p.insentif_disetujui_total) }}</td>
              <td class="px-3 py-3 text-right text-gray-700 dark:text-gray-300">{{ p.insentif_ditolak_count }}</td>
              <td class="px-3 py-3 text-right text-red-600 dark:text-red-400 font-medium">{{ formatRupiah(p.insentif_ditolak_total) }}</td>
              <td class="px-3 py-3 text-right text-sky-600 dark:text-sky-400 font-medium">{{ p.tiket_disetujui }}</td>
              <td class="px-3 py-3 text-right text-orange-600 dark:text-orange-400 font-medium">{{ p.tiket_ditolak }}</td>
            </tr>
            <tr v-if="items.length === 0">
              <td colspan="7" class="px-3 py-12 text-center text-gray-500 dark:text-gray-400" data-testid="empty-state">Tidak ada data admin di company ini.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <p v-if="items.length > 0 && items.every(p => p.insentif_disetujui_count + p.insentif_ditolak_count + p.tiket_disetujui + p.tiket_ditolak === 0)" class="text-xs text-amber-600 dark:text-amber-400">
      <i class="fas fa-info-circle mr-1"></i> Semua admin belum punya aktivitas review di periode ini. Coba rentang tanggal yang lebih luas.
    </p>
  </div>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-active > div:last-child, .modal-leave-active > div:last-child { transition: transform 0.2s ease, opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-from > div:last-child { transform: scale(0.95) translateY(10px); opacity: 0; }
.modal-leave-to > div:last-child { transform: scale(0.95) translateY(10px); opacity: 0; }
</style>
