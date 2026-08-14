<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import OperatorPerusahaanLayout from '@/Layouts/OperatorPerusahaanLayout.vue';
import CountryCodeSelect from '@/Components/CountryCodeSelect.vue';
import FormErrorSummary from '@/Components/FormErrorSummary.vue';
import { useToast } from '@/Composables/useToast';
import { errorSummary } from '@/Composables/useFormErrorToast';
import ToastContainer from '@/Components/ToastContainer.vue';

defineOptions({ layout: OperatorPerusahaanLayout });

const props = defineProps({
  admins: Object,
  filters: Object,
});

const toast = useToast();

// ── Search & Filter State ──
const searchInput = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const terhapusFilter = ref(props.filters?.terhapus || 'tidak');
const sortField = ref(props.filters?.sort_field || '');
const sortDir = ref(props.filters?.sort_dir || 'asc');
const perPage = ref(props.filters?.per_page && !isNaN(props.filters.per_page) ? Number(props.filters.per_page) : 10);
const perPageOptions = [5, 10, 25, 50, 100];

function buildQuery(overrides = {}) {
  const params = { ...overrides };
  if (params.search === undefined) params.search = searchInput.value || undefined;
  if (params.status === undefined) params.status = statusFilter.value || undefined;
  if (params.terhapus === undefined) params.terhapus = terhapusFilter.value || undefined;
  if (params.sort_field === undefined) params.sort_field = sortField.value || undefined;
  if (params.sort_dir === undefined) params.sort_dir = sortDir.value || undefined;
  if (params.per_page === undefined) params.per_page = perPage.value;
  Object.keys(params).forEach(k => { if (params[k] === undefined) delete params[k]; });
  return params;
}

function fetchData(overrides = {}) {
  router.get('/operator-perusahaan/admin-perusahaan', buildQuery(overrides), {
    preserveState: true, preserveScroll: true, replace: true,
  });
}

function applySearch() { fetchData({ search: searchInput.value || undefined, status: statusFilter.value || undefined, terhapus: terhapusFilter.value, page: 1 }); }
function clearSearch() { searchInput.value = ''; fetchData({ search: undefined, status: statusFilter.value || undefined, terhapus: terhapusFilter.value, page: 1 }); }
function applyFilters() { fetchData({ search: searchInput.value || undefined, status: statusFilter.value || undefined, terhapus: terhapusFilter.value, page: 1 }); }
function resetFilters() { searchInput.value = ''; statusFilter.value = ''; terhapusFilter.value = 'tidak'; fetchData({ search: undefined, status: undefined, terhapus: 'tidak', page: 1 }); }

// ── Sort ──
function sort(field) {
  if (sortField.value === field) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : (sortField.value = '', 'asc');
  } else { sortField.value = field; sortDir.value = 'asc'; }
  fetchData({ sort_field: sortField.value || undefined, sort_dir: sortDir.value, page: 1 });
}
function sortIcon(field) { return sortField.value !== field ? 'fa-sort' : (sortDir.value === 'asc' ? 'fa-sort-up' : 'fa-sort-down'); }
function sortOrder(field) { return sortField.value === field ? 1 : null; }

// ── Pagination (server-driven) ──
const currentPage = computed(() => props.admins?.current_page || 1);
const totalPages = computed(() => props.admins?.last_page || 1);
function changePerPage(n) { perPage.value = n; fetchData({ per_page: n, page: 1 }); }
function goToPage(page) { fetchData({ page: Math.max(1, Math.min(page, totalPages.value)) }); }
const visiblePages = computed(() => {
  const p = []; const t = totalPages.value; const c = currentPage.value;
  let st = Math.max(1, c - 2); let en = Math.min(t, c + 2);
  if (en - st < 4) { if (st === 1) en = Math.min(t, st + 4); else st = Math.max(1, en - 4); }
  for (let i = st; i <= en; i++) p.push(i); return p;
});

// ── Bulk Action ──
const selectedIds = ref([]); const selectAll = ref(false);
const adminsList = computed(() => props.admins?.data || []);

function toggleSelectAll() { selectedIds.value = selectAll.value ? adminsList.value.map(a => a.id) : []; }
function toggleSelect(id) { const i = selectedIds.value.indexOf(id); i === -1 ? selectedIds.value.push(id) : selectedIds.value.splice(i, 1); selectAll.value = selectedIds.value.length === adminsList.value.length; }

function bulkDelete() {
  if (!selectedIds.value.length) return;
  router.post('/operator-perusahaan/admin-perusahaan/bulk-delete', { ids: [...selectedIds.value] }, {
    preserveState: true, preserveScroll: true,
    onSuccess: () => { selectedIds.value = []; selectAll.value = false; toast.success(`${selectedIds.value.length} admin berhasil dihapus.`); },
    onError: () => toast.error('Gagal menghapus admin.'),
  });
}

function bulkRestore() {
  if (!selectedIds.value.length) return;
  router.post('/operator-perusahaan/admin-perusahaan/bulk-restore', { ids: [...selectedIds.value] }, {
    preserveState: true, preserveScroll: true,
    onSuccess: () => { selectedIds.value = []; selectAll.value = false; toast.success('Admin berhasil dipulihkan.'); },
    onError: () => toast.error('Gagal memulihkan admin.'),
  });
}

function bulkSetStatus(status) {
  if (!selectedIds.value.length) return;
  router.post('/operator-perusahaan/admin-perusahaan/bulk-status', { ids: [...selectedIds.value], status }, {
    preserveState: true, preserveScroll: true,
    onSuccess: () => { selectedIds.value = []; selectAll.value = false; toast.success(`Admin berhasil ${status === 'Aktif' ? 'diaktifkan' : 'dinonaktifkan'}.`); },
    onError: () => toast.error('Gagal mengubah status admin.'),
  });
}

function restoreAdmin(admin) {
  router.patch(`/operator-perusahaan/admin-perusahaan/${admin.id}/restore`, {}, {
    preserveState: true, preserveScroll: true,
    onSuccess: () => toast.success(`Admin "${admin.nama}" berhasil dipulihkan.`),
    onError: () => toast.error('Gagal memulihkan admin.'),
  });
}

// ── Helpers ──
function statusBadge(s) { return s === 'Aktif' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'; }
function formatTelepon(a) { return (a.kode_negara || '') + ' ' + (a.no_telp || ''); }

// ── CRUD State ──
const selectedAdmin = ref(null);
const showCreateModal = ref(false); const showDetailModal = ref(false); const showEditModal = ref(false); const showDeleteModal = ref(false);
const kodeNegaraList = ['+62', '+60', '+65', '+66', '+84', '+1', '+44', '+81', '+86'];

const createForm = useForm({ nama: '', email: '', kode_negara: '+62', no_telp: '', status: 'Aktif', password: '' });
const editForm = useForm({ id: null, nama: '', email: '', kode_negara: '+62', no_telp: '', status: 'Aktif', password: '' });

const showCreatePw = ref(false);
const showEditPw = ref(false);
function openCreate() { createForm.reset(); createForm.clearErrors(); showCreateModal.value = true; }
function openDetail(a) { selectedAdmin.value = a; showDetailModal.value = true; }
function openEdit(a) { editForm.reset(); editForm.clearErrors(); editForm.id = a.id; editForm.nama = a.nama; editForm.email = a.email; editForm.kode_negara = a.kode_negara; editForm.no_telp = a.no_telp; editForm.status = a.status; editForm.password = ''; showEditModal.value = true; }
function openDelete(a) { selectedAdmin.value = a; showDeleteModal.value = true; }

function saveCreate() {
  createForm.post('/operator-perusahaan/admin-perusahaan', {
    preserveState: true, preserveScroll: true,
    onSuccess: () => { showCreateModal.value = false; toast.success('Admin Perusahaan berhasil ditambahkan.'); },
    onError: () => toast.error('Validasi gagal: ' + errorSummary(createForm.errors), 6000),
  });
}

function saveEdit() {
  editForm.put(`/operator-perusahaan/admin-perusahaan/${editForm.id}`, {
    preserveState: true, preserveScroll: true,
    onSuccess: () => { showEditModal.value = false; toast.success('Admin Perusahaan berhasil diperbarui.'); },
    onError: () => toast.error('Validasi gagal: ' + errorSummary(editForm.errors), 6000),
  });
}

function confirmDelete() {
  const name = selectedAdmin.value?.nama;
  router.delete(`/operator-perusahaan/admin-perusahaan/${selectedAdmin.value?.id}`, {
    preserveState: true, preserveScroll: true,
    onSuccess: () => { showDeleteModal.value = false; toast.success(`Admin "${name}" berhasil dihapus.`); if (adminsList.value.length === 0 && currentPage.value > 1) goToPage(currentPage.value - 1); },
    onError: () => toast.error('Gagal menghapus admin.'),
  });
}
</script>

<template>
  <div>
    <Head title="Admin Perusahaan | Operator Perusahaan" />
    <ToastContainer />

    <div class="space-y-6">
      <!-- Breadcrumbs -->
      <nav class="flex items-center gap-1.5 text-sm">
        <Link href="/operator-perusahaan/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-sky-600 dark:hover:text-sky-400 transition-colors"><i class="fas fa-home"></i></Link>
        <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
        <Link href="/operator-perusahaan/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-sky-600 dark:hover:text-sky-400 transition-colors">Dashboard</Link>
        <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
        <span class="text-gray-900 dark:text-white font-medium">Admin Perusahaan</span>
      </nav>

      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div><h2 class="text-2xl font-bold text-gray-900 dark:text-white">Admin Perusahaan</h2><p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Kelola akun admin untuk perusahaan ini.</p></div>
        <button @click="openCreate" class="inline-flex items-center px-4 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm"><i class="fas fa-plus mr-1.5"></i> Tambah Admin</button>
      </div>

      <!-- Search & Count Bar -->
      <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
        <div class="relative w-full sm:w-72">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-search text-gray-400 text-sm"></i></div>
          <input v-model="searchInput" type="text" placeholder="Cari admin..." class="w-full pl-10 pr-16 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors" @keydown.enter="applySearch" />
          <div class="absolute inset-y-0 right-0 flex items-center gap-1 pr-1.5">
            <button v-if="searchInput" @click="clearSearch" class="p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-white transition-colors" title="Clear"><i class="fas fa-times text-xs"></i></button>
            <button @click="applySearch" class="px-2 py-1 rounded bg-sky-600 text-white hover:bg-sky-700 transition-colors" title="Cari"><i class="fas fa-search text-xs"></i></button>
          </div>
        </div>
        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400"><span>{{ props.admins?.total || 0 }} data</span><button v-if="statusFilter || terhapusFilter !== 'tidak' || searchInput" @click="resetFilters" class="text-xs text-red-500 hover:text-red-700 dark:hover:text-red-400 underline">Reset filter</button></div>
      </div>

      <!-- Filter Card -->
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm">
        <div class="flex flex-wrap items-end gap-4">
          <div class="min-w-[160px]">
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Status</label>
            <select v-model="statusFilter" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors">
              <option value="">Semua</option>
              <option value="Aktif">Ya (Aktif)</option>
              <option value="Nonaktif">Tidak (Nonaktif)</option>
            </select>
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

      <!-- Bulk Action Bar -->
      <div v-if="selectedIds.length > 0" class="flex items-center justify-between px-4 py-3 bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-800 rounded-xl shadow-sm">
        <span class="text-sm font-medium text-sky-700 dark:text-sky-300"><i class="fas fa-check-circle mr-1.5"></i> {{ selectedIds.length }} data dipilih</span>
        <div class="flex items-center gap-2">
          <button v-if="terhapusFilter === 'ya'" @click="bulkRestore()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition-colors"><i class="fas fa-undo-alt mr-1"></i> Pulihkan</button>
          <button v-if="terhapusFilter !== 'ya'" @click="bulkSetStatus('Aktif')" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition-colors"><i class="fas fa-check mr-1"></i> Aktifkan</button>
          <button v-if="terhapusFilter !== 'ya'" @click="bulkSetStatus('Nonaktif')" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-amber-600 text-white hover:bg-amber-700 transition-colors"><i class="fas fa-ban mr-1"></i> Nonaktifkan</button>
          <button @click="bulkDelete()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-red-600 text-white hover:bg-red-700 transition-colors"><i class="fas fa-trash-alt mr-1"></i> Hapus</button>
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm min-w-[700px]">
            <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
              <tr>
                <th class="px-4 py-3 w-10"><input v-model="selectAll" type="checkbox" @change="toggleSelectAll" class="rounded border-gray-300 dark:border-gray-600 text-sky-600 focus:ring-sky-500" /></th>
                <th @click="sort('name')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none hover:text-gray-900 dark:hover:text-white transition-colors">
                  <span class="inline-flex items-center gap-1">Nama <span v-if="sortOrder('name')" class="inline-flex items-center gap-0.5 text-sky-500 dark:text-sky-400"><i :class="['fas', sortIcon('name'), 'text-[10px]']"></i><span class="text-[10px] font-bold leading-none">{{ sortOrder('name') }}</span></span><i v-else class="fas fa-sort text-[10px] text-gray-400 dark:text-gray-500"></i></span>
                </th>
                <th @click="sort('email')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none hover:text-gray-900 dark:hover:text-white transition-colors">
                  <span class="inline-flex items-center gap-1">Email <span v-if="sortOrder('email')" class="inline-flex items-center gap-0.5 text-sky-500 dark:text-sky-400"><i :class="['fas', sortIcon('email'), 'text-[10px]']"></i><span class="text-[10px] font-bold leading-none">{{ sortOrder('email') }}</span></span><i v-else class="fas fa-sort text-[10px] text-gray-400 dark:text-gray-500"></i></span>
                </th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Telepon</th>
                <th @click="sort('is_active')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none hover:text-gray-900 dark:hover:text-white transition-colors">
                  <span class="inline-flex items-center gap-1">Status <span v-if="sortOrder('is_active')" class="inline-flex items-center gap-0.5 text-sky-500 dark:text-sky-400"><i :class="['fas', sortIcon('is_active'), 'text-[10px]']"></i><span class="text-[10px] font-bold leading-none">{{ sortOrder('is_active') }}</span></span><i v-else class="fas fa-sort text-[10px] text-gray-400 dark:text-gray-500"></i></span>
                </th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-400 w-32">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-if="adminsList.length === 0"><td colspan="6" class="px-4 py-16 text-center text-gray-500 dark:text-gray-400"><i class="fas fa-user-tie text-3xl mb-2 block opacity-40"></i>Tidak ada data admin perusahaan.</td></tr>
              <tr v-for="a in adminsList" :key="a.id" :class="['transition-colors', a.dihapus ? 'bg-red-50/30 dark:bg-red-900/10 opacity-60' : 'hover:bg-gray-50 dark:hover:bg-gray-700/30']">
                <td class="px-4 py-3"><input :checked="selectedIds.includes(a.id)" type="checkbox" @change="toggleSelect(a.id)" class="rounded border-gray-300 dark:border-gray-600 text-sky-600 focus:ring-sky-500" /></td>
                <td class="px-4 py-3"><div class="flex items-center gap-2.5"><div class="w-8 h-8 rounded-full bg-gradient-to-br from-sky-500 to-blue-600 flex items-center justify-center text-white text-xs font-bold shrink-0">{{ a.nama.charAt(0) }}</div><span class="font-medium text-gray-900 dark:text-white whitespace-nowrap" :class="{ 'line-through': a.dihapus }">{{ a.nama }}</span></div></td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap" :class="{ 'line-through': a.dihapus }">{{ a.email }}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap" :class="{ 'line-through': a.dihapus }">{{ formatTelepon(a) }}</td>
                <td class="px-4 py-3 whitespace-nowrap">
                  <span v-if="a.dihapus" class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">Terhapus</span>
                  <span v-else :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium', statusBadge(a.status)]">{{ a.status }}</span>
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                  <div class="flex items-center justify-end gap-1">
                    <button @click="openDetail(a)" class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-sky-600 dark:hover:text-sky-400 transition-colors" title="Detail"><i class="fas fa-eye text-sm"></i></button>
                    <template v-if="a.dihapus">
                      <button @click="restoreAdmin(a)" class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors" title="Pulihkan"><i class="fas fa-undo-alt text-sm"></i></button>
                    </template>
                    <template v-else>
                      <button @click="openEdit(a)" class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-sky-600 dark:hover:text-sky-400 transition-colors" title="Edit"><i class="fas fa-edit text-sm"></i></button>
                      <button @click="openDelete(a)" class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-red-600 dark:hover:text-red-400 transition-colors" title="Hapus"><i class="fas fa-trash-alt text-sm"></i></button>
                    </template>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-3">
          <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
            <span>Tampilkan</span>
            <select :value="perPage" @change="changePerPage(Number($event.target.value))" class="min-w-[65px] px-2 py-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none">
              <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }}</option>
            </select>
            <span>dari {{ props.admins?.total || 0 }} data</span>
          </div>
          <div class="flex items-center gap-1 flex-wrap justify-center sm:justify-start">
            <button @click="goToPage(1)" :disabled="currentPage === 1" class="px-2.5 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"><i class="fas fa-chevron-double-left text-xs"></i></button>
            <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1" class="px-2.5 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"><i class="fas fa-chevron-left text-xs"></i></button>
            <button v-for="p in visiblePages" :key="p" @click="goToPage(p)" :class="['px-3 py-1.5 rounded-lg text-sm font-medium transition-colors', p === currentPage ? 'bg-sky-600 text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700']">{{ p }}</button>
            <button @click="goToPage(currentPage + 1)" :disabled="currentPage === totalPages" class="px-2.5 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"><i class="fas fa-chevron-right text-xs"></i></button>
            <button @click="goToPage(totalPages)" :disabled="currentPage === totalPages" class="px-2.5 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"><i class="fas fa-chevron-double-right text-xs"></i></button>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══════════════ DETAIL MODAL ═══════════════ -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showDetailModal = false">
          <div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
          <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col">
            <div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-eye mr-2 text-sky-500"></i>Detail Admin</h3>
              <button @click="showDetailModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button>
            </div>
            <div class="overflow-y-auto flex-1 px-6 py-5 space-y-5 modal-scroll">
              <div class="flex items-center gap-4 pb-4 border-b border-gray-100 dark:border-gray-700">
                <div class="w-16 h-16 rounded-full bg-gradient-to-br from-sky-500 to-blue-600 flex items-center justify-center text-white text-2xl font-bold shrink-0">{{ selectedAdmin?.nama?.charAt(0) }}</div>
                <div>
                  <h4 class="text-xl font-bold text-gray-900 dark:text-white">{{ selectedAdmin?.nama }}</h4>
                  <span v-if="selectedAdmin?.dihapus" class="inline-flex mt-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">Terhapus</span>
                  <span v-else :class="['inline-flex mt-1 px-2.5 py-0.5 rounded-full text-xs font-medium', statusBadge(selectedAdmin?.status)]">{{ selectedAdmin?.status }}</span>
                </div>
              </div>
              <div>
                <h5 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Informasi Akun</h5>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Email</label><p class="text-sm text-gray-900 dark:text-white mt-0.5">{{ selectedAdmin?.email }}</p></div>
                  <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Kode Negara</label><p class="text-sm text-gray-900 dark:text-white mt-0.5">{{ selectedAdmin?.kode_negara }}</p></div>
                  <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">No. Telepon</label><p class="text-sm text-gray-900 dark:text-white mt-0.5">{{ selectedAdmin?.no_telp }}</p></div>
                </div>
              </div>
              <div>
                <h5 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Riwayat</h5>
                <div class="space-y-2.5">
                  <div class="flex items-start gap-3">
                    <div class="w-7 h-7 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center shrink-0 mt-0.5"><i class="fas fa-plus text-emerald-600 dark:text-emerald-400 text-xs"></i></div>
                    <div><p class="text-sm text-gray-900 dark:text-white">Dibuat</p><p class="text-xs text-gray-500 dark:text-gray-400">{{ selectedAdmin?.created_at }}</p><p v-if="selectedAdmin?.created_by" class="text-xs text-gray-400 dark:text-gray-500">oleh {{ selectedAdmin?.created_by }}</p></div>
                  </div>
                  <div class="flex items-start gap-3">
                    <div class="w-7 h-7 rounded-full bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center shrink-0 mt-0.5"><i class="fas fa-pen text-sky-600 dark:text-sky-400 text-xs"></i></div>
                    <div><p class="text-sm text-gray-900 dark:text-white">Terakhir diperbarui</p><p class="text-xs text-gray-500 dark:text-gray-400">{{ selectedAdmin?.updated_at }}</p><p v-if="selectedAdmin?.updated_by" class="text-xs text-gray-400 dark:text-gray-500">oleh {{ selectedAdmin?.updated_by }}</p></div>
                  </div>
                  <template v-if="selectedAdmin?.dihapus">
                    <div class="flex items-start gap-3">
                      <div class="w-7 h-7 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center shrink-0 mt-0.5"><i class="fas fa-trash-alt text-red-600 dark:text-red-400 text-xs"></i></div>
                      <div><p class="text-sm text-gray-900 dark:text-white">Dihapus</p><p class="text-xs text-gray-500 dark:text-gray-400">{{ selectedAdmin?.deleted_at }}</p><p v-if="selectedAdmin?.deleted_by" class="text-xs text-gray-400 dark:text-gray-500">oleh {{ selectedAdmin?.deleted_by }}</p></div>
                    </div>
                  </template>
                  <template v-if="selectedAdmin?.restored_at">
                    <div class="flex items-start gap-3">
                      <div class="w-7 h-7 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center shrink-0 mt-0.5"><i class="fas fa-undo-alt text-emerald-600 dark:text-emerald-400 text-xs"></i></div>
                      <div><p class="text-sm text-gray-900 dark:text-white">Dipulihkan</p><p class="text-xs text-gray-500 dark:text-gray-400">{{ selectedAdmin?.restored_at }}</p><p v-if="selectedAdmin?.restored_by" class="text-xs text-gray-400 dark:text-gray-500">oleh {{ selectedAdmin?.restored_by }}</p></div>
                    </div>
                  </template>
                </div>
              </div>
            </div>
            <div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
              <button v-if="!selectedAdmin?.dihapus" @click="showDetailModal = false; openEdit(selectedAdmin)" class="px-4 py-2 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors"><i class="fas fa-edit mr-1.5"></i> Edit</button>
              <button v-if="selectedAdmin?.dihapus" @click="showDetailModal = false; restoreAdmin(selectedAdmin)" class="px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors"><i class="fas fa-undo-alt mr-1.5"></i> Pulihkan</button>
              <button @click="showDetailModal = false" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Tutup</button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ═══════════════ CREATE MODAL ═══════════════ -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showCreateModal = false">
          <div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
          <form @submit.prevent="saveCreate" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col">
            <div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-plus text-emerald-500 mr-2"></i>Tambah Admin Perusahaan</h3>
              <button type="button" @click="showCreateModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button>
            </div>
            <div class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll">
              <FormErrorSummary :errors="createForm.errors" test-id="form-error-summary-create" />
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nama <span class="text-red-500">*</span></label>
                <input v-model="createForm.nama" type="text" placeholder="Nama lengkap" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', createForm.errors.nama ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500 focus:border-sky-500']" />
                <p v-if="createForm.errors.nama" class="text-red-500 text-xs mt-1">{{ createForm.errors.nama }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email <span class="text-red-500">*</span></label>
                <input v-model="createForm.email" type="email" placeholder="email@perusahaan.id" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', createForm.errors.email ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500 focus:border-sky-500']" />
                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1"><i class="fas fa-info-circle mr-1"></i>Email harus valid dan unik.</p>
<p v-if="createForm.errors.email" class="text-red-500 text-xs mt-1">{{ createForm.errors.email }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Password <span class="text-red-500">*</span></label>
                <div class="relative"><input v-model="createForm.password" :type="showCreatePw ? 'text' : 'password'" placeholder="Minimal 8 karakter" :class="['w-full px-3 py-2.5 pr-10 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', createForm.errors.password ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500 focus:border-sky-500']" /><button type="button" @click="showCreatePw = !showCreatePw" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-sky-500 transition-colors" :title="showCreatePw ? 'Sembunyikan' : 'Tampilkan'"><i :class="['fas text-sm', showCreatePw ? 'fa-eye-slash' : 'fa-eye']"></i></button></div>
                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1"><i class="fas fa-info-circle mr-1"></i>Minimal 8 karakter.</p>
<p v-if="createForm.errors.password" class="text-red-500 text-xs mt-1">{{ createForm.errors.password }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Telepon <span class="text-red-500">*</span></label>
                <div class="flex gap-2">
                  <select v-model="createForm.kode_negara" class="w-24 px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors hidden"><option v-for="k in kodeNegaraList" :key="k" :value="k">{{ k }}</option></select><div class="w-36"><CountryCodeSelect v-model="createForm.kode_negara" accent="sky" size="sm" /></div>
                  <input v-model="createForm.no_telp" type="text" placeholder="81234567890" :class="['flex-1 px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', createForm.errors.no_telp ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500 focus:border-sky-500']" />
                </div>
                <p v-if="createForm.errors.no_telp" class="text-red-500 text-xs mt-1">{{ createForm.errors.no_telp }}</p>
                <p v-if="createForm.errors.kode_negara" class="text-red-500 text-xs mt-1">{{ createForm.errors.kode_negara }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Status <span class="text-red-500">*</span></label>
                <select v-model="createForm.status" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-800 text-gray-900 dark:text-white', createForm.errors.status ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500 focus:border-sky-500']"><option value="Aktif">Aktif</option><option value="Nonaktif">Nonaktif</option></select>
                <p v-if="createForm.errors.status" class="text-red-500 text-xs mt-1">{{ createForm.errors.status }}</p>
              </div>
            </div>
            <div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
              <button type="button" @click="showCreateModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button>
              <button type="submit" :disabled="createForm.processing" class="px-6 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm disabled:opacity-50"><i class="fas fa-save mr-1.5"></i>Simpan</button>
            </div>
          </form>
        </div>
      </Transition>
    </Teleport>

    <!-- ═══════════════ EDIT MODAL ═══════════════ -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showEditModal = false">
          <div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
          <form @submit.prevent="saveEdit" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col">
            <div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-edit text-sky-500 mr-2"></i>Edit Admin Perusahaan</h3>
              <button type="button" @click="showEditModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button>
            </div>
            <div class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll">
              <FormErrorSummary :errors="editForm.errors" test-id="form-error-summary-edit" />
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nama <span class="text-red-500">*</span></label>
                <input v-model="editForm.nama" type="text" placeholder="Nama lengkap" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', editForm.errors.nama ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500 focus:border-sky-500']" />
                <p v-if="editForm.errors.nama" class="text-red-500 text-xs mt-1">{{ editForm.errors.nama }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email <span class="text-red-500">*</span></label>
                <input v-model="editForm.email" type="email" placeholder="email@perusahaan.id" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', editForm.errors.email ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500 focus:border-sky-500']" />
                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1"><i class="fas fa-info-circle mr-1"></i>Email harus valid dan unik.</p>
<p v-if="editForm.errors.email" class="text-red-500 text-xs mt-1">{{ editForm.errors.email }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Password <span class="text-gray-400">(kosongkan jika tidak diubah)</span></label>
                <div class="relative"><input v-model="editForm.password" :type="showEditPw ? 'text' : 'password'" placeholder="Minimal 8 karakter" :class="['w-full px-3 py-2.5 pr-10 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', editForm.errors.password ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500 focus:border-sky-500']" /><button type="button" @click="showEditPw = !showEditPw" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-sky-500 transition-colors" :title="showEditPw ? 'Sembunyikan' : 'Tampilkan'"><i :class="['fas text-sm', showEditPw ? 'fa-eye-slash' : 'fa-eye']"></i></button></div>
                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1"><i class="fas fa-info-circle mr-1"></i>Minimal 8 karakter.</p>
<p v-if="editForm.errors.password" class="text-red-500 text-xs mt-1">{{ editForm.errors.password }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Telepon <span class="text-red-500">*</span></label>
                <div class="flex gap-2">
                  <select v-model="editForm.kode_negara" class="w-24 px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors hidden"><option v-for="k in kodeNegaraList" :key="k" :value="k">{{ k }}</option></select><div class="w-36"><CountryCodeSelect v-model="editForm.kode_negara" accent="sky" size="sm" /></div>
                  <input v-model="editForm.no_telp" type="text" placeholder="81234567890" :class="['flex-1 px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', editForm.errors.no_telp ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500 focus:border-sky-500']" />
                </div>
                <p v-if="editForm.errors.no_telp" class="text-red-500 text-xs mt-1">{{ editForm.errors.no_telp }}</p>
                <p v-if="editForm.errors.kode_negara" class="text-red-500 text-xs mt-1">{{ editForm.errors.kode_negara }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Status <span class="text-red-500">*</span></label>
                <select v-model="editForm.status" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-800 text-gray-900 dark:text-white', editForm.errors.status ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500 focus:border-sky-500']"><option value="Aktif">Aktif</option><option value="Nonaktif">Nonaktif</option></select>
                <p v-if="editForm.errors.status" class="text-red-500 text-xs mt-1">{{ editForm.errors.status }}</p>
              </div>
            </div>
            <div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
              <button type="button" @click="showEditModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button>
              <button type="submit" :disabled="editForm.processing" class="px-6 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm disabled:opacity-50"><i class="fas fa-check mr-1.5"></i>Update</button>
            </div>
          </form>
        </div>
      </Transition>
    </Teleport>

    <!-- ═══════════════ DELETE MODAL ═══════════════ -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showDeleteModal = false">
          <div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
          <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
            <div class="px-6 py-5 text-center">
              <div class="w-14 h-14 mx-auto rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-4"><i class="fas fa-exclamation-triangle text-red-500 text-xl"></i></div>
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Hapus Admin?</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400">Anda akan menghapus <strong class="text-gray-700 dark:text-gray-300">{{ selectedAdmin?.nama }}</strong>. Data bisa dipulihkan kembali.</p>
            </div>
            <div class="flex justify-center gap-3 px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
              <button @click="showDeleteModal = false" class="px-5 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button>
              <button @click="confirmDelete" class="px-5 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors"><i class="fas fa-trash-alt mr-1.5"></i> Hapus</button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
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
