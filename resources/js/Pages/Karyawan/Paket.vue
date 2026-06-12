<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import KaryawanLayout from '@/Layouts/KaryawanLayout.vue';
import ToastContainer from '@/Components/ToastContainer.vue';

defineOptions({ layout: KaryawanLayout });

const props = defineProps({ items: Object, filters: Object });

const searchInput = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || 'Aktif');
const terhapusFilter = ref(props.filters?.terhapus || 'tidak');
const sortField = ref(props.filters?.sort_field || '');
const sortDir = ref(props.filters?.sort_dir || 'asc');
const perPage = ref(props.filters?.per_page ? Number(props.filters.per_page) : 10);
const perPageOptions = [5, 10, 25, 50, 100];

const paketsList = computed(() => props.items?.data || []);
const currentPage = computed(() => props.pakets?.current_page || 1);
const totalPages = computed(() => props.pakets?.last_page || 1);

function buildQuery(o = {}) {
    const p = { ...o };
    if (p.search === undefined) p.search = searchInput.value || undefined;
    if (p.status === undefined) p.status = statusFilter.value || undefined;
    if (p.terhapus === undefined) p.terhapus = terhapusFilter.value || undefined;
    if (p.sort_field === undefined) p.sort_field = sortField.value || undefined;
    if (p.sort_dir === undefined) p.sort_dir = sortDir.value;
    if (p.per_page === undefined) p.per_page = perPage.value;
    Object.keys(p).forEach(k => { if (p[k] === undefined) delete p[k]; });
    return p;
}
function fetchData(o = {}) {
    router.get('/karyawan/paket', buildQuery(o), { preserveState: true, preserveScroll: true, replace: true });
}
function applySearch() { fetchData({ search: searchInput.value || undefined, page: 1 }); }
function clearSearch() { searchInput.value = ''; fetchData({ search: undefined, page: 1 }); }
function applyFilters() { fetchData({ page: 1 }); }
function resetFilters() { searchInput.value = ''; statusFilter.value = 'Aktif'; terhapusFilter.value = 'tidak'; fetchData({ search: undefined, status: 'Aktif', terhapus: 'tidak', page: 1 }); }
function changePerPage(n) { perPage.value = n; fetchData({ per_page: n, page: 1 }); }
function goToPage(p) { fetchData({ page: Math.max(1, Math.min(p, totalPages.value)) }); }

function sort(f) {
    if (sortField.value === f) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = f;
        sortDir.value = 'asc';
    }
    fetchData({ sort_field: sortField.value, sort_dir: sortDir.value, page: 1 });
}
function sortIcon(f) { return sortField.value !== f ? 'fa-sort' : (sortDir.value === 'asc' ? 'fa-sort-up' : 'fa-sort-down'); }
function sortOrder(f) { return sortField.value === f ? 1 : null; }

const visiblePages = computed(() => {
    const pg = []; const t = totalPages.value; const c = currentPage.value;
    let st = Math.max(1, c - 2); let en = Math.min(t, c + 2);
    if (en - st < 4) {
        if (st === 1) en = Math.min(t, st + 4);
        else st = Math.max(1, en - 4);
    }
    for (let i = st; i <= en; i++) pg.push(i);
    return pg;
});

const selectedPaket = ref(null);
const showDetailModal = ref(false);
function openDetail(p) {
    selectedPaket.value = p;
    showDetailModal.value = true;
}
function closeDetail() {
    showDetailModal.value = false;
    selectedPaket.value = null;
}

function formatRupiah(n) {
    if (n === null || n === undefined) return '-';
    return 'Rp ' + Number(n).toLocaleString('id-ID');
}
function formatSpeed(kbps) {
    if (!kbps || kbps === 0) return '-';
    if (kbps >= 1024) return (kbps / 1024).toFixed(0) + ' Mbps';
    return kbps + ' Kbps';
}
function cycleLabel(c) {
    return c === 'daily' ? 'Harian' : c === 'weekly' ? 'Mingguan' : c === 'monthly' ? 'Bulanan' : c === 'yearly' ? 'Tahunan' : c;
}
function statusBadgeClass(s) {
    return s === 'Aktif' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
        : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
}
</script>

<template>
  <div>
    <Head title="Paket Internet | Karyawan" />
    <ToastContainer />
    <div class="space-y-6">
      <nav class="flex items-center gap-1.5 text-sm">
        <Link href="/karyawan/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-amber-600 dark:hover:text-amber-400 transition-colors"><i class="fas fa-home"></i></Link>
        <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
        <Link href="/karyawan/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-amber-600 dark:hover:text-amber-400 transition-colors">Dashboard</Link>
        <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
        <span class="text-gray-900 dark:text-white font-medium">Paket Internet</span>
      </nav>

      <div class="flex items-center justify-between gap-3">
        <div>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Paket Internet</h2>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Daftar paket internet yang tersedia di perusahaan Anda.</p>
        </div>
      </div>

      <!-- Search + filter row -->
      <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
        <div class="relative w-full sm:w-72">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-search text-gray-400 text-sm"></i></div>
          <input v-model="searchInput" type="text" placeholder="Cari paket..." class="w-full pl-10 pr-16 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-colors" @keydown.enter="applySearch" />
          <div class="absolute inset-y-0 right-0 flex items-center gap-1 pr-1.5">
            <button v-if="searchInput" @click="clearSearch" class="p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-white transition-colors" title="Clear"><i class="fas fa-times text-xs"></i></button>
            <button @click="applySearch" class="px-2 py-1 rounded bg-amber-600 text-white hover:bg-amber-700 transition-colors" title="Cari"><i class="fas fa-search text-xs"></i></button>
          </div>
        </div>
        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
          <span>{{ props.items?.total || 0 }} data</span>
          <button v-if="searchInput || statusFilter !== 'Aktif' || terhapusFilter !== 'tidak'" @click="resetFilters" class="text-xs text-red-500 hover:text-red-700 dark:hover:text-red-400 underline">Reset filter</button>
        </div>
      </div>

      <!-- Filter card -->
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm">
        <div class="flex flex-wrap items-end gap-4">
          <div class="min-w-[160px]">
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Status</label>
            <select v-model="statusFilter" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-colors">
              <option value="Aktif">Aktif</option>
              <option value="Nonaktif">Nonaktif</option>
            </select>
          </div>
          <div class="min-w-[160px]">
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Terhapus</label>
            <select v-model="terhapusFilter" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-colors">
              <option value="tidak">Tidak</option>
              <option value="ya">Ya</option>
            </select>
          </div>
          <button @click="applyFilters" class="px-4 py-2 bg-amber-600 text-white text-sm font-medium rounded-lg hover:bg-amber-700 transition-colors shadow-sm"><i class="fas fa-filter mr-1.5"></i>Filter</button>
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm min-w-[800px]">
            <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
              <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Kode</th>
                <th @click="sort('name')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none hover:text-gray-900 dark:hover:text-white transition-colors">
                  <span class="inline-flex items-center gap-1">Nama <i :class="['fas', sortIcon('name'), 'text-[10px]']"></i></span>
                </th>
                <th @click="sort('price')" class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none hover:text-gray-900 dark:hover:text-white transition-colors">
                  <span class="inline-flex items-center gap-1">Harga <i :class="['fas', sortIcon('price'), 'text-[10px]']"></i></span>
                </th>
                <th @click="sort('billing_cycle')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none hover:text-gray-900 dark:hover:text-white transition-colors">
                  <span class="inline-flex items-center gap-1">Cycle <i :class="['fas', sortIcon('billing_cycle'), 'text-[10px]']"></i></span>
                </th>
                <th @click="sort('subscriptions_count')" class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none hover:text-gray-900 dark:hover:text-white transition-colors">
                  <span class="inline-flex items-center gap-1">Langganan <i :class="['fas', sortIcon('subscriptions_count'), 'text-[10px]']"></i></span>
                </th>
                <th @click="sort('is_active')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none hover:text-gray-900 dark:hover:text-white transition-colors">
                  <span class="inline-flex items-center gap-1">Status <i :class="['fas', sortIcon('is_active'), 'text-[10px]']"></i></span>
                </th>
                <th class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-400 w-20">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-if="paketsList.length === 0">
                <td colspan="7" class="px-4 py-16 text-center text-gray-500 dark:text-gray-400">
                  <i class="fas fa-box-open text-3xl mb-2 block opacity-40"></i>Tidak ada data paket.
                </td>
              </tr>
              <tr v-for="p in paketsList" :key="p.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                <td class="px-4 py-3 font-mono text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ p.code }}</td>
                <td class="px-4 py-3">
                  <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white text-xs font-bold shrink-0">
                      <i class="fas fa-box"></i>
                    </div>
                    <div>
                      <div class="font-medium text-gray-900 dark:text-white">{{ p.name }}</div>
                      <div class="text-xs text-gray-500 dark:text-gray-400">{{ formatSpeed(p.speed_down_kbps) }} / {{ formatSpeed(p.speed_up_kbps) }}<span v-if="p.is_unlimited" class="ml-1 text-amber-600 dark:text-amber-400">• Unlimited</span></div>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-3 text-right font-mono text-xs text-gray-900 dark:text-white whitespace-nowrap">{{ formatRupiah(p.price) }}</td>
                <td class="px-4 py-3 whitespace-nowrap">
                  <span :class="['inline-flex px-2 py-0.5 rounded text-xs font-medium', p.billing_cycle === 'daily' ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400' : p.billing_cycle === 'weekly' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : p.billing_cycle === 'monthly' ? 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400' : 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400']">{{ cycleLabel(p.billing_cycle) }}</span>
                </td>
                <td class="px-4 py-3 text-right font-mono text-xs text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ p.langganan_aktif }} aktif</td>
                <td class="px-4 py-3 whitespace-nowrap">
                  <span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium', statusBadgeClass(p.status)]">{{ p.status }}</span>
                </td>
                <td class="px-4 py-3 text-center whitespace-nowrap">
                  <button @click="openDetail(p)" class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-amber-50 dark:hover:bg-amber-900/30 hover:text-amber-600 dark:hover:text-amber-400 transition-colors" title="Lihat Detail">
                    <i class="fas fa-eye text-sm"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-3">
          <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
            <span>Tampilkan</span>
            <select :value="perPage" @change="changePerPage(Number($event.target.value))" class="min-w-[65px] px-2 py-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
              <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }}</option>
            </select>
            <span>dari {{ props.items?.total || 0 }} data</span>
          </div>
          <div class="flex items-center gap-1 flex-wrap justify-center sm:justify-start">
            <button @click="goToPage(1)" :disabled="currentPage === 1" class="px-2.5 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"><i class="fas fa-chevron-double-left text-xs"></i></button>
            <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1" class="px-2.5 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"><i class="fas fa-chevron-left text-xs"></i></button>
            <button v-for="p in visiblePages" :key="p" @click="goToPage(p)" :class="['px-3 py-1.5 rounded-lg text-sm font-medium transition-colors', p === currentPage ? 'bg-amber-600 text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700']">{{ p }}</button>
            <button @click="goToPage(currentPage + 1)" :disabled="currentPage === totalPages" class="px-2.5 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"><i class="fas fa-chevron-right text-xs"></i></button>
            <button @click="goToPage(totalPages)" :disabled="currentPage === totalPages" class="px-2.5 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"><i class="fas fa-chevron-double-right text-xs"></i></button>
          </div>
        </div>
      </div>
    </div>

    <!-- Detail Modal -->
    <Teleport to="body"><Transition name="modal">
      <div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="closeDetail">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col">
          <div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              <i class="fas fa-box mr-2 text-amber-500"></i>Detail Paket
            </h3>
            <button @click="closeDetail" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button>
          </div>
          <div v-if="selectedPaket" class="overflow-y-auto flex-1 px-6 py-5 space-y-5 modal-scroll">
            <!-- Header summary -->
            <div class="flex items-center gap-4 pb-4 border-b border-gray-100 dark:border-gray-700">
              <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white text-2xl font-bold shrink-0">
                <i class="fas fa-box"></i>
              </div>
              <div class="flex-1 min-w-0">
                <h4 class="text-xl font-bold text-gray-900 dark:text-white truncate">{{ selectedPaket.name }}</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400 font-mono">{{ selectedPaket.code }}</p>
                <div class="mt-1 flex items-center gap-2 flex-wrap">
                  <span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium', statusBadgeClass(selectedPaket.status)]">{{ selectedPaket.status }}</span>
                  <span :class="['inline-flex px-2 py-0.5 rounded text-xs font-medium', selectedPaket.billing_cycle === 'daily' ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400' : selectedPaket.billing_cycle === 'weekly' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : selectedPaket.billing_cycle === 'monthly' ? 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400' : 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400']">{{ cycleLabel(selectedPaket.billing_cycle) }}</span>
                </div>
              </div>
            </div>

            <!-- Pricing & Speeds -->
            <div>
              <h5 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Harga & Kecepatan</h5>
              <div class="grid grid-cols-2 gap-3">
                <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-3">
                  <label class="text-xs text-gray-500 dark:text-gray-400">Harga</label>
                  <p class="text-lg font-bold text-amber-600 dark:text-amber-400 mt-0.5">{{ formatRupiah(selectedPaket.price) }} <span class="text-xs text-gray-500 dark:text-gray-400 font-normal">/ {{ cycleLabel(selectedPaket.billing_cycle).toLowerCase() }}</span></p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-3">
                  <label class="text-xs text-gray-500 dark:text-gray-400">Kecepatan</label>
                  <p class="text-lg font-bold text-gray-900 dark:text-white mt-0.5">{{ formatSpeed(selectedPaket.speed_down_kbps) }} <span class="text-xs text-gray-500 dark:text-gray-400 font-normal">/ {{ formatSpeed(selectedPaket.speed_up_kbps) }}</span></p>
                </div>
              </div>
            </div>

            <!-- Quota & Devices -->
            <div>
              <h5 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Kuota & Perangkat</h5>
              <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                <div><label class="text-xs text-gray-500 dark:text-gray-400">Kuota</label><p class="text-sm font-medium text-gray-900 dark:text-white mt-0.5">{{ selectedPaket.is_unlimited ? 'Unlimited' : (selectedPaket.quota_gb ? selectedPaket.quota_gb + ' GB' : '-') }}</p></div>
                <div><label class="text-xs text-gray-500 dark:text-gray-400">Max Devices</label><p class="text-sm font-medium text-gray-900 dark:text-white mt-0.5">{{ selectedPaket.max_devices || '-' }}</p></div>
                <div><label class="text-xs text-gray-500 dark:text-gray-400">Langganan Aktif</label><p class="text-sm font-medium text-gray-900 dark:text-white mt-0.5">{{ selectedPaket.langganan_aktif }} customer</p></div>
              </div>
            </div>

            <!-- FUP -->
            <div v-if="selectedPaket.fup_quota_down || selectedPaket.fup_quota_up">
              <h5 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Fair Usage Policy (FUP)</h5>
              <div class="grid grid-cols-2 gap-3">
                <div><label class="text-xs text-gray-500 dark:text-gray-400">FUP Kuota Download</label><p class="text-sm font-medium text-gray-900 dark:text-white mt-0.5">{{ selectedPaket.fup_quota_down ? selectedPaket.fup_quota_down + ' GB' : '-' }}</p></div>
                <div><label class="text-xs text-gray-500 dark:text-gray-400">FUP Kuota Upload</label><p class="text-sm font-medium text-gray-900 dark:text-white mt-0.5">{{ selectedPaket.fup_quota_up ? selectedPaket.fup_quota_up + ' GB' : '-' }}</p></div>
                <div><label class="text-xs text-gray-500 dark:text-gray-400">FUP Speed Download</label><p class="text-sm font-medium text-gray-900 dark:text-white mt-0.5">{{ formatSpeed(selectedPaket.fup_speed_down_kbps) }}</p></div>
                <div><label class="text-xs text-gray-500 dark:text-gray-400">FUP Speed Upload</label><p class="text-sm font-medium text-gray-900 dark:text-white mt-0.5">{{ formatSpeed(selectedPaket.fup_speed_up_kbps) }}</p></div>
              </div>
            </div>

            <!-- Description -->
            <div v-if="selectedPaket.description">
              <h5 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Deskripsi</h5>
              <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ selectedPaket.description }}</p>
            </div>

            <!-- Estimasi Pendapatan -->
            <div v-if="selectedPaket.estimasi_pendapatan" class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg p-3">
              <h5 class="text-xs font-semibold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider mb-1">Estimasi Pendapatan</h5>
              <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ formatRupiah(selectedPaket.estimasi_pendapatan) }}</p>
              <p class="text-xs text-emerald-600/70 dark:text-emerald-400/70 mt-1">{{ selectedPaket.langganan_aktif }} langganan aktif × {{ formatRupiah(selectedPaket.price) }}</p>
            </div>
          </div>
          <div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <button @click="closeDetail" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Tutup</button>
          </div>
        </div>
      </div>
    </Transition></Teleport>
  </div>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-active > div:last-child, .modal-leave-active > div:last-child { transition: transform 0.2s ease, opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-from > div:last-child { transform: scale(0.95) translateY(10px); opacity: 0; }
.modal-leave-to > div:last-child { transform: scale(0.95) translateY(10px); opacity: 0; }
</style>

<style>
.modal-scroll::-webkit-scrollbar { width: 6px; }
.modal-scroll::-webkit-scrollbar-track { background: transparent; }
.modal-scroll::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 9999px; }
.modal-scroll::-webkit-scrollbar-thumb:hover { background: #9ca3af; }
.dark .modal-scroll::-webkit-scrollbar-thumb { background: #374151; }
.dark .modal-scroll::-webkit-scrollbar-thumb:hover { background: #4b5563; }
</style>
