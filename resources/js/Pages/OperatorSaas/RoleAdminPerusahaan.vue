<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import OperatorSaasLayout from '@/Layouts/OperatorSaasLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useToast } from '@/Composables/useToast';
import ToastContainer from '@/Components/ToastContainer.vue';

defineOptions({ layout: OperatorSaasLayout });

const props = defineProps({
  assignments: Object,
  filters: Object,
});

const toast = useToast();

// ── Filter & Search State ──
const searchInput = ref(props.filters?.search || '');
const companyFilter = ref(props.filters?.company || '');
const sortField = ref(props.filters?.sort_field || '');
const sortDir = ref(props.filters?.sort_dir || 'asc');
const perPage = ref(props.filters?.per_page && !isNaN(props.filters.per_page) ? Number(props.filters.per_page) : 10);
const perPageOptions = [5, 10, 25, 50, 100];

// ── Build Query ──
function buildQuery(overrides = {}) {
  const params = { ...overrides };
  if (params.search === undefined) params.search = searchInput.value || undefined;
  if (params.company === undefined) params.company = companyFilter.value || undefined;
  if (params.sort_field === undefined) params.sort_field = sortField.value || undefined;
  if (params.sort_dir === undefined) params.sort_dir = sortDir.value || undefined;
  if (params.per_page === undefined) params.per_page = perPage.value;
  Object.keys(params).forEach(k => {
    if (params[k] === undefined || params[k] === '') delete params[k];
  });
  return params;
}

function fetchData(overrides = {}) {
  router.get('/operator-saas/role-admin-perusahaan', buildQuery(overrides), {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}

function applySearch() {
  fetchData({ search: searchInput.value || undefined, page: 1 });
}

function clearSearch() {
  searchInput.value = '';
  fetchData({ search: undefined, page: 1 });
}

function applyCompanyFilter(companyId) {
  companyFilter.value = companyId;
  fetchData({ company: companyId || undefined, page: 1 });
}

function resetFilters() {
  searchInput.value = '';
  companyFilter.value = '';
  fetchData({ search: undefined, company: undefined, page: 1 });
}

// ── Sort ──
function sort(field) {
  if (sortField.value === field) {
    if (sortDir.value === 'asc') {
      sortDir.value = 'desc';
    } else {
      sortField.value = '';
      sortDir.value = 'asc';
    }
  } else {
    sortField.value = field;
    sortDir.value = 'asc';
  }
  fetchData({ sort_field: sortField.value || undefined, sort_dir: sortDir.value, page: 1 });
}

function sortIcon(field) {
  if (sortField.value !== field) return 'fa-sort';
  return sortDir.value === 'asc' ? 'fa-sort-up' : 'fa-sort-down';
}

function sortOrder(field) {
  return sortField.value === field ? 1 : null;
}

// ── Pagination ──
const currentPage = computed(() => props.assignments?.current_page || 1);
const totalPages = computed(() => props.assignments?.last_page || 1);

function changePerPage(n) {
  perPage.value = n;
  fetchData({ per_page: n, page: 1 });
}

function goToPage(page) {
  fetchData({ page: Math.max(1, Math.min(page, totalPages.value)) });
}

const visiblePages = computed(() => {
  const pages = [];
  const total = totalPages.value;
  const current = currentPage.value;
  let start = Math.max(1, current - 2);
  let end = Math.min(total, current + 2);
  if (end - start < 4) {
    if (start === 1) end = Math.min(total, start + 4);
    else start = Math.max(1, end - 4);
  }
  for (let i = start; i <= end; i++) pages.push(i);
  return pages;
});

// ── Bulk Action ──
const selectedIds = ref([]);
const selectAll = ref(false);

const assignmentsList = computed(() => props.assignments?.data || []);

function toggleSelectAll() {
  if (selectAll.value) {
    selectedIds.value = assignmentsList.value.map(a => a.id);
  } else {
    selectedIds.value = [];
  }
}

function toggleSelect(id) {
  const idx = selectedIds.value.indexOf(id);
  if (idx === -1) {
    selectedIds.value.push(id);
  } else {
    selectedIds.value.splice(idx, 1);
  }
  selectAll.value = selectedIds.value.length === assignmentsList.value.length;
}

function bulkDelete() {
  if (selectedIds.value.length === 0) return;
  const count = selectedIds.value.length;
  router.post('/operator-saas/role-admin-perusahaan/bulk-delete', { ids: [...selectedIds.value] }, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      selectedIds.value = [];
      selectAll.value = false;
      toast.success(`${count} penugasan role berhasil dihapus.`);
    },
    onError: () => {
      toast.error('Gagal menghapus penugasan role. Silakan coba lagi.');
    },
  });
}

// ── Helpers ──
function statusBadge(status) {
  return status === 'Aktif'
    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
    : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
}

// ── Company Options (shared for filter + form) ──
const companyOptions = ref([]);
const companySearchText = ref('');

async function loadCompanies(search = '') {
  const { data } = await axios.get('/operator-saas/perusahaan/select-search', {
    params: { search: search || undefined },
  });
  companyOptions.value = data.data || [];
}

function onFilterCompanySearch(search) {
  companySearchText.value = search;
  loadCompanies(search);
}

// ── Form State for Create ──
const createForm = useForm({
  admin_id: '',
  company_id: '',
  role_id: '',
});

// ── Role & Admin Options for Create (loaded when company changes) ──
const createRoleOptions = ref([]);
const createAdminOptions = ref([]);

watch(() => createForm.company_id, async (companyId) => {
  if (companyId) {
    const [rolesRes, adminsRes] = await Promise.all([
      axios.get('/operator-saas/role-admin-perusahaan/roles-by-company', { params: { company_id: companyId } }),
      axios.get('/operator-saas/role-admin-perusahaan/admins-by-company', { params: { company_id: companyId } }),
    ]);
    createRoleOptions.value = rolesRes.data.map(r => ({ value: r.id, label: r.name }));
    createAdminOptions.value = adminsRes.data.map(a => ({ value: a.id, label: `${a.name} (${a.email})` }));
    createForm.admin_id = '';
    createForm.role_id = '';
  } else {
    createRoleOptions.value = [];
    createAdminOptions.value = [];
    createForm.admin_id = '';
    createForm.role_id = '';
  }
});

// ── CRUD State ──
const selectedAssignment = ref(null);
const showCreateModal = ref(false);
const showDetailModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);

// Edit form (only role can be changed)
const editForm = useForm({
  role_id: '',
});
const editRoleOptions = ref([]);

// ── CRUD Actions ──
function openCreate() {
  createForm.reset();
  createForm.clearErrors();
  createRoleOptions.value = [];
  createAdminOptions.value = [];
  showCreateModal.value = true;
}

function openDetail(assignment) {
  selectedAssignment.value = assignment;
  showDetailModal.value = true;
}

function openEdit(assignment) {
  selectedAssignment.value = assignment;
  editForm.reset();
  editForm.clearErrors();
  editForm.role_id = assignment.role_id;
  showEditModal.value = true;

  axios.get('/operator-saas/role-admin-perusahaan/roles-by-company', {
    params: { company_id: assignment.company_id },
  }).then(res => {
    editRoleOptions.value = res.data.map(r => ({ value: r.id, label: r.name }));
  });
}

function openDelete(assignment) {
  selectedAssignment.value = assignment;
  showDeleteModal.value = true;
}

function saveCreate() {
  createForm.post('/operator-saas/role-admin-perusahaan', {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      showCreateModal.value = false;
      toast.success('Role admin perusahaan berhasil ditetapkan.');
    },
    onError: () => {
      toast.error('Validasi gagal. Periksa kembali isian form.');
    },
  });
}

function saveEdit() {
  editForm.put(`/operator-saas/role-admin-perusahaan/${selectedAssignment.value.id}`, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      showEditModal.value = false;
      toast.success('Role admin perusahaan berhasil diperbarui.');
    },
    onError: () => {
      toast.error('Validasi gagal. Periksa kembali isian form.');
    },
  });
}

function confirmDelete() {
  const name = selectedAssignment.value?.admin_nama;
  router.delete(`/operator-saas/role-admin-perusahaan/${selectedAssignment.value?.id}`, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      showDeleteModal.value = false;
      toast.success(`Penugasan role "${name}" berhasil dihapus.`);
      if (assignmentsList.value.length === 0 && currentPage.value > 1) {
        goToPage(currentPage.value - 1);
      }
    },
    onError: () => {
      toast.error('Gagal menghapus penugasan role. Silakan coba lagi.');
    },
  });
}

// ── Init ──
onMounted(() => {
  loadCompanies();
});
</script>

<template>
  <div>
    <Head title="Role Admin Perusahaan | Operator SaaS" />
    <ToastContainer />

    <div class="space-y-6">
      <!-- Breadcrumbs -->
      <nav class="flex items-center gap-1.5 text-sm">
        <Link href="/operator-saas/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"><i class="fas fa-home"></i></Link>
        <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
        <Link href="/operator-saas/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Dashboard</Link>
        <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
        <span class="text-gray-900 dark:text-white font-medium">Role Admin Perusahaan</span>
      </nav>

      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div><h2 class="text-2xl font-bold text-gray-900 dark:text-white">Role Admin Perusahaan</h2><p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Pemetaan role untuk setiap admin di perusahaan tenant.</p></div>
        <button @click="openCreate" class="inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm"><i class="fas fa-plus mr-1.5"></i> Tambah Mapping</button>
      </div>

      <!-- Filters Bar -->
      <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
          <div class="relative w-full sm:w-72">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-search text-gray-400 text-sm"></i></div>
            <input v-model="searchInput" type="text" placeholder="Cari admin, perusahaan, role..." class="w-full pl-10 pr-16 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors" @keydown.enter="applySearch" />
            <div class="absolute inset-y-0 right-0 flex items-center gap-1 pr-1.5">
              <button v-if="searchInput" @click="clearSearch" class="p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-white transition-colors" title="Clear"><i class="fas fa-times text-xs"></i></button>
              <button @click="applySearch" class="px-2 py-1 rounded bg-indigo-600 text-white hover:bg-indigo-700 transition-colors" title="Cari"><i class="fas fa-search text-xs"></i></button>
            </div>
          </div>
          <SearchableSelect :model-value="companyFilter" :options="companyOptions" placeholder="Semua Perusahaan" @update:model-value="applyCompanyFilter" @search="onFilterCompanySearch" />
        </div>
        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400"><span>{{ props.assignments?.total || 0 }} data</span><button v-if="searchInput || companyFilter" @click="resetFilters" class="text-xs text-red-500 hover:text-red-700 dark:hover:text-red-400 underline">Reset filter</button></div>
      </div>

      <!-- Bulk Action Bar -->
      <div v-if="selectedIds.length > 0" class="flex items-center justify-between px-4 py-3 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-xl shadow-sm">
        <span class="text-sm font-medium text-indigo-700 dark:text-indigo-300"><i class="fas fa-check-circle mr-1.5"></i> {{ selectedIds.length }} data dipilih</span>
        <div class="flex items-center gap-2">
          <button @click="bulkDelete()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-red-600 text-white hover:bg-red-700 transition-colors"><i class="fas fa-trash-alt mr-1"></i> Hapus</button>
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm min-w-[700px]">
            <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
              <tr>
                <th class="px-4 py-3 w-10"><input v-model="selectAll" type="checkbox" @change="toggleSelectAll" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500" /></th>
                <th @click="sort('admin_nama')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none hover:text-gray-900 dark:hover:text-white transition-colors">
                  <span class="inline-flex items-center gap-1">
                    Admin
                    <span v-if="sortOrder('admin_nama')" class="inline-flex items-center gap-0.5 text-indigo-500 dark:text-indigo-400"><i :class="['fas', sortIcon('admin_nama'), 'text-[10px]']"></i><span class="text-[10px] font-bold leading-none">{{ sortOrder('admin_nama') }}</span></span>
                    <i v-else class="fas fa-sort text-[10px] text-gray-400 dark:text-gray-500"></i>
                  </span>
                </th>
                <th @click="sort('perusahaan')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none hover:text-gray-900 dark:hover:text-white transition-colors">
                  <span class="inline-flex items-center gap-1">
                    Perusahaan
                    <span v-if="sortOrder('perusahaan')" class="inline-flex items-center gap-0.5 text-indigo-500 dark:text-indigo-400"><i :class="['fas', sortIcon('perusahaan'), 'text-[10px]']"></i><span class="text-[10px] font-bold leading-none">{{ sortOrder('perusahaan') }}</span></span>
                    <i v-else class="fas fa-sort text-[10px] text-gray-400 dark:text-gray-500"></i>
                  </span>
                </th>
                <th @click="sort('role_nama')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none hover:text-gray-900 dark:hover:text-white transition-colors">
                  <span class="inline-flex items-center gap-1">
                    Role
                    <span v-if="sortOrder('role_nama')" class="inline-flex items-center gap-0.5 text-indigo-500 dark:text-indigo-400"><i :class="['fas', sortIcon('role_nama'), 'text-[10px]']"></i><span class="text-[10px] font-bold leading-none">{{ sortOrder('role_nama') }}</span></span>
                    <i v-else class="fas fa-sort text-[10px] text-gray-400 dark:text-gray-500"></i>
                  </span>
                </th>
                <th @click="sort('admin_status')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none hover:text-gray-900 dark:hover:text-white transition-colors">
                  <span class="inline-flex items-center gap-1">
                    Status
                    <span v-if="sortOrder('admin_status')" class="inline-flex items-center gap-0.5 text-indigo-500 dark:text-indigo-400"><i :class="['fas', sortIcon('admin_status'), 'text-[10px]']"></i><span class="text-[10px] font-bold leading-none">{{ sortOrder('admin_status') }}</span></span>
                    <i v-else class="fas fa-sort text-[10px] text-gray-400 dark:text-gray-500"></i>
                  </span>
                </th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-400 w-32">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-if="assignmentsList.length === 0"><td colspan="6" class="px-4 py-16 text-center text-gray-500 dark:text-gray-400"><i class="fas fa-user-gear text-3xl mb-2 block opacity-40"></i>Tidak ada data role admin perusahaan.</td></tr>
              <tr v-for="a in assignmentsList" :key="a.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                <td class="px-4 py-3"><input :checked="selectedIds.includes(a.id)" type="checkbox" @change="toggleSelect(a.id)" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500" /></td>
                <td class="px-4 py-3">
                  <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-xs font-bold shrink-0">{{ a.admin_nama?.charAt(0) }}</div>
                    <div>
                      <span class="font-medium text-gray-900 dark:text-white whitespace-nowrap block">{{ a.admin_nama }}</span>
                      <span class="text-xs text-gray-400 dark:text-gray-500">{{ a.admin_email }}</span>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap">{{ a.perusahaan }}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap"><span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400"><i class="fas fa-tag text-[10px]"></i> {{ a.role_nama }}</span></td>
                <td class="px-4 py-3 whitespace-nowrap"><span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium', statusBadge(a.admin_status)]">{{ a.admin_status }}</span></td>
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

        <!-- Pagination -->
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
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-eye mr-2 text-indigo-500"></i>Detail Role Admin</h3>
              <button @click="showDetailModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button>
            </div>
            <div class="overflow-y-auto flex-1 px-6 py-5 space-y-5 modal-scroll">
              <div class="flex items-center gap-4 pb-4 border-b border-gray-100 dark:border-gray-700">
                <div class="w-16 h-16 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-2xl font-bold shrink-0">{{ selectedAssignment?.admin_nama?.charAt(0) }}</div>
                <div>
                  <h4 class="text-xl font-bold text-gray-900 dark:text-white">{{ selectedAssignment?.admin_nama }}</h4>
                  <span :class="['inline-flex mt-1 px-2.5 py-0.5 rounded-full text-xs font-medium', statusBadge(selectedAssignment?.admin_status)]">{{ selectedAssignment?.admin_status }}</span>
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Email Admin</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ selectedAssignment?.admin_email }}</p></div>
                <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Perusahaan</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ selectedAssignment?.perusahaan }}</p></div>
                <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Role</label><p class="text-sm text-gray-900 dark:text-white mt-1"><span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400"><i class="fas fa-tag text-[10px]"></i> {{ selectedAssignment?.role_nama }}</span></p></div>
                <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Dibuat</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ selectedAssignment?.created_at }}</p></div>
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
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-plus text-emerald-500 mr-2"></i>Tambah Role Admin</h3>
              <button type="button" @click="showCreateModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button>
            </div>
            <div class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Perusahaan <span class="text-red-500">*</span></label>
                <SearchableSelect v-model="createForm.company_id" :options="companyOptions" placeholder="— Pilih Perusahaan —" @search="onFilterCompanySearch" />
                <p v-if="createForm.errors.company_id" class="text-red-500 text-xs mt-1">{{ createForm.errors.company_id }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Admin <span class="text-red-500">*</span></label>
                <SearchableSelect v-model="createForm.admin_id" :options="createAdminOptions" :placeholder="createForm.company_id ? '— Pilih Admin —' : 'Pilih perusahaan dulu'" :disabled="!createForm.company_id" />
                <p v-if="createForm.errors.admin_id" class="text-red-500 text-xs mt-1">{{ createForm.errors.admin_id }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Role <span class="text-red-500">*</span></label>
                <SearchableSelect v-model="createForm.role_id" :options="createRoleOptions" :placeholder="createForm.company_id ? '— Pilih Role —' : 'Pilih perusahaan dulu'" :disabled="!createForm.company_id" />
                <p v-if="createForm.errors.role_id" class="text-red-500 text-xs mt-1">{{ createForm.errors.role_id }}</p>
              </div>
            </div>
            <div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
              <button type="button" @click="showCreateModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button>
              <button type="submit" :disabled="createForm.processing" class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm disabled:opacity-50">
                <i class="fas fa-save mr-1.5"></i>Simpan
              </button>
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
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-edit text-sky-500 mr-2"></i>Edit Role Admin</h3>
              <button type="button" @click="showEditModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button>
            </div>
            <div class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll">
              <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-xs font-bold shrink-0">{{ selectedAssignment?.admin_nama?.charAt(0) }}</div>
                <div>
                  <p class="text-sm font-medium text-gray-900 dark:text-white">{{ selectedAssignment?.admin_nama }}</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">{{ selectedAssignment?.admin_email }}</p>
                </div>
              </div>
              <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white text-xs font-bold shrink-0"><i class="fas fa-building"></i></div>
                <div>
                  <p class="text-sm font-medium text-gray-900 dark:text-white">{{ selectedAssignment?.perusahaan }}</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">Perusahaan</p>
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Role <span class="text-red-500">*</span></label>
                <SearchableSelect v-model="editForm.role_id" :options="editRoleOptions" placeholder="— Pilih Role —" />
                <p v-if="editForm.errors.role_id" class="text-red-500 text-xs mt-1">{{ editForm.errors.role_id }}</p>
              </div>
            </div>
            <div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
              <button type="button" @click="showEditModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button>
              <button type="submit" :disabled="editForm.processing" class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm disabled:opacity-50">
                <i class="fas fa-check mr-1.5"></i>Update
              </button>
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
