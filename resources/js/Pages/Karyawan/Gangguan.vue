<script setup>
import { ref, computed, reactive } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import KaryawanLayout from '@/Layouts/KaryawanLayout.vue';
import { useToast } from '@/Composables/useToast';
import SearchableSelectAjax from '@/Components/SearchableSelectAjax.vue';
import ToastContainer from '@/Components/ToastContainer.vue';
import FormErrorSummary from '@/Components/FormErrorSummary.vue';
import { errorSummary } from '@/Composables/useFormErrorToast';

defineOptions({ layout: KaryawanLayout });

const props = defineProps({
  gangguans: Object,
  filters: Object,
  custInternets: { type: Array, default: () => [] },
  employees: { type: Array, default: () => [] },
  statusPengerjaanOptions: { type: Array, default: () => [] },
  statusVerifikasiOptions: { type: Array, default: () => [] },
  attachmentTypeOptions: { type: Array, default: () => [] },
});

const ATT_TYPE_BUKTI_ISSUE = 'bukti_issue';
const ATT_TYPE_BUKTI_ISSUE_SELESAI = 'bukti_issue_selesai';

const toast = useToast();
const searchInput = ref(props.filters?.search || '');
const statusPengerjaanFilter = ref(props.filters?.status_pengerjaan || '');
const statusVerifikasiFilter = ref(props.filters?.status_verifikasi || '');
const assignedFilter = ref(props.filters?.assigned_to_employee_id || '');
const sortField = ref(props.filters?.sort_field || 'created_at');
const sortDir = ref(props.filters?.sort_dir || 'desc');
const perPage = ref(props.filters?.per_page ? Number(props.filters.per_page) : 10);
const perPageOptions = [5, 10, 25, 50, 100];
const selectedIds = ref([]);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showResolveModal = ref(false);
const showDetailModal = ref(false);
const showDeleteModal = ref(false);
const selectedItem = ref(null);

const createErrors = ref({});
const editErrors = ref({});
const resolveErrors = ref({});

const createForm = useForm({
  cust_internet_id: '', main_pic_employee_id: '', additional_pic_employee_ids: [],
  catatan: '', issue_dimulai_dari: '', issue_diselesaikan_pada: '',
});
const editForm = useForm({
  main_pic_employee_id: '', additional_pic_employee_ids: [],
  catatan: '', status_pengerjaan: '', issue_dimulai_dari: '', issue_diselesaikan_pada: '',
});
const resolveForm = useForm({});
const additionalPics = ref([]);

/**
 * Attachment form state (parallel arrays — index-aligned):
 *   createAttachments[type].files[]     — array of File objects (Object URLs)
 *   createAttachments[type].names[]     — array of strings (user input)
 *   createAttachments[type].descs[]     — array of strings (user input, optional)
 *
 * Existing attachments (loaded from backend) stored separately for display + keepIds.
 */
const createAttachments = reactive({
  [ATT_TYPE_BUKTI_ISSUE]: { files: [], names: [], descs: [] },
});
const editAttachments = reactive({
  [ATT_TYPE_BUKTI_ISSUE]: { files: [], names: [], descs: [] },
  [ATT_TYPE_BUKTI_ISSUE_SELESAI]: { files: [], names: [], descs: [] },
});
const resolveAttachments = reactive({
  [ATT_TYPE_BUKTI_ISSUE_SELESAI]: { files: [], names: [], descs: [] },
});

// Existing attachments (per type, per modal)
const createExistingAttachments = ref({ [ATT_TYPE_BUKTI_ISSUE]: [] });
const editExistingAttachments = ref({ [ATT_TYPE_BUKTI_ISSUE]: [], [ATT_TYPE_BUKTI_ISSUE_SELESAI]: [] });
const resolveExistingAttachments = ref({ [ATT_TYPE_BUKTI_ISSUE_SELESAI]: [] });

function addAdditionalPic(employeeId, employeeName) {
  if (!employeeId) return;
  if (employeeId === createForm.main_pic_employee_id || employeeId === editForm.main_pic_employee_id) { toast.error('Sudah jadi PIC Utama.'); return; }
  if (additionalPics.value.find(p => p.employee_id === employeeId)) { toast.error('Sudah ditambahkan.'); return; }
  additionalPics.value.push({ employee_id: employeeId, employee_name: employeeName });
}
function removeAdditionalPic(employeeId) {
  additionalPics.value = additionalPics.value.filter(p => p.employee_id !== employeeId);
}

/**
 * Tambah file baru ke state untuk modal create/edit/resolve.
 * typeKey: 'bukti_issue' | 'bukti_issue_selesai'
 */
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

async function deleteAttachment(modalState, typeKey, attachmentId, gangguanId) {
  if (!confirm('Hapus attachment ini?')) return;
  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const resp = await fetch(`/karyawan/gangguan/${gangguanId}/attachments/${attachmentId}`, {
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    });
    const data = await resp.json().catch(() => ({}));
    if (resp.ok) {
      // Refresh list di modal state
      const list = modalState.value?.[typeKey] || [];
      modalState.value[typeKey] = list.filter(a => a.id !== attachmentId);
      toast.success('Attachment dihapus.');
    } else { toast.error(data.message || `Gagal (HTTP ${resp.status})`); }
  } catch (e) { toast.error('Error: ' + e.message); }
}

function buildQuery(o = {}) {
  const p = { ...o };
  if (p.search === undefined) p.search = searchInput.value || undefined;
  if (p.status_pengerjaan === undefined) p.status_pengerjaan = statusPengerjaanFilter.value || undefined;
  if (p.status_verifikasi === undefined) p.status_verifikasi = statusVerifikasiFilter.value || undefined;
  if (p.assigned_to_employee_id === undefined) p.assigned_to_employee_id = assignedFilter.value || undefined;
  if (p.sort_field === undefined) p.sort_field = sortField.value || undefined;
  if (p.sort_dir === undefined) p.sort_dir = sortDir.value || undefined;
  if (p.per_page === undefined) p.per_page = perPage.value;
  Object.keys(p).forEach(k => { if (p[k] === undefined) delete p[k]; });
  return p;
}
function fetchData(o = {}) { router.get('/karyawan/gangguan', buildQuery(o), { preserveState: true, preserveScroll: true, replace: true }); }
function applySearch() { fetchData({ page: 1 }); }
function clearSearch() { searchInput.value = ''; fetchData({ page: 1 }); }
function applyFilters() { fetchData({ page: 1 }); }
function resetFilters() {
  searchInput.value = ''; statusPengerjaanFilter.value = ''; statusVerifikasiFilter.value = ''; assignedFilter.value = '';
  fetchData({ search: undefined, status_pengerjaan: undefined, status_verifikasi: undefined, assigned_to_employee_id: undefined, page: 1 });
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
function fileExt(name) { if (!name) return ''; const i = name.lastIndexOf('.'); return i >= 0 ? name.substring(i).toLowerCase() : ''; }
function isImage(ext) { return ['.jpg', '.jpeg', '.png', '.webp'].includes(ext); }

const items = computed(() => props.gangguans?.data || []);
const pagination = computed(() => ({ current: props.gangguans?.current_page || 1, last: props.gangguans?.last_page || 1, total: props.gangguans?.total || 0 }));
const hasSelected = computed(() => selectedIds.value.length > 0);
const isAllSelected = computed({
  get: () => items.value.length > 0 && items.value.every(i => selectedIds.value.includes(i.id)),
  set: (val) => { selectedIds.value = val ? items.value.map(i => i.id) : []; },
});
function toggleSelect(id) { const i = selectedIds.value.indexOf(id); i === -1 ? selectedIds.value.push(id) : selectedIds.value.splice(i, 1); }
function clearSelected() { selectedIds.value = []; }
async function bulkDelete() {
  if (selectedIds.value.length === 0) { toast.error('Pilih data terlebih dahulu.'); return; }
  if (!confirm(`Hapus ${selectedIds.value.length} tiket?`)) return;
  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const resp = await fetch('/karyawan/gangguan/bulk-delete', { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify({ ids: selectedIds.value }) });
    const data = await resp.json().catch(() => ({}));
    if (resp.ok) { selectedIds.value = []; toast.success(data.message || `${selectedIds.value.length} tiket dihapus`); fetchData(); }
    else { toast.error(data.message || `Gagal (HTTP ${resp.status})`); }
  } catch (e) { toast.error('Error: ' + e.message); }
}
async function bulkRestore() {
  if (selectedIds.value.length === 0) { toast.error('Pilih data terlebih dahulu.'); return; }
  if (!confirm(`Pulihkan ${selectedIds.value.length} tiket?`)) return;
  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const resp = await fetch('/karyawan/gangguan/bulk-restore', { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify({ ids: selectedIds.value }) });
    const data = await resp.json().catch(() => ({}));
    if (resp.ok) { selectedIds.value = []; toast.success(data.message || `${selectedIds.value.length} tiket dipulihkan`); fetchData(); }
    else { toast.error(data.message || `Gagal (HTTP ${resp.status})`); }
  } catch (e) { toast.error('Error: ' + e.message); }
}

function resetAttachmentState() {
  Object.keys(createAttachments).forEach(k => {
    createAttachments[k].files = []; createAttachments[k].names = []; createAttachments[k].descs = [];
  });
  Object.keys(editAttachments).forEach(k => {
    editAttachments[k].files = []; editAttachments[k].names = []; editAttachments[k].descs = [];
  });
  Object.keys(resolveAttachments).forEach(k => {
    resolveAttachments[k].files = []; resolveAttachments[k].names = []; resolveAttachments[k].descs = [];
  });
}

function openCreate() {
  createForm.reset(); createForm.clearErrors();
  createErrors.value = {};
  additionalPics.value = [];
  resetAttachmentState();
  createExistingAttachments.value = { [ATT_TYPE_BUKTI_ISSUE]: [] };
  showCreateModal.value = true;
}

async function submitCreate() {
  const fd = new FormData();
  fd.append('cust_internet_id', createForm.cust_internet_id);
  fd.append('main_pic_employee_id', createForm.main_pic_employee_id || '');
  additionalPics.value.forEach(p => fd.append('additional_pic_employee_ids[]', p.employee_id));
  fd.append('catatan', createForm.catatan);
  fd.append('issue_dimulai_dari', createForm.issue_dimulai_dari);
  if (createForm.issue_diselesaikan_pada) fd.append('issue_diselesaikan_pada', createForm.issue_diselesaikan_pada);

  // Parallel arrays untuk attachments (BuktiIssue)
  createAttachments[ATT_TYPE_BUKTI_ISSUE].files.forEach(f => fd.append('attachments_bukti_issue[]', f));
  createAttachments[ATT_TYPE_BUKTI_ISSUE].names.forEach(n => fd.append('attachment_names[]', n));
  createAttachments[ATT_TYPE_BUKTI_ISSUE].descs.forEach(d => fd.append('attachment_descriptions[]', d));

  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const resp = await fetch('/karyawan/gangguan', { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body: fd });
    const data = await resp.json().catch(() => ({}));
    if (resp.ok) {
      showCreateModal.value = false; additionalPics.value = []; resetAttachmentState();
      toast.success('Tiket berhasil dibuat.'); fetchData();
    } else if (data.errors) {
      createErrors.value = data.errors;
      toast.error('Validasi gagal: ' + errorSummary(data.errors), 6000);
    } else { toast.error(data.message || `Gagal (HTTP ${resp.status})`); }
  } catch (e) { toast.error('Error: ' + e.message); }
}

function openEdit(item) {
  editForm.reset(); editForm.clearErrors();
  editErrors.value = {};
  editForm.main_pic_employee_id = item.assigned_to_employee_id || '';
  editForm.catatan = item.catatan || '';
  editForm.status_pengerjaan = item.status_pengerjaan || '';
  editForm.issue_dimulai_dari = item.issue_dimulai_dari ? item.issue_dimulai_dari.substring(0, 16) : '';
  editForm.issue_diselesaikan_pada = item.issue_diselesaikan_pada ? item.issue_diselesaikan_pada.substring(0, 16) : '';
  additionalPics.value = (item.additional_pics || []).map(p => ({ employee_id: p.employee_id, employee_name: p.employee_name }));
  resetAttachmentState();
  editExistingAttachments.value = {
    [ATT_TYPE_BUKTI_ISSUE]: item.attachments?.[ATT_TYPE_BUKTI_ISSUE] || [],
    [ATT_TYPE_BUKTI_ISSUE_SELESAI]: item.attachments?.[ATT_TYPE_BUKTI_ISSUE_SELESAI] || [],
  };
  selectedItem.value = item;
  showEditModal.value = true;
}

async function submitEdit() {
  const fd = new FormData();
  fd.append('_method', 'PUT');
  fd.append('main_pic_employee_id', editForm.main_pic_employee_id || '');
  additionalPics.value.forEach(p => fd.append('additional_pic_employee_ids[]', p.employee_id));
  fd.append('catatan', editForm.catatan || '');
  fd.append('status_pengerjaan', editForm.status_pengerjaan || '');
  if (editForm.issue_dimulai_dari) fd.append('issue_dimulai_dari', editForm.issue_dimulai_dari);
  if (editForm.issue_diselesaikan_pada) fd.append('issue_diselesaikan_pada', editForm.issue_diselesaikan_pada);

  // attachments_to_keep = existing IDs yg mau dipertahankan
  const keep = [];
  editExistingAttachments.value[ATT_TYPE_BUKTI_ISSUE].forEach(a => keep.push(a.id));
  editExistingAttachments.value[ATT_TYPE_BUKTI_ISSUE_SELESAI].forEach(a => keep.push(a.id));
  keep.forEach(id => fd.append('attachments_to_keep[]', id));

  // New uploads — parallel arrays
  editAttachments[ATT_TYPE_BUKTI_ISSUE].files.forEach(f => fd.append('attachments_bukti_issue[]', f));
  editAttachments[ATT_TYPE_BUKTI_ISSUE_SELESAI].files.forEach(f => fd.append('attachments_bukti_issue_selesai[]', f));
  const allNames = [...editAttachments[ATT_TYPE_BUKTI_ISSUE].names, ...editAttachments[ATT_TYPE_BUKTI_ISSUE_SELESAI].names];
  const allDescs = [...editAttachments[ATT_TYPE_BUKTI_ISSUE].descs, ...editAttachments[ATT_TYPE_BUKTI_ISSUE_SELESAI].descs];
  allNames.forEach(n => fd.append('attachment_names[]', n));
  allDescs.forEach(d => fd.append('attachment_descriptions[]', d));

  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const resp = await fetch(`/karyawan/gangguan/${selectedItem.value.id}`, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body: fd });
    const data = await resp.json().catch(() => ({}));
    if (resp.ok) {
      showEditModal.value = false; resetAttachmentState();
      toast.success('Tiket berhasil diperbarui.'); fetchData();
    } else if (data.errors) {
      editErrors.value = data.errors;
      toast.error('Validasi gagal: ' + errorSummary(data.errors), 6000);
    } else { toast.error(data.message || `Gagal (HTTP ${resp.status})`); }
  } catch (e) { toast.error('Error: ' + e.message); }
}

function openResolve(item) {
  resolveForm.reset(); resolveForm.clearErrors();
  resolveErrors.value = {};
  resetAttachmentState();
  resolveExistingAttachments.value = {
    [ATT_TYPE_BUKTI_ISSUE_SELESAI]: item.attachments?.[ATT_TYPE_BUKTI_ISSUE_SELESAI] || [],
  };
  selectedItem.value = item;
  showResolveModal.value = true;
}

async function submitResolve() {
  const fd = new FormData();
  const keep = [];
  resolveExistingAttachments.value[ATT_TYPE_BUKTI_ISSUE_SELESAI].forEach(a => keep.push(a.id));
  keep.forEach(id => fd.append('attachments_to_keep[]', id));
  resolveAttachments[ATT_TYPE_BUKTI_ISSUE_SELESAI].files.forEach(f => fd.append('attachments_bukti_issue_selesai[]', f));
  resolveAttachments[ATT_TYPE_BUKTI_ISSUE_SELESAI].names.forEach(n => fd.append('attachment_names[]', n));
  resolveAttachments[ATT_TYPE_BUKTI_ISSUE_SELESAI].descs.forEach(d => fd.append('attachment_descriptions[]', d));

  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const resp = await fetch(`/karyawan/gangguan/${selectedItem.value.id}/resolve`, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body: fd });
    const data = await resp.json().catch(() => ({}));
    if (resp.ok) {
      showResolveModal.value = false; resetAttachmentState();
      toast.success('Tiket ditandai selesai. Menunggu verifikasi admin.'); fetchData();
    } else if (data.errors) {
      resolveErrors.value = data.errors;
      toast.error('Validasi gagal: ' + errorSummary(data.errors), 6000);
    } else { toast.error(data.message || `Gagal (HTTP ${resp.status})`); }
  } catch (e) { toast.error('Error: ' + e.message); }
}

function openDetail(item) { selectedItem.value = item; showDetailModal.value = true; }
function openDelete(item) { selectedItem.value = item; showDeleteModal.value = true; }
function confirmDelete() { router.delete(`/karyawan/gangguan/${selectedItem.value.id}`, { onSuccess: () => { showDeleteModal.value = false; fetchData(); toast.success('Tiket berhasil dihapus.'); } }); }
</script>

<template>
  <Head title="Tiket Gangguan" />
  <ToastContainer />

  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Tiket Gangguan</h1>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Kelola tiket gangguan internet dari customer.</p>
      </div>
      <button data-testid="btn-buat-tiket" @click="openCreate" class="inline-flex items-center px-4 py-2.5 bg-amber-600 text-white text-sm font-medium rounded-lg hover:bg-amber-700 transition-colors shadow-sm">
        <i class="fas fa-plus mr-1.5"></i> Buat Tiket
      </button>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
      <div class="flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-[200px]">
          <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Cari</label>
          <input v-model="searchInput" @keyup.enter="applySearch" type="text" placeholder="Cari kode / catatan / customer..." class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-amber-500 outline-none" />
        </div>
        <div class="min-w-[160px]">
          <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Pengerjaan</label>
          <select v-model="statusPengerjaanFilter" @change="applyFilters" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-amber-500 outline-none">
            <option value="">Semua</option>
            <option v-for="s in statusPengerjaanOptions" :key="s" :value="s">{{ s }}</option>
          </select>
        </div>
        <div class="min-w-[160px]">
          <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Verifikasi</label>
          <select v-model="statusVerifikasiFilter" @change="applyFilters" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-amber-500 outline-none">
            <option value="">Semua</option>
            <option v-for="s in statusVerifikasiOptions" :key="s" :value="s">{{ s }}</option>
          </select>
        </div>
        <div class="min-w-[160px]">
          <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">PIC Utama</label>
          <SearchableSelectAjax data-testid="filter-pic-utama" v-model="assignedFilter" url="/karyawan/api/search/employees" placeholder="— Semua —" display-key="name" @update:modelValue="applyFilters" />
        </div>
        <button @click="applyFilters" class="px-4 py-2 bg-amber-600 text-white text-sm rounded-lg hover:bg-amber-700"><i class="fas fa-filter mr-1"></i>Filter</button>
        <button @click="resetFilters" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">Reset</button>
      </div>
    </div>

    <!-- Bulk Action Bar -->
    <div v-if="hasSelected" class="flex items-center justify-between px-4 py-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl shadow-sm">
      <span class="text-sm font-medium text-amber-700 dark:text-amber-300"><i class="fas fa-check-circle mr-1.5"></i> {{ selectedIds.length }} tiket dipilih</span>
      <div class="flex items-center gap-2">
        <button data-testid="btn-bulk-delete" @click="bulkDelete" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-red-600 text-white hover:bg-red-700"><i class="fas fa-trash-alt mr-1"></i>Hapus</button>
        <button data-testid="btn-bulk-clear" @click="clearSelected" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-600">Batal Pilih</button>
      </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
            <tr>
              <th class="px-3 py-3 w-10"><input data-testid="checkbox-select-all" type="checkbox" v-model="isAllSelected" class="rounded border-gray-300 text-amber-600 focus:ring-amber-500" /></th>
              <th class="px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 text-xs">Kode</th>
              <th class="px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 text-xs">Customer</th>
              <th class="px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 text-xs">PIC Utama</th>
              <th class="px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 text-xs">Lampiran</th>
              <th class="px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 text-xs cursor-pointer" @click="sort('status_pengerjaan')">Pengerjaan <i :class="['fas', sortIcon('status_pengerjaan')]"></i></th>
              <th class="px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 text-xs cursor-pointer" @click="sort('status_verifikasi')">Verifikasi <i :class="['fas', sortIcon('status_verifikasi')]"></i></th>
              <th class="px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 text-xs cursor-pointer" @click="sort('issue_dimulai_dari')">Tgl Mulai <i :class="['fas', sortIcon('issue_dimulai_dari')]"></i></th>
              <th class="px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 text-xs cursor-pointer" @click="sort('issue_diselesaikan_pada')">Tgl Selesai <i :class="['fas', sortIcon('issue_diselesaikan_pada')]"></i></th>
              <th class="px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 text-xs">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in items" :key="item.id" :class="['border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-900/30', selectedIds.includes(item.id) ? 'bg-amber-50/50 dark:bg-amber-900/10' : '']">
              <td class="px-3 py-3"><input data-testid="checkbox-row" type="checkbox" :checked="selectedIds.includes(item.id)" @change="toggleSelect(item.id)" class="rounded border-gray-300 text-amber-600 focus:ring-amber-500" /></td>
              <td class="px-3 py-3 font-mono text-xs text-gray-900 dark:text-white">{{ item.code }}</td>
              <td class="px-3 py-3 text-xs text-gray-600 dark:text-gray-400">{{ item.customer_name }}<br><span class="text-gray-400 text-[10px]">{{ item.cust_internet_label }}</span></td>
              <td class="px-3 py-3 text-xs text-gray-600 dark:text-gray-400">{{ item.main_pic_name || '—' }}</td>
              <td class="px-3 py-3 text-xs">
                <span v-if="(item.attachment_count || 0) > 0" class="inline-flex items-center px-2 py-0.5 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded text-xs font-medium"><i class="fas fa-paperclip mr-1 text-[10px]"></i>{{ item.attachment_count }}</span>
                <span v-else class="text-gray-400">—</span>
              </td>
              <td class="px-3 py-3"><span :class="['inline-flex px-2 py-0.5 rounded text-xs font-medium', statusPengerjaanBadge(item.status_pengerjaan)]">{{ item.status_pengerjaan_label }}</span></td>
              <td class="px-3 py-3"><span :class="['inline-flex px-2 py-0.5 rounded text-xs font-medium', statusVerifikasiBadge(item.status_verifikasi)]">{{ item.status_verifikasi_label }}</span></td>
              <td class="px-3 py-3 text-xs text-gray-500 dark:text-gray-400">{{ formatDate(item.issue_dimulai_dari) }}</td>
              <td class="px-3 py-3 text-xs text-gray-500 dark:text-gray-400">{{ formatDate(item.issue_diselesaikan_pada) }}</td>
              <td class="px-3 py-3">
                <div class="flex items-center gap-1">
                  <button data-testid="btn-detail" @click="openDetail(item)" title="Detail" class="p-1.5 rounded-lg text-gray-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:text-amber-400 dark:hover:bg-amber-900/30"><i class="fas fa-eye"></i></button>
                  <button data-testid="btn-edit" @click="openEdit(item)" title="Edit" class="p-1.5 rounded-lg text-gray-400 hover:text-sky-600 hover:bg-sky-50 dark:hover:text-sky-400 dark:hover:bg-sky-900/30"><i class="fas fa-edit"></i></button>
                  <button v-if="item.status_pengerjaan !== 'resolved'" data-testid="btn-resolve" @click="openResolve(item)" title="Tandai Selesai" class="p-1.5 rounded-lg text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:text-emerald-400 dark:hover:bg-emerald-900/30"><i class="fas fa-check-circle"></i></button>
                  <button v-if="!item.deleted_at" data-testid="btn-delete" @click="openDelete(item)" title="Hapus" class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:text-red-400 dark:hover:bg-red-900/30"><i class="fas fa-trash-alt"></i></button>
                </div>
              </td>
            </tr>
            <tr v-if="items.length === 0"><td colspan="9" class="px-3 py-12 text-center text-gray-500 dark:text-gray-400">Belum ada tiket gangguan.</td></tr>
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
    <Teleport to="body"><Transition name="modal"><div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showCreateModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><form data-testid="modal-create" @submit.prevent="submitCreate" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col"><div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-plus text-emerald-500 mr-2"></i>Buat Tiket Gangguan</h3><button type="button" @click="showCreateModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button></div><div class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll">
      <FormErrorSummary :errors="createErrors" testId="create-error-summary" />
      <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Kode Langganan <span class="text-red-500">*</span></label><span data-testid="btn-select-cust-internet"><SearchableSelectAjax data-testid="select-cust-internet" v-model="createForm.cust_internet_id" url="/karyawan/api/search/langganans" placeholder="— Pilih Kode Langganan —" display-key="label" :error="!!createErrors.cust_internet_id" /></span><p v-if="createErrors.cust_internet_id" class="text-red-500 text-xs mt-1">{{ createErrors.cust_internet_id }}</p></div>
      <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Tgl Mulai Gangguan <span class="text-red-500">*</span></label><input data-testid="input-issue-dimulai" v-model="createForm.issue_dimulai_dari" type="datetime-local" :class="['w-full px-3 py-2.5 rounded-lg border bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm outline-none', createErrors.issue_dimulai_dari ? 'border-red-400 focus:ring-2 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-amber-500']" /><p v-if="createErrors.issue_dimulai_dari" class="text-red-500 text-xs mt-1">{{ createErrors.issue_dimulai_dari }}</p></div>
      <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Tgl Selesai Gangguan <span class="text-xs text-gray-400">(opsional, jika sudah fix)</span></label><input data-testid="input-issue-diselesaikan" v-model="createForm.issue_diselesaikan_pada" type="datetime-local" :class="['w-full px-3 py-2.5 rounded-lg border bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm outline-none', createErrors.issue_diselesaikan_pada ? 'border-red-400 focus:ring-2 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-amber-500']" /><p v-if="createErrors.issue_diselesaikan_pada" class="text-red-500 text-xs mt-1">{{ createErrors.issue_diselesaikan_pada }}</p></div>
      <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">PIC Utama <span class="text-xs text-gray-400">(opsional)</span></label><span data-testid="btn-select-main-pic"><SearchableSelectAjax data-testid="select-main-pic" v-model="createForm.main_pic_employee_id" url="/karyawan/api/search/employees" placeholder="— Pilih PIC Utama —" display-key="name" :error="!!createErrors.main_pic_employee_id" /></span><p v-if="createErrors.main_pic_employee_id" class="text-red-500 text-xs mt-1">{{ createErrors.main_pic_employee_id }}</p></div>
      <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">PIC Tambahan <span class="text-xs text-gray-400">(opsional, bisa lebih dari 1)</span></label>
        <div v-if="additionalPics.length > 0" class="flex flex-wrap items-center gap-2 mb-2">
          <span v-for="pic in additionalPics" :key="pic.employee_id" data-testid="chip-additional-pic" class="inline-flex items-center px-3 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded-full text-xs font-medium">{{ pic.employee_name }}<button type="button" data-testid="btn-remove-pic" @click="removeAdditionalPic(pic.employee_id)" class="ml-1.5 hover:text-red-600"><i class="fas fa-times"></i></button></span>
        </div>
        <SearchableSelectAjax data-testid="select-additional-pic" url="/karyawan/api/search/employees" placeholder="— Tambah PIC Tambahan —" display-key="name" :error="!!createErrors.additional_pic_employee_ids" @update:modelValue="(v) => { if (v) { const emp = employees.find(e => e.id === v); if (emp) addAdditionalPic(emp.id, emp.name); }}" />
        <p v-if="createErrors.additional_pic_employee_ids" class="text-red-500 text-xs mt-1">{{ createErrors.additional_pic_employee_ids }}</p>
      </div>
      <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Catatan <span class="text-red-500">*</span></label><textarea data-testid="textarea-catatan" v-model="createForm.catatan" rows="4" placeholder="Jelaskan masalah..." :class="['w-full px-3 py-2.5 rounded-lg border bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm outline-none resize-none', createErrors.catatan ? 'border-red-400 focus:ring-2 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-amber-500']"></textarea><p v-if="createErrors.catatan" class="text-red-500 text-xs mt-1">{{ createErrors.catatan }}</p></div>

      <!-- Bukti Issue Attachments (multi-file, parallel arrays) -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Bukti Issue <span class="text-xs text-gray-400">(opsional, bisa lebih dari 1)</span></label>
        <div v-if="createAttachments[ATT_TYPE_BUKTI_ISSUE].files.length > 0" class="space-y-2 mb-2">
          <div v-for="(f, i) in createAttachments[ATT_TYPE_BUKTI_ISSUE].files" :key="i" data-testid="create-attachment-row" class="p-2.5 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg space-y-1.5">
            <div class="flex items-center justify-between">
              <span class="text-xs font-medium text-amber-700 dark:text-amber-300"><i class="fas fa-paperclip mr-1"></i>{{ f.name }} ({{ Math.round(f.size/1024) }} KB)</span>
              <button type="button" data-testid="btn-remove-attachment" @click="removeAttachmentFile(ATT_TYPE_BUKTI_ISSUE, createAttachments, i)" class="text-xs text-red-600 hover:text-red-700"><i class="fas fa-times"></i></button>
            </div>
            <input v-model="createAttachments[ATT_TYPE_BUKTI_ISSUE].names[i]" placeholder="Nama / label file (opsional)" class="w-full px-2 py-1 text-xs border border-amber-300 dark:border-amber-700 rounded bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-1 focus:ring-amber-500 outline-none" />
            <input v-model="createAttachments[ATT_TYPE_BUKTI_ISSUE].descs[i]" placeholder="Keterangan (opsional)" class="w-full px-2 py-1 text-xs border border-amber-300 dark:border-amber-700 rounded bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-1 focus:ring-amber-500 outline-none" />
          </div>
        </div>
        <input data-testid="input-file-bukti-issue" @change="e => { for (const f of e.target.files) addAttachmentFile(ATT_TYPE_BUKTI_ISSUE, createAttachments, f); e.target.value = ''; }" type="file" multiple accept=".jpg,.jpeg,.png,.webp,.pdf" class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-amber-50 file:text-amber-700 dark:file:bg-amber-900/30 dark:file:text-amber-400 hover:file:bg-amber-100 dark:hover:file:bg-amber-900/50" />
        <p v-if="createErrors.attachments_bukti_issue" class="text-red-500 text-xs mt-1">{{ createErrors.attachments_bukti_issue }}</p>
      </div>
    </div><div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700"><button type="button" @click="showCreateModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button><button data-testid="btn-simpan" type="submit" :disabled="createForm.processing" class="px-6 py-2.5 bg-amber-600 text-white text-sm font-medium rounded-lg hover:bg-amber-700 transition-colors shadow-sm disabled:opacity-50"><i class="fas fa-save mr-1.5"></i>Simpan</button></div></form></div></Transition></Teleport>

    <!-- Edit Modal -->
    <Teleport to="body"><Transition name="modal"><div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showEditModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><form data-testid="modal-edit" @submit.prevent="submitEdit" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col"><div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-edit text-amber-500 mr-2"></i>Edit Tiket</h3><button type="button" @click="showEditModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button></div><div class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll">
      <FormErrorSummary :errors="editErrors" testId="edit-error-summary" />
      <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">PIC Utama</label><span data-testid="btn-select-main-pic"><SearchableSelectAjax data-testid="select-main-pic" v-model="editForm.main_pic_employee_id" url="/karyawan/api/search/employees" placeholder="— Pilih PIC Utama —" display-key="name" :error="!!editErrors.main_pic_employee_id" /></span><p v-if="editErrors.main_pic_employee_id" class="text-red-500 text-xs mt-1">{{ editErrors.main_pic_employee_id }}</p></div>
      <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">PIC Tambahan</label>
        <div v-if="additionalPics.length > 0" class="flex flex-wrap items-center gap-2 mb-2">
          <span v-for="pic in additionalPics" :key="pic.employee_id" data-testid="chip-additional-pic" class="inline-flex items-center px-3 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded-full text-xs font-medium">{{ pic.employee_name }}<button type="button" data-testid="btn-remove-pic" @click="removeAdditionalPic(pic.employee_id)" class="ml-1.5 hover:text-red-600"><i class="fas fa-times"></i></button></span>
        </div>
        <SearchableSelectAjax data-testid="select-additional-pic" url="/karyawan/api/search/employees" placeholder="— Tambah PIC Tambahan —" display-key="name" :error="!!editErrors.additional_pic_employee_ids" @update:modelValue="(v) => { if (v) { const emp = employees.find(e => e.id === v); if (emp) addAdditionalPic(emp.id, emp.name); }}" />
        <p v-if="editErrors.additional_pic_employee_ids" class="text-red-500 text-xs mt-1">{{ editErrors.additional_pic_employee_ids }}</p>
      </div>
      <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Tgl Mulai Gangguan</label><input data-testid="input-issue-dimulai" v-model="editForm.issue_dimulai_dari" type="datetime-local" :class="['w-full px-3 py-2.5 rounded-lg border bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm outline-none', editErrors.issue_dimulai_dari ? 'border-red-400 focus:ring-2 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-amber-500']" /><p v-if="editErrors.issue_dimulai_dari" class="text-red-500 text-xs mt-1">{{ editErrors.issue_dimulai_dari }}</p></div>
      <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Tgl Selesai Gangguan</label><input data-testid="input-issue-diselesaikan" v-model="editForm.issue_diselesaikan_pada" type="datetime-local" :class="['w-full px-3 py-2.5 rounded-lg border bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm outline-none', editErrors.issue_diselesaikan_pada ? 'border-red-400 focus:ring-2 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-amber-500']" /><p v-if="editErrors.issue_diselesaikan_pada" class="text-red-500 text-xs mt-1">{{ editErrors.issue_diselesaikan_pada }}</p></div>
      <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Status Pengerjaan</label><select data-testid="select-status-pengerjaan" v-model="editForm.status_pengerjaan" :class="['w-full px-3 py-2.5 rounded-lg border bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm outline-none', editErrors.status_pengerjaan ? 'border-red-400 focus:ring-2 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-amber-500']"><option v-for="s in statusPengerjaanOptions" :key="s" :value="s">{{ s }}</option></select><p v-if="editErrors.status_pengerjaan" class="text-red-500 text-xs mt-1">{{ editErrors.status_pengerjaan }}</p></div>
      <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Catatan</label><textarea data-testid="textarea-catatan" v-model="editForm.catatan" rows="3" :class="['w-full px-3 py-2.5 rounded-lg border bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm outline-none resize-none', editErrors.catatan ? 'border-red-400 focus:ring-2 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-amber-500']"></textarea><p v-if="editErrors.catatan" class="text-red-500 text-xs mt-1">{{ editErrors.catatan }}</p></div>

      <!-- Attachments: existing + new uploads -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Bukti Issue</label>
        <div v-if="editExistingAttachments[ATT_TYPE_BUKTI_ISSUE].length > 0" class="space-y-1.5 mb-2">
          <div v-for="att in editExistingAttachments[ATT_TYPE_BUKTI_ISSUE]" :key="att.id" data-testid="existing-attachment-row" class="flex items-center justify-between p-2 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded text-xs">
            <div class="flex items-center gap-2 truncate">
              <i class="fas fa-paperclip text-amber-500"></i>
              <a :href="att.url" target="_blank" class="text-amber-700 dark:text-amber-300 hover:underline truncate">{{ att.file_name }}</a>
              <span v-if="att.file_description" class="text-gray-500 truncate">— {{ att.file_description }}</span>
            </div>
            <button type="button" data-testid="btn-delete-attachment" @click="deleteAttachment(editExistingAttachments, ATT_TYPE_BUKTI_ISSUE, att.id, selectedItem.id)" class="text-red-600 hover:text-red-700 ml-2"><i class="fas fa-trash-alt"></i></button>
          </div>
        </div>
        <div v-if="editAttachments[ATT_TYPE_BUKTI_ISSUE].files.length > 0" class="space-y-2 mb-2">
          <div v-for="(f, i) in editAttachments[ATT_TYPE_BUKTI_ISSUE].files" :key="i" data-testid="edit-attachment-new-row" class="p-2.5 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg space-y-1.5">
            <div class="flex items-center justify-between">
              <span class="text-xs font-medium text-emerald-700 dark:text-emerald-300"><i class="fas fa-plus mr-1"></i>{{ f.name }} ({{ Math.round(f.size/1024) }} KB)</span>
              <button type="button" @click="removeAttachmentFile(ATT_TYPE_BUKTI_ISSUE, editAttachments, i)" class="text-xs text-red-600 hover:text-red-700"><i class="fas fa-times"></i></button>
            </div>
            <input v-model="editAttachments[ATT_TYPE_BUKTI_ISSUE].names[i]" placeholder="Nama / label" class="w-full px-2 py-1 text-xs border border-emerald-300 dark:border-emerald-700 rounded bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-1 focus:ring-emerald-500 outline-none" />
            <input v-model="editAttachments[ATT_TYPE_BUKTI_ISSUE].descs[i]" placeholder="Keterangan" class="w-full px-2 py-1 text-xs border border-emerald-300 dark:border-emerald-700 rounded bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-1 focus:ring-emerald-500 outline-none" />
          </div>
        </div>
        <input data-testid="input-file-bukti-issue" @change="e => { for (const f of e.target.files) addAttachmentFile(ATT_TYPE_BUKTI_ISSUE, editAttachments, f); e.target.value = ''; }" type="file" multiple accept=".jpg,.jpeg,.png,.webp,.pdf" class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-amber-50 file:text-amber-700 dark:file:bg-amber-900/30 dark:file:text-amber-400 hover:file:bg-amber-100 dark:hover:file:bg-amber-900/50" />
        <p v-if="editErrors.attachments_bukti_issue" class="text-red-500 text-xs mt-1">{{ editErrors.attachments_bukti_issue }}</p>
      </div>

      <!-- Bukti Issue Selesai attachments -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Bukti Issue Selesai <span class="text-xs text-gray-400">(opsional)</span></label>
        <div v-if="editExistingAttachments[ATT_TYPE_BUKTI_ISSUE_SELESAI].length > 0" class="space-y-1.5 mb-2">
          <div v-for="att in editExistingAttachments[ATT_TYPE_BUKTI_ISSUE_SELESAI]" :key="att.id" data-testid="existing-attachment-row" class="flex items-center justify-between p-2 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded text-xs">
            <div class="flex items-center gap-2 truncate">
              <i class="fas fa-paperclip text-emerald-500"></i>
              <a :href="att.url" target="_blank" class="text-emerald-700 dark:text-emerald-300 hover:underline truncate">{{ att.file_name }}</a>
            </div>
            <button type="button" data-testid="btn-delete-attachment" @click="deleteAttachment(editExistingAttachments, ATT_TYPE_BUKTI_ISSUE_SELESAI, att.id, selectedItem.id)" class="text-red-600 hover:text-red-700 ml-2"><i class="fas fa-trash-alt"></i></button>
          </div>
        </div>
        <div v-if="editAttachments[ATT_TYPE_BUKTI_ISSUE_SELESAI].files.length > 0" class="space-y-2 mb-2">
          <div v-for="(f, i) in editAttachments[ATT_TYPE_BUKTI_ISSUE_SELESAI].files" :key="i" data-testid="edit-attachment-new-row" class="p-2.5 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg space-y-1.5">
            <div class="flex items-center justify-between">
              <span class="text-xs font-medium text-emerald-700 dark:text-emerald-300"><i class="fas fa-plus mr-1"></i>{{ f.name }} ({{ Math.round(f.size/1024) }} KB)</span>
              <button type="button" @click="removeAttachmentFile(ATT_TYPE_BUKTI_ISSUE_SELESAI, editAttachments, i)" class="text-xs text-red-600 hover:text-red-700"><i class="fas fa-times"></i></button>
            </div>
            <input v-model="editAttachments[ATT_TYPE_BUKTI_ISSUE_SELESAI].names[i]" placeholder="Nama / label" class="w-full px-2 py-1 text-xs border border-emerald-300 dark:border-emerald-700 rounded bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-1 focus:ring-emerald-500 outline-none" />
            <input v-model="editAttachments[ATT_TYPE_BUKTI_ISSUE_SELESAI].descs[i]" placeholder="Keterangan" class="w-full px-2 py-1 text-xs border border-emerald-300 dark:border-emerald-700 rounded bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-1 focus:ring-emerald-500 outline-none" />
          </div>
        </div>
        <input data-testid="input-file-bukti-selesai" @change="e => { for (const f of e.target.files) addAttachmentFile(ATT_TYPE_BUKTI_ISSUE_SELESAI, editAttachments, f); e.target.value = ''; }" type="file" multiple accept=".jpg,.jpeg,.png,.webp,.pdf" class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-emerald-50 file:text-emerald-700 dark:file:bg-emerald-900/30 dark:file:text-emerald-400 hover:file:bg-emerald-100 dark:hover:file:bg-emerald-900/50" />
        <p v-if="editErrors.attachments_bukti_issue_selesai" class="text-red-500 text-xs mt-1">{{ editErrors.attachments_bukti_issue_selesai }}</p>
      </div>
    </div><div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700"><button type="button" @click="showEditModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button><button data-testid="btn-update" type="submit" :disabled="editForm.processing" class="px-6 py-2.5 bg-amber-600 text-white text-sm font-medium rounded-lg hover:bg-amber-700 transition-colors shadow-sm disabled:opacity-50"><i class="fas fa-check mr-1.5"></i>Update</button></div></form></div></Transition></Teleport>

    <!-- Resolve Modal -->
    <Teleport to="body"><Transition name="modal"><div v-if="showResolveModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showResolveModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><form data-testid="modal-resolve" @submit.prevent="submitResolve" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col"><div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-check-circle text-emerald-500 mr-2"></i>Tandai Selesai</h3><button type="button" @click="showResolveModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button></div><div class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll">
      <FormErrorSummary :errors="resolveErrors" testId="resolve-error-summary" />
      <p class="text-sm text-gray-600 dark:text-gray-400">Tandai tiket <strong class="text-gray-900 dark:text-white">{{ selectedItem?.code }}</strong> sebagai selesai.</p>

      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Bukti Issue Selesai</label>
        <div v-if="resolveExistingAttachments[ATT_TYPE_BUKTI_ISSUE_SELESAI].length > 0" class="space-y-1.5 mb-2">
          <div v-for="att in resolveExistingAttachments[ATT_TYPE_BUKTI_ISSUE_SELESAI]" :key="att.id" class="flex items-center justify-between p-2 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded text-xs">
            <div class="flex items-center gap-2 truncate">
              <i class="fas fa-paperclip text-emerald-500"></i>
              <a :href="att.url" target="_blank" class="text-emerald-700 dark:text-emerald-300 hover:underline truncate">{{ att.file_name }}</a>
            </div>
            <button type="button" data-testid="btn-delete-attachment" @click="deleteAttachment(resolveExistingAttachments, ATT_TYPE_BUKTI_ISSUE_SELESAI, att.id, selectedItem.id)" class="text-red-600 hover:text-red-700 ml-2"><i class="fas fa-trash-alt"></i></button>
          </div>
        </div>
        <div v-if="resolveAttachments[ATT_TYPE_BUKTI_ISSUE_SELESAI].files.length > 0" class="space-y-2 mb-2">
          <div v-for="(f, i) in resolveAttachments[ATT_TYPE_BUKTI_ISSUE_SELESAI].files" :key="i" class="p-2.5 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg space-y-1.5">
            <div class="flex items-center justify-between">
              <span class="text-xs font-medium text-emerald-700 dark:text-emerald-300"><i class="fas fa-plus mr-1"></i>{{ f.name }} ({{ Math.round(f.size/1024) }} KB)</span>
              <button type="button" @click="removeAttachmentFile(ATT_TYPE_BUKTI_ISSUE_SELESAI, resolveAttachments, i)" class="text-xs text-red-600 hover:text-red-700"><i class="fas fa-times"></i></button>
            </div>
            <input v-model="resolveAttachments[ATT_TYPE_BUKTI_ISSUE_SELESAI].names[i]" placeholder="Nama / label" class="w-full px-2 py-1 text-xs border border-emerald-300 dark:border-emerald-700 rounded bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-1 focus:ring-emerald-500 outline-none" />
            <input v-model="resolveAttachments[ATT_TYPE_BUKTI_ISSUE_SELESAI].descs[i]" placeholder="Keterangan" class="w-full px-2 py-1 text-xs border border-emerald-300 dark:border-emerald-700 rounded bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-1 focus:ring-emerald-500 outline-none" />
          </div>
        </div>
        <input data-testid="input-file-bukti-selesai" @change="e => { for (const f of e.target.files) addAttachmentFile(ATT_TYPE_BUKTI_ISSUE_SELESAI, resolveAttachments, f); e.target.value = ''; }" type="file" multiple accept=".jpg,.jpeg,.png,.webp,.pdf" class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-emerald-50 file:text-emerald-700 dark:file:bg-emerald-900/30 dark:file:text-emerald-400 hover:file:bg-emerald-100 dark:hover:file:bg-emerald-900/50" />
        <p v-if="resolveErrors.attachments_bukti_issue_selesai" class="text-red-500 text-xs mt-1">{{ resolveErrors.attachments_bukti_issue_selesai }}</p>
      </div>
    </div><div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700"><button type="button" @click="showResolveModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button><button data-testid="btn-confirm-resolve" type="submit" :disabled="resolveForm.processing" class="px-6 py-2.5 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors shadow-sm disabled:opacity-50"><i class="fas fa-check mr-1.5"></i>Tandai Selesai</button></div></form></div></Transition></Teleport>

    <!-- Detail Modal -->
    <Teleport to="body"><Transition name="modal"><div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showDetailModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col"><div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white">Detail Tiket</h3><button @click="showDetailModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700"><i class="fas fa-times"></i></button></div><div class="overflow-y-auto flex-1 px-6 py-5 modal-scroll" v-if="selectedItem">
      <div class="grid grid-cols-2 gap-4">
        <div><label class="text-xs text-gray-500 dark:text-gray-400">Kode</label><p class="font-mono font-medium text-gray-900 dark:text-white">{{ selectedItem.code }}</p></div>
        <div><label class="text-xs text-gray-500 dark:text-gray-400">Customer</label><p class="text-sm text-gray-900 dark:text-white">{{ selectedItem.customer_name }}</p></div>
        <div><label class="text-xs text-gray-500 dark:text-gray-400">PIC Utama</label><p class="text-sm font-medium text-gray-900 dark:text-white" data-testid="detail-main-pic">{{ selectedItem.main_pic_name || '—' }}</p></div>
        <div v-if="selectedItem.additional_pics && selectedItem.additional_pics.length > 0" class="col-span-2"><label class="text-xs text-gray-500 dark:text-gray-400">PIC Tambahan ({{ selectedItem.additional_pics.length }})</label><div class="flex flex-wrap gap-1.5 mt-1"><span v-for="pic in selectedItem.additional_pics" :key="pic.id" data-testid="detail-additional-pic" class="inline-flex items-center px-2 py-0.5 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded text-xs font-medium">{{ pic.employee_name }}</span></div></div>
        <div><label class="text-xs text-gray-500 dark:text-gray-400">Pengerjaan</label><p><span :class="['inline-flex px-2 py-0.5 rounded text-xs font-medium', statusPengerjaanBadge(selectedItem.status_pengerjaan)]">{{ selectedItem.status_pengerjaan_label }}</span></p></div>
        <div><label class="text-xs text-gray-500 dark:text-gray-400">Verifikasi</label><p><span :class="['inline-flex px-2 py-0.5 rounded text-xs font-medium', statusVerifikasiBadge(selectedItem.status_verifikasi)]">{{ selectedItem.status_verifikasi_label }}</span></p></div>
        <div><label class="text-xs text-gray-500 dark:text-gray-400">Tgl Mulai</label><p class="text-sm text-gray-900 dark:text-white">{{ formatDate(selectedItem.issue_dimulai_dari) }}</p></div>
        <div><label class="text-xs text-gray-500 dark:text-gray-400">Tgl Selesai</label><p class="text-sm text-gray-900 dark:text-white">{{ formatDate(selectedItem.issue_diselesaikan_pada) }}</p></div>
        <div class="col-span-2"><label class="text-xs text-gray-500 dark:text-gray-400">Catatan</label><p class="text-sm text-gray-900 dark:text-white whitespace-pre-wrap">{{ selectedItem.catatan }}</p></div>
        <div v-if="selectedItem.alasan_verifikasi" class="col-span-2"><label class="text-xs text-gray-500 dark:text-gray-400">Alasan Verifikasi</label><p class="text-sm text-gray-900 dark:text-white whitespace-pre-wrap">{{ selectedItem.alasan_verifikasi }}</p></div>

        <div v-if="(selectedItem.attachments?.bukti_issue?.length || 0) > 0" class="col-span-2">
          <label class="text-xs text-gray-500 dark:text-gray-400">Bukti Issue ({{ selectedItem.attachments.bukti_issue.length }})</label>
          <div class="flex flex-wrap gap-1.5 mt-1">
            <a v-for="att in selectedItem.attachments.bukti_issue" :key="att.id" :href="att.url" target="_blank" class="inline-flex items-center px-2.5 py-1 bg-amber-600 text-white text-xs rounded-lg hover:bg-amber-700"><i class="fas fa-image mr-1"></i>{{ att.file_name }}</a>
          </div>
        </div>
        <div v-if="(selectedItem.attachments?.bukti_issue_selesai?.length || 0) > 0" class="col-span-2">
          <label class="text-xs text-gray-500 dark:text-gray-400">Bukti Selesai ({{ selectedItem.attachments.bukti_issue_selesai.length }})</label>
          <div class="flex flex-wrap gap-1.5 mt-1">
            <a v-for="att in selectedItem.attachments.bukti_issue_selesai" :key="att.id" :href="att.url" target="_blank" class="inline-flex items-center px-2.5 py-1 bg-emerald-600 text-white text-xs rounded-lg hover:bg-emerald-700"><i class="fas fa-image mr-1"></i>{{ att.file_name }}</a>
          </div>
        </div>
      </div>
    </div></div></div></Transition></Teleport>

    <!-- Delete Modal -->
    <Teleport to="body"><Transition name="modal"><div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showDeleteModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-sm"><div class="px-6 py-5 text-center"><div class="w-14 h-14 mx-auto rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-4"><i class="fas fa-exclamation-triangle text-red-500 text-xl"></i></div><h3 class="text-lg font-semibold mb-2 text-gray-900 dark:text-white">Hapus Tiket?</h3><p class="text-sm text-gray-600 dark:text-gray-400">Hapus tiket <strong class="text-gray-900 dark:text-white">{{ selectedItem?.code }}</strong>?</p></div><div class="flex justify-center gap-3 px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700"><button @click="showDeleteModal = false" class="px-5 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">Batal</button><button data-testid="btn-confirm-delete" @click="confirmDelete" class="px-5 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700"><i class="fas fa-trash-alt mr-1.5"></i> Hapus</button></div></div></div></Transition></Teleport>
  </div>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-active > div:last-child, .modal-leave-active > div:last-child { transition: transform 0.2s ease, opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-from > div:last-child { transform: scale(0.95) translateY(10px); opacity: 0; }
.modal-leave-to > div:last-child { transform: scale(0.95) translateY(10px); opacity: 0; }
</style>
