<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import OperatorSaasLayout from '@/Layouts/OperatorSaasLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import FormErrorSummary from '@/Components/FormErrorSummary.vue';
import { useToast } from '@/Composables/useToast';
import { errorSummary } from '@/Composables/useFormErrorToast';
import ToastContainer from '@/Components/ToastContainer.vue';

defineOptions({ layout: OperatorSaasLayout });

const props = defineProps({
  roles: Object,
  availablePermissions: Array,
  filters: Object,
});

const toast = useToast();

// ── Permission Groups (derived from flat permissions array) ──
const permissionGroups = computed(() => {
  const groups = {};
  (props.availablePermissions || []).forEach(p => {
    const parts = p.nama.split('.');
    const module = parts.length > 1 ? parts.slice(0, -1).join(' ') : parts[0];
    const moduleLabel = module.replace(/-/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    if (!groups[moduleLabel]) groups[moduleLabel] = [];
    groups[moduleLabel].push({ id: p.id, key: p.nama, label: parts[parts.length - 1]?.replace(/-/g, ' ') || p.nama, deskripsi: p.deskripsi });
  });
  return Object.entries(groups).map(([module, perms]) => ({ module, permissions: perms }));
});

// ── Company Options (AJAX searchable select) ──
const companyOptions = ref([]);
const companyPage = ref(1);
const companySearch = ref('');

function searchCompanies(query) {
  companySearch.value = query || '';
  companyPage.value = 1;
  axios.get('/operator-saas/perusahaan/select-search', {
    params: { search: companySearch.value, page: 1 },
  }).then(res => {
    companyOptions.value = res.data.data || [];
  });
}

function loadMoreCompanies() {
  companyPage.value++;
  axios.get('/operator-saas/perusahaan/select-search', {
    params: { search: companySearch.value, page: companyPage.value },
  }).then(res => {
    const newList = res.data.data || [];
    if (newList.length) companyOptions.value = [...companyOptions.value, ...newList];
  });
}

function loadInitialCompanies() {
  axios.get('/operator-saas/perusahaan/select-search', { params: { page: 1 } })
    .then(res => { companyOptions.value = res.data.data || []; });
}

loadInitialCompanies();

// ── Search & Filter State (synced from props.filters) ──
const searchInput = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const companyFilter = ref(props.filters?.company || '');
const terhapusFilter = ref(props.filters?.terhapus || 'tidak');
const sortField = ref(props.filters?.sort_field || '');
const sortDir = ref(props.filters?.sort_dir || 'asc');
const perPage = ref(props.filters?.per_page && !isNaN(props.filters.per_page) ? Number(props.filters.per_page) : 10);
const perPageOptions = [5, 10, 25, 50, 100];

function buildQuery(overrides = {}) {
  const params = { ...overrides };
  if (params.search === undefined) params.search = searchInput.value || undefined;
  if (params.status === undefined) params.status = statusFilter.value || undefined;
  if (params.company === undefined) params.company = companyFilter.value || undefined;
  if (params.terhapus === undefined) params.terhapus = terhapusFilter.value || undefined;
  if (params.sort_field === undefined) params.sort_field = sortField.value || undefined;
  if (params.sort_dir === undefined) params.sort_dir = sortDir.value || undefined;
  if (params.per_page === undefined) params.per_page = perPage.value;
  Object.keys(params).forEach(k => {
    if (params[k] === undefined || params[k] === '') delete params[k];
  });
  return params;
}

function fetchData(overrides = {}) {
  router.get('/operator-saas/role-perusahaan', buildQuery(overrides), {
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

function applyFilters() {
  fetchData({ page: 1 });
}

function resetFilters() {
  searchInput.value = '';
  statusFilter.value = '';
  companyFilter.value = '';
  companyOptions.value = [];
  terhapusFilter.value = 'tidak';
  loadInitialCompanies();
  fetchData({ search: undefined, status: undefined, company: undefined, terhapus: 'tidak', page: 1 });
}

// ── Sort ──
function sort(field) {
  if (sortField.value === field) {
    if (sortDir.value === 'asc') { sortDir.value = 'desc'; }
    else { sortField.value = ''; sortDir.value = 'asc'; }
  } else { sortField.value = field; sortDir.value = 'asc'; }
  fetchData({ sort_field: sortField.value || undefined, sort_dir: sortDir.value, page: 1 });
}

function sortIcon(field) {
  if (sortField.value !== field) return 'fa-sort';
  return sortDir.value === 'asc' ? 'fa-sort-up' : 'fa-sort-down';
}

function sortOrder(field) {
  return sortField.value === field ? 1 : null;
}

// ── Pagination (server-driven) ──
const currentPage = computed(() => props.roles?.current_page || 1);
const totalPages = computed(() => props.roles?.last_page || 1);
const rolesList = computed(() => props.roles?.data || []);

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

function toggleSelectAll() {
  if (selectAll.value) {
    selectedIds.value = rolesList.value.filter(r => !r.dihapus).map(r => r.id);
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
  selectAll.value = selectedIds.value.length === rolesList.value.filter(r => !r.dihapus).length;
}

function bulkDelete() {
  if (selectedIds.value.length === 0) return;
  const count = selectedIds.value.length;
  router.post('/operator-saas/role-perusahaan/bulk-delete', { ids: [...selectedIds.value] }, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      selectedIds.value = [];
      selectAll.value = false;
      toast.success(`${count} role berhasil dihapus.`);
    },
    onError: () => {
      toast.error('Gagal menghapus role. Silakan coba lagi.');
    },
  });
}

function bulkSetStatus(status) {
  if (selectedIds.value.length === 0) return;
  const count = selectedIds.value.length;
  router.post('/operator-saas/role-perusahaan/bulk-status', { ids: [...selectedIds.value], status }, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      selectedIds.value = [];
      selectAll.value = false;
      toast.success(`${count} role berhasil ${status === 'Aktif' ? 'diaktifkan' : 'dinonaktifkan'}.`);
    },
    onError: () => {
      toast.error('Gagal mengubah status role. Silakan coba lagi.');
    },
  });
}

function restoreRole(role) {
  router.post(`/operator-saas/role-perusahaan/${role.id}/restore`, {}, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      toast.success(`Role "${role.nama_role}" berhasil dipulihkan.`);
    },
    onError: () => {
      toast.error('Gagal memulihkan role. Silakan coba lagi.');
    },
  });
}

// ── Helpers ──
function statusBadge(s) {
  return s === 'Aktif'
    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
    : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
}

// ── Permission Checkbox Helpers ──
function isPermChecked(permissionIds, key) {
  return (permissionIds || []).includes(key);
}

function togglePerm(arr, key) {
  const idx = arr.indexOf(key);
  if (idx === -1) arr.push(key);
  else arr.splice(idx, 1);
}

function toggleGroupPerms(group, targetArr) {
  const keys = group.permissions.map(p => p.id);
  const allChecked = keys.every(k => targetArr.includes(k));
  if (allChecked) {
    keys.forEach(k => {
      const idx = targetArr.indexOf(k);
      if (idx !== -1) targetArr.splice(idx, 1);
    });
  } else {
    keys.forEach(k => {
      if (!targetArr.includes(k)) targetArr.push(k);
    });
  }
}

function isGroupAllChecked(group, targetArr) {
  return group.permissions.every(p => targetArr.includes(p.id));
}

function isGroupPartialChecked(group, targetArr) {
  const checked = group.permissions.filter(p => targetArr.includes(p.id)).length;
  return checked > 0 && checked < group.permissions.length;
}

// ── CRUD State ──
const selectedRole = ref(null);
const showCreateModal = ref(false);
const showDetailModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);

const createForm = useForm({
  nama_role: '',
  company_id: '',
  deskripsi: '',
  status: 'Aktif',
  permission_ids: [],
});

const editForm = useForm({
  id: null,
  nama_role: '',
  company_id: '',
  deskripsi: '',
  status: 'Aktif',
  permission_ids: [],
});

function openCreate() {
  createForm.reset();
  createForm.clearErrors();
  showCreateModal.value = true;
}

function openDetail(role) {
  selectedRole.value = role;
  showDetailModal.value = true;
}

function openEdit(role) {
  editForm.reset();
  editForm.clearErrors();
  editForm.id = role.id;
  editForm.nama_role = role.nama_role;
  editForm.company_id = role.company_id || '';
  editForm.deskripsi = role.deskripsi || '';
  editForm.status = role.status;
  editForm.permission_ids = role.permission_ids ? [...role.permission_ids] : [];
  if (role.company_id && role.perusahaan) {
    const exists = companyOptions.value.find(o => o.value === role.company_id);
    if (!exists) companyOptions.value = [{ value: role.company_id, label: role.perusahaan }, ...companyOptions.value];
  }
  showEditModal.value = true;
}

function openDelete(role) {
  selectedRole.value = role;
  showDeleteModal.value = true;
}

function saveCreate() {
  createForm.post('/operator-saas/role-perusahaan', {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      showCreateModal.value = false;
      toast.success('Role perusahaan berhasil ditambahkan.');
    },
    onError: () => {
      toast.error('Validasi gagal: ' + errorSummary(createForm.errors), 6000);
    },
  });
}

function saveEdit() {
  editForm.put(`/operator-saas/role-perusahaan/${editForm.id}`, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      showEditModal.value = false;
      toast.success('Role perusahaan berhasil diperbarui.');
    },
    onError: () => {
      toast.error('Validasi gagal: ' + errorSummary(editForm.errors), 6000);
    },
  });
}

function confirmDelete() {
  const name = selectedRole.value?.nama_role;
  router.delete(`/operator-saas/role-perusahaan/${selectedRole.value?.id}`, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      showDeleteModal.value = false;
      toast.success(`Role "${name}" berhasil dihapus.`);
      if (rolesList.value.length === 0 && currentPage.value > 1) {
        goToPage(currentPage.value - 1);
      }
    },
    onError: () => {
      toast.error('Gagal menghapus role. Silakan coba lagi.');
    },
  });
}
</script>

<template>
  <div>
    <Head title="Role Perusahaan | Operator SaaS" />
    <ToastContainer />

    <div class="space-y-6">
      <!-- Breadcrumbs -->
      <nav class="flex items-center gap-1.5 text-sm">
        <Link href="/operator-saas/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"><i class="fas fa-home"></i></Link>
        <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
        <Link href="/operator-saas/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Dashboard</Link>
        <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
        <span class="text-gray-900 dark:text-white font-medium">Role Perusahaan</span>
      </nav>

      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div><h2 class="text-2xl font-bold text-gray-900 dark:text-white">Role Perusahaan</h2><p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Kelola role dan permission untuk setiap perusahaan tenant.</p></div>
        <button @click="openCreate" class="inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm" data-testid="btn-tambah"><i class="fas fa-plus mr-1.5"></i> Tambah Role</button>
      </div>

      <!-- Search Bar -->
      <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
        <div class="relative w-full sm:w-72">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-search text-gray-400 text-sm"></i></div>
          <input v-model="searchInput" type="text" placeholder="Cari role..." class="w-full pl-10 pr-16 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors" @keydown.enter="applySearch" data-testid="input-search">
          <div class="absolute inset-y-0 right-0 flex items-center gap-1 pr-1.5">
            <button v-if="searchInput" @click="clearSearch" class="p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-white transition-colors" title="Clear"><i class="fas fa-times text-xs"></i></button>
            <button @click="applySearch" class="px-2 py-1 rounded bg-indigo-600 text-white hover:bg-indigo-700 transition-colors" title="Cari"><i class="fas fa-search text-xs"></i></button>
          </div>
        </div>
        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400"><span>{{ props.roles?.total || 0 }} data</span><button v-if="statusFilter || companyFilter || terhapusFilter !== 'tidak' || searchInput" @click="resetFilters" class="text-xs text-red-500 hover:text-red-700 dark:hover:text-red-400 underline">Reset filter</button></div>
      </div>

      <!-- Filter Card -->
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm">
        <div class="flex flex-wrap items-end gap-4">
          <div class="min-w-[200px]">
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Perusahaan</label>
            <SearchableSelect
              :model-value="companyFilter"
              :options="companyOptions"
              placeholder="Semua Perusahaan"
              @update:model-value="companyFilter = $event; applyFilters()"
              @search="searchCompanies"
              @load-more="loadMoreCompanies"
            />
          </div>
          <div class="min-w-[160px]">
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Status</label>
            <select v-model="statusFilter" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
              <option value="">Semua</option>
              <option value="Aktif">Aktif</option>
              <option value="Nonaktif">Nonaktif</option>
            </select>
          </div>
          <div class="min-w-[160px]">
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Terhapus</label>
            <select v-model="terhapusFilter" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
              <option value="tidak">Tidak</option>
              <option value="ya">Ya</option>
            </select>
          </div>
          <button @click="applyFilters" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm"><i class="fas fa-filter mr-1.5"></i>Filter</button>
        </div>
      </div>

      <!-- Bulk Action Bar -->
      <div v-if="selectedIds.length > 0" class="flex items-center justify-between px-4 py-3 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-xl shadow-sm">
        <span class="text-sm font-medium text-indigo-700 dark:text-indigo-300"><i class="fas fa-check-circle mr-1.5"></i> {{ selectedIds.length }} data dipilih</span>
        <div class="flex items-center gap-2">
          <button @click="bulkSetStatus('Aktif')" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition-colors"><i class="fas fa-check mr-1"></i> Aktifkan</button>
          <button @click="bulkSetStatus('Nonaktif')" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-amber-600 text-white hover:bg-amber-700 transition-colors"><i class="fas fa-ban mr-1"></i> Nonaktifkan</button>
          <button @click="bulkDelete()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-red-600 text-white hover:bg-red-700 transition-colors"><i class="fas fa-trash-alt mr-1"></i> Hapus</button>
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table data-testid="table-data" class="w-full text-sm min-w-[700px]">
            <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
              <tr>
                <th class="px-4 py-3 w-10"><input v-model="selectAll" type="checkbox" @change="toggleSelectAll" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500" data-testid="checkbox-select-all" /></th>
                <th @click="sort('name')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none hover:text-gray-900 dark:hover:text-white transition-colors">
                  <span class="inline-flex items-center gap-1">Nama Role
                    <span v-if="sortOrder('name')" class="inline-flex items-center gap-0.5 text-indigo-500 dark:text-indigo-400"><i :class="['fas', sortIcon('name'), 'text-[10px]']"></i><span class="text-[10px] font-bold leading-none">{{ sortOrder('name') }}</span></span>
                    <i v-else class="fas fa-sort text-[10px] text-gray-400 dark:text-gray-500"></i>
                  </span>
                </th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Perusahaan</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Permission</th>
                <th @click="sort('is_active')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none hover:text-gray-900 dark:hover:text-white transition-colors">
                  <span class="inline-flex items-center gap-1">Status
                    <span v-if="sortOrder('is_active')" class="inline-flex items-center gap-0.5 text-indigo-500 dark:text-indigo-400"><i :class="['fas', sortIcon('is_active'), 'text-[10px]']"></i><span class="text-[10px] font-bold leading-none">{{ sortOrder('is_active') }}</span></span>
                    <i v-else class="fas fa-sort text-[10px] text-gray-400 dark:text-gray-500"></i>
                  </span>
                </th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-400 w-32">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-if="rolesList.length === 0"><td colspan="6" class="px-4 py-16 text-center text-gray-500 dark:text-gray-400"><i class="fas fa-tags text-3xl mb-2 block opacity-40"></i>Tidak ada data role perusahaan.</td></tr>
              <tr v-for="r in rolesList" :key="r.id" :class="['transition-colors', r.dihapus ? 'bg-red-50/30 dark:bg-red-900/10 opacity-60' : 'hover:bg-gray-50 dark:hover:bg-gray-700/30']">
                <td class="px-4 py-3">
                  <input v-if="!r.dihapus" :checked="selectedIds.includes(r.id)" type="checkbox" @change="toggleSelect(r.id)" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500" />
                </td>
                <td class="px-4 py-3"><div class="flex items-center gap-2.5"><div class="w-8 h-8 rounded-lg bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white text-xs font-bold shrink-0"><i class="fas fa-tag text-[10px]"></i></div><span class="font-medium text-gray-900 dark:text-white whitespace-nowrap" :class="{ 'line-through': r.dihapus }">{{ r.nama_role }}</span></div></td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap" :class="{ 'line-through': r.dihapus }">{{ r.perusahaan || '—' }}</td>
                <td class="px-4 py-3"><span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400 whitespace-nowrap"><i class="fas fa-shield-alt text-[10px]"></i> {{ r.permission_count || 0 }} akses</span></td>
                <td class="px-4 py-3 whitespace-nowrap">
                  <span v-if="r.dihapus" class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">Terhapus</span>
                  <span v-else :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium', statusBadge(r.status)]">{{ r.status }}</span>
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                  <div class="flex items-center justify-end gap-1">
                    <button @click="openDetail(r)" class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors" title="Detail"><i class="fas fa-eye text-sm"></i></button>
                    <template v-if="r.dihapus">
                      <button @click="restoreRole(r)" class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors" title="Pulihkan"><i class="fas fa-undo-alt text-sm"></i></button>
                    </template>
                    <template v-else>
                      <button @click="openEdit(r)" class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-sky-600 dark:hover:text-sky-400 transition-colors" title="Edit"><i class="fas fa-edit text-sm"></i></button>
                      <button @click="openDelete(r)" class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-red-600 dark:hover:text-red-400 transition-colors" title="Hapus"><i class="fas fa-trash-alt text-sm"></i></button>
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
            <select :value="perPage" @change="changePerPage(Number($event.target.value))" class="min-w-[65px] px-2 py-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
              <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }}</option>
            </select>
            <span>dari {{ props.roles?.total || 0 }} data</span>
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
          <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-xl max-h-[90vh] flex flex-col">
            <div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-eye mr-2 text-indigo-500"></i>Detail Role</h3>
              <button @click="showDetailModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button>
            </div>
            <div class="overflow-y-auto flex-1 px-6 py-5 space-y-5 modal-scroll">
              <div class="flex items-center gap-4 pb-4 border-b border-gray-100 dark:border-gray-700">
                <div class="w-16 h-16 rounded-lg bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white text-xl font-bold shrink-0"><i class="fas fa-tag"></i></div>
                <div>
                  <h4 class="text-xl font-bold text-gray-900 dark:text-white">{{ selectedRole?.nama_role }}</h4>
                  <span v-if="selectedRole?.dihapus" class="inline-flex mt-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">Terhapus</span>
                  <span v-else :class="['inline-flex mt-1 px-2.5 py-0.5 rounded-full text-xs font-medium', statusBadge(selectedRole?.status)]">{{ selectedRole?.status }}</span>
                </div>
              </div>

              <!-- Section: Info Dasar -->
              <div>
                <h5 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Informasi Role</h5>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Perusahaan</label><p class="text-sm text-gray-900 dark:text-white mt-0.5">{{ selectedRole?.perusahaan || '—' }}</p></div>
                  <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Permission</label><p class="text-sm text-gray-900 dark:text-white mt-0.5"><span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400"><i class="fas fa-shield-alt text-[10px]"></i> {{ selectedRole?.permission_count || 0 }} akses</span></p></div>
                  <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Display Order</label><p class="text-sm text-gray-900 dark:text-white mt-0.5">{{ selectedRole?.display_order ?? '—' }}</p></div>
                </div>
                <div class="mt-3">
                  <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Deskripsi</label>
                  <p class="text-sm text-gray-900 dark:text-white mt-0.5">{{ selectedRole?.deskripsi || '—' }}</p>
                </div>
              </div>

              <!-- Section: Permission -->
              <div v-if="selectedRole?.permission_names?.length">
                <h5 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Permission ({{ selectedRole.permission_count }})</h5>
                <div class="border border-gray-200 dark:border-gray-700 rounded-xl divide-y divide-gray-200 dark:divide-gray-700 max-h-64 overflow-y-auto">
                  <div v-for="(perms, module) in selectedRole.permission_names.reduce((acc, name) => { const m = name.includes('.') ? name.substring(0, name.lastIndexOf('.')) : name; (acc[m] = acc[m] || []).push(name); return acc; }, {})" :key="module" class="px-3 py-2">
                    <div class="text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">{{ module }}</div>
                    <div class="flex flex-wrap gap-1">
                      <span v-for="name in perms" :key="name" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400"><i class="fas fa-check-circle text-[9px]"></i> {{ name }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Section: Riwayat -->
              <div>
                <h5 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Riwayat</h5>
                <div class="space-y-2.5">
                  <div class="flex items-start gap-3">
                    <div class="w-7 h-7 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center shrink-0 mt-0.5"><i class="fas fa-plus text-emerald-600 dark:text-emerald-400 text-xs"></i></div>
                    <div>
                      <p class="text-sm text-gray-900 dark:text-white">Dibuat</p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">{{ selectedRole?.created_at }}</p>
                      <p v-if="selectedRole?.created_by" class="text-xs text-gray-400 dark:text-gray-500">oleh {{ selectedRole?.created_by }}</p>
                    </div>
                  </div>
                  <div class="flex items-start gap-3">
                    <div class="w-7 h-7 rounded-full bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center shrink-0 mt-0.5"><i class="fas fa-pen text-sky-600 dark:text-sky-400 text-xs"></i></div>
                    <div>
                      <p class="text-sm text-gray-900 dark:text-white">Terakhir diperbarui</p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">{{ selectedRole?.updated_at }}</p>
                      <p v-if="selectedRole?.updated_by" class="text-xs text-gray-400 dark:text-gray-500">oleh {{ selectedRole?.updated_by }}</p>
                    </div>
                  </div>
                  <template v-if="selectedRole?.dihapus">
                    <div class="flex items-start gap-3">
                      <div class="w-7 h-7 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center shrink-0 mt-0.5"><i class="fas fa-trash-alt text-red-600 dark:text-red-400 text-xs"></i></div>
                      <div>
                        <p class="text-sm text-gray-900 dark:text-white">Dihapus</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ selectedRole?.deleted_at }}</p>
                        <p v-if="selectedRole?.deleted_by" class="text-xs text-gray-400 dark:text-gray-500">oleh {{ selectedRole?.deleted_by }}</p>
                      </div>
                    </div>
                  </template>
                  <template v-if="selectedRole?.restored_at">
                    <div class="flex items-start gap-3">
                      <div class="w-7 h-7 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center shrink-0 mt-0.5"><i class="fas fa-undo-alt text-emerald-600 dark:text-emerald-400 text-xs"></i></div>
                      <div>
                        <p class="text-sm text-gray-900 dark:text-white">Dipulihkan</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ selectedRole?.restored_at }}</p>
                        <p v-if="selectedRole?.restored_by" class="text-xs text-gray-400 dark:text-gray-500">oleh {{ selectedRole?.restored_by }}</p>
                      </div>
                    </div>
                  </template>
                </div>
              </div>
            </div>
            <div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
              <button v-if="!selectedRole?.dihapus" @click="showDetailModal = false; openEdit(selectedRole)" class="px-4 py-2 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors"><i class="fas fa-edit mr-1.5"></i> Edit</button>
              <button v-if="selectedRole?.dihapus" @click="showDetailModal = false; restoreRole(selectedRole)" class="px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors"><i class="fas fa-undo-alt mr-1.5"></i> Pulihkan</button>
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
          <form @submit.prevent="saveCreate" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col">
            <div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-plus text-emerald-500 mr-2"></i>Tambah Role Perusahaan</h3>
              <button type="button" @click="showCreateModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button>
            </div>
            <div class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll">
              <FormErrorSummary :errors="createForm.errors" />
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nama Role <span class="text-red-500">*</span></label>
                <input v-model="createForm.nama_role" type="text" placeholder="Contoh: Admin Keuangan" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', createForm.errors.nama_role ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500']" />
                <p v-if="createForm.errors.nama_role" class="text-red-500 text-xs mt-1">{{ createForm.errors.nama_role }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Perusahaan <span class="text-red-500">*</span></label>
                <SearchableSelect v-model="createForm.company_id" :options="companyOptions" placeholder="— Pilih Perusahaan —" @search="searchCompanies" @load-more="loadMoreCompanies" :error="!!createForm.errors.company_id" />
                <p v-if="createForm.errors.company_id" class="text-red-500 text-xs mt-1">{{ createForm.errors.company_id }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Deskripsi</label>
                <textarea v-model="createForm.deskripsi" rows="2" placeholder="Deskripsi role (opsional)" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors resize-none bg-white dark:bg-gray-900 text-gray-900 dark:text-white', createForm.errors.deskripsi ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500']"></textarea>
                <p v-if="createForm.errors.deskripsi" class="text-red-500 text-xs mt-1">{{ createForm.errors.deskripsi }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Status <span class="text-red-500">*</span></label>
                <select v-model="createForm.status" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', createForm.errors.status ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500']">
                  <option value="Aktif">Aktif</option>
                  <option value="Nonaktif">Nonaktif</option>
                </select>
                <p v-if="createForm.errors.status" class="text-red-500 text-xs mt-1">{{ createForm.errors.status }}</p>
              </div>
              <!-- Permission Section -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Permission <span class="text-xs text-gray-400 font-normal ml-1">({{ createForm.permission_ids.length }} dipilih)</span></label>
                <div class="space-y-3 border border-gray-200 dark:border-gray-700 rounded-xl p-4 max-h-64 overflow-y-auto">
                  <div v-for="group in permissionGroups" :key="group.module" class="border-b border-gray-100 dark:border-gray-700 last:border-b-0 pb-3 last:pb-0">
                    <label class="flex items-center gap-2 cursor-pointer select-none py-1">
                      <input type="checkbox" :checked="isGroupAllChecked(group, createForm.permission_ids)" :indeterminate="isGroupPartialChecked(group, createForm.permission_ids)" @change="toggleGroupPerms(group, createForm.permission_ids)" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500" />
                      <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ group.module }}</span>
                    </label>
                    <div class="flex flex-wrap gap-1.5 ml-6 mt-1.5">
                      <label v-for="p in group.permissions" :key="p.id" class="flex items-center gap-1.5 cursor-pointer select-none">
                        <input type="checkbox" :checked="createForm.permission_ids.includes(p.id)" @change="togglePerm(createForm.permission_ids, p.id)" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500" />
                        <span class="text-xs text-gray-600 dark:text-gray-400">{{ p.label }}</span>
                      </label>
                    </div>
                  </div>
                </div>
                <p v-if="createForm.errors.permission_ids" class="text-red-500 text-xs mt-1">{{ createForm.errors.permission_ids }}</p>
              </div>
            </div>
            <div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
              <button type="button" @click="showCreateModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button>
              <button type="submit" :disabled="createForm.processing" class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm disabled:opacity-50"><i class="fas fa-save mr-1.5"></i>Simpan</button>
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
          <form @submit.prevent="saveEdit" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col">
            <div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-edit text-sky-500 mr-2"></i>Edit Role Perusahaan</h3>
              <button type="button" @click="showEditModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button>
            </div>
            <div class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll">
              <FormErrorSummary :errors="editForm.errors" />
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nama Role <span class="text-red-500">*</span></label>
                <input v-model="editForm.nama_role" type="text" placeholder="Contoh: Admin Keuangan" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', editForm.errors.nama_role ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500']" />
                <p v-if="editForm.errors.nama_role" class="text-red-500 text-xs mt-1">{{ editForm.errors.nama_role }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Perusahaan <span class="text-red-500">*</span></label>
                <SearchableSelect v-model="editForm.company_id" :options="companyOptions" placeholder="— Pilih Perusahaan —" @search="searchCompanies" @load-more="loadMoreCompanies" :error="!!editForm.errors.company_id" />
                <p v-if="editForm.errors.company_id" class="text-red-500 text-xs mt-1">{{ editForm.errors.company_id }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Deskripsi</label>
                <textarea v-model="editForm.deskripsi" rows="2" placeholder="Deskripsi role (opsional)" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors resize-none bg-white dark:bg-gray-900 text-gray-900 dark:text-white', editForm.errors.deskripsi ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500']"></textarea>
                <p v-if="editForm.errors.deskripsi" class="text-red-500 text-xs mt-1">{{ editForm.errors.deskripsi }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Status <span class="text-red-500">*</span></label>
                <select v-model="editForm.status" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', editForm.errors.status ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500']">
                  <option value="Aktif">Aktif</option>
                  <option value="Nonaktif">Nonaktif</option>
                </select>
                <p v-if="editForm.errors.status" class="text-red-500 text-xs mt-1">{{ editForm.errors.status }}</p>
              </div>
              <!-- Permission Section -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Permission <span class="text-xs text-gray-400 font-normal ml-1">({{ editForm.permission_ids.length }} dipilih)</span></label>
                <div class="space-y-3 border border-gray-200 dark:border-gray-700 rounded-xl p-4 max-h-64 overflow-y-auto">
                  <div v-for="group in permissionGroups" :key="group.module" class="border-b border-gray-100 dark:border-gray-700 last:border-b-0 pb-3 last:pb-0">
                    <label class="flex items-center gap-2 cursor-pointer select-none py-1">
                      <input type="checkbox" :checked="isGroupAllChecked(group, editForm.permission_ids)" :indeterminate="isGroupPartialChecked(group, editForm.permission_ids)" @change="toggleGroupPerms(group, editForm.permission_ids)" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500" />
                      <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ group.module }}</span>
                    </label>
                    <div class="flex flex-wrap gap-1.5 ml-6 mt-1.5">
                      <label v-for="p in group.permissions" :key="p.id" class="flex items-center gap-1.5 cursor-pointer select-none">
                        <input type="checkbox" :checked="editForm.permission_ids.includes(p.id)" @change="togglePerm(editForm.permission_ids, p.id)" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500" />
                        <span class="text-xs text-gray-600 dark:text-gray-400">{{ p.label }}</span>
                      </label>
                    </div>
                  </div>
                </div>
                <p v-if="editForm.errors.permission_ids" class="text-red-500 text-xs mt-1">{{ editForm.errors.permission_ids }}</p>
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

    <!-- ═══════════════ DELETE MODAL ═══════════════ -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showDeleteModal = false">
          <div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
          <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
            <div class="px-6 py-5 text-center">
              <div class="w-14 h-14 mx-auto rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-4"><i class="fas fa-exclamation-triangle text-red-500 text-xl"></i></div>
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Hapus Role?</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400">Anda akan menghapus <strong class="text-gray-700 dark:text-gray-300">{{ selectedRole?.nama_role }}</strong>. Data yang dihapus tidak dapat dikembalikan.</p>
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
