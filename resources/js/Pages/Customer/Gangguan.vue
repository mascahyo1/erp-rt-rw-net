<script setup>
import { ref, computed, reactive } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import FormErrorSummary from '@/Components/FormErrorSummary.vue';
import { errorSummary } from '@/Composables/useFormErrorToast.js';
import { useToast } from '@/Composables/useToast';
import SearchableSelectAjax from '@/Components/SearchableSelectAjax.vue';
import ToastContainer from '@/Components/ToastContainer.vue';

defineOptions({ layout: CustomerLayout });

const ATT_TYPE_BUKTI_ISSUE = 'bukti_issue';

const props = defineProps({
  gangguans: Object,
  filters: Object,
  custInternets: { type: Array, default: () => [] },
  statusPengerjaanOptions: { type: Array, default: () => [] },
  statusVerifikasiOptions: { type: Array, default: () => [] },
});

const toast = useToast();
const searchInput = ref(props.filters?.search || '');
const statusPengerjaanFilter = ref(props.filters?.status_pengerjaan || '');
const statusVerifikasiFilter = ref(props.filters?.status_verifikasi || '');
const sortField = ref(props.filters?.sort_field || 'created_at');
const sortDir = ref(props.filters?.sort_dir || 'desc');
const perPage = ref(props.filters?.per_page ? Number(props.filters.per_page) : 10);
const perPageOptions = [5, 10, 25, 50, 100];
const selectedIds = ref([]);
const showCreateModal = ref(false);
const showDetailModal = ref(false);
const showDeleteModal = ref(false);
const selectedItem = ref(null);

const createForm = useForm({ cust_internet_id: '', main_pic_employee_id: '', additional_pic_employee_ids: [], catatan: '', issue_dimulai_dari: '' });
const createErrors = ref({});
const createAttachments = reactive({
  [ATT_TYPE_BUKTI_ISSUE]: { files: [], names: [], descs: [] },
});

function buildQuery(o = {}) {
  const p = { ...o };
  if (p.search === undefined) p.search = searchInput.value || undefined;
  if (p.status_pengerjaan === undefined) p.status_pengerjaan = statusPengerjaanFilter.value || undefined;
  if (p.status_verifikasi === undefined) p.status_verifikasi = statusVerifikasiFilter.value || undefined;
  if (p.sort_field === undefined) p.sort_field = sortField.value || undefined;
  if (p.sort_dir === undefined) p.sort_dir = sortDir.value || undefined;
  if (p.per_page === undefined) p.per_page = perPage.value;
  Object.keys(p).forEach(k => { if (p[k] === undefined) delete p[k]; });
  return p;
}
function fetchData(o = {}) {
  router.get('/customer/gangguan', buildQuery(o), { preserveState: true, preserveScroll: true, replace: true });
}
function applySearch() { fetchData({ page: 1 }); }
function clearSearch() { searchInput.value = ''; fetchData({ page: 1 }); }
function applyFilters() { fetchData({ page: 1 }); }
function resetFilters() {
  searchInput.value = ''; statusPengerjaanFilter.value = ''; statusVerifikasiFilter.value = '';
  fetchData({ search: undefined, status_pengerjaan: undefined, status_verifikasi: undefined, page: 1 });
}
function sort(f) {
  if (sortField.value === f) { if (sortDir.value === 'asc') sortDir.value = 'desc'; else { sortField.value = ''; sortDir.value = 'desc'; } }
  else { sortField.value = f; sortDir.value = 'asc'; }
  fetchData({ sort_field: sortField.value || undefined, sort_dir: sortDir.value || undefined });
}
function sortIcon(f) { if (sortField.value !== f) return 'fa-sort'; return sortDir.value === 'asc' ? 'fa-sort-up' : 'fa-sort-down'; }
function changePerPage(n) { perPage.value = n; fetchData({ per_page: n, page: 1 }); }
function goToPage(p) { fetchData({ page: p }); }

function statusPengerjaanBadge(s) {
  if (s === 'open') return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
  if (s === 'in_progress') return 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400';
  if (s === 'resolved') return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
  return 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400';
}
function statusVerifikasiBadge(s) {
  if (s === 'approved') return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
  if (s === 'rejected') return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
  return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
}
function formatDate(iso) {
  if (!iso) return '—';
  const d = new Date(iso);
  return d.toLocaleString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}
const items = computed(() => props.gangguans?.data || []);
const pagination = computed(() => ({
  current: props.gangguans?.current_page || 1,
  last: props.gangguans?.last_page || 1,
  total: props.gangguans?.total || 0,
}));
const hasSelected = computed(() => selectedIds.value.length > 0);
const isAllSelected = computed({
  get: () => items.value.length > 0 && items.value.every(i => selectedIds.value.includes(i.id)),
  set: (val) => { selectedIds.value = val ? items.value.map(i => i.id) : []; },
});
function toggleSelect(id) { const i = selectedIds.value.indexOf(id); i === -1 ? selectedIds.value.push(id) : selectedIds.value.splice(i, 1); }
function clearSelected() { selectedIds.value = []; }
async function bulkDelete() {
  if (selectedIds.value.length === 0) { toast.error('Pilih data terlebih dahulu.'); return; }
  if (!confirm(`Hapus ${selectedIds.value.length} laporan? Hanya yang masih OPEN yang bisa dihapus.`)) return;
  let success = 0; let fail = 0;
  for (const id of selectedIds.value) {
    try {
      const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
      const resp = await fetch(`/customer/gangguan/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
      if (resp.ok) success++; else fail++;
    } catch (e) { fail++; }
  }
  selectedIds.value = [];
  if (success > 0) toast.success(`${success} laporan berhasil dihapus.`);
  if (fail > 0) toast.error(`${fail} laporan gagal (mungkin sudah diproses admin).`);
  fetchData();
}

function addAttachmentFile(typeKey, stateRef, file) {
  stateRef[typeKey].files.push(file);
  stateRef[typeKey].names.push('');
  stateRef[typeKey].descs.push('');
}
function removeAttachmentFile(typeKey, stateRef, index) {
  stateRef[typeKey].files.splice(index, 1);
  stateRef[typeKey].names.splice(index, 1);
  stateRef[typeKey].descs.splice(index, 1);
}

function openCreate() {
  createForm.reset();
  createForm.clearErrors();
  createErrors.value = {};
  createAttachments[ATT_TYPE_BUKTI_ISSUE] = { files: [], names: [], descs: [] };
  showCreateModal.value = true;
}
async function submitCreate() {
  const fd = new FormData();
  fd.append('cust_internet_id', createForm.cust_internet_id);
  fd.append('main_pic_employee_id', createForm.main_pic_employee_id || '');
  (createForm.additional_pic_employee_ids || []).forEach(id => fd.append('additional_pic_employee_ids[]', id));
  fd.append('catatan', createForm.catatan);
  fd.append('issue_dimulai_dari', createForm.issue_dimulai_dari);

  // Multi-file attachments_bukti_issue (parallel arrays)
  createAttachments[ATT_TYPE_BUKTI_ISSUE].files.forEach(f => fd.append('attachments_bukti_issue[]', f));
  createAttachments[ATT_TYPE_BUKTI_ISSUE].names.forEach(n => fd.append('attachment_names[]', n));
  createAttachments[ATT_TYPE_BUKTI_ISSUE].descs.forEach(d => fd.append('attachment_descriptions[]', d));

  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const resp = await fetch('/customer/gangguan', {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
      body: fd,
    });
    const data = await resp.json().catch(() => ({}));
    if (resp.ok) {
      showCreateModal.value = false;
      createAttachments[ATT_TYPE_BUKTI_ISSUE] = { files: [], names: [], descs: [] };
      toast.success('Laporan gangguan berhasil dikirim. Tim kami akan segera menindaklanjuti.');
      fetchData();
    } else {
      const errs = data.errors || {};
      if (Object.keys(errs).length > 0) {
        createErrors.value = errs;
        toast.error('Validasi gagal: ' + errorSummary(errs), 6000);
      } else {
        toast.error(data.message || `Gagal (HTTP ${resp.status})`);
      }
    }
  } catch (e) { toast.error('Error: ' + e.message); }
}
function openDetail(item) { selectedItem.value = item; showDetailModal.value = true; }
function openDelete(item) { selectedItem.value = item; showDeleteModal.value = true; }
function confirmDelete() { router.delete(`/customer/gangguan/${selectedItem.value.id}`, { onSuccess: () => { showDeleteModal.value = false; fetchData(); toast.success('Laporan berhasil dihapus.'); } }); }
</script>

<template>
  <Head title="Lapor Gangguan" />
  <ToastContainer />

  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Lapor Gangguan</h1>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Laporkan gangguan internet Anda. Tim kami akan segera menindaklanjuti.</p>
      </div>
      <button data-testid="btn-buat-laporan" @click="openCreate" class="inline-flex items-center px-4 py-2.5 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors shadow-sm">
        <i class="fas fa-plus mr-1.5"></i> Buat Laporan
      </button>
    </div>

    <!-- Filter -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
      <div class="flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-[200px]">
          <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Cari</label>
          <input v-model="searchInput" @keyup.enter="applySearch" type="text" placeholder="Cari kode / catatan..." class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 outline-none" data-testid="input-search">
        </div>
        <div class="min-w-[160px]">
          <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Status Pengerjaan</label>
          <select v-model="statusPengerjaanFilter" @change="applyFilters" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
            <option value="">Semua</option>
            <option v-for="s in statusPengerjaanOptions" :key="s" :value="s">{{ s }}</option>
          </select>
        </div>
        <div class="min-w-[160px]">
          <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Status Verifikasi</label>
          <select v-model="statusVerifikasiFilter" @change="applyFilters" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
            <option value="">Semua</option>
            <option v-for="s in statusVerifikasiOptions" :key="s" :value="s">{{ s }}</option>
          </select>
        </div>
        <button @click="applyFilters" class="px-4 py-2 bg-emerald-600 text-white text-sm rounded-lg hover:bg-emerald-700"><i class="fas fa-filter mr-1"></i>Filter</button>
        <button @click="resetFilters" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">Reset</button>
      </div>
    </div>

    <!-- Table -->
    <!-- Bulk Action Bar -->
    <div v-if="hasSelected" class="flex items-center justify-between px-4 py-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl shadow-sm">
      <span class="text-sm font-medium text-emerald-700 dark:text-emerald-300"><i class="fas fa-check-circle mr-1.5"></i> {{ selectedIds.length }} laporan dipilih</span>
      <div class="flex items-center gap-2">
        <button data-testid="btn-bulk-delete" @click="bulkDelete" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-red-600 text-white hover:bg-red-700"><i class="fas fa-trash-alt mr-1"></i>Hapus</button>
        <button data-testid="btn-bulk-clear" @click="clearSelected" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-600">Batal Pilih</button>
      </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
      <div class="overflow-x-auto">
        <table data-testid="table-data" class="w-full text-sm">
          <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
            <tr>
              <th class="px-3 py-3 w-10"><input data-testid="checkbox-select-all" type="checkbox" v-model="isAllSelected" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" /></th>
              <th class="px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 text-xs">Kode</th>
              <th class="px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 text-xs">Kode Langganan</th>
              <th class="px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 text-xs">Catatan</th>
              <th class="px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 text-xs">PIC</th>
              <th class="px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 text-xs cursor-pointer" @click="sort('status_pengerjaan')">Pengerjaan <i :class="['fas', sortIcon('status_pengerjaan')]"></i></th>
              <th class="px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 text-xs cursor-pointer" @click="sort('status_verifikasi')">Verifikasi <i :class="['fas', sortIcon('status_verifikasi')]"></i></th>
              <th class="px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 text-xs cursor-pointer" @click="sort('issue_dimulai_dari')">Tgl Mulai <i :class="['fas', sortIcon('issue_dimulai_dari')]"></i></th>
              <th class="px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 text-xs cursor-pointer" @click="sort('issue_diselesaikan_pada')">Tgl Selesai <i :class="['fas', sortIcon('issue_diselesaikan_pada')]"></i></th>
              <th class="px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 text-xs">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in items" :key="item.id" :class="['border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-900/30', selectedIds.includes(item.id) ? 'bg-emerald-50/50 dark:bg-emerald-900/10' : '']">
              <td class="px-3 py-3"><input data-testid="checkbox-row" type="checkbox" :checked="selectedIds.includes(item.id)" @change="toggleSelect(item.id)" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" /></td>
              <td class="px-3 py-3 font-mono text-xs text-gray-900 dark:text-white">{{ item.code }}</td>
              <td class="px-3 py-3 text-xs text-gray-600 dark:text-gray-400">{{ item.cust_internet_label }}</td>
              <td class="px-3 py-3 text-xs text-gray-600 dark:text-gray-400 max-w-xs truncate">{{ item.catatan }}</td>
              <td class="px-3 py-3 text-xs text-gray-600 dark:text-gray-400">{{ item.main_pic_name || '—' }}</td>
              <td class="px-3 py-3"><span :class="['inline-flex px-2 py-0.5 rounded text-xs font-medium', statusPengerjaanBadge(item.status_pengerjaan)]">{{ item.status_pengerjaan_label }}</span></td>
              <td class="px-3 py-3"><span :class="['inline-flex px-2 py-0.5 rounded text-xs font-medium', statusVerifikasiBadge(item.status_verifikasi)]">{{ item.status_verifikasi_label }}</span></td>
              <td class="px-3 py-3 text-xs text-gray-500 dark:text-gray-400">{{ formatDate(item.issue_dimulai_dari) }}</td>
              <td class="px-3 py-3 text-xs text-gray-500 dark:text-gray-400">{{ formatDate(item.issue_diselesaikan_pada) }}</td>
              <td class="px-3 py-3">
                <div class="flex items-center gap-1">
                  <button data-testid="btn-detail" @click="openDetail(item)" title="Detail" class="p-1.5 rounded-lg text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:text-emerald-400 dark:hover:bg-emerald-900/30"><i class="fas fa-eye"></i></button>
                  <button v-if="item.status_pengerjaan === 'open'" data-testid="btn-delete" @click="openDelete(item)" title="Hapus" class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:text-red-400 dark:hover:bg-red-900/30"><i class="fas fa-trash-alt"></i></button>
                </div>
              </td>
            </tr>
            <tr v-if="items.length === 0"><td colspan="9" class="px-3 py-12 text-center text-gray-500 dark:text-gray-400">Belum ada laporan gangguan.</td></tr>
          </tbody>
        </table>
      </div>
      <div class="flex flex-col sm:flex-row items-center justify-between px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
          <span>Tampilkan</span>
          <select v-model="perPage" @change="changePerPage(perPage)" class="px-2 py-1 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm">
            <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }}</option>
          </select>
          <span>dari {{ pagination.total }} data</span>
        </div>
        <div class="flex items-center gap-1">
          <button @click="goToPage(1)" :disabled="pagination.current <= 1" class="px-2 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-30"><i class="fas fa-angle-double-left"></i></button>
          <button @click="goToPage(pagination.current-1)" :disabled="pagination.current <= 1" class="px-2 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-30"><i class="fas fa-angle-left"></i></button>
          <span class="px-2 text-sm text-gray-500">{{ pagination.current }} / {{ pagination.last }}</span>
          <button @click="goToPage(pagination.current+1)" :disabled="pagination.current >= pagination.last" class="px-2 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-30"><i class="fas fa-angle-right"></i></button>
          <button @click="goToPage(pagination.last)" :disabled="pagination.current >= pagination.last" class="px-2 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-30"><i class="fas fa-angle-double-right"></i></button>
        </div>
      </div>
    </div>

    <!-- Create Modal -->
    <Teleport to="body"><Transition name="modal"><div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showCreateModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><form data-testid="modal-create" @submit.prevent="submitCreate" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col"><div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-triangle-exclamation text-amber-500 mr-2"></i>Buat Laporan Gangguan</h3><button type="button" @click="showCreateModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button></div><div class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll">
        <FormErrorSummary :errors="createErrors" testId="form-error-summary-create-gangguan" />
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Kode Langganan <span class="text-red-500">*</span></label><span data-testid="btn-select-cust-internet"><SearchableSelectAjax data-testid="select-cust-internet" v-model="createForm.cust_internet_id" url="/customer/api/search/langganans" placeholder="— Pilih Kode Langganan —" display-key="label" :error="!!createErrors.cust_internet_id" /></span><p v-if="createErrors.cust_internet_id" class="text-red-500 text-xs mt-1">{{ createErrors.cust_internet_id }}</p></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Kapan gangguan terjadi? <span class="text-red-500">*</span></label><input data-testid="input-issue-dimulai" v-model="createForm.issue_dimulai_dari" type="datetime-local" :class="['w-full px-3 py-2.5 rounded-lg border bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 outline-none', createErrors.issue_dimulai_dari ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-emerald-500']" /><p v-if="createErrors.issue_dimulai_dari" class="text-red-500 text-xs mt-1">{{ createErrors.issue_dimulai_dari }}</p></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Catatan <span class="text-red-500">*</span></label><textarea data-testid="textarea-catatan" v-model="createForm.catatan" rows="4" placeholder="Jelaskan masalah yang Anda alami..." :class="['w-full px-3 py-2.5 rounded-lg border bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 outline-none resize-none', createErrors.catatan ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-emerald-500']"></textarea><p v-if="createErrors.catatan" class="text-red-500 text-xs mt-1">{{ createErrors.catatan }}</p></div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Bukti Issue <span class="text-xs text-gray-400">(opsional, bisa lebih dari 1 file)</span></label>
          <div v-if="createAttachments[ATT_TYPE_BUKTI_ISSUE].files.length > 0" class="space-y-2 mb-2">
            <div v-for="(f, i) in createAttachments[ATT_TYPE_BUKTI_ISSUE].files" :key="i" class="p-2.5 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg space-y-1.5">
              <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-emerald-700 dark:text-emerald-300"><i class="fas fa-paperclip mr-1"></i>{{ f.name }} ({{ Math.round(f.size/1024) }} KB)</span>
                <button type="button" data-testid="btn-remove-attachment" @click="removeAttachmentFile(ATT_TYPE_BUKTI_ISSUE, createAttachments, i)" class="text-xs text-red-600 hover:text-red-700"><i class="fas fa-times"></i></button>
              </div>
              <input v-model="createAttachments[ATT_TYPE_BUKTI_ISSUE].names[i]" placeholder="Nama / label file (opsional)" class="w-full px-2 py-1 text-xs border border-emerald-300 dark:border-emerald-700 rounded bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-1 focus:ring-emerald-500 outline-none" />
              <input v-model="createAttachments[ATT_TYPE_BUKTI_ISSUE].descs[i]" placeholder="Keterangan (opsional)" class="w-full px-2 py-1 text-xs border border-emerald-300 dark:border-emerald-700 rounded bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-1 focus:ring-emerald-500 outline-none" />
            </div>
          </div>
          <input data-testid="input-file-bukti" @change="e => { for (const f of e.target.files) addAttachmentFile(ATT_TYPE_BUKTI_ISSUE, createAttachments, f); e.target.value = ''; }" type="file" multiple accept=".jpg,.jpeg,.png,.webp,.pdf" class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-emerald-50 file:text-emerald-700 dark:file:bg-emerald-900/30 dark:file:text-emerald-400 hover:file:bg-emerald-100 dark:hover:file:bg-emerald-900/50" />
        </div>
      </div><div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700"><button type="button" @click="showCreateModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button><button data-testid="btn-kirim" type="submit" :disabled="createForm.processing" class="px-6 py-2.5 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors shadow-sm disabled:opacity-50"><i class="fas fa-paper-plane mr-1.5"></i>Kirim Laporan</button></div></form></div></Transition></Teleport>

    <!-- Detail Modal -->
    <Teleport to="body"><Transition name="modal"><div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showDetailModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col"><div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white">Detail Laporan</h3><button @click="showDetailModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700"><i class="fas fa-times"></i></button></div><div class="overflow-y-auto flex-1 px-6 py-5 modal-scroll" v-if="selectedItem">
        <div class="grid grid-cols-2 gap-4">
          <div><label class="text-xs text-gray-500 dark:text-gray-400">Kode</label><p class="font-mono font-medium text-gray-900 dark:text-white">{{ selectedItem.code }}</p></div>
          <div><label class="text-xs text-gray-500 dark:text-gray-400">Kode Langganan</label><p class="text-sm text-gray-900 dark:text-white">{{ selectedItem.cust_internet_label }}</p></div>
          <div><label class="text-xs text-gray-500 dark:text-gray-400">Status Pengerjaan</label><p><span :class="['inline-flex px-2 py-0.5 rounded text-xs font-medium', statusPengerjaanBadge(selectedItem.status_pengerjaan)]">{{ selectedItem.status_pengerjaan_label }}</span></p></div>
          <div><label class="text-xs text-gray-500 dark:text-gray-400">Status Verifikasi</label><p><span :class="['inline-flex px-2 py-0.5 rounded text-xs font-medium', statusVerifikasiBadge(selectedItem.status_verifikasi)]">{{ selectedItem.status_verifikasi_label }}</span></p></div>
          <div><label class="text-xs text-gray-500 dark:text-gray-400">Penanggung Jawab</label><p class="text-sm text-gray-900 dark:text-white">{{ selectedItem.assigned_to_name || '—' }}</p></div>
          <div><label class="text-xs text-gray-500 dark:text-gray-400">Tgl Mulai</label><p class="text-sm text-gray-900 dark:text-white">{{ formatDate(selectedItem.issue_dimulai_dari) }}</p></div>
          <div><label class="text-xs text-gray-500 dark:text-gray-400">Tgl Selesai</label><p class="text-sm text-gray-900 dark:text-white">{{ formatDate(selectedItem.issue_diselesaikan_pada) }}</p></div>
          <div class="col-span-2"><label class="text-xs text-gray-500 dark:text-gray-400">Catatan</label><p class="text-sm text-gray-900 dark:text-white whitespace-pre-wrap">{{ selectedItem.catatan }}</p></div>
          <div v-if="selectedItem.alasan_verifikasi"><label class="text-xs text-gray-500 dark:text-gray-400">Alasan Verifikasi</label><p class="text-sm text-gray-900 dark:text-white whitespace-pre-wrap">{{ selectedItem.alasan_verifikasi }}</p></div>
          <div v-if="(selectedItem.attachments?.bukti_issue?.length || 0) > 0" class="col-span-2">
            <label class="text-xs text-gray-500 dark:text-gray-400">Bukti Issue ({{ selectedItem.attachments.bukti_issue.length }})</label>
            <div class="flex flex-wrap gap-1.5 mt-1">
              <a v-for="att in selectedItem.attachments.bukti_issue" :key="att.id" :href="att.url" target="_blank" data-testid="detail-attachment-issue" class="inline-flex items-center px-2.5 py-1 bg-emerald-600 text-white text-xs rounded-lg hover:bg-emerald-700"><i class="fas fa-image mr-1"></i>{{ att.file_name }}</a>
            </div>
          </div>
          <div v-if="(selectedItem.attachments?.bukti_issue_selesai?.length || 0) > 0" class="col-span-2">
            <label class="text-xs text-gray-500 dark:text-gray-400">Bukti Selesai ({{ selectedItem.attachments.bukti_issue_selesai.length }})</label>
            <div class="flex flex-wrap gap-1.5 mt-1">
              <a v-for="att in selectedItem.attachments.bukti_issue_selesai" :key="att.id" :href="att.url" target="_blank" data-testid="detail-attachment-selesai" class="inline-flex items-center px-2.5 py-1 bg-emerald-600 text-white text-xs rounded-lg hover:bg-emerald-700"><i class="fas fa-image mr-1"></i>{{ att.file_name }}</a>
            </div>
          </div>
        </div>
      </div></div></div></Transition></Teleport>

    <!-- Delete Modal -->
    <Teleport to="body"><Transition name="modal"><div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showDeleteModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-sm"><div class="px-6 py-5 text-center"><div class="w-14 h-14 mx-auto rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-4"><i class="fas fa-exclamation-triangle text-red-500 text-xl"></i></div><h3 class="text-lg font-semibold mb-2 text-gray-900 dark:text-white">Hapus Laporan?</h3><p class="text-sm text-gray-600 dark:text-gray-400">Hapus laporan <strong class="text-gray-900 dark:text-white">{{ selectedItem?.code }}</strong>? Hanya laporan yang masih OPEN yang bisa dihapus.</p></div><div class="flex justify-center gap-3 px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700"><button @click="showDeleteModal = false" class="px-5 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">Batal</button><button data-testid="btn-confirm-delete" @click="confirmDelete" class="px-5 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700"><i class="fas fa-trash-alt mr-1.5"></i> Hapus</button></div></div></div></Transition></Teleport>
  </div>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-active > div:last-child, .modal-leave-active > div:last-child { transition: transform 0.2s ease, opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-from > div:last-child { transform: scale(0.95) translateY(10px); opacity: 0; }
.modal-leave-to > div:last-child { transform: scale(0.95) translateY(10px); opacity: 0; }
</style>
