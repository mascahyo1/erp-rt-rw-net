<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import OperatorPerusahaanLayout from '@/Layouts/OperatorPerusahaanLayout.vue';
import { useToast } from '@/Composables/useToast';
import SearchableSelectAjax from '@/Components/SearchableSelectAjax.vue';

defineOptions({ layout: OperatorPerusahaanLayout });

const props = defineProps({ riwayats: Object, filters: Object });
const toast = useToast();
const page = usePage();

const can = (perm) => page.props.permissions?.includes(perm);

const searchInput = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const terhapusFilter = ref(props.filters?.terhapus || 'tidak');
const invoiceNumberFilter = ref(props.filters?.invoice_number || '');
const dueDateStart = ref(props.filters?.due_date_start || '');
const dueDateEnd = ref(props.filters?.due_date_end || '');
const sortField = ref(props.filters?.sort_field || '');
const sortDir = ref(props.filters?.sort_dir || 'asc');
const perPage = ref(props.filters?.per_page ? Number(props.filters.per_page) : 10);
const perPageOptions = [5, 10, 25, 50, 100];
const selectedIds = ref([]);
const selectedItem = ref(null);
const showCreateModal = ref(false);
const showDetailModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const showReviewModal = ref(false);
const showExportDropdown = ref(false);
const showImportModal = ref(false);
const importing = ref(false);
const importFile = ref(null);

const reviewForm = useForm({ review_status: '', review_reason: '', review_attachment: null });
const bulkReviewForm = useForm({ review_status: '', review_reason: '', review_attachment: null });
const importForm = useForm({ file: null });

function buildQuery(o = {}) {
  const p = { ...o };
  if (p.search === undefined) p.search = searchInput.value || undefined;
  if (p.status === undefined) p.status = statusFilter.value || undefined;
  if (p.terhapus === undefined) p.terhapus = terhapusFilter.value || undefined;
  if (p.invoice_number === undefined) p.invoice_number = invoiceNumberFilter.value || undefined;
  if (p.due_date_start === undefined) p.due_date_start = dueDateStart.value || undefined;
  if (p.due_date_end === undefined) p.due_date_end = dueDateEnd.value || undefined;
  if (p.sort_field === undefined) p.sort_field = sortField.value || undefined;
  if (p.sort_dir === undefined) p.sort_dir = sortDir.value || undefined;
  if (p.per_page === undefined) p.per_page = perPage.value;
  Object.keys(p).forEach(k => { if (p[k] === undefined) delete p[k]; });
  return p;
}
function fetchData(o = {}) {
  router.get('/operator-perusahaan/riwayat-insentif', buildQuery(o), { preserveState: true, preserveScroll: true, replace: true });
}
function applySearch() { fetchData({ search: searchInput.value || undefined, status: statusFilter.value || undefined, terhapus: terhapusFilter.value, page: 1 }); }
function clearSearch() { searchInput.value = ''; fetchData({ search: undefined, status: statusFilter.value || undefined, terhapus: terhapusFilter.value, page: 1 }); }
function applyFilters() { fetchData({ page: 1 }); }
function resetFilters() { searchInput.value = ''; statusFilter.value = ''; terhapusFilter.value = 'tidak'; invoiceNumberFilter.value = ''; dueDateStart.value = ''; dueDateEnd.value = ''; fetchData({ search: undefined, status: undefined, terhapus: 'tidak', invoice_number: undefined, due_date_start: undefined, due_date_end: undefined, page: 1 }); }
function sort(f) {
  if (sortField.value === f) { if (sortDir.value === 'asc') sortDir.value = 'desc'; else { sortField.value = ''; sortDir.value = 'asc'; } }
  else { sortField.value = f; sortDir.value = 'asc'; }
  fetchData({ sort_field: sortField.value || undefined, sort_dir: sortDir.value || undefined });
}
function sortIcon(f) { if (sortField.value !== f) return 'fa-sort'; return sortDir.value === 'asc' ? 'fa-sort-up' : 'fa-sort-down'; }
function toggleSelect(id) { const i = selectedIds.value.indexOf(id); i === -1 ? selectedIds.value.push(id) : selectedIds.value.splice(i, 1); }
function toggleSelectAll() {
  if (isAllSelected.value) {
    selectedIds.value = [];
  } else {
    selectedIds.value = items.value.map(i => i.id);
  }
}
function goToPage(p) { fetchData({ page: p }); }
function changePerPage(n) { perPage.value = n; fetchData({ per_page: n, page: 1 }); }
function statusBadgeClass(s) { if (s === 'approved') return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'; if (s === 'pending') return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'; return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'; }
function statusLabel(s) { if (s === 'approved') return 'Disetujui'; if (s === 'pending') return 'Pending'; if (s === 'rejected') return 'Ditolak'; return s; }

const createForm = useForm({ emp_incentive_id: '', cust_internet_invcs_id: '', invoice_number: '', amount: '', date: '', submitted_by_id: '', submitted_by_name: '', reason: '', attachment: null });
const editForm = useForm({ emp_incentive_id: '', cust_internet_invcs_id: '', invoice_number: '', amount: '', date: '', submitted_by_id: '', submitted_by_name: '', reason: '', attachment: null });

function openCreate() { createForm.reset(); createForm.clearErrors(); createForm.attachment = null; showCreateModal.value = true; }
function submitCreate() {
  createForm.post('/operator-perusahaan/riwayat-insentif', {
    preserveState: true, preserveScroll: true,
    onSuccess: () => { showCreateModal.value = false; fetchData(); toast.success('Riwayat insentif berhasil ditambahkan.'); },
    onError: () => toast.error('Validasi gagal.')
  });
}
function openEdit(item) {
  editForm.emp_incentive_id = item.emp_incentive_id;
  editForm.cust_internet_invcs_id = item.cust_internet_invcs_id;
  editForm.invoice_number = item.invoice_number;
  editForm.amount = item.amount;
  editForm.date = item.date;
  editForm.submitted_by_id = item.submitted_by_id || '';
  editForm.submitted_by_name = item.submitted_by_name || '';
  editForm.reason = item.reason || '';
  editForm.attachment = null;
  editForm.clearErrors();
  selectedItem.value = item;
  showEditModal.value = true;
}
function submitEdit() {
  editForm.put('/operator-perusahaan/riwayat-insentif/' + selectedItem.value.id, {
    preserveState: true, preserveScroll: true,
    onSuccess: () => { showEditModal.value = false; fetchData(); toast.success('Riwayat insentif berhasil diperbarui.'); },
    onError: () => toast.error('Validasi gagal.')
  });
}
function openDetail(item) { selectedItem.value = item; showDetailModal.value = true; }
function openDelete(item) { selectedItem.value = item; showDeleteModal.value = true; }
function confirmDelete() { router.delete('/operator-perusahaan/riwayat-insentif/' + selectedItem.value.id, { onSuccess: () => { showDeleteModal.value = false; fetchData(); toast.success('Riwayat insentif berhasil dihapus.'); } }); }
function confirmRestore(id) { router.patch('/operator-perusahaan/riwayat-insentif/' + id + '/restore', { onSuccess: () => { fetchData(); toast.success('Riwayat insktif berhasil dipulihkan.'); } }); }
function bulkDelete() { router.post('/operator-perusahaan/riwayat-insentif/bulk-delete', { ids: selectedIds.value }, { onSuccess: () => { selectedIds.value = []; selectAll.value = false; fetchData(); toast.success('Riwayat insentif berhasil dihapus.'); } }); }
function bulkRestore() { router.post('/operator-perusahaan/riwayat-insentif/bulk-restore', { ids: selectedIds.value }, { onSuccess: () => { selectedIds.value = []; selectAll.value = false; fetchData(); toast.success('Riwayat insktif berhasil dipulihkan.'); } }); }

function openReview(item) {
  reviewForm.reset();
  reviewForm.review_status = '';
  reviewForm.review_reason = '';
  reviewForm.review_attachment = null;
  reviewForm.clearErrors();
  selectedItem.value = item;
  showReviewModal.value = true;
}
function submitReview() {
  reviewForm.post('/operator-perusahaan/riwayat-insentif/' + selectedItem.value.id + '/review', {
    preserveState: true, preserveScroll: true,
    onSuccess: () => { showReviewModal.value = false; fetchData(); toast.success('Review berhasil disimpan.'); },
    onError: () => toast.error('Validasi gagal.')
  });
}
function openBulkReview() {
  bulkReviewForm.reset();
  bulkReviewForm.review_status = '';
  bulkReviewForm.review_reason = '';
  bulkReviewForm.review_attachment = null;
  bulkReviewForm.clearErrors();
  showReviewModal.value = true;
}
function submitBulkReview() {
  bulkReviewForm.post('/operator-perusahaan/riwayat-insentif/bulk-review', {
    preserveState: true, preserveScroll: true,
    onSuccess: () => { showReviewModal.value = false; selectedIds.value = []; selectAll.value = false; fetchData(); toast.success('Bulk review berhasil disimpan.'); },
    onError: () => toast.error('Validasi gagal.')
  });
}

function downloadTemplate() { window.location.href = '/operator-perusahaan/riwayat-insentif/template'; }
function openImport() { importForm.reset(); importFile.value = null; showImportModal.value = true; }
function submitImport() {
  if (!importFile.value) { toast.error('Pilih file Excel terlebih dahulu.'); return; }
  importing.value = true;
  importForm.post('/operator-perusahaan/riwayat-insentif/import', {
    onSuccess: () => { showImportModal.value = false; importing.value = false; fetchData(); toast.success('Import berhasil.'); },
    onError: () => { importing.value = false; toast.error('Import gagal.'); }
  });
}
function onImportFileChange(e) { importFile.value = e.target.files[0]; importForm.file = e.target.files[0]; }

function buildFilterParams() {
  const params = new URLSearchParams();
  if (searchInput.value) params.append('search', searchInput.value);
  if (statusFilter.value) params.append('status', statusFilter.value);
  if (terhapusFilter.value && terhapusFilter.value !== 'tidak') params.append('terhapus', terhapusFilter.value);
  if (invoiceNumberFilter.value) params.append('invoice_number', invoiceNumberFilter.value);
  if (dueDateStart.value) params.append('due_date_start', dueDateStart.value);
  if (dueDateEnd.value) params.append('due_date_end', dueDateEnd.value);
  if (sortField.value) { params.append('sort_field', sortField.value); params.append('sort_dir', sortDir.value); }
  return params.toString();
}
function exportAll() {
  const params = buildFilterParams();
  window.open('/operator-perusahaan/riwayat-insentif/export?' + params, '_blank');
  showExportDropdown.value = false;
}
function exportSelected() {
  if (selectedIds.value.length === 0) { toast.error('Pilih data terlebih dahulu.'); return; }
  const params = new URLSearchParams();
  selectedIds.value.forEach(id => params.append('ids[]', id));
  const filterParams = buildFilterParams();
  if (filterParams) params.append('filters', filterParams);
  window.open('/operator-perusahaan/riwayat-insentif/export?' + params.toString(), '_blank');
  showExportDropdown.value = false;
}

const items = computed(() => props.riwayats?.data || []);
const pagination = computed(() => ({ current: props.riwayats?.current_page || 1, last: props.riwayats?.last_page || 1, total: props.riwayats?.total || 0 }));
const hasFilter = computed(() => searchInput.value || statusFilter.value || terhapusFilter.value !== 'tidak' || invoiceNumberFilter.value || dueDateStart.value || dueDateEnd.value);
const hasSelected = computed(() => selectedIds.value.length > 0);
const selectedPendingCount = computed(() => items.value.filter(i => selectedIds.value.includes(i.id) && i.review_status === 'pending').length);
const isAllSelected = computed(() => items.value.length > 0 && items.value.every(i => selectedIds.value.includes(i.id)));
</script>

<template>
  <div>
    <Head title="Riwayat Insentif | Perusahaan" />
    <ToastContainer />
    <div class="space-y-6">
      <nav class="flex items-center gap-1.5 text-sm"><Link href="/operator-perusahaan/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-sky-600 dark:hover:text-sky-400 transition-colors"><i class="fas fa-home"></i></Link><i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i><span class="text-gray-900 dark:text-white font-medium">Riwayat Insentif</span></nav>
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div><h2 class="text-2xl font-bold text-gray-900 dark:text-white">Riwayat Insentif</h2><p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Riwayat perhitungan insentif untuk setiap transaksi.</p></div>
        <div class="flex items-center gap-2">
          <button v-if="can('riwayat-insentif.import') && terhapusFilter !== 'ya'" @click="openImport" class="inline-flex items-center px-3 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors shadow-sm"><i class="fas fa-file-import mr-1.5"></i> Import</button>
          <button v-if="can('riwayat-insentif.import')" @click="downloadTemplate" class="inline-flex items-center px-3 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors shadow-sm"><i class="fas fa-download mr-1.5"></i> Template</button>
          <div v-if="can('riwayat-insentif.export')" class="relative">
            <button @click="showExportDropdown = !showExportDropdown" class="inline-flex items-center px-3 py-2 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm">
              <i class="fas fa-file-export mr-1.5"></i> Export <i class="fas fa-chevron-down ml-1.5 text-xs"></i>
            </button>
            <div v-if="showExportDropdown" class="absolute right-0 mt-1 w-44 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-lg py-1 z-30">
              <button @click="exportAll" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"><i class="fas fa-list mr-2 text-emerald-500"></i>Export Semua</button>
              <button @click="exportSelected" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"><i class="fas fa-check-square mr-2 text-emerald-500"></i>Export Selected ({{ selectedIds.length }})</button>
            </div>
          </div>
          <button v-if="can('riwayat-insentif.create') && terhapusFilter !== 'ya'" @click="openCreate" class="inline-flex items-center px-4 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm"><i class="fas fa-plus mr-1.5"></i> Tambah Riwayat</button>
        </div>
      </div>
      <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between"><div class="relative w-full sm:w-72"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-search text-gray-400 text-sm"></i></div><input v-model="searchInput" type="text" placeholder="Cari..." class="w-full pl-10 pr-16 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm focus:ring-2 focus:ring-sky-500 outline-none" @keydown.enter="applySearch" /><div class="absolute inset-y-0 right-0 flex items-center gap-1 pr-1.5"><button v-if="searchInput" @click="clearSearch" class="p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-white" title="Clear"><i class="fas fa-times text-xs"></i></button><button @click="applySearch" class="px-2 py-1 rounded bg-sky-600 text-white hover:bg-sky-700" title="Cari"><i class="fas fa-search text-xs"></i></button></div></div><div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400"><span>{{ props.riwayats?.total || 0 }} data</span><button v-if="hasFilter" @click="resetFilters" class="text-xs text-red-500 hover:text-red-700 dark:hover:text-red-400 underline">Reset filter</button></div></div>

      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm">
        <div class="flex flex-wrap items-end gap-4">
          <div class="min-w-[160px]">
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Status</label>
            <select v-model="statusFilter" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors">
              <option value="">Semua</option><option value="pending">Pending</option><option value="approved">Disetujui</option><option value="rejected">Ditolak</option>
            </select>
          </div>
          <div class="min-w-[160px]">
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Kode Invoice</label>
            <input v-model="invoiceNumberFilter" type="text" placeholder="Filter invoice..." class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors" />
          </div>
          <div class="min-w-[140px]">
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Tgl Jatuh Tempo</label>
            <input v-model="dueDateStart" type="date" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors" />
          </div>
          <div class="min-w-[140px]">
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">S/d</label>
            <input v-model="dueDateEnd" type="date" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors" />
          </div>
          <div class="min-w-[120px]">
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Terhapus</label>
            <select v-model="terhapusFilter" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors">
              <option value="tidak">Tidak</option><option value="ya">Ya</option>
            </select>
          </div>
          <button @click="applyFilters" class="px-4 py-2 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm"><i class="fas fa-filter mr-1.5"></i>Filter</button>
        </div>
      </div>

      <!-- Bulk action bar -->
      <div v-if="hasSelected" class="flex items-center justify-between px-4 py-3 bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-800 rounded-xl shadow-sm">
        <span class="text-sm font-medium text-sky-700 dark:text-sky-300"><i class="fas fa-check-circle mr-1.5"></i> {{ selectedIds.length }} data dipilih</span>
        <div class="flex items-center gap-2">
          <button v-if="can('riwayat-insentif.persetujuan') && selectedPendingCount > 0" @click="openBulkReview" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-sky-600 text-white hover:bg-sky-700"><i class="fas fa-clipboard-check mr-1"></i> Review ({{ selectedPendingCount }} pending)</button>
          <button v-if="terhapusFilter === 'ya'" @click="bulkRestore()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-emerald-600 text-white hover:bg-emerald-700"><i class="fas fa-undo-alt mr-1"></i> Pulihkan</button>
          <button v-if="can('riwayat-insentif.delete')" @click="bulkDelete()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-red-600 text-white hover:bg-red-700"><i class="fas fa-trash-alt mr-1"></i> Hapus</button>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm min-w-[900px]">
            <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
              <tr>
                <th class="px-4 py-3 w-10">
                  <input :checked="isAllSelected" type="checkbox" @click="toggleSelectAll" class="rounded border-gray-300 text-sky-600" />
                </th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Insentif</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">No. Invoice</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Pelanggan</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Pengaju</th>
                <th @click="sort('amount')" class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none">
                  <span class="inline-flex items-center gap-1">Jumlah <i :class="['fas', sortIcon('amount'), 'text-[10px]', sortField === 'amount' ? 'text-sky-500' : 'text-gray-400']"></i></span>
                </th>
                <th @click="sort('date')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none">
                  <span class="inline-flex items-center gap-1">Tanggal <i :class="['fas', sortIcon('date'), 'text-[10px]', sortField === 'date' ? 'text-sky-500' : 'text-gray-400']"></i></span>
                </th>
                <th @click="sort('review_status')" class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none">
                  <span class="inline-flex items-center gap-1">Status <i :class="['fas', sortIcon('review_status'), 'text-[10px]', sortField === 'review_status' ? 'text-sky-500' : 'text-gray-400']"></i></span>
                </th>
                <th @click="sort('created_at')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none">
                  <span class="inline-flex items-center gap-1">Tgl Dibuat <i :class="['fas', sortIcon('created_at'), 'text-[10px]', sortField === 'created_at' ? 'text-sky-500' : 'text-gray-400']"></i></span>
                </th>
                <th class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-400 w-28">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="items.length === 0">
                <td colspan="10" class="px-4 py-16 text-center text-gray-400 dark:text-gray-500">
                  <i class="fas fa-inbox text-4xl mb-3 block"></i><span class="text-sm">Tidak ada data riwayat insentif</span>
                </td>
              </tr>
              <tr
                v-for="item in items" :key="item.id"
                class="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors cursor-pointer"
                :class="{'opacity-60': item.dihapus}"
                @click="toggleSelect(item.id)"
              >
                <td class="px-4 py-3" @click.stop>
                  <input :checked="selectedIds.includes(item.id)" type="checkbox" @click.stop="toggleSelect(item.id)" class="rounded border-gray-300 text-sky-600" />
                </td>
                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ item.incentive_name || '-' }}</td>
                <td class="px-4 py-3"><span class="font-mono text-xs text-gray-900 dark:text-white">{{ item.invoice_number || '-' }}</span></td>
                <td class="px-4 py-3">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-sky-900/40 flex items-center justify-center text-sky-600 dark:text-sky-400 font-semibold text-xs">{{ (item.customer_name || '?')[0] }}</div>
                    <div>
                      <span class="font-medium text-gray-900 dark:text-white">{{ item.customer_name }}</span>
                      <span class="block text-xs text-gray-500 dark:text-gray-400">{{ item.phone_number }}</span>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-3 text-gray-700 dark:text-gray-300 text-xs">{{ item.submitted_by_name || '-' }}</td>
                <td class="px-4 py-3 text-right text-gray-900 dark:text-white font-mono text-xs">{{ item.amount ? 'Rp ' + Number(item.amount).toLocaleString('id') : '-' }}</td>
                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">{{ item.date }}</td>
                <td class="px-4 py-3 text-center"><span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium', statusBadgeClass(item.review_status)]">{{ statusLabel(item.review_status) }}</span></td>
                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">{{ item.created_at }}</td>
                <td class="px-4 py-3" @click.stop>
                  <div class="flex items-center justify-center gap-1">
                    <button v-if="can('riwayat-insentif.detail')" @click="openDetail(item)" title="Detail" class="p-1.5 rounded-lg text-gray-400 hover:text-sky-600 hover:bg-sky-50 dark:hover:text-sky-400 dark:hover:bg-indigo-900/30"><i class="fas fa-eye"></i></button>
                    <button v-if="can('riwayat-insentif.edit') && !item.dihapus && item.review_status === 'pending'" @click="openEdit(item)" title="Edit" class="p-1.5 rounded-lg text-gray-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:text-amber-400 dark:hover:bg-amber-900/30"><i class="fas fa-edit"></i></button>
                    <button v-if="can('riwayat-insentif.persetujuan') && !item.dihapus && item.review_status === 'pending'" @click="openReview(item)" title="Review" class="p-1.5 rounded-lg text-gray-400 hover:text-sky-600 hover:bg-sky-50 dark:hover:text-sky-400 dark:hover:bg-indigo-900/30"><i class="fas fa-clipboard-check"></i></button>
                    <button v-if="can('riwayat-insentif.delete') && !item.dihapus" @click="openDelete(item)" title="Hapus" class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:text-red-400 dark:hover:bg-red-900/30"><i class="fas fa-trash-alt"></i></button>
                    <button v-if="item.dihapus" @click="confirmRestore(item.id)" title="Pulihkan" class="p-1.5 rounded-lg text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:text-emerald-400 dark:hover:bg-emerald-900/30"><i class="fas fa-undo"></i></button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="flex flex-col sm:flex-row items-center justify-between px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
          <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 mb-3 sm:mb-0"><span>Tampilkan</span><select v-model="perPage" @change="changePerPage(perPage)" class="px-2 py-1 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm"><option v-for="n in perPageOptions" :key="n" :value="n">{{ n }}</option></select><span>dari {{ pagination.total }} data</span></div>
          <div class="flex items-center gap-1"><button @click="goToPage(1)" :disabled="pagination.current <= 1" class="px-2 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-30"><i class="fas fa-angle-double-left"></i></button><button @click="goToPage(pagination.current-1)" :disabled="pagination.current <= 1" class="px-2 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-30"><i class="fas fa-angle-left"></i></button><span class="px-2 text-sm text-gray-500">{{ pagination.current }} / {{ pagination.last }}</span><button @click="goToPage(pagination.current+1)" :disabled="pagination.current >= pagination.last" class="px-2 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-30"><i class="fas fa-angle-right"></i></button><button @click="goToPage(pagination.last)" :disabled="pagination.current >= pagination.last" class="px-2 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-30"><i class="fas fa-angle-double-right"></i></button></div>
        </div>
      </div>
    </div>

    <!-- Detail Modal -->
    <Teleport to="body"><Transition name="modal"><div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showDetailModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg"><div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white">Detail Riwayat Insentif</h3><button @click="showDetailModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700"><i class="fas fa-times"></i></button></div><div class="px-6 py-5 space-y-4" v-if="selectedItem"><div class="grid grid-cols-2 gap-4"><div><label class="text-xs text-gray-500 dark:text-gray-400">Insentif</label><p class="font-medium text-gray-900 dark:text-white">{{ selectedItem.incentive_name }}</p></div><div><label class="text-xs text-gray-500 dark:text-gray-400">No. Invoice</label><p class="font-mono text-sm text-gray-900 dark:text-white">{{ selectedItem.invoice_number || '-' }}</p></div><div><label class="text-xs text-gray-500 dark:text-gray-400">Pelanggan</label><p class="font-medium text-gray-900 dark:text-white">{{ selectedItem.customer_name }}</p></div><div><label class="text-xs text-gray-500 dark:text-gray-400">No. Telepon</label><p class="text-sm text-gray-700 dark:text-gray-300">{{ selectedItem.phone_number }}</p></div><div><label class="text-xs text-gray-500 dark:text-gray-400">Jumlah</label><p class="font-mono font-semibold text-gray-900 dark:text-white">Rp {{ Number(selectedItem.amount || 0).toLocaleString('id') }}</p></div><div><label class="text-xs text-gray-500 dark:text-gray-400">Tanggal</label><p class="text-gray-700 dark:text-gray-300">{{ selectedItem.date }}</p></div><div><label class="text-xs text-gray-500 dark:text-gray-400">Pengaju</label><p class="text-gray-700 dark:text-gray-300">{{ selectedItem.submitted_by_name || '-' }}</p></div><div><label class="text-xs text-gray-500 dark:text-gray-400">Status</label><p><span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium', statusBadgeClass(selectedItem.review_status)]">{{ statusLabel(selectedItem.review_status) }}</span></p></div></div><div v-if="selectedItem.reason" class="mt-4"><label class="text-xs text-gray-500 dark:text-gray-400">Alasan</label><p class="text-sm text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900 rounded-lg p-3">{{ selectedItem.reason }}</p></div><div v-if="selectedItem.attachment" class="mt-4"><label class="text-xs text-gray-500 dark:text-gray-400">Bukti Pengajuan</label><a :href="'/storage/' + selectedItem.attachment" target="_blank" class="inline-flex items-center gap-1.5 text-sm text-sky-600 hover:text-sky-700 dark:text-sky-400"><i class="fas fa-paperclip"></i> Lihat lampiran</a></div><div class="grid grid-cols-2 gap-4 mt-4 pt-4 border-t border-gray-200 dark:border-gray-700"><div><label class="text-xs text-gray-500 dark:text-gray-400">Tgl Dibuat</label><p class="text-sm text-gray-700 dark:text-gray-300">{{ selectedItem.created_at }}</p></div><div><label class="text-xs text-gray-500 dark:text-gray-400">Diubah</label><p class="text-sm text-gray-700 dark:text-gray-300">{{ selectedItem.updated_at }}</p></div><div v-if="selectedItem.reviewed_at"><label class="text-xs text-gray-500 dark:text-gray-400">Tgl Direview</label><p class="text-sm text-gray-700 dark:text-gray-300">{{ selectedItem.reviewed_at }}</p></div><div v-if="selectedItem.review_reason"><label class="text-xs text-gray-500 dark:text-gray-400">Alasan Review</label><p class="text-sm text-gray-700 dark:text-gray-300">{{ selectedItem.review_reason }}</p></div></div></div></div></div></Transition></Teleport>

    <!-- CREATE MODAL -->
    <Teleport to="body"><Transition name="modal"><div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showCreateModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><form @submit.prevent="submitCreate" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col"><div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-plus text-emerald-500 mr-2"></i>Tambah Riwayat Insentif</h3><button type="button" @click="showCreateModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button></div><div class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll">
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Kode Invoice <span class="text-red-500">*</span></label><input v-model="createForm.invoice_number" type="text" placeholder="Contoh: INV/2025/001" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" /><p v-if="createForm.errors.invoice_number" class="text-red-500 text-xs mt-1">{{ createForm.errors.invoice_number }}</p></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Insentif <span class="text-red-500">*</span></label><SearchableSelectAjax v-model="createForm.emp_incentive_id" url="/operator-perusahaan/api/search/incentives" placeholder="— Pilih Insentif —" /><p v-if="createForm.errors.emp_incentive_id" class="text-red-500 text-xs mt-1">{{ createForm.errors.emp_incentive_id }}</p></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Invoice <span class="text-red-500">*</span></label><SearchableSelectAjax v-model="createForm.cust_internet_invcs_id" url="/operator-perusahaan/api/search/invoices" placeholder="— Pilih Invoice —" /><p v-if="createForm.errors.cust_internet_invcs_id" class="text-red-500 text-xs mt-1">{{ createForm.errors.cust_internet_invcs_id }}</p></div>
        <div class="grid grid-cols-2 gap-3"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Jumlah <span class="text-red-500">*</span></label><input v-model="createForm.amount" type="number" placeholder="0" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" /><p v-if="createForm.errors.amount" class="text-red-500 text-xs mt-1">{{ createForm.errors.amount }}</p></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Tanggal <span class="text-red-500">*</span></label><input v-model="createForm.date" type="date" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" /><p v-if="createForm.errors.date" class="text-red-500 text-xs mt-1">{{ createForm.errors.date }}</p></div></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Diajukan Untuk</label><SearchableSelectAjax v-model="createForm.submitted_by_id" url="/operator-perusahaan/api/search/employees" placeholder="— Pilih Karyawan —" :on-select="(opt) => { createForm.submitted_by_name = opt ? opt.label.split(' (')[0] : ''; }" /><p v-if="createForm.errors.submitted_by_id" class="text-red-500 text-xs mt-1">{{ createForm.errors.submitted_by_id }}</p></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Alasan Pengajuan</label><textarea v-model="createForm.reason" rows="2" placeholder="Jelaskan alasan pengajuan..." class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none resize-none"></textarea><p v-if="createForm.errors.reason" class="text-red-500 text-xs mt-1">{{ createForm.errors.reason }}</p></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Bukti Pengajuan</label><input @change="createForm.attachment = $event.target.files[0]" type="file" accept=".jpg,.jpeg,.png,.pdf" class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-sky-50 file:text-sky-700 dark:file:bg-sky-900/30 dark:file:text-sky-400 hover:file:bg-sky-100 dark:hover:file:bg-sky-900/50" /><p v-if="createForm.errors.attachment" class="text-red-500 text-xs mt-1">{{ createForm.errors.attachment }}</p></div>
      </div><div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700"><button type="button" @click="showCreateModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button><button type="submit" :disabled="createForm.processing" class="px-6 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm disabled:opacity-50"><i class="fas fa-save mr-1.5"></i>Simpan</button></div></form></div></Transition></Teleport>

    <!-- EDIT MODAL -->
    <Teleport to="body"><Transition name="modal"><div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showEditModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><form @submit.prevent="submitEdit" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col"><div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-edit text-sky-500 mr-2"></i>Edit Riwayat Insentif</h3><button type="button" @click="showEditModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button></div><div class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll">
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Kode Invoice <span class="text-red-500">*</span></label><input v-model="editForm.invoice_number" type="text" placeholder="Contoh: INV/2025/001" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" /><p v-if="editForm.errors.invoice_number" class="text-red-500 text-xs mt-1">{{ editForm.errors.invoice_number }}</p></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Insentif <span class="text-red-500">*</span></label><SearchableSelectAjax v-model="editForm.emp_incentive_id" url="/operator-perusahaan/api/search/incentives" placeholder="— Pilih Insentif —" :selected-label="selectedItem?.incentive_name" /><p v-if="editForm.errors.emp_incentive_id" class="text-red-500 text-xs mt-1">{{ editForm.errors.emp_incentive_id }}</p></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Invoice <span class="text-red-500">*</span></label><SearchableSelectAjax v-model="editForm.cust_internet_invcs_id" url="/operator-perusahaan/api/search/invoices" placeholder="— Pilih Invoice —" :selected-label="selectedItem?.invoice_number" /><p v-if="editForm.errors.cust_internet_invcs_id" class="text-red-500 text-xs mt-1">{{ editForm.errors.cust_internet_invcs_id }}</p></div>
        <div class="grid grid-cols-2 gap-3"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Jumlah <span class="text-red-500">*</span></label><input v-model="editForm.amount" type="number" placeholder="0" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" /><p v-if="editForm.errors.amount" class="text-red-500 text-xs mt-1">{{ editForm.errors.amount }}</p></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Tanggal <span class="text-red-500">*</span></label><input v-model="editForm.date" type="date" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" /><p v-if="editForm.errors.date" class="text-red-500 text-xs mt-1">{{ editForm.errors.date }}</p></div></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Diajukan Untuk</label><input v-model="editForm.submitted_by_name" type="text" placeholder="Nama orang yang diajukan" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" /><p v-if="editForm.errors.submitted_by_name" class="text-red-500 text-xs mt-1">{{ editForm.errors.submitted_by_name }}</p></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Alasan Pengajuan</label><textarea v-model="editForm.reason" rows="2" placeholder="Jelaskan alasan pengajuan..." class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none resize-none"></textarea><p v-if="editForm.errors.reason" class="text-red-500 text-xs mt-1">{{ editForm.errors.reason }}</p></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Bukti Pengajuan <span class="text-xs text-gray-400">(kosongkan jika tidak diubah)</span></label><input @change="editForm.attachment = $event.target.files[0]" type="file" accept=".jpg,.jpeg,.png,.pdf" class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-sky-50 file:text-sky-700 dark:file:bg-sky-900/30 dark:file:text-sky-400 hover:file:bg-sky-100 dark:hover:file:bg-sky-900/50" /><p v-if="editForm.errors.attachment" class="text-red-500 text-xs mt-1">{{ editForm.errors.attachment }}</p></div>
      </div><div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700"><button type="button" @click="showEditModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button><button type="submit" :disabled="editForm.processing" class="px-6 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm disabled:opacity-50"><i class="fas fa-check mr-1.5"></i>Update</button></div></form></div></Transition></Teleport>

    <!-- IMPORT MODAL -->
    <Teleport to="body"><Transition name="modal"><div v-if="showImportModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showImportModal = false; importForm.reset(); importFile = null;"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><form @submit.prevent="submitImport" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] flex flex-col"><div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-file-import text-emerald-500 mr-2"></i>Import Riwayat Insentif</h3><button type="button" @click="showImportModal = false; importForm.reset();" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button></div><div class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll"><div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg px-4 py-3 text-sm text-amber-700 dark:text-amber-400"><i class="fas fa-info-circle mr-1.5"></i> Pastikan file Excel sesuai dengan template. Kolom: Nama Insentif, No. Invoice, Amount, Date, Submitted By Name, Reason.</div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">File Excel <span class="text-red-500">*</span></label><input @change="onImportFileChange" type="file" accept=".xlsx,.xls" class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-sky-50 file:text-sky-700 dark:file:bg-sky-900/30 dark:file:text-sky-400 hover:file:bg-sky-100 dark:hover:file:bg-sky-900/50" /><p v-if="importForm.errors.file" class="text-red-500 text-xs mt-1">{{ importForm.errors.file }}</p></div><div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400"><i class="fas fa-file-arrow-down"></i><a href="/operator-perusahaan/riwayat-insentif/template" class="text-sky-600 hover:text-sky-700 dark:text-sky-400 hover:underline">Download Template Excel</a></div></div><div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700"><button type="button" @click="showImportModal = false; importForm.reset();" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button><button type="submit" :disabled="importing" class="px-6 py-2.5 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors shadow-sm disabled:opacity-50"><i class="fas fa-upload mr-1.5"></i>{{ importing ? 'Mengimport...' : 'Import' }}</button></div></form></div></Transition></Teleport>

    <!-- REVIEW MODAL (single + bulk) -->
    <Teleport to="body"><Transition name="modal"><div v-if="showReviewModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showReviewModal = false; reviewForm.reset(); bulkReviewForm.reset();"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
      <!-- Single Review -->
      <form v-if="selectedItem && !hasSelected" @submit.prevent="submitReview" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] flex flex-col">
        <div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-clipboard-check text-sky-500 mr-2"></i>Review: {{ selectedItem?.incentive_name }}</h3><button type="button" @click="showReviewModal = false; reviewForm.reset();" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button></div>
        <div class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll">
          <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-3"><div class="text-xs text-gray-500 dark:text-gray-400 mb-1">No. Invoice</div><div class="font-mono font-medium text-gray-900 dark:text-white">{{ selectedItem?.invoice_number }}</div></div>
          <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-3"><div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Jumlah</div><div class="font-mono font-semibold text-sky-600">Rp {{ Number(selectedItem?.amount || 0).toLocaleString('id') }}</div></div>
          <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Status Review <span class="text-red-500">*</span></label><select v-model="reviewForm.review_status" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none"><option value="">— Pilih —</option><option value="approved">Disetujui</option><option value="rejected">Ditolak</option></select><p v-if="reviewForm.errors.review_status" class="text-red-500 text-xs mt-1">{{ reviewForm.errors.review_status }}</p></div>
          <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Alasan <span v-if="reviewForm.review_status === 'rejected'" class="text-red-500">*</span><span v-else class="text-xs text-gray-400">(wajib jika ditolak)</span></label><textarea v-model="reviewForm.review_reason" rows="3" placeholder="Jelaskan alasan persetujuan atau penolakan..." class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none resize-none"></textarea><p v-if="reviewForm.errors.review_reason" class="text-red-500 text-xs mt-1">{{ reviewForm.errors.review_reason }}</p></div>
          <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Lampiran Review <span class="text-xs text-gray-400">(opsional)</span></label><input @change="reviewForm.review_attachment = $event.target.files[0]" type="file" accept=".jpg,.jpeg,.png,.pdf" class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-sky-50 file:text-sky-700 dark:file:bg-sky-900/30 dark:file:text-sky-400 hover:file:bg-sky-100 dark:hover:file:bg-sky-900/50" /><p v-if="reviewForm.errors.review_attachment" class="text-red-500 text-xs mt-1">{{ reviewForm.errors.review_attachment }}</p></div>
        </div>
        <div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700"><button type="button" @click="showReviewModal = false; reviewForm.reset();" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button><button type="submit" :disabled="reviewForm.processing" class="px-6 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm disabled:opacity-50"><i class="fas fa-check mr-1.5"></i>Simpan Review</button></div>
      </form>
      <!-- Bulk Review -->
      <form v-else @submit.prevent="submitBulkReview" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] flex flex-col">
        <div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-clipboard-check text-sky-500 mr-2"></i>Bulk Review ({{ selectedIds.length }} item)</h3><button type="button" @click="showReviewModal = false; bulkReviewForm.reset();" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button></div>
        <div class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll">
          <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg px-4 py-3 text-sm text-amber-700 dark:text-amber-400"><i class="fas fa-info-circle mr-1.5"></i> {{ selectedPendingCount }} item berstatus pending akan direview.</div>
          <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Status Review <span class="text-red-500">*</span></label><select v-model="bulkReviewForm.review_status" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none"><option value="">— Pilih —</option><option value="approved">Disetujui</option><option value="rejected">Ditolak</option></select><p v-if="bulkReviewForm.errors.review_status" class="text-red-500 text-xs mt-1">{{ bulkReviewForm.errors.review_status }}</p></div>
          <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Alasan <span v-if="bulkReviewForm.review_status === 'rejected'" class="text-red-500">*</span><span v-else class="text-xs text-gray-400">(wajib jika ditolak)</span></label><textarea v-model="bulkReviewForm.review_reason" rows="3" placeholder="Jelaskan alasan persetujuan atau penolakan..." class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none resize-none"></textarea><p v-if="bulkReviewForm.errors.review_reason" class="text-red-500 text-xs mt-1">{{ bulkReviewForm.errors.review_reason }}</p></div>
          <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Lampiran Review <span class="text-xs text-gray-400">(opsional)</span></label><input @change="bulkReviewForm.review_attachment = $event.target.files[0]" type="file" accept=".jpg,.jpeg,.png,.pdf" class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-sky-50 file:text-sky-700 dark:file:bg-sky-900/30 dark:file:text-sky-400 hover:file:bg-sky-100 dark:hover:file:bg-sky-900/50" /><p v-if="bulkReviewForm.errors.review_attachment" class="text-red-500 text-xs mt-1">{{ bulkReviewForm.errors.review_attachment }}</p></div>
        </div>
        <div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700"><button type="button" @click="showReviewModal = false; bulkReviewForm.reset();" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button><button type="submit" :disabled="bulkReviewForm.processing" class="px-6 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm disabled:opacity-50"><i class="fas fa-check mr-1.5"></i>Simpan Bulk Review</button></div>
      </form>
    </div></Transition></Teleport>

    <!-- DELETE MODAL -->
    <Teleport to="body"><Transition name="modal"><div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showDeleteModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-sm"><div class="px-6 py-5 text-center"><div class="w-14 h-14 mx-auto rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-4"><i class="fas fa-exclamation-triangle text-red-500 text-xl"></i></div><h3 class="text-lg font-semibold mb-2 text-gray-900 dark:text-white">Konfirmasi Hapus</h3><p class="text-sm text-gray-600 dark:text-gray-400">Anda akan menghapus riwayat insentif <strong class="text-gray-900 dark:text-white">{{ selectedItem?.incentive_name }}</strong>. Tindakan ini tidak dapat dibatalkan.</p></div><div class="flex justify-center gap-3 px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700"><button @click="showDeleteModal = false" class="px-5 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">Batal</button><button @click="confirmDelete" class="px-5 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700"><i class="fas fa-trash-alt mr-1.5"></i> Hapus</button></div></div></div></Transition></Teleport>
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
/* Date input dark mode fix */
.dark input[type="date"],
.dark input[type="date"]::-webkit-calendar-picker-indicator {
  color-scheme: light;
}
.dark input[type="date"]::-webkit-calendar-picker-indicator {
  filter: invert(0.6);
}
</style>
