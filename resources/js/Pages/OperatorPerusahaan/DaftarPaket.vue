<script setup>
import { ref, computed } from 'vue';
import OperatorPerusahaanLayout from '@/Layouts/OperatorPerusahaanLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineOptions({ layout: OperatorPerusahaanLayout });

const pakets = ref([
  { id: 1, nama_paket: 'Basic 10Mbps', kecepatan: 10, harga: 150000, fup_limit: 300, fup_speed: 3, deskripsi: 'Paket hemat untuk browsing dan streaming ringan', status: 'Aktif', created_at: '2025-01-15' },
  { id: 2, nama_paket: 'Silver 20Mbps', kecepatan: 20, harga: 250000, fup_limit: 500, fup_speed: 5, deskripsi: 'Cocok untuk keluarga kecil, streaming HD', status: 'Aktif', created_at: '2025-01-20' },
  { id: 3, nama_paket: 'Gold 50Mbps', kecepatan: 50, harga: 400000, fup_limit: 1000, fup_speed: 10, deskripsi: 'Streaming 4K, gaming, work from home', status: 'Aktif', created_at: '2025-02-01' },
  { id: 4, nama_paket: 'Platinum 100Mbps', kecepatan: 100, harga: 750000, fup_limit: 2000, fup_speed: 20, deskripsi: 'Kecepatan maksimal untuk segala kebutuhan, FUP 2TB', status: 'Aktif', created_at: '2025-02-15' },
  { id: 5, nama_paket: 'Trial 5Mbps', kecepatan: 5, harga: 0, fup_limit: 10, fup_speed: 1, deskripsi: 'Paket uji coba gratis 7 hari', status: 'Nonaktif', created_at: '2025-03-01' },
]);

const searchInput = ref('');
const search = ref('');
const statusFilter = ref('');
const currentPage = ref(1);
const perPage = ref(5);
const perPageOptions = [5, 10, 25, 50, 100];
const selectedIds = ref([]);
const selectAll = ref(false);
const sortFields = ref([]);
const selectedItem = ref(null);
const showCreateModal = ref(false);
const showDetailModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const form = ref({ id: null, nama_paket: '', kecepatan: '', harga: '', fup_limit: '', fup_speed: '', deskripsi: '', status: 'Aktif' });
const formErrors = ref({});
const nextId = ref(6);

function applySearch() { search.value = searchInput.value; currentPage.value = 1; }
function clearSearch() { searchInput.value = ''; search.value = ''; currentPage.value = 1; }
function applyStatusFilter(s) { statusFilter.value = s; currentPage.value = 1; }
function changePerPage(n) { perPage.value = n; currentPage.value = 1; }
function toggleSelectAll() { selectedIds.value = selectAll.value ? paginatedPakets.value.map(p => p.id) : []; }
function toggleSelect(id) { const i = selectedIds.value.indexOf(id); i === -1 ? selectedIds.value.push(id) : selectedIds.value.splice(i, 1); selectAll.value = selectedIds.value.length === paginatedPakets.value.length; }
function bulkDelete() { pakets.value = pakets.value.filter(p => !selectedIds.value.includes(p.id)); selectedIds.value = []; selectAll.value = false; }
function bulkSetStatus(s) { pakets.value.forEach(p => { if (selectedIds.value.includes(p.id)) p.status = s; }); selectedIds.value = []; selectAll.value = false; }
function sort(field) { const e = sortFields.value.findIndex(s => s.field === field); if (e !== -1) { if (sortFields.value[e].dir === 'asc') sortFields.value[e].dir = 'desc'; else sortFields.value.splice(e, 1); } else sortFields.value.push({ field, dir: 'asc' }); }
function sortIcon(f) { const s = sortFields.value.find(s => s.field === f); if (!s) return 'fa-sort'; return s.dir === 'asc' ? 'fa-sort-up' : 'fa-sort-down'; }
function sortOrder(f) { const i = sortFields.value.findIndex(s => s.field === f); return i !== -1 ? i + 1 : null; }
function statusBadge(s) { return s === 'Aktif' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'; }
function formatRupiah(n) { return 'Rp ' + Number(n).toLocaleString('id-ID'); }

const filteredPakets = computed(() => {
  let r = [...pakets.value];
  if (search.value) { const q = search.value.toLowerCase(); r = r.filter(x => x.nama_paket.toLowerCase().includes(q) || x.deskripsi?.toLowerCase().includes(q)); }
  if (statusFilter.value) r = r.filter(x => x.status === statusFilter.value);
  if (sortFields.value.length > 0) r.sort((a, b) => { for (const s of sortFields.value) { let av = a[s.field], bv = b[s.field]; if (s.field === 'harga' || s.field === 'kecepatan') { av = Number(av); bv = Number(bv); } if (av != bv) return (typeof av === 'string' ? av.localeCompare(bv) : av - bv) * (s.dir === 'asc' ? 1 : -1); } return 0; });
  return r;
});
const totalPages = computed(() => Math.ceil(filteredPakets.value.length / perPage.value) || 1);
const paginatedPakets = computed(() => { const s = (currentPage.value - 1) * perPage.value; return filteredPakets.value.slice(s, s + perPage.value); });
const visiblePages = computed(() => { const p = []; const t = totalPages.value; const c = currentPage.value; let st = Math.max(1, c - 2); let en = Math.min(t, c + 2); if (en - st < 4) { if (st === 1) en = Math.min(t, st + 4); else st = Math.max(1, en - 4); } for (let i = st; i <= en; i++) p.push(i); return p; });

function openCreate() { form.value = { id: null, nama_paket: '', kecepatan: '', harga: '', fup_limit: '', fup_speed: '', deskripsi: '', status: 'Aktif' }; formErrors.value = {}; showCreateModal.value = true; }
function openDetail(p) { selectedItem.value = p; showDetailModal.value = true; }
function openEdit(p) { form.value = { ...p }; formErrors.value = {}; showEditModal.value = true; }
function openDelete(p) { selectedItem.value = p; showDeleteModal.value = true; }
function validateForm() { const e = {}; if (!form.value.nama_paket.trim()) e.nama_paket = 'Nama paket wajib diisi'; if (!form.value.kecepatan || Number(form.value.kecepatan) <= 0) e.kecepatan = 'Kecepatan harus > 0'; if (form.value.harga === '' || Number(form.value.harga) < 0) e.harga = 'Harga tidak valid'; formErrors.value = e; return Object.keys(e).length === 0; }
function saveCreate() { if (!validateForm()) return; pakets.value.unshift({ id: nextId.value++, ...form.value, kecepatan: Number(form.value.kecepatan), harga: Number(form.value.harga), created_at: new Date().toISOString().split('T')[0] }); showCreateModal.value = false; }
function saveEdit() { if (!validateForm()) return; const i = pakets.value.findIndex(p => p.id === form.value.id); if (i !== -1) pakets.value[i] = { ...pakets.value[i], ...form.value, kecepatan: Number(form.value.kecepatan), harga: Number(form.value.harga) }; showEditModal.value = false; }
function confirmDelete() { pakets.value = pakets.value.filter(p => p.id !== selectedItem.value.id); showDeleteModal.value = false; if (paginatedPakets.value.length === 0 && currentPage.value > 1) currentPage.value--; }
function goToPage(p) { currentPage.value = Math.max(1, Math.min(p, totalPages.value)); }
</script>

<template>
  <div>
    <Head title="Daftar Paket | Perusahaan" />
    <div class="space-y-6">
      <nav class="flex items-center gap-1.5 text-sm">
        <Link href="/operator-perusahaan/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-sky-600 dark:hover:text-sky-400 transition-colors"><i class="fas fa-home"></i></Link>
        <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
        <Link href="/operator-perusahaan/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-sky-600 dark:hover:text-sky-400 transition-colors">Dashboard</Link>
        <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
        <span class="text-gray-900 dark:text-white font-medium">Daftar Paket</span>
      </nav>

      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div><h2 class="text-2xl font-bold text-gray-900 dark:text-white">Daftar Paket</h2><p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Kelola paket internet yang ditawarkan kepada pelanggan.</p></div>
        <button @click="openCreate" class="inline-flex items-center px-4 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm"><i class="fas fa-plus mr-1.5"></i> Tambah Paket</button>
      </div>

      <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
          <div class="relative w-full sm:w-72">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-search text-gray-400 text-sm"></i></div>
            <input v-model="searchInput" type="text" placeholder="Cari paket..." class="w-full pl-10 pr-16 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors" @keydown.enter="applySearch" />
            <div class="absolute inset-y-0 right-0 flex items-center gap-1 pr-1.5">
              <button v-if="searchInput" @click="clearSearch" class="p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-white transition-colors" title="Clear"><i class="fas fa-times text-xs"></i></button>
              <button @click="applySearch" class="px-2 py-1 rounded bg-sky-600 text-white hover:bg-sky-700 transition-colors" title="Cari"><i class="fas fa-search text-xs"></i></button>
            </div>
          </div>
          <div class="flex gap-1 flex-wrap">
            <button v-for="s in [{v:'',l:'Semua'},{v:'Aktif',l:'Aktif'},{v:'Nonaktif',l:'Nonaktif'}]" :key="s.v" @click="applyStatusFilter(s.v)" :class="['px-3 py-2 rounded-lg text-xs font-medium border transition-colors whitespace-nowrap', statusFilter === s.v ? (s.v === 'Aktif' ? 'bg-emerald-50 dark:bg-emerald-900/30 border-emerald-300 dark:border-emerald-700 text-emerald-700 dark:text-emerald-400' : s.v === 'Nonaktif' ? 'bg-red-50 dark:bg-red-900/30 border-red-300 dark:border-red-700 text-red-700 dark:text-red-400' : 'bg-sky-50 dark:bg-sky-900/30 border-sky-300 dark:border-sky-700 text-sky-700 dark:text-sky-400') : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700']">
              <i v-if="s.v === 'Aktif'" class="fas fa-check-circle mr-1"></i><i v-else-if="s.v === 'Nonaktif'" class="fas fa-times-circle mr-1"></i>{{ s.l }}
            </button>
          </div>
        </div>
        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400"><span>{{ filteredPakets.length }} data</span><button v-if="search || statusFilter" @click="searchInput = ''; search = ''; applyStatusFilter('')" class="text-xs text-red-500 hover:text-red-700 dark:hover:text-red-400 underline">Reset filter</button></div>
      </div>

      <div v-if="selectedIds.length > 0" class="flex items-center justify-between px-4 py-3 bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-800 rounded-xl shadow-sm">
        <span class="text-sm font-medium text-sky-700 dark:text-sky-300"><i class="fas fa-check-circle mr-1.5"></i> {{ selectedIds.length }} data dipilih</span>
        <div class="flex items-center gap-2">
          <button @click="bulkSetStatus('Aktif')" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition-colors"><i class="fas fa-check mr-1"></i> Aktifkan</button>
          <button @click="bulkSetStatus('Nonaktif')" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-amber-600 text-white hover:bg-amber-700 transition-colors"><i class="fas fa-ban mr-1"></i> Nonaktifkan</button>
          <button @click="bulkDelete()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-red-600 text-white hover:bg-red-700 transition-colors"><i class="fas fa-trash-alt mr-1"></i> Hapus</button>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm min-w-[800px]">
            <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
              <tr>
                <th class="px-4 py-3 w-10"><input v-model="selectAll" type="checkbox" @change="toggleSelectAll" class="rounded border-gray-300 dark:border-gray-600 text-sky-600 focus:ring-sky-500" /></th>
                <th @click="sort('nama_paket')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none hover:text-gray-900 dark:hover:text-white transition-colors"><span class="inline-flex items-center gap-1">Nama Paket <span v-if="sortOrder('nama_paket')" class="inline-flex items-center gap-0.5 text-sky-500 dark:text-sky-400"><i :class="['fas', sortIcon('nama_paket'), 'text-[10px]']"></i><span class="text-[10px] font-bold leading-none">{{ sortOrder('nama_paket') }}</span></span><i v-else class="fas fa-sort text-[10px] text-gray-400 dark:text-gray-500"></i></span></th>
                <th @click="sort('kecepatan')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none hover:text-gray-900 dark:hover:text-white transition-colors"><span class="inline-flex items-center gap-1">Kecepatan <span v-if="sortOrder('kecepatan')" class="inline-flex items-center gap-0.5 text-sky-500 dark:text-sky-400"><i :class="['fas', sortIcon('kecepatan'), 'text-[10px]']"></i><span class="text-[10px] font-bold leading-none">{{ sortOrder('kecepatan') }}</span></span><i v-else class="fas fa-sort text-[10px] text-gray-400 dark:text-gray-500"></i></span></th>
                <th @click="sort('harga')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none hover:text-gray-900 dark:hover:text-white transition-colors"><span class="inline-flex items-center gap-1">Harga <span v-if="sortOrder('harga')" class="inline-flex items-center gap-0.5 text-sky-500 dark:text-sky-400"><i :class="['fas', sortIcon('harga'), 'text-[10px]']"></i><span class="text-[10px] font-bold leading-none">{{ sortOrder('harga') }}</span></span><i v-else class="fas fa-sort text-[10px] text-gray-400 dark:text-gray-500"></i></span></th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 hidden md:table-cell">FUP</th>
                <th @click="sort('status')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none hover:text-gray-900 dark:hover:text-white transition-colors"><span class="inline-flex items-center gap-1">Status <span v-if="sortOrder('status')" class="inline-flex items-center gap-0.5 text-sky-500 dark:text-sky-400"><i :class="['fas', sortIcon('status'), 'text-[10px]']"></i><span class="text-[10px] font-bold leading-none">{{ sortOrder('status') }}</span></span><i v-else class="fas fa-sort text-[10px] text-gray-400 dark:text-gray-500"></i></span></th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-400 w-32">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-if="paginatedPakets.length === 0"><td colspan="7" class="px-4 py-16 text-center text-gray-500 dark:text-gray-400"><i class="fas fa-box text-3xl mb-2 block opacity-40"></i>Tidak ada data paket.</td></tr>
              <tr v-for="p in paginatedPakets" :key="p.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                <td class="px-4 py-3"><input :checked="selectedIds.includes(p.id)" type="checkbox" @change="toggleSelect(p.id)" class="rounded border-gray-300 dark:border-gray-600 text-sky-600 focus:ring-sky-500" /></td>
                <td class="px-4 py-3"><div class="flex items-center gap-2.5"><div class="w-8 h-8 rounded-lg bg-gradient-to-br from-sky-500 to-blue-600 flex items-center justify-center text-white text-xs font-bold shrink-0"><i class="fas fa-wifi text-[10px]"></i></div><span class="font-medium text-gray-900 dark:text-white whitespace-nowrap">{{ p.nama_paket }}</span></div></td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap font-medium">{{ p.kecepatan }} Mbps</td>
                <td class="px-4 py-3 text-gray-900 dark:text-white whitespace-nowrap font-medium">{{ formatRupiah(p.harga) }}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap hidden md:table-cell">
                  <span v-if="p.fup_limit" class="text-xs">{{ p.fup_limit }} GB <i class="fas fa-arrow-right text-[10px] mx-0.5 text-gray-400"></i> {{ p.fup_speed }} Mbps</span>
                  <span v-else class="text-xs text-gray-400">Unlimited</span>
                </td>
                <td class="px-4 py-3 whitespace-nowrap"><span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium', statusBadge(p.status)]">{{ p.status }}</span></td>
                <td class="px-4 py-3 whitespace-nowrap">
                  <div class="flex items-center justify-end gap-1">
                    <button @click="openDetail(p)" class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-sky-600 dark:hover:text-sky-400 transition-colors" title="Detail"><i class="fas fa-eye text-sm"></i></button>
                    <button @click="openEdit(p)" class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-sky-600 dark:hover:text-sky-400 transition-colors" title="Edit"><i class="fas fa-edit text-sm"></i></button>
                    <button @click="openDelete(p)" class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-red-600 dark:hover:text-red-400 transition-colors" title="Hapus"><i class="fas fa-trash-alt text-sm"></i></button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-3">
          <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400"><span>Tampilkan</span><select :value="perPage" @change="changePerPage(Number($event.target.value))" class="min-w-[65px] px-2 py-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none"><option v-for="n in perPageOptions" :key="n" :value="n">{{ n }}</option></select><span>dari {{ filteredPakets.length }} data</span></div>
          <div class="flex items-center gap-1 flex-wrap justify-center sm:justify-start">
            <button @click="goToPage(1)" :disabled="currentPage === 1" class="px-2.5 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"><i class="fas fa-chevron-double-left text-xs"></i></button>
            <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1" class="px-2.5 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"><i class="fas fa-chevron-left text-xs"></i></button>
            <button v-for="page in visiblePages" :key="page" @click="goToPage(page)" :class="['px-3 py-1.5 rounded-lg text-sm font-medium transition-colors', page === currentPage ? 'bg-sky-600 text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700']">{{ page }}</button>
            <button @click="goToPage(currentPage + 1)" :disabled="currentPage === totalPages" class="px-2.5 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"><i class="fas fa-chevron-right text-xs"></i></button>
            <button @click="goToPage(totalPages)" :disabled="currentPage === totalPages" class="px-2.5 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"><i class="fas fa-chevron-double-right text-xs"></i></button>
          </div>
        </div>
      </div>
    </div>

    <Teleport to="body"><Transition name="modal"><div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showDetailModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden"><div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-eye mr-2 text-sky-500"></i>Detail Paket</h3><button @click="showDetailModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button></div><div class="px-6 py-5 space-y-4"><div class="flex items-center gap-4 pb-4 border-b border-gray-100 dark:border-gray-700"><div class="w-16 h-16 rounded-lg bg-gradient-to-br from-sky-500 to-blue-600 flex items-center justify-center text-white text-xl shrink-0"><i class="fas fa-wifi"></i></div><div><h4 class="text-xl font-bold text-gray-900 dark:text-white">{{ selectedItem?.nama_paket }}</h4><span :class="['inline-flex mt-1 px-2.5 py-0.5 rounded-full text-xs font-medium', statusBadge(selectedItem?.status)]">{{ selectedItem?.status }}</span></div></div><div class="grid grid-cols-1 sm:grid-cols-2 gap-4"><div><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kecepatan</label><p class="text-sm text-gray-900 dark:text-white mt-1 font-medium">{{ selectedItem?.kecepatan }} Mbps</p></div><div><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Harga</label><p class="text-sm text-gray-900 dark:text-white mt-1 font-medium">{{ formatRupiah(selectedItem?.harga) }}</p></div><div><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">FUP</label><p class="text-sm text-gray-900 dark:text-white mt-1 font-medium"><span v-if="selectedItem?.fup_limit">{{ selectedItem?.fup_limit }} GB &rarr; {{ selectedItem?.fup_speed }} Mbps</span><span v-else class="text-gray-400">Unlimited</span></p></div><div><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tanggal Dibuat</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ selectedItem?.created_at }}</p></div></div><div><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Deskripsi</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ selectedItem?.deskripsi || '—' }}</p></div></div><div class="flex justify-end gap-2 px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700"><button @click="showDetailModal = false; openEdit(selectedItem)" class="px-4 py-2 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors"><i class="fas fa-edit mr-1.5"></i> Edit</button><button @click="showDetailModal = false" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Tutup</button></div></div></div></Transition></Teleport>

    <Teleport to="body"><Transition name="modal"><div v-if="showCreateModal || showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showCreateModal = false; showEditModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto"><div class="sticky top-0 z-10 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-t-2xl"><h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i :class="['fas mr-2', showCreateModal ? 'fa-plus text-emerald-500' : 'fa-edit text-sky-500']"></i>{{ showCreateModal ? 'Tambah Paket' : 'Edit Paket' }}</h3><button @click="showCreateModal = false; showEditModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button></div><form class="px-6 py-5 space-y-4" @submit.prevent="showCreateModal ? saveCreate() : saveEdit()">
      <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nama Paket <span class="text-red-500">*</span></label><input v-model="form.nama_paket" type="text" placeholder="Contoh: Gold 50Mbps" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', formErrors.nama_paket ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500 focus:border-sky-500']" /><p v-if="formErrors.nama_paket" class="text-red-500 text-xs mt-1">{{ formErrors.nama_paket }}</p></div>
      <div class="grid grid-cols-2 gap-4">
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Kecepatan (Mbps) <span class="text-red-500">*</span></label><input v-model="form.kecepatan" type="number" min="1" placeholder="50" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', formErrors.kecepatan ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500 focus:border-sky-500']" /><p v-if="formErrors.kecepatan" class="text-red-500 text-xs mt-1">{{ formErrors.kecepatan }}</p></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Harga (Rp) <span class="text-red-500">*</span></label><input v-model="form.harga" type="number" min="0" placeholder="400000" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', formErrors.harga ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500 focus:border-sky-500']" /><p v-if="formErrors.harga" class="text-red-500 text-xs mt-1">{{ formErrors.harga }}</p></div>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">FUP Limit (GB)</label><input v-model="form.fup_limit" type="number" min="0" placeholder="300" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors" /></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Kecepatan Setelah FUP (Mbps)</label><input v-model="form.fup_speed" type="number" min="0" placeholder="3" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors" /></div>
      </div>
      <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Deskripsi</label><textarea v-model="form.deskripsi" rows="2" placeholder="Deskripsi paket (opsional)" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors resize-none"></textarea></div>
      <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Status</label><select v-model="form.status" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors"><option value="Aktif">Aktif</option><option value="Nonaktif">Nonaktif</option></select></div>
      <div class="flex justify-end gap-2 pt-2"><button type="button" @click="showCreateModal = false; showEditModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button><button type="submit" class="px-6 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm"><i :class="['fas mr-1.5', showCreateModal ? 'fa-save' : 'fa-check']"></i>{{ showCreateModal ? 'Simpan' : 'Update' }}</button></div>
    </form></div></div></Transition></Teleport>

    <Teleport to="body"><Transition name="modal"><div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showDeleteModal = false"><div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div><div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden"><div class="px-6 py-5 text-center"><div class="w-14 h-14 mx-auto rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-4"><i class="fas fa-exclamation-triangle text-red-500 text-xl"></i></div><h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Hapus Paket?</h3><p class="text-sm text-gray-500 dark:text-gray-400">Anda akan menghapus <strong class="text-gray-700 dark:text-gray-300">{{ selectedItem?.nama_paket }}</strong>. Data yang dihapus tidak dapat dikembalikan.</p></div><div class="flex justify-center gap-3 px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700"><button @click="showDeleteModal = false" class="px-5 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button><button @click="confirmDelete" class="px-5 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors"><i class="fas fa-trash-alt mr-1.5"></i> Hapus</button></div></div></div></Transition></Teleport>
  </div>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-active > div:last-child, .modal-leave-active > div:last-child { transition: transform 0.2s ease, opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-from > div:last-child { transform: scale(0.95) translateY(10px); opacity: 0; }
.modal-leave-to > div:last-child { transform: scale(0.95) translateY(10px); opacity: 0; }
</style>
