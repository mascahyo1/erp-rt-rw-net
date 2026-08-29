<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import OperatorPerusahaanLayout from '@/Layouts/OperatorPerusahaanLayout.vue';
import { useToast } from '@/Composables/useToast';
import ToastContainer from '@/Components/ToastContainer.vue';
import FormErrorSummary from '@/Components/FormErrorSummary.vue';
import { errorSummary } from '@/Composables/useFormErrorToast';

defineOptions({ layout: OperatorPerusahaanLayout });

const props = defineProps({ configs: Object, filters: Object });
const toast = useToast();
const page = usePage();
const can = (perm) => page.props.permissions?.includes(perm);

const searchInput = ref(props.filters?.search || '');
const typeFilter = ref(props.filters?.type || '');
const terhapusFilter = ref(props.filters?.terhapus || 'tidak');
const sortField = ref(props.filters?.sort_field || '');
const sortDir = ref(props.filters?.sort_dir || 'asc');
const perPage = ref(props.filters?.per_page ? Number(props.filters.per_page) : 10);
const perPageOptions = [5, 10, 25, 50, 100];
const selectedIds = ref([]);
// Select-all checkbox: computed so it's always in sync with selectedIds
const selectAll = computed({
    get: () => items.value.length > 0 && selectedIds.value.length === items.value.length,
    set: (val) => { selectedIds.value = val ? items.value.map(c => c.id) : []; }
});
const selectedItem = ref(null);
const showCreateModal = ref(false); const showDetailModal = ref(false);
const showEditModal = ref(false); const showDeleteModal = ref(false);
const showImportModal = ref(false);

// Value masking state: per-row for datatable, per-modal for create/edit/detail
const revealedIds = ref(new Set());
const detailValueVisible = ref(false);
// Kredensial: toggle input type between "password" (masked) and "text" (visible).
// Default = password (masked). Click eye to reveal as plain text.
const kredensialReveal = ref(false);

function buildQuery(o = {}) {
  const p = { ...o };
  if (p.search === undefined) p.search = searchInput.value || undefined;
  if (p.type === undefined) p.type = typeFilter.value || undefined;
  if (p.terhapus === undefined) p.terhapus = terhapusFilter.value === 'tidak' ? undefined : terhapusFilter.value;
  if (p.sort_field === undefined) p.sort_field = sortField.value || undefined;
  if (p.sort_dir === undefined) p.sort_dir = sortDir.value || undefined;
  if (p.per_page === undefined) p.per_page = perPage.value;
  Object.keys(p).forEach(k => { if (p[k] === undefined) delete p[k]; });
  return p;
}
function fetchData(o = {}) {
  router.get('/operator-perusahaan/konfigurasi-perusahaan', buildQuery(o), { preserveState: true, preserveScroll: true, replace: true });
}
function applySearch() { fetchData({ search: searchInput.value || undefined, type: typeFilter.value || undefined, terhapus: terhapusFilter.value === 'tidak' ? undefined : terhapusFilter.value, page: 1 }); }
function clearSearch() { searchInput.value = ''; fetchData({ search: undefined, type: typeFilter.value || undefined, terhapus: terhapusFilter.value === 'tidak' ? undefined : terhapusFilter.value, page: 1 }); }
function applyTypeFilter(t) { typeFilter.value = t; fetchData({ type: t || undefined, page: 1 }); }
function applyTerhapusFilter(t) { terhapusFilter.value = t; fetchData({ terhapus: t === 'tidak' ? undefined : t, page: 1 }); }
function resetFilters() { searchInput.value = ''; typeFilter.value = ''; terhapusFilter.value = 'tidak'; fetchData({ search: undefined, type: undefined, terhapus: undefined, page: 1 }); }
function sort(f) {
  if (sortField.value === f) { if (sortDir.value === 'asc') sortDir.value = 'desc'; else { sortField.value = ''; sortDir.value = 'asc'; } }
  else { sortField.value = f; sortDir.value = 'asc'; }
  fetchData({ sort_field: sortField.value || undefined, sort_dir: sortDir.value || undefined });
}
function sortIcon(f) { if (sortField.value !== f) return 'fa-sort'; return sortDir.value === 'asc' ? 'fa-sort-up' : 'fa-sort-down'; }
function goToPage(p) { fetchData({ page: p }); }
function changePerPage(n) { perPage.value = n; fetchData({ per_page: n, page: 1 }); }
function typeBadgeClass(t) {
  return {
    'text': 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400',
    'file': 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400',
    'number': 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    'boolean': 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    'kredensial': 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
  }[t] || 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
}
function typeIcon(t) {
  return { text: 'fa-font', file: 'fa-file', number: 'fa-hashtag', boolean: 'fa-toggle-on', kredensial: 'fa-key' }[t] || 'fa-circle';
}
function typeLabel(t) {
  return { text: 'Teks', file: 'File', number: 'Angka', boolean: 'Boolean', kredensial: 'Kredensial' }[t] || t;
}
function toggleReveal(id) {
  if (revealedIds.value.has(id)) revealedIds.value.delete(id);
  else revealedIds.value.add(id);
  // trigger reactivity for Set
  revealedIds.value = new Set(revealedIds.value);
}

const createForm = useForm({ key: '', type: 'text', value: '', description: '' });
const editForm = useForm({ key: '', type: 'text', value: '', description: '' });
const importForm = useForm({ file: null });
const importing = ref(false);

function openCreate() { createForm.reset(); createForm.clearErrors(); createForm.type = 'text'; kredensialReveal.value = false; showCreateModal.value = true; }
function submitCreate() { createForm.post('/operator-perusahaan/konfigurasi-perusahaan', { onSuccess: () => { showCreateModal.value = false; fetchData(); toast.success('Konfigurasi berhasil ditambahkan.'); }, onError: () => toast.error('Validasi gagal: ' + errorSummary(createForm.errors), 6000) }); }
function openEdit(item) { editForm.defaults({ key: item.key, type: item.type, value: item.value, description: item.description || '' }); editForm.reset(); editForm.clearErrors(); selectedItem.value = item; kredensialReveal.value = false; showEditModal.value = true; }
function submitEdit() { editForm.transform(data => ({...data, _method: 'PUT'})).post('/operator-perusahaan/konfigurasi-perusahaan/' + selectedItem.value.id, { onSuccess: () => { showEditModal.value = false; fetchData(); toast.success('Konfigurasi berhasil diperbarui.'); }, onError: () => toast.error('Validasi gagal: ' + errorSummary(editForm.errors), 6000) }); }
function openDetail(item) { selectedItem.value = item; detailValueVisible.value = false; showDetailModal.value = true; }
function openDelete(item) { selectedItem.value = item; showDeleteModal.value = true; }
function confirmDelete() { router.delete('/operator-perusahaan/konfigurasi-perusahaan/' + selectedItem.value.id, { onSuccess: () => { showDeleteModal.value = false; fetchData(); toast.success('Konfigurasi berhasil dihapus.'); } }); }
function bulkDelete() { router.post('/operator-perusahaan/konfigurasi-perusahaan/bulk-delete', { ids: selectedIds.value }, { onSuccess: () => { selectedIds.value = []; fetchData(); toast.success('Konfigurasi berhasil dihapus.'); } }); }
function bulkRestore() { router.post('/operator-perusahaan/konfigurasi-perusahaan/bulk-restore', { ids: selectedIds.value }, { onSuccess: () => { selectedIds.value = []; fetchData(); toast.success('Konfigurasi berhasil dipulihkan.'); } }); }

function openImport() { importForm.reset(); showImportModal.value = true; }
function submitImport() { importing.value = true; importForm.post('/operator-perusahaan/konfigurasi-perusahaan/import', { onSuccess: () => { showImportModal.value = false; importing.value = false; fetchData(); toast.success('Import berhasil.'); }, onError: () => { importing.value = false; toast.error('Import gagal: ' + errorSummary(importForm.errors), 6000); } }); }
function downloadTemplate() { window.location.href = '/operator-perusahaan/konfigurasi-perusahaan/template'; }
function buildFilterParams() {
  const params = new URLSearchParams();
  if (searchInput.value) params.set('search', searchInput.value);
  if (typeFilter.value) params.set('type', typeFilter.value);
  if (terhapusFilter.value !== 'tidak') params.set('terhapus', terhapusFilter.value);
  return params.toString();
}
function exportAll() {
  const params = buildFilterParams();
  window.location.href = '/operator-perusahaan/konfigurasi-perusahaan/export' + (params ? '?' + params : '');
}
function exportSelected() {
  if (selectedIds.value.length > 0) {
    window.location.href = '/operator-perusahaan/konfigurasi-perusahaan/export?ids=' + selectedIds.value.join(',');
  } else {
    const params = buildFilterParams();
    window.location.href = '/operator-perusahaan/konfigurasi-perusahaan/export' + (params ? '?' + params : '');
  }
}

const items = computed(() => props.configs?.data || []);
const pagination = computed(() => ({ current: props.configs?.current_page || 1, last: props.configs?.last_page || 1, total: props.configs?.total || 0 }));
const hasFilter = computed(() => searchInput.value || typeFilter.value || terhapusFilter.value !== 'tidak');
const isTrashedView = computed(() => terhapusFilter.value === 'ya');
</script>

<template>
  <div>
    <Head title="Konfigurasi Perusahaan | Perusahaan" />
    <ToastContainer />
    <div class="space-y-6">
      <nav class="flex items-center gap-1.5 text-sm"><Link href="/operator-perusahaan/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-sky-600 dark:hover:text-sky-400 transition-colors"><i class="fas fa-home"></i></Link><i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i><span class="text-gray-900 dark:text-white font-medium">Konfigurasi</span></nav>
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div><h2 class="text-2xl font-bold text-gray-900 dark:text-white">Konfigurasi</h2><p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Kelola konfigurasi khusus perusahaan.</p></div>
        <div class="flex flex-wrap items-center gap-2">
          <button v-if="can('konfigurasi-perusahaan.import')" @click="openImport" class="inline-flex items-center px-3 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors shadow-sm" data-testid="btn-import"><i class="fas fa-file-import mr-1.5"></i> Import</button>
          <button v-if="can('konfigurasi-perusahaan.import')" @click="downloadTemplate" class="inline-flex items-center px-3 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors shadow-sm"><i class="fas fa-download mr-1.5"></i> Template</button>
          <div v-if="can('konfigurasi-perusahaan.export')" class="relative group">
            <button class="inline-flex items-center px-3 py-2 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm"><i class="fas fa-file-export mr-1.5"></i> Export <i class="fas fa-chevron-down ml-1.5 text-xs"></i></button>
            <div class="absolute right-0 mt-1 w-44 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-lg py-1 z-30 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all">
              <button @click="exportAll" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700" data-testid="btn-export"><i class="fas fa-list mr-2"></i> Export Semua</button>
              <button @click="exportSelected" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"><i class="fas fa-check-square mr-2"></i> Export Selected{{ selectedIds.length > 0 ? ' (' + selectedIds.length + ')' : '' }}</button>
            </div>
          </div>
          <button v-if="can('konfigurasi-perusahaan.create') && !isTrashedView" @click="openCreate" class="inline-flex items-center px-4 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm" data-testid="btn-tambah"><i class="fas fa-plus mr-1.5"></i> Tambah</button>
        </div>
      </div>
      <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto flex-wrap">
          <div class="relative w-full sm:w-72"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-search text-gray-400 text-sm"></i></div><input v-model="searchInput" type="text" placeholder="Cari key atau value..." class="w-full pl-10 pr-16 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm focus:ring-2 focus:ring-sky-500 outline-none" @keydown.enter="applySearch" data-testid="input-search"><div class="absolute inset-y-0 right-0 flex items-center gap-1 pr-1.5"><button v-if="searchInput" @click="clearSearch" class="p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-white" title="Clear"><i class="fas fa-times text-xs"></i></button><button @click="applySearch" class="px-2 py-1 rounded bg-sky-600 text-white hover:bg-sky-700" title="Cari"><i class="fas fa-search text-xs"></i></button></div></div>
          <div class="flex gap-1 flex-wrap">
            <button v-for="t in [{v:'',l:'Semua'},{v:'text',l:'Teks'},{v:'file',l:'File'},{v:'number',l:'Angka'},{v:'boolean',l:'Boolean'},{v:'kredensial',l:'Kredensial'}]" :key="t.v" @click="applyTypeFilter(t.v)" :class="['px-3 py-2 rounded-lg text-xs font-medium border transition-colors whitespace-nowrap', typeFilter === t.v ? 'bg-sky-50 border-sky-300 text-sky-700 dark:bg-sky-900/30 dark:border-sky-700 dark:text-sky-400' : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400']"><i v-if="t.v" :class="['fas mr-1', typeIcon(t.v)]"></i>{{ t.l }}</button>
          </div>
          <div class="flex gap-1 flex-wrap">
            <button v-for="t in [{v:'tidak',l:'Aktif'},{v:'ya',l:'Terhapus'}]" :key="t.v" @click="applyTerhapusFilter(t.v)" :class="['px-3 py-2 rounded-lg text-xs font-medium border transition-colors whitespace-nowrap', terhapusFilter === t.v ? (t.v === 'ya' ? 'bg-red-50 border-red-300 text-red-700 dark:bg-red-900/30 dark:border-red-700 dark:text-red-400' : 'bg-sky-50 border-sky-300 text-sky-700 dark:bg-sky-900/30 dark:border-sky-700 dark:text-sky-400') : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400']"><i :class="['fas mr-1', t.v === 'ya' ? 'fa-trash-alt' : 'fa-check-circle']"></i>{{ t.l }}</button>
          </div>
          <button v-if="hasFilter" @click="resetFilters" class="inline-flex items-center gap-1 px-3 py-2 text-xs font-medium text-red-600 dark:text-red-400 hover:underline whitespace-nowrap"><i class="fas fa-times-circle"></i> Reset Filter</button>
        </div>
      </div>
      <div v-if="selectedIds.length > 0" class="flex items-center justify-between px-4 py-3 bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-800 rounded-xl shadow-sm">
        <span class="text-sm font-medium text-sky-700 dark:text-sky-300"><i class="fas fa-check-circle mr-1.5"></i> {{ selectedIds.length }} data dipilih</span>
        <div class="flex items-center gap-2">
          <template v-if="isTrashedView">
            <button v-if="can('konfigurasi-perusahaan.restore')" @click="bulkRestore()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-emerald-600 text-white hover:bg-emerald-700"><i class="fas fa-undo-alt mr-1"></i> Pulihkan</button>
          </template>
          <template v-else>
            <button v-if="can('konfigurasi-perusahaan.delete')" @click="bulkDelete()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-red-600 text-white hover:bg-red-700"><i class="fas fa-trash-alt mr-1"></i> Hapus</button>
          </template>
        </div>
      </div>
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto"><table data-testid="table-data" class="w-full text-sm min-w-[800px]"><thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700"><tr><th class="px-4 py-3 w-10"><input v-model="selectAll" type="checkbox" @change="toggleSelectAll" class="rounded border-gray-300 text-sky-600" data-testid="checkbox-select-all" /></th><th @click="sort('key')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none"><span class="inline-flex items-center gap-1">Key <i :class="['fas', sortIcon('key'), 'text-[10px]', sortField === 'key' ? 'text-sky-500' : 'text-gray-400']"></i></span></th><th @click="sort('type')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none w-28"><span class="inline-flex items-center gap-1">Tipe <i :class="['fas', sortIcon('type'), 'text-[10px]', sortField === 'type' ? 'text-sky-500' : 'text-gray-400']"></i></span></th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Value</th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Description</th><th @click="sort('created_at')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none"><span class="inline-flex items-center gap-1">Tgl Dibuat <i :class="['fas', sortIcon('created_at'), 'text-[10px]', sortField === 'created_at' ? 'text-sky-500' : 'text-gray-400']"></i></span></th><th class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-400 w-24">Aksi</th></tr></thead>
        <tbody>
          <tr v-if="items.length === 0"><td colspan="7" class="px-4 py-16 text-center text-gray-400 dark:text-gray-500"><i class="fas fa-inbox text-4xl mb-3 block"></i><span class="text-sm">Tidak ada data konfigurasi</span></td></tr>
          <tr v-for="item in items" :key="item.id" @click="(e) => { if (!e.target.closest('button') && !e.target.closest('input') && !e.target.closest('a')) selectedIds = selectedIds.includes(item.id) ? selectedIds.filter(x => x !== item.id) : [...selectedIds, item.id]; }" :class="['border-t border-gray-100 dark:border-gray-700 cursor-pointer transition-colors', item.dihapus ? 'opacity-60' : '', 'hover:bg-gray-50 dark:hover:bg-gray-700/30']">
            <td class="px-4 py-3" @click.stop><input v-model="selectedIds" :value="item.id" type="checkbox" class="rounded border-gray-300 text-sky-600" /></td>
            <td class="px-4 py-3"><code class="text-sm font-mono text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">{{ item.key }}</code></td>
            <td class="px-4 py-3"><span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', typeBadgeClass(item.type)]"><i :class="['fas mr-1 text-[10px]', typeIcon(item.type)]"></i>{{ typeLabel(item.type) }}</span></td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-2 max-w-xs">
                <code v-if="item.type === 'kredensial'" class="font-mono text-xs truncate" :class="revealedIds.has(item.id) ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-500 select-none'">{{ revealedIds.has(item.id) ? item.value : '••••••••' }}</code>
                <code v-else class="font-mono text-xs truncate text-gray-900 dark:text-white">{{ item.value }}</code>
                <button v-if="item.type === 'kredensial'" @click.stop="toggleReveal(item.id)" :title="revealedIds.has(item.id) ? 'Sembunyikan value' : 'Tampilkan value'" class="shrink-0 p-1 rounded text-gray-400 hover:text-sky-600 dark:hover:text-sky-400"><i :class="['fas text-xs', revealedIds.has(item.id) ? 'fa-eye-slash' : 'fa-eye']"></i></button>
              </div>
            </td>
            <td class="px-4 py-3 text-gray-500 dark:text-gray-400 max-w-xs truncate text-xs">{{ item.description || '-' }}</td>
            <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs whitespace-nowrap">{{ item.created_at }}</td>
            <td class="px-4 py-3" @click.stop>
              <div class="flex items-center justify-center gap-1">
                <button v-if="can('konfigurasi-perusahaan.list')" @click="openDetail(item)" title="Detail" class="p-1.5 rounded-lg text-gray-400 hover:text-sky-600 hover:bg-sky-50 dark:hover:text-sky-400 dark:hover:bg-indigo-900/30"><i class="fas fa-eye"></i></button>
                <button v-if="can('konfigurasi-perusahaan.edit') && !item.dihapus" @click="openEdit(item)" title="Edit" class="p-1.5 rounded-lg text-gray-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:text-amber-400 dark:hover:bg-amber-900/30"><i class="fas fa-edit"></i></button>
                <button v-if="can('konfigurasi-perusahaan.delete') && !item.dihapus" @click="openDelete(item)" title="Hapus" class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:text-red-400 dark:hover:bg-red-900/30"><i class="fas fa-trash-alt"></i></button>
                <span v-if="item.dihapus" class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400" title="Terhapus"><i class="fas fa-trash-alt text-[10px]"></i><span>Terhapus</span></span>
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

    <Teleport to="body"><Transition name="modal"><div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showDetailModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col"><div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-eye mr-2 text-sky-500"></i>Detail Konfigurasi</h3><button @click="showDetailModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button></div><div class="overflow-y-auto flex-1 px-6 py-5 space-y-5 modal-scroll" v-if="selectedItem">
        <div class="grid grid-cols-2 gap-3">
          <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Key</label><p class="font-mono text-sm font-medium text-gray-900 dark:text-white break-all">{{ selectedItem.key }}</p></div>
          <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Tipe</label><p><span :class="['inline-flex items-center px-2 py-0.5 rounded text-xs font-medium', typeBadgeClass(selectedItem.type)]"><i :class="['fas mr-1', typeIcon(selectedItem.type)]"></i>{{ typeLabel(selectedItem.type) }}</span></p></div>
        </div>
        <div>
          <div v-if="selectedItem.type === 'kredensial'" class="flex items-center justify-between mb-1.5"><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Value</label><button type="button" @click="detailValueVisible = !detailValueVisible" :title="detailValueVisible ? 'Sembunyikan value' : 'Tampilkan value'" class="p-1.5 rounded text-gray-400 hover:text-sky-600 dark:hover:text-sky-400"><i :class="['fas text-xs', detailValueVisible ? 'fa-eye-slash' : 'fa-eye']"></i></button></div>
          <label v-else class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 block">Value</label>
          <pre v-if="selectedItem.type !== 'kredensial'" class="font-mono text-sm break-all bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white rounded-lg p-3 whitespace-pre-wrap">{{ selectedItem.value }}</pre>
          <pre v-else-if="detailValueVisible" class="font-mono text-sm break-all bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white rounded-lg p-3 whitespace-pre-wrap">{{ selectedItem.value }}</pre>
          <div v-else class="font-mono text-sm bg-gray-50 dark:bg-gray-900 text-gray-400 dark:text-gray-500 rounded-lg p-3 select-none">••••••••••••••••</div>
        </div>
        <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Description</label><p class="mt-1 text-sm text-gray-700 dark:text-gray-300 break-words">{{ selectedItem.description || '-' }}</p></div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Tgl Dibuat</label><p class="text-sm text-gray-900 dark:text-white">{{ selectedItem.created_at }}</p></div>
          <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Tgl Diperbarui</label><p class="text-sm text-gray-900 dark:text-white">{{ selectedItem.updated_at }}</p></div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Dibuat Oleh</label><p class="text-sm text-gray-700 dark:text-gray-300">{{ selectedItem.created_by || '-' }}</p></div>
          <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Diperbarui Oleh</label><p class="text-sm text-gray-700 dark:text-gray-300">{{ selectedItem.updated_by || '-' }}</p></div>
        </div>
      </div></div></div></Transition></Teleport>

    <Teleport to="body"><Transition name="modal"><div v-if="showCreateModal || showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showCreateModal = showEditModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col"><div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i :class="['fas mr-2', showCreateModal ? 'fa-plus text-emerald-500' : 'fa-edit text-amber-500']"></i>{{ showCreateModal ? 'Tambah Konfigurasi' : 'Edit Konfigurasi' }}</h3><button @click="showCreateModal = showEditModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button></div><form class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll" @submit.prevent="showCreateModal ? submitCreate() : submitEdit()"><FormErrorSummary :errors="(showCreateModal ? createForm : editForm).errors" />
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Key <span class="text-red-500">*</span></label><input v-model="(showCreateModal ? createForm : editForm).key" type="text" placeholder="Contoh: company.tagline" :class="['w-full px-3 py-2.5 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm font-mono outline-none', (showCreateModal ? createForm : editForm).errors.key ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500']" /><p v-if="(showCreateModal ? createForm : editForm).errors.key" class="text-red-500 text-xs mt-1">{{ (showCreateModal ? createForm : editForm).errors.key }}</p></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Tipe <span class="text-red-500">*</span></label><select v-model="(showCreateModal ? createForm : editForm).type" :class="['w-full px-3 py-2.5 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm', (showCreateModal ? createForm : editForm).errors.type ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500']"><option value="text">Teks</option><option value="file">File</option><option value="number">Angka</option><option value="boolean">Boolean (true/false)</option><option value="kredensial">Kredensial (disembunyikan default)</option></select><p v-if="(showCreateModal ? createForm : editForm).errors.type" class="text-red-500 text-xs mt-1">{{ (showCreateModal ? createForm : editForm).errors.type }}</p></div>
        <div>
          <div class="flex items-center justify-between mb-1.5">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Value <span class="text-red-500">*</span></label>
            <button v-if="(showCreateModal ? createForm : editForm).type === 'kredensial'" type="button" @click="kredensialReveal = !kredensialReveal" :title="kredensialReveal ? 'Sembunyikan value' : 'Tampilkan value'" class="p-1.5 rounded text-gray-400 hover:text-sky-600 dark:hover:text-sky-400"><i :class="['fas text-xs', kredensialReveal ? 'fa-eye-slash' : 'fa-eye']"></i></button>
          </div>
          <textarea v-if="(showCreateModal ? createForm : editForm).type === 'text' || (showCreateModal ? createForm : editForm).type === 'file'" v-model="(showCreateModal ? createForm : editForm).value" rows="4" :class="['w-full px-3 py-2.5 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm font-mono outline-none resize-none', (showCreateModal ? createForm : editForm).errors.value ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500']" :placeholder="(showCreateModal ? createForm : editForm).type === 'file' ? 'Path file atau URL' : 'Teks bebas'"></textarea>
          <input v-else-if="(showCreateModal ? createForm : editForm).type === 'number'" :value="(showCreateModal ? createForm : editForm).value" @input="(showCreateModal ? createForm : editForm).value = String($event.target.value)" type="number" step="any" placeholder="0" :class="['w-full px-3 py-2.5 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm font-mono outline-none', (showCreateModal ? createForm : editForm).errors.value ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500']" />
          <select v-else-if="(showCreateModal ? createForm : editForm).type === 'boolean'" :value="(showCreateModal ? createForm : editForm).value" @change="(showCreateModal ? createForm : editForm).value = String($event.target.value)" :class="['w-full px-3 py-2.5 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm', (showCreateModal ? createForm : editForm).errors.value ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500']"><option value="true">true (Ya)</option><option value="false">false (Tidak)</option></select>
          <input v-else-if="(showCreateModal ? createForm : editForm).type === 'kredensial'" v-model="(showCreateModal ? createForm : editForm).value" :type="kredensialReveal ? 'text' : 'password'" autocomplete="off" :placeholder="kredensialReveal ? 'API key / token / secret (visible)' : 'API key / token / secret (masked)'" :class="['w-full px-3 py-2.5 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm font-mono outline-none', (showCreateModal ? createForm : editForm).errors.value ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500']" />
          <p v-if="(showCreateModal ? createForm : editForm).errors.value" class="text-red-500 text-xs mt-1">{{ (showCreateModal ? createForm : editForm).errors.value }}</p>
        </div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Description</label><textarea v-model="(showCreateModal ? createForm : editForm).description" rows="2" placeholder="Penjelasan singkat (opsional)" :class="['w-full px-3 py-2.5 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm outline-none resize-none', (showCreateModal ? createForm : editForm).errors.description ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500']"></textarea><p v-if="(showCreateModal ? createForm : editForm).errors.description" class="text-red-500 text-xs mt-1">{{ (showCreateModal ? createForm : editForm).errors.description }}</p></div>
        <div class="shrink-0 flex justify-end gap-2 pt-2 border-t border-gray-200 dark:border-gray-700 -mx-6 px-6 -mb-5 pb-4"><button type="button" @click="showCreateModal = showEditModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button><button type="submit" class="px-6 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm"><i class="fas fa-save mr-1.5"></i>{{ showCreateModal ? 'Simpan' : 'Update' }}</button></div>
      </form></div></div></Transition></Teleport>

    <Teleport to="body"><Transition name="modal"><div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showDeleteModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-sm"><div class="px-6 py-5 text-center"><div class="w-14 h-14 mx-auto rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-4"><i class="fas fa-exclamation-triangle text-red-500 text-xl"></i></div><h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Hapus Konfigurasi?</h3><p class="text-sm text-gray-500 dark:text-gray-400">Anda akan menghapus key <strong class="text-gray-900 dark:text-white">{{ selectedItem?.key }}</strong>. Data dapat dipulihkan dari filter Terhapus.</p></div><div class="flex justify-center gap-3 px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700"><button @click="showDeleteModal = false" class="px-5 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button><button @click="confirmDelete" class="px-5 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors"><i class="fas fa-trash-alt mr-1.5"></i> Hapus</button></div></div></div></Transition></Teleport>

    <Teleport to="body"><Transition name="modal"><div v-if="showImportModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showImportModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><form @submit.prevent="submitImport" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] flex flex-col"><div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-file-import text-emerald-500 mr-2"></i>Import Konfigurasi</h3><button type="button" @click="showImportModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button></div><div class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll"><FormErrorSummary :errors="importForm.errors" />
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3 text-xs text-blue-700 dark:text-blue-400"><i class="fas fa-info-circle mr-1"></i> File harus format .xlsx atau .csv. Kolom: Key, Type, Value, Description. Type yang valid: text, file, number, boolean.</div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">File Excel <span class="text-red-500">*</span></label><input @change="importForm.file = $event.target.files[0]" type="file" accept=".xlsx,.csv" :class="['w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-sky-50 file:text-sky-700 dark:file:bg-sky-900/30 dark:file:text-sky-400 hover:file:bg-sky-100 dark:hover:file:bg-sky-900/50', importForm.errors.file ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500']" /><p v-if="importForm.errors.file" class="text-red-500 text-xs mt-1">{{ importForm.errors.file }}</p></div>
        <button type="button" @click="downloadTemplate" class="text-xs text-sky-600 dark:text-sky-400 hover:underline"><i class="fas fa-download mr-1"></i>Download template</button>
      </div><div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700"><button type="button" @click="showImportModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button><button type="submit" :disabled="importing || !importForm.file" class="px-6 py-2.5 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors shadow-sm disabled:opacity-50"><i :class="['fas', importing ? 'fa-spinner fa-spin' : 'fa-upload', 'mr-1.5']"></i>{{ importing ? 'Mengimport...' : 'Import' }}</button></div></form></div></Transition></Teleport>
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
