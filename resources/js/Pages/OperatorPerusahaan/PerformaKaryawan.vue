<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import OperatorPerusahaanLayout from '@/Layouts/OperatorPerusahaanLayout.vue';
import { useToast } from '@/Composables/useToast';
import ToastContainer from '@/Components/ToastContainer.vue';

defineOptions({ layout: OperatorPerusahaanLayout });

const props = defineProps({ performa: Array, filters: Object });
const toast = useToast();
const page = usePage();

const can = (perm) => page.props.permissions?.includes(perm);

const dariTgl = ref(props.filters?.dari_tgl || new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0, 10));
const sampaiTgl = ref(props.filters?.sampai_tgl || new Date().toISOString().slice(0, 10));

const items = computed(() => props.performa || []);

function applyFilter() {
  router.get('/operator-perusahaan/performa-karyawan', { dari_tgl: dariTgl.value, sampai_tgl: sampaiTgl.value }, { preserveState: true, preserveScroll: true });
}

function resetFilter() {
  dariTgl.value = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0, 10);
  sampaiTgl.value = new Date().toISOString().slice(0, 10);
  applyFilter();
}

function exportExcel() {
  const url = `/operator-perusahaan/performa-karyawan/export?dari_tgl=${dariTgl.value}&sampai_tgl=${sampaiTgl.value}`;
  window.open(url, '_blank');
  toast.success('Export Excel sedang diproses.');
}

function formatRupiah(n) {
  if (n === null || n === undefined) return 'Rp 0';
  return 'Rp ' + new Intl.NumberFormat('id-ID').format(n);
}
</script>

<template>
  <Head title="Performa Karyawan" />
  <ToastContainer />

  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Performa Karyawan</h1>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Laporan kinerja karyawan: insentif + gangguan yang diselesaikan. Identifikasi yang kerja beneran vs AFK.</p>
      </div>
      <div class="flex items-center gap-2">
        <button data-testid="btn-export-excel" @click="exportExcel" class="inline-flex items-center px-4 py-2.5 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors shadow-sm">
          <i class="fas fa-file-excel mr-1.5"></i>Export Excel
        </button>
      </div>
    </div>

    <!-- Filter -->
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

    <!-- Summary -->
    <div v-if="items.length > 0" class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Karyawan</div>
        <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white" data-testid="summary-total-karyawan">{{ items.length }}</div>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Insentif</div>
        <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white" data-testid="summary-total-insentif">{{ items.reduce((a, b) => a + b.jumlah_insentif, 0) }} insentif</div>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Nominal Insentif</div>
        <div class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400" data-testid="summary-total-nominal">{{ formatRupiah(items.reduce((a, b) => a + b.nominal_insentif, 0)) }}</div>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Gangguan Solved</div>
        <div class="mt-1 text-2xl font-bold text-sky-600 dark:text-sky-400" data-testid="summary-total-gangguan">{{ items.reduce((a, b) => a + b.gangguan_solved_pj_utama + b.gangguan_solved_pj_lain, 0) }} tiket</div>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
      <div class="overflow-x-auto">
        <table data-testid="table-data" class="w-full text-sm">
          <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
            <tr>
              <th class="px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 text-xs">Kode Karyawan</th>
              <th class="px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 text-xs">Nama Karyawan</th>
              <th class="px-3 py-3 text-right font-semibold text-gray-600 dark:text-gray-400 text-xs">Jumlah Insentif</th>
              <th class="px-3 py-3 text-right font-semibold text-gray-600 dark:text-gray-400 text-xs">Nominal Insentif</th>
              <th class="px-3 py-3 text-right font-semibold text-gray-600 dark:text-gray-400 text-xs">Gangguan solved (PJ Utama)</th>
              <th class="px-3 py-3 text-right font-semibold text-gray-600 dark:text-gray-400 text-xs">Gangguan solved (PJ Lain)</th>
              <th class="px-3 py-3 text-right font-semibold text-gray-600 dark:text-gray-400 text-xs">Total Solved</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in items" :key="p.id" :class="['border-b border-gray-100 dark:border-gray-700', (p.jumlah_insentif === 0 && (p.gangguan_solved_pj_utama + p.gangguan_solved_pj_lain) === 0) ? 'opacity-60' : '']" data-testid="row-karyawan">
              <td class="px-3 py-3 font-mono text-xs text-gray-900 dark:text-white">{{ p.code }}</td>
              <td class="px-3 py-3 text-gray-900 dark:text-white">{{ p.name }}</td>
              <td class="px-3 py-3 text-right text-gray-700 dark:text-gray-300">{{ p.jumlah_insentif }}</td>
              <td class="px-3 py-3 text-right text-emerald-600 dark:text-emerald-400 font-medium">{{ formatRupiah(p.nominal_insentif) }}</td>
              <td class="px-3 py-3 text-right text-sky-600 dark:text-sky-400 font-medium">{{ p.gangguan_solved_pj_utama }}</td>
              <td class="px-3 py-3 text-right text-cyan-600 dark:text-cyan-400 font-medium">{{ p.gangguan_solved_pj_lain }}</td>
              <td class="px-3 py-3 text-right text-gray-900 dark:text-white font-semibold">{{ p.gangguan_solved_pj_utama + p.gangguan_solved_pj_lain }}</td>
            </tr>
            <tr v-if="items.length === 0">
              <td colspan="7" class="px-3 py-12 text-center text-gray-500 dark:text-gray-400" data-testid="empty-state">Tidak ada data karyawan di company ini.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <p v-if="items.length > 0 && items.every(p => p.jumlah_insentif === 0 && p.gangguan_solved_pj_utama === 0 && p.gangguan_solved_pj_lain === 0)" class="text-xs text-amber-600 dark:text-amber-400">
      <i class="fas fa-info-circle mr-1"></i> Semua karyawan belum punya insentif atau gangguan solved di periode ini. Mungkin data belum ada, atau coba rentang tanggal yang lebih luas.
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
