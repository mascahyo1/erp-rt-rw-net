<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import OperatorPerusahaanLayout from '@/Layouts/OperatorPerusahaanLayout.vue';
import { useToast } from '@/Composables/useToast';
import ToastContainer from '@/Components/ToastContainer.vue';
import SearchableSelectAjax from '@/Components/SearchableSelectAjax.vue';

defineOptions({ layout: OperatorPerusahaanLayout });

const props = defineProps({ tagihans: Object, filters: Object, packages: Object });
const toast = useToast();
const page = usePage();
const can = (perm) => page.props.permissions?.includes(perm);

const searchInput = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const paketFilter = ref(props.filters?.paket || '');
const terhapusFilter = ref(props.filters?.terhapus || 'tidak');
const dueDateStart = ref(props.filters?.due_date_start || '');
const dueDateEnd = ref(props.filters?.due_date_end || '');
const sortField = ref(props.filters?.sort_field || '');
const sortDir = ref(props.filters?.sort_dir || 'asc');
const perPage = ref(props.filters?.per_page ? Number(props.filters.per_page) : 10);
const perPageOptions = [5, 10, 25, 50, 100];
const selectedIds = ref([]);
const selectAll = ref(false);
const selectedItem = ref(null);
const showCreateModal = ref(false);
const showDetailModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const showGenerateModal = ref(false);
const showImportModal = ref(false);

function buildQuery(o = {}) {
  const p = { ...o };
  if (p.search === undefined) p.search = searchInput.value || undefined;
  if (p.status === undefined) p.status = statusFilter.value || undefined;
  if (p.paket === undefined) p.paket = paketFilter.value || undefined;
  if (p.terhapus === undefined) p.terhapus = terhapusFilter.value || undefined;
  if (p.due_date_start === undefined) p.due_date_start = dueDateStart.value || undefined;
  if (p.due_date_end === undefined) p.due_date_end = dueDateEnd.value || undefined;
  if (p.sort_field === undefined) p.sort_field = sortField.value || undefined;
  if (p.sort_dir === undefined) p.sort_dir = sortDir.value || undefined;
  if (p.per_page === undefined) p.per_page = perPage.value;
  Object.keys(p).forEach(k => { if (p[k] === undefined) delete p[k]; });
  return p;
}
function fetchData(o = {}) {
  router.get('/operator-perusahaan/tagihan', buildQuery(o), { preserveState: true, preserveScroll: true, replace: true });
}
function applySearch() { fetchData({ search: searchInput.value || undefined, status: statusFilter.value || undefined, paket: paketFilter.value || undefined, terhapus: terhapusFilter.value, due_date_start: dueDateStart.value || undefined, due_date_end: dueDateEnd.value || undefined, page: 1 }); }
function clearSearch() { searchInput.value = ''; fetchData({ search: undefined, status: statusFilter.value || undefined, paket: paketFilter.value || undefined, terhapus: terhapusFilter.value, due_date_start: undefined, due_date_end: undefined, page: 1 }); }
function applyFilters() { fetchData({ page: 1 }); }
function resetFilters() { searchInput.value = ''; statusFilter.value = ''; paketFilter.value = ''; terhapusFilter.value = 'tidak'; dueDateStart.value = ''; dueDateEnd.value = ''; fetchData({ search: undefined, status: undefined, paket: undefined, terhapus: 'tidak', due_date_start: undefined, due_date_end: undefined, page: 1 }); }
function sort(f) {
  if (sortField.value === f) { if (sortDir.value === 'asc') sortDir.value = 'desc'; else { sortField.value = ''; sortDir.value = 'asc'; } }
  else { sortField.value = f; sortDir.value = 'asc'; }
  fetchData({ sort_field: sortField.value || undefined, sort_dir: sortDir.value || undefined });
}
function sortIcon(f) { if (sortField.value !== f) return 'fa-sort'; return sortDir.value === 'asc' ? 'fa-sort-up' : 'fa-sort-down'; }
function toggleSelectAll() { selectedIds.value = selectAll.value ? props.tagihans.data.map(t => t.id) : []; }
function toggleSelect(id) { const i = selectedIds.value.indexOf(id); i === -1 ? selectedIds.value.push(id) : selectedIds.value.splice(i, 1); }
function goToPage(p) { fetchData({ page: p }); }
function changePerPage(n) { perPage.value = n; fetchData({ per_page: n, page: 1 }); }
function statusBadgeClass(s) {
    // 2-status sederhana: Lunas / Belum Bayar
    if (s === 'paid' || s === 'lunas') return 'bg-emerald-100 text-emerald-700 dark:!bg-emerald-900/30 dark:!text-emerald-300';
    return 'bg-red-100 text-red-700 dark:!bg-red-900/30 dark:!text-red-300';
}
function statusLabel(s) {
    if (s === 'paid') return 'Lunas';
    if (s === 'partial') return 'Sebagian';
    if (s === 'unpaid') return 'Belum Bayar';
    if (s === 'overdue') return 'Kadaluarsa';
    return s;
}

const createForm = useForm({ cust_internet_id: '', usage_start_date: '', usage_end_date: '', total_amount: '', discount_amount: '', tax_amount: '', due_date: '', description: '' });
const editForm = useForm({ cust_internet_id: '', usage_start_date: '', usage_end_date: '', total_amount: '', discount_amount: '', tax_amount: '', due_date: '', description: '' });
const generateForm = useForm({ cycle: 'monthly', period_year: new Date().getFullYear(), period_month: new Date().getMonth() + 1, usage_date: new Date().toISOString().slice(0, 10), due_date: '' });
const importForm = useForm({ file: null });
const importing = ref(false);

function openCreate() { createForm.reset(); showCreateModal.value = true; }
function submitCreate() { createForm.post('/operator-perusahaan/tagihan', { onSuccess: () => { showCreateModal.value = false; fetchData(); toast.success('Tagihan berhasil ditambahkan.'); } }); }
function openEdit(item) { editForm.defaults({ cust_internet_id: item.cust_internet_id, usage_start_date: item.usage_start_date || '', usage_end_date: item.usage_end_date || '', total_amount: item.total_amount, discount_amount: item.discount_amount, tax_amount: item.tax_amount, due_date: item.due_date || '', description: item.description || '' }); editForm.reset(); selectedItem.value = item; showEditModal.value = true; }
function submitEdit() { editForm.put('/operator-perusahaan/tagihan/' + selectedItem.value.id, { onSuccess: () => { showEditModal.value = false; fetchData(); toast.success('Tagihan berhasil diperbarui.'); } }); }

// State untuk Riwayat Pembayaran di detail modal
const paymentHistory = ref({ payments: [], total_paid: 0, remaining: 0, grand_total: 0, payment_status_label: 'unpaid' });
const paymentHistoryLoading = ref(false);

async function openDetail(item) {
    selectedItem.value = item;
    showDetailModal.value = true;
    // Fetch payment history via AJAX
    paymentHistoryLoading.value = true;
    try {
        const res = await fetch(`/operator-perusahaan/api/tagihan/${item.id}/payments`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });
        const json = await res.json();
        if (json.success) {
            paymentHistory.value = json.data;
        }
    } catch (e) {
        console.error('Failed to load payment history:', e);
    } finally {
        paymentHistoryLoading.value = false;
    }
}
function openDelete(item) { selectedItem.value = item; showDeleteModal.value = true; }
function confirmDelete() { router.delete('/operator-perusahaan/tagihan/' + selectedItem.value.id, { onSuccess: () => { showDeleteModal.value = false; fetchData(); toast.success('Tagihan berhasil dihapus.'); } }); }
function confirmRestore(id) { router.patch('/operator-perusahaan/tagihan/' + id + '/restore', { onSuccess: () => { fetchData(); toast.success('Tagihan berhasil dipulihkan.'); } }); }
function bulkDelete() { router.post('/operator-perusahaan/tagihan/bulk-delete', { ids: selectedIds.value }, { onSuccess: () => { selectedIds.value = []; selectAll.value = false; fetchData(); toast.success('Tagihan berhasil dihapus.'); } }); }
function bulkRestore() { router.post('/operator-perusahaan/tagihan/bulk-restore', { ids: selectedIds.value }, { onSuccess: () => { selectedIds.value = []; selectAll.value = false; fetchData(); toast.success('Tagihan berhasil dipulihkan.'); } }); }
function bulkSetStatus(status) { router.post('/operator-perusahaan/tagihan/bulk-status', { ids: selectedIds.value, status }, { onSuccess: () => { selectedIds.value = []; selectAll.value = false; fetchData(); toast.success('Status berhasil diubah.'); } }); }

function openGenerate() { generateForm.reset(); generateForm.cycle = 'monthly'; generateForm.period_year = new Date().getFullYear(); generateForm.period_month = new Date().getMonth() + 1; generateForm.usage_date = new Date().toISOString().slice(0, 10); showGenerateModal.value = true; }
function submitGenerate() { generateForm.post('/operator-perusahaan/tagihan/generate', { onSuccess: () => { showGenerateModal.value = false; fetchData(); toast.success('Tagihan berhasil digenerate.'); } }); }

function openImport() { importForm.reset(); showImportModal.value = true; }
function submitImport() { importing.value = true; importForm.post('/operator-perusahaan/tagihan/import', { onSuccess: () => { showImportModal.value = false; importing.value = false; fetchData(); toast.success('Import berhasil.'); }, onError: () => { importing.value = false; toast.error('Import gagal.'); } }); }
function downloadTemplate() { window.location.href = '/operator-perusahaan/tagihan/template'; }
function buildFilterParams() {
    const params = new URLSearchParams();
    if (searchInput.value) params.set('search', searchInput.value);
    if (statusFilter.value) params.set('status', statusFilter.value);
    if (terhapusFilter.value !== 'tidak') params.set('terhapus', terhapusFilter.value);
    if (paketFilter.value) params.set('paket', paketFilter.value);
    if (dueDateStart.value) params.set('due_date_start', dueDateStart.value);
    if (dueDateEnd.value) params.set('due_date_end', dueDateEnd.value);
    return params.toString();
}
function exportAll() {
    const params = buildFilterParams();
    window.location.href = '/operator-perusahaan/tagihan/export' + (params ? '?' + params : '');
}
function exportSelected() {
    if (selectedIds.value.length > 0) {
        window.location.href = '/operator-perusahaan/tagihan/export?ids=' + selectedIds.value.join(',');
    } else {
        const params = buildFilterParams();
        window.location.href = '/operator-perusahaan/tagihan/export' + (params ? '?' + params : '');
    }
}

const items = computed(() => props.tagihans?.data || []);
const pagination = computed(() => ({ current: props.tagihans?.current_page || 1, last: props.tagihans?.last_page || 1, total: props.tagihans?.total || 0 }));
const hasFilter = computed(() => searchInput.value || statusFilter.value || terhapusFilter.value !== 'tidak' || paketFilter.value || dueDateStart.value || dueDateEnd.value);
</script>

<template>
  <div>
    <Head title="Tagihan | Perusahaan" />
    <ToastContainer />
    <div class="space-y-6">
      <nav class="flex items-center gap-1.5 text-sm"><Link href="/operator-perusahaan/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-sky-600 dark:hover:text-sky-400 transition-colors"><i class="fas fa-home"></i></Link><i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i><span class="text-gray-900 dark:text-white font-medium">Tagihan</span></nav>
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div><h2 class="text-2xl font-bold text-gray-900 dark:text-white">Tagihan</h2><p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Kelola tagihan bulanan pelanggan.</p></div>
        <div class="flex flex-wrap items-center gap-2">
          <button v-if="can('tagihan.generate')" @click="openGenerate" class="inline-flex items-center px-3 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors shadow-sm"><i class="fas fa-magic mr-1.5"></i> Generate</button>
          <button v-if="can('tagihan.import')" @click="openImport" class="inline-flex items-center px-3 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors shadow-sm"><i class="fas fa-file-import mr-1.5"></i> Import</button>
          <button v-if="can('tagihan.import')" @click="downloadTemplate" class="inline-flex items-center px-3 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors shadow-sm"><i class="fas fa-download mr-1.5"></i> Template</button>
          <div v-if="can('tagihan.export')" class="relative group">
            <button class="inline-flex items-center px-3 py-2 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm"><i class="fas fa-file-export mr-1.5"></i> Export <i class="fas fa-chevron-down ml-1.5 text-xs"></i></button>
            <div class="absolute right-0 mt-1 w-44 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-lg py-1 z-30 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all">
              <button @click="exportAll" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"><i class="fas fa-list mr-2"></i> Export Semua</button>
              <button @click="exportSelected" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"><i class="fas fa-check-square mr-2"></i>Export Selected{{ selectedIds.length > 0 ? ' (' + selectedIds.length + ')' : '' }}</button>
            </div>
          </div>
          <button v-if="can('tagihan.create')" @click="openCreate" class="inline-flex items-center px-4 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm"><i class="fas fa-plus mr-1.5"></i> Tambah</button>
        </div>
      </div>
      <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between"><div class="relative w-full sm:w-72"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-search text-gray-400 text-sm"></i></div><input v-model="searchInput" type="text" placeholder="Cari..." class="w-full pl-10 pr-16 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm focus:ring-2 focus:ring-sky-500 outline-none" @keydown.enter="applySearch" /><div class="absolute inset-y-0 right-0 flex items-center gap-1 pr-1.5"><button v-if="searchInput" @click="clearSearch" class="p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-white" title="Clear"><i class="fas fa-times text-xs"></i></button><button @click="applySearch" class="px-2 py-1 rounded bg-sky-600 text-white hover:bg-sky-700" title="Cari"><i class="fas fa-search text-xs"></i></button></div></div><div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400"><span>{{ props.tagihans?.total || 0 }} data</span><button v-if="hasFilter" @click="resetFilters" class="text-xs text-red-500 hover:text-red-700 dark:hover:text-red-400 underline">Reset filter</button></div></div>

      <!-- Filter Card -->
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm">
        <div class="flex flex-wrap items-end gap-3">
          <div class="min-w-[160px]">
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Status</label>
            <select v-model="statusFilter" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors">
              <option value="">Semua</option>
              <option value="paid">Lunas</option>
              <option value="unpaid">Belum Bayar</option>
              <option value="overdue">Kadaluarsa</option>
            </select>
          </div>
          <div class="min-w-[220px]">
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Paket</label>
            <SearchableSelectAjax v-model="paketFilter" url="/operator-perusahaan/api/search/packages" placeholder="Semua Paket" :page-size="25" label-key="label" value-key="value" />
          </div>
          <div class="min-w-[150px]">
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Jatuh Tempo (Awal)</label>
            <input v-model="dueDateStart" type="date" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors dark:[color-scheme:dark]" />
          </div>
          <div class="min-w-[150px]">
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Jatuh Tempo (Akhir)</label>
            <input v-model="dueDateEnd" type="date" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors dark:[color-scheme:dark]" />
          </div>
          <div class="min-w-[160px]">
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Terhapus</label>
            <select v-model="terhapusFilter" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors">
              <option value="tidak">Tidak</option>
              <option value="ya">Ya</option>
            </select>
          </div>
          <button @click="applyFilters" class="px-4 py-2 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm"><i class="fas fa-filter mr-1.5"></i>Filter</button>
        </div>
      </div>

      <!-- Bulk Action Banner -->
      <div v-if="selectedIds.length > 0" class="flex items-center justify-between px-4 py-3 bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-800 rounded-xl shadow-sm">
        <span class="text-sm font-medium text-sky-700 dark:text-sky-300"><i class="fas fa-check-circle mr-1.5"></i> {{ selectedIds.length }} data dipilih</span>
        <div class="flex items-center gap-2">
          <template v-if="terhapusFilter === 'ya'">
            <button v-if="can('tagihan.restore')" @click="bulkRestore()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-emerald-600 text-white hover:bg-emerald-700"><i class="fas fa-undo-alt mr-1"></i> Pulihkan</button>
            <button v-if="can('tagihan.delete')" @click="bulkDelete()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-red-600 text-white hover:bg-red-700"><i class="fas fa-trash-alt mr-1"></i> Hapus</button>
          </template>
          <template v-else>
            <button v-if="can('tagihan.edit')" @click="bulkSetStatus('paid')" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-emerald-600 text-white hover:bg-emerald-700"><i class="fas fa-check mr-1"></i> Set Lunas</button>
            <button v-if="can('tagihan.edit')" @click="bulkSetStatus('unpaid')" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-amber-600 text-white hover:bg-amber-700"><i class="fas fa-times mr-1"></i> Set Belum Bayar</button>
            <button v-if="can('tagihan.delete')" @click="bulkDelete()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-red-600 text-white hover:bg-red-700"><i class="fas fa-trash-alt mr-1"></i> Hapus</button>
          </template>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto"><table class="w-full text-sm min-w-[1200px]"><thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700"><tr><th class="px-4 py-3 w-10"><input v-model="selectAll" type="checkbox" @change="toggleSelectAll" class="rounded border-gray-300 text-sky-600 cursor-pointer" /></th><th @click="sort('invoice_number')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none"><span class="inline-flex items-center gap-1">No. Invoice <i :class="['fas', sortIcon('invoice_number'), 'text-[10px]', sortField === 'invoice_number' ? 'text-sky-500' : 'text-gray-400']"></i></span></th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">No. Langganan</th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Kode Pelanggan</th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Nama Pelanggan</th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">No. Telp</th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Email</th><th @click="sort('usage_start_date')" class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none"><span class="inline-flex items-center gap-1">Awal Usage <i :class="['fas', sortIcon('usage_start_date'), 'text-[10px]', sortField === 'usage_start_date' ? 'text-sky-500' : 'text-gray-400']"></i></span></th><th @click="sort('usage_end_date')" class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none"><span class="inline-flex items-center gap-1">Akhir Usage <i :class="['fas', sortIcon('usage_end_date'), 'text-[10px]', sortField === 'usage_end_date' ? 'text-sky-500' : 'text-gray-400']"></i></span></th><th @click="sort('total_amount')" class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none"><span class="inline-flex items-center gap-1">Total <i :class="['fas', sortIcon('total_amount'), 'text-[10px]', sortField === 'total_amount' ? 'text-sky-500' : 'text-gray-400']"></i></span></th><th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-400">Diskon</th><th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-400">Pajak</th><th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-400">Grand Total</th><th @click="sort('due_date')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none"><span class="inline-flex items-center gap-1">Jatuh Tempo <i :class="['fas', sortIcon('due_date'), 'text-[10px]', sortField === 'due_date' ? 'text-sky-500' : 'text-gray-400']"></i></span></th><th @click="sort('payment_status')" class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none"><span class="inline-flex items-center gap-1">Status <i :class="['fas', sortIcon('payment_status'), 'text-[10px]', sortField === 'payment_status' ? 'text-sky-500' : 'text-gray-400']"></i></span></th><th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-400">Pembayaran Lunas</th><th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-400">Sisa Kurang Pembayaran</th><th class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-400 w-24">Aksi</th></tr></thead>
        <tbody>
          <tr v-if="items.length === 0"><td colspan="15" class="px-4 py-16 text-center text-gray-400 dark:text-gray-500"><i class="fas fa-inbox text-4xl mb-3 block"></i><span class="text-sm">Tidak ada data tagihan</span></td></tr>
          <tr v-for="item in items" :key="item.id" class="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors cursor-pointer" :class="{'opacity-60': item.dihapus}" @click="(e) => { if (!e.target.closest('button') && !e.target.closest('input')) toggleSelect(item.id); }">
            <td class="px-4 py-3" @click.stop><input v-model="selectedIds" :value="item.id" type="checkbox" class="rounded border-gray-300 text-sky-600 cursor-pointer" /></td>
            <td class="px-4 py-3"><span class="font-mono text-xs text-gray-900 dark:text-white font-medium">{{ item.invoice_number }}</span></td>
            <td class="px-4 py-3"><span class="font-mono text-xs text-gray-700 dark:text-gray-300">{{ item.account_number || '-' }}</span></td>
            <td class="px-4 py-3"><span class="font-mono text-xs text-gray-700 dark:text-gray-300">{{ item.customer_code || '-' }}</span></td>
            <td class="px-4 py-3"><div class="flex items-center gap-3"><div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-sky-900/40 flex items-center justify-center text-sky-600 dark:text-sky-400 font-semibold text-xs">{{ (item.customer_name || '?')[0] }}</div><span class="font-medium text-gray-900 dark:text-white">{{ item.customer_name }}</span></div></td>
            <td class="px-4 py-3"><span class="text-xs text-gray-700 dark:text-gray-300">{{ item.phone_country_code }} {{ item.phone_number || '-' }}</span></td>
            <td class="px-4 py-3"><span class="text-xs text-gray-500 dark:text-gray-400">{{ item.email || '-' }}</span></td>
            <td class="px-4 py-3 text-center text-gray-500 dark:text-gray-400 text-xs">{{ item.usage_start_date || '-' }}</td>
            <td class="px-4 py-3 text-center text-gray-500 dark:text-gray-400 text-xs">{{ item.usage_end_date || '-' }}</td>
            <td class="px-4 py-3 text-right text-gray-900 dark:text-white font-mono text-xs">{{ item.total_amount ? 'Rp ' + Number(item.total_amount).toLocaleString('id') : '-' }}</td>
            <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400 font-mono text-xs">{{ item.discount_amount ? 'Rp ' + Number(item.discount_amount).toLocaleString('id') : '-' }}</td>
            <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400 font-mono text-xs">{{ item.tax_amount ? 'Rp ' + Number(item.tax_amount).toLocaleString('id') : '-' }}</td>
            <td class="px-4 py-3 text-right text-gray-900 dark:text-white font-mono text-xs font-semibold">{{ item.grand_total ? 'Rp ' + Number(item.grand_total).toLocaleString('id') : '-' }}</td>
            <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">{{ item.due_date }}</td>
            <td class="px-4 py-3 text-center"><span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium', statusBadgeClass(item.payment_status_label)]">{{ statusLabel(item.payment_status_label) }}</span></td>
            <td class="px-4 py-3 text-right font-mono text-emerald-700 dark:text-emerald-400">{{ Number(item.total_paid || 0).toLocaleString('id') }}</td>
            <td class="px-4 py-3 text-right font-mono" :class="item.remaining > 0 ? 'text-red-700 dark:text-red-400' : 'text-emerald-700 dark:text-emerald-400'">{{ Number(item.remaining || 0).toLocaleString('id') }}</td>
            <td class="px-4 py-3" @click.stop>
              <div class="flex items-center justify-center gap-1">
                <button v-if="can('tagihan.detail') && !item.dihapus" @click="openDetail(item)" title="Detail" class="p-1.5 rounded-lg text-gray-400 hover:text-sky-600 hover:bg-sky-50 dark:hover:text-sky-400 dark:hover:bg-indigo-900/30"><i class="fas fa-eye"></i></button>
                <button v-if="can('tagihan.edit') && !item.dihapus" @click="openEdit(item)" title="Edit" class="p-1.5 rounded-lg text-gray-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:text-amber-400 dark:hover:bg-amber-900/30"><i class="fas fa-edit"></i></button>
                <button v-if="can('tagihan.delete') && !item.dihapus" @click="openDelete(item)" title="Hapus" class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:text-red-400 dark:hover:bg-red-900/30"><i class="fas fa-trash-alt"></i></button>
                <button v-if="can('tagihan.restore') && item.dihapus" @click="confirmRestore(item.id)" title="Pulihkan" class="p-1.5 rounded-lg text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:text-emerald-400 dark:hover:bg-emerald-900/30"><i class="fas fa-undo"></i></button>
              </div>
            </td>
          </tr>
        </tbody></table></div>
        <div class="flex flex-col sm:flex-row items-center justify-between px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
          <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 mb-3 sm:mb-0"><span>Tampilkan</span><select v-model="perPage" @change="changePerPage(perPage)" class="px-2 py-1 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm"><option v-for="n in perPageOptions" :key="n" :value="n">{{ n }}</option></select><span>dari {{ pagination.total }} data</span></div>
          <div class="flex items-center gap-1"><button @click="goToPage(1)" :disabled="pagination.current <= 1" class="px-2 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-30"><i class="fas fa-angle-double-left"></i></button><button @click="goToPage(pagination.current-1)" :disabled="pagination.current <= 1" class="px-2 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-30"><i class="fas fa-angle-left"></i></button><span class="px-2 text-sm text-gray-500">{{ pagination.current }} / {{ pagination.last }}</span><button @click="goToPage(pagination.current+1)" :disabled="pagination.current >= pagination.last" class="px-2 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-30"><i class="fas fa-angle-right"></i></button><button @click="goToPage(pagination.last)" :disabled="pagination.current >= pagination.last" class="px-2 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-30"><i class="fas fa-angle-double-right"></i></button></div>
        </div>
      </div>
    </div>

    <!-- DETAIL MODAL -->
    <Teleport to="body"><Transition name="modal"><div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showDetailModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col"><div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-eye mr-2 text-sky-500"></i>Detail Tagihan</h3><button @click="showDetailModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button></div><div class="overflow-y-auto flex-1 px-6 py-5 space-y-5 dark:text-white modal-scroll" v-if="selectedItem">
        <div class="grid grid-cols-2 gap-3">
          <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">No. Invoice</label><p class="font-mono font-medium text-gray-900 dark:text-white">{{ selectedItem.invoice_number }}</p></div>
          <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Pelanggan</label><p class="font-medium text-gray-900 dark:text-white">{{ selectedItem.customer_name }}</p></div>
          <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">No. Langganan</label><p class="font-mono text-gray-900 dark:text-white">{{ selectedItem.account_number || '-' }}</p></div>
          <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Kode Pelanggan</label><p class="font-mono text-gray-900 dark:text-white">{{ selectedItem.customer_code || '-' }}</p></div>
          <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Awal Usage</label><p class="text-gray-900 dark:text-white">{{ selectedItem.usage_start_date || '-' }}</p></div>
          <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Akhir Usage</label><p class="text-gray-900 dark:text-white">{{ selectedItem.usage_end_date || '-' }}</p></div>
          <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Total</label><p class="font-mono text-gray-900 dark:text-white">Rp {{ Number(selectedItem.total_amount || 0).toLocaleString('id') }}</p></div>
          <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Diskon</label><p class="font-mono text-gray-900 dark:text-white">Rp {{ Number(selectedItem.discount_amount || 0).toLocaleString('id') }}</p></div>
          <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Pajak</label><p class="font-mono text-gray-900 dark:text-white">Rp {{ Number(selectedItem.tax_amount || 0).toLocaleString('id') }}</p></div>
          <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Grand Total</label><p class="font-mono font-semibold text-gray-900 dark:text-white">Rp {{ Number(selectedItem.grand_total || 0).toLocaleString('id') }}</p></div>
          <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Jatuh Tempo</label><p class="text-gray-900 dark:text-white">{{ selectedItem.due_date || '-' }}</p></div>
          <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Status</label><p><span :class="['inline-flex px-2.5 py-0.5 rounded text-xs font-medium', statusBadgeClass(selectedItem.payment_status_label)]">{{ statusLabel(selectedItem.payment_status_label) }}</span></p></div>
          <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Dibayar Pada</label><p class="text-gray-900 dark:text-white">{{ selectedItem.paid_at || '-' }}</p></div>
          <div class="col-span-2"><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Deskripsi</label><p class="text-gray-900 dark:text-white">{{ selectedItem.description || '-' }}</p></div>
        </div>
        <div>
          <div class="flex items-center justify-between mb-2"><h4 class="text-sm font-semibold text-gray-900 dark:text-white">Riwayat Pembayaran</h4><span v-if="paymentHistory.payments.length > 0" :class="['inline-flex px-2 py-0.5 rounded-full text-xs font-medium', statusBadgeClass(paymentHistory.payment_status_label)]">{{ statusLabel(paymentHistory.payment_status_label) }}</span></div>
          <div v-if="paymentHistoryLoading" class="text-xs text-gray-500 dark:text-gray-400 py-2">Memuat...</div>
          <div v-else-if="paymentHistory.payments.length === 0" class="text-xs text-gray-500 dark:text-gray-400 italic py-2">Belum ada pembayaran.</div>
          <div v-else class="overflow-x-auto"><table class="w-full text-xs"><thead><tr class="bg-gray-50 dark:bg-gray-900"><th class="px-2 py-1.5 text-left font-semibold text-gray-600 dark:text-gray-400">Kode Pembayaran</th><th class="px-2 py-1.5 text-center font-semibold text-gray-600 dark:text-gray-400">Tgl Bayar</th><th class="px-2 py-1.5 text-right font-semibold text-gray-600 dark:text-gray-400">Nominal</th></tr></thead><tbody><tr v-for="(p, idx) in paymentHistory.payments" :key="p.id" :class="['border-b border-gray-100 dark:border-gray-700', idx % 2 === 0 ? 'bg-white dark:bg-gray-800' : 'bg-gray-50/50 dark:bg-gray-900/30']"><td class="px-2 py-1.5 font-mono text-gray-900 dark:text-white">{{ p.code }}</td><td class="px-2 py-1.5 text-center text-gray-900 dark:text-white">{{ p.payment_date }}</td><td class="px-2 py-1.5 text-right font-mono text-gray-900 dark:text-white">Rp {{ Number(p.amount_paid || 0).toLocaleString('id') }}</td></tr></tbody><tfoot><tr class="border-t-2 border-gray-300 dark:border-gray-600"><td colspan="2" class="px-2 py-1.5 text-right text-xs font-semibold text-gray-700 dark:text-gray-300">Total Pembayaran:</td><td class="px-2 py-1.5 text-right font-mono font-semibold text-emerald-700 dark:text-emerald-400">Rp {{ Number(paymentHistory.total_paid || 0).toLocaleString('id') }}</td></tr><tr><td colspan="2" class="px-2 py-1.5 text-right text-xs font-semibold text-gray-700 dark:text-gray-300">Sisa yang belum dibayar:</td><td class="px-2 py-1.5 text-right font-mono font-semibold" :class="paymentHistory.remaining > 0 ? 'text-red-700 dark:text-red-400' : 'text-emerald-700 dark:text-emerald-400'">Rp {{ Number(paymentHistory.remaining || 0).toLocaleString('id') }}</td></tr></tfoot></table></div>
        </div>
        <div>
          <h5 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Riwayat Audit</h5>
          <div class="space-y-2.5">
            <div class="flex items-start gap-3">
              <div class="w-7 h-7 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center shrink-0 mt-0.5"><i class="fas fa-plus text-emerald-600 dark:text-emerald-400 text-xs"></i></div>
              <div><p class="text-sm font-medium text-gray-900 dark:text-white">Dibuat</p><p class="text-xs text-gray-500 dark:text-gray-400">{{ selectedItem.created_at || '-' }}</p><p class="text-xs text-gray-400 dark:text-gray-500">oleh {{ selectedItem.created_by || '-' }}</p></div>
            </div>
            <div class="flex items-start gap-3">
              <div class="w-7 h-7 rounded-full bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center shrink-0 mt-0.5"><i class="fas fa-pen text-sky-600 dark:text-sky-400 text-xs"></i></div>
              <div><p class="text-sm font-medium text-gray-900 dark:text-white">Terakhir diperbarui</p><p class="text-xs text-gray-500 dark:text-gray-400">{{ selectedItem.updated_at || '-' }}</p><p class="text-xs text-gray-400 dark:text-gray-500">oleh {{ selectedItem.updated_by || '-' }}</p></div>
            </div>
            <template v-if="selectedItem.deleted_at">
              <div class="flex items-start gap-3">
                <div class="w-7 h-7 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center shrink-0 mt-0.5"><i class="fas fa-trash-alt text-red-600 dark:text-red-400 text-xs"></i></div>
                <div><p class="text-sm font-medium text-gray-900 dark:text-white">Dihapus</p><p class="text-xs text-gray-500 dark:text-gray-400">{{ selectedItem.deleted_at }}</p><p class="text-xs text-gray-400 dark:text-gray-500">oleh {{ selectedItem.deleted_by || '-' }}</p></div>
              </div>
            </template>
            <template v-if="selectedItem.restored_at">
              <div class="flex items-start gap-3">
                <div class="w-7 h-7 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center shrink-0 mt-0.5"><i class="fas fa-undo-alt text-emerald-600 dark:text-emerald-400 text-xs"></i></div>
                <div><p class="text-sm font-medium text-gray-900 dark:text-white">Dipulihkan</p><p class="text-xs text-gray-500 dark:text-gray-400">{{ selectedItem.restored_at }}</p><p class="text-xs text-gray-400 dark:text-gray-500">oleh {{ selectedItem.restored_by || '-' }}</p></div>
              </div>
            </template>
          </div>
        </div>
        <div v-if="can('tagihan.export')" class="flex items-center justify-end gap-2 pt-4 border-t border-gray-200 dark:border-gray-700"><a :href="`/operator-perusahaan/tagihan/${selectedItem.id}/export-pdf`" target="_blank" class="inline-flex items-center px-3 py-1.5 bg-red-600 text-white text-xs font-medium rounded-lg hover:bg-red-700 transition-colors"><i class="fas fa-file-pdf mr-1.5"></i>Export PDF</a><a :href="`/operator-perusahaan/tagihan/${selectedItem.id}/export-word`" target="_blank" class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700 transition-colors"><i class="fas fa-file-word mr-1.5"></i>Export Word</a></div>
      </div></div></div></Transition></Teleport>

    <!-- CREATE MODAL -->
    <Teleport to="body"><Transition name="modal"><div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showCreateModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><form @submit.prevent="submitCreate" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col"><div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-plus text-emerald-500 mr-2"></i>Tambah Tagihan</h3><button type="button" @click="showCreateModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button></div><div class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll">
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Langganan <span class="text-red-500">*</span></label><SearchableSelectAjax v-model="createForm.cust_internet_id" url="/operator-perusahaan/api/search/langganans" placeholder="— Pilih Langganan —" /><p v-if="createForm.errors.cust_internet_id" class="text-red-500 text-xs mt-1">{{ createForm.errors.cust_internet_id }}</p></div>
        <div class="grid grid-cols-2 gap-3"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Awal Usage</label><input v-model="createForm.usage_start_date" type="date" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none dark:[color-scheme:dark]" /></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Akhir Usage</label><input v-model="createForm.usage_end_date" type="date" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none dark:[color-scheme:dark]" /></div></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Total Tagihan <span class="text-red-500">*</span></label><input v-model="createForm.total_amount" type="number" placeholder="0" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" /></div>
        <div class="grid grid-cols-2 gap-3"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Diskon</label><input v-model="createForm.discount_amount" type="number" placeholder="0" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" /></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Pajak</label><input v-model="createForm.tax_amount" type="number" placeholder="0" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" /></div></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Jatuh Tempo</label><input v-model="createForm.due_date" type="date" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none dark:[color-scheme:dark]" /></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Deskripsi</label><textarea v-model="createForm.description" rows="2" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none resize-none"></textarea></div>
      </div><div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700"><button type="button" @click="showCreateModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button><button type="submit" :disabled="createForm.processing" class="px-6 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm disabled:opacity-50"><i class="fas fa-save mr-1.5"></i>Simpan</button></div></form></div></Transition></Teleport>

    <!-- EDIT MODAL -->
    <Teleport to="body"><Transition name="modal"><div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showEditModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><form @submit.prevent="submitEdit" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col"><div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-edit text-sky-500 mr-2"></i>Edit Tagihan</h3><button type="button" @click="showEditModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button></div><div class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll">
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Langganan <span class="text-red-500">*</span></label><SearchableSelectAjax v-model="editForm.cust_internet_id" url="/operator-perusahaan/api/search/langganans" placeholder="— Pilih Langganan —" :selected-label="selectedItem?.customer_name" /><p v-if="editForm.errors.cust_internet_id" class="text-red-500 text-xs mt-1">{{ editForm.errors.cust_internet_id }}</p></div>
        <div class="grid grid-cols-2 gap-3"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Awal Usage</label><input v-model="editForm.usage_start_date" type="date" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none dark:[color-scheme:dark]" /></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Akhir Usage</label><input v-model="editForm.usage_end_date" type="date" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none dark:[color-scheme:dark]" /></div></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Total Tagihan <span class="text-red-500">*</span></label><input v-model="editForm.total_amount" type="number" placeholder="0" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" /></div>
        <div class="grid grid-cols-2 gap-3"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Diskon</label><input v-model="editForm.discount_amount" type="number" placeholder="0" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" /></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Pajak</label><input v-model="editForm.tax_amount" type="number" placeholder="0" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" /></div></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Jatuh Tempo</label><input v-model="editForm.due_date" type="date" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none dark:[color-scheme:dark]" /></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Deskripsi</label><textarea v-model="editForm.description" rows="2" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none resize-none"></textarea></div>
      </div><div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700"><button type="button" @click="showEditModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button><button type="submit" :disabled="editForm.processing" class="px-6 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm disabled:opacity-50"><i class="fas fa-check mr-1.5"></i>Update</button></div></form></div></Transition></Teleport>

    <!-- GENERATE MODAL -->
    <Teleport to="body"><Transition name="modal"><div v-if="showGenerateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showGenerateModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><form @submit.prevent="submitGenerate" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md"><div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-magic text-purple-500 mr-2"></i>Generate Tagihan Massal</h3><button type="button" @click="showGenerateModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button></div><div class="px-6 py-5 space-y-4">
        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-3 text-xs text-amber-700 dark:text-amber-400"><i class="fas fa-info-circle mr-1"></i> Generate akan membuat tagihan untuk semua langganan aktif pada cycle & periode yang dipilih.</div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Billing Cycle <span class="text-red-500">*</span></label>
          <div class="grid grid-cols-4 gap-2">
            <label v-for="c in [{v:'daily',l:'Harian',i:'fa-calendar-day'},{v:'weekly',l:'Mingguan',i:'fa-calendar-week'},{v:'monthly',l:'Bulanan',i:'fa-calendar'},{v:'yearly',l:'Tahunan',i:'fa-calendar-alt'}]" :key="c.v" :class="['flex flex-col items-center justify-center px-2 py-2.5 rounded-lg border cursor-pointer text-xs font-medium transition-colors', generateForm.cycle === c.v ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300' : 'border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:border-gray-400 dark:hover:border-gray-500']"><input v-model="generateForm.cycle" type="radio" :value="c.v" class="sr-only" />{{ c.l }}<i :class="['fas mt-1', c.i, 'text-base']"></i></label>
          </div>
        </div>
        <div v-if="generateForm.cycle === 'daily' || generateForm.cycle === 'weekly'">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Usage Date <span class="text-red-500">*</span></label>
          <input v-model="generateForm.usage_date" type="date" :min="generateForm.cycle === 'weekly' ? null : new Date().toISOString().slice(0, 10)" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none dark:[color-scheme:dark]" />
          <p class="text-xs text-gray-500 mt-1" v-if="generateForm.cycle === 'weekly'">Akan di-snap ke hari Senin (Senin minggu itu)</p>
        </div>
        <div v-if="generateForm.cycle === 'monthly'" class="grid grid-cols-2 gap-3">
          <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Tahun <span class="text-red-500">*</span></label><input v-model="generateForm.period_year" type="number" min="2020" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" /></div>
          <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Bulan <span class="text-red-500">*</span></label><select v-model="generateForm.period_month" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none"><option v-for="m in 12" :key="m" :value="m">{{ new Date(2000, m-1, 1).toLocaleString('id', { month: 'long' }) }}</option></select></div>
        </div>
        <div v-if="generateForm.cycle === 'yearly'">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Tahun <span class="text-red-500">*</span></label>
          <input v-model="generateForm.period_year" type="number" min="2020" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" />
        </div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Jatuh Tempo (opsional)</label><input v-model="generateForm.due_date" type="date" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none dark:[color-scheme:dark]" /><p class="text-xs text-gray-500 mt-1">Kosongkan untuk menggunakan default 30 hari setelah akhir periode</p></div>
      </div><div class="flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700"><button type="button" @click="showGenerateModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button><button type="submit" :disabled="generateForm.processing" class="px-6 py-2.5 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors shadow-sm disabled:opacity-50"><i class="fas fa-magic mr-1.5"></i>Generate</button></div></form></div></Transition></Teleport>

    <!-- IMPORT MODAL -->
    <Teleport to="body"><Transition name="modal"><div v-if="showImportModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showImportModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><form @submit.prevent="submitImport" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md"><div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-file-import text-emerald-500 mr-2"></i>Import Tagihan</h3><button type="button" @click="showImportModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button></div><div class="px-6 py-5 space-y-4">
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3 text-xs text-blue-700 dark:text-blue-400"><i class="fas fa-info-circle mr-1"></i> File harus format .xlsx atau .csv. Download template untuk参照.</div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">File Excel <span class="text-red-500">*</span></label><input @change="importForm.file = $event.target.files[0]" type="file" accept=".xlsx,.csv" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100" /><p v-if="importForm.errors.file" class="text-red-500 text-xs mt-1">{{ importForm.errors.file }}</p></div>
      </div><div class="flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700"><button type="button" @click="showImportModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button><button type="submit" :disabled="importing || !importForm.file" class="px-6 py-2.5 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors shadow-sm disabled:opacity-50"><i :class="['fas', importing ? 'fa-spinner fa-spin' : 'fa-upload', 'mr-1.5']"></i>{{ importing ? 'Mengimport...' : 'Import' }}</button></div></form></div></Transition></Teleport>

    <!-- DELETE MODAL -->
    <Teleport to="body"><Transition name="modal"><div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showDeleteModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-sm"><div class="px-6 py-5 text-center"><div class="w-14 h-14 mx-auto rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-4"><i class="fas fa-exclamation-triangle text-red-500 text-xl"></i></div><h3 class="text-lg font-semibold mb-2">Hapus Tagihan?</h3><p class="text-sm text-gray-500">Anda akan menghapus tagihan <strong>{{ selectedItem?.invoice_number }}</strong> dari <strong>{{ selectedItem?.customer_name }}</strong>.</p></div><div class="flex justify-center gap-3 px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700"><button @click="showDeleteModal = false" class="px-5 py-2.5 bg-gray-200 dark:bg-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">Batal</button><button @click="confirmDelete" class="px-5 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700"><i class="fas fa-trash-alt mr-1.5"></i> Hapus</button></div></div></div></Transition></Teleport>
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