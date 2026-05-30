<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import OperatorPerusahaanLayout from '@/Layouts/OperatorPerusahaanLayout.vue';
import { useToast } from '@/Composables/useToast';
import SearchableSelectAjax from '@/Components/SearchableSelectAjax.vue';

defineOptions({ layout: OperatorPerusahaanLayout });

const props = defineProps({ pembayarans: Object, filters: Object, providerOptions: Object, paymentMethodOptions: Object });
const toast = useToast();
const page = usePage();

const can = (perm) => page.props.permissions?.includes(perm);

const searchInput = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const providerFilter = ref(props.filters?.provider || '');
const paymentMethodFilter = ref(props.filters?.payment_method || '');
const createdStartFilter = ref(props.filters?.created_start || '');
const createdEndFilter = ref(props.filters?.created_end || '');
const terhapusFilter = ref(props.filters?.terhapus || 'tidak');
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
const showPersetujuanModal = ref(false);
const showExportDropdown = ref(false);
const showImportModal = ref(false);
const importing = ref(false);
const importFile = ref(null);

const createForm = useForm({ cust_internet_invc_id: '', amount_paid: '', payment_date: '', payment_method: '', provider: '', proof_file: null, status_description: '' });
const editForm = useForm({ cust_internet_invc_id: '', amount_paid: '', payment_date: '', payment_method: '', provider: '', proof_file: null, status_description: '' });
const persetujuanForm = useForm({ status_reason: '' });
const importForm = useForm({ file: null });

function buildQuery(o = {}) {
  const p = { ...o };
  if (p.search === undefined) p.search = searchInput.value || undefined;
  if (p.status === undefined) p.status = statusFilter.value || undefined;
  if (p.provider === undefined) p.provider = providerFilter.value || undefined;
  if (p.payment_method === undefined) p.payment_method = paymentMethodFilter.value || undefined;
  if (p.created_start === undefined) p.created_start = createdStartFilter.value || undefined;
  if (p.created_end === undefined) p.created_end = createdEndFilter.value || undefined;
  if (p.terhapus === undefined) p.terhapus = terhapusFilter.value || undefined;
  if (p.sort_field === undefined) p.sort_field = sortField.value || undefined;
  if (p.sort_dir === undefined) p.sort_dir = sortDir.value || undefined;
  if (p.per_page === undefined) p.per_page = perPage.value;
  Object.keys(p).forEach(k => { if (p[k] === undefined) delete p[k]; });
  return p;
}
function fetchData(o = {}) {
  router.get('/operator-perusahaan/riwayat-pembayaran', buildQuery(o), { preserveState: true, preserveScroll: true, replace: true });
}
function applySearch() { fetchData({ search: searchInput.value || undefined, status: statusFilter.value || undefined, provider: providerFilter.value || undefined, payment_method: paymentMethodFilter.value || undefined, created_start: createdStartFilter.value || undefined, created_end: createdEndFilter.value || undefined, terhapus: terhapusFilter.value, page: 1 }); }
function clearSearch() { searchInput.value = ''; fetchData({ search: undefined, status: statusFilter.value || undefined, provider: providerFilter.value || undefined, payment_method: paymentMethodFilter.value || undefined, created_start: createdStartFilter.value || undefined, created_end: createdEndFilter.value || undefined, terhapus: terhapusFilter.value, page: 1 }); }
function applyFilters() { fetchData({ page: 1 }); }
function resetFilters() { searchInput.value = ''; statusFilter.value = ''; providerFilter.value = ''; paymentMethodFilter.value = ''; createdStartFilter.value = ''; createdEndFilter.value = ''; terhapusFilter.value = 'tidak'; fetchData({ search: undefined, status: undefined, provider: undefined, payment_method: undefined, created_start: undefined, created_end: undefined, terhapus: 'tidak', page: 1 }); }
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
function statusBadgeClass(s) { if (s === 'paid') return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'; if (s === 'pending') return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'; return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'; }
function statusLabel(s) { if (s === 'paid') return 'Lunas'; if (s === 'pending') return 'Pending'; if (s === 'cancelled') return 'Dibatalkan'; if (s === 'rejected') return 'Ditolak'; if (s === 'expired') return 'Kedaluarsa'; return s; }
function providerLabel(p) { if (p === 'internal') return 'Internal'; if (p === 'external') return 'Eksternal'; return p; }
function paymentMethodLabel(m) { if (m === 'tunai') return 'Tunai'; if (m === 'transfer_manual') return 'Transfer Manual'; return m; }

const createFormFile = ref(null);
const editFormFile = ref(null);

function openCreate() { createForm.reset(); createForm.clearErrors(); createFormFile.value = null; showCreateModal.value = true; }
function submitCreate() {
  const formData = new FormData();
  formData.append('cust_internet_invc_id', createForm.cust_internet_invc_id);
  formData.append('amount_paid', createForm.amount_paid);
  formData.append('payment_date', createForm.payment_date || '');
  formData.append('payment_method', createForm.payment_method);
  formData.append('provider', createForm.provider);
  formData.append('status_description', createForm.status_description || '');
  if (createFormFile.value) formData.append('proof_file', createFormFile.value);
  router.post('/operator-perusahaan/riwayat-pembayaran', formData, {
    preserveState: true, preserveScroll: true,
    onSuccess: () => { showCreateModal.value = false; fetchData(); toast.success('Pembayaran berhasil ditambahkan.'); },
    onError: () => toast.error('Validasi gagal.')
  });
}
function openEdit(item) {
  editForm.cust_internet_invc_id = item.cust_internet_invc_id;
  editForm.amount_paid = item.amount_paid;
  editForm.payment_date = item.payment_date || '';
  editForm.payment_method = item.payment_method;
  editForm.provider = item.provider;
  editForm.status_description = item.status_description || '';
  editForm.clearErrors();
  editFormFile.value = null;
  selectedItem.value = item;
  showEditModal.value = true;
}
function submitEdit() {
  const formData = new FormData();
  formData.append('amount_paid', editForm.amount_paid);
  formData.append('payment_date', editForm.payment_date || '');
  formData.append('payment_method', editForm.payment_method);
  formData.append('provider', editForm.provider);
  formData.append('status_description', editForm.status_description || '');
  if (editFormFile.value) formData.append('proof_file', editFormFile.value);
  router.post('/operator-perusahaan/riwayat-pembayaran/' + selectedItem.value.id + '?_method=PUT', formData, {
    preserveState: true, preserveScroll: true,
    onSuccess: () => { showEditModal.value = false; fetchData(); toast.success('Pembayaran berhasil diperbarui.'); },
    onError: () => toast.error('Validasi gagal.')
  });
}
function openDetail(item) { selectedItem.value = item; showDetailModal.value = true; }
function openDelete(item) { selectedItem.value = item; showDeleteModal.value = true; }
function confirmDelete() { router.delete('/operator-perusahaan/riwayat-pembayaran/' + selectedItem.value.id, { onSuccess: () => { showDeleteModal.value = false; fetchData(); toast.success('Pembayaran berhasil dihapus.'); } }); }
function confirmRestore(id) { router.patch('/operator-perusahaan/riwayat-pembayaran/' + id + '/restore', { onSuccess: () => { fetchData(); toast.success('Pembayaran berhasil dipulihkan.'); } }); }
function openPersetujuan(item) {
  selectedItem.value = item;
  persetujuanForm.reset();
  persetujuanForm.status_reason = '';
  persetujuanForm.clearErrors();
  showPersetujuanModal.value = true;
}
function submitPersetujuan() {
  persetujuanForm.post('/operator-perusahaan/riwayat-pembayaran/' + selectedItem.value.id + '/approve', {
    preserveState: true, preserveScroll: true,
    onSuccess: () => { showPersetujuanModal.value = false; fetchData(); toast.success('Pembayaran berhasil disetujui.'); },
    onError: () => toast.error('Validasi gagal.')
  });
}
function submitTolak() {
  if (!persetujuanForm.status_reason) { toast.error('Alasan penolakan wajib diisi.'); return; }
  persetujuanForm.post('/operator-perusahaan/riwayat-pembayaran/' + selectedItem.value.id + '/reject', {
    preserveState: true, preserveScroll: true,
    onSuccess: () => { showPersetujuanModal.value = false; fetchData(); toast.success('Pembayaran berhasil ditolak.'); },
    onError: () => toast.error('Validasi gagal.')
  });
}
function bulkDelete() { router.post('/operator-perusahaan/riwayat-pembayaran/bulk-delete', { ids: selectedIds.value }, { onSuccess: () => { selectedIds.value = []; fetchData(); toast.success('Pembayaran berhasil dihapus.'); } }); }
function bulkRestore() { router.post('/operator-perusahaan/riwayat-pembayaran/bulk-restore', { ids: selectedIds.value }, { onSuccess: () => { selectedIds.value = []; fetchData(); toast.success('Pembayaran berhasil dipulihkan.'); } }); }
function downloadTemplate() { window.location.href = '/operator-perusahaan/riwayat-pembayaran/template'; }
function openImport() { importFile.value = null; showImportModal.value = true; }
function onImportFileChange(e) { importFile.value = e.target.files[0]; importForm.file = e.target.files[0]; }
function submitImport() {
  if (!importFile.value) { toast.error('Pilih file Excel terlebih dahulu.'); return; }
  const formData = new FormData();
  formData.append('file', importFile.value);
  importing.value = true;
  router.post('/operator-perusahaan/riwayat-pembayaran/import', formData, {
    onSuccess: () => { showImportModal.value = false; importing.value = false; fetchData(); toast.success('Import berhasil.'); },
    onError: () => { importing.value = false; toast.error('Import gagal.'); }
  });
}

function buildFilterParams() {
  const params = new URLSearchParams();
  if (searchInput.value) params.append('search', searchInput.value);
  if (statusFilter.value) params.append('status', statusFilter.value);
  if (providerFilter.value) params.append('provider', providerFilter.value);
  if (paymentMethodFilter.value) params.append('payment_method', paymentMethodFilter.value);
  if (createdStartFilter.value) params.append('created_start', createdStartFilter.value);
  if (createdEndFilter.value) params.append('created_end', createdEndFilter.value);
  if (terhapusFilter.value && terhapusFilter.value !== 'tidak') params.append('terhapus', terhapusFilter.value);
  if (sortField.value) { params.append('sort_field', sortField.value); params.append('sort_dir', sortDir.value); }
  return params.toString();
}
function exportAll() {
  const params = buildFilterParams();
  window.open('/operator-perusahaan/riwayat-pembayaran/export?' + params, '_blank');
  showExportDropdown.value = false;
}
function exportSelected() {
  if (selectedIds.value.length === 0) { toast.error('Pilih data terlebih dahulu.'); return; }
  const params = new URLSearchParams();
  selectedIds.value.forEach(id => params.append('ids[]', id));
  const filterParams = buildFilterParams();
  if (filterParams) params.append('filters', filterParams);
  window.open('/operator-perusahaan/riwayat-pembayaran/export?' + params.toString(), '_blank');
  showExportDropdown.value = false;
}

const items = computed(() => props.pembayarans?.data || []);
const pagination = computed(() => ({ current: props.pembayarans?.current_page || 1, last: props.pembayarans?.last_page || 1, total: props.pembayarans?.total || 0 }));
const hasFilter = computed(() => searchInput.value || statusFilter.value || providerFilter.value || paymentMethodFilter.value || createdStartFilter.value || createdEndFilter.value || terhapusFilter.value !== 'tidak');
const hasSelected = computed(() => selectedIds.value.length > 0);
const selectedPendingCount = computed(() => items.value.filter(i => selectedIds.value.includes(i.id) && i.status === 'pending').length);
const isAllSelected = computed(() => items.value.length > 0 && items.value.every(i => selectedIds.value.includes(i.id)));
const paymentMethods = ['tunai', 'transfer_manual'];
const providers = ['internal', 'external'];
</script>

<template>
  <div>
    <Head title="Riwayat Pembayaran | Perusahaan" />
    <ToastContainer />
    <div class="space-y-6">
      <nav class="flex items-center gap-1.5 text-sm"><Link href="/operator-perusahaan/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-sky-600 dark:hover:text-sky-400 transition-colors"><i class="fas fa-home"></i></Link><i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i><span class="text-gray-900 dark:text-white font-medium">Riwayat Pembayaran</span></nav>
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div><h2 class="text-2xl font-bold text-gray-900 dark:text-white">Riwayat Pembayaran</h2><p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Kelola riwayat pembayaran dari semua pelanggan.</p></div>
        <div class="flex items-center gap-2">
          <button v-if="can('riwayat-pembayaran.import') && terhapusFilter !== 'ya'" @click="openImport" class="inline-flex items-center px-3 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors shadow-sm"><i class="fas fa-file-import mr-1.5"></i> Import</button>
          <button v-if="can('riwayat-pembayaran.import')" @click="downloadTemplate" class="inline-flex items-center px-3 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors shadow-sm"><i class="fas fa-download mr-1.5"></i> Template</button>
          <div v-if="can('riwayat-pembayaran.export')" class="relative">
            <button @click="showExportDropdown = !showExportDropdown" class="inline-flex items-center px-3 py-2 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm">
              <i class="fas fa-file-export mr-1.5"></i> Export <i class="fas fa-chevron-down ml-1.5 text-xs"></i>
            </button>
            <div v-if="showExportDropdown" class="absolute right-0 mt-1 w-44 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-lg py-1 z-30">
              <button @click="exportAll" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"><i class="fas fa-list mr-2 text-emerald-500"></i>Export Semua</button>
              <button @click="exportSelected" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"><i class="fas fa-check-square mr-2 text-emerald-500"></i>Export Selected ({{ selectedIds.length }})</button>
            </div>
          </div>
          <button v-if="can('riwayat-pembayaran.create') && terhapusFilter !== 'ya'" @click="openCreate" class="inline-flex items-center px-4 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm"><i class="fas fa-plus mr-1.5"></i> Tambah Pembayaran</button>
        </div>
      </div>
      <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between"><div class="relative w-full sm:w-72"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-search text-gray-400 text-sm"></i></div><input v-model="searchInput" type="text" placeholder="Cari..." class="w-full pl-10 pr-16 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm focus:ring-2 focus:ring-sky-500 outline-none" @keydown.enter="applySearch" /><div class="absolute inset-y-0 right-0 flex items-center gap-1 pr-1.5"><button v-if="searchInput" @click="clearSearch" class="p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-white" title="Clear"><i class="fas fa-times text-xs"></i></button><button @click="applySearch" class="px-2 py-1 rounded bg-sky-600 text-white hover:bg-sky-700" title="Cari"><i class="fas fa-search text-xs"></i></button></div></div><div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400"><span>{{ props.pembayarans?.total || 0 }} data</span><button v-if="hasFilter" @click="resetFilters" class="text-xs text-red-500 hover:text-red-700 dark:hover:text-red-400 underline">Reset filter</button></div></div>

      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm">
        <div class="flex flex-wrap items-end gap-4">
          <div class="min-w-[160px]"><label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Provider</label><select v-model="providerFilter" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors"><option value="">Semua</option><option v-for="p in providers" :key="p" :value="p">{{ providerLabel(p) }}</option></select></div>
          <div class="min-w-[160px]"><label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Metode</label><select v-model="paymentMethodFilter" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors"><option value="">Semua</option><option v-for="m in paymentMethods" :key="m" :value="m">{{ paymentMethodLabel(m) }}</option></select></div>
          <div class="min-w-[160px]"><label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Status</label><select v-model="statusFilter" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors"><option value="">Semua</option><option value="paid">Lunas</option><option value="pending">Pending</option><option value="cancelled">Dibatalkan</option><option value="rejected">Ditolak</option><option value="expired">Kedaluarsa</option></select></div>
          <div class="min-w-[140px]"><label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Dari Tgl</label><input v-model="createdStartFilter" type="date" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors" /></div>
          <div class="min-w-[140px]"><label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">S/d</label><input v-model="createdEndFilter" type="date" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors" /></div>
          <div class="min-w-[120px]"><label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Terhapus</label><select v-model="terhapusFilter" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors"><option value="tidak">Tidak</option><option value="ya">Ya</option></select></div>
          <button @click="applyFilters" class="px-4 py-2 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm"><i class="fas fa-filter mr-1.5"></i>Filter</button>
        </div>
      </div>

      <div v-if="hasSelected" class="flex items-center justify-between px-4 py-3 bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-800 rounded-xl shadow-sm">
        <span class="text-sm font-medium text-sky-700 dark:text-sky-300"><i class="fas fa-check-circle mr-1.5"></i> {{ selectedIds.length }} data dipilih</span>
        <div class="flex items-center gap-2">
          <button v-if="can('riwayat-pembayaran.persetujuan') && selectedPendingCount > 0" @click="openPersetujuan({id: selectedIds[0]})" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-sky-600 text-white hover:bg-sky-700"><i class="fas fa-clipboard-check mr-1"></i> Persetujuan ({{ selectedPendingCount }} pending)</button>
          <button v-if="terhapusFilter === 'ya'" @click="bulkRestore()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-emerald-600 text-white hover:bg-emerald-700"><i class="fas fa-undo-alt mr-1"></i> Pulihkan</button>
          <button v-if="can('riwayat-pembayaran.delete')" @click="bulkDelete()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-red-600 text-white hover:bg-red-700"><i class="fas fa-trash-alt mr-1"></i> Hapus</button>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm min-w-[1400px]">
            <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
              <tr>
                <th class="px-3 py-3 w-10">
                  <input :checked="isAllSelected" type="checkbox" @click="toggleSelectAll" class="rounded border-gray-300 text-sky-600" />
                </th>
                <th class="px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 text-xs">Kode Pembayaran</th>
                <th class="px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 text-xs">Kode Tagihan</th>
                <th class="px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 text-xs">Kode Paket</th>
                <th class="px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 text-xs">Nama Paket</th>
                <th class="px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 text-xs">Kode Plg</th>
                <th class="px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 text-xs">Nama Pelanggan</th>
                <th class="px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 text-xs">Email</th>
                <th class="px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 text-xs">Telp</th>
                <th class="px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 text-xs">Provider</th>
                <th class="px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 text-xs">Metode</th>
                <th class="px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 text-xs">Tgl Bayar</th>
                <th class="px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 text-xs">Tgl Dibuat</th>
                <th class="px-3 py-3 text-center font-semibold text-gray-600 dark:text-gray-400 text-xs">Status</th>
                <th class="px-3 py-3 text-right font-semibold text-gray-600 dark:text-gray-400 text-xs">Nominal</th>
                <th class="px-3 py-3 text-center font-semibold text-gray-600 dark:text-gray-400 text-xs w-28">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="items.length === 0">
                <td colspan="16" class="px-4 py-16 text-center text-gray-400 dark:text-gray-500">
                  <i class="fas fa-inbox text-4xl mb-3 block"></i><span class="text-sm">Tidak ada data pembayaran</span>
                </td>
              </tr>
              <tr
                v-for="item in items" :key="item.id"
                class="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors cursor-pointer"
                :class="{'opacity-60': item.dihapus}"
                @click="toggleSelect(item.id)"
              >
                <td class="px-3 py-3" @click.stop>
                  <input :checked="selectedIds.includes(item.id)" type="checkbox" @click.stop="toggleSelect(item.id)" class="rounded border-gray-300 text-sky-600" />
                </td>
                <td class="px-3 py-3"><span class="font-mono text-xs text-gray-900 dark:text-white font-medium">{{ item.code || '-' }}</span></td>
                <td class="px-3 py-3"><span class="font-mono text-xs text-gray-600 dark:text-gray-400">{{ item.invoice_number || '-' }}</span></td>
                <td class="px-3 py-3"><span class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ item.kode_paket || '-' }}</span></td>
                <td class="px-3 py-3"><span class="text-xs text-gray-600 dark:text-gray-400">{{ item.nama_paket || '-' }}</span></td>
                <td class="px-3 py-3"><span class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ item.customer_code || '-' }}</span></td>
                <td class="px-3 py-3"><span class="text-xs font-medium text-gray-900 dark:text-white">{{ item.customer_name || '-' }}</span></td>
                <td class="px-3 py-3"><span class="text-xs text-gray-500 dark:text-gray-400">{{ item.email || '-' }}</span></td>
                <td class="px-3 py-3"><span class="text-xs text-gray-500 dark:text-gray-400">{{ item.phone_number || '-' }}</span></td>
                <td class="px-3 py-3"><span class="text-xs text-gray-600 dark:text-gray-400">{{ providerLabel(item.provider) || '-' }}</span></td>
                <td class="px-3 py-3"><span class="text-xs text-gray-600 dark:text-gray-400">{{ paymentMethodLabel(item.payment_method) || '-' }}</span></td>
                <td class="px-3 py-3"><span class="text-xs text-gray-500 dark:text-gray-400">{{ item.payment_date || '-' }}</span></td>
                <td class="px-3 py-3"><span class="text-xs text-gray-500 dark:text-gray-400">{{ item.created_at }}</span></td>
                <td class="px-3 py-3 text-center"><span :class="['inline-flex px-2 py-0.5 rounded-full text-xs font-medium', statusBadgeClass(item.status)]">{{ statusLabel(item.status) }}</span></td>
                <td class="px-3 py-3 text-right"><span class="font-mono text-xs text-gray-900 dark:text-white font-medium">{{ item.amount_paid ? 'Rp ' + Number(item.amount_paid).toLocaleString('id') : '-' }}</span></td>
                <td class="px-3 py-3" @click.stop>
                  <div class="flex items-center justify-center gap-1">
                    <button @click="openDetail(item)" title="Detail" class="p-1.5 rounded-lg text-gray-400 hover:text-sky-600 hover:bg-sky-50 dark:hover:text-sky-400 dark:hover:bg-indigo-900/30"><i class="fas fa-eye"></i></button>
                    <button v-if="can('riwayat-pembayaran.edit') && !item.dihapus && item.status === 'pending'" @click="openEdit(item)" title="Edit" class="p-1.5 rounded-lg text-gray-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:text-amber-400 dark:hover:bg-amber-900/30"><i class="fas fa-edit"></i></button>
                    <button v-if="can('riwayat-pembayaran.persetujuan') && !item.dihapus && item.status === 'pending'" @click="openPersetujuan(item)" title="Persetujuan" class="p-1.5 rounded-lg text-gray-400 hover:text-sky-600 hover:bg-sky-50 dark:hover:text-sky-400 dark:hover:bg-indigo-900/30"><i class="fas fa-clipboard-check"></i></button>
                    <button v-if="can('riwayat-pembayaran.delete') && !item.dihapus" @click="openDelete(item)" title="Hapus" class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:text-red-400 dark:hover:bg-red-900/30"><i class="fas fa-trash-alt"></i></button>
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
    <Teleport to="body"><Transition name="modal"><div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showDetailModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl"><div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white">Detail Pembayaran</h3><button @click="showDetailModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700"><i class="fas fa-times"></i></button></div><div class="px-6 py-5" v-if="selectedItem"><div class="grid grid-cols-3 gap-4"><div><label class="text-xs text-gray-500 dark:text-gray-400">Kode Pembayaran</label><p class="font-mono font-medium text-gray-900 dark:text-white">{{ selectedItem.code || '-' }}</p></div><div><label class="text-xs text-gray-500 dark:text-gray-400">Kode Tagihan</label><p class="font-mono text-sm text-gray-900 dark:text-white">{{ selectedItem.invoice_number || '-' }}</p></div><div><label class="text-xs text-gray-500 dark:text-gray-400">Kode Paket</label><p class="font-mono text-sm text-gray-600 dark:text-gray-400">{{ selectedItem.kode_paket || '-' }}</p></div><div><label class="text-xs text-gray-500 dark:text-gray-400">Nama Paket</label><p class="text-sm text-gray-600 dark:text-gray-400">{{ selectedItem.nama_paket || '-' }}</p></div><div><label class="text-xs text-gray-500 dark:text-gray-400">Kode Pelanggan</label><p class="font-mono text-sm text-gray-600 dark:text-gray-400">{{ selectedItem.customer_code || '-' }}</p></div><div><label class="text-xs text-gray-500 dark:text-gray-400">Nama Pelanggan</label><p class="font-medium text-gray-900 dark:text-white">{{ selectedItem.customer_name || '-' }}</p></div><div><label class="text-xs text-gray-500 dark:text-gray-400">Email</label><p class="text-sm text-gray-600 dark:text-gray-400">{{ selectedItem.email || '-' }}</p></div><div><label class="text-xs text-gray-500 dark:text-gray-400">Telp</label><p class="text-sm text-gray-600 dark:text-gray-400">{{ selectedItem.phone_number || '-' }}</p></div><div><label class="text-xs text-gray-500 dark:text-gray-400">Provider</label><p class="text-sm text-gray-600 dark:text-gray-400">{{ providerLabel(selectedItem.provider) || '-' }}</p></div><div><label class="text-xs text-gray-500 dark:text-gray-400">Metode</label><p class="text-sm text-gray-600 dark:text-gray-400">{{ paymentMethodLabel(selectedItem.payment_method) || '-' }}</p></div><div><label class="text-xs text-gray-500 dark:text-gray-400">Tgl Bayar</label><p class="text-sm text-gray-600 dark:text-gray-400">{{ selectedItem.payment_date || '-' }}</p></div><div><label class="text-xs text-gray-500 dark:text-gray-400">Tgl Dibuat</label><p class="text-sm text-gray-600 dark:text-gray-400">{{ selectedItem.created_at }}</p></div><div><label class="text-xs text-gray-500 dark:text-gray-400">Status</label><p><span :class="['inline-flex px-2 py-0.5 rounded text-xs font-medium', statusBadgeClass(selectedItem.status)]">{{ statusLabel(selectedItem.status) }}</span></p></div><div><label class="text-xs text-gray-500 dark:text-gray-400">Nominal</label><p class="font-mono font-semibold text-lg text-gray-900 dark:text-white">Rp {{ Number(selectedItem.amount_paid || 0).toLocaleString('id') }}</p></div><div class="col-span-3"><label class="text-xs text-gray-500 dark:text-gray-400">Keterangan</label><p class="text-sm text-gray-600 dark:text-gray-400">{{ selectedItem.status_description || '-' }}</p></div></div></div></div></div></Transition></Teleport>

    <!-- Create Modal -->
    <Teleport to="body"><Transition name="modal"><div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showCreateModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><form @submit.prevent="submitCreate" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col"><div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-plus text-emerald-500 mr-2"></i>Tambah Pembayaran</h3><button type="button" @click="showCreateModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button></div><div class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll">
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Invoice <span class="text-red-500">*</span></label><SearchableSelectAjax v-model="createForm.cust_internet_invc_id" url="/operator-perusahaan/api/search/invoices" placeholder="— Pilih Invoice —" /><p v-if="createForm.errors.cust_internet_invc_id" class="text-red-500 text-xs mt-1">{{ createForm.errors.cust_internet_invc_id }}</p></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Jumlah (Rp) <span class="text-red-500">*</span></label><input v-model="createForm.amount_paid" type="number" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" /></div>
        <div class="grid grid-cols-2 gap-4"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Provider <span class="text-red-500">*</span></label><select v-model="createForm.provider" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none"><option value="">— Pilih —</option><option v-for="p in providers" :key="p" :value="p">{{ providerLabel(p) }}</option></select><p v-if="createForm.errors.provider" class="text-red-500 text-xs mt-1">{{ createForm.errors.provider }}</p></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Metode <span class="text-red-500">*</span></label><select v-model="createForm.payment_method" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none"><option value="">— Pilih —</option><option v-for="m in paymentMethods" :key="m" :value="m">{{ paymentMethodLabel(m) }}</option></select><p v-if="createForm.errors.payment_method" class="text-red-500 text-xs mt-1">{{ createForm.errors.payment_method }}</p></div></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Bukti Bayar (jpg, png, pdf) <span class="text-red-500">*</span></label><input @change="e => createFormFile = e.target.files[0]" type="file" accept=".jpg,.jpeg,.png,.pdf" class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-sky-50 file:text-sky-700 dark:file:bg-sky-900/30 dark:file:text-sky-400 hover:file:bg-sky-100 dark:hover:file:bg-sky-900/50" /><p v-if="createForm.errors.proof_file" class="text-red-500 text-xs mt-1">{{ createForm.errors.proof_file }}</p></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Keterangan</label><textarea v-model="createForm.status_description" rows="2" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none resize-none"></textarea></div>
      </div><div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700"><button type="button" @click="showCreateModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button><button type="submit" :disabled="createForm.processing" class="px-6 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm disabled:opacity-50"><i class="fas fa-save mr-1.5"></i>Simpan</button></div></form></div></Transition></Teleport>

    <!-- Edit Modal -->
    <Teleport to="body"><Transition name="modal"><div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showEditModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><form @submit.prevent="submitEdit" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col"><div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-edit text-sky-500 mr-2"></i>Edit Pembayaran</h3><button type="button" @click="showEditModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button></div><div class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll">
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Invoice <span class="text-red-500">*</span></label><SearchableSelectAjax v-model="editForm.cust_internet_invc_id" url="/operator-perusahaan/api/search/invoices" placeholder="— Pilih Invoice —" :selected-label="selectedItem?.invoice_number" /><p v-if="editForm.errors.cust_internet_invc_id" class="text-red-500 text-xs mt-1">{{ editForm.errors.cust_internet_invc_id }}</p></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Jumlah (Rp) <span class="text-red-500">*</span></label><input v-model="editForm.amount_paid" type="number" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" /></div>
        <div class="grid grid-cols-2 gap-4"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Provider <span class="text-red-500">*</span></label><select v-model="editForm.provider" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none"><option value="">— Pilih —</option><option v-for="p in providers" :key="p" :value="p">{{ providerLabel(p) }}</option></select><p v-if="editForm.errors.provider" class="text-red-500 text-xs mt-1">{{ editForm.errors.provider }}</p></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Metode <span class="text-red-500">*</span></label><select v-model="editForm.payment_method" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none"><option value="">— Pilih —</option><option v-for="m in paymentMethods" :key="m" :value="m">{{ paymentMethodLabel(m) }}</option></select><p v-if="editForm.errors.payment_method" class="text-red-500 text-xs mt-1">{{ editForm.errors.payment_method }}</p></div></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Bukti Bayar <span class="text-xs text-gray-400">(kosongkan jika tidak diubah)</span></label><input @change="e => editFormFile = e.target.files[0]" type="file" accept=".jpg,.jpeg,.png,.pdf" class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-sky-50 file:text-sky-700 dark:file:bg-sky-900/30 dark:file:text-sky-400 hover:file:bg-sky-100 dark:hover:file:bg-sky-900/50" /><p v-if="editForm.errors.proof_file" class="text-red-500 text-xs mt-1">{{ editForm.errors.proof_file }}</p></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Keterangan</label><textarea v-model="editForm.status_description" rows="2" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none resize-none"></textarea></div>
      </div><div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700"><button type="button" @click="showEditModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button><button type="submit" :disabled="editForm.processing" class="px-6 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm disabled:opacity-50"><i class="fas fa-check mr-1.5"></i>Update</button></div></form></div></Transition></Teleport>

    <!-- Delete Modal -->
    <Teleport to="body"><Transition name="modal"><div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showDeleteModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-sm"><div class="px-6 py-5 text-center"><div class="w-14 h-14 mx-auto rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-4"><i class="fas fa-exclamation-triangle text-red-500 text-xl"></i></div><h3 class="text-lg font-semibold mb-2">Hapus Pembayaran?</h3><p class="text-sm text-gray-500">Anda akan menghapus pembayaran <strong>{{ selectedItem?.code }}</strong>.</p></div><div class="flex justify-center gap-3 px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700"><button @click="showDeleteModal = false" class="px-5 py-2.5 bg-gray-200 dark:bg-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">Batal</button><button @click="confirmDelete" class="px-5 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700"><i class="fas fa-trash-alt mr-1.5"></i> Hapus</button></div></div></div></Transition></Teleport>

    <!-- Persetujuan Modal -->
    <Teleport to="body"><Transition name="modal"><div v-if="showPersetujuanModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showPersetujuanModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md"><div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-clipboard-check text-sky-500 mr-2"></i>Persetujuan Pembayaran</h3><button @click="showPersetujuanModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700"><i class="fas fa-times"></i></button></div><div class="px-6 py-5 space-y-4"><div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-3"><div class="grid grid-cols-2 gap-3"><div><label class="text-xs text-gray-500 dark:text-gray-400">Kode Pembayaran</label><p class="font-mono font-medium text-gray-900 dark:text-white">{{ selectedItem?.code }}</p></div><div><label class="text-xs text-gray-500 dark:text-gray-400">Nominal</label><p class="font-mono font-semibold text-sky-600">Rp {{ Number(selectedItem?.amount_paid || 0).toLocaleString('id') }}</p></div></div></div><div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg px-4 py-3 text-sm text-amber-700 dark:text-amber-300"><i class="fas fa-info-circle mr-1.5"></i> Pastikan bukti pembayaran sudah valid sebelum menyetujui.</div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Alasan (wajib jika tolak)</label><textarea v-model="persetujuanForm.status_reason" rows="3" placeholder="Jelaskan alasan persetujuan atau penolakan..." class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none resize-none"></textarea></div></div><div class="flex justify-end gap-2 px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700"><button @click="showPersetujuanModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">Batal</button><button @click="submitTolak" class="px-5 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700"><i class="fas fa-times mr-1.5"></i>Tolak</button><button @click="submitPersetujuan" class="px-5 py-2.5 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700"><i class="fas fa-check mr-1.5"></i>Setujui</button></div></div></div></Transition></Teleport>

    <!-- Import Modal -->
    <Teleport to="body"><Transition name="modal"><div v-if="showImportModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showImportModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><form @submit.prevent="submitImport" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] flex flex-col"><div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-file-import text-emerald-500 mr-2"></i>Import Pembayaran</h3><button type="button" @click="showImportModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700"><i class="fas fa-times"></i></button></div><div class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll"><div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg px-4 py-3 text-sm text-amber-700 dark:text-amber-300"><i class="fas fa-info-circle mr-1.5"></i> Pastikan file Excel sesuai template. Kolom: No. Invoice, Jumlah Bayar, Provider, Metode Pembayaran.</div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">File Excel <span class="text-red-500">*</span></label><input @change="onImportFileChange" type="file" accept=".xlsx,.xls" class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-sky-50 file:text-sky-700 dark:file:bg-sky-900/30 dark:file:text-sky-400 hover:file:bg-sky-100 dark:hover:file:bg-sky-900/50" /><p v-if="importForm.errors.file" class="text-red-500 text-xs mt-1">{{ importForm.errors.file }}</p></div></div><div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700"><button type="button" @click="showImportModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">Batal</button><button type="submit" :disabled="importing" class="px-6 py-2.5 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 disabled:opacity-50"><i class="fas fa-upload mr-1.5"></i>{{ importing ? 'Importing...' : 'Import' }}</button></div></form></div></Transition></Teleport>
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