<script setup>
import { ref, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import OperatorPerusahaanLayout from '@/Layouts/OperatorPerusahaanLayout.vue';
import { useToast } from '@/Composables/useToast';
import SearchableSelectAjax from '@/Components/SearchableSelectAjax.vue';
import ToastContainer from '@/Components/ToastContainer.vue';

defineOptions({ layout: OperatorPerusahaanLayout });

const props = defineProps({
  gangguans: Object,
  filters: Object,
  custInternets: { type: Array, default: () => [] },
  employees: { type: Array, default: () => [] },
  statusPengerjaanOptions: { type: Array, default: () => [] },
  statusVerifikasiOptions: { type: Array, default: () => [] },
});

const toast = useToast();
const searchInput = ref(props.filters?.search || '');
const statusPengerjaanFilter = ref(props.filters?.status_pengerjaan || '');
const statusVerifikasiFilter = ref(props.filters?.status_verifikasi || '');
const assignedFilter = ref(props.filters?.assigned_to_employee_id || '');
const custInetFilter = ref(props.filters?.cust_internet_id || '');
const createdStartFilter = ref(props.filters?.created_start || '');
const createdEndFilter = ref(props.filters?.created_end || '');
const sortField = ref(props.filters?.sort_field || 'created_at');
const sortDir = ref(props.filters?.sort_dir || 'desc');
const perPage = ref(props.filters?.per_page ? Number(props.filters.per_page) : 10);
const perPageOptions = [5, 10, 25, 50, 100];
const selectedIds = ref([]);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showVerifyModal = ref(false);
const showDetailModal = ref(false);
const showDeleteModal = ref(false);
const showImportModal = ref(false);
const importing = ref(false);
const importFile = ref(null);
const selectedItem = ref(null);

const createForm = useForm({ cust_internet_id: '', assigned_to_employee_id: '', catatan: '', file_bukti_issue: null });
const editForm = useForm({ assigned_to_employee_id: '', catatan: '', status_pengerjaan: '', file_bukti_issue: null, file_bukti_issue_diselesaikan: null, alasan_verifikasi: '', status_verifikasi: '' });
const verifyForm = useForm({ status_verifikasi: '', alasan_verifikasi: '' });
const createFormFile = ref(null);
const editFormFileIssue = ref(null);
const editFormFileSelesai = ref(null);

function buildQuery(o = {}) {
  const p = { ...o };
  if (p.search === undefined) p.search = searchInput.value || undefined;
  if (p.status_pengerjaan === undefined) p.status_pengerjaan = statusPengerjaanFilter.value || undefined;
  if (p.status_verifikasi === undefined) p.status_verifikasi = statusVerifikasiFilter.value || undefined;
  if (p.assigned_to_employee_id === undefined) p.assigned_to_employee_id = assignedFilter.value || undefined;
  if (p.cust_internet_id === undefined) p.cust_internet_id = custInetFilter.value || undefined;
  if (p.created_start === undefined) p.created_start = createdStartFilter.value || undefined;
  if (p.created_end === undefined) p.created_end = createdEndFilter.value || undefined;
  if (p.sort_field === undefined) p.sort_field = sortField.value || undefined;
  if (p.sort_dir === undefined) p.sort_dir = sortDir.value || undefined;
  if (p.per_page === undefined) p.per_page = perPage.value;
  Object.keys(p).forEach(k => { if (p[k] === undefined) delete p[k]; });
  return p;
}
function fetchData(o = {}) { router.get('/operator-perusahaan/gangguan', buildQuery(o), { preserveState: true, preserveScroll: true, replace: true }); }
function applySearch() { fetchData({ page: 1 }); }
function applyFilters() { fetchData({ page: 1 }); }
function resetFilters() {
  searchInput.value = ''; statusPengerjaanFilter.value = ''; statusVerifikasiFilter.value = '';
  assignedFilter.value = ''; custInetFilter.value = ''; createdStartFilter.value = ''; createdEndFilter.value = '';
  fetchData({ search: undefined, status_pengerjaan: undefined, status_verifikasi: undefined, assigned_to_employee_id: undefined, cust_internet_id: undefined, created_start: undefined, created_end: undefined, page: 1 });
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
  return new Date(iso).toLocaleString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}
const items = computed(() => props.gangguans?.data || []);
const pagination = computed(() => ({ current: props.gangguans?.current_page || 1, last: props.gangguans?.last_page || 1, total: props.gangguans?.total || 0 }));

function openCreate() { createForm.reset(); createForm.clearErrors(); createFormFile.value = null; showCreateModal.value = true; }
async function submitCreate() {
  const fd = new FormData();
  fd.append('cust_internet_id', createForm.cust_internet_id);
  fd.append('assigned_to_employee_id', createForm.assigned_to_employee_id || '');
  fd.append('catatan', createForm.catatan);
  if (createFormFile.value) fd.append('file_bukti_issue', createFormFile.value);
  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const resp = await fetch('/operator-perusahaan/gangguan', { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body: fd });
    const data = await resp.json().catch(() => ({}));
    if (resp.ok) { showCreateModal.value = false; toast.success('Tiket berhasil dibuat.'); fetchData(); }
    else { toast.error(data.message || `Gagal (HTTP ${resp.status})`); }
  } catch (e) { toast.error('Error: ' + e.message); }
}

function openEdit(item) {
  editForm.reset(); editForm.clearErrors();
  editForm.assigned_to_employee_id = item.assigned_to_employee_id || '';
  editForm.catatan = item.catatan;
  editForm.status_pengerjaan = item.status_pengerjaan;
  editForm.alasan_verifikasi = item.alasan_verifikasi || '';
  editForm.status_verifikasi = item.status_verifikasi;
  editFormFileIssue.value = null; editFormFileSelesai.value = null;
  selectedItem.value = item;
  showEditModal.value = true;
}
async function submitEdit() {
  const fd = new FormData();
  fd.append('_method', 'PUT');
  fd.append('assigned_to_employee_id', editForm.assigned_to_employee_id || '');
  fd.append('catatan', editForm.catatan || '');
  fd.append('status_pengerjaan', editForm.status_pengerjaan || '');
  fd.append('alasan_verifikasi', editForm.alasan_verifikasi || '');
  fd.append('status_verifikasi', editForm.status_verifikasi || '');
  if (editFormFileIssue.value) fd.append('file_bukti_issue', editFormFileIssue.value);
  if (editFormFileSelesai.value) fd.append('file_bukti_issue_diselesaikan', editFormFileSelesai.value);
  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const resp = await fetch(`/operator-perusahaan/gangguan/${selectedItem.value.id}`, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body: fd });
    const data = await resp.json().catch(() => ({}));
    if (resp.ok) { showEditModal.value = false; toast.success('Tiket berhasil diperbarui.'); fetchData(); }
    else { toast.error(data.message || `Gagal (HTTP ${resp.status})`); }
  } catch (e) { toast.error('Error: ' + e.message); }
}

function openVerify(item) {
  verifyForm.reset(); verifyForm.clearErrors();
  verifyForm.status_verifikasi = '';
  verifyForm.alasan_verifikasi = '';
  selectedItem.value = item;
  showVerifyModal.value = true;
}
async function submitVerify() {
  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const resp = await fetch(`/operator-perusahaan/gangguan/${selectedItem.value.id}/verify`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ status_verifikasi: verifyForm.status_verifikasi, alasan_verifikasi: verifyForm.alasan_verifikasi }),
    });
    const data = await resp.json().catch(() => ({}));
    if (resp.ok) { showVerifyModal.value = false; toast.success('Verifikasi berhasil disimpan.'); fetchData(); }
    else { toast.error(data.message || `Gagal (HTTP ${resp.status})`); }
  } catch (e) { toast.error('Error: ' + e.message); }
}

function openDetail(item) { selectedItem.value = item; showDetailModal.value = true; }
function openDelete(item) { selectedItem.value = item; showDeleteModal.value = true; }
function confirmDelete() { router.delete(`/operator-perusahaan/gangguan/${selectedItem.value.id}`, { onSuccess: () => { showDeleteModal.value = false; fetchData(); toast.success('Tiket berhasil dihapus.'); } }); }

function downloadTemplate() { window.open('/operator-perusahaan/gangguan/template', '_blank'); }
function exportData() { window.open('/operator-perusahaan/gangguan/export', '_blank'); }
function openImport() { importFile.value = null; showImportModal.value = true; }
function onImportFileChange(e) { importFile.value = e.target.files[0]; }
async function submitImport() {
  if (!importFile.value) { toast.error('Pilih file Excel dulu.'); return; }
  importing.value = true;
  const fd = new FormData();
  fd.append('file', importFile.value);
  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const resp = await fetch('/operator-perusahaan/gangguan/import', { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body: fd });
    const data = await resp.json().catch(() => ({}));
    if (resp.ok) { showImportModal.value = false; toast.success(data.message || 'Import berhasil.'); fetchData(); }
    else { toast.error(data.message || `Gagal (HTTP ${resp.status})`); }
  } catch (e) { toast.error('Error: ' + e.message); }
  finally { importing.value = false; }
}
</script>

<template>
  <Head title="Gangguan" />
  <ToastContainer />

  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Gangguan</h1>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Kelola tiket gangguan dari semua customer. Verifikasi hasil resolution.</p>
      </div>
      <button data-testid="btn-buat-tiket" @click="openCreate" class="inline-flex items-center px-4 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm">
        <i class="fas fa-plus mr-1.5"></i> Buat Tiket
      </button>
      <div class="flex items-center gap-2">
        <button data-testid="btn-template" @click="downloadTemplate" title="Download Template Excel" class="inline-flex items-center px-3 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"><i class="fas fa-download mr-1.5"></i>Template</button>
        <button data-testid="btn-import" @click="openImport" title="Import dari Excel" class="inline-flex items-center px-3 py-2 bg-emerald-600 text-white text-sm rounded-lg hover:bg-emerald-700 transition-colors shadow-sm"><i class="fas fa-file-upload mr-1.5"></i>Import</button>
        <button data-testid="btn-export" @click="exportData" title="Export ke Excel" class="inline-flex items-center px-3 py-2 bg-amber-600 text-white text-sm rounded-lg hover:bg-amber-700 transition-colors shadow-sm"><i class="fas fa-file-excel mr-1.5"></i>Export</button>
      </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
      <div class="flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-[200px]">
          <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Cari</label>
          <input v-model="searchInput" @keyup.enter="applySearch" type="text" placeholder="Cari kode / catatan / customer..." class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" />
        </div>
        <div class="min-w-[140px]">
          <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Pengerjaan</label>
          <select v-model="statusPengerjaanFilter" @change="applyFilters" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none">
            <option value="">Semua</option>
            <option v-for="s in statusPengerjaanOptions" :key="s" :value="s">{{ s }}</option>
          </select>
        </div>
        <div class="min-w-[140px]">
          <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Verifikasi</label>
          <select v-model="statusVerifikasiFilter" @change="applyFilters" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none">
            <option value="">Semua</option>
            <option v-for="s in statusVerifikasiOptions" :key="s" :value="s">{{ s }}</option>
          </select>
        </div>
        <div class="min-w-[160px]">
          <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Penanggung Jawab</label>
          <select v-model="assignedFilter" @change="applyFilters" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none">
            <option value="">Semua</option>
            <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.name }}</option>
          </select>
        </div>
        <div class="min-w-[120px]">
          <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Dari</label>
          <input v-model="createdStartFilter" @change="applyFilters" type="date" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" />
        </div>
        <div class="min-w-[120px]">
          <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">S/d</label>
          <input v-model="createdEndFilter" @change="applyFilters" type="date" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" />
        </div>
        <button @click="applyFilters" class="px-4 py-2 bg-sky-600 text-white text-sm rounded-lg hover:bg-sky-700"><i class="fas fa-filter mr-1"></i>Filter</button>
        <button @click="resetFilters" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">Reset</button>
      </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
            <tr>
              <th class="px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 text-xs">Kode</th>
              <th class="px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 text-xs">Customer / Langganan</th>
              <th class="px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 text-xs">Penanggung Jawab</th>
              <th class="px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 text-xs cursor-pointer" @click="sort('status_pengerjaan')">Pengerjaan <i :class="['fas', sortIcon('status_pengerjaan')]"></i></th>
              <th class="px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 text-xs cursor-pointer" @click="sort('status_verifikasi')">Verifikasi <i :class="['fas', sortIcon('status_verifikasi')]"></i></th>
              <th class="px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 text-xs cursor-pointer" @click="sort('issue_dimulai_dari')">Tgl Mulai <i :class="['fas', sortIcon('issue_dimulai_dari')]"></i></th>
              <th class="px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 text-xs">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in items" :key="item.id" class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-900/30">
              <td class="px-3 py-3 font-mono text-xs text-gray-900 dark:text-white">{{ item.code }}</td>
              <td class="px-3 py-3 text-xs text-gray-600 dark:text-gray-400">{{ item.customer_name }}<br><span class="text-gray-400 text-[10px]">{{ item.cust_internet_label }}</span></td>
              <td class="px-3 py-3 text-xs text-gray-600 dark:text-gray-400">{{ item.assigned_to_name || '—' }}</td>
              <td class="px-3 py-3"><span :class="['inline-flex px-2 py-0.5 rounded text-xs font-medium', statusPengerjaanBadge(item.status_pengerjaan)]">{{ item.status_pengerjaan_label }}</span></td>
              <td class="px-3 py-3"><span :class="['inline-flex px-2 py-0.5 rounded text-xs font-medium', statusVerifikasiBadge(item.status_verifikasi)]">{{ item.status_verifikasi_label }}</span></td>
              <td class="px-3 py-3 text-xs text-gray-500 dark:text-gray-400">{{ formatDate(item.issue_dimulai_dari) }}</td>
              <td class="px-3 py-3">
                <div class="flex items-center gap-1">
                  <button data-testid="btn-detail" @click="openDetail(item)" title="Detail" class="p-1.5 rounded-lg text-gray-400 hover:text-sky-600 hover:bg-sky-50 dark:hover:text-sky-400 dark:hover:bg-sky-900/30"><i class="fas fa-eye"></i></button>
                  <button data-testid="btn-edit" @click="openEdit(item)" title="Edit" class="p-1.5 rounded-lg text-gray-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:text-amber-400 dark:hover:bg-amber-900/30"><i class="fas fa-edit"></i></button>
                  <button v-if="item.status_pengerjaan === 'resolved' && item.status_verifikasi === 'pending'" data-testid="btn-verify" @click="openVerify(item)" title="Verifikasi" class="p-1.5 rounded-lg text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:text-emerald-400 dark:hover:bg-emerald-900/30"><i class="fas fa-clipboard-check"></i></button>
                  <button v-if="!item.deleted_at" data-testid="btn-delete" @click="openDelete(item)" title="Hapus" class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:text-red-400 dark:hover:bg-red-900/30"><i class="fas fa-trash-alt"></i></button>
                </div>
              </td>
            </tr>
            <tr v-if="items.length === 0"><td colspan="7" class="px-3 py-12 text-center text-gray-500 dark:text-gray-400">Belum ada tiket gangguan.</td></tr>
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
          <button @click="goToPage(pagination.last)" :disabled="pagination.current >= pagination.last" class="px-2 py-1.5 rounded-lg text-sm text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-30"><i class="fas fa-angle-double-right"></i></button>
        </div>
      </div>
    </div>

    <!-- Create Modal -->
    <Teleport to="body"><Transition name="modal"><div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showCreateModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><form data-testid="modal-create" @submit.prevent="submitCreate" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col"><div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-plus text-emerald-500 mr-2"></i>Buat Tiket Gangguan</h3><button type="button" @click="showCreateModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button></div><div class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll">
      <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Kode Langganan <span class="text-red-500">*</span></label><span data-testid="btn-select-cust-internet"><SearchableSelectAjax data-testid="select-cust-internet" v-model="createForm.cust_internet_id" url="/operator-perusahaan/api/search/langganans" placeholder="— Pilih Kode Langganan —" display-key="label" /></span></div>
      <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Penanggung Jawab (opsional)</label><select data-testid="select-assigned" v-model="createForm.assigned_to_employee_id" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none"><option value="">— Belum di-assign —</option><option v-for="e in employees" :key="e.id" :value="e.id">{{ e.name }}</option></select></div>
      <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Catatan <span class="text-red-500">*</span></label><textarea data-testid="textarea-catatan" v-model="createForm.catatan" rows="4" placeholder="Jelaskan masalah..." class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none resize-none"></textarea></div>
      <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Foto Bukti (opsional)</label><input data-testid="input-file-bukti" @change="e => createFormFile = e.target.files[0]" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf" class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-sky-50 file:text-sky-700 dark:file:bg-sky-900/30 dark:file:text-sky-400 hover:file:bg-sky-100 dark:hover:file:bg-sky-900/50" /></div>
    </div><div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700"><button type="button" @click="showCreateModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button><button data-testid="btn-simpan" type="submit" :disabled="createForm.processing" class="px-6 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm disabled:opacity-50"><i class="fas fa-save mr-1.5"></i>Simpan</button></div></form></div></Transition></Teleport>

    <!-- Edit Modal -->
    <Teleport to="body"><Transition name="modal"><div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showEditModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><form data-testid="modal-edit" @submit.prevent="submitEdit" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col"><div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-edit text-sky-500 mr-2"></i>Edit Tiket</h3><button type="button" @click="showEditModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button></div><div class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll">
      <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Penanggung Jawab</label><select data-testid="select-assigned" v-model="editForm.assigned_to_employee_id" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none"><option value="">— Belum di-assign —</option><option v-for="e in employees" :key="e.id" :value="e.id">{{ e.name }}</option></select></div>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Pengerjaan</label><select data-testid="select-status-pengerjaan" v-model="editForm.status_pengerjaan" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none"><option v-for="s in statusPengerjaanOptions" :key="s" :value="s">{{ s }}</option></select></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Verifikasi</label><select data-testid="select-status-verifikasi" v-model="editForm.status_verifikasi" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none"><option v-for="s in statusVerifikasiOptions" :key="s" :value="s">{{ s }}</option></select></div>
      </div>
      <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Catatan</label><textarea data-testid="textarea-catatan" v-model="editForm.catatan" rows="3" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none resize-none"></textarea></div>
      <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Alasan Verifikasi</label><textarea data-testid="textarea-alasan" v-model="editForm.alasan_verifikasi" rows="2" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none resize-none"></textarea></div>
      <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Bukti Issue (kosongkan jika tidak diubah)</label><input data-testid="input-file-bukti" @change="e => editFormFileIssue = e.target.files[0]" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf" class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-sky-50 file:text-sky-700 dark:file:bg-sky-900/30 dark:file:text-sky-400 hover:file:bg-sky-100 dark:hover:file:bg-sky-900/50" /></div>
      <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Bukti Selesai (kosongkan jika tidak diubah)</label><input data-testid="input-file-bukti-selesai" @change="e => editFormFileSelesai = e.target.files[0]" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf" class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-sky-50 file:text-sky-700 dark:file:bg-sky-900/30 dark:file:text-sky-400 hover:file:bg-sky-100 dark:hover:file:bg-sky-900/50" /></div>
    </div><div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700"><button type="button" @click="showEditModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button><button data-testid="btn-update" type="submit" :disabled="editForm.processing" class="px-6 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm disabled:opacity-50"><i class="fas fa-check mr-1.5"></i>Update</button></div></form></div></Transition></Teleport>

    <!-- Verify Modal -->
    <Teleport to="body"><Transition name="modal"><div v-if="showVerifyModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showVerifyModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><form data-testid="modal-verify" @submit.prevent="submitVerify" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] flex flex-col"><div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-clipboard-check text-emerald-500 mr-2"></i>Verifikasi Hasil</h3><button type="button" @click="showVerifyModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button></div><div class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll">
      <p class="text-sm text-gray-600 dark:text-gray-400">Verifikasi tiket <strong class="text-gray-900 dark:text-white">{{ selectedItem?.code }}</strong>:</p>
      <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Status Verifikasi <span class="text-red-500">*</span></label><select data-testid="select-verify-status" v-model="verifyForm.status_verifikasi" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 outline-none"><option value="">— Pilih —</option><option value="approved">Disetujui</option><option value="rejected">Ditolak</option></select></div>
      <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Alasan <span class="text-red-500">*</span></label><textarea data-testid="textarea-alasan" v-model="verifyForm.alasan_verifikasi" rows="3" placeholder="Jelaskan alasan persetujuan/penolakan..." class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 outline-none resize-none"></textarea></div>
    </div><div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700"><button type="button" @click="showVerifyModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button><button data-testid="btn-confirm-verify" type="submit" :disabled="verifyForm.processing" class="px-6 py-2.5 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors shadow-sm disabled:opacity-50"><i class="fas fa-check mr-1.5"></i>Verifikasi</button></div></form></div></Transition></Teleport>

    <!-- Detail Modal -->
    <Teleport to="body"><Transition name="modal"><div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showDetailModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col"><div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white">Detail Tiket</h3><button @click="showDetailModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700"><i class="fas fa-times"></i></button></div><div class="overflow-y-auto flex-1 px-6 py-5 modal-scroll" v-if="selectedItem">
      <div class="grid grid-cols-2 gap-4">
        <div><label class="text-xs text-gray-500 dark:text-gray-400">Kode</label><p class="font-mono font-medium text-gray-900 dark:text-white">{{ selectedItem.code }}</p></div>
        <div><label class="text-xs text-gray-500 dark:text-gray-400">Customer</label><p class="text-sm text-gray-900 dark:text-white">{{ selectedItem.customer_name }}<br><span class="text-xs text-gray-500">{{ selectedItem.cust_internet_label }}</span></p></div>
        <div><label class="text-xs text-gray-500 dark:text-gray-400">Penanggung Jawab</label><p class="text-sm text-gray-900 dark:text-white">{{ selectedItem.assigned_to_name || '—' }}</p></div>
        <div><label class="text-xs text-gray-500 dark:text-gray-400">Pengerjaan</label><p><span :class="['inline-flex px-2 py-0.5 rounded text-xs font-medium', statusPengerjaanBadge(selectedItem.status_pengerjaan)]">{{ selectedItem.status_pengerjaan_label }}</span></p></div>
        <div><label class="text-xs text-gray-500 dark:text-gray-400">Verifikasi</label><p><span :class="['inline-flex px-2 py-0.5 rounded text-xs font-medium', statusVerifikasiBadge(selectedItem.status_verifikasi)]">{{ selectedItem.status_verifikasi_label }}</span></p></div>
        <div><label class="text-xs text-gray-500 dark:text-gray-400">Tgl Mulai</label><p class="text-sm text-gray-900 dark:text-white">{{ formatDate(selectedItem.issue_dimulai_dari) }}</p></div>
        <div><label class="text-xs text-gray-500 dark:text-gray-400">Tgl Selesai</label><p class="text-sm text-gray-900 dark:text-white">{{ formatDate(selectedItem.issue_diselesaikan_pada) }}</p></div>
        <div class="col-span-2"><label class="text-xs text-gray-500 dark:text-gray-400">Catatan</label><p class="text-sm text-gray-900 dark:text-white whitespace-pre-wrap">{{ selectedItem.catatan }}</p></div>
        <div v-if="selectedItem.alasan_verifikasi" class="col-span-2"><label class="text-xs text-gray-500 dark:text-gray-400">Alasan Verifikasi</label><p class="text-sm text-gray-900 dark:text-white whitespace-pre-wrap">{{ selectedItem.alasan_verifikasi }}</p></div>
        <div v-if="selectedItem.file_bukti_issue_url"><label class="text-xs text-gray-500 dark:text-gray-400">Bukti Issue</label><a :href="selectedItem.file_bukti_issue_url" target="_blank" class="inline-flex items-center mt-1 px-3 py-1.5 bg-sky-600 text-white text-xs rounded-lg hover:bg-sky-700"><i class="fas fa-image mr-1"></i>Lihat</a></div>
        <div v-if="selectedItem.file_bukti_issue_diselesaikan_url"><label class="text-xs text-gray-500 dark:text-gray-400">Bukti Selesai</label><a :href="selectedItem.file_bukti_issue_diselesaikan_url" target="_blank" class="inline-flex items-center mt-1 px-3 py-1.5 bg-sky-600 text-white text-xs rounded-lg hover:bg-sky-700"><i class="fas fa-image mr-1"></i>Lihat</a></div>
      </div>
    </div></div></div></Transition></Teleport>

    <!-- Delete Modal -->
    <Teleport to="body"><Transition name="modal"><div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showDeleteModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-sm"><div class="px-6 py-5 text-center"><div class="w-14 h-14 mx-auto rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-4"><i class="fas fa-exclamation-triangle text-red-500 text-xl"></i></div><h3 class="text-lg font-semibold mb-2 text-gray-900 dark:text-white">Hapus Tiket?</h3><p class="text-sm text-gray-600 dark:text-gray-400">Hapus tiket <strong class="text-gray-900 dark:text-white">{{ selectedItem?.code }}</strong>?</p></div><div class="flex justify-center gap-3 px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700"><button @click="showDeleteModal = false" class="px-5 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">Batal</button><button data-testid="btn-confirm-delete" @click="confirmDelete" class="px-5 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700"><i class="fas fa-trash-alt mr-1.5"></i> Hapus</button></div></div></div></Transition></Teleport>

    <!-- Import Modal -->
    <Teleport to="body"><Transition name="modal"><div v-if="showImportModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showImportModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><form data-testid="modal-import" @submit.prevent="submitImport" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] flex flex-col"><div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-file-upload text-emerald-500 mr-2"></i>Import Tiket dari Excel</h3><button type="button" @click="showImportModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button></div><div class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll">
      <div class="bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-800 rounded-lg px-4 py-3 text-sm text-sky-700 dark:text-sky-400"><i class="fas fa-info-circle mr-1.5"></i>Kolom wajib: <strong>Kode Langganan (Account Number)</strong> dan <strong>Catatan</strong>. Opsional: <strong>Penanggung Jawab (Nama Karyawan)</strong>.</div>
      <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">File Excel (.xlsx, maks 10MB) <span class="text-red-500">*</span></label><input data-testid="input-import-file" @change="onImportFileChange" type="file" accept=".xlsx,.xls" class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-emerald-50 file:text-emerald-700 dark:file:bg-emerald-900/30 dark:file:text-emerald-400 hover:file:bg-emerald-100 dark:hover:file:bg-emerald-900/50" /><p v-if="importFile" class="text-xs text-gray-500 mt-1">{{ importFile.name }} ({{ Math.round(importFile.size / 1024) }} KB)</p></div>
    </div><div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700"><button type="button" @click="showImportModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button><button data-testid="btn-confirm-import" type="submit" :disabled="importing" class="px-6 py-2.5 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors shadow-sm disabled:opacity-50"><i class="fas fa-upload mr-1.5"></i>{{ importing ? 'Importing...' : 'Import' }}</button></div></form></div></Transition></Teleport>
  </div>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-active > div:last-child, .modal-leave-active > div:last-child { transition: transform 0.2s ease, opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-from > div:last-child { transform: scale(0.95) translateY(10px); opacity: 0; }
.modal-leave-to > div:last-child { transform: scale(0.95) translateY(10px); opacity: 0; }
</style>
