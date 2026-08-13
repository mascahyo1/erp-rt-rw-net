<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import OperatorPerusahaanLayout from '@/Layouts/OperatorPerusahaanLayout.vue';
import SearchableSelectAjax from '@/Components/SearchableSelectAjax.vue';
import LocationPicker from '@/Components/LocationPicker.vue';
import { useToast } from '@/Composables/useToast';
import ToastContainer from '@/Components/ToastContainer.vue';
import FormErrorSummary from '@/Components/FormErrorSummary.vue';
import { errorSummary } from '@/Composables/useFormErrorToast';

defineOptions({ layout: OperatorPerusahaanLayout });

const props = defineProps({ langganans: Object, filters: Object });
const toast = useToast();
const page = usePage();
const can = (perm) => page.props.permissions?.includes(perm);

const searchInput = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const paketFilter = ref(props.filters?.paket || '');
const terhapusFilter = ref(props.filters?.terhapus || 'tidak');
const sortField = ref(props.filters?.sort_field || '');
const sortDir = ref(props.filters?.sort_dir || 'asc');
const perPage = ref(props.filters?.per_page ? Number(props.filters.per_page) : 10);
const perPageOptions = [5, 10, 25, 50, 100];
const selectedIds = ref([]); const selectAll = ref(false);
const selectedItem = ref(null);
const showCreateModal = ref(false); const showDetailModal = ref(false);
const showEditModal = ref(false); const showDeleteModal = ref(false);
const showImportModal = ref(false);
const importFile = ref(null);
const importErrors = ref({});
const createLocationPicker = ref(null);
const editLocationPicker = ref(null);
const detailLocationPicker = ref(null);

function buildQuery(o = {}) {
  const p = { ...o };
  if (p.search === undefined) p.search = searchInput.value || undefined;
  if (p.status === undefined) p.status = statusFilter.value || undefined;
  if (p.paket === undefined) p.paket = paketFilter.value || undefined;
  if (p.terhapus === undefined) p.terhapus = terhapusFilter.value || undefined;
  if (p.sort_field === undefined) p.sort_field = sortField.value || undefined;
  if (p.sort_dir === undefined) p.sort_dir = sortDir.value || undefined;
  if (p.per_page === undefined) p.per_page = perPage.value;
  Object.keys(p).forEach(k => { if (p[k] === undefined) delete p[k]; });
  return p;
}
function fetchData(o = {}) {
  router.get('/operator-perusahaan/langganan-customer', buildQuery(o), { preserveState: true, preserveScroll: true, replace: true });
}
function applySearch() { fetchData({ search: searchInput.value || undefined, status: statusFilter.value || undefined, terhapus: terhapusFilter.value, page: 1 }); }
function clearSearch() { searchInput.value = ''; fetchData({ search: undefined, status: statusFilter.value || undefined, terhapus: terhapusFilter.value, page: 1 }); }
function applyFilters() { fetchData({ page: 1 }); }
function resetFilters() { searchInput.value = ''; statusFilter.value = ''; paketFilter.value = ''; terhapusFilter.value = 'tidak'; fetchData({ search: undefined, status: undefined, paket: undefined, terhapus: 'tidak', page: 1 }); }
function sort(f) {
  if (sortField.value === f) { if (sortDir.value === 'asc') sortDir.value = 'desc'; else { sortField.value = ''; sortDir.value = 'asc'; } }
  else { sortField.value = f; sortDir.value = 'asc'; }
  fetchData({ sort_field: sortField.value || undefined, sort_dir: sortDir.value || undefined });
}
function sortIcon(f) { if (sortField.value !== f) return 'fa-sort'; return sortDir.value === 'asc' ? 'fa-sort-up' : 'fa-sort-down'; }
function toggleSelectAll() { selectedIds.value = selectAll.value ? props.langganans.data.map(l => l.id) : []; }
function toggleSelect(id) { const i = selectedIds.value.indexOf(id); i === -1 ? selectedIds.value.push(id) : selectedIds.value.splice(i, 1); }
function goToPage(p) { fetchData({ page: p }); }
function changePerPage(n) { perPage.value = n; fetchData({ per_page: n, page: 1 }); }
function statusBadgeClass(s) {
  if (s === 'active') return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
  if (s === 'inactive') return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
  if (s === 'suspended') return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
  if (s === 'terminated') return 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
  return 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400';
}

const createForm = useForm({ customer_id: '', internet_package_id: '', account_number: '', router_sn: '', customer_address: '', customer_address_long: '', customer_address_lat: '', internet_status: 'active', company_notes: '' });
const editForm = useForm({ customer_id: '', internet_package_id: '', account_number: '', router_sn: '', customer_address: '', customer_address_long: '', customer_address_lat: '', internet_status: '', company_notes: '' });

function openCreate() { createForm.reset(); createForm.clearErrors(); showCreateModal.value = true; nextTick(() => createLocationPicker.value?.invalidateSize()); }
function submitCreate() { createForm.post('/operator-perusahaan/langganan-customer', { preserveState: true, preserveScroll: true, onSuccess: () => { showCreateModal.value = false; fetchData(); toast.success('Langganan berhasil ditambahkan.'); }, onError: () => toast.error('Validasi gagal: ' + errorSummary(createForm.errors), 6000) }); }
function openEdit(item) { editForm.customer_id = item.customer_id; editForm.internet_package_id = item.internet_package_id; editForm.account_number = item.account_number || ''; editForm.router_sn = item.router_sn || ''; editForm.customer_address = item.customer_address || ''; editForm.customer_address_long = item.customer_address_long || ''; editForm.customer_address_lat = item.customer_address_lat || ''; editForm.internet_status = item.internet_status; editForm.company_notes = item.company_notes; editForm.clearErrors(); selectedItem.value = item; showEditModal.value = true; nextTick(() => editLocationPicker.value?.invalidateSize()); }
function openDetail(item) { selectedItem.value = item; showDetailModal.value = true; nextTick(() => detailLocationPicker.value?.invalidateSize()); }
function submitEdit() { editForm.put('/operator-perusahaan/langganan-customer/' + selectedItem.value.id, { preserveState: true, preserveScroll: true, onSuccess: () => { showEditModal.value = false; fetchData(); toast.success('Langganan berhasil diperbarui.'); }, onError: () => toast.error('Validasi gagal: ' + errorSummary(editForm.errors), 6000) }); }
function openDelete(item) { selectedItem.value = item; showDeleteModal.value = true; }
function confirmDelete() { router.delete('/operator-perusahaan/langganan-customer/' + selectedItem.value.id, { onSuccess: () => { showDeleteModal.value = false; fetchData(); toast.success('Langganan berhasil dihapus.'); } }); }
function confirmRestore(id) { router.patch('/operator-perusahaan/langganan-customer/' + id + '/restore', { onSuccess: () => { fetchData(); toast.success('Langganan berhasil dipulihkan.'); } }); }
function bulkDelete() { router.post('/operator-perusahaan/langganan-customer/bulk-delete', { ids: selectedIds.value }, { onSuccess: () => { selectedIds.value = []; selectAll.value = false; fetchData(); toast.success('Langganan berhasil dihapus.'); } }); }
function bulkRestore() { router.post('/operator-perusahaan/langganan-customer/bulk-restore', { ids: selectedIds.value }, { onSuccess: () => { selectedIds.value = []; selectAll.value = false; fetchData(); toast.success('Langganan berhasil dipulihkan.'); } }); }
function bulkSetStatus(s) { router.post('/operator-perusahaan/langganan-customer/bulk-status', { ids: selectedIds.value, status: s }, { onSuccess: () => { selectedIds.value = []; selectAll.value = false; fetchData(); toast.success('Status berhasil diubah.'); } }); }
function buildFilterParams() {
    const params = new URLSearchParams();
    if (searchInput.value) params.set('search', searchInput.value);
    if (statusFilter.value) params.set('status', statusFilter.value);
    if (terhapusFilter.value !== 'tidak') params.set('terhapus', terhapusFilter.value);
    if (paketFilter.value) params.set('paket', paketFilter.value);
    return params.toString();
}
function exportAll() {
    const params = buildFilterParams();
    window.location.href = '/operator-perusahaan/langganan-customer/export' + (params ? '?' + params : '');
}
function exportSelected() {
    if (selectedIds.value.length > 0) {
        window.location.href = '/operator-perusahaan/langganan-customer/export?ids=' + selectedIds.value.join(',');
    } else {
        const params = buildFilterParams();
        window.location.href = '/operator-perusahaan/langganan-customer/export' + (params ? '?' + params : '');
    }
}
function downloadTemplate() { window.location.href = '/operator-perusahaan/langganan-customer/template'; }
function openImportModal() { importFile.value = null; importErrors.value = {}; showImportModal.value = true; }
function submitImport() { if (!importFile.value) return; const formData = new FormData(); formData.append('file', importFile.value); toast.add({ message: 'Mengimport data...', type: 'info' }); router.post('/operator-perusahaan/langganan-customer/import', formData, { onSuccess: () => { showImportModal.value = false; importFile.value = null; fetchData(); toast.success('Import berhasil.'); }, onError: (errors) => { importErrors.value = errors || {}; toast.error('Import gagal: ' + errorSummary(errors), 6000); } }); }

const items = computed(() => props.langganans?.data || []);
const pagination = computed(() => ({ current: props.langganans?.current_page || 1, last: props.langganans?.last_page || 1, total: props.langganans?.total || 0 }));
const hasFilter = computed(() => searchInput.value || statusFilter.value || terhapusFilter.value !== 'tidak' || paketFilter.value);
onMounted(() => { });
</script>
<template>
  <div>
    <Head title="Langganan Customer | Perusahaan" />
    <ToastContainer />
    <div class="space-y-6">
      <nav class="flex items-center gap-1.5 text-sm"><Link href="/operator-perusahaan/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-sky-600 dark:hover:text-sky-400 transition-colors"><i class="fas fa-home"></i></Link><i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i><span class="text-gray-900 dark:text-white font-medium">Langganan Customer</span></nav>
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3"><div><h2 class="text-2xl font-bold text-gray-900 dark:text-white">Langganan Customer</h2><p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Kelola data pemasangan paket internet customer.</p></div><div class="flex flex-wrap items-center gap-2">
          <button v-if="can('langganan.import') && terhapusFilter !== 'ya'" @click="openImportModal" class="inline-flex items-center px-4 py-2.5 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors shadow-sm"><i class="fas fa-file-import mr-1.5"></i> Import</button>
          <button v-if="can('langganan.export')" @click="exportAll" class="inline-flex items-center px-4 py-2.5 bg-violet-600 text-white text-sm font-medium rounded-lg hover:bg-violet-700 transition-colors shadow-sm"><i class="fas fa-file-export mr-1.5"></i> Export</button>
          <button v-if="can('langganan.export')" @click="exportSelected" class="inline-flex items-center px-4 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm"><i class="fas fa-check-square mr-1.5"></i> Export Selected{{ selectedIds.length > 0 ? ' (' + selectedIds.length + ')' : '' }}</button>
          <button v-if="can('langganan.create') && terhapusFilter !== 'ya'" @click="openCreate" class="inline-flex items-center px-4 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm"><i class="fas fa-plus mr-1.5"></i> Tambah Langganan</button>
        </div></div>
      <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between"><div class="relative w-full sm:w-72"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-search text-gray-400 text-sm"></i></div><input v-model="searchInput" type="text" placeholder="Cari..." class="w-full pl-10 pr-16 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm focus:ring-2 focus:ring-sky-500 outline-none" @keydown.enter="applySearch" /><div class="absolute inset-y-0 right-0 flex items-center gap-1 pr-1.5"><button v-if="searchInput" @click="clearSearch" class="p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-white" title="Clear"><i class="fas fa-times text-xs"></i></button><button @click="applySearch" class="px-2 py-1 rounded bg-sky-600 text-white hover:bg-sky-700" title="Cari"><i class="fas fa-search text-xs"></i></button></div></div><div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400"><span>{{ props.langganans?.total || 0 }} data</span><button v-if="statusFilter || terhapusFilter !== 'tidak' || searchInput || paketFilter" @click="resetFilters" class="text-xs text-red-500 hover:text-red-700 dark:hover:text-red-400 underline">Reset filter</button></div></div>

      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm"><div class="flex flex-wrap items-end gap-4"><div class="min-w-[160px]"><label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Status</label><select v-model="statusFilter" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors"><option value="">Semua</option><option value="active">Aktif</option><option value="inactive">Nonaktif</option><option value="suspended">Suspend</option><option value="terminated">Terminasi</option></select></div><div class="min-w-[220px]"><label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Paket</label><SearchableSelectAjax v-model="paketFilter" url="/operator-perusahaan/api/search/packages" placeholder="Semua Paket" :page-size="25" label-key="label" value-key="value" /></div><div class="min-w-[160px]"><label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Terhapus</label><select v-model="terhapusFilter" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors"><option value="tidak">Tidak</option><option value="ya">Ya</option></select></div><button @click="applyFilters" class="px-4 py-2 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm"><i class="fas fa-filter mr-1.5"></i>Filter</button></div></div>

      <div v-if="selectedIds.length > 0" class="flex items-center justify-between px-4 py-3 bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-800 rounded-xl shadow-sm"><span class="text-sm font-medium text-sky-700 dark:text-sky-300"><i class="fas fa-check-circle mr-1.5"></i> {{ selectedIds.length }} data dipilih</span><div class="flex items-center gap-2"><button v-if="can('langganan.restore') && terhapusFilter === 'ya'" @click="bulkRestore()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-emerald-600 text-white hover:bg-emerald-700"><i class="fas fa-undo-alt mr-1"></i> Pulihkan</button><button v-if="can('langganan.edit') && terhapusFilter !== 'ya'" @click="bulkSetStatus('active')" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-emerald-600 text-white hover:bg-emerald-700"><i class="fas fa-check mr-1"></i> Aktifkan</button><button v-if="can('langganan.edit') && terhapusFilter !== 'ya'" @click="bulkSetStatus('inactive')" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-amber-600 text-white hover:bg-amber-700"><i class="fas fa-ban mr-1"></i> Nonaktifkan</button><button v-if="can('langganan.delete')" @click="bulkDelete()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-red-600 text-white hover:bg-red-700"><i class="fas fa-trash-alt mr-1"></i> Hapus</button></div></div>
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto"><table class="w-full text-sm min-w-[1000px]"><thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700"><tr><th class="px-4 py-3 w-10"><input v-model="selectAll" type="checkbox" @change="toggleSelectAll" class="rounded border-gray-300 text-sky-600" /></th><th @click="sort('customer_name')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none"><span class="inline-flex items-center gap-1">Nama Customer <i :class="['fas', sortIcon('customer_name'), 'text-[10px]', sortField === 'customer_name' ? 'text-sky-500' : 'text-gray-400']"></i></span></th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Email</th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">No. HP</th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">No. Akun</th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Nama Paket</th><th @click="sort('internet_status')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none"><span class="inline-flex items-center gap-1">Status <i :class="['fas', sortIcon('internet_status'), 'text-[10px]', sortField === 'internet_status' ? 'text-sky-500' : 'text-gray-400']"></i></span></th><th class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-400 w-24">Aksi</th></tr></thead>
        <tbody>
          <tr v-if="items.length === 0"><td colspan="8" class="px-4 py-16 text-center text-gray-400 dark:text-gray-500"><i class="fas fa-inbox text-4xl mb-3 block"></i><span class="text-sm">Tidak ada data langganan</span></td></tr>
          <tr v-for="item in items" :key="item.id" class="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors cursor-pointer" :class="{'opacity-60': item.dihapus}" @click="(e) => { if (!e.target.closest('button') && !e.target.closest('input')) toggleSelect(item.id); }">
            <td class="px-4 py-3" @click.stop><input type="checkbox" :checked="selectedIds.includes(item.id)" @change="toggleSelect(item.id)" class="rounded border-gray-300 text-sky-600" /></td>
            <td class="px-4 py-3"><div class="flex items-center gap-3"><div class="w-8 h-8 rounded-full bg-sky-100 dark:bg-sky-900/40 flex items-center justify-center text-sky-600 dark:text-sky-400 font-semibold text-xs">{{ (item.customer_name || '?')[0] }}</div><span class="font-medium text-gray-900 dark:text-white">{{ item.customer_name }}</span></div></td>
            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ item.customer_email || '-' }}</td>
            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ item.customer_phone || '-' }}</td>
            <td class="px-4 py-3 font-mono text-xs text-gray-600 dark:text-gray-400">{{ item.account_number || '-' }}</td>
            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ item.internet_package_name || '-' }}</td>
            <td class="px-4 py-3"><span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium', statusBadgeClass(item.internet_status)]">{{ item.internet_status }}</span></td>
            <td class="px-4 py-3" @click.stop>
              <div class="flex items-center justify-center gap-1">
                <button v-if="can('langganan.detail') && !item.dihapus" @click="openDetail(item)" title="Detail" class="p-1.5 rounded-lg text-gray-400 hover:text-sky-600 hover:bg-sky-50 dark:hover:text-sky-400 dark:hover:bg-indigo-900/30"><i class="fas fa-eye"></i></button>
                <button v-if="can('langganan.edit') && !item.dihapus" @click="openEdit(item)" title="Edit" class="p-1.5 rounded-lg text-gray-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:text-amber-400 dark:hover:bg-amber-900/30"><i class="fas fa-edit"></i></button>
                <button v-if="can('langganan.delete') && !item.dihapus" @click="openDelete(item)" title="Hapus" class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:text-red-400 dark:hover:bg-red-900/30"><i class="fas fa-trash-alt"></i></button>
                <button v-if="can('langganan.restore') && item.dihapus" @click="confirmRestore(item.id)" title="Pulihkan" class="p-1.5 rounded-lg text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:text-emerald-400 dark:hover:bg-emerald-900/30"><i class="fas fa-undo"></i></button>
              </div>
            </td>
          </tr>
        </tbody></table></div>
        <div class="flex flex-col sm:flex-row items-center justify-between px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
          <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 mb-3 sm:mb-0"><span>Tampilkan</span><select v-model="perPage" @change="changePerPage(perPage)" class="px-2 py-1 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm"><option v-for="n in perPageOptions" :key="n" :value="n">{{ n }}</option></select><span>dari {{ pagination.total }} data</span></div>
          <div class="flex items-center gap-1"><button @click="goToPage(1)" :disabled="pagination.current <= 1" class="px-2 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-30"><i class="fas fa-angle-double-left"></i></button><button @click="goToPage(pagination.current-1)" :disabled="pagination.current <= 1" class="px-2 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-30"><i class="fas fa-angle-left"></i></button><button v-for="p in []" :key="p" class="px-3 py-1.5 rounded-lg text-sm font-medium" :class="p === pagination.current ? 'bg-sky-600 text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'">{{ p }}</button><span class="px-2 text-sm text-gray-500">{{ pagination.current }} / {{ pagination.last }}</span><button @click="goToPage(pagination.current+1)" :disabled="pagination.current >= pagination.last" class="px-2 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-30"><i class="fas fa-angle-right"></i></button><button @click="goToPage(pagination.last)" :disabled="pagination.current >= pagination.last" class="px-2 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-30"><i class="fas fa-angle-double-right"></i></button></div>
        </div>
      </div>
    </div>
    <Teleport to="body"><Transition name="modal"><div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showDetailModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col"><div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white">Detail Langganan</h3><button @click="showDetailModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700"><i class="fas fa-times"></i></button></div><div class="overflow-y-auto flex-1 px-6 py-5 space-y-3 modal-scroll" v-if="selectedItem"><div class="grid grid-cols-2 gap-3"><div><label class="text-xs text-gray-500 dark:text-gray-400">Customer</label><p class="font-medium text-gray-900 dark:text-white">{{ selectedItem.customer_name }}</p></div><div><label class="text-xs text-gray-500 dark:text-gray-400">Email</label><p class="text-sm text-gray-900 dark:text-white">{{ selectedItem.customer_email || '-' }}</p></div><div><label class="text-xs text-gray-500 dark:text-gray-400">No. HP</label><p class="text-sm text-gray-900 dark:text-white">{{ selectedItem.customer_phone || '-' }}</p></div><div><label class="text-xs text-gray-500 dark:text-gray-400">Paket</label><p class="font-medium text-gray-900 dark:text-white">{{ selectedItem.internet_package_name }}</p></div><div><label class="text-xs text-gray-500 dark:text-gray-400">No. Akun</label><p class="font-mono font-medium text-gray-900 dark:text-white">{{ selectedItem.account_number || '-' }}</p></div><div><label class="text-xs text-gray-500 dark:text-gray-400">Router SN</label><p class="font-mono text-xs text-gray-900 dark:text-white">{{ selectedItem.router_sn || '-' }}</p></div><div class="col-span-2"><label class="text-xs text-gray-500 dark:text-gray-400">Alamat</label><p class="text-sm text-gray-900 dark:text-white">{{ selectedItem.customer_address || '-' }}</p></div><div><label class="text-xs text-gray-500 dark:text-gray-400">Status</label><p><span :class="['px-2 py-0.5 rounded text-xs font-medium', statusBadgeClass(selectedItem.internet_status)]">{{ selectedItem.internet_status }}</span></p></div><div><label class="text-xs text-gray-500 dark:text-gray-400">Tagihan</label><p class="font-mono text-gray-900 dark:text-white">Rp {{ Number(selectedItem.billing_amount || 0).toLocaleString('id') }}</p></div><div class="col-span-2"><label class="text-xs text-gray-500 dark:text-gray-400">Tgl Daftar</label><p class="text-gray-900 dark:text-white">{{ selectedItem.created_at }}</p></div></div><div v-if="selectedItem.customer_address_lat && selectedItem.customer_address_long" class="pt-2"><label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5"><i class="fas fa-map-marker-alt mr-1"></i>Lokasi</label><LocationPicker ref="detailLocationPicker" readonly :lat="selectedItem.customer_address_lat" :lng="selectedItem.customer_address_long" height="220px" /></div><div v-else class="pt-2 text-center text-xs text-gray-400 italic"><i class="fas fa-info-circle mr-1"></i>Lokasi belum di-set</div></div><div class="shrink-0 flex justify-end gap-2 px-6 py-3 border-t border-gray-200 dark:border-gray-700"><button @click="showDetailModal = false" class="px-5 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Tutup</button></div></div></div></Transition></Teleport>
    <!-- CREATE MODAL -->
    <Teleport to="body"><Transition name="modal">
      <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showCreateModal = false">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
        <form @submit.prevent="submitCreate" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col">
          <div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-plus text-emerald-500 mr-2"></i>Tambah Langganan</h3><button type="button" @click="showCreateModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button></div>
          <div class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll"><FormErrorSummary :errors="createForm.errors" />
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nama Pelanggan <span class="text-red-500">*</span></label><SearchableSelectAjax v-model="createForm.customer_id" url="/operator-perusahaan/api/search/customers" placeholder="— Pilih Pelanggan —"  :error="!!createForm.errors.customer_id" /><p v-if="createForm.errors.customer_id" class="text-red-500 text-xs mt-1">{{ createForm.errors.customer_id }}</p></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nama Paket <span class="text-red-500">*</span></label><SearchableSelectAjax v-model="createForm.internet_package_id" url="/operator-perusahaan/api/search/packages" placeholder="— Pilih Paket —"  :error="!!createForm.errors.internet_package_id" /><p v-if="createForm.errors.internet_package_id" class="text-red-500 text-xs mt-1">{{ createForm.errors.internet_package_id }}</p></div>
            <div class="grid grid-cols-2 gap-3"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">No. Akun <span class="text-red-500">*</span></label><input v-model="createForm.account_number" type="text" placeholder="ACC-001" :class="['w-full px-3 py-2.5 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm outline-none', createForm.errors.account_number ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500']" /><p v-if="createForm.errors.account_number" class="text-red-500 text-xs mt-1">{{ createForm.errors.account_number }}</p></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Router SN</label><input v-model="createForm.router_sn" type="text" placeholder="SN-XXXX" :class="['w-full px-3 py-2.5 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm outline-none', createForm.errors.router_sn ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500']" /><p v-if="createForm.errors.router_sn" class="text-red-500 text-xs mt-1">{{ createForm.errors.router_sn }}</p></div></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Alamat</label><input v-model="createForm.customer_address" type="text" placeholder="Jl. Raya..." :class="['w-full px-3 py-2.5 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm outline-none', createForm.errors.customer_address ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500']" /><p v-if="createForm.errors.customer_address" class="text-red-500 text-xs mt-1">{{ createForm.errors.customer_address }}</p></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5"><i class="fas fa-map-marker-alt mr-1 text-sky-600 dark:text-sky-400"></i>Titik Lokasi</label><LocationPicker ref="createLocationPicker" v-model:lat="createForm.customer_address_lat" v-model:lng="createForm.customer_address_long" /><p v-if="createForm.errors.customer_address_lat" class="text-red-500 text-xs mt-1">{{ createForm.errors.customer_address_lat }}</p></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Status</label><select v-model="createForm.internet_status" :class="['w-full px-3 py-2.5 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm outline-none', createForm.errors.internet_status ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500']"><option value="active">Aktif</option><option value="inactive">Nonaktif</option><option value="suspended">Suspend</option><option value="terminated">Terminasi</option></select><p v-if="createForm.errors.internet_status" class="text-red-500 text-xs mt-1">{{ createForm.errors.internet_status }}</p></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Catatan Perusahaan</label><textarea v-model="createForm.company_notes" rows="2" placeholder="Catatan internal..." :class="['w-full px-3 py-2.5 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm outline-none resize-none', createForm.errors.company_notes ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500']"></textarea><p v-if="createForm.errors.company_notes" class="text-red-500 text-xs mt-1">{{ createForm.errors.company_notes }}</p></div>
          </div>
          <div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700"><button type="button" @click="showCreateModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button><button type="submit" :disabled="createForm.processing" class="px-6 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm disabled:opacity-50"><i class="fas fa-save mr-1.5"></i>Simpan</button></div>
        </form>
      </div>
    </Transition></Teleport>

    <!-- EDIT MODAL -->
    <Teleport to="body"><Transition name="modal">
      <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showEditModal = false">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
        <form @submit.prevent="submitEdit" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col">
          <div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-edit text-sky-500 mr-2"></i>Edit Langganan</h3><button type="button" @click="showEditModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button></div>
          <div class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll"><FormErrorSummary :errors="editForm.errors" />
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nama Pelanggan <span class="text-red-500">*</span></label><SearchableSelectAjax v-model="editForm.customer_id" url="/operator-perusahaan/api/search/customers" placeholder="— Pilih Pelanggan —" :selected-label="selectedItem?.customer_name"  :error="!!editForm.errors.customer_id" /><p v-if="editForm.errors.customer_id" class="text-red-500 text-xs mt-1">{{ editForm.errors.customer_id }}</p></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nama Paket <span class="text-red-500">*</span></label><SearchableSelectAjax v-model="editForm.internet_package_id" url="/operator-perusahaan/api/search/packages" placeholder="— Pilih Paket —" :selected-label="selectedItem?.internet_package_name"  :error="!!editForm.errors.internet_package_id" /><p v-if="editForm.errors.internet_package_id" class="text-red-500 text-xs mt-1">{{ editForm.errors.internet_package_id }}</p></div>
            <div class="grid grid-cols-2 gap-3"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">No. Akun <span class="text-red-500">*</span></label><input v-model="editForm.account_number" type="text" placeholder="ACC-001" :class="['w-full px-3 py-2.5 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm outline-none', editForm.errors.account_number ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500']" /><p v-if="editForm.errors.account_number" class="text-red-500 text-xs mt-1">{{ editForm.errors.account_number }}</p></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Router SN</label><input v-model="editForm.router_sn" type="text" placeholder="SN-XXXX" :class="['w-full px-3 py-2.5 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm outline-none', editForm.errors.router_sn ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500']" /><p v-if="editForm.errors.router_sn" class="text-red-500 text-xs mt-1">{{ editForm.errors.router_sn }}</p></div></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Alamat</label><input v-model="editForm.customer_address" type="text" placeholder="Jl. Raya..." :class="['w-full px-3 py-2.5 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm outline-none', editForm.errors.customer_address ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500']" /><p v-if="editForm.errors.customer_address" class="text-red-500 text-xs mt-1">{{ editForm.errors.customer_address }}</p></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5"><i class="fas fa-map-marker-alt mr-1 text-sky-600 dark:text-sky-400"></i>Titik Lokasi</label><LocationPicker ref="editLocationPicker" v-model:lat="editForm.customer_address_lat" v-model:lng="editForm.customer_address_long" /><p v-if="editForm.errors.customer_address_lat" class="text-red-500 text-xs mt-1">{{ editForm.errors.customer_address_lat }}</p></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Status</label><select v-model="editForm.internet_status" :class="['w-full px-3 py-2.5 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm outline-none', editForm.errors.internet_status ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500']"><option value="active">Aktif</option><option value="inactive">Nonaktif</option><option value="suspended">Suspend</option><option value="terminated">Terminasi</option></select><p v-if="editForm.errors.internet_status" class="text-red-500 text-xs mt-1">{{ editForm.errors.internet_status }}</p></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Catatan Perusahaan</label><textarea v-model="editForm.company_notes" rows="2" placeholder="Catatan internal..." :class="['w-full px-3 py-2.5 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm outline-none resize-none', editForm.errors.company_notes ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500']"></textarea><p v-if="editForm.errors.company_notes" class="text-red-500 text-xs mt-1">{{ editForm.errors.company_notes }}</p></div>
          </div>
          <div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700"><button type="button" @click="showEditModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button><button type="submit" :disabled="editForm.processing" class="px-6 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm disabled:opacity-50"><i class="fas fa-check mr-1.5"></i>Update</button></div>
        </form>
      </div>
    </Transition></Teleport>
    <Teleport to="body"><Transition name="modal"><div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showDeleteModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-sm"><div class="px-6 py-5 text-center"><div class="w-14 h-14 mx-auto rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-4"><i class="fas fa-exclamation-triangle text-red-500 text-xl"></i></div><h3 class="text-lg font-semibold mb-2">Hapus Langganan?</h3><p class="text-sm text-gray-500">Anda akan menghapus langganan <strong>{{ selectedItem?.customer_name }}</strong>.</p></div><div class="flex justify-center gap-3 px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700"><button @click="showDeleteModal = false" class="px-5 py-2.5 bg-gray-200 dark:bg-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">Batal</button><button @click="confirmDelete" class="px-5 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700"><i class="fas fa-trash-alt mr-1.5"></i> Hapus</button></div></div></div></Transition></Teleport>
    <!-- IMPORT MODAL -->
    <Teleport to="body"><Transition name="modal">
      <div v-if="showImportModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showImportModal = false">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md">
          <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-file-import text-emerald-500 mr-2"></i>Import Langganan</h3><button type="button" @click="showImportModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700"><i class="fas fa-times"></i></button></div>
          <div class="px-6 py-5 space-y-4"><FormErrorSummary :errors="importErrors" />
            <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-6 text-center">
              <input type="file" ref="importFile" @change="importFile = $event.target.files[0]" accept=".xlsx,.xls,.csv" class="hidden" />
              <button @click="$refs.importFile.click()" class="inline-flex items-center gap-2 px-4 py-2 bg-sky-100 dark:bg-sky-900/40 text-sky-600 dark:text-sky-400 text-sm font-medium rounded-lg hover:bg-sky-200 dark:hover:bg-sky-900/60 transition-colors"><i class="fas fa-upload"></i>{{ importFile ? importFile.name : 'Pilih File' }}</button>
              <p class="text-xs text-gray-400 mt-2">Format: .xlsx, .xls, .csv (max 2MB)</p><p v-if="importErrors.file" class="text-red-500 text-xs mt-1">{{ importErrors.file }}</p>
            </div>
            <button @click="downloadTemplate" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-sky-600 dark:hover:text-sky-400 transition-colors"><i class="fas fa-download"></i>Download Template</button>
          </div>
          <div class="flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700"><button type="button" @click="showImportModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">Batal</button><button type="button" @click="submitImport" :disabled="!importFile" class="px-6 py-2.5 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors shadow-sm disabled:opacity-50"><i class="fas fa-file-import mr-1.5"></i>Import</button></div>
        </div>
      </div>
    </Transition></Teleport>
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
