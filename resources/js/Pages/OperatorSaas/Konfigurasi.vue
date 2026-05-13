<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import OperatorSaasLayout from '@/Layouts/OperatorSaasLayout.vue';
import { useToast } from '@/Composables/useToast';
import ToastContainer from '@/Components/ToastContainer.vue';

defineOptions({ layout: OperatorSaasLayout });

const props = defineProps({
  configs: Object,
  filters: Object,
});

const toast = useToast();

// ── Search & Filter State ──
const searchInput = ref(props.filters?.search || '');
const typeFilter = ref(props.filters?.type || '');
const sortField = ref(props.filters?.sort_field || '');
const sortDir = ref(props.filters?.sort_dir || 'asc');
const perPage = ref(props.filters?.per_page && !isNaN(props.filters.per_page) ? Number(props.filters.per_page) : 10);
const perPageOptions = [5, 10, 25, 50, 100];

function buildQuery(overrides = {}) {
  const params = { ...overrides };
  if (params.search === undefined) params.search = searchInput.value || undefined;
  if (params.type === undefined) params.type = typeFilter.value || undefined;
  if (params.sort_field === undefined) params.sort_field = sortField.value || undefined;
  if (params.sort_dir === undefined) params.sort_dir = sortDir.value || undefined;
  if (params.per_page === undefined) params.per_page = perPage.value;
  Object.keys(params).forEach(k => {
    if (params[k] === undefined) delete params[k];
  });
  return params;
}

function fetchData(overrides = {}) {
  router.get('/operator-saas/konfigurasi', buildQuery(overrides), {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}

function applySearch() {
  fetchData({ search: searchInput.value || undefined, type: typeFilter.value || undefined, page: 1 });
}

function clearSearch() {
  searchInput.value = '';
  fetchData({ search: undefined, type: typeFilter.value || undefined, page: 1 });
}

function applyFilters() {
  fetchData({ search: searchInput.value || undefined, type: typeFilter.value || undefined, page: 1 });
}

function resetFilters() {
  searchInput.value = '';
  typeFilter.value = '';
  fetchData({ search: undefined, type: undefined, page: 1 });
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

// ── Pagination (server-driven) ──
const currentPage = computed(() => props.configs?.current_page || 1);
const totalPages = computed(() => props.configs?.last_page || 1);

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

const configsList = computed(() => props.configs?.data || []);

function toggleSelectAll() {
  if (selectAll.value) {
    selectedIds.value = configsList.value.map(a => a.id);
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
  selectAll.value = selectedIds.value.length === configsList.value.length;
}

function bulkDelete() {
  if (selectedIds.value.length === 0) return;
  const count = selectedIds.value.length;
  router.post('/operator-saas/konfigurasi/bulk-delete', { ids: [...selectedIds.value] }, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      selectedIds.value = [];
      selectAll.value = false;
      toast.success(`${count} konfigurasi berhasil dihapus.`);
    },
    onError: () => {
      toast.error('Gagal menghapus konfigurasi. Silakan coba lagi.');
    },
  });
}

// ── CRUD State ──
const selectedConfig = ref(null);
const showCreateModal = ref(false);
const showDetailModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);

const typeOptions = [
  { value: 'text', label: 'Text' },
  { value: 'file', label: 'File' },
];

// Create/Edit form using Inertia useForm
const createForm = useForm({
  key: '',
  type: 'text',
  value: '',
  descripton: '',
});

const editForm = useForm({
  id: null,
  key: '',
  type: 'text',
  value: '',
  descripton: '',
});

// ── CRUD Actions ──
function openCreate() {
  createForm.reset();
  createForm.clearErrors();
  showCreateModal.value = true;
}

function openDetail(config) {
  selectedConfig.value = config;
  showDetailModal.value = true;
}

function openEdit(config) {
  editForm.reset();
  editForm.clearErrors();
  editForm.id = config.id;
  editForm.key = config.key;
  editForm.type = config.type;
  editForm.value = config.value;
  editForm.descripton = config.descripton || '';
  showEditModal.value = true;
}

function openDelete(config) {
  selectedConfig.value = config;
  showDeleteModal.value = true;
}

function saveCreate() {
  createForm.post('/operator-saas/konfigurasi', {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      showCreateModal.value = false;
      toast.success('Konfigurasi berhasil ditambahkan.');
    },
    onError: () => {
      toast.error('Validasi gagal. Periksa kembali isian form.');
    },
  });
}

function saveEdit() {
  editForm.put(`/operator-saas/konfigurasi/${editForm.id}`, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      showEditModal.value = false;
      toast.success('Konfigurasi berhasil diperbarui.');
    },
    onError: () => {
      toast.error('Validasi gagal. Periksa kembali isian form.');
    },
  });
}

function confirmDelete() {
  const key = selectedConfig.value?.key;
  router.delete(`/operator-saas/konfigurasi/${selectedConfig.value?.id}`, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      showDeleteModal.value = false;
      toast.success(`Konfigurasi "${key}" berhasil dihapus.`);
      if (configsList.value.length === 0 && currentPage.value > 1) {
        goToPage(currentPage.value - 1);
      }
    },
    onError: () => {
      toast.error('Gagal menghapus konfigurasi. Silakan coba lagi.');
    },
  });
}

function typeLabel(type) {
  return type === 'file' ? 'File' : 'Text';
}

function typeBadge(type) {
  return type === 'file'
    ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400'
    : 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400';
}
</script>

<template>
  <div>
    <Head title="Konfigurasi | Operator SaaS" />
    <ToastContainer />

    <div class="space-y-6">
      <!-- Breadcrumbs -->
      <nav class="flex items-center gap-1.5 text-sm">
        <Link href="/operator-saas/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"><i class="fas fa-home"></i></Link>
        <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
        <Link href="/operator-saas/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Dashboard</Link>
        <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
        <span class="text-gray-900 dark:text-white font-medium">Konfigurasi</span>
      </nav>

      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div><h2 class="text-2xl font-bold text-gray-900 dark:text-white">Konfigurasi SaaS</h2><p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Kelola konfigurasi platform SaaS (key-value).</p></div>
        <button @click="openCreate" class="inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm"><i class="fas fa-plus mr-1.5"></i> Tambah Konfigurasi</button>
      </div>

      <!-- Filters Bar -->
      <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
        <div class="relative w-full sm:w-72">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-search text-gray-400 text-sm"></i></div>
          <input v-model="searchInput" type="text" placeholder="Cari konfigurasi..." class="w-full pl-10 pr-16 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors" @keydown.enter="applySearch" />
          <div class="absolute inset-y-0 right-0 flex items-center gap-1 pr-1.5">
            <button v-if="searchInput" @click="clearSearch" class="p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-white transition-colors" title="Clear"><i class="fas fa-times text-xs"></i></button>
            <button @click="applySearch" class="px-2 py-1 rounded bg-indigo-600 text-white hover:bg-indigo-700 transition-colors" title="Cari"><i class="fas fa-search text-xs"></i></button>
          </div>
        </div>
        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400"><span>{{ props.configs?.total || 0 }} data</span><button v-if="typeFilter || searchInput" @click="resetFilters" class="text-xs text-red-500 hover:text-red-700 dark:hover:text-red-400 underline">Reset filter</button></div>
      </div>

      <!-- Filter Card -->
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm">
        <div class="flex flex-wrap items-end gap-4">
          <div class="min-w-[160px]">
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Tipe</label>
            <select
              v-model="typeFilter"
              class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors"
            >
              <option value="">Semua</option>
              <option value="text">Text</option>
              <option value="file">File</option>
            </select>
          </div>
          <button @click="applyFilters" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
            <i class="fas fa-filter mr-1.5"></i>Filter
          </button>
        </div>
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
                <th @click="sort('key')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none hover:text-gray-900 dark:hover:text-white transition-colors">
                  <span class="inline-flex items-center gap-1">
                    Key
                    <span v-if="sortOrder('key')" class="inline-flex items-center gap-0.5 text-indigo-500 dark:text-indigo-400">
                      <i :class="['fas', sortIcon('key'), 'text-[10px]']"></i>
                      <span class="text-[10px] font-bold leading-none">{{ sortOrder('key') }}</span>
                    </span>
                    <i v-else class="fas fa-sort text-[10px] text-gray-400 dark:text-gray-500"></i>
                  </span>
                </th>
                <th @click="sort('value')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none hover:text-gray-900 dark:hover:text-white transition-colors">
                  <span class="inline-flex items-center gap-1">
                    Value
                    <span v-if="sortOrder('value')" class="inline-flex items-center gap-0.5 text-indigo-500 dark:text-indigo-400">
                      <i :class="['fas', sortIcon('value'), 'text-[10px]']"></i>
                      <span class="text-[10px] font-bold leading-none">{{ sortOrder('value') }}</span>
                    </span>
                    <i v-else class="fas fa-sort text-[10px] text-gray-400 dark:text-gray-500"></i>
                  </span>
                </th>
                <th @click="sort('type')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none hover:text-gray-900 dark:hover:text-white transition-colors">
                  <span class="inline-flex items-center gap-1">
                    Tipe
                    <span v-if="sortOrder('type')" class="inline-flex items-center gap-0.5 text-indigo-500 dark:text-indigo-400">
                      <i :class="['fas', sortIcon('type'), 'text-[10px]']"></i>
                      <span class="text-[10px] font-bold leading-none">{{ sortOrder('type') }}</span>
                    </span>
                    <i v-else class="fas fa-sort text-[10px] text-gray-400 dark:text-gray-500"></i>
                  </span>
                </th>
                <th @click="sort('created_at')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none hover:text-gray-900 dark:hover:text-white transition-colors">
                  <span class="inline-flex items-center gap-1">
                    Dibuat
                    <span v-if="sortOrder('created_at')" class="inline-flex items-center gap-0.5 text-indigo-500 dark:text-indigo-400">
                      <i :class="['fas', sortIcon('created_at'), 'text-[10px]']"></i>
                      <span class="text-[10px] font-bold leading-none">{{ sortOrder('created_at') }}</span>
                    </span>
                    <i v-else class="fas fa-sort text-[10px] text-gray-400 dark:text-gray-500"></i>
                  </span>
                </th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-400 w-32">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-if="configsList.length === 0"><td colspan="6" class="px-4 py-16 text-center text-gray-500 dark:text-gray-400"><i class="fas fa-sliders text-3xl mb-2 block opacity-40"></i>Tidak ada data konfigurasi.</td></tr>
              <tr v-for="c in configsList" :key="c.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                <td class="px-4 py-3">
                  <input :checked="selectedIds.includes(c.id)" type="checkbox" @change="toggleSelect(c.id)" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500" />
                </td>
                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white whitespace-nowrap">{{ c.key }}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 max-w-xs truncate" :title="c.value">{{ c.value }}</td>
                <td class="px-4 py-3 whitespace-nowrap">
                  <span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium', typeBadge(c.type)]">{{ typeLabel(c.type) }}</span>
                </td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap">{{ c.created_at }}</td>
                <td class="px-4 py-3 whitespace-nowrap">
                  <div class="flex items-center justify-end gap-1">
                    <button @click="openDetail(c)" class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors" title="Detail"><i class="fas fa-eye text-sm"></i></button>
                    <button @click="openEdit(c)" class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-sky-600 dark:hover:text-sky-400 transition-colors" title="Edit"><i class="fas fa-edit text-sm"></i></button>
                    <button @click="openDelete(c)" class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-red-600 dark:hover:text-red-400 transition-colors" title="Hapus"><i class="fas fa-trash-alt text-sm"></i></button>
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
            <span>dari {{ props.configs?.total || 0 }} data</span>
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
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-eye mr-2 text-indigo-500"></i>Detail Konfigurasi</h3>
              <button @click="showDetailModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button>
            </div>
            <div class="overflow-y-auto flex-1 px-6 py-5 space-y-5 modal-scroll">
              <div class="flex items-center gap-4 pb-4 border-b border-gray-100 dark:border-gray-700">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-sky-500 to-indigo-600 flex items-center justify-center text-white text-lg font-bold shrink-0">{{ selectedConfig?.key?.charAt(0)?.toUpperCase() }}</div>
                <div>
                  <h4 class="text-xl font-bold text-gray-900 dark:text-white">{{ selectedConfig?.key }}</h4>
                  <span :class="['inline-flex mt-1 px-2.5 py-0.5 rounded-full text-xs font-medium', typeBadge(selectedConfig?.type)]">{{ typeLabel(selectedConfig?.type) }}</span>
                </div>
              </div>

              <div>
                <h5 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Informasi Konfigurasi</h5>
                <div class="space-y-3">
                  <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Key</label><p class="text-sm text-gray-900 dark:text-white mt-0.5 font-medium">{{ selectedConfig?.key }}</p></div>
                  <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Value</label><p class="text-sm text-gray-900 dark:text-white mt-0.5 break-all">{{ selectedConfig?.value }}</p></div>
                  <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Tipe</label><p class="text-sm text-gray-900 dark:text-white mt-0.5">{{ typeLabel(selectedConfig?.type) }}</p></div>
                  <div v-if="selectedConfig?.descripton"><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Deskripsi</label><p class="text-sm text-gray-700 dark:text-gray-300 mt-0.5">{{ selectedConfig?.descripton }}</p></div>
                </div>
              </div>

              <div>
                <h5 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Riwayat</h5>
                <div class="space-y-2.5">
                  <div class="flex items-start gap-3">
                    <div class="w-7 h-7 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center shrink-0 mt-0.5"><i class="fas fa-plus text-emerald-600 dark:text-emerald-400 text-xs"></i></div>
                    <div>
                      <p class="text-sm text-gray-900 dark:text-white">Dibuat</p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">{{ selectedConfig?.created_at }}</p>
                      <p v-if="selectedConfig?.created_by" class="text-xs text-gray-400 dark:text-gray-500">oleh {{ selectedConfig?.created_by }}</p>
                    </div>
                  </div>
                  <div class="flex items-start gap-3">
                    <div class="w-7 h-7 rounded-full bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center shrink-0 mt-0.5"><i class="fas fa-pen text-sky-600 dark:text-sky-400 text-xs"></i></div>
                    <div>
                      <p class="text-sm text-gray-900 dark:text-white">Terakhir diperbarui</p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">{{ selectedConfig?.updated_at }}</p>
                      <p v-if="selectedConfig?.updated_by" class="text-xs text-gray-400 dark:text-gray-500">oleh {{ selectedConfig?.updated_by }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
              <button @click="showDetailModal = false; openEdit(selectedConfig)" class="px-4 py-2 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors"><i class="fas fa-edit mr-1.5"></i> Edit</button>
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
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-plus text-emerald-500 mr-2"></i>Tambah Konfigurasi</h3>
              <button type="button" @click="showCreateModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button>
            </div>
            <div class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Key <span class="text-red-500">*</span></label>
                <input v-model="createForm.key" type="text" placeholder="app.name" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', createForm.errors.key ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500']" />
                <p v-if="createForm.errors.key" class="text-red-500 text-xs mt-1">{{ createForm.errors.key }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Tipe <span class="text-red-500">*</span></label>
                <select v-model="createForm.type" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                  <option v-for="opt in typeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </select>
                <p v-if="createForm.errors.type" class="text-red-500 text-xs mt-1">{{ createForm.errors.type }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Value <span class="text-red-500">*</span></label>
                <textarea v-model="createForm.value" rows="3" placeholder="Nilai konfigurasi..." :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white resize-vertical', createForm.errors.value ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500']"></textarea>
                <p v-if="createForm.errors.value" class="text-red-500 text-xs mt-1">{{ createForm.errors.value }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Deskripsi</label>
                <textarea v-model="createForm.descripton" rows="2" placeholder="Deskripsi konfigurasi (opsional)..." :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white resize-vertical', createForm.errors.descripton ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500']"></textarea>
                <p v-if="createForm.errors.descripton" class="text-red-500 text-xs mt-1">{{ createForm.errors.descripton }}</p>
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
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-edit text-sky-500 mr-2"></i>Edit Konfigurasi</h3>
              <button type="button" @click="showEditModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button>
            </div>
            <div class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Key <span class="text-red-500">*</span></label>
                <input v-model="editForm.key" type="text" placeholder="app.name" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', editForm.errors.key ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500']" />
                <p v-if="editForm.errors.key" class="text-red-500 text-xs mt-1">{{ editForm.errors.key }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Tipe <span class="text-red-500">*</span></label>
                <select v-model="editForm.type" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                  <option v-for="opt in typeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </select>
                <p v-if="editForm.errors.type" class="text-red-500 text-xs mt-1">{{ editForm.errors.type }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Value <span class="text-red-500">*</span></label>
                <textarea v-model="editForm.value" rows="3" placeholder="Nilai konfigurasi..." :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white resize-vertical', editForm.errors.value ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500']"></textarea>
                <p v-if="editForm.errors.value" class="text-red-500 text-xs mt-1">{{ editForm.errors.value }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Deskripsi</label>
                <textarea v-model="editForm.descripton" rows="2" placeholder="Deskripsi konfigurasi (opsional)..." :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white resize-vertical', editForm.errors.descripton ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500']"></textarea>
                <p v-if="editForm.errors.descripton" class="text-red-500 text-xs mt-1">{{ editForm.errors.descripton }}</p>
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
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Hapus Konfigurasi?</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400">Anda akan menghapus konfigurasi <strong class="text-gray-700 dark:text-gray-300">{{ selectedConfig?.key }}</strong>. Data yang dihapus tidak dapat dikembalikan.</p>
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
