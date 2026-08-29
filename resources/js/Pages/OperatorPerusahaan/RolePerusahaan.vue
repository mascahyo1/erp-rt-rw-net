<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import OperatorPerusahaanLayout from '@/Layouts/OperatorPerusahaanLayout.vue';
import { useToast } from '@/Composables/useToast';
import FormErrorSummary from '@/Components/FormErrorSummary.vue';
import ToastContainer from '@/Components/ToastContainer.vue';
import PermissionGroupChecklist from '@/Components/PermissionGroupChecklist.vue';
import { errorSummary } from '@/Composables/useFormErrorToast';

defineOptions({ layout: OperatorPerusahaanLayout });

const props = defineProps({ roles: Object, availablePermissions: Array, filters: Object });
const toast = useToast();

const searchInput = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const terhapusFilter = ref(props.filters?.terhapus || 'tidak');
const sortField = ref(props.filters?.sort_field || '');
const sortDir = ref(props.filters?.sort_dir || 'asc');
const perPage = ref(props.filters?.per_page ? Number(props.filters.per_page) : 10);
const perPageOptions = [5, 10, 25, 50, 100];
const selectedIds = ref([]); const selectAll = ref(false);
const selectedItem = ref(null);
const showCreateModal = ref(false); const showDetailModal = ref(false);
const showEditModal = ref(false); const showDeleteModal = ref(false);

function buildQuery(o = {}) {
  const p = { ...o };
  if (p.search === undefined) p.search = searchInput.value || undefined;
  if (p.status === undefined) p.status = statusFilter.value || undefined;
  if (p.terhapus === undefined) p.terhapus = terhapusFilter.value || undefined;
  if (p.sort_field === undefined) p.sort_field = sortField.value || undefined;
  if (p.sort_dir === undefined) p.sort_dir = sortDir.value || undefined;
  if (p.per_page === undefined) p.per_page = perPage.value;
  Object.keys(p).forEach(k => { if (p[k] === undefined) delete p[k]; });
  return p;
}
function fetchData(o = {}) {
  router.get('/operator-perusahaan/role-perusahaan', buildQuery(o), { preserveState: true, preserveScroll: true, replace: true });
}
function applySearch() { fetchData({ search: searchInput.value || undefined, status: statusFilter.value || undefined, terhapus: terhapusFilter.value, page: 1 }); }
function clearSearch() { searchInput.value = ''; fetchData({ search: undefined, status: statusFilter.value || undefined, terhapus: terhapusFilter.value, page: 1 }); }
function applyFilters() { fetchData({ page: 1 }); }
function resetFilters() { searchInput.value = ''; statusFilter.value = ''; terhapusFilter.value = 'tidak'; fetchData({ search: undefined, status: undefined, terhapus: 'tidak', page: 1 }); }
function sort(f) {
  if (sortField.value === f) { if (sortDir.value === 'asc') sortDir.value = 'desc'; else { sortField.value = ''; sortDir.value = 'asc'; } }
  else { sortField.value = f; sortDir.value = 'asc'; }
  fetchData({ sort_field: sortField.value || undefined, sort_dir: sortDir.value || undefined });
}
function sortIcon(f) { if (sortField.value !== f) return 'fa-sort'; return sortDir.value === 'asc' ? 'fa-sort-up' : 'fa-sort-down'; }
function toggleSelectAll() { selectedIds.value = selectAll.value ? items.value.map(r => r.id) : []; }
function toggleSelect(id) { const i = selectedIds.value.indexOf(id); i === -1 ? selectedIds.value.push(id) : selectedIds.value.splice(i, 1); }
function goToPage(p) { fetchData({ page: p }); }
function changePerPage(n) { perPage.value = n; fetchData({ per_page: n, page: 1 }); }
function statusBadgeClass(s) { return s === 'Aktif' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'; }

const createForm = useForm({ nama_role: '', deskripsi: '', status: 'Aktif', permission_ids: [] });
const editForm = useForm({ nama_role: '', deskripsi: '', status: 'Aktif', permission_ids: [] });

function openCreate() { createForm.reset(); showCreateModal.value = true; }
function submitCreate() { createForm.post('/operator-perusahaan/role-perusahaan', { onSuccess: () => { showCreateModal.value = false; fetchData(); toast.success('Role berhasil ditambahkan.'); }, onError: () => toast.error('Validasi gagal: ' + errorSummary(createForm.errors), 6000) }); }
function openEdit(item) {
  editForm.defaults({
    nama_role: item.nama_role,
    deskripsi: item.deskripsi || '',
    status: item.status,
    permission_ids: item.permissions ? item.permissions.map(p => p.id) : []
  });
  editForm.reset();
  selectedItem.value = item;
  showEditModal.value = true;
}
function submitEdit() { editForm.put('/operator-perusahaan/role-perusahaan/' + selectedItem.value.id, { onSuccess: () => { showEditModal.value = false; fetchData(); toast.success('Role berhasil diperbarui.'); }, onError: () => toast.error('Validasi gagal: ' + errorSummary(editForm.errors), 6000) }); }
function openDetail(item) { selectedItem.value = item; showDetailModal.value = true; }
function openDelete(item) { selectedItem.value = item; showDeleteModal.value = true; }
function confirmDelete() { router.delete('/operator-perusahaan/role-perusahaan/' + selectedItem.value.id, { onSuccess: () => { showDeleteModal.value = false; fetchData(); toast.success('Role berhasil dihapus.'); } }); }
function confirmRestore(id) { router.patch('/operator-perusahaan/role-perusahaan/' + id + '/restore', { onSuccess: () => { fetchData(); toast.success('Role berhasil dipulihkan.'); } }); }
function bulkDelete() { router.post('/operator-perusahaan/role-perusahaan/bulk-delete', { ids: selectedIds.value }, { onSuccess: () => { selectedIds.value = []; selectAll.value = false; fetchData(); toast.success('Role berhasil dihapus.'); } }); }
function bulkSetStatus(s) { router.post('/operator-perusahaan/role-perusahaan/bulk-status', { ids: selectedIds.value, status: s }, { onSuccess: () => { selectedIds.value = []; selectAll.value = false; fetchData(); toast.success('Status berhasil diubah.'); } }); }

// Get full permission objects (with .nama) for current role
function getActivePerms() {
  const list = (showCreateModal.value ? createForm : editForm).permission_ids
    .map(id => (props.availablePermissions || []).find(p => p.id === id))
    .filter(Boolean);
  return list;
}
function groupedPermsForDetail(perms) {
  const map = {};
  perms.forEach(p => {
    const m = p.nama?.includes('.') ? p.nama.substring(0, p.nama.lastIndexOf('.')) : (p.nama || 'lainnya');
    (map[m] = map[m] || []).push(p);
  });
  return map;
}

const items = computed(() => props.roles?.data || []);
const pagination = computed(() => ({ current: props.roles?.current_page || 1, last: props.roles?.last_page || 1, total: props.roles?.total || 0 }));
const hasFilter = computed(() => searchInput.value || statusFilter.value || terhapusFilter.value !== 'tidak');
const permissionCount = (r) => r?.permissions?.length || r?.permission_count || 0;
</script>

<template>
  <div>
    <Head title="Role Perusahaan | Perusahaan" />
    <ToastContainer />
    <div class="space-y-6">
      <nav class="flex items-center gap-1.5 text-sm"><Link href="/operator-perusahaan/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-sky-600 dark:hover:text-sky-400 transition-colors"><i class="fas fa-home"></i></Link><i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i><span class="text-gray-900 dark:text-white font-medium">Role Perusahaan</span></nav>
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3"><div><h2 class="text-2xl font-bold text-gray-900 dark:text-white">Role Perusahaan</h2><p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Kelola role dan permission untuk karyawan perusahaan.</p></div><button v-if="terhapusFilter !== 'ya'" @click="openCreate" class="inline-flex items-center px-4 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm" data-testid="btn-tambah"><i class="fas fa-plus mr-1.5"></i> Tambah Role</button></div>
      <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
          <div class="relative w-full sm:w-72"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-search text-gray-400 text-sm"></i></div><input v-model="searchInput" type="text" placeholder="Cari..." class="w-full pl-10 pr-16 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm focus:ring-2 focus:ring-sky-500 outline-none" @keydown.enter="applySearch" data-testid="input-search"><div class="absolute inset-y-0 right-0 flex items-center gap-1 pr-1.5"><button v-if="searchInput" @click="clearSearch" class="p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-white" title="Clear"><i class="fas fa-times text-xs"></i></button><button @click="applySearch" class="px-2 py-1 rounded bg-sky-600 text-white hover:bg-sky-700" title="Cari"><i class="fas fa-search text-xs"></i></button></div></div>
          <div class="flex gap-1 flex-wrap">
            <button v-for="s in [{v:'tidak',l:'Aktif'},{v:'ya',l:'Terhapus'}]" :key="s.v" @click="applyTerhapus(s.v)" :class="['px-3 py-2 rounded-lg text-xs font-medium border transition-colors whitespace-nowrap', terhapusFilter === s.v ? 'bg-sky-50 border-sky-300 text-sky-700 dark:bg-sky-900/30 dark:border-sky-700 dark:text-sky-400' : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400']">{{ s.l }}</button>
          </div>
          <div v-if="terhapusFilter !== 'ya'" class="flex gap-1 flex-wrap"><button v-for="s in [{v:'',l:'Semua'},{v:'Aktif',l:'Aktif'},{v:'Nonaktif',l:'Nonaktif'}]" :key="s.v" @click="applyFilters(s.v)" :class="['px-3 py-2 rounded-lg text-xs font-medium border transition-colors whitespace-nowrap', statusFilter === s.v ? 'bg-sky-50 border-sky-300 text-sky-700 dark:bg-sky-900/30 dark:border-sky-700 dark:text-sky-400' : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400']">{{ s.l }}</button></div>
          <button v-if="hasFilter" @click="resetFilters" class="inline-flex items-center gap-1 px-3 py-2 text-xs font-medium text-red-600 dark:text-red-400 hover:underline whitespace-nowrap"><i class="fas fa-times-circle"></i> Reset Filter</button>
        </div>
      </div>
      <div v-if="selectedIds.length > 0" class="flex items-center justify-between px-4 py-3 bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-800 rounded-xl shadow-sm"><span class="text-sm font-medium text-sky-700 dark:text-sky-300"><i class="fas fa-check-circle mr-1.5"></i> {{ selectedIds.length }} data dipilih</span><div class="flex items-center gap-2"><button v-if="terhapusFilter !== 'ya'" @click="bulkSetStatus('Aktif')" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-emerald-600 text-white hover:bg-emerald-700"><i class="fas fa-check mr-1"></i> Aktifkan</button><button v-if="terhapusFilter !== 'ya'" @click="bulkSetStatus('Nonaktif')" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-amber-600 text-white hover:bg-amber-700"><i class="fas fa-ban mr-1"></i> Nonaktifkan</button><button @click="bulkDelete()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-red-600 text-white hover:bg-red-700"><i class="fas fa-trash-alt mr-1"></i> Hapus</button></div></div>
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto"><table data-testid="table-data" class="w-full text-sm min-w-[700px]"><thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700"><tr><th class="px-4 py-3 w-10"><input v-model="selectAll" type="checkbox" @change="toggleSelectAll" class="rounded border-gray-300 text-sky-600" data-testid="checkbox-select-all" /></th><th @click="sort('name')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none"><span class="inline-flex items-center gap-1">Nama Role <i :class="['fas', sortIcon('name'), 'text-[10px]', sortField === 'name' ? 'text-sky-500' : 'text-gray-400']"></i></span></th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Permission</th><th @click="sort('is_active')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none"><span class="inline-flex items-center gap-1">Status <i :class="['fas', sortIcon('is_active'), 'text-[10px]', sortField === 'is_active' ? 'text-sky-500' : 'text-gray-400']"></i></span></th><th @click="sort('created_at')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none"><span class="inline-flex items-center gap-1">Tgl Dibuat <i :class="['fas', sortIcon('created_at'), 'text-[10px]', sortField === 'created_at' ? 'text-sky-500' : 'text-gray-400']"></i></span></th><th class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-400 w-24">Aksi</th></tr></thead>
        <tbody>
          <tr v-if="items.length === 0"><td colspan="6" class="px-4 py-16 text-center text-gray-400 dark:text-gray-500"><i class="fas fa-inbox text-4xl mb-3 block"></i><span class="text-sm">Tidak ada data role</span></td></tr>
          <tr v-for="item in items" :key="item.id" class="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors" :class="{'opacity-60': item.dihapus}">
            <td class="px-4 py-3"><input v-model="selectedIds" :value="item.id" type="checkbox" @change="toggleSelect(item.id)" class="rounded border-gray-300 text-sky-600" /></td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-amber-100 dark:bg-amber-900/40 flex items-center justify-center text-amber-600 dark:text-amber-400"><i class="fas fa-tag text-xs"></i></div>
                <div><span class="font-medium text-gray-900 dark:text-white">{{ item.nama_role }}</span><p v-if="item.deskripsi" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ item.deskripsi }}</p></div>
              </div>
            </td>
            <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><span class="inline-flex items-center gap-1 text-xs"><i class="fas fa-shield-alt text-sky-500 text-[10px]"></i> {{ permissionCount(item) }} permission</span></td>
            <td class="px-4 py-3"><span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium', statusBadgeClass(item.status)]">{{ item.status }}</span></td>
            <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">{{ item.created_at }}</td>
            <td class="px-4 py-3">
              <div class="flex items-center justify-center gap-1">
                <button v-if="!item.dihapus" @click="openDetail(item)" title="Detail" class="p-1.5 rounded-lg text-gray-400 hover:text-sky-600 hover:bg-sky-50 dark:hover:text-sky-400 dark:hover:bg-indigo-900/30"><i class="fas fa-eye"></i></button>
                <button v-if="!item.dihapus" @click="openEdit(item)" title="Edit" class="p-1.5 rounded-lg text-gray-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:text-amber-400 dark:hover:bg-amber-900/30"><i class="fas fa-edit"></i></button>
                <button v-if="!item.dihapus" @click="openDelete(item)" title="Hapus" class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:text-red-400 dark:hover:bg-red-900/30"><i class="fas fa-trash-alt"></i></button>
                <button v-if="item.dihapus" @click="confirmRestore(item.id)" title="Pulihkan" class="p-1.5 rounded-lg text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:text-emerald-400 dark:hover:bg-emerald-900/30"><i class="fas fa-undo"></i></button>
              </div>
            </td>
          </tr>
        </tbody></table></div>
        <div class="flex flex-col sm:flex-row items-center justify-between px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
          <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 mb-3 sm:mb-0"><span>Tampilkan</span><select v-model="perPage" @change="changePerPage(perPage)" class="px-2 py-1 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm"><option v-for="n in perPageOptions" :key="n" :value="n">{{ n }}</option></select><span>dari {{ pagination.total }} data</span></div>
          <div class="flex items-center gap-1"><button @click="goToPage(1)" :disabled="pagination.current <= 1" class="px-2 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-30"><i class="fas fa-angle-double-left"></i></button><button @click="goToPage(pagination.current-1)" :disabled="pagination.current <= 1" class="px-2 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-30"><i class="fas fa-angle-left"></i></button><span class="px-2 text-sm text-gray-500">{{ pagination.current }} / {{ pagination.last }}</span><button @click="goToPage(pagination.current+1)" :disabled="pagination.current >= pagination.last" class="px-2 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-30"><i class="fas fa-angle-right"></i></button><button @click="goToPage(pagination.last)" :disabled="pagination.current >= pagination.last" class="px-2 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-30"><i class="fas fa-angle-double-right"></i></button></div>
        </div>
      </div>
    </div>
    <Teleport to="body"><Transition name="modal"><div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showDetailModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col"><div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-eye mr-2 text-sky-500"></i>Detail Role</h3><button @click="showDetailModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button></div><div class="overflow-y-auto flex-1 px-6 py-5 space-y-5 modal-scroll" v-if="selectedItem"><div class="grid grid-cols-1 sm:grid-cols-2 gap-3"><div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Nama Role</label><p class="text-sm font-semibold text-gray-900 dark:text-white mt-0.5">{{ selectedItem.nama_role }}</p></div><div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Status</label><p class="mt-0.5"><span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium', statusBadgeClass(selectedItem.status)]">{{ selectedItem.status }}</span></p></div><div class="sm:col-span-2"><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Deskripsi</label><p class="text-sm text-gray-900 dark:text-white mt-0.5">{{ selectedItem.deskripsi || '—' }}</p></div><div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Tgl Dibuat</label><p class="text-sm text-gray-900 dark:text-white mt-0.5">{{ selectedItem.created_at }}</p></div></div><div v-if="selectedItem.permissions?.length"><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Permission ({{ selectedItem.permissions.length }})</label><div class="mt-2 border border-gray-200 dark:border-gray-700 rounded-xl divide-y divide-gray-200 dark:divide-gray-700 max-h-64 overflow-y-auto"><div v-for="(perms, module) in groupedPermsForDetail(selectedItem.permissions)" :key="module" class="px-3 py-2"><div class="text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">{{ module }}</div><div class="flex flex-wrap gap-1"><span v-for="p in perms" :key="p.id" class="inline-flex px-2 py-0.5 rounded text-xs bg-sky-50 dark:bg-sky-900/20 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800" :title="p.nama">{{ p.nama.split('.').slice(-1)[0] }}</span></div></div></div></div></div></div></div></Transition></Teleport>
    <Teleport to="body"><Transition name="modal"><div v-if="showCreateModal || showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showCreateModal = showEditModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col"><div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i :class="['fas mr-2', showCreateModal ? 'fa-plus text-emerald-500' : 'fa-edit text-amber-500']"></i>{{ showCreateModal ? 'Tambah Role Perusahaan' : 'Edit Role Perusahaan' }}</h3><button @click="showCreateModal = showEditModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button></div><form class="flex-1 overflow-y-auto px-6 py-5 space-y-4 modal-scroll" @submit.prevent="showCreateModal ? submitCreate() : submitEdit()">
        <FormErrorSummary :errors="(showCreateModal ? createForm : editForm).errors" />
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nama Role <span class="text-red-500">*</span></label><input v-model="(showCreateModal ? createForm : editForm).nama_role" type="text" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', (showCreateModal ? createForm : editForm).errors.nama_role ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500 focus:border-sky-500']" /><p v-if="(showCreateModal ? createForm : editForm).errors.nama_role" class="text-red-500 text-xs mt-1">{{ (showCreateModal ? createForm : editForm).errors.nama_role }}</p></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Deskripsi</label><textarea v-model="(showCreateModal ? createForm : editForm).deskripsi" rows="2" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors resize-none bg-white dark:bg-gray-900 text-gray-900 dark:text-white', (showCreateModal ? createForm : editForm).errors.deskripsi ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500 focus:border-sky-500']"></textarea><p v-if="(showCreateModal ? createForm : editForm).errors.deskripsi" class="text-red-500 text-xs mt-1">{{ (showCreateModal ? createForm : editForm).errors.deskripsi }}</p></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Status <span class="text-red-500">*</span></label><select v-model="(showCreateModal ? createForm : editForm).status" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', (showCreateModal ? createForm : editForm).errors.status ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500 focus:border-sky-500']"><option value="Aktif">Aktif</option><option value="Nonaktif">Nonaktif</option></select><p v-if="(showCreateModal ? createForm : editForm).errors.status" class="text-red-500 text-xs mt-1">{{ (showCreateModal ? createForm : editForm).errors.status }}</p></div>
        <div :class="(showCreateModal ? createForm : editForm).errors.permission_ids ? 'rounded-lg ring-2 ring-red-400 p-2' : ''"><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Permission</label><PermissionGroupChecklist :permissions="props.availablePermissions || []" v-model="(showCreateModal ? createForm : editForm).permission_ids" color="sky" /><p v-if="(showCreateModal ? createForm : editForm).errors.permission_ids" class="text-red-500 text-xs mt-1">{{ (showCreateModal ? createForm : editForm).errors.permission_ids }}</p></div>
        <div class="shrink-0 flex justify-end gap-2 pt-2 border-t border-gray-200 dark:border-gray-700 -mx-6 -mb-5 px-6 py-4 bg-white dark:bg-gray-800"><button type="button" @click="showCreateModal = showEditModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">Batal</button><button type="submit" class="px-6 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 shadow-sm"><i class="fas fa-save mr-1.5"></i>{{ showCreateModal ? 'Simpan' : 'Update' }}</button></div>
      </form></div></div></Transition></Teleport>
    <Teleport to="body"><Transition name="modal"><div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showDeleteModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-sm"><div class="px-6 py-5 text-center"><div class="w-14 h-14 mx-auto rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-4"><i class="fas fa-exclamation-triangle text-red-500 text-xl"></i></div><h3 class="text-lg font-semibold mb-2">Hapus Role?</h3><p class="text-sm text-gray-500">Anda akan menghapus role <strong>{{ selectedItem?.nama_role }}</strong>.</p></div><div class="flex justify-center gap-3 px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700"><button @click="showDeleteModal = false" class="px-5 py-2.5 bg-gray-200 dark:bg-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">Batal</button><button @click="confirmDelete" class="px-5 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700"><i class="fas fa-trash-alt mr-1.5"></i> Hapus</button></div></div></div></Transition></Teleport>
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
