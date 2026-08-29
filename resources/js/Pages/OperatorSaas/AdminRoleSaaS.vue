<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import OperatorSaasLayout from '@/Layouts/OperatorSaasLayout.vue';
import SearchableSelectAjax from '@/Components/SearchableSelectAjax.vue';
import MultiSelectAjax from '@/Components/MultiSelectAjax.vue';
import FormErrorSummary from '@/Components/FormErrorSummary.vue';
import { useToast } from '@/Composables/useToast';
import { errorSummary } from '@/Composables/useFormErrorToast';
import ToastContainer from '@/Components/ToastContainer.vue';

defineOptions({ layout: OperatorSaasLayout });

const props = defineProps({
  assignments: Object,
  filters: Object,
});

const toast = useToast();

const ADMIN_URL = '/operator-saas/admin-role-saas/admins';
const ROLE_URL = '/operator-saas/admin-role-saas/roles';

// ── Search & Filter State ──
const searchInput = ref(props.filters?.search || '');
const sortField = ref(props.filters?.sort_field || '');
const sortDir = ref(props.filters?.sort_dir || 'asc');
const perPage = ref(props.filters?.per_page && !isNaN(props.filters.per_page) ? Number(props.filters.per_page) : 10);
const perPageOptions = [5, 10, 25, 50, 100];

function buildQuery(overrides = {}) {
  const params = { ...overrides };
  if (params.search === undefined) params.search = searchInput.value || undefined;
  if (params.sort_field === undefined) params.sort_field = sortField.value || undefined;
  if (params.sort_dir === undefined) params.sort_dir = sortDir.value || undefined;
  if (params.per_page === undefined) params.per_page = perPage.value;
  Object.keys(params).forEach(k => { if (params[k] === undefined) delete params[k]; });
  return params;
}

function fetchData(overrides = {}) {
  router.get('/operator-saas/admin-role-saas', buildQuery(overrides), {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}

function applySearch() { fetchData({ search: searchInput.value || undefined, page: 1 }); }
function clearSearch() { searchInput.value = ''; fetchData({ search: undefined, page: 1 }); }
function resetFilters() { searchInput.value = ''; fetchData({ search: undefined, sort_field: undefined, sort_dir: 'asc', page: 1 }); }

// ── Sort ──
function sort(field) {
  if (sortField.value === field) {
    if (sortDir.value === 'asc') { sortDir.value = 'desc'; }
    else { sortField.value = ''; sortDir.value = 'asc'; }
  } else { sortField.value = field; sortDir.value = 'asc'; }
  fetchData({ sort_field: sortField.value || undefined, sort_dir: sortDir.value, page: 1 });
}

function sortIcon(field) { return sortField.value !== field ? 'fa-sort' : sortDir.value === 'asc' ? 'fa-sort-up' : 'fa-sort-down'; }
function sortOrder(field) { return sortField.value === field ? 1 : null; }

// ── Pagination ──
const currentPage = computed(() => props.assignments?.current_page || 1);
const totalPages = computed(() => props.assignments?.last_page || 1);

function changePerPage(n) { perPage.value = n; fetchData({ per_page: n, page: 1 }); }
function goToPage(page) { fetchData({ page: Math.max(1, Math.min(page, totalPages.value)) }); }

const visiblePages = computed(() => {
  const pages = []; const total = totalPages.value; const current = currentPage.value;
  let start = Math.max(1, current - 2); let end = Math.min(total, current + 2);
  if (end - start < 4) { if (start === 1) end = Math.min(total, start + 4); else start = Math.max(1, end - 4); }
  for (let i = start; i <= end; i++) pages.push(i);
  return pages;
});

// ── Bulk Action ──
const selectedIds = ref([]);
const selectAll = ref(false);

const assignmentsList = computed(() => props.assignments?.data || []);

function toggleSelectAll() {
  if (selectAll.value) { selectedIds.value = assignmentsList.value.map(a => a.id); }
  else { selectedIds.value = []; }
}

function toggleSelect(id) {
  const idx = selectedIds.value.indexOf(id);
  if (idx === -1) { selectedIds.value.push(id); } else { selectedIds.value.splice(idx, 1); }
  selectAll.value = selectedIds.value.length === assignmentsList.value.length;
}

function bulkDelete() {
  if (selectedIds.value.length === 0) return;
  const count = selectedIds.value.length;
  router.post('/operator-saas/admin-role-saas/bulk-delete', { ids: [...selectedIds.value] }, {
    preserveState: true, preserveScroll: true,
    onSuccess: () => { selectedIds.value = []; selectAll.value = false; toast.success(`${count} penugasan role berhasil dihapus.`); },
    onError: () => { toast.error('Gagal menghapus penugasan role.'); },
  });
}

// ── Helpers ──
function statusBadge(status) {
  return status === 'Aktif' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
}

// ── CRUD State ──
const selectedAssignment = ref(null);
const showCreateModal = ref(false);
const showDetailModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const showBulkAssignModal = ref(false);
const showBulkUpdateRoleModal = ref(false);

const createForm = useForm({ admin_id: '', role_id: '' });
const editForm = useForm({ id: null, role_id: '' });
const bulkAssignForm = useForm({ admin_ids: [], role_id: '' });
const bulkUpdateRoleForm = useForm({ ids: [], role_id: '' });

function openBulkAssign() { bulkAssignForm.reset(); bulkAssignForm.clearErrors(); showBulkAssignModal.value = true; }
function saveBulkAssign() {
  bulkAssignForm.post('/operator-saas/admin-role-saas/bulk-assign', {
    preserveState: true, preserveScroll: true,
    onSuccess: () => { showBulkAssignModal.value = false; toast.success('Role berhasil ditetapkan ke admin terpilih.'); },
    onError: () => { toast.error('Validasi gagal: ' + errorSummary(bulkAssignForm.errors), 6000); },
  });
}
function openBulkUpdateRole() { bulkUpdateRoleForm.reset(); bulkUpdateRoleForm.clearErrors(); bulkUpdateRoleForm.ids = [...selectedIds.value]; showBulkUpdateRoleModal.value = true; }
function saveBulkUpdateRole() {
  bulkUpdateRoleForm.post('/operator-saas/admin-role-saas/bulk-update-role', {
    preserveState: true, preserveScroll: true,
    onSuccess: () => { showBulkUpdateRoleModal.value = false; selectedIds.value = []; selectAll.value = false; toast.success('Role mapping berhasil diubah.'); },
    onError: () => { toast.error('Validasi gagal: ' + errorSummary(bulkUpdateRoleForm.errors), 6000); },
  });
}

function openCreate() { createForm.reset(); createForm.clearErrors(); showCreateModal.value = true; }
function openDetail(a) { selectedAssignment.value = a; showDetailModal.value = true; }
function openEdit(a) { editForm.reset(); editForm.clearErrors(); editForm.id = a.id; editForm.role_id = a.role_id; showEditModal.value = true; }
function openDelete(a) { selectedAssignment.value = a; showDeleteModal.value = true; }

function saveCreate() {
  createForm.post('/operator-saas/admin-role-saas', {
    preserveState: true, preserveScroll: true,
    onSuccess: () => { showCreateModal.value = false; toast.success('Role admin SaaS berhasil ditetapkan.'); },
    onError: () => { toast.error('Validasi gagal: ' + errorSummary(createForm.errors), 6000); },
  });
}

function saveEdit() {
  editForm.put(`/operator-saas/admin-role-saas/${editForm.id}`, {
    preserveState: true, preserveScroll: true,
    onSuccess: () => { showEditModal.value = false; toast.success('Role admin SaaS berhasil diperbarui.'); },
    onError: () => { toast.error('Validasi gagal: ' + errorSummary(editForm.errors), 6000); },
  });
}

function confirmDelete() {
  const name = selectedAssignment.value?.admin_nama;
  router.delete(`/operator-saas/admin-role-saas/${selectedAssignment.value?.id}`, {
    preserveState: true, preserveScroll: true,
    onSuccess: () => {
      showDeleteModal.value = false;
      toast.success(`Penugasan role "${name}" berhasil dihapus.`);
      if (assignmentsList.value.length === 0 && currentPage.value > 1) goToPage(currentPage.value - 1);
    },
    onError: () => { toast.error('Gagal menghapus penugasan role.'); },
  });
}

</script>

<template>
  <div>
    <Head title="Admin Role SaaS | Operator SaaS" />
    <ToastContainer />

    <div class="space-y-6">
      <nav class="flex items-center gap-1.5 text-sm">
        <Link href="/operator-saas/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"><i class="fas fa-home"></i></Link>
        <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
        <Link href="/operator-saas/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Dashboard</Link>
        <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
        <span class="text-gray-900 dark:text-white font-medium">Admin Role SaaS</span>
      </nav>

      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div><h2 class="text-2xl font-bold text-gray-900 dark:text-white">Admin Role SaaS</h2><p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Pemetaan role untuk admin platform SaaS.</p></div>
        <div class="flex items-center gap-2 flex-wrap">
          <button data-testid="btn-open-create" @click="openCreate" class="inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm"><i class="fas fa-plus mr-1.5"></i> Tambah Mapping</button>
          <button data-testid="btn-open-bulk-assign" @click="openBulkAssign" class="inline-flex items-center px-4 py-2.5 bg-violet-600 text-white text-sm font-medium rounded-lg hover:bg-violet-700 transition-colors shadow-sm"><i class="fas fa-layer-group mr-1.5"></i> Tambah Sekaligus</button>
        </div>
      </div>

      <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
        <div class="relative w-full sm:w-72">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-search text-gray-400 text-sm"></i></div>
          <input v-model="searchInput" type="text" placeholder="Cari admin..." class="w-full pl-10 pr-16 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors" @keydown.enter="applySearch" data-testid="input-search">
          <div class="absolute inset-y-0 right-0 flex items-center gap-1 pr-1.5">
            <button v-if="searchInput" @click="clearSearch" class="p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-white transition-colors" title="Clear"><i class="fas fa-times text-xs"></i></button>
            <button @click="applySearch" class="px-2 py-1 rounded bg-indigo-600 text-white hover:bg-indigo-700 transition-colors" title="Cari"><i class="fas fa-search text-xs"></i></button>
          </div>
        </div>
        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
          <span>{{ props.assignments?.total || 0 }} data</span>
          <button v-if="searchInput" @click="resetFilters" class="text-xs text-red-500 hover:text-red-700 dark:hover:text-red-400 underline">Reset filter</button>
        </div>
      </div>

      <div v-if="selectedIds.length > 0" class="flex items-center justify-between px-4 py-3 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-xl shadow-sm">
        <span class="text-sm font-medium text-indigo-700 dark:text-indigo-300"><i class="fas fa-check-circle mr-1.5"></i> {{ selectedIds.length }} data dipilih</span>
        <div class="flex items-center gap-2">
          <button data-testid="btn-bulk-update-role" @click="openBulkUpdateRole" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-amber-600 text-white hover:bg-amber-700 transition-colors"><i class="fas fa-user-tag mr-1"></i> Ubah Sekaligus</button>
          <button @click="bulkDelete()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-red-600 text-white hover:bg-red-700 transition-colors"><i class="fas fa-trash-alt mr-1"></i> Hapus</button>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table data-testid="table-data" class="w-full text-sm min-w-[700px]">
            <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
              <tr>
                <th class="px-4 py-3 w-10"><input v-model="selectAll" type="checkbox" @change="toggleSelectAll" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500" data-testid="checkbox-select-all" /></th>
                <th @click="sort('admin_nama')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none hover:text-gray-900 dark:hover:text-white transition-colors">
                  <span class="inline-flex items-center gap-1">Admin <span v-if="sortOrder('admin_nama')" class="inline-flex items-center gap-0.5 text-indigo-500 dark:text-indigo-400"><i :class="['fas', sortIcon('admin_nama'), 'text-[10px]']"></i><span class="text-[10px] font-bold leading-none">{{ sortOrder('admin_nama') }}</span></span><i v-else class="fas fa-sort text-[10px] text-gray-400 dark:text-gray-500"></i></span>
                </th>
                <th @click="sort('role_nama')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none hover:text-gray-900 dark:hover:text-white transition-colors">
                  <span class="inline-flex items-center gap-1">Role <span v-if="sortOrder('role_nama')" class="inline-flex items-center gap-0.5 text-indigo-500 dark:text-indigo-400"><i :class="['fas', sortIcon('role_nama'), 'text-[10px]']"></i><span class="text-[10px] font-bold leading-none">{{ sortOrder('role_nama') }}</span></span><i v-else class="fas fa-sort text-[10px] text-gray-400 dark:text-gray-500"></i></span>
                </th>
                <th @click="sort('admin_status')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none hover:text-gray-900 dark:hover:text-white transition-colors">
                  <span class="inline-flex items-center gap-1">Status <span v-if="sortOrder('admin_status')" class="inline-flex items-center gap-0.5 text-indigo-500 dark:text-indigo-400"><i :class="['fas', sortIcon('admin_status'), 'text-[10px]']"></i><span class="text-[10px] font-bold leading-none">{{ sortOrder('admin_status') }}</span></span><i v-else class="fas fa-sort text-[10px] text-gray-400 dark:text-gray-500"></i></span>
                </th>
                <th @click="sort('created_at')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none hover:text-gray-900 dark:hover:text-white transition-colors">
                  <span class="inline-flex items-center gap-1">Dibuat <span v-if="sortOrder('created_at')" class="inline-flex items-center gap-0.5 text-indigo-500 dark:text-indigo-400"><i :class="['fas', sortIcon('created_at'), 'text-[10px]']"></i><span class="text-[10px] font-bold leading-none">{{ sortOrder('created_at') }}</span></span><i v-else class="fas fa-sort text-[10px] text-gray-400 dark:text-gray-500"></i></span>
                </th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-400 w-32">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-if="assignmentsList.length === 0"><td colspan="6" class="px-4 py-16 text-center text-gray-500 dark:text-gray-400"><i class="fas fa-users-cog text-3xl mb-2 block opacity-40"></i>Tidak ada data admin role SaaS.</td></tr>
              <tr v-for="a in assignmentsList" :key="a.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                <td class="px-4 py-3"><input :checked="selectedIds.includes(a.id)" type="checkbox" @change="toggleSelect(a.id)" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500" /></td>
                <td class="px-4 py-3">
                  <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center text-white text-xs font-bold shrink-0">{{ a.admin_nama?.charAt(0) }}</div>
                    <div>
                      <p class="font-medium text-gray-900 dark:text-white whitespace-nowrap">{{ a.admin_nama }}</p>
                      <p class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ a.admin_email }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap"><span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400"><i class="fas fa-user-tag text-[10px]"></i> {{ a.role_nama }}</span></td>
                <td class="px-4 py-3 whitespace-nowrap"><span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium', statusBadge(a.admin_status)]">{{ a.admin_status }}</span></td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap text-xs">{{ a.created_at }}</td>
                <td class="px-4 py-3 whitespace-nowrap">
                  <div class="flex items-center justify-end gap-1">
                    <button @click="openDetail(a)" class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors" title="Detail"><i class="fas fa-eye text-sm"></i></button>
                    <button @click="openEdit(a)" class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-sky-600 dark:hover:text-sky-400 transition-colors" title="Edit"><i class="fas fa-edit text-sm"></i></button>
                    <button @click="openDelete(a)" class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-red-600 dark:hover:text-red-400 transition-colors" title="Hapus"><i class="fas fa-trash-alt text-sm"></i></button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-3">
          <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
            <span>Tampilkan</span>
            <select :value="perPage" @change="changePerPage(Number($event.target.value))" class="min-w-[65px] px-2 py-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
              <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }}</option>
            </select>
            <span>dari {{ props.assignments?.total || 0 }} data</span>
          </div>
          <div class="flex items-center gap-1 flex-wrap justify-center sm:justify-start">
            <button @click="goToPage(1)" :disabled="currentPage === 1" class="px-2.5 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"><i class="fas fa-chevron-double-left text-xs"></i></button>
            <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1" class="px-2.5 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"><i class="fas fa-chevron-left text-xs"></i></button>
            <button v-for="p in visiblePages" :key="p" @click="goToPage(p)" :class="['px-3 py-1.5 rounded-lg text-sm font-medium transition-colors', p === currentPage ? 'bg-indigo-600 text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700']">{{ p }}</button>
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
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-eye mr-2 text-indigo-500"></i>Detail Admin Role</h3>
              <button @click="showDetailModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button>
            </div>
            <div class="overflow-y-auto flex-1 px-6 py-5 space-y-5 modal-scroll">
              <div class="flex items-center gap-4 pb-4 border-b border-gray-100 dark:border-gray-700">
                <div class="w-16 h-16 rounded-full bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center text-white text-2xl font-bold shrink-0">{{ selectedAssignment?.admin_nama?.charAt(0) }}</div>
                <div>
                  <h4 class="text-xl font-bold text-gray-900 dark:text-white">{{ selectedAssignment?.admin_nama }}</h4>
                  <span :class="['inline-flex mt-1 px-2.5 py-0.5 rounded-full text-xs font-medium', statusBadge(selectedAssignment?.admin_status)]">{{ selectedAssignment?.admin_status }}</span>
                </div>
              </div>
              <div>
                <h5 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Informasi Penugasan</h5>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Email Admin</label><p class="text-sm text-gray-900 dark:text-white mt-0.5">{{ selectedAssignment?.admin_email }}</p></div>
                  <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Role</label><p class="text-sm text-gray-900 dark:text-white mt-0.5"><span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400"><i class="fas fa-user-tag text-[10px]"></i> {{ selectedAssignment?.role_nama }}</span></p></div>
                  <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Tanggal Dibuat</label><p class="text-sm text-gray-900 dark:text-white mt-0.5">{{ selectedAssignment?.created_at }}</p></div>
                </div>
              </div>
            </div>
            <div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
              <button @click="showDetailModal = false; openEdit(selectedAssignment)" class="px-4 py-2 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors"><i class="fas fa-edit mr-1.5"></i> Edit</button>
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
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-plus text-emerald-500 mr-2"></i>Tambah Admin Role</h3>
              <button type="button" @click="showCreateModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button>
            </div>
            <div class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll">
              <FormErrorSummary :errors="createForm.errors" />
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Admin <span class="text-red-500">*</span></label>
                <SearchableSelectAjax v-model="createForm.admin_id" :url="ADMIN_URL" placeholder="— Pilih Admin —" test-id="select-create-admin" :error="!!createForm.errors.admin_id" />
                <p v-if="createForm.errors.admin_id" class="text-red-500 text-xs mt-1">{{ createForm.errors.admin_id }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Role <span class="text-red-500">*</span></label>
                <SearchableSelectAjax v-model="createForm.role_id" :url="ROLE_URL" placeholder="— Pilih Role —" test-id="select-create-role" :error="!!createForm.errors.role_id" />
                <p v-if="createForm.errors.role_id" class="text-red-500 text-xs mt-1">{{ createForm.errors.role_id }}</p>
              </div>
            </div>
            <div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
              <button type="button" @click="showCreateModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button>
              <button type="submit" data-testid="btn-submit-create" :disabled="createForm.processing" class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm disabled:opacity-50"><i class="fas fa-save mr-1.5"></i>Simpan</button>
            </div>
          </form>
        </div>
      </Transition>
    </Teleport>

    <!-- ═══════════════ BULK ASSIGN MODAL ═══════════════ -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showBulkAssignModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showBulkAssignModal = false">
          <div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
          <form @submit.prevent="saveBulkAssign" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col" data-testid="modal-bulk-assign">
            <div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-layer-group text-violet-500 mr-2"></i>Tambah Role Sekaligus</h3>
              <button type="button" @click="showBulkAssignModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button>
            </div>
            <div class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll">
              <FormErrorSummary :errors="bulkAssignForm.errors" />
              <div class="bg-violet-50 dark:bg-violet-900/20 border border-violet-200 dark:border-violet-800 rounded-lg p-3 text-xs text-violet-700 dark:text-violet-300">
                <i class="fas fa-info-circle mr-1.5"></i>Pilih beberapa admin sekaligus, lalu tetapkan satu role untuk mereka semua. Mapping yang sudah ada akan ditimpa (upsert).
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Admin <span class="text-red-500">*</span> <span class="text-xs text-gray-400 font-normal">(multi pilih — {{ bulkAssignForm.admin_ids.length }} dipilih)</span></label>
                <MultiSelectAjax v-model="bulkAssignForm.admin_ids" :url="ADMIN_URL" placeholder="— Pilih Admin —" test-id="multiselect-bulk-admin" :error="!!bulkAssignForm.errors.admin_ids" />
                <p v-if="bulkAssignForm.errors.admin_ids" class="text-red-500 text-xs mt-1">{{ bulkAssignForm.errors.admin_ids }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Role <span class="text-red-500">*</span></label>
                <SearchableSelectAjax v-model="bulkAssignForm.role_id" :url="ROLE_URL" placeholder="— Pilih Role —" test-id="select-bulk-assign-role" :error="!!bulkAssignForm.errors.role_id" />
                <p v-if="bulkAssignForm.errors.role_id" class="text-red-500 text-xs mt-1">{{ bulkAssignForm.errors.role_id }}</p>
              </div>
            </div>
            <div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
              <button type="button" @click="showBulkAssignModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button>
              <button type="submit" data-testid="btn-submit-bulk-assign" :disabled="bulkAssignForm.processing" class="px-6 py-2.5 bg-violet-600 text-white text-sm font-medium rounded-lg hover:bg-violet-700 transition-colors shadow-sm disabled:opacity-50"><i class="fas fa-layer-group mr-1.5"></i>Simpan Sekaligus</button>
            </div>
          </form>
        </div>
      </Transition>
    </Teleport>

    <!-- ═══════════════ BULK UPDATE ROLE MODAL ═══════════════ -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showBulkUpdateRoleModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showBulkUpdateRoleModal = false">
          <div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
          <form @submit.prevent="saveBulkUpdateRole" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] flex flex-col" data-testid="modal-bulk-update-role">
            <div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-user-tag text-amber-500 mr-2"></i>Ubah Role Sekaligus</h3>
              <button type="button" @click="showBulkUpdateRoleModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button>
            </div>
            <div class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll">
              <FormErrorSummary :errors="bulkUpdateRoleForm.errors" />
              <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-3 text-xs text-amber-700 dark:text-amber-300">
                <i class="fas fa-info-circle mr-1.5"></i>Mengubah role untuk <strong>{{ bulkUpdateRoleForm.ids.length }}</strong> mapping terpilih. Role baru akan menimpa role lama.
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Role Baru <span class="text-red-500">*</span></label>
                <SearchableSelectAjax v-model="bulkUpdateRoleForm.role_id" :url="ROLE_URL" placeholder="— Pilih Role —" test-id="select-bulk-update-role" :error="!!bulkUpdateRoleForm.errors.role_id" />
                <p v-if="bulkUpdateRoleForm.errors.role_id" class="text-red-500 text-xs mt-1">{{ bulkUpdateRoleForm.errors.role_id }}</p>
              </div>
            </div>
            <div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
              <button type="button" @click="showBulkUpdateRoleModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button>
              <button type="submit" data-testid="btn-submit-bulk-update-role" :disabled="bulkUpdateRoleForm.processing" class="px-6 py-2.5 bg-amber-600 text-white text-sm font-medium rounded-lg hover:bg-amber-700 transition-colors shadow-sm disabled:opacity-50"><i class="fas fa-user-tag mr-1.5"></i>Ubah Sekaligus</button>
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
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-edit text-sky-500 mr-2"></i>Edit Admin Role</h3>
              <button type="button" @click="showEditModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button>
            </div>
            <div class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll">
              <FormErrorSummary :errors="editForm.errors" />
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Admin</label>
                <p class="px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-300 text-sm">{{ selectedAssignment?.admin_nama }} <span class="text-gray-400">({{ selectedAssignment?.admin_email }})</span></p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Role <span class="text-red-500">*</span></label>
                <SearchableSelectAjax v-model="editForm.role_id" :url="ROLE_URL" placeholder="— Pilih Role —" test-id="select-edit-role" :error="!!editForm.errors.role_id" />
                <p v-if="editForm.errors.role_id" class="text-red-500 text-xs mt-1">{{ editForm.errors.role_id }}</p>
              </div>
            </div>
            <div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
              <button type="button" @click="showEditModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button>
              <button type="submit" :disabled="editForm.processing" class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm disabled:opacity-50"><i class="fas fa-check mr-1.5"></i>Update</button>
            </div>
          </form>
        </div>
      </Transition>
    </Teleport>

    <!-- ═══════════════ DELETE CONFIRMATION MODAL ═══════════════ -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showDeleteModal = false">
          <div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
          <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
            <div class="px-6 py-5 text-center">
              <div class="w-14 h-14 mx-auto rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-4"><i class="fas fa-exclamation-triangle text-red-500 text-xl"></i></div>
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Hapus Mapping?</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400">Anda akan menghapus mapping <strong class="text-gray-700 dark:text-gray-300">{{ selectedAssignment?.admin_nama }} — {{ selectedAssignment?.role_nama }}</strong>.</p>
            </div>
            <div class="flex justify-center gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
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
