<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import KaryawanLayout from '@/Layouts/KaryawanLayout.vue';
import { useToast } from '@/Composables/useToast';
import ToastContainer from '@/Components/ToastContainer.vue';

defineOptions({ layout: KaryawanLayout });

const props = defineProps({ insentifs: Object, filters: Object });
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
  router.get('/karyawan/insentif-saya', buildQuery(o), { preserveState: true, preserveScroll: true, replace: true });
}
function applySearch() { fetchData({ search: searchInput.value || undefined, status: statusFilter.value || undefined, terhapus: terhapusFilter.value, page: 1 }); }
function clearSearch() { searchInput.value = ''; fetchData({ search: undefined, status: statusFilter.value || undefined, terhapus: terhapusFilter.value, page: 1 }); }
function applyFilters(s) { statusFilter.value = s; fetchData({ status: s || undefined, terhapus: terhapusFilter.value, page: 1 }); }
function applyTerhapus(t) { terhapusFilter.value = t; statusFilter.value = ''; fetchData({ terhapus: t, status: undefined, page: 1 }); }
function resetFilters() { searchInput.value = ''; statusFilter.value = ''; terhapusFilter.value = 'tidak'; fetchData({ search: undefined, status: undefined, terhapus: 'tidak', page: 1 }); }
function sort(f) {
  if (sortField.value === f) { if (sortDir.value === 'asc') sortDir.value = 'desc'; else { sortField.value = ''; sortDir.value = 'asc'; } }
  else { sortField.value = f; sortDir.value = 'asc'; }
  fetchData({ sort_field: sortField.value || undefined, sort_dir: sortDir.value || undefined });
}
function sortIcon(f) { if (sortField.value !== f) return 'fa-sort'; return sortDir.value === 'asc' ? 'fa-sort-up' : 'fa-sort-down'; }
function toggleSelectAll() { selectedIds.value = selectAll.value ? items.value.map(i => i.id) : []; }
function toggleSelect(id) { const i = selectedIds.value.indexOf(id); i === -1 ? selectedIds.value.push(id) : selectedIds.value.splice(i, 1); }
function goToPage(p) { fetchData({ page: p }); }
function changePerPage(n) { perPage.value = n; fetchData({ per_page: n, page: 1 }); }
function statusBadgeClass(s) { return s === 'Aktif' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'; }

const createForm = useForm({ name: '', type: 'fixed', value: 0, status: 'Aktif', description: '' });
const editForm = useForm({ name: '', type: 'fixed', value: 0, status: 'Aktif', description: '' });

function openCreate() { createForm.reset(); showCreateModal.value = true; }
function submitCreate() { createForm.post('/karyawan/insentif-saya', { onSuccess: () => { showCreateModal.value = false; fetchData(); toast.success('Insentif berhasil ditambahkan.'); } }); }
function openEdit(item) { editForm.defaults({ name: item.name, type: item.type, value: item.value, status: item.status, description: item.description || '' }); editForm.reset(); selectedItem.value = item; showEditModal.value = true; }
function submitEdit() { editForm.put('/karyawan/insentif-saya/' + selectedItem.value.id, { onSuccess: () => { showEditModal.value = false; fetchData(); toast.success('Insentif berhasil diperbarui.'); } }); }
function openDetail(item) { selectedItem.value = item; showDetailModal.value = true; }
function openDelete(item) { selectedItem.value = item; showDeleteModal.value = true; }
function confirmDelete() { router.delete('/karyawan/insentif-saya/' + selectedItem.value.id, { onSuccess: () => { showDeleteModal.value = false; fetchData(); toast.success('Insentif berhasil dihapus.'); } }); }
function confirmRestore(id) { router.patch('/karyawan/insentif-saya/' + id + '/restore', { onSuccess: () => { fetchData(); toast.success('Insentif berhasil dipulihkan.'); } }); }
function bulkDelete() { router.post('/operator-perusahaan/insentif/bulk-delete', { ids: selectedIds.value }, { onSuccess: () => { selectedIds.value = []; selectAll.value = false; fetchData(); toast.success('Insentif berhasil dihapus.'); } }); }
function bulkSetStatus(s) { router.post('/operator-perusahaan/insentif/bulk-status', { ids: selectedIds.value, status: s }, { onSuccess: () => { selectedIds.value = []; selectAll.value = false; fetchData(); toast.success('Status berhasil diubah.'); } }); }

const items = computed(() => props.insentifs?.data || []);
const pagination = computed(() => ({ current: props.insentifs?.current_page || 1, last: props.insentifs?.last_page || 1, total: props.insentifs?.total || 0 }));
const hasFilter = computed(() => searchInput.value || statusFilter.value || terhapusFilter.value !== 'tidak');
</script>

<template>
  <div>
    <Head title="Insentif Saya | Karyawan" />
    <ToastContainer />
    <div class="space-y-6">
      <nav class="flex items-center gap-1.5 text-sm"><Link href="/karyawan/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-amber-600 dark:hover:text-amber-400 transition-colors"><i class="fas fa-home"></i></Link><i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i><span class="text-gray-900 dark:text-white font-medium">Insentif Saya</span></nav>
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3"><div><h2 class="text-2xl font-bold text-gray-900 dark:text-white">Insentif Saya</h2><p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Data insentif yang Anda dapatkan.</p></div><button v-if="terhapusFilter !== 'ya'" @click="openCreate" class="inline-flex items-center px-4 py-2.5 bg-amber-600 text-white text-sm font-medium rounded-lg hover:bg-amber-700 transition-colors shadow-sm"><i class="fas fa-plus mr-1.5"></i> Tambah Insentif</button></div>
      <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
          <div class="relative w-full sm:w-72"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-search text-gray-400 text-sm"></i></div><input v-model="searchInput" type="text" placeholder="Cari..." class="w-full pl-10 pr-16 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm focus:ring-2 focus:ring-amber-500 outline-none" @keydown.enter="applySearch" /><div class="absolute inset-y-0 right-0 flex items-center gap-1 pr-1.5"><button v-if="searchInput" @click="clearSearch" class="p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-white" title="Clear"><i class="fas fa-times text-xs"></i></button><button @click="applySearch" class="px-2 py-1 rounded bg-amber-600 text-white hover:bg-amber-700" title="Cari"><i class="fas fa-search text-xs"></i></button></div></div>
          <div class="flex gap-1 flex-wrap"><button v-for="s in [{v:'tidak',l:'Aktif'},{v:'ya',l:'Terhapus'}]" :key="s.v" @click="applyTerhapus(s.v)" :class="['px-3 py-2 rounded-lg text-xs font-medium border transition-colors whitespace-nowrap', terhapusFilter === s.v ? 'bg-amber-50 border-amber-300 text-amber-700 dark:bg-amber-900/30 dark:border-amber-700 dark:text-amber-400' : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400']">{{ s.l }}</button></div>
          <div v-if="terhapusFilter !== 'ya'" class="flex gap-1 flex-wrap"><button v-for="s in [{v:'',l:'Semua'},{v:'Aktif',l:'Aktif'},{v:'Nonaktif',l:'Nonaktif'}]" :key="s.v" @click="applyFilters(s.v)" :class="['px-3 py-2 rounded-lg text-xs font-medium border transition-colors whitespace-nowrap', statusFilter === s.v ? 'bg-amber-50 border-amber-300 text-amber-700 dark:bg-amber-900/30 dark:border-amber-700 dark:text-amber-400' : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400']">{{ s.l }}</button></div>
          <button v-if="hasFilter" @click="resetFilters" class="inline-flex items-center gap-1 px-3 py-2 text-xs font-medium text-red-600 dark:text-red-400 hover:underline whitespace-nowrap"><i class="fas fa-times-circle"></i> Reset Filter</button>
        </div>
      </div>
      <div v-if="selectedIds.length > 0" class="flex items-center justify-between px-4 py-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl shadow-sm"><span class="text-sm font-medium text-amber-700 dark:text-amber-300"><i class="fas fa-check-circle mr-1.5"></i> {{ selectedIds.length }} data dipilih</span><div class="flex items-center gap-2"><button v-if="terhapusFilter !== 'ya'" @click="bulkSetStatus('Aktif')" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-emerald-600 text-white hover:bg-emerald-700"><i class="fas fa-check mr-1"></i> Aktifkan</button><button v-if="terhapusFilter !== 'ya'" @click="bulkSetStatus('Nonaktif')" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-red-600 text-white hover:bg-red-700"><i class="fas fa-ban mr-1"></i> Nonaktifkan</button><button @click="bulkDelete()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-red-600 text-white hover:bg-red-700"><i class="fas fa-trash-alt mr-1"></i> Hapus</button></div></div>
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto"><table class="w-full text-sm min-w-[700px]"><thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700"><tr><th class="px-4 py-3 w-10"><input v-model="selectAll" type="checkbox" @change="toggleSelectAll" class="rounded border-gray-300 text-amber-600" /></th><th @click="sort('name')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none"><span class="inline-flex items-center gap-1">Nama <i :class="['fas', sortIcon('name'), 'text-[10px]', sortField === 'name' ? 'text-amber-500' : 'text-gray-400']"></i></span></th><th @click="sort('type')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none"><span class="inline-flex items-center gap-1">Tipe <i :class="['fas', sortIcon('type'), 'text-[10px]', sortField === 'type' ? 'text-amber-500' : 'text-gray-400']"></i></span></th><th @click="sort('value')" class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none"><span class="inline-flex items-center gap-1">Nilai <i :class="['fas', sortIcon('value'), 'text-[10px]', sortField === 'value' ? 'text-amber-500' : 'text-gray-400']"></i></span></th><th @click="sort('is_active')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none"><span class="inline-flex items-center gap-1">Status <i :class="['fas', sortIcon('is_active'), 'text-[10px]', sortField === 'is_active' ? 'text-amber-500' : 'text-gray-400']"></i></span></th><th class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-400 w-24">Aksi</th></tr></thead>
        <tbody><tr v-if="items.length === 0"><td colspan="6" class="px-4 py-16 text-center text-gray-400 dark:text-gray-500"><i class="fas fa-inbox text-4xl mb-3 block"></i><span class="text-sm">Tidak ada data insentif</span></td></tr>
        <tr v-for="item in items" :key="item.id" class="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors" :class="{'opacity-60': item.dihapus}"><td class="px-4 py-3"><input v-model="selectedIds" :value="item.id" type="checkbox" @change="toggleSelect(item.id)" class="rounded border-gray-300 text-amber-600" /></td><td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ item.name }}</td><td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ item.type === 'percentage' ? 'Persentase' : 'Nominal' }}</td><td class="px-4 py-3 text-right text-gray-900 dark:text-white font-mono text-xs">{{ item.type === 'percentage' ? item.value + '%' : 'Rp ' + Number(item.value).toLocaleString('id') }}</td><td class="px-4 py-3"><span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium', statusBadgeClass(item.status)]">{{ item.status }}</span></td><td class="px-4 py-3"><div class="flex items-center justify-center gap-1"><button v-if="!item.dihapus" @click="openDetail(item)" title="Detail" class="p-1.5 rounded-lg text-gray-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:text-amber-400 dark:hover:bg-amber-900/30"><i class="fas fa-eye"></i></button><button v-if="!item.dihapus" @click="openEdit(item)" title="Edit" class="p-1.5 rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:text-indigo-400 dark:hover:bg-indigo-900/30"><i class="fas fa-edit"></i></button><button v-if="!item.dihapus" @click="openDelete(item)" title="Hapus" class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:text-red-400 dark:hover:bg-red-900/30"><i class="fas fa-trash-alt"></i></button><button v-if="item.dihapus" @click="confirmRestore(item.id)" title="Pulihkan" class="p-1.5 rounded-lg text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:text-emerald-400 dark:hover:bg-emerald-900/30"><i class="fas fa-undo"></i></button></div></td></tr></tbody></table></div>
        <div class="flex flex-col sm:flex-row items-center justify-between px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900"><div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 mb-3 sm:mb-0"><span>Tampilkan</span><select v-model="perPage" @change="changePerPage(perPage)" class="px-2 py-1 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm"><option v-for="n in perPageOptions" :key="n" :value="n">{{ n }}</option></select><span>dari {{ pagination.total }} data</span></div><div class="flex items-center gap-1"><button @click="goToPage(1)" :disabled="pagination.current <= 1" class="px-2 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-30"><i class="fas fa-angle-double-left"></i></button><button @click="goToPage(pagination.current-1)" :disabled="pagination.current <= 1" class="px-2 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-30"><i class="fas fa-angle-left"></i></button><span class="px-2 text-sm text-gray-500">{{ pagination.current }} / {{ pagination.last }}</span><button @click="goToPage(pagination.current+1)" :disabled="pagination.current >= pagination.last" class="px-2 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-30"><i class="fas fa-angle-right"></i></button><button @click="goToPage(pagination.last)" :disabled="pagination.current >= pagination.last" class="px-2 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-30"><i class="fas fa-angle-double-right"></i></button></div></div>
      </div>
    </div>
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
