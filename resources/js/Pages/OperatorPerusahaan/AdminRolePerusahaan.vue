<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import OperatorPerusahaanLayout from '@/Layouts/OperatorPerusahaanLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useToast } from '@/Composables/useToast';
import ToastContainer from '@/Components/ToastContainer.vue';

defineOptions({ layout: OperatorPerusahaanLayout });

const props = defineProps({ assignments: Object, admins: Array, roles: Array, filters: Object });
const toast = useToast();
const page = usePage();
const can = (perm) => page.props.permissions?.includes(perm);

const searchInput = ref(props.filters?.search || '');
const sortField = ref(props.filters?.sort_field || '');
const sortDir = ref(props.filters?.sort_dir || 'asc');
const perPage = ref(props.filters?.per_page ? Number(props.filters.per_page) : 10);
const perPageOptions = [5, 10, 25, 50, 100];
const selectedIds = ref([]); const selectAll = ref(false);
const selectedItem = ref(null);
const showCreateModal = ref(false); const showDetailModal = ref(false);
const showEditModal = ref(false); const showDeleteModal = ref(false);
const showImportModal = ref(false);
const importForm = useForm({ file: null });

const adminOptions = computed(() => (props.admins || []).map(a => ({ value: a.id, label: a.name + (a.email ? ' — ' + a.email : '') })));
const roleOptions = computed(() => (props.roles || []).map(r => ({ value: r.id, label: r.name })));

function buildQuery(o = {}) {
  const p = { ...o };
  if (p.search === undefined) p.search = searchInput.value || undefined;
  if (p.sort_field === undefined) p.sort_field = sortField.value || undefined;
  if (p.sort_dir === undefined) p.sort_dir = sortDir.value || undefined;
  if (p.per_page === undefined) p.per_page = perPage.value;
  Object.keys(p).forEach(k => { if (p[k] === undefined) delete p[k]; });
  return p;
}
function fetchData(o = {}) {
  router.get('/operator-perusahaan/admin-role-perusahaan', buildQuery(o), { preserveState: true, preserveScroll: true, replace: true });
}
function applySearch() { fetchData({ search: searchInput.value || undefined, page: 1 }); }
function clearSearch() { searchInput.value = ''; fetchData({ search: undefined, page: 1 }); }
function sort(f) {
  if (sortField.value === f) { if (sortDir.value === 'asc') sortDir.value = 'desc'; else { sortField.value = ''; sortDir.value = 'asc'; } }
  else { sortField.value = f; sortDir.value = 'asc'; }
  fetchData({ sort_field: sortField.value || undefined, sort_dir: sortDir.value || undefined });
}
function sortIcon(f) { if (sortField.value !== f) return 'fa-sort'; return sortDir.value === 'asc' ? 'fa-sort-up' : 'fa-sort-down'; }
function toggleSelectAll() { selectedIds.value = selectAll.value ? items.value.map(m => m.id) : []; }
function toggleSelect(id) { const i = selectedIds.value.indexOf(id); i === -1 ? selectedIds.value.push(id) : selectedIds.value.splice(i, 1); }
function goToPage(p) { fetchData({ page: p }); }
function changePerPage(n) { perPage.value = n; fetchData({ per_page: n, page: 1 }); }

const createForm = useForm({ admin_id: '', role_id: '' });
const editForm = useForm({ role_id: '' });

function openCreate() { createForm.reset(); createForm.clearErrors(); showCreateModal.value = true; }
function submitCreate() { createForm.post('/operator-perusahaan/admin-role-perusahaan', { preserveState: true, preserveScroll: true, onSuccess: () => { showCreateModal.value = false; fetchData(); toast.success('Mapping berhasil ditambahkan.'); }, onError: () => toast.error('Validasi gagal. Periksa kembali isian form.') }); }
function openEdit(item) { editForm.defaults({ role_id: item.role_id }); editForm.reset(); editForm.clearErrors(); selectedItem.value = item; showEditModal.value = true; }
function submitEdit() { editForm.put('/operator-perusahaan/admin-role-perusahaan/' + selectedItem.value.id, { preserveState: true, preserveScroll: true, onSuccess: () => { showEditModal.value = false; fetchData(); toast.success('Mapping berhasil diperbarui.'); }, onError: () => toast.error('Validasi gagal. Periksa kembali isian form.') }); }
function openDetail(item) { selectedItem.value = item; showDetailModal.value = true; }
function openDelete(item) { selectedItem.value = item; showDeleteModal.value = true; }
function confirmDelete() {
  const label = selectedItem.value ? `${selectedItem.value.admin_nama} — ${selectedItem.value.role_nama}` : '';
  router.delete('/operator-perusahaan/admin-role-perusahaan/' + selectedItem.value.id, { preserveState: true, preserveScroll: true, onSuccess: () => { showDeleteModal.value = false; fetchData(); toast.success(`Mapping ${label} berhasil dihapus.`) }, onError: () => toast.error('Gagal menghapus mapping.') });
}
function bulkDelete() { if (!selectedIds.value.length) return; router.post('/operator-perusahaan/admin-role-perusahaan/bulk-delete', { ids: [...selectedIds.value] }, { preserveState: true, preserveScroll: true, onSuccess: () => { selectedIds.value = []; selectAll.value = false; fetchData(); toast.success('Mapping berhasil dihapus.') }, onError: () => toast.error('Gagal menghapus mapping.') }); }

function exportAll() { window.open('/operator-perusahaan/admin-role-perusahaan/export', '_blank'); }
function exportSelected() { if (!selectedIds.value.length) return; window.open('/operator-perusahaan/admin-role-perusahaan/export?ids=' + selectedIds.value.join(','), '_blank'); }
function downloadTemplate() { window.open('/operator-perusahaan/admin-role-perusahaan/template', '_blank'); }
function openImport() { importForm.reset(); importForm.clearErrors(); showImportModal.value = true; }
function submitImport() { importForm.post('/operator-perusahaan/admin-role-perusahaan/import', { preserveState: true, preserveScroll: true, onSuccess: () => { showImportModal.value = false; fetchData(); toast.success('Import berhasil.') }, onError: () => toast.error('Import gagal. Periksa format file.') }); }

const items = computed(() => props.assignments?.data || []);
const pagination = computed(() => ({ current: props.assignments?.current_page || 1, last: props.assignments?.last_page || 1, total: props.assignments?.total || 0 }));
</script>

<template>
  <div>
    <Head title="Admin Role Perusahaan | Perusahaan" />
    <ToastContainer />
    <div class="space-y-6">
      <nav class="flex items-center gap-1.5 text-sm">
        <Link href="/operator-perusahaan/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-sky-600 dark:hover:text-sky-400 transition-colors"><i class="fas fa-home"></i></Link>
        <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
        <span class="text-gray-900 dark:text-white font-medium">Admin Role Perusahaan</span>
      </nav>

      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Admin Role Perusahaan</h2>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Pemetaan role untuk admin perusahaan.</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
          <button v-if="can('admin-role-perusahaan-op.create')" @click="openCreate" class="inline-flex items-center px-4 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm">
            <i class="fas fa-plus mr-1.5"></i> Tambah Mapping
          </button>
          <button v-if="can('admin-role-perusahaan-op.import')" @click="openImport" class="inline-flex items-center px-4 py-2.5 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors shadow-sm">
            <i class="fas fa-upload mr-1.5"></i> Import
          </button>
          <button v-if="can('admin-role-perusahaan-op.export')" @click="exportAll" class="inline-flex items-center px-4 py-2.5 bg-violet-600 text-white text-sm font-medium rounded-lg hover:bg-violet-700 transition-colors shadow-sm">
            <i class="fas fa-download mr-1.5"></i> Export
          </button>
          <a v-if="can('admin-role-perusahaan-op.import')" @click="downloadTemplate" class="inline-flex items-center px-3 py-2.5 text-xs text-sky-600 dark:text-sky-400 hover:underline cursor-pointer">
            <i class="fas fa-file-excel mr-1"></i> Template
          </a>
        </div>
      </div>

      <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
          <div class="relative w-full sm:w-72">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-search text-gray-400 text-sm"></i></div>
            <input v-model="searchInput" type="text" placeholder="Cari admin..." class="w-full pl-10 pr-16 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors" @keydown.enter="applySearch" />
            <div class="absolute inset-y-0 right-0 flex items-center gap-1 pr-1.5">
              <button v-if="searchInput" @click="clearSearch" class="p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-white transition-colors" title="Clear"><i class="fas fa-times text-xs"></i></button>
              <button @click="applySearch" class="px-2 py-1 rounded bg-sky-600 text-white hover:bg-sky-700 transition-colors" title="Cari"><i class="fas fa-search text-xs"></i></button>
            </div>
          </div>
        </div>
      </div>

      <div v-if="selectedIds.length > 0" class="flex items-center justify-between px-4 py-3 bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-800 rounded-xl shadow-sm flex-wrap gap-2">
        <span class="text-sm font-medium text-sky-700 dark:text-sky-300"><i class="fas fa-check-circle mr-1.5"></i> {{ selectedIds.length }} data dipilih</span>
        <div class="flex items-center gap-2 flex-wrap">
          <button v-if="can('admin-role-perusahaan-op.delete')" @click="bulkDelete()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-red-600 text-white hover:bg-red-700 transition-colors"><i class="fas fa-trash-alt mr-1"></i> Hapus</button>
          <button v-if="can('admin-role-perusahaan-op.export')" @click="exportSelected" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-violet-600 text-white hover:bg-violet-700 transition-colors"><i class="fas fa-download mr-1"></i> Export Selected</button>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm min-w-[640px]">
            <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
              <tr>
                <th class="px-4 py-3 w-10"><input v-model="selectAll" type="checkbox" @change="toggleSelectAll" class="rounded border-gray-300 dark:border-gray-600 text-sky-600 focus:ring-sky-500" /></th>
                <th @click="sort('admin_nama')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none hover:text-gray-900 dark:hover:text-white transition-colors">
                  <span class="inline-flex items-center gap-1">Admin <i :class="['fas', sortIcon('admin_nama'), 'text-[10px]', sortField === 'admin_nama' ? 'text-sky-500' : 'text-gray-400']"></i></span>
                </th>
                <th @click="sort('role_nama')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none hover:text-gray-900 dark:hover:text-white transition-colors">
                  <span class="inline-flex items-center gap-1">Role <i :class="['fas', sortIcon('role_nama'), 'text-[10px]', sortField === 'role_nama' ? 'text-sky-500' : 'text-gray-400']"></i></span>
                </th>
                <th @click="sort('created_at')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none hover:text-gray-900 dark:hover:text-white transition-colors">
                  <span class="inline-flex items-center gap-1">Tgl Dibuat <i :class="['fas', sortIcon('created_at'), 'text-[10px]', sortField === 'created_at' ? 'text-sky-500' : 'text-gray-400']"></i></span>
                </th>
                <th class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-400 w-24">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-if="items.length === 0"><td colspan="5" class="px-4 py-16 text-center text-gray-500 dark:text-gray-400"><i class="fas fa-inbox text-3xl mb-2 block opacity-40"></i>Tidak ada data mapping.</td></tr>
              <tr v-for="item in items" :key="item.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                <td class="px-4 py-3"><input :checked="selectedIds.includes(item.id)" type="checkbox" @change="toggleSelect(item.id)" class="rounded border-gray-300 dark:border-gray-600 text-sky-600 focus:ring-sky-500" /></td>
                <td class="px-4 py-3">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-sky-500 to-blue-600 flex items-center justify-center text-white text-xs font-bold shrink-0">{{ (item.admin_nama || '?')[0] }}</div>
                    <div class="min-w-0">
                      <div class="font-medium text-gray-900 dark:text-white whitespace-nowrap">{{ item.admin_nama }}</div>
                      <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ item.admin_email || '—' }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-3"><span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400"><i class="fas fa-tag text-[10px]"></i> {{ item.role_nama }}</span></td>
                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs whitespace-nowrap">{{ item.created_at }}</td>
                <td class="px-4 py-3">
                  <div class="flex items-center justify-center gap-1">
                    <button @click="openDetail(item)" title="Detail" class="p-1.5 rounded-lg text-gray-400 hover:text-sky-600 hover:bg-sky-50 dark:hover:text-sky-400 dark:hover:bg-sky-900/30 transition-colors"><i class="fas fa-eye"></i></button>
                    <button v-if="can('admin-role-perusahaan-op.edit')" @click="openEdit(item)" title="Edit" class="p-1.5 rounded-lg text-gray-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:text-amber-400 dark:hover:bg-amber-900/30 transition-colors"><i class="fas fa-edit"></i></button>
                    <button v-if="can('admin-role-perusahaan-op.delete')" @click="openDelete(item)" title="Hapus" class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:text-red-400 dark:hover:bg-red-900/30 transition-colors"><i class="fas fa-trash-alt"></i></button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="flex flex-col sm:flex-row items-center justify-between px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 gap-3">
          <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
            <span>Tampilkan</span>
            <select :value="perPage" @change="changePerPage(Number($event.target.value))" class="min-w-[65px] px-2 py-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none">
              <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }}</option>
            </select>
            <span>dari {{ pagination.total }} data</span>
          </div>
          <div class="flex items-center gap-1">
            <button @click="goToPage(1)" :disabled="pagination.current <= 1" class="px-2 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-30 transition-colors"><i class="fas fa-angle-double-left"></i></button>
            <button @click="goToPage(pagination.current-1)" :disabled="pagination.current <= 1" class="px-2 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-30 transition-colors"><i class="fas fa-angle-left"></i></button>
            <span class="px-2 text-sm text-gray-500">{{ pagination.current }} / {{ pagination.last }}</span>
            <button @click="goToPage(pagination.current+1)" :disabled="pagination.current >= pagination.last" class="px-2 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-30 transition-colors"><i class="fas fa-angle-right"></i></button>
            <button @click="goToPage(pagination.last)" :disabled="pagination.current >= pagination.last" class="px-2 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-30 transition-colors"><i class="fas fa-angle-double-right"></i></button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Detail -->
    <Teleport to="body"><Transition name="modal">
      <div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showDetailModal = false">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col">
          <div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-eye mr-2 text-sky-500"></i>Detail Mapping</h3>
            <button @click="showDetailModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button>
          </div>
          <div class="overflow-y-auto flex-1 px-6 py-5 space-y-3 modal-scroll" v-if="selectedItem">
            <div class="flex items-center gap-3 pb-3 border-b border-gray-100 dark:border-gray-700">
              <div class="w-12 h-12 rounded-full bg-gradient-to-br from-sky-500 to-blue-600 flex items-center justify-center text-white text-base font-bold shrink-0">{{ (selectedItem.admin_nama || '?')[0] }}</div>
              <div class="min-w-0 flex-1">
                <div class="font-semibold text-gray-900 dark:text-white">{{ selectedItem.admin_nama }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ selectedItem.admin_email || '—' }}</div>
              </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Role</label>
                <p class="mt-0.5">
                  <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                    <i class="fas fa-tag text-[10px]"></i> {{ selectedItem.role_nama }}
                  </span>
                </p>
              </div>
              <div>
                <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Status Admin</label>
                <p class="mt-0.5 text-sm text-gray-900 dark:text-white">{{ selectedItem.admin_status }}</p>
              </div>
              <div>
                <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Tgl Ditugaskan</label>
                <p class="text-sm text-gray-900 dark:text-white mt-0.5">{{ selectedItem.created_at }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition></Teleport>

    <!-- Modal Tambah / Edit -->
    <Teleport to="body"><Transition name="modal">
      <div v-if="showCreateModal || showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showCreateModal = showEditModal = false">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md sm:max-w-lg max-h-[90vh] flex flex-col">
          <div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              <i :class="['fas mr-2', showCreateModal ? 'fa-plus text-emerald-500' : 'fa-edit text-amber-500']"></i>
              {{ showCreateModal ? 'Tambah Mapping Admin-Role' : 'Edit Mapping Admin-Role' }}
            </h3>
            <button type="button" @click="showCreateModal = showEditModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button>
          </div>
          <form class="flex-1 overflow-y-auto px-6 py-5 space-y-4 modal-scroll" @submit.prevent="showCreateModal ? submitCreate() : submitEdit()">
            <div v-if="showCreateModal">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Admin <span class="text-red-500">*</span></label>
              <SearchableSelect v-model="createForm.admin_id" :options="adminOptions" placeholder="— Pilih Admin —" />
              <p v-if="createForm.errors.admin_id" class="text-red-500 text-xs mt-1">{{ createForm.errors.admin_id }}</p>
              <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1"><i class="fas fa-info-circle mr-1"></i>Hanya menampilkan admin aktif di perusahaan ini.</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Role <span class="text-red-500">*</span></label>
              <SearchableSelect v-model="(showCreateModal ? createForm : editForm).role_id" :options="roleOptions" placeholder="— Pilih Role —" />
              <p v-if="(showCreateModal ? createForm : editForm).errors.role_id" class="text-red-500 text-xs mt-1">{{ (showCreateModal ? createForm : editForm).errors.role_id }}</p>
            </div>
            <div v-if="!showCreateModal" class="bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-800 rounded-lg p-3 text-xs text-sky-700 dark:text-sky-300">
              <i class="fas fa-info-circle mr-1.5"></i>Mengubah role akan menimpa penugasan role sebelumnya untuk admin ini.
            </div>
            <div class="shrink-0 flex justify-end gap-2 pt-2 border-t border-gray-200 dark:border-gray-700 -mx-6 -mb-5 px-6 py-4 bg-white dark:bg-gray-800">
              <button type="button" @click="showCreateModal = showEditModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button>
              <button type="submit" :disabled="createForm.processing || editForm.processing" class="px-6 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm disabled:opacity-50">
                <i class="fas fa-save mr-1.5"></i>{{ showCreateModal ? 'Simpan' : 'Update' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition></Teleport>

    <!-- Modal Hapus -->
    <Teleport to="body"><Transition name="modal">
      <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showDeleteModal = false">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
          <div class="px-6 py-5 text-center">
            <div class="w-14 h-14 mx-auto rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-4"><i class="fas fa-exclamation-triangle text-red-500 text-xl"></i></div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Hapus Mapping?</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Anda akan menghapus mapping <strong class="text-gray-700 dark:text-gray-200">{{ selectedItem?.admin_nama }}</strong> dari role <strong class="text-gray-700 dark:text-gray-200">{{ selectedItem?.role_nama }}</strong>.</p>
          </div>
          <div class="flex justify-center gap-3 px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
            <button @click="showDeleteModal = false" class="px-5 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button>
            <button @click="confirmDelete" class="px-5 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors"><i class="fas fa-trash-alt mr-1.5"></i> Hapus</button>
          </div>
        </div>
      </div>
    </Transition></Teleport>

    <!-- Modal Import -->
    <Teleport to="body"><Transition name="modal">
      <div v-if="showImportModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showImportModal = false">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
        <form @submit.prevent="submitImport" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] flex flex-col">
          <div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-upload text-emerald-500 mr-2"></i>Import Mapping Admin-Role</h3>
            <button type="button" @click="showImportModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button>
          </div>
          <div class="overflow-y-auto flex-1 px-6 py-5 space-y-4 modal-scroll">
            <div class="bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-800 rounded-lg p-3 text-sm text-sky-700 dark:text-sky-300">
              <i class="fas fa-info-circle mr-1.5"></i>Upload file .xlsx atau .csv. <a @click="downloadTemplate" class="underline font-medium cursor-pointer">Download template</a> untuk format yang benar.
            </div>
            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-3 text-xs text-amber-700 dark:text-amber-300">
              <i class="fas fa-exclamation-triangle mr-1.5"></i>Kolom wajib: <strong>Email Admin</strong> + <strong>Role</strong>. Mapping yang sudah ada akan ditimpa (upsert).
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">File Excel <span class="text-red-500">*</span></label>
              <input type="file" @input="importForm.file = $event.target.files[0]" accept=".xlsx,.csv" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-sky-600 file:text-white file:text-xs file:hover:bg-sky-700" />
              <p v-if="importForm.errors.file" class="text-red-500 text-xs mt-1">{{ importForm.errors.file }}</p>
            </div>
          </div>
          <div class="shrink-0 flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <button type="button" @click="showImportModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button>
            <button type="submit" :disabled="importForm.processing" class="px-6 py-2.5 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors shadow-sm disabled:opacity-50">
              <i class="fas fa-upload mr-1.5"></i>Import
            </button>
          </div>
        </form>
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
.dark .modal-scroll::-webkit-scrollbar-thumb:hover { background: #4b5565; }
</style>
