<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import OperatorPerusahaanLayout from '@/Layouts/OperatorPerusahaanLayout.vue';
import { useToast } from '@/Composables/useToast';
import ToastContainer from '@/Components/ToastContainer.vue';

defineOptions({ layout: OperatorPerusahaanLayout });
const props = defineProps({ items: Object, filters: Object });
const toast = useToast();

const page = usePage();
const can = (perm) => page.props.permissions?.includes(perm);

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
const showImportModal = ref(false);

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
  router.get('/operator-perusahaan/daftar-paket', buildQuery(o), { preserveState: true, preserveScroll: true, replace: true });
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
function toggleSelectAll() { selectedIds.value = selectAll.value ? props.items.data.map(l => l.id) : []; }
function toggleSelect(id) { const i = selectedIds.value.indexOf(id); i === -1 ? selectedIds.value.push(id) : selectedIds.value.splice(i, 1); }
function goToPage(p) { fetchData({ page: p }); }
function changePerPage(n) { perPage.value = n; fetchData({ per_page: n, page: 1 }); }
function statusBadge(s) { return s==='Aktif' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'; }
function formatBillingCycle(bc) { const m={daily:'Harian',weekly:'Mingguan',monthly:'Bulanan',yearly:'Tahunan'}; return m[bc]||bc; }
function fc(p) { return p ? 'Rp '+Number(p).toLocaleString('id') : '-'; }

const createForm = useForm({ code:'', name:'', price:'', speed_down_kbps:'', speed_up_kbps:'', quota_gb:'', billing_cycle:'monthly', is_unlimited:false, max_devices:'', fup_quota_down:'', fup_quota_up:'', fup_speed_down_kbps:'', fup_speed_up_kbps:'', description:'' });
const editForm = useForm({ code:'', name:'', price:'', speed_down_kbps:'', speed_up_kbps:'', quota_gb:'', billing_cycle:'', is_unlimited:false, max_devices:'', fup_quota_down:'', fup_quota_up:'', fup_speed_down_kbps:'', fup_speed_up_kbps:'', description:'' });

function openCreate() { createForm.reset(); createForm.clearErrors(); showCreateModal.value = true; }
function submitCreate() { createForm.post('/operator-perusahaan/daftar-paket', { preserveState: true, preserveScroll: true, onSuccess: () => { showCreateModal.value = false; fetchData(); toast.success('Paket berhasil ditambahkan.'); }, onError: () => toast.error('Validasi gagal.') }); }
function openEdit(item) { editForm.code = item.code; editForm.name = item.name; editForm.price = item.price; editForm.speed_down_kbps = item.speed_down_kbps; editForm.speed_up_kbps = item.speed_up_kbps; editForm.quota_gb = item.quota_gb; editForm.billing_cycle = item.billing_cycle; editForm.is_unlimited = item.is_unlimited; editForm.max_devices = item.max_devices; editForm.fup_quota_down = item.fup_quota_down; editForm.fup_quota_up = item.fup_quota_up; editForm.fup_speed_down_kbps = item.fup_speed_down_kbps; editForm.fup_speed_up_kbps = item.fup_speed_up_kbps; editForm.description = item.description; editForm.clearErrors(); selectedItem.value = item; showEditModal.value = true; }
function submitEdit() { editForm.put('/operator-perusahaan/daftar-paket/' + selectedItem.value.id, { preserveState: true, preserveScroll: true, onSuccess: () => { showEditModal.value = false; fetchData(); toast.success('Paket berhasil diperbarui.'); }, onError: () => toast.error('Validasi gagal.') }); }
function openDetail(item) { selectedItem.value = item; showDetailModal.value = true; }
function openDelete(item) { selectedItem.value = item; showDeleteModal.value = true; }
function confirmDelete() { router.delete('/operator-perusahaan/daftar-paket/' + selectedItem.value.id, { onSuccess: () => { showDeleteModal.value = false; fetchData(); toast.success('Paket berhasil dihapus.'); } }); }
function confirmRestore(item) { const id = typeof item === 'object' ? item.id : item; router.patch('/operator-perusahaan/daftar-paket/' + id + '/restore', {}, { onSuccess: () => { fetchData(); toast.success('Paket berhasil dipulihkan.'); } }); }
function bulkDelete() { router.post('/operator-perusahaan/daftar-paket/bulk-delete', { ids: selectedIds.value }, { onSuccess: () => { selectedIds.value = []; selectAll.value = false; fetchData(); toast.success('Paket berhasil dihapus.'); } }); }
function bulkSetStatus(s) { router.post('/operator-perusahaan/daftar-paket/bulk-status', { ids: selectedIds.value, status: s }, { onSuccess: () => { selectedIds.value = []; selectAll.value = false; fetchData(); toast.success('Status berhasil diubah.'); } }); }
function bulkRestore() { router.post('/operator-perusahaan/daftar-paket/bulk-restore', { ids: selectedIds.value }, { onSuccess: () => { selectedIds.value = []; selectAll.value = false; fetchData(); toast.success('Paket berhasil dipulihkan.'); } }); }

// ── Import / Export ──
const importForm = useForm({ file: null });

function exportAll() {
  window.open('/operator-perusahaan/daftar-paket/export', '_blank');
}

function exportSelected() {
  if (!selectedIds.value.length) return;
  window.open('/operator-perusahaan/daftar-paket/export?ids=' + selectedIds.value.join(','), '_blank');
}

function downloadTemplate() {
  window.open('/operator-perusahaan/daftar-paket/template', '_blank');
}

function openImport() {
  importForm.reset();
  importForm.clearErrors();
  showImportModal.value = true;
}

function submitImport() {
  importForm.post('/operator-perusahaan/daftar-paket/import', {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      showImportModal.value = false;
      fetchData();
      toast.success('Import berhasil.');
    },
    onError: () => toast.error('Import gagal.'),
  });
}

const items = computed(() => props.items?.data || []);
const pagination = computed(() => ({ current: props.items?.current_page || 1, last: props.items?.last_page || 1, total: props.items?.total || 0 }));
const hasFilter = computed(() => searchInput.value || statusFilter.value || terhapusFilter.value !== 'tidak');
</script>

<template>
  <div>
    <Head title="Paket Customer | Perusahaan" />
    <ToastContainer />
    <div class="space-y-6">
      <nav class="flex items-center gap-1.5 text-sm"><Link href="/operator-perusahaan/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-sky-600 dark:hover:text-sky-400 transition-colors"><i class="fas fa-home"></i></Link><i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i><span class="text-gray-900 dark:text-white font-medium">Paket Customer</span></nav>
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3"><div><h2 class="text-2xl font-bold text-gray-900 dark:text-white">Paket Customer</h2><p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Kelola paket internet untuk pelanggan.</p></div><div class="flex items-center gap-2 flex-wrap"><button v-if="can('paket.create') && terhapusFilter !== 'ya'" @click="openCreate" class="inline-flex items-center px-4 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm"><i class="fas fa-plus mr-1.5"></i> Tambah Paket</button><button v-if="can('paket.import')" @click="openImport" class="inline-flex items-center px-4 py-2.5 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors shadow-sm"><i class="fas fa-upload mr-1.5"></i> Import</button><button v-if="can('paket.export')" @click="exportAll" class="inline-flex items-center px-4 py-2.5 bg-violet-600 text-white text-sm font-medium rounded-lg hover:bg-violet-700 transition-colors shadow-sm"><i class="fas fa-download mr-1.5"></i> Export</button><a v-if="can('paket.import')" @click="downloadTemplate" class="inline-flex items-center px-3 py-2.5 text-xs text-sky-600 dark:text-sky-400 hover:underline cursor-pointer"><i class="fas fa-file-excel mr-1"></i> Template</a></div></div>
      <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between"><div class="relative w-full sm:w-72"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-search text-gray-400 text-sm"></i></div><input v-model="searchInput" type="text" placeholder="Cari..." class="w-full pl-10 pr-16 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm focus:ring-2 focus:ring-sky-500 outline-none" @keydown.enter="applySearch" /><div class="absolute inset-y-0 right-0 flex items-center gap-1 pr-1.5"><button v-if="searchInput" @click="clearSearch" class="p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-white" title="Clear"><i class="fas fa-times text-xs"></i></button><button @click="applySearch" class="px-2 py-1 rounded bg-sky-600 text-white hover:bg-sky-700" title="Cari"><i class="fas fa-search text-xs"></i></button></div></div><div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400"><span>{{ props.items?.total || 0 }} data</span><button v-if="statusFilter || terhapusFilter !== 'tidak' || searchInput" @click="resetFilters" class="text-xs text-red-500 hover:text-red-700 dark:hover:text-red-400 underline">Reset filter</button></div></div>

      <!-- Filter Card -->
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm"><div class="flex flex-wrap items-end gap-4"><div class="min-w-[160px]"><label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Status</label><select v-model="statusFilter" @change="applyFilters()" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors"><option value="">Semua</option><option value="Aktif">Ya (Aktif)</option><option value="Nonaktif">Tidak (Nonaktif)</option></select></div><div class="min-w-[160px]"><label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Terhapus</label><select v-model="terhapusFilter" @change="applyFilters()" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors"><option value="tidak">Tidak</option><option value="ya">Ya</option></select></div><button @click="applyFilters" class="px-4 py-2 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm"><i class="fas fa-filter mr-1.5"></i>Filter</button></div></div>

      <div v-if="selectedIds.length > 0" class="flex items-center justify-between px-4 py-3 bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-800 rounded-xl shadow-sm"><span class="text-sm font-medium text-sky-700 dark:text-sky-300"><i class="fas fa-check-circle mr-1.5"></i> {{ selectedIds.length }} data dipilih</span><div class="flex items-center gap-2"><button v-if="terhapusFilter === 'ya'" @click="bulkRestore()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-emerald-600 text-white hover:bg-emerald-700"><i class="fas fa-undo-alt mr-1"></i> Pulihkan</button><button v-if="terhapusFilter !== 'ya'" @click="bulkSetStatus('Aktif')" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-emerald-600 text-white hover:bg-emerald-700"><i class="fas fa-check mr-1"></i> Aktifkan</button><button v-if="terhapusFilter !== 'ya'" @click="bulkSetStatus('Nonaktif')" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-amber-600 text-white hover:bg-amber-700"><i class="fas fa-ban mr-1"></i> Nonaktifkan</button><button @click="bulkDelete()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-red-600 text-white hover:bg-red-700"><i class="fas fa-trash-alt mr-1"></i> Hapus</button><button v-if="can('paket.export')" @click="exportSelected" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-violet-600 text-white hover:bg-violet-700"><i class="fas fa-download mr-1"></i> Export Selected</button></div></div>
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto"><table class="w-full text-sm min-w-[700px]"><thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700"><tr><th class="px-4 py-3 w-10"><input v-model="selectAll" type="checkbox" @change="toggleSelectAll" class="rounded border-gray-300 text-sky-600" /></th>
                <th @click="sort('name')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none"><span class="inline-flex items-center gap-1">Nama Paket <i :class="['fas', sortIcon('name'), 'text-[10px]', sortField === 'name' ? 'text-sky-500' : 'text-gray-400']"></i></span></th>
                <th @click="sort('price')" class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none"><span class="inline-flex items-center gap-1">Harga <i :class="['fas', sortIcon('price'), 'text-[10px]', sortField === 'price' ? 'text-sky-500' : 'text-gray-400']"></i></span></th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Speed</th>
                <th class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-400">Quota</th>
                <th @click="sort('billing_cycle')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none"><span class="inline-flex items-center gap-1">Billing <i :class="['fas', sortIcon('billing_cycle'), 'text-[10px]', sortField === 'billing_cycle' ? 'text-sky-500' : 'text-gray-400']"></i></span></th>
                <th @click="sort('subscriptions_count')" class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none"><span class="inline-flex items-center gap-1">Langganan Aktif <i :class="['fas', sortIcon('subscriptions_count'), 'text-[10px]', sortField === 'subscriptions_count' ? 'text-sky-500' : 'text-gray-400']"></i></span></th>
                <th @click="sort('estimasi_pendapatan')" class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none"><span class="inline-flex items-center gap-1">Estimasi Pendapatan <i :class="['fas', sortIcon('estimasi_pendapatan'), 'text-[10px]', sortField === 'estimasi_pendapatan' ? 'text-sky-500' : 'text-gray-400']"></i></span></th>
                <th @click="sort('is_active')" class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none"><span class="inline-flex items-center gap-1">Status <i :class="['fas', sortIcon('is_active'), 'text-[10px]', sortField === 'is_active' ? 'text-sky-500' : 'text-gray-400']"></i></span></th>
                <th class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-400 w-24">Aksi</th></tr></thead>
        <tbody>
          <tr v-if="items.length === 0"><td colspan="10" class="px-4 py-16 text-center text-gray-400 dark:text-gray-500"><i class="fas fa-inbox text-4xl mb-3 block"></i><span class="text-sm">Tidak ada data paket</span></td></tr>
          <tr v-for="item in items" :key="item.id" class="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors" :class="{'opacity-60': item.dihapus}">
            <td class="px-4 py-3"><input v-model="selectedIds" :value="item.id" type="checkbox" class="rounded border-gray-300 text-sky-600" /></td>
            <td class="px-4 py-3"><div class="flex items-center gap-3"><div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-sky-900/40 flex items-center justify-center text-sky-600 dark:text-sky-400 font-semibold text-xs">{{ (item.name || '?')[0] }}</div><div><span class="font-medium text-gray-900 dark:text-white">{{ item.name }}</span><span v-if="item.code" class="ml-2 text-xs text-gray-400 font-mono">{{ item.code }}</span></div></div></td>
            <td class="px-4 py-3 text-right text-gray-900 dark:text-white font-mono text-xs whitespace-nowrap">{{ fc(item.price) }}</td>
            <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-xs whitespace-nowrap">{{ item.speed_down_kbps }}↓ / {{ item.speed_up_kbps }}↑ kbps</td>
            <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-xs text-center">{{ item.quota_gb }} GB{{ item.is_unlimited ? ' ∞' : '' }}</td>
            <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-xs whitespace-nowrap">{{ formatBillingCycle(item.billing_cycle) }}</td>
            <td class="px-4 py-3 text-center text-gray-900 dark:text-white font-semibold text-xs">{{ item.langganan_aktif ?? 0 }}</td>
            <td class="px-4 py-3 text-right text-gray-900 dark:text-white font-mono text-xs">{{ fc(item.estimasi_pendapatan ?? 0) }}</td>
            <td class="px-4 py-3"><span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium', statusBadge(item.status)]">{{ item.status }}</span></td>
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
          <div class="flex items-center gap-1"><button @click="goToPage(1)" :disabled="pagination.current <= 1" class="px-2 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-30"><i class="fas fa-angle-double-left"></i></button><button @click="goToPage(pagination.current-1)" :disabled="pagination.current <= 1" class="px-2 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-30"><i class="fas fa-angle-left"></i></button><button v-for="p in []" :key="p" class="px-3 py-1.5 rounded-lg text-sm font-medium" :class="p === pagination.current ? 'bg-sky-600 text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'">{{ p }}</button><span class="px-2 text-sm text-gray-500">{{ pagination.current }} / {{ pagination.last }}</span><button @click="goToPage(pagination.current+1)" :disabled="pagination.current >= pagination.last" class="px-2 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-30"><i class="fas fa-angle-right"></i></button><button @click="goToPage(pagination.last)" :disabled="pagination.current >= pagination.last" class="px-2 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-30"><i class="fas fa-angle-double-right"></i></button></div>
        </div>
      </div>
    </div>
    <Teleport to="body"><Transition name="modal"><div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showDetailModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col"><div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-eye mr-2 text-sky-500"></i>Detail Paket</h3><button @click="showDetailModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button></div><div class="overflow-y-auto flex-1 px-6 py-5 space-y-5 modal-scroll"><div class="flex items-center gap-4 pb-4 border-b border-gray-100 dark:border-gray-700"><div class="w-16 h-16 rounded-full bg-gradient-to-br from-sky-500 to-blue-600 flex items-center justify-center text-white text-2xl font-bold shrink-0">{{ (selectedItem?.name || '?')[0] }}</div><div><h4 class="text-xl font-bold text-gray-900 dark:text-white">{{ selectedItem?.name }}</h4><span v-if="selectedItem?.code" class="text-xs text-gray-400 font-mono">{{ selectedItem?.code }}</span><span v-if="selectedItem?.dihapus" class="inline-flex mt-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">Terhapus</span><span v-else :class="['inline-flex mt-1 px-2.5 py-0.5 rounded-full text-xs font-medium', statusBadge(selectedItem?.status)]">{{ selectedItem?.status }}</span></div></div><div><h5 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Informasi Paket</h5><div class="grid grid-cols-1 sm:grid-cols-2 gap-3"><div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Harga</label><p class="text-sm text-gray-900 dark:text-white mt-0.5">{{ fc(selectedItem?.price) }}</p></div><div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Billing Cycle</label><p class="text-sm text-gray-900 dark:text-white mt-0.5">{{ formatBillingCycle(selectedItem?.billing_cycle) }}</p></div><div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Download</label><p class="text-sm text-gray-900 dark:text-white mt-0.5">{{ selectedItem?.speed_down_kbps }} kbps</p></div><div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Upload</label><p class="text-sm text-gray-900 dark:text-white mt-0.5">{{ selectedItem?.speed_up_kbps }} kbps</p></div><div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Quota</label><p class="text-sm text-gray-900 dark:text-white mt-0.5">{{ selectedItem?.quota_gb }} GB{{ selectedItem?.is_unlimited ? ' (Unlimited)' : '' }}</p></div><div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Max Devices</label><p class="text-sm text-gray-900 dark:text-white mt-0.5">{{ selectedItem?.max_devices || '-' }}</p></div></div></div><div><h5 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">FUP</h5><div class="grid grid-cols-1 sm:grid-cols-2 gap-3"><div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Quota Down</label><p class="text-sm text-gray-900 dark:text-white mt-0.5">{{ selectedItem?.fup_quota_down || '-' }} GB</p></div><div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Quota Up</label><p class="text-sm text-gray-900 dark:text-white mt-0.5">{{ selectedItem?.fup_quota_up || '-' }} GB</p></div><div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Speed Down</label><p class="text-sm text-gray-900 dark:text-white mt-0.5">{{ selectedItem?.fup_speed_down_kbps || '-' }} kbps</p></div><div><label class="text-xs font-medium text-gray-500 dark:text-gray-400">Speed Up</label><p class="text-sm text-gray-900 dark:text-white mt-0.5">{{ selectedItem?.fup_speed_up_kbps || '-' }} kbps</p></div></div></div><div v-if="selectedItem?.description"><h5 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Deskripsi</h5><p class="text-sm text-gray-900 dark:text-white">{{ selectedItem?.description }}</p></div><div><h5 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Riwayat</h5><div class="space-y-2.5"><div class="flex items-start gap-3"><div class="w-7 h-7 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center shrink-0 mt-0.5"><i class="fas fa-plus text-emerald-600 dark:text-emerald-400 text-xs"></i></div><div><p class="text-sm text-gray-900 dark:text-white">Dibuat</p><p class="text-xs text-gray-500 dark:text-gray-400">{{ selectedItem?.created_at }}</p><p v-if="selectedItem?.created_by" class="text-xs text-gray-400 dark:text-gray-500">oleh {{ selectedItem?.created_by }}</p></div></div></div></div></div><div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700"><button v-if="!selectedItem?.dihapus" @click="showDetailModal = false; openEdit(selectedItem)" class="px-4 py-2 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors"><i class="fas fa-edit mr-1.5"></i> Edit</button><button v-if="selectedItem?.dihapus" @click="showDetailModal = false; confirmRestore(selectedItem)" class="px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors"><i class="fas fa-undo-alt mr-1.5"></i> Pulihkan</button><button @click="showDetailModal = false" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Tutup</button></div></div></div></Transition></Teleport>
    <!-- CREATE / EDIT MODAL -->
    <Teleport to="body"><Transition name="modal">
      <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showCreateModal = false">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
        <form @submit.prevent="submitCreate" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col">
          <div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-plus text-emerald-500 mr-2"></i>Tambah Paket</h3>
            <button type="button" @click="showCreateModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button>
          </div>
          <div class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll">
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Kode Paket <span class="text-red-500">*</span></label><input v-model="createForm.code" type="text" placeholder="e.g. b10, p25, u50" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" /></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nama Paket <span class="text-red-500">*</span></label><input v-model="createForm.name" type="text" placeholder="Nama paket internet" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" /></div>
            <div class="grid grid-cols-2 gap-3"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Harga <span class="text-red-500">*</span></label><input v-model="createForm.price" type="number" placeholder="150000" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" /></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Billing Cycle</label><select v-model="createForm.billing_cycle" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm"><option value="monthly">Bulanan</option><option value="weekly">Mingguan</option><option value="daily">Harian</option><option value="yearly">Tahunan</option></select></div></div>
            <div class="grid grid-cols-2 gap-3"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Download (kbps)</label><input v-model="createForm.speed_down_kbps" type="number" placeholder="20000" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" /></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Upload (kbps)</label><input v-model="createForm.speed_up_kbps" type="number" placeholder="10000" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" /></div></div>
            <div class="grid grid-cols-2 gap-3"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Quota (GB)</label><input v-model="createForm.quota_gb" type="number" placeholder="500" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" /></div><div class="flex items-end pb-2.5"><label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300"><input v-model="createForm.is_unlimited" type="checkbox" class="rounded border-gray-300 dark:border-gray-600 text-sky-600 focus:ring-sky-500" /> Unlimited</label></div></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Max Devices</label><input v-model="createForm.max_devices" type="number" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" /></div>
            <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-2"><h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">FUP (Fair Usage Policy)</h4><div class="grid grid-cols-2 gap-3"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Quota Down (GB)</label><input v-model="createForm.fup_quota_down" type="number" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" /></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Quota Up (GB)</label><input v-model="createForm.fup_quota_up" type="number" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" /></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Speed Down (kbps)</label><input v-model="createForm.fup_speed_down_kbps" type="number" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" /></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Speed Up (kbps)</label><input v-model="createForm.fup_speed_up_kbps" type="number" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" /></div></div></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Deskripsi</label><textarea v-model="createForm.description" rows="2" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none resize-none"></textarea></div>
          </div>
          <div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <button type="button" @click="showCreateModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button>
            <button type="submit" :disabled="createForm.processing" class="px-6 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm disabled:opacity-50"><i class="fas fa-save mr-1.5"></i>Simpan</button>
          </div>
        </form>
      </div>
    </Transition></Teleport>

    <!-- EDIT MODAL -->
    <Teleport to="body"><Transition name="modal">
      <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showEditModal = false">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
        <form @submit.prevent="submitEdit" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col">
          <div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-edit text-sky-500 mr-2"></i>Edit Paket</h3>
            <button type="button" @click="showEditModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button>
          </div>
          <div class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll">
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Kode Paket <span class="text-red-500">*</span></label><input v-model="editForm.code" type="text" placeholder="e.g. b10, p25, u50" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" /></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nama Paket <span class="text-red-500">*</span></label><input v-model="editForm.name" type="text" placeholder="Nama paket internet" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" /></div>
            <div class="grid grid-cols-2 gap-3"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Harga <span class="text-red-500">*</span></label><input v-model="editForm.price" type="number" placeholder="150000" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" /></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Billing Cycle</label><select v-model="editForm.billing_cycle" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm"><option value="monthly">Bulanan</option><option value="weekly">Mingguan</option><option value="daily">Harian</option><option value="yearly">Tahunan</option></select></div></div>
            <div class="grid grid-cols-2 gap-3"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Download (kbps)</label><input v-model="editForm.speed_down_kbps" type="number" placeholder="20000" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" /></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Upload (kbps)</label><input v-model="editForm.speed_up_kbps" type="number" placeholder="10000" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" /></div></div>
            <div class="grid grid-cols-2 gap-3"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Quota (GB)</label><input v-model="editForm.quota_gb" type="number" placeholder="500" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" /></div><div class="flex items-end pb-2.5"><label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300"><input v-model="editForm.is_unlimited" type="checkbox" class="rounded border-gray-300 dark:border-gray-600 text-sky-600 focus:ring-sky-500" /> Unlimited</label></div></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Max Devices</label><input v-model="editForm.max_devices" type="number" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" /></div>
            <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-2"><h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">FUP (Fair Usage Policy)</h4><div class="grid grid-cols-2 gap-3"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Quota Down (GB)</label><input v-model="editForm.fup_quota_down" type="number" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" /></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Quota Up (GB)</label><input v-model="editForm.fup_quota_up" type="number" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" /></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Speed Down (kbps)</label><input v-model="editForm.fup_speed_down_kbps" type="number" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" /></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Speed Up (kbps)</label><input v-model="editForm.fup_speed_up_kbps" type="number" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" /></div></div></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Deskripsi</label><textarea v-model="editForm.description" rows="2" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none resize-none"></textarea></div>
          </div>
          <div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <button type="button" @click="showEditModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button>
            <button type="submit" :disabled="editForm.processing" class="px-6 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm disabled:opacity-50"><i class="fas fa-check mr-1.5"></i>Update</button>
          </div>
        </form>
      </div>
    </Transition></Teleport>
    <!-- IMPORT MODAL -->
    <Teleport to="body"><Transition name="modal">
      <div v-if="showImportModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showImportModal = false">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
        <form @submit.prevent="submitImport" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col">
          <div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-upload text-emerald-500 mr-2"></i>Import Paket</h3><button type="button" @click="showImportModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button></div>
          <div class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll">
            <div class="bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-800 rounded-lg p-3 text-sm text-sky-700 dark:text-sky-300"><i class="fas fa-info-circle mr-1.5"></i> Upload file .xlsx atau .csv. <a @click="downloadTemplate" class="underline font-medium cursor-pointer">Download template</a> untuk format yang benar.</div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">File Excel <span class="text-red-500">*</span></label><input type="file" @input="importForm.file = $event.target.files[0]" accept=".xlsx,.csv" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-sky-600 file:text-white file:text-xs file:hover:bg-sky-700" /><p v-if="importForm.errors.file" class="text-red-500 text-xs mt-1">{{ importForm.errors.file }}</p></div>
          </div>
          <div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700"><button type="button" @click="showImportModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button><button type="submit" :disabled="importForm.processing" class="px-6 py-2.5 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors shadow-sm disabled:opacity-50"><i class="fas fa-upload mr-1.5"></i>Import</button></div>
        </form>
      </div>
    </Transition></Teleport>

    <Teleport to="body"><Transition name="modal"><div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showDeleteModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden"><div class="px-6 py-5 text-center"><div class="w-14 h-14 mx-auto rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-4"><i class="fas fa-exclamation-triangle text-red-500 text-xl"></i></div><h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Hapus Paket?</h3><p class="text-sm text-gray-500 dark:text-gray-400">Anda akan menghapus <strong class="text-gray-700 dark:text-gray-300">{{ selectedItem?.name }}</strong>. Data bisa dipulihkan kembali.</p></div><div class="flex justify-center gap-3 px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700"><button @click="showDeleteModal = false" class="px-5 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button><button @click="confirmDelete" class="px-5 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors"><i class="fas fa-trash-alt mr-1.5"></i> Hapus</button></div></div></div></Transition></Teleport>
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

