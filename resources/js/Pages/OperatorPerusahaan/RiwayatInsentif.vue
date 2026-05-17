<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import OperatorPerusahaanLayout from '@/Layouts/OperatorPerusahaanLayout.vue';
import { useToast } from '@/Composables/useToast';
import ToastContainer from '@/Components/ToastContainer.vue';

defineOptions({ layout: OperatorPerusahaanLayout });

const props = defineProps({ riwayats: Object, filters: Object });
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
  router.get('/operator-perusahaan/riwayat-insentif', buildQuery(o), { preserveState: true, preserveScroll: true, replace: true });
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
function toggleSelectAll() { selectedIds.value = selectAll.value ? props.riwayats.data.map(r => r.id) : []; }
function toggleSelect(id) { const i = selectedIds.value.indexOf(id); i === -1 ? selectedIds.value.push(id) : selectedIds.value.splice(i, 1); }
function goToPage(p) { fetchData({ page: p }); }
function changePerPage(n) { perPage.value = n; fetchData({ per_page: n, page: 1 }); }
function statusBadgeClass(s) { if (s === 'approved') return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'; if (s === 'pending') return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'; return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'; }
function statusLabel(s) { if (s === 'approved') return 'Disetujui'; if (s === 'pending') return 'Pending'; if (s === 'rejected') return 'Ditolak'; return s; }

const createForm = useForm({ emp_incentive_id: '', cust_internet_invcs_id: '', amount: '', date: '' });
const editForm = useForm({ emp_incentive_id: '', cust_internet_invcs_id: '', amount: '', date: '' });

function openCreate() { createForm.reset(); showCreateModal.value = true; }
function submitCreate() { createForm.post('/operator-perusahaan/riwayat-insentif', { onSuccess: () => { showCreateModal.value = false; fetchData(); toast.success('Riwayat insentif berhasil ditambahkan.'); } }); }
function openEdit(item) { editForm.defaults({ emp_incentive_id: item.emp_incentive_id, cust_internet_invcs_id: item.cust_internet_invcs_id, amount: item.amount, date: item.date }); editForm.reset(); selectedItem.value = item; showEditModal.value = true; }
function submitEdit() { editForm.put('/operator-perusahaan/riwayat-insentif/' + selectedItem.value.id, { onSuccess: () => { showEditModal.value = false; fetchData(); toast.success('Riwayat insentif berhasil diperbarui.'); } }); }
function openDetail(item) { selectedItem.value = item; showDetailModal.value = true; }
function openDelete(item) { selectedItem.value = item; showDeleteModal.value = true; }
function confirmDelete() { router.delete('/operator-perusahaan/riwayat-insentif/' + selectedItem.value.id, { onSuccess: () => { showDeleteModal.value = false; fetchData(); toast.success('Riwayat insentif berhasil dihapus.'); } }); }
function confirmRestore(id) { router.patch('/operator-perusahaan/riwayat-insentif/' + id + '/restore', { onSuccess: () => { fetchData(); toast.success('Riwayat insentif berhasil dipulihkan.'); } }); }
function confirmApprove(item) { router.post('/operator-perusahaan/riwayat-insentif/' + item.id + '/approve', {}, { preserveState: true, onSuccess: () => { fetchData(); toast.success('Riwayat insentif berhasil disetujui.'); } }); }
function bulkDelete() { router.post('/operator-perusahaan/riwayat-insentif/bulk-delete', { ids: selectedIds.value }, { onSuccess: () => { selectedIds.value = []; selectAll.value = false; fetchData(); toast.success('Riwayat insentif berhasil dihapus.'); } }); }
function bulkRestore() { router.post('/operator-perusahaan/riwayat-insentif/bulk-restore', { ids: selectedIds.value }, { onSuccess: () => { selectedIds.value = []; selectAll.value = false; fetchData(); toast.success('Riwayat insentif berhasil dipulihkan.'); } }); }

const items = computed(() => props.riwayats?.data || []);
const pagination = computed(() => ({ current: props.riwayats?.current_page || 1, last: props.riwayats?.last_page || 1, total: props.riwayats?.total || 0 }));
const hasFilter = computed(() => searchInput.value || statusFilter.value || terhapusFilter.value !== 'tidak');
</script>

<template>
  <div>
    <Head title="Riwayat Insentif | Perusahaan" />
    <ToastContainer />
    <div class="space-y-6">
      <nav class="flex items-center gap-1.5 text-sm"><Link href="/operator-perusahaan/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-sky-600 dark:hover:text-sky-400 transition-colors"><i class="fas fa-home"></i></Link><i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i><span class="text-gray-900 dark:text-white font-medium">Riwayat Insentif</span></nav>
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3"><div><h2 class="text-2xl font-bold text-gray-900 dark:text-white">Riwayat Insentif</h2><p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Riwayat perhitungan insentif untuk setiap transaksi.</p></div><button v-if="terhapusFilter !== 'ya'" @click="openCreate" class="inline-flex items-center px-4 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm"><i class="fas fa-plus mr-1.5"></i> Tambah Riwayat</button></div>
      <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between"><div class="relative w-full sm:w-72"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-search text-gray-400 text-sm"></i></div><input v-model="searchInput" type="text" placeholder="Cari..." class="w-full pl-10 pr-16 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm focus:ring-2 focus:ring-sky-500 outline-none" @keydown.enter="applySearch" /><div class="absolute inset-y-0 right-0 flex items-center gap-1 pr-1.5"><button v-if="searchInput" @click="clearSearch" class="p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-white" title="Clear"><i class="fas fa-times text-xs"></i></button><button @click="applySearch" class="px-2 py-1 rounded bg-sky-600 text-white hover:bg-sky-700" title="Cari"><i class="fas fa-search text-xs"></i></button></div></div><div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400"><span>{{ props.riwayats?.total || 0 }} data</span><button v-if="statusFilter || terhapusFilter !== 'tidak' || searchInput" @click="resetFilters" class="text-xs text-red-500 hover:text-red-700 dark:hover:text-red-400 underline">Reset filter</button></div></div>

      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm"><div class="flex flex-wrap items-end gap-4"><div class="min-w-[160px]"><label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Status</label><select v-model="statusFilter" @change="applyFilters()" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors"><option value="">Semua</option><option value="pending">Pending</option><option value="approved">Disetujui</option><option value="rejected">Ditolak</option></select></div><div class="min-w-[160px]"><label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Terhapus</label><select v-model="terhapusFilter" @change="applyFilters()" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors"><option value="tidak">Tidak</option><option value="ya">Ya</option></select></div><button @click="applyFilters" class="px-4 py-2 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm"><i class="fas fa-filter mr-1.5"></i>Filter</button></div></div>

      <div v-if="selectedIds.length > 0" class="flex items-center justify-between px-4 py-3 bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-800 rounded-xl shadow-sm"><span class="text-sm font-medium text-sky-700 dark:text-sky-300"><i class="fas fa-check-circle mr-1.5"></i> {{ selectedIds.length }} data dipilih</span><div class="flex items-center gap-2"><button v-if="terhapusFilter === 'ya'" @click="bulkRestore()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-emerald-600 text-white hover:bg-emerald-700"><i class="fas fa-undo-alt mr-1"></i> Pulihkan</button><button @click="bulkDelete()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-red-600 text-white hover:bg-red-700"><i class="fas fa-trash-alt mr-1"></i> Hapus</button></div></div>
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto"><table class="w-full text-sm min-w-[700px]"><thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700"><tr><th class="px-4 py-3 w-10"><input v-model="selectAll" type="checkbox" @change="toggleSelectAll" class="rounded border-gray-300 text-sky-600" /></th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Insentif</th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">No. Invoice</th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Pelanggan</th><th @click="sort('amount')" class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none"><span class="inline-flex items-center gap-1">Jumlah <i :class="['fas', sortIcon('amount'), 'text-[10px]', sortField === 'amount' ? 'text-sky-500' : 'text-gray-400']"></i></span></th><th @click="sort('date')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none"><span class="inline-flex items-center gap-1">Tanggal <i :class="['fas', sortIcon('date'), 'text-[10px]', sortField === 'date' ? 'text-sky-500' : 'text-gray-400']"></i></span></th><th @click="sort('approval_status')" class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none"><span class="inline-flex items-center gap-1">Status <i :class="['fas', sortIcon('approval_status'), 'text-[10px]', sortField === 'approval_status' ? 'text-sky-500' : 'text-gray-400']"></i></span></th><th @click="sort('created_at')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none"><span class="inline-flex items-center gap-1">Tgl Dibuat <i :class="['fas', sortIcon('created_at'), 'text-[10px]', sortField === 'created_at' ? 'text-sky-500' : 'text-gray-400']"></i></span></th><th class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-400 w-28">Aksi</th></tr></thead>
        <tbody>
          <tr v-if="items.length === 0"><td colspan="9" class="px-4 py-16 text-center text-gray-400 dark:text-gray-500"><i class="fas fa-inbox text-4xl mb-3 block"></i><span class="text-sm">Tidak ada data riwayat insentif</span></td></tr>
          <tr v-for="item in items" :key="item.id" class="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors" :class="{'opacity-60': item.dihapus}">
            <td class="px-4 py-3"><input v-model="selectedIds" :value="item.id" type="checkbox" @change="toggleSelect(item.id)" class="rounded border-gray-300 text-sky-600" /></td>
            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ item.incentive_name || '-' }}</td>
            <td class="px-4 py-3"><span class="font-mono text-xs text-gray-900 dark:text-white">{{ item.invoice_number || '-' }}</span></td>
            <td class="px-4 py-3"><div class="flex items-center gap-3"><div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-sky-900/40 flex items-center justify-center text-sky-600 dark:text-sky-400 font-semibold text-xs">{{ (item.customer_name || '?')[0] }}</div><span class="font-medium text-gray-900 dark:text-white">{{ item.customer_name }}</span></div></td>
            <td class="px-4 py-3 text-right text-gray-900 dark:text-white font-mono text-xs">{{ item.amount ? 'Rp ' + Number(item.amount).toLocaleString('id') : '-' }}</td>
            <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">{{ item.date }}</td>
            <td class="px-4 py-3 text-center"><span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium', statusBadgeClass(item.approval_status)]">{{ statusLabel(item.approval_status) }}</span></td>
            <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">{{ item.created_at }}</td>
            <td class="px-4 py-3">
              <div class="flex items-center justify-center gap-1">
                <button v-if="!item.dihapus" @click="openDetail(item)" title="Detail" class="p-1.5 rounded-lg text-gray-400 hover:text-sky-600 hover:bg-sky-50 dark:hover:text-sky-400 dark:hover:bg-indigo-900/30"><i class="fas fa-eye"></i></button>
                <button v-if="!item.dihapus" @click="openEdit(item)" title="Edit" class="p-1.5 rounded-lg text-gray-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:text-amber-400 dark:hover:bg-amber-900/30"><i class="fas fa-edit"></i></button>
                <button v-if="!item.dihapus && item.approval_status === 'pending'" @click="confirmApprove(item)" title="Setujui" class="p-1.5 rounded-lg text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:text-emerald-400 dark:hover:bg-emerald-900/30"><i class="fas fa-check"></i></button>
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
    <Teleport to="body"><Transition name="modal"><div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showDetailModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg"><div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold">Detail Riwayat Insentif</h3><button @click="showDetailModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700"><i class="fas fa-times"></i></button></div><div class="px-6 py-5 space-y-3" v-if="selectedItem"><div class="grid grid-cols-2 gap-3"><div><label class="text-xs text-gray-500">Insentif</label><p class="font-medium">{{ selectedItem.incentive_name }}</p></div><div><label class="text-xs text-gray-500">No. Invoice</label><p class="font-mono">{{ selectedItem.invoice_number }}</p></div><div><label class="text-xs text-gray-500">Pelanggan</label><p class="font-medium">{{ selectedItem.customer_name }}</p></div><div><label class="text-xs text-gray-500">Jumlah</label><p class="font-mono font-semibold">Rp {{ Number(selectedItem.amount || 0).toLocaleString('id') }}</p></div><div><label class="text-xs text-gray-500">Tanggal</label><p>{{ selectedItem.date }}</p></div><div><label class="text-xs text-gray-500">Status</label><p><span :class="['px-2 py-0.5 rounded text-xs font-medium', statusBadgeClass(selectedItem.approval_status)]">{{ statusLabel(selectedItem.approval_status) }}</span></p></div><div><label class="text-xs text-gray-500">Disetujui Pada</label><p>{{ selectedItem.approved_at || '-' }}</p></div></div></div></div></div></Transition></Teleport>
    <Teleport to="body"><Transition name="modal"><div v-if="showCreateModal || showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showCreateModal = showEditModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto"><div class="sticky top-0 z-10 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-t-2xl"><h3 class="text-lg font-semibold"><i :class="['fas mr-2', showCreateModal ? 'fa-plus text-emerald-500' : 'fa-edit text-amber-500']"></i>{{ showCreateModal ? 'Tambah Riwayat Insentif' : 'Edit Riwayat Insentif' }}</h3><button @click="showCreateModal = showEditModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700"><i class="fas fa-times"></i></button></div><form class="px-6 py-5 space-y-4" @submit.prevent="showCreateModal ? submitCreate() : submitEdit()">
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">ID Insentif <span class="text-red-500">*</span></label><input v-model="(showCreateModal ? createForm : editForm).emp_incentive_id" type="text" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" /></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">ID Invoice <span class="text-red-500">*</span></label><input v-model="(showCreateModal ? createForm : editForm).cust_internet_invcs_id" type="text" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" /></div>
        <div class="grid grid-cols-2 gap-4"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Jumlah (Rp) <span class="text-red-500">*</span></label><input v-model="(showCreateModal ? createForm : editForm).amount" type="number" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" /></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Tanggal <span class="text-red-500">*</span></label><input v-model="(showCreateModal ? createForm : editForm).date" type="date" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none" /></div></div>
        <div class="flex justify-end gap-2 pt-2"><button type="button" @click="showCreateModal = showEditModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">Batal</button><button type="submit" class="px-6 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 shadow-sm"><i class="fas fa-save mr-1.5"></i>{{ showCreateModal ? 'Simpan' : 'Update' }}</button></div>
      </form></div></div></Transition></Teleport>
    <Teleport to="body"><Transition name="modal"><div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showDeleteModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-sm"><div class="px-6 py-5 text-center"><div class="w-14 h-14 mx-auto rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-4"><i class="fas fa-exclamation-triangle text-red-500 text-xl"></i></div><h3 class="text-lg font-semibold mb-2">Hapus Riwayat Insentif?</h3><p class="text-sm text-gray-500">Anda akan menghapus riwayat insentif <strong>{{ selectedItem?.incentive_name }}</strong> untuk <strong>{{ selectedItem?.customer_name }}</strong>.</p></div><div class="flex justify-center gap-3 px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700"><button @click="showDeleteModal = false" class="px-5 py-2.5 bg-gray-200 dark:bg-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">Batal</button><button @click="confirmDelete" class="px-5 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700"><i class="fas fa-trash-alt mr-1.5"></i> Hapus</button></div></div></div></Transition></Teleport>
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
