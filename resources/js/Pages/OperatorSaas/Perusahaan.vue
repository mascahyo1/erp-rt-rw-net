<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import OperatorSaasLayout from '@/Layouts/OperatorSaasLayout.vue';
import { useToast } from '@/Composables/useToast';
import ToastContainer from '@/Components/ToastContainer.vue';

defineOptions({ layout: OperatorSaasLayout });

const props = defineProps({
  companies: Object,
  filters: Object,
});

const toast = useToast();

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
  router.get('/operator-saas/perusahaan', buildQuery(overrides), {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}

function applyFilters() {
  fetchData({
    search: searchInput.value || undefined,
    status: statusFilter.value || undefined,
    terhapus: terhapusFilter.value,
    page: 1,
  });
}

function resetFilters() {
  searchInput.value = '';
  statusFilter.value = '';
  terhapusFilter.value = 'tidak';
  fetchData({ search: undefined, status: undefined, terhapus: 'tidak', page: 1 });
}

function applySearch() {
  fetchData({
    search: searchInput.value || undefined,
    status: statusFilter.value || undefined,
    terhapus: terhapusFilter.value,
    page: 1,
  });
}

function clearSearch() {
  searchInput.value = '';
  fetchData({
    search: undefined,
    status: statusFilter.value || undefined,
    terhapus: terhapusFilter.value,
    page: 1,
  });
}

function sort(field) {
  if (sortField.value === field) {
    if (sortDir.value === 'asc') { sortDir.value = 'desc'; }
    else { sortField.value = ''; sortDir.value = 'asc'; }
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

function sortOrder(field) { return sortField.value === field ? 1 : null; }

const currentPage = computed(() => props.companies?.current_page || 1);
const totalPages = computed(() => props.companies?.last_page || 1);

function changePerPage(n) { perPage.value = n; fetchData({ per_page: n, page: 1 }); }
function goToPage(page) { fetchData({ page: Math.max(1, Math.min(page, totalPages.value)) }); }

const visiblePages = computed(() => {
  const pages = [];
  const total = totalPages.value;
  const current = currentPage.value;
  let start = Math.max(1, current - 2);
  let end = Math.min(total, current + 2);
  if (end - start < 4) { if (start === 1) end = Math.min(total, start + 4); else start = Math.max(1, end - 4); }
  for (let i = start; i <= end; i++) pages.push(i);
  return pages;
});

const selectedIds = ref([]);
const selectAll = ref(false);
const companiesList = computed(() => props.companies?.data || []);

function toggleSelectAll() { if (selectAll.value) { selectedIds.value = companiesList.value.map(c => c.id); } else { selectedIds.value = []; } }
function toggleSelect(id) { const idx = selectedIds.value.indexOf(id); if (idx === -1) selectedIds.value.push(id); else selectedIds.value.splice(idx, 1); selectAll.value = selectedIds.value.length === companiesList.value.length; }

function bulkDelete() {
  if (selectedIds.value.length === 0) return;
  const count = selectedIds.value.length;
  router.post('/operator-saas/perusahaan/bulk-delete', { ids: [...selectedIds.value] }, {
    preserveState: true, preserveScroll: true,
    onSuccess: () => { selectedIds.value = []; selectAll.value = false; toast.success(`${count} perusahaan berhasil dihapus.`); },
    onError: () => { toast.error('Gagal menghapus perusahaan.'); },
  });
}

function bulkSetStatus(status) {
  if (selectedIds.value.length === 0) return;
  const count = selectedIds.value.length;
  router.post('/operator-saas/perusahaan/bulk-status', { ids: [...selectedIds.value], status }, {
    preserveState: true, preserveScroll: true,
    onSuccess: () => { selectedIds.value = []; selectAll.value = false; toast.success(`${count} perusahaan berhasil ${status === 'Aktif' ? 'diaktifkan' : 'dinonaktifkan'}.`); },
    onError: () => { toast.error('Gagal mengubah status perusahaan.'); },
  });
}

function statusBadge(status) { return status === 'Aktif' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'; }
function formatTelepon(c) { return (c.kode_negara || '') + ' ' + (c.no_telp || ''); }

const selectedCompany = ref(null);
const showCreateModal = ref(false);
const showDetailModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const kodeNegaraList = ['+62', '+60', '+65', '+66', '+84', '+1', '+44', '+81', '+86'];
const logoLightPreview = ref(null);
const logoDarkPreview = ref(null);
const currentEditCompany = ref(null);

const createForm = useForm({ nama_perusahaan: '', email: '', kode_negara: '+62', no_telp: '', alamat: '', deskripsi: '', status: 'Aktif', logo: null, logo_dark: null });
const editForm = useForm({ id: null, nama_perusahaan: '', email: '', kode_negara: '+62', no_telp: '', alamat: '', deskripsi: '', status: 'Aktif', logo: null, logo_dark: null });

function openCreate() { createForm.reset(); createForm.clearErrors(); logoLightPreview.value = null; logoDarkPreview.value = null; showCreateModal.value = true; }
function openDetail(c) { selectedCompany.value = c; showDetailModal.value = true; }
function openEdit(c) {
  currentEditCompany.value = c;
  editForm.reset(); editForm.clearErrors();
  editForm.id = c.id; editForm.nama_perusahaan = c.nama_perusahaan; editForm.email = c.email;
  editForm.kode_negara = c.kode_negara; editForm.no_telp = c.no_telp; editForm.alamat = c.alamat;
  editForm.deskripsi = c.deskripsi || ''; editForm.status = c.status;
  logoLightPreview.value = null; logoDarkPreview.value = null;
  showEditModal.value = true;
}
function openDelete(c) { selectedCompany.value = c; showDeleteModal.value = true; }

function onLogoLightChange(e) {
  const file = e.target.files[0];
  if (file) { createForm.logo = file; editForm.logo = file; logoLightPreview.value = URL.createObjectURL(file); }
}
function onLogoDarkChange(e) {
  const file = e.target.files[0];
  if (file) { createForm.logo_dark = file; editForm.logo_dark = file; logoDarkPreview.value = URL.createObjectURL(file); }
}
function clearLogoLight() { createForm.logo = null; editForm.logo = null; logoLightPreview.value = null; }
function clearLogoDark() { createForm.logo_dark = null; editForm.logo_dark = null; logoDarkPreview.value = null; }

function saveCreate() {
  createForm.post('/operator-saas/perusahaan', {
    preserveState: true, preserveScroll: true, forceFormData: true,
    onSuccess: () => { showCreateModal.value = false; logoLightPreview.value = null; logoDarkPreview.value = null; toast.success('Perusahaan berhasil ditambahkan.'); },
    onError: () => { toast.error('Validasi gagal. Periksa kembali isian form.'); },
  });
}

function saveEdit() {
  editForm.put(`/operator-saas/perusahaan/${editForm.id}`, {
    preserveState: true, preserveScroll: true, forceFormData: true,
    onSuccess: () => { showEditModal.value = false; logoLightPreview.value = null; logoDarkPreview.value = null; toast.success('Perusahaan berhasil diperbarui.'); },
    onError: () => { toast.error('Validasi gagal. Periksa kembali isian form.'); },
  });
}

function confirmDelete() {
  const name = selectedCompany.value?.nama_perusahaan;
  router.delete(`/operator-saas/perusahaan/${selectedCompany.value?.id}`, {
    preserveState: true, preserveScroll: true,
    onSuccess: () => { showDeleteModal.value = false; toast.success(`Perusahaan "${name}" berhasil dihapus.`); if (companiesList.value.length === 0 && currentPage.value > 1) goToPage(currentPage.value - 1); },
    onError: () => { toast.error('Gagal menghapus perusahaan.'); },
  });
}

function restoreCompany(c) {
  router.post(`/operator-saas/perusahaan/${c.id}/restore`, {}, {
    preserveState: true, preserveScroll: true,
    onSuccess: () => { toast.success(`Perusahaan "${c.nama_perusahaan}" berhasil dipulihkan.`); },
    onError: () => { toast.error('Gagal memulihkan perusahaan.'); },
  });
}
</script>

<template>
  <div>
    <Head title="Perusahaan | Operator SaaS" />
    <ToastContainer />

    <div class="space-y-6">
      <nav class="flex items-center gap-1.5 text-sm">
        <Link href="/operator-saas/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"><i class="fas fa-home"></i></Link>
        <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
        <Link href="/operator-saas/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Dashboard</Link>
        <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
        <span class="text-gray-900 dark:text-white font-medium">Perusahaan</span>
      </nav>

      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div><h2 class="text-2xl font-bold text-gray-900 dark:text-white">Perusahaan</h2><p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Kelola data perusahaan tenant RT/RW Net.</p></div>
        <button @click="openCreate" class="inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm"><i class="fas fa-plus mr-1.5"></i> Tambah Perusahaan</button>
      </div>

      <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
        <div class="relative w-full sm:w-72">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-search text-gray-400 text-sm"></i></div>
          <input v-model="searchInput" type="text" placeholder="Cari perusahaan..." class="w-full pl-10 pr-16 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors" @keydown.enter="applySearch" />
          <div class="absolute inset-y-0 right-0 flex items-center gap-1 pr-1.5">
            <button v-if="searchInput" @click="clearSearch" class="p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-white transition-colors"><i class="fas fa-times text-xs"></i></button>
            <button @click="applySearch" class="px-2 py-1 rounded bg-indigo-600 text-white hover:bg-indigo-700 transition-colors"><i class="fas fa-search text-xs"></i></button>
          </div>
        </div>
        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400"><span>{{ props.companies?.total || 0 }} data</span><button v-if="statusFilter || terhapusFilter !== 'tidak' || searchInput" @click="resetFilters" class="text-xs text-red-500 hover:text-red-700 dark:hover:text-red-400 underline">Reset filter</button></div>
      </div>

      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm">
        <div class="flex flex-wrap items-end gap-4">
          <div class="min-w-[160px]">
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Status</label>
            <select v-model="statusFilter" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
              <option value="">Semua</option>
              <option value="Aktif">Ya (Aktif)</option>
              <option value="Nonaktif">Tidak (Nonaktif)</option>
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

      <div v-if="selectedIds.length > 0" class="flex items-center justify-between px-4 py-3 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-xl shadow-sm">
        <span class="text-sm font-medium text-indigo-700 dark:text-indigo-300"><i class="fas fa-check-circle mr-1.5"></i> {{ selectedIds.length }} data dipilih</span>
        <div class="flex items-center gap-2">
          <button @click="bulkSetStatus('Aktif')" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition-colors"><i class="fas fa-check mr-1"></i> Aktifkan</button>
          <button @click="bulkSetStatus('Nonaktif')" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-amber-600 text-white hover:bg-amber-700 transition-colors"><i class="fas fa-ban mr-1"></i> Nonaktifkan</button>
          <button @click="bulkDelete()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-red-600 text-white hover:bg-red-700 transition-colors"><i class="fas fa-trash-alt mr-1"></i> Hapus</button>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm min-w-[900px]">
            <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
              <tr>
                <th class="px-4 py-3 w-10"><input v-model="selectAll" type="checkbox" @change="toggleSelectAll" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500" /></th>
                <th @click="sort('name')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none hover:text-gray-900 dark:hover:text-white transition-colors">
                  <span class="inline-flex items-center gap-1">Nama <span v-if="sortOrder('name')" class="inline-flex items-center gap-0.5 text-indigo-500 dark:text-indigo-400"><i :class="['fas', sortIcon('name'), 'text-[10px]']"></i><span class="text-[10px] font-bold leading-none">{{ sortOrder('name') }}</span></span><i v-else class="fas fa-sort text-[10px] text-gray-400 dark:text-gray-500"></i></span>
                </th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Alamat</th>
                <th @click="sort('email')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none hover:text-gray-900 dark:hover:text-white transition-colors">
                  <span class="inline-flex items-center gap-1">Email <span v-if="sortOrder('email')" class="inline-flex items-center gap-0.5 text-indigo-500 dark:text-indigo-400"><i :class="['fas', sortIcon('email'), 'text-[10px]']"></i><span class="text-[10px] font-bold leading-none">{{ sortOrder('email') }}</span></span><i v-else class="fas fa-sort text-[10px] text-gray-400 dark:text-gray-500"></i></span>
                </th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Telepon</th>
                <th @click="sort('is_active')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none hover:text-gray-900 dark:hover:text-white transition-colors">
                  <span class="inline-flex items-center gap-1">Status <span v-if="sortOrder('is_active')" class="inline-flex items-center gap-0.5 text-indigo-500 dark:text-indigo-400"><i :class="['fas', sortIcon('is_active'), 'text-[10px]']"></i><span class="text-[10px] font-bold leading-none">{{ sortOrder('is_active') }}</span></span><i v-else class="fas fa-sort text-[10px] text-gray-400 dark:text-gray-500"></i></span>
                </th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-400 w-32">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-if="companiesList.length === 0"><td colspan="7" class="px-4 py-16 text-center text-gray-500 dark:text-gray-400"><i class="fas fa-building text-3xl mb-2 block opacity-40"></i>Tidak ada data perusahaan.</td></tr>
              <tr v-for="c in companiesList" :key="c.id" :class="['transition-colors', c.dihapus ? 'bg-red-50/30 dark:bg-red-900/10 opacity-60' : 'hover:bg-gray-50 dark:hover:bg-gray-700/30']">
                <td class="px-4 py-3"><input v-if="!c.dihapus" :checked="selectedIds.includes(c.id)" type="checkbox" @change="toggleSelect(c.id)" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500" /></td>
                <td class="px-4 py-3"><div class="flex items-center gap-2.5"><div class="w-8 h-8 rounded-lg bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 flex items-center justify-center text-sky-500 shrink-0 overflow-hidden"><img v-if="c.logo_url" :src="c.logo_url" alt="logo" class="w-full h-full object-contain p-0.5" /><span v-else class="text-xs font-bold text-white bg-gradient-to-br from-sky-500 to-indigo-600 w-full h-full flex items-center justify-center">{{ c.nama_perusahaan.charAt(0) }}</span></div><span class="font-medium text-gray-900 dark:text-white whitespace-nowrap" :class="{ 'line-through': c.dihapus }">{{ c.nama_perusahaan }}</span></div></td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 max-w-[200px] truncate" :class="{ 'line-through': c.dihapus }">{{ c.alamat }}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap" :class="{ 'line-through': c.dihapus }">{{ c.email }}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap" :class="{ 'line-through': c.dihapus }">{{ formatTelepon(c) }}</td>
                <td class="px-4 py-3 whitespace-nowrap">
                  <span v-if="c.dihapus" class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">Terhapus</span>
                  <span v-else :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium', statusBadge(c.status)]">{{ c.status }}</span>
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                  <div class="flex items-center justify-end gap-1">
                    <button @click="openDetail(c)" class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"><i class="fas fa-eye text-sm"></i></button>
                    <template v-if="c.dihapus">
                      <button @click="restoreCompany(c)" class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors"><i class="fas fa-undo-alt text-sm"></i></button>
                    </template>
                    <template v-else>
                      <button @click="openEdit(c)" class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-sky-600 dark:hover:text-sky-400 transition-colors"><i class="fas fa-edit text-sm"></i></button>
                      <button @click="openDelete(c)" class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-red-600 dark:hover:text-red-400 transition-colors"><i class="fas fa-trash-alt text-sm"></i></button>
                    </template>
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
            <span>dari {{ props.companies?.total || 0 }} data</span>
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
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-eye mr-2 text-indigo-500"></i>Detail Perusahaan</h3>
              <button @click="showDetailModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button>
            </div>
            <div class="overflow-y-auto flex-1 px-6 py-5 space-y-5 modal-scroll">
              <div class="flex items-center gap-4 pb-4 border-b border-gray-100 dark:border-gray-700">
                <div class="w-16 h-16 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 flex items-center justify-center text-sky-500 shrink-0 overflow-hidden">
                  <img v-if="selectedCompany?.logo_url" :src="selectedCompany.logo_url" alt="Logo" class="w-full h-full object-contain p-1" />
                  <span v-else class="text-xl font-bold text-white bg-gradient-to-br from-sky-500 to-indigo-600 w-full h-full flex items-center justify-center rounded-2xl">{{ selectedCompany?.nama_perusahaan?.charAt(0) }}</span>
                </div>
                <div>
                  <h4 class="text-xl font-bold text-gray-900 dark:text-white">{{ selectedCompany?.nama_perusahaan }}</h4>
                  <span v-if="selectedCompany?.dihapus" class="inline-flex mt-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">Terhapus</span>
                  <span v-else :class="['inline-flex mt-1 px-2.5 py-0.5 rounded-full text-xs font-medium', statusBadge(selectedCompany?.status)]">{{ selectedCompany?.status }}</span>
                </div>
              </div>

              <div v-if="selectedCompany?.logo_url || selectedCompany?.logo_dark_url">
                <h5 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Logo</h5>
                <div class="grid grid-cols-2 gap-3">
                  <div>
                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 block">Mode Terang</label>
                    <div class="bg-gray-50 border border-gray-200 dark:border-gray-700 rounded-lg p-3 flex items-center justify-center min-h-[100px]">
                      <img v-if="selectedCompany?.logo_url" :src="selectedCompany.logo_url" alt="Light" class="max-h-20 max-w-full object-contain" />
                      <span v-else class="text-xs text-gray-400">—</span>
                    </div>
                  </div>
                  <div>
                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 block">Mode Gelap</label>
                    <div class="bg-gray-900 border border-gray-700 rounded-lg p-3 flex items-center justify-center min-h-[100px]">
                      <img v-if="selectedCompany?.logo_dark_url" :src="selectedCompany.logo_dark_url" alt="Dark" class="max-h-20 max-w-full object-contain" />
                      <span v-else class="text-xs text-gray-500">—</span>
                    </div>
                  </div>
                </div>
              </div>

              <div>
                <h5 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Informasi Perusahaan</h5>
                <div class="space-y-3">
                  <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Email</label><p class="text-sm text-gray-900 dark:text-white mt-0.5">{{ selectedCompany?.email }}</p></div>
                  <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Telepon</label><p class="text-sm text-gray-900 dark:text-white mt-0.5">{{ formatTelepon(selectedCompany) }}</p></div>
                  <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Alamat</label><p class="text-sm text-gray-900 dark:text-white mt-0.5">{{ selectedCompany?.alamat }}</p></div>
                  <div v-if="selectedCompany?.deskripsi"><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Deskripsi</label><p class="text-sm text-gray-900 dark:text-white mt-0.5">{{ selectedCompany?.deskripsi }}</p></div>
                </div>
              </div>

              <div>
                <h5 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Riwayat</h5>
                <div class="space-y-2.5">
                  <div class="flex items-start gap-3">
                    <div class="w-7 h-7 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center shrink-0 mt-0.5"><i class="fas fa-plus text-emerald-600 dark:text-emerald-400 text-xs"></i></div>
                    <div><p class="text-sm text-gray-900 dark:text-white">Dibuat</p><p class="text-xs text-gray-500 dark:text-gray-400">{{ selectedCompany?.created_at }}</p><p v-if="selectedCompany?.created_by" class="text-xs text-gray-400 dark:text-gray-500">oleh {{ selectedCompany?.created_by }}</p></div>
                  </div>
                  <div class="flex items-start gap-3">
                    <div class="w-7 h-7 rounded-full bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center shrink-0 mt-0.5"><i class="fas fa-pen text-sky-600 dark:text-sky-400 text-xs"></i></div>
                    <div><p class="text-sm text-gray-900 dark:text-white">Terakhir diperbarui</p><p class="text-xs text-gray-500 dark:text-gray-400">{{ selectedCompany?.updated_at }}</p><p v-if="selectedCompany?.updated_by" class="text-xs text-gray-400 dark:text-gray-500">oleh {{ selectedCompany?.updated_by }}</p></div>
                  </div>
                  <template v-if="selectedCompany?.dihapus">
                    <div class="flex items-start gap-3">
                      <div class="w-7 h-7 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center shrink-0 mt-0.5"><i class="fas fa-trash-alt text-red-600 dark:text-red-400 text-xs"></i></div>
                      <div><p class="text-sm text-gray-900 dark:text-white">Dihapus</p><p class="text-xs text-gray-500 dark:text-gray-400">{{ selectedCompany?.deleted_at }}</p><p v-if="selectedCompany?.deleted_by" class="text-xs text-gray-400 dark:text-gray-500">oleh {{ selectedCompany?.deleted_by }}</p></div>
                    </div>
                  </template>
                  <template v-if="selectedCompany?.restored_at">
                    <div class="flex items-start gap-3">
                      <div class="w-7 h-7 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center shrink-0 mt-0.5"><i class="fas fa-undo-alt text-emerald-600 dark:text-emerald-400 text-xs"></i></div>
                      <div><p class="text-sm text-gray-900 dark:text-white">Dipulihkan</p><p class="text-xs text-gray-500 dark:text-gray-400">{{ selectedCompany?.restored_at }}</p><p v-if="selectedCompany?.restored_by" class="text-xs text-gray-400 dark:text-gray-500">oleh {{ selectedCompany?.restored_by }}</p></div>
                    </div>
                  </template>
                </div>
              </div>
            </div>
            <div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
              <button v-if="!selectedCompany?.dihapus" @click="showDetailModal = false; openEdit(selectedCompany)" class="px-4 py-2 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors"><i class="fas fa-edit mr-1.5"></i> Edit</button>
              <button v-if="selectedCompany?.dihapus" @click="showDetailModal = false; restoreCompany(selectedCompany)" class="px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors"><i class="fas fa-undo-alt mr-1.5"></i> Pulihkan</button>
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
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-plus text-emerald-500 mr-2"></i>Tambah Perusahaan</h3>
              <button type="button" @click="showCreateModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button>
            </div>
            <div class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nama Perusahaan <span class="text-red-500">*</span></label>
                <input v-model="createForm.nama_perusahaan" type="text" placeholder="PT Net Sejahtera" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', createForm.errors.nama_perusahaan ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500']" />
                <p v-if="createForm.errors.nama_perusahaan" class="text-red-500 text-xs mt-1">{{ createForm.errors.nama_perusahaan }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email</label>
                <input v-model="createForm.email" type="email" placeholder="info@perusahaan.id" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', createForm.errors.email ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500']" />
                <p v-if="createForm.errors.email" class="text-red-500 text-xs mt-1">{{ createForm.errors.email }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Telepon <span class="text-red-500">*</span></label>
                <div class="flex gap-2">
                  <select v-model="createForm.kode_negara" class="w-24 px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                    <option v-for="kode in kodeNegaraList" :key="kode" :value="kode">{{ kode }}</option>
                  </select>
                  <input v-model="createForm.no_telp" type="text" placeholder="81234567890" :class="['flex-1 px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', createForm.errors.no_telp ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500']" />
                </div>
                <p v-if="createForm.errors.no_telp" class="text-red-500 text-xs mt-1">{{ createForm.errors.no_telp }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Alamat <span class="text-red-500">*</span></label>
                <textarea v-model="createForm.alamat" rows="2" placeholder="Jl. Merdeka No. 10, Jakarta" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', createForm.errors.alamat ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500']"></textarea>
                <p v-if="createForm.errors.alamat" class="text-red-500 text-xs mt-1">{{ createForm.errors.alamat }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Deskripsi</label>
                <textarea v-model="createForm.deskripsi" rows="2" placeholder="Deskripsi singkat perusahaan..." :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', createForm.errors.deskripsi ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500']"></textarea>
                <p v-if="createForm.errors.deskripsi" class="text-red-500 text-xs mt-1">{{ createForm.errors.deskripsi }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Status</label>
                <select v-model="createForm.status" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                  <option value="Aktif">Aktif</option>
                  <option value="Nonaktif">Nonaktif</option>
                </select>
              </div>
              <!-- Logo Section -->
              <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Logo Perusahaan</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Logo (Mode Terang)</label>
                    <div class="bg-gray-50 border border-gray-200 dark:border-gray-700 rounded-lg p-3 flex items-center justify-center min-h-[100px] mb-2">
                      <img v-if="logoLightPreview" :src="logoLightPreview" alt="Preview Light" class="max-h-16 max-w-full object-contain" />
                      <span v-else class="text-xs text-gray-400">Pilih file untuk preview</span>
                    </div>
                    <input type="file" accept="image/jpeg,image/png,image/webp,image/svg+xml" @change="onLogoLightChange" class="w-full text-xs text-gray-700 dark:text-gray-300 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 dark:file:bg-indigo-900/30 dark:file:text-indigo-300 hover:file:bg-indigo-100 dark:hover:file:bg-indigo-900/50 cursor-pointer" />
                    <div class="flex items-center justify-between mt-1">
                      <p class="text-[10px] text-gray-500 dark:text-gray-400">JPG/PNG/WebP/SVG, maks 2MB</p>
                      <button v-if="logoLightPreview" type="button" @click="clearLogoLight" class="text-[10px] text-red-500 hover:text-red-700">Hapus</button>
                    </div>
                    <p v-if="createForm.errors.logo" class="text-red-500 text-xs mt-1">{{ createForm.errors.logo }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Logo (Mode Gelap)</label>
                    <div class="bg-gray-900 border border-gray-700 rounded-lg p-3 flex items-center justify-center min-h-[100px] mb-2">
                      <img v-if="logoDarkPreview" :src="logoDarkPreview" alt="Preview Dark" class="max-h-16 max-w-full object-contain" />
                      <span v-else class="text-xs text-gray-500">Pilih file untuk preview</span>
                    </div>
                    <input type="file" accept="image/jpeg,image/png,image/webp,image/svg+xml" @change="onLogoDarkChange" class="w-full text-xs text-gray-700 dark:text-gray-300 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 dark:file:bg-indigo-900/30 dark:file:text-indigo-300 hover:file:bg-indigo-100 dark:hover:file:bg-indigo-900/50 cursor-pointer" />
                    <div class="flex items-center justify-between mt-1">
                      <p class="text-[10px] text-gray-500 dark:text-gray-400">Versi untuk dark mode</p>
                      <button v-if="logoDarkPreview" type="button" @click="clearLogoDark" class="text-[10px] text-red-500 hover:text-red-700">Hapus</button>
                    </div>
                    <p v-if="createForm.errors.logo_dark" class="text-red-500 text-xs mt-1">{{ createForm.errors.logo_dark }}</p>
                  </div>
                </div>
                <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-2"><i class="fas fa-info-circle mr-1"></i>Gambar raster (JPG/PNG/WebP) otomatis dikompres ke WebP. File SVG disimpan apa adanya.</p>
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
          <form @submit.prevent="saveEdit" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col">
            <div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-edit text-sky-500 mr-2"></i>Edit Perusahaan</h3>
              <button type="button" @click="showEditModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button>
            </div>
            <div class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nama Perusahaan <span class="text-red-500">*</span></label>
                <input v-model="editForm.nama_perusahaan" type="text" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', editForm.errors.nama_perusahaan ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500']" />
                <p v-if="editForm.errors.nama_perusahaan" class="text-red-500 text-xs mt-1">{{ editForm.errors.nama_perusahaan }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email</label>
                <input v-model="editForm.email" type="email" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', editForm.errors.email ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500']" />
                <p v-if="editForm.errors.email" class="text-red-500 text-xs mt-1">{{ editForm.errors.email }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Telepon <span class="text-red-500">*</span></label>
                <div class="flex gap-2">
                  <select v-model="editForm.kode_negara" class="w-24 px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                    <option v-for="kode in kodeNegaraList" :key="kode" :value="kode">{{ kode }}</option>
                  </select>
                  <input v-model="editForm.no_telp" type="text" :class="['flex-1 px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', editForm.errors.no_telp ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500']" />
                </div>
                <p v-if="editForm.errors.no_telp" class="text-red-500 text-xs mt-1">{{ editForm.errors.no_telp }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Alamat <span class="text-red-500">*</span></label>
                <textarea v-model="editForm.alamat" rows="2" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', editForm.errors.alamat ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500']"></textarea>
                <p v-if="editForm.errors.alamat" class="text-red-500 text-xs mt-1">{{ editForm.errors.alamat }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Deskripsi</label>
                <textarea v-model="editForm.deskripsi" rows="2" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', editForm.errors.deskripsi ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500']"></textarea>
                <p v-if="editForm.errors.deskripsi" class="text-red-500 text-xs mt-1">{{ editForm.errors.deskripsi }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Status</label>
                <select v-model="editForm.status" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                  <option value="Aktif">Aktif</option>
                  <option value="Nonaktif">Nonaktif</option>
                </select>
              </div>
              <!-- Logo Section -->
              <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Logo Perusahaan</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Logo (Mode Terang)</label>
                    <div class="bg-gray-50 border border-gray-200 dark:border-gray-700 rounded-lg p-3 flex items-center justify-center min-h-[100px] mb-2">
                      <img v-if="logoLightPreview || currentEditCompany?.logo_url" :src="logoLightPreview || currentEditCompany.logo_url" alt="Light" class="max-h-16 max-w-full object-contain" />
                      <span v-else class="text-xs text-gray-400">Pilih file untuk ganti</span>
                    </div>
                    <input type="file" accept="image/jpeg,image/png,image/webp,image/svg+xml" @change="onLogoLightChange" class="w-full text-xs text-gray-700 dark:text-gray-300 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 dark:file:bg-indigo-900/30 dark:file:text-indigo-300 hover:file:bg-indigo-100 dark:hover:file:bg-indigo-900/50 cursor-pointer" />
                    <div class="flex items-center justify-between mt-1">
                      <p class="text-[10px] text-gray-500 dark:text-gray-400">JPG/PNG/WebP/SVG, maks 2MB</p>
                      <button v-if="logoLightPreview || currentEditCompany?.logo_url" type="button" @click="clearLogoLight" class="text-[10px] text-red-500 hover:text-red-700">Hapus</button>
                    </div>
                    <p v-if="editForm.errors.logo" class="text-red-500 text-xs mt-1">{{ editForm.errors.logo }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Logo (Mode Gelap)</label>
                    <div class="bg-gray-900 border border-gray-700 rounded-lg p-3 flex items-center justify-center min-h-[100px] mb-2">
                      <img v-if="logoDarkPreview || currentEditCompany?.logo_dark_url" :src="logoDarkPreview || currentEditCompany.logo_dark_url" alt="Dark" class="max-h-16 max-w-full object-contain" />
                      <span v-else class="text-xs text-gray-500">Pilih file untuk ganti</span>
                    </div>
                    <input type="file" accept="image/jpeg,image/png,image/webp,image/svg+xml" @change="onLogoDarkChange" class="w-full text-xs text-gray-700 dark:text-gray-300 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 dark:file:bg-indigo-900/30 dark:file:text-indigo-300 hover:file:bg-indigo-100 dark:hover:file:bg-indigo-900/50 cursor-pointer" />
                    <div class="flex items-center justify-between mt-1">
                      <p class="text-[10px] text-gray-500 dark:text-gray-400">Versi untuk dark mode</p>
                      <button v-if="logoDarkPreview || currentEditCompany?.logo_dark_url" type="button" @click="clearLogoDark" class="text-[10px] text-red-500 hover:text-red-700">Hapus</button>
                    </div>
                    <p v-if="editForm.errors.logo_dark" class="text-red-500 text-xs mt-1">{{ editForm.errors.logo_dark }}</p>
                  </div>
                </div>
                <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-2"><i class="fas fa-info-circle mr-1"></i>Upload file baru untuk mengganti. Gambar raster otomatis dikompres ke WebP, SVG disimpan apa adanya.</p>
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
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Hapus Perusahaan?</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400">Anda akan menghapus <strong class="text-gray-700 dark:text-gray-300">{{ selectedCompany?.nama_perusahaan }}</strong>. Data yang dihapus dapat dipulihkan kembali.</p>
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
