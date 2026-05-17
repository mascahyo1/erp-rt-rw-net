<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import OperatorPerusahaanLayout from '@/Layouts/OperatorPerusahaanLayout.vue';
import { useToast } from '@/Composables/useToast';
import ToastContainer from '@/Components/ToastContainer.vue';

defineOptions({ layout: OperatorPerusahaanLayout });

const props = defineProps({ pembayarans: Object, filters: Object });
const toast = useToast();

const searchInput = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const terhapusFilter = ref(props.filters?.terhapus || 'tidak');
const sortField = ref(props.filters?.sort_field || '');
const sortDir = ref(props.filters?.sort_dir || 'asc');
const perPage = ref(props.filters?.per_page ? Number(props.filters.per_page) : 10);
const perPageOptions = [5, 10, 25, 50, 100];
const selectedIds = ref([]);
const selectAll = ref(false);
const selectedItem = ref(null);
const showCreateModal = ref(false);
const showDetailModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);

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
  router.get('/operator-perusahaan/riwayat-pembayaran', buildQuery(o), { preserveState: true, preserveScroll: true, replace: true });
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
function toggleSelectAll() { selectedIds.value = selectAll.value ? props.pembayarans.data.map(p => p.id) : []; }
function toggleSelect(id) { const i = selectedIds.value.indexOf(id); i === -1 ? selectedIds.value.push(id) : selectedIds.value.splice(i, 1); }
function goToPage(p) { fetchData({ page: p }); }
function changePerPage(n) { perPage.value = n; fetchData({ per_page: n, page: 1 }); }
function statusBadgeClass(s) { if (s === 'paid') return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'; if (s === 'pending') return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'; if (s === 'cancelled') return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'; if (s === 'rejected') return 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400'; return 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400'; }
function statusLabel(s) { if (s === 'paid') return 'Lunas'; if (s === 'pending') return 'Pending'; if (s === 'cancelled') return 'Dibatalkan'; if (s === 'rejected') return 'Ditolak'; if (s === 'expired') return 'Kadaluarsa'; return s; }

const createForm = useForm({ cust_internet_invc_id: '', amount_paid: '', payment_method: '', provider: '', status_description: '' });
const editForm = useForm({ cust_internet_invc_id: '', amount_paid: '', payment_method: '', provider: '', status_description: '' });

function openCreate() { createForm.reset(); showCreateModal.value = true; }
function submitCreate() { createForm.post('/operator-perusahaan/riwayat-pembayaran', { onSuccess: () => { showCreateModal.value = false; fetchData(); toast.success('Pembayaran berhasil ditambahkan.'); } }); }
function openEdit(item) { editForm.defaults({ cust_internet_invc_id: item.cust_internet_invc_id, amount_paid: item.amount_paid, payment_method: item.payment_method, provider: item.provider, status_description: item.status_description }); editForm.reset(); selectedItem.value = item; showEditModal.value = true; }
function submitEdit() { editForm.put('/operator-perusahaan/riwayat-pembayaran/' + selectedItem.value.id, { onSuccess: () => { showEditModal.value = false; fetchData(); toast.success('Pembayaran berhasil diperbarui.'); } }); }
function openDetail(item) { selectedItem.value = item; showDetailModal.value = true; }
function openDelete(item) { selectedItem.value = item; showDeleteModal.value = true; }
function confirmDelete() { router.delete('/operator-perusahaan/riwayat-pembayaran/' + selectedItem.value.id, { onSuccess: () => { showDeleteModal.value = false; fetchData(); toast.success('Pembayaran berhasil dihapus.'); } }); }
function confirmRestore(id) { router.patch('/operator-perusahaan/riwayat-pembayaran/' + id + '/restore', { onSuccess: () => { fetchData(); toast.success('Pembayaran berhasil dipulihkan.'); } }); }
function confirmApprove(item) { router.post('/operator-perusahaan/riwayat-pembayaran/' + item.id + '/approve', {}, { preserveState: true, onSuccess: () => { fetchData(); toast.success('Pembayaran berhasil disetujui.'); } }); }
function bulkDelete() { router.post('/operator-perusahaan/riwayat-pembayaran/bulk-delete', { ids: selectedIds.value }, { onSuccess: () => { selectedIds.value = []; selectAll.value = false; fetchData(); toast.success('Pembayaran berhasil dihapus.'); } }); }

const items = computed(() => props.pembayarans?.data || []);
const pagination = computed(() => ({ current: props.pembayarans?.current_page || 1, last: props.pembayarans?.last_page || 1, total: props.pembayarans?.total || 0 }));
const hasFilter = computed(() => searchInput.value || statusFilter.value || terhapusFilter.value !== 'tidak');
</script>

<template>
  <div>
    <Head title="Riwayat Pembayaran | Perusahaan" />
    <ToastContainer />
    <div class="space-y-6">
      <nav class="flex items-center gap-1.5 text-sm"><Link href="/operator-perusahaan/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"><i class="fas fa-home"></i></Link><i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i><span class="text-gray-900 dark:text-white font-medium">Riwayat Pembayaran</span></nav>
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3"><div><h2 class="text-2xl font-bold text-gray-900 dark:text-white">Riwayat Pembayaran</h2><p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Kelola riwayat pembayaran dari semua pelanggan.</p></div><button v-if="terhapusFilter !== 'ya'" @click="openCreate" class="inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm"><i class="fas fa-plus mr-1.5"></i> Tambah Pembayaran</button></div>
      <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
          <div class="relative w-full sm:w-72"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-search text-gray-400 text-sm"></i></div><input v-model="searchInput" type="text" placeholder="Cari..." class="w-full pl-10 pr-16 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm focus:ring-2 focus:ring-indigo-500 outline-none" @keydown.enter="applySearch" /><div class="absolute inset-y-0 right-0 flex items-center gap-1 pr-1.5"><button v-if="searchInput" @click="clearSearch" class="p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-white" title="Clear"><i class="fas fa-times text-xs"></i></button><button @click="applySearch" class="px-2 py-1 rounded bg-indigo-600 text-white hover:bg-indigo-700" title="Cari"><i class="fas fa-search text-xs"></i></button></div></div>
          <div class="flex gap-1 flex-wrap">
            <button v-for="s in [{v:'tidak',l:'Aktif'},{v:'ya',l:'Terhapus'}]" :key="s.v" @click="applyTerhapus(s.v)" :class="['px-3 py-2 rounded-lg text-xs font-medium border transition-colors whitespace-nowrap', terhapusFilter === s.v ? 'bg-indigo-50 border-indigo-300 text-indigo-700 dark:bg-indigo-900/30 dark:border-indigo-700 dark:text-indigo-400' : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400']">{{ s.l }}</button>
          </div>
          <div v-if="terhapusFilter !== 'ya'" class="flex gap-1 flex-wrap"><button v-for="s in [{v:'',l:'Semua'},{v:'paid',l:'Lunas'},{v:'pending',l:'Pending'},{v:'cancelled',l:'Dibatalkan'},{v:'rejected',l:'Ditolak'},{v:'expired',l:'Kadaluarsa'}]" :key="s.v" @click="applyFilters(s.v)" :class="['px-3 py-2 rounded-lg text-xs font-medium border transition-colors whitespace-nowrap', statusFilter === s.v ? 'bg-indigo-50 border-indigo-300 text-indigo-700 dark:bg-indigo-900/30 dark:border-indigo-700 dark:text-indigo-400' : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400']">{{ s.l }}</button></div>
          <button v-if="hasFilter" @click="resetFilters" class="inline-flex items-center gap-1 px-3 py-2 text-xs font-medium text-red-600 dark:text-red-400 hover:underline whitespace-nowrap"><i class="fas fa-times-circle"></i> Reset Filter</button>
        </div>
      </div>
      <div v-if="selectedIds.length > 0" class="flex items-center justify-between px-4 py-3 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-xl shadow-sm"><span class="text-sm font-medium text-indigo-700 dark:text-indigo-300"><i class="fas fa-check-circle mr-1.5"></i> {{ selectedIds.length }} data dipilih</span><div class="flex items-center gap-2"><button v-if="terhapusFilter !== 'ya'" @click="bulkDelete()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-red-600 text-white hover:bg-red-700"><i class="fas fa-trash-alt mr-1"></i> Hapus</button></div></div>
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto"><table class="w-full text-sm min-w-[800px]"><thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700"><tr><th class="px-4 py-3 w-10"><input v-model="selectAll" type="checkbox" @change="toggleSelectAll" class="rounded border-gray-300 text-indigo-600" /></th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">No. Invoice</th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Pelanggan</th><th @click="sort('amount_paid')" class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none"><span class="inline-flex items-center gap-1">Jumlah <i :class="['fas', sortIcon('amount_paid'), 'text-[10px]', sortField === 'amount_paid' ? 'text-indigo-500' : 'text-gray-400']"></i></span></th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Metode</th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Provider</th><th @click="sort('status')" class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none"><span class="inline-flex items-center gap-1">Status <i :class="['fas', sortIcon('status'), 'text-[10px]', sortField === 'status' ? 'text-indigo-500' : 'text-gray-400']"></i></span></th><th @click="sort('created_at')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none"><span class="inline-flex items-center gap-1">Tgl Dibuat <i :class="['fas', sortIcon('created_at'), 'text-[10px]', sortField === 'created_at' ? 'text-indigo-500' : 'text-gray-400']"></i></span></th><th class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-400 w-32">Aksi</th></tr></thead>
        <tbody>
          <tr v-if="items.length === 0"><td colspan="9" class="px-4 py-16 text-center text-gray-400 dark:text-gray-500"><i class="fas fa-inbox text-4xl mb-3 block"></i><span class="text-sm">Tidak ada data pembayaran</span></td></tr>
          <tr v-for="item in items" :key="item.id" class="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors" :class="{'opacity-60': item.dihapus}">
            <td class="px-4 py-3"><input v-model="selectedIds" :value="item.id" type="checkbox" @change="toggleSelect(item.id)" class="rounded border-gray-300 text-indigo-600" /></td>
            <td class="px-4 py-3"><span class="font-mono text-xs text-gray-900 dark:text-white font-medium">{{ item.invoice_number }}</span></td>
            <td class="px-4 py-3"><div class="flex items-center gap-3"><div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-semibold text-xs">{{ (item.customer_name || '?')[0] }}</div><span class="font-medium text-gray-900 dark:text-white">{{ item.customer_name }}</span></div></td>
            <td class="px-4 py-3 text-right text-gray-900 dark:text-white font-mono text-xs">{{ item.amount_paid ? 'Rp ' + Number(item.amount_paid).toLocaleString('id') : '-' }}</td>
            <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-xs">{{ item.payment_method || '-' }}</td>
            <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-xs">{{ item.provider || '-' }}</td>
            <td class="px-4 py-3 text-center"><span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium', statusBadgeClass(item.status)]">{{ statusLabel(item.status) }}</span></td>
            <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">{{ item.created_at }}</td>
            <td class="px-4 py-3">
              <div class="flex items-center justify-center gap-1">
                <button v-if="!item.dihapus" @click="openDetail(item)" title="Detail" class="p-1.5 rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:text-indigo-400 dark:hover:bg-indigo-900/30"><i class="fas fa-eye"></i></button>
                <button v-if="!item.dihapus" @click="openEdit(item)" title="Edit" class="p-1.5 rounded-lg text-gray-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:text-amber-400 dark:hover:bg-amber-900/30"><i class="fas fa-edit"></i></button>
                <button v-if="!item.dihapus && item.status === 'pending'" @click="confirmApprove(item)" title="Setujui" class="p-1.5 rounded-lg text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:text-emerald-400 dark:hover:bg-emerald-900/30"><i class="fas fa-check"></i></button>
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
    <Teleport to="body"><Transition name="modal"><div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showDetailModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg"><div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold">Detail Pembayaran</h3><button @click="showDetailModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700"><i class="fas fa-times"></i></button></div><div class="px-6 py-5 space-y-3" v-if="selectedItem"><div class="grid grid-cols-2 gap-3"><div><label class="text-xs text-gray-500">No. Invoice</label><p class="font-mono font-medium">{{ selectedItem.invoice_number }}</p></div><div><label class="text-xs text-gray-500">Pelanggan</label><p class="font-medium">{{ selectedItem.customer_name }}</p></div><div><label class="text-xs text-gray-500">Jumlah</label><p class="font-mono font-semibold">Rp {{ Number(selectedItem.amount_paid || 0).toLocaleString('id') }}</p></div><div><label class="text-xs text-gray-500">Metode</label><p>{{ selectedItem.payment_method || '-' }}</p></div><div><label class="text-xs text-gray-500">Provider</label><p>{{ selectedItem.provider || '-' }}</p></div><div><label class="text-xs text-gray-500">Status</label><p><span :class="['px-2 py-0.5 rounded text-xs font-medium', statusBadgeClass(selectedItem.status)]">{{ statusLabel(selectedItem.status) }}</span></p></div><div><label class="text-xs text-gray-500">Keterangan</label><p>{{ selectedItem.status_description || '-' }}</p></div><div><label class="text-xs text-gray-500">Bukti</label><p>{{ selectedItem.proof_file || '-' }}</p></div></div></div></div></div></Transition></Teleport>
    <Teleport to="body"><Transition name="modal"><div v-if="showCreateModal || showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showCreateModal = showEditModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto"><div class="sticky top-0 z-10 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-t-2xl"><h3 class="text-lg font-semibold"><i :class="['fas mr-2', showCreateModal ? 'fa-plus text-emerald-500' : 'fa-edit text-amber-500']"></i>{{ showCreateModal ? 'Tambah Pembayaran' : 'Edit Pembayaran' }}</h3><button @click="showCreateModal = showEditModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700"><i class="fas fa-times"></i></button></div><form class="px-6 py-5 space-y-4" @submit.prevent="showCreateModal ? submitCreate() : submitEdit()">
        <div><label class="block text-sm font-medium mb-1.5">ID Invoice <span class="text-red-500">*</span></label><input v-model="(showCreateModal ? createForm : editForm).cust_internet_invc_id" type="text" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm focus:ring-2 focus:ring-indigo-500 outline-none" /></div>
        <div><label class="block text-sm font-medium mb-1.5">Jumlah (Rp) <span class="text-red-500">*</span></label><input v-model="(showCreateModal ? createForm : editForm).amount_paid" type="number" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm focus:ring-2 focus:ring-indigo-500 outline-none" /></div>
        <div class="grid grid-cols-2 gap-4"><div><label class="block text-sm font-medium mb-1.5">Metode</label><input v-model="(showCreateModal ? createForm : editForm).payment_method" type="text" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm focus:ring-2 focus:ring-indigo-500 outline-none" /></div><div><label class="block text-sm font-medium mb-1.5">Provider</label><input v-model="(showCreateModal ? createForm : editForm).provider" type="text" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm focus:ring-2 focus:ring-indigo-500 outline-none" /></div></div>
        <div><label class="block text-sm font-medium mb-1.5">Keterangan</label><textarea v-model="(showCreateModal ? createForm : editForm).status_description" rows="2" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"></textarea></div>
        <div class="flex justify-end gap-2 pt-2"><button type="button" @click="showCreateModal = showEditModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">Batal</button><button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 shadow-sm"><i class="fas fa-save mr-1.5"></i>{{ showCreateModal ? 'Simpan' : 'Update' }}</button></div>
      </form></div></div></Transition></Teleport>
    <Teleport to="body"><Transition name="modal"><div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showDeleteModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-sm"><div class="px-6 py-5 text-center"><div class="w-14 h-14 mx-auto rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-4"><i class="fas fa-exclamation-triangle text-red-500 text-xl"></i></div><h3 class="text-lg font-semibold mb-2">Hapus Pembayaran?</h3><p class="text-sm text-gray-500">Anda akan menghapus pembayaran <strong>{{ selectedItem?.invoice_number }}</strong> dari <strong>{{ selectedItem?.customer_name }}</strong>.</p></div><div class="flex justify-center gap-3 px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700"><button @click="showDeleteModal = false" class="px-5 py-2.5 bg-gray-200 dark:bg-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">Batal</button><button @click="confirmDelete" class="px-5 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700"><i class="fas fa-trash-alt mr-1.5"></i> Hapus</button></div></div></div></Transition></Teleport>
  </div>
</template>
<style scoped>.modal-enter-active,.modal-leave-active{transition:opacity .2s ease}.modal-enter-active>div:last-child,.modal-leave-active>div:last-child{transition:transform .2s ease,opacity .2s ease}.modal-enter-from,.modal-leave-to{opacity:0}.modal-enter-from>div:last-child{transform:scale(.95) translateY(10px);opacity:0}.modal-leave-to>div:last-child{transform:scale(.95) translateY(10px);opacity:0}</style>
