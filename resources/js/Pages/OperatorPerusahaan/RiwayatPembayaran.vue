<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import OperatorPerusahaanLayout from '@/Layouts/OperatorPerusahaanLayout.vue';
import { useToast } from '@/Composables/useToast';
import SearchableSelectAjax from '@/Components/SearchableSelectAjax.vue';
import FormErrorSummary from '@/Components/FormErrorSummary.vue';
import ToastContainer from '@/Components/ToastContainer.vue';
import { errorSummary } from '@/Composables/useFormErrorToast';

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
const invoiceFilter = ref(props.filters?.invoice_number || '');
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
const bulkVerifying = ref(false);
const showReviewModal = ref(false);
const showExportDropdown = ref(false);
const showImportModal = ref(false);
const importing = ref(false);
const importFile = ref(null);

const createForm = useForm({ code: '', cust_internet_invc_id: '', amount_paid: '', payment_date: '', payment_method: 'tunai', provider: 'internal', proof_file: null, status_description: '' });
const editForm = useForm({ code: '', cust_internet_invc_id: '', amount_paid: '', payment_date: '', payment_method: '', provider: '', proof_file: null, status_description: '' });
const reviewForm = useForm({ review_status: '', review_reason: '', review_attachment: null });
const bulkReviewForm = useForm({ review_status: '', review_reason: '', review_attachment: null });
const importForm = useForm({ file: null });
const createAjaxErrors = ref({});
const editAjaxErrors = ref({});
const importAjaxErrors = ref({});

function buildQuery(o = {}) {
  const p = { ...o };
  if (p.search === undefined) p.search = searchInput.value || undefined;
  if (p.status === undefined) p.status = statusFilter.value || undefined;
  if (p.provider === undefined) p.provider = providerFilter.value || undefined;
  if (p.payment_method === undefined) p.payment_method = paymentMethodFilter.value || undefined;
  if (p.created_start === undefined) p.created_start = createdStartFilter.value || undefined;
  if (p.created_end === undefined) p.created_end = createdEndFilter.value || undefined;
  if (p.invoice_number === undefined) p.invoice_number = invoiceFilter.value || undefined;
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
function applyFilters() { fetchData({ invoice_number: invoiceFilter.value || undefined, page: 1 }); }
function resetFilters() { searchInput.value = ''; statusFilter.value = ''; providerFilter.value = ''; paymentMethodFilter.value = ''; createdStartFilter.value = ''; createdEndFilter.value = ''; invoiceFilter.value = ''; terhapusFilter.value = 'tidak'; fetchData({ search: undefined, status: undefined, provider: undefined, payment_method: undefined, created_start: undefined, created_end: undefined, invoice_number: undefined, terhapus: 'tidak', page: 1 }); }
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
function providerLabel(p) { if (p === 'internal') return 'Internal'; if (p === 'external') return 'Eksternal'; if (p === 'midtrans') return 'Midtrans (Bayar Online)'; return p; }
function paymentMethodLabel(m) { if (m === 'tunai') return 'Tunai'; if (m === 'transfer_manual') return 'Transfer Manual'; if (m === 'midtrans') return 'Midtrans'; return m; }

const createFormFile = ref(null);
const editFormFile = ref(null);

function openCreate() { createForm.reset(); createForm.clearErrors(); createForm.provider = 'internal'; createForm.payment_method = 'tunai'; createFormFile.value = null; createAjaxErrors.value = {}; showCreateModal.value = true; }
async function submitCreate() {
  // ===== Midtrans flow: gunakan fetch() agar bisa dapat JSON snap_token + redirect_url =====
  if (createForm.provider === 'midtrans') {
    const formData = new FormData();
    formData.append('code', createForm.code);
    formData.append('cust_internet_invc_id', createForm.cust_internet_invc_id);
    formData.append('amount_paid', createForm.amount_paid);
    formData.append('payment_date', createForm.payment_date || '');
    formData.append('payment_method', 'midtrans');
    formData.append('provider', 'midtrans');
    formData.append('status_description', createForm.status_description || '');
    try {
      const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
      const resp = await fetch('/operator-perusahaan/riwayat-pembayaran', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        body: formData,
      });
      const data = await resp.json().catch(() => ({}));
      if (resp.ok && data.redirect_url) {
        showCreateModal.value = false;
        toast.success('Transaksi Midtrans dibuat. Membuka halaman pembayaran...');
        // Open Snap UI di tab baru — admin perusahaan bisa bantu customer selesaikan pembayaran
        window.open(data.redirect_url, '_blank', 'noopener,noreferrer');
        fetchData();
      } else {
        const errs = data.errors || {};
        createAjaxErrors.value = errs;
        if (Object.keys(errs).length) {
          toast.error('Validasi gagal: ' + errorSummary(errs), 6000);
        } else {
          toast.error(data.error || data.message || `Gagal (HTTP ${resp.status})`);
        }
      }
    } catch (e) {
      toast.error('Error: ' + e.message);
    }
    return;
  }
  // ===== Internal flow (existing) =====
  const formData = new FormData();
  formData.append('code', createForm.code);
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
    onError: (errors) => { createAjaxErrors.value = errors || {}; toast.error('Validasi gagal: ' + errorSummary(errors), 6000); }
  });
}
function openEdit(item) {
  editForm.code = item.code || '';
  editForm.cust_internet_invc_id = item.cust_internet_invc_id;
  editForm.amount_paid = item.amount_paid;
  editForm.payment_date = item.payment_date || '';
  editForm.payment_method = item.payment_method;
  editForm.provider = item.provider;
  editForm.status_description = item.status_description || '';
  editForm.clearErrors();
  editFormFile.value = null;
  editAjaxErrors.value = {};
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
    onError: (errors) => { editAjaxErrors.value = errors || {}; toast.error('Validasi gagal: ' + errorSummary(errors), 6000); }
  });
}
function openDetail(item) { selectedItem.value = item; showDetailModal.value = true; }
function openDelete(item) { selectedItem.value = item; showDeleteModal.value = true; }
function confirmDelete() { router.delete('/operator-perusahaan/riwayat-pembayaran/' + selectedItem.value.id, { onSuccess: () => { showDeleteModal.value = false; fetchData(); toast.success('Pembayaran berhasil dihapus.'); } }); }
function confirmRestore(id) { router.patch('/operator-perusahaan/riwayat-pembayaran/' + id + '/restore', { onSuccess: () => { fetchData(); toast.success('Pembayaran berhasil dipulihkan.'); } }); }
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
  reviewForm.post('/operator-perusahaan/riwayat-pembayaran/' + selectedItem.value.id + '/review', {
    preserveState: true, preserveScroll: true,
    onSuccess: () => { showReviewModal.value = false; fetchData(); toast.success('Review berhasil disimpan.'); },
    onError: () => toast.error('Validasi gagal: ' + errorSummary(reviewForm.errors), 6000)
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
  bulkReviewForm.post('/operator-perusahaan/riwayat-pembayaran/bulk-review', {
    preserveState: true, preserveScroll: true,
    onSuccess: () => { showReviewModal.value = false; selectedIds.value = []; fetchData(); toast.success('Bulk review berhasil disimpan.'); },
    onError: () => toast.error('Validasi gagal: ' + errorSummary(bulkReviewForm.errors), 6000)
  });
}
function bulkDelete() { router.post('/operator-perusahaan/riwayat-pembayaran/bulk-delete', { ids: selectedIds.value }, { onSuccess: () => { selectedIds.value = []; fetchData(); toast.success('Pembayaran berhasil dihapus.'); } }); }
function bulkRestore() { router.post('/operator-perusahaan/riwayat-pembayaran/bulk-restore', { ids: selectedIds.value }, { onSuccess: () => { selectedIds.value = []; fetchData(); toast.success('Pembayaran berhasil dipulihkan.'); } }); }
async function bulkVerifyMidtrans() {
  if (selectedIds.value.length === 0) { toast.error('Pilih data terlebih dahulu.'); return; }
  if (selectedMidtransPendingCount.value === 0) { toast.error('Pilih minimal 1 record Midtrans+pending.'); return; }
  if (!confirm(`Sinkron status Midtrans untuk ${selectedMidtransPendingCount.value} record?\n\nRecord non-Midtrans/pending akan di-skip otomatis.`)) return;
  bulkVerifying.value = true;
  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const res = await fetch('/operator-perusahaan/api/riwayat-pembayaran/bulk-verify-midtrans', {
      method: 'POST',
      headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
      body: JSON.stringify({ ids: selectedIds.value }),
    });
    const data = await res.json().catch(() => ({}));
    if (res.ok && data.status === 'ok') {
      const s = data.summary || {};
      const parts = [`${s.ok || 0} berhasil`];
      if (s.failed) parts.push(`${s.failed} gagal`);
      if (s.skipped) parts.push(`${s.skipped} di-skip`);
      toast.success(`Sinkron selesai: ${parts.join(', ')} (dari ${s.verified || 0} payment Midtrans).`);
      selectedIds.value = [];
      fetchData();
    } else {
      toast.error(data.error || `Gagal sinkron bulk (HTTP ${res.status}).`);
    }
  } catch (err) {
    toast.error(err.message || 'Gagal sinkron bulk.');
  } finally {
    bulkVerifying.value = false;
  }
}
function downloadTemplate() { window.location.href = '/operator-perusahaan/riwayat-pembayaran/template'; }
function openImport() { importFile.value = null; importAjaxErrors.value = {}; showImportModal.value = true; }
function onImportFileChange(e) { importFile.value = e.target.files[0]; importForm.file = e.target.files[0]; }
function submitImport() {
  if (!importFile.value) { toast.error('Pilih file Excel terlebih dahulu.'); return; }
  const formData = new FormData();
  formData.append('file', importFile.value);
  importing.value = true;
  router.post('/operator-perusahaan/riwayat-pembayaran/import', formData, {
    onSuccess: () => { showImportModal.value = false; importing.value = false; fetchData(); toast.success('Import berhasil.'); },
    onError: (errors) => { importing.value = false; importAjaxErrors.value = errors || {}; toast.error('Validasi gagal: ' + errorSummary(errors), 6000); }
  });
}

// Verifikasi manual status Midtrans (fallback saat webhook lambat/gagal).
// Tampilkan spinner pada tombol saat request, reload data kalau status berubah.
const verifyingId = ref(null);
async function verifyMidtrans(item) {
  if (verifyingId.value) return;
  verifyingId.value = item.id;
  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const res = await fetch(`/operator-perusahaan/api/riwayat-pembayaran/${item.id}/verify-midtrans`, {
      method: 'POST',
      headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
      credentials: 'same-origin',
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.error || `HTTP ${res.status}`);
    if (data.changed) {
      toast.success(data.message || `Status diperbarui ke ${data.payment?.status}.`);
    } else {
      toast.info(data.message || 'Status tidak berubah.');
    }
    fetchData();
  } catch (err) {
    toast.error(err.message || 'Gagal sinkron status Midtrans.');
  } finally {
    verifyingId.value = null;
  }
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
const hasFilter = computed(() => searchInput.value || statusFilter.value || providerFilter.value || paymentMethodFilter.value || createdStartFilter.value || createdEndFilter.value || invoiceFilter.value || terhapusFilter.value !== 'tidak');
const hasSelected = computed(() => selectedIds.value.length > 0);
const selectedPendingCount = computed(() => items.value.filter(i => selectedIds.value.includes(i.id) && i.status === 'pending').length);
const selectedMidtransPendingCount = computed(() => items.value.filter(i => selectedIds.value.includes(i.id) && i.status === 'pending' && i.provider === 'midtrans').length);
const isAllSelected = computed(() => items.value.length > 0 && items.value.every(i => selectedIds.value.includes(i.id)));
const paymentMethods = (props.paymentMethodOptions && props.paymentMethodOptions.length) ? props.paymentMethodOptions : ['tunai', 'transfer_manual'];
const providers = (props.providerOptions && props.providerOptions.length) ? props.providerOptions : ['internal', 'external'];
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
        <div class="flex flex-wrap items-end gap-3 sm:gap-4">
          <div class="min-w-[140px] flex-1 sm:flex-none"><label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Provider</label><select v-model="providerFilter" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors"><option value="">Semua</option><option v-for="p in providers" :key="p" :value="p">{{ providerLabel(p) }}</option></select></div>
          <div class="min-w-[140px] flex-1 sm:flex-none"><label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Metode</label><select v-model="paymentMethodFilter" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors"><option value="">Semua</option><option v-for="m in paymentMethods" :key="m" :value="m">{{ paymentMethodLabel(m) }}</option></select></div>
          <div class="min-w-[140px] flex-1 sm:flex-none"><label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Status</label><select v-model="statusFilter" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors"><option value="">Semua</option><option value="paid">Lunas</option><option value="pending">Pending</option><option value="cancelled">Dibatalkan</option><option value="rejected">Ditolak</option><option value="expired">Kedaluarsa</option></select></div>
          <div class="min-w-[130px] flex-1 sm:flex-none"><label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Dari Tgl</label><input v-model="createdStartFilter" type="date" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors" /></div>
          <div class="min-w-[130px] flex-1 sm:flex-none"><label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">S/d</label><input v-model="createdEndFilter" type="date" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors" /></div>
          <div class="min-w-[160px] flex-1 sm:flex-none"><label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Invoice</label><SearchableSelectAjax v-model="invoiceFilter" url="/operator-perusahaan/api/search/invoices" placeholder="— Pilih —" display-key="invoice_number" /></div>
          <div class="min-w-[110px] flex-1 sm:flex-none"><label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Terhapus</label><select v-model="terhapusFilter" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors"><option value="tidak">Tidak</option><option value="ya">Ya</option></select></div>
          <button @click="applyFilters" class="px-4 py-2 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm whitespace-nowrap"><i class="fas fa-filter mr-1.5"></i>Filter</button>
        </div>
      </div>

      <div v-if="hasSelected" class="flex items-center justify-between px-4 py-3 bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-800 rounded-xl shadow-sm">
        <span class="text-sm font-medium text-sky-700 dark:text-sky-300"><i class="fas fa-check-circle mr-1.5"></i> {{ selectedIds.length }} data dipilih</span>
        <div class="flex items-center gap-2">
          <button v-if="can('riwayat-pembayaran.persetujuan') && selectedPendingCount > 0" @click="openBulkReview" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-sky-600 text-white hover:bg-sky-700"><i class="fas fa-clipboard-check mr-1"></i> Review ({{ selectedPendingCount }} pending)</button>
          <button v-if="can('riwayat-pembayaran.persetujuan') && selectedMidtransPendingCount > 0" @click="bulkVerifyMidtrans()" :disabled="bulkVerifying" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-violet-600 text-white hover:bg-violet-700 disabled:opacity-60 disabled:cursor-not-allowed"><i class="fas fa-sync-alt mr-1"></i> Verifikasi Midtrans ({{ selectedMidtransPendingCount }})</button>
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
                    <button v-if="can('riwayat-pembayaran.edit') && !item.dihapus && item.status === 'pending' && item.provider === 'internal'" @click="openEdit(item)" title="Edit" class="p-1.5 rounded-lg text-gray-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:text-amber-400 dark:hover:bg-amber-900/30"><i class="fas fa-edit"></i></button>
                    <button v-if="can('riwayat-pembayaran.persetujuan') && !item.dihapus && item.status === 'pending' && item.provider === 'internal'" @click="openReview(item)" title="Review" class="p-1.5 rounded-lg text-gray-400 hover:text-sky-600 hover:bg-sky-50 dark:hover:text-sky-400 dark:hover:bg-indigo-900/30"><i class="fas fa-clipboard-check"></i></button>
                    <!-- Lock icon untuk payment non-internal (read-only) -->
                    <span v-if="item.provider !== 'internal' && !item.dihapus" title="Read-only — pembayaran non-internal hanya bisa di-sinkronkan via Midtrans" class="p-1.5 text-gray-300 dark:text-gray-600 cursor-not-allowed"><i class="fas fa-lock"></i></span>
                    <!-- Tombol Sinkron Status Midtrans (khusus midtrans+pending) -->
                    <button v-if="item.provider === 'midtrans' && item.status === 'pending' && !item.dihapus" @click="verifyMidtrans(item)" :disabled="verifyingId === item.id" :title="verifyingId === item.id ? 'Sinkron sedang berjalan...' : 'Sinkron Status Midtrans (verifikasi manual saat webhook lambat)'" class="p-1.5 rounded-lg text-violet-600 hover:text-white hover:bg-violet-600 dark:text-violet-400 dark:hover:text-white dark:hover:bg-violet-500 transition-colors disabled:opacity-60 disabled:cursor-wait">
                      <i :class="['fas', verifyingId === item.id ? 'fa-spinner fa-spin' : 'fa-sync-alt']"></i>
                    </button>
                    <button v-if="can('riwayat-pembayaran.delete') && !item.dihapus && item.provider === 'internal'" @click="openDelete(item)" title="Hapus" class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:text-red-400 dark:hover:bg-red-900/30"><i class="fas fa-trash-alt"></i></button>
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
    <Teleport to="body"><Transition name="modal"><div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showDetailModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl"><div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white">Detail Pembayaran</h3><button @click="showDetailModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700"><i class="fas fa-times"></i></button></div><div class="px-6 py-5" v-if="selectedItem"><div class="grid grid-cols-3 gap-4"><div><label class="text-xs text-gray-500 dark:text-gray-400">Kode Pembayaran</label><p class="font-mono font-medium text-gray-900 dark:text-white">{{ selectedItem.code || '-' }}</p></div><div><label class="text-xs text-gray-500 dark:text-gray-400">Kode Tagihan</label><p class="font-mono text-sm text-gray-900 dark:text-white">{{ selectedItem.invoice_number || '-' }}</p></div><div><label class="text-xs text-gray-500 dark:text-gray-400">Kode Paket</label><p class="font-mono text-sm text-gray-600 dark:text-gray-400">{{ selectedItem.kode_paket || '-' }}</p></div><div><label class="text-xs text-gray-500 dark:text-gray-400">Nama Paket</label><p class="text-sm text-gray-600 dark:text-gray-400">{{ selectedItem.nama_paket || '-' }}</p></div><div><label class="text-xs text-gray-500 dark:text-gray-400">Kode Pelanggan</label><p class="font-mono text-sm text-gray-600 dark:text-gray-400">{{ selectedItem.customer_code || '-' }}</p></div><div><label class="text-xs text-gray-500 dark:text-gray-400">Nama Pelanggan</label><p class="font-medium text-gray-900 dark:text-white">{{ selectedItem.customer_name || '-' }}</p></div><div><label class="text-xs text-gray-500 dark:text-gray-400">Email</label><p class="text-sm text-gray-600 dark:text-gray-400">{{ selectedItem.email || '-' }}</p></div><div><label class="text-xs text-gray-500 dark:text-gray-400">Telp</label><p class="text-sm text-gray-600 dark:text-gray-400">{{ selectedItem.phone_number || '-' }}</p></div><div><label class="text-xs text-gray-500 dark:text-gray-400">Provider</label><p class="text-sm text-gray-600 dark:text-gray-400">{{ providerLabel(selectedItem.provider) || '-' }}</p></div><div><label class="text-xs text-gray-500 dark:text-gray-400">Metode</label><p class="text-sm text-gray-600 dark:text-gray-400">{{ paymentMethodLabel(selectedItem.payment_method) || '-' }}</p></div><div><label class="text-xs text-gray-500 dark:text-gray-400">Tgl Bayar</label><p class="text-sm text-gray-600 dark:text-gray-400">{{ selectedItem.payment_date || '-' }}</p></div><div><label class="text-xs text-gray-500 dark:text-gray-400">Tgl Dibuat</label><p class="text-sm text-gray-600 dark:text-gray-400">{{ selectedItem.created_at }}</p></div><div><label class="text-xs text-gray-500 dark:text-gray-400">Status</label><p><span :class="['inline-flex px-2 py-0.5 rounded text-xs font-medium', statusBadgeClass(selectedItem.status)]">{{ statusLabel(selectedItem.status) }}</span></p></div><div><label class="text-xs text-gray-500 dark:text-gray-400">Nominal</label><p class="font-mono font-semibold text-lg text-gray-900 dark:text-white">Rp {{ Number(selectedItem.amount_paid || 0).toLocaleString('id') }}</p></div><div class="col-span-3"><label class="text-xs text-gray-500 dark:text-gray-400">Keterangan</label><p class="text-sm text-gray-600 dark:text-gray-400">{{ selectedItem.status_description || '-' }}</p></div></div><div v-if="selectedItem.proof_file_url" class="col-span-3 mt-4"><label class="text-xs text-gray-500 dark:text-gray-400">Bukti Pembayaran</label><div class="mt-2"><a :href="selectedItem.proof_file_url" target="_blank" class="inline-flex items-center px-4 py-2 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm"><i class="fas fa-image mr-1.5"></i> Lihat Bukti Bayar</a></div></div></div><div class="flex justify-end gap-2 px-6 pt-4 mt-4 pb-4 border-t border-gray-200 dark:border-gray-700"><a :href="'/operator-perusahaan/riwayat-pembayaran/' + selectedItem.id + '/pdf'" target="_blank" class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors shadow-sm"><i class="fas fa-file-pdf mr-1.5"></i> PDF</a><a :href="'/operator-perusahaan/riwayat-pembayaran/' + selectedItem.id + '/word'" target="_blank" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors shadow-sm"><i class="fas fa-file-word mr-1.5"></i> Word</a></div></div></div></Transition></Teleport>

    <!-- Create Modal -->
    <Teleport to="body"><Transition name="modal"><div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showCreateModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><form data-testid="modal-create" @submit.prevent="submitCreate" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col"><div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-plus text-emerald-500 mr-2"></i>Tambah Pembayaran</h3><button type="button" @click="showCreateModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button></div><div class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll">
        <FormErrorSummary :errors="createAjaxErrors" />
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Kode Pembayaran <span class="text-red-500">*</span></label><input data-testid="input-code" v-model="createForm.code" type="text" placeholder="Contoh: BYR-20250601-XXXX" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', createAjaxErrors.code ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500 focus:border-sky-500']" /><p v-if="createAjaxErrors.code" class="text-red-500 text-xs mt-1">{{ createAjaxErrors.code }}</p></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Invoice <span class="text-red-500">*</span></label><span data-testid="btn-select-invoice"><SearchableSelectAjax v-model="createForm.cust_internet_invc_id" url="/operator-perusahaan/api/search/invoices" placeholder="— Pilih Invoice —" :error="!!createAjaxErrors.cust_internet_invc_id" /></span><p v-if="createAjaxErrors.cust_internet_invc_id" class="text-red-500 text-xs mt-1">{{ createAjaxErrors.cust_internet_invc_id }}</p></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Jumlah (Rp) <span class="text-red-500">*</span></label><input data-testid="input-amount" v-model="createForm.amount_paid" type="number" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', createAjaxErrors.amount_paid ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500 focus:border-sky-500']" /><p v-if="createAjaxErrors.amount_paid" class="text-red-500 text-xs mt-1">{{ createAjaxErrors.amount_paid }}</p></div>
        <div class="grid grid-cols-2 gap-4"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Provider <span class="text-red-500">*</span></label><select data-testid="select-provider" v-model="createForm.provider" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', createAjaxErrors.provider ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500 focus:border-sky-500']"><option value="">— Pilih —</option><option v-for="p in providers" :key="p" :value="p">{{ providerLabel(p) }}</option></select><p v-if="createAjaxErrors.provider" class="text-red-500 text-xs mt-1">{{ createAjaxErrors.provider }}</p></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Metode <span class="text-red-500">*</span></label><select data-testid="select-metode" v-model="createForm.payment_method" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', createAjaxErrors.payment_method ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500 focus:border-sky-500']"><option value="">— Pilih —</option><option v-for="m in paymentMethods" :key="m" :value="m">{{ paymentMethodLabel(m) }}</option></select><p v-if="createAjaxErrors.payment_method" class="text-red-500 text-xs mt-1">{{ createAjaxErrors.payment_method }}</p></div></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Bukti Bayar (jpg, png, webp, pdf) <span class="text-xs text-gray-400">(opsional, maks 2MB)</span></label><input @change="e => createFormFile = e.target.files[0]" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf" :class="['w-full text-sm text-gray-500 dark:text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-sky-50 file:text-sky-700 dark:file:bg-sky-900/30 dark:file:text-sky-400 hover:file:bg-sky-100 dark:hover:file:bg-sky-900/50', createAjaxErrors.proof_file ? 'text-red-400 dark:text-red-300' : '']" /><p v-if="createAjaxErrors.proof_file" class="text-red-500 text-xs mt-1">{{ createAjaxErrors.proof_file }}</p></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Keterangan</label><textarea v-model="createForm.status_description" rows="2" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors resize-none bg-white dark:bg-gray-900 text-gray-900 dark:text-white', createAjaxErrors.status_description ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500 focus:border-sky-500']"></textarea><p v-if="createAjaxErrors.status_description" class="text-red-500 text-xs mt-1">{{ createAjaxErrors.status_description }}</p></div>
      </div><div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700"><button type="button" @click="showCreateModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button><button data-testid="btn-simpan" type="submit" :disabled="createForm.processing" class="px-6 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm disabled:opacity-50"><i class="fas fa-save mr-1.5"></i>Simpan</button></div></form></div></Transition></Teleport>

    <!-- Edit Modal -->
    <Teleport to="body"><Transition name="modal"><div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showEditModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><form @submit.prevent="submitEdit" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col"><div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-edit text-sky-500 mr-2"></i>Edit Pembayaran</h3><button type="button" @click="showEditModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button></div><div class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll">
        <FormErrorSummary :errors="editAjaxErrors" />
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Kode Pembayaran <span class="text-red-500">*</span></label><input v-model="editForm.code" type="text" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-900 text-gray-500 dark:text-gray-400 text-sm focus:ring-2 focus:ring-sky-500 outline-none" readonly /><p class="text-xs text-gray-400 mt-1">Kode tidak dapat diubah.</p></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Invoice <span class="text-red-500">*</span></label><SearchableSelectAjax v-model="editForm.cust_internet_invc_id" url="/operator-perusahaan/api/search/invoices" placeholder="— Pilih Invoice —" :error="!!editAjaxErrors.cust_internet_invc_id" :selected-label="selectedItem?.invoice_number" /><p v-if="editAjaxErrors.cust_internet_invc_id" class="text-red-500 text-xs mt-1">{{ editAjaxErrors.cust_internet_invc_id }}</p></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Jumlah (Rp) <span class="text-red-500">*</span></label><input v-model="editForm.amount_paid" type="number" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', editAjaxErrors.amount_paid ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500 focus:border-sky-500']" /><p v-if="editAjaxErrors.amount_paid" class="text-red-500 text-xs mt-1">{{ editAjaxErrors.amount_paid }}</p></div>
        <div class="grid grid-cols-2 gap-4"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Provider <span class="text-red-500">*</span></label><select v-model="editForm.provider" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', editAjaxErrors.provider ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500 focus:border-sky-500']"><option value="">— Pilih —</option><option v-for="p in providers" :key="p" :value="p">{{ providerLabel(p) }}</option></select><p v-if="editAjaxErrors.provider" class="text-red-500 text-xs mt-1">{{ editAjaxErrors.provider }}</p></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Metode <span class="text-red-500">*</span></label><select v-model="editForm.payment_method" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', editAjaxErrors.payment_method ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500 focus:border-sky-500']"><option value="">— Pilih —</option><option v-for="m in paymentMethods" :key="m" :value="m">{{ paymentMethodLabel(m) }}</option></select><p v-if="editAjaxErrors.payment_method" class="text-red-500 text-xs mt-1">{{ editAjaxErrors.payment_method }}</p></div></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Bukti Bayar <span class="text-xs text-gray-400">(kosongkan jika tidak diubah, maks 2MB)</span></label><input @change="e => editFormFile = e.target.files[0]" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf" :class="['w-full text-sm text-gray-500 dark:text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-sky-50 file:text-sky-700 dark:file:bg-sky-900/30 dark:file:text-sky-400 hover:file:bg-sky-100 dark:hover:file:bg-sky-900/50', editAjaxErrors.proof_file ? 'text-red-400 dark:text-red-300' : '']" /><p v-if="editAjaxErrors.proof_file" class="text-red-500 text-xs mt-1">{{ editAjaxErrors.proof_file }}</p></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Keterangan</label><textarea v-model="editForm.status_description" rows="2" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors resize-none bg-white dark:bg-gray-900 text-gray-900 dark:text-white', editAjaxErrors.status_description ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500 focus:border-sky-500']"></textarea><p v-if="editAjaxErrors.status_description" class="text-red-500 text-xs mt-1">{{ editAjaxErrors.status_description }}</p></div>
      </div><div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700"><button type="button" @click="showEditModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button><button type="submit" :disabled="editForm.processing" class="px-6 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm disabled:opacity-50"><i class="fas fa-check mr-1.5"></i>Update</button></div></form></div></Transition></Teleport>

    <!-- Delete Modal -->
    <Teleport to="body"><Transition name="modal"><div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showDeleteModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-sm"><div class="px-6 py-5 text-center"><div class="w-14 h-14 mx-auto rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-4"><i class="fas fa-exclamation-triangle text-red-500 text-xl"></i></div><h3 class="text-lg font-semibold mb-2 text-gray-900 dark:text-white">Konfirmasi Hapus</h3><p class="text-sm text-gray-600 dark:text-gray-400">Anda akan menghapus pembayaran <strong class="text-gray-900 dark:text-white">{{ selectedItem?.code }}</strong>. Tindakan ini tidak dapat dibatalkan.</p></div><div class="flex justify-center gap-3 px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700"><button @click="showDeleteModal = false" class="px-5 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">Batal</button><button @click="confirmDelete" class="px-5 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700"><i class="fas fa-trash-alt mr-1.5"></i> Hapus</button></div></div></div></Transition></Teleport>

    <!-- REVIEW MODAL (single + bulk) -->
    <Teleport to="body"><Transition name="modal"><div v-if="showReviewModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showReviewModal = false; reviewForm.reset(); bulkReviewForm.reset();"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
      <!-- Single Review -->
      <form v-if="selectedItem && !hasSelected" @submit.prevent="submitReview" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] flex flex-col">
        <div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-clipboard-check text-sky-500 mr-2"></i>Review: {{ selectedItem?.code }}</h3><button type="button" @click="showReviewModal = false; reviewForm.reset();" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button></div>
        <div class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll">
          <FormErrorSummary :errors="reviewForm.errors" />
          <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-3"><div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Kode Pembayaran</div><div class="font-mono font-medium text-gray-900 dark:text-white">{{ selectedItem?.code }}</div></div>
          <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-3"><div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Nominal</div><div class="font-mono font-semibold text-sky-600">Rp {{ Number(selectedItem?.amount_paid || 0).toLocaleString('id') }}</div></div>
          <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Status Review <span class="text-red-500">*</span></label><select v-model="reviewForm.review_status" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', reviewForm.errors.review_status ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500 focus:border-sky-500']"><option value="">— Pilih —</option><option value="approved">Disetujui</option><option value="rejected">Ditolak</option></select><p v-if="reviewForm.errors.review_status" class="text-red-500 text-xs mt-1">{{ reviewForm.errors.review_status }}</p></div>
          <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Alasan <span v-if="reviewForm.review_status === 'rejected'" class="text-red-500">*</span><span v-else class="text-xs text-gray-400">(wajib jika ditolak)</span></label><textarea v-model="reviewForm.review_reason" rows="3" placeholder="Jelaskan alasan persetujuan atau penolakan..." :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors resize-none bg-white dark:bg-gray-900 text-gray-900 dark:text-white', reviewForm.errors.review_reason ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500 focus:border-sky-500']"></textarea><p v-if="reviewForm.errors.review_reason" class="text-red-500 text-xs mt-1">{{ reviewForm.errors.review_reason }}</p></div>
          <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Lampiran Review <span class="text-xs text-gray-400">(opsional)</span></label><input @change="reviewForm.review_attachment = $event.target.files[0]" type="file" accept=".jpg,.jpeg,.png,.pdf" :class="['w-full text-sm text-gray-500 dark:text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-sky-50 file:text-sky-700 dark:file:bg-sky-900/30 dark:file:text-sky-400 hover:file:bg-sky-100 dark:hover:file:bg-sky-900/50', reviewForm.errors.review_attachment ? 'text-red-400 dark:text-red-300' : '']" /><p v-if="reviewForm.errors.review_attachment" class="text-red-500 text-xs mt-1">{{ reviewForm.errors.review_attachment }}</p></div>
        </div>
        <div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700"><button type="button" @click="showReviewModal = false; reviewForm.reset();" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button><button type="submit" :disabled="reviewForm.processing" class="px-6 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm disabled:opacity-50"><i class="fas fa-check mr-1.5"></i>Simpan Review</button></div>
      </form>
      <!-- Bulk Review -->
      <form v-else @submit.prevent="submitBulkReview" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] flex flex-col">
        <div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-clipboard-check text-sky-500 mr-2"></i>Bulk Review ({{ selectedIds.length }} item)</h3><button type="button" @click="showReviewModal = false; bulkReviewForm.reset();" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button></div>
        <div class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll">
          <FormErrorSummary :errors="bulkReviewForm.errors" />
          <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg px-4 py-3 text-sm text-amber-700 dark:text-amber-400"><i class="fas fa-info-circle mr-1.5"></i> {{ selectedPendingCount }} item berstatus pending akan direview.</div>
          <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Status Review <span class="text-red-500">*</span></label><select v-model="bulkReviewForm.review_status" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', bulkReviewForm.errors.review_status ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500 focus:border-sky-500']"><option value="">— Pilih —</option><option value="approved">Disetujui</option><option value="rejected">Ditolak</option></select><p v-if="bulkReviewForm.errors.review_status" class="text-red-500 text-xs mt-1">{{ bulkReviewForm.errors.review_status }}</p></div>
          <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Alasan <span v-if="bulkReviewForm.review_status === 'rejected'" class="text-red-500">*</span><span v-else class="text-xs text-gray-400">(wajib jika ditolak)</span></label><textarea v-model="bulkReviewForm.review_reason" rows="3" placeholder="Jelaskan alasan persetujuan atau penolakan..." :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors resize-none bg-white dark:bg-gray-900 text-gray-900 dark:text-white', bulkReviewForm.errors.review_reason ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500 focus:border-sky-500']"></textarea><p v-if="bulkReviewForm.errors.review_reason" class="text-red-500 text-xs mt-1">{{ bulkReviewForm.errors.review_reason }}</p></div>
          <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Lampiran Review <span class="text-xs text-gray-400">(opsional)</span></label><input @change="bulkReviewForm.review_attachment = $event.target.files[0]" type="file" accept=".jpg,.jpeg,.png,.pdf" :class="['w-full text-sm text-gray-500 dark:text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-sky-50 file:text-sky-700 dark:file:bg-sky-900/30 dark:file:text-sky-400 hover:file:bg-sky-100 dark:hover:file:bg-sky-900/50', bulkReviewForm.errors.review_attachment ? 'text-red-400 dark:text-red-300' : '']" /><p v-if="bulkReviewForm.errors.review_attachment" class="text-red-500 text-xs mt-1">{{ bulkReviewForm.errors.review_attachment }}</p></div>
        </div>
        <div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700"><button type="button" @click="showReviewModal = false; bulkReviewForm.reset();" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button><button type="submit" :disabled="bulkReviewForm.processing" class="px-6 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm disabled:opacity-50"><i class="fas fa-check mr-1.5"></i>Simpan Bulk Review</button></div>
      </form>
    </div></Transition></Teleport>

    <!-- Import Modal -->
    <Teleport to="body"><Transition name="modal"><div v-if="showImportModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showImportModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><form @submit.prevent="submitImport" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] flex flex-col"><div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-file-import text-emerald-500 mr-2"></i>Import Pembayaran</h3><button type="button" @click="showImportModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700"><i class="fas fa-times"></i></button></div><div class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll"><FormErrorSummary :errors="importAjaxErrors" /><div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg px-4 py-3 text-sm text-amber-700 dark:text-amber-300"><i class="fas fa-info-circle mr-1.5"></i> Pastikan file Excel sesuai template. Kolom: No. Invoice, Jumlah Bayar, Provider, Metode Pembayaran.</div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">File Excel <span class="text-red-500">*</span></label><input @change="onImportFileChange" type="file" accept=".xlsx,.xls" :class="['w-full text-sm text-gray-500 dark:text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-sky-50 file:text-sky-700 dark:file:bg-sky-900/30 dark:file:text-sky-400 hover:file:bg-sky-100 dark:hover:file:bg-sky-900/50', importAjaxErrors.file ? 'text-red-400 dark:text-red-300' : '']" /><p v-if="importAjaxErrors.file" class="text-red-500 text-xs mt-1">{{ importAjaxErrors.file }}</p></div></div><div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700"><button type="button" @click="showImportModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">Batal</button><button type="submit" :disabled="importing" class="px-6 py-2.5 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 disabled:opacity-50"><i class="fas fa-upload mr-1.5"></i>{{ importing ? 'Importing...' : 'Import' }}</button></div></form></div></Transition></Teleport>
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