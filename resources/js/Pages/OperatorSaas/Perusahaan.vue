<script setup>
import { ref, computed } from 'vue';
import OperatorSaasLayout from '@/Layouts/OperatorSaasLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineOptions({ layout: OperatorSaasLayout });

// ── Data ──
const perusahaans = ref([
  { id: 1, nama_perusahaan: 'PT Net Sejahtera', alamat: 'Jl. Merdeka No. 10, Jakarta Selatan', email: 'info@netsejahtera.id', status: 'Aktif', kode_negara: '+62', no_telp: '81234567890', created_at: '2025-01-10' },
  { id: 2, nama_perusahaan: 'CV Digital Media', alamat: 'Jl. Sudirman No. 25, Bandung', email: 'admin@digitalmedia.id', status: 'Aktif', kode_negara: '+62', no_telp: '81298765432', created_at: '2025-02-15' },
  { id: 3, nama_perusahaan: 'UD Net Mandiri', alamat: 'Jl. Ahmad Yani No. 5, Surabaya', email: 'support@netmandiri.id', status: 'Nonaktif', kode_negara: '+62', no_telp: '85611223344', created_at: '2025-03-01' },
  { id: 4, nama_perusahaan: 'PT Teknologi Nusantara', alamat: 'Jl. Gatot Subroto No. 88, Jakarta Pusat', email: 'hello@teknusantara.id', status: 'Aktif', kode_negara: '+62', no_telp: '87755669988', created_at: '2025-03-20' },
  { id: 5, nama_perusahaan: 'CV Media Connect', alamat: 'Jl. Diponegoro No. 42, Yogyakarta', email: 'info@mediaconnect.id', status: 'Aktif', kode_negara: '+62', no_telp: '82211110000', created_at: '2025-04-05' },
]);

// ── Search & Filter ──
const searchInput = ref('');
const search = ref('');
const statusFilter = ref('');

function applySearch() {
  search.value = searchInput.value;
  currentPage.value = 1;
}

function clearSearch() {
  searchInput.value = '';
  search.value = '';
  currentPage.value = 1;
}

function applyStatusFilter(status) {
  statusFilter.value = status;
  currentPage.value = 1;
}

// ── Bulk Action ──
const selectedIds = ref([]);
const selectAll = ref(false);

function toggleSelectAll() {
  if (selectAll.value) {
    selectedIds.value = paginatedPerusahaans.value.map(p => p.id);
  } else {
    selectedIds.value = [];
  }
}

function toggleSelect(id) {
  const idx = selectedIds.value.indexOf(id);
  if (idx === -1) {
    selectedIds.value.push(id);
  } else {
    selectedIds.value.splice(idx, 1);
  }
  selectAll.value = selectedIds.value.length === paginatedPerusahaans.value.length;
}

function bulkDelete() {
  perusahaans.value = perusahaans.value.filter(p => !selectedIds.value.includes(p.id));
  selectedIds.value = [];
  selectAll.value = false;
}

function bulkSetStatus(status) {
  perusahaans.value.forEach(p => {
    if (selectedIds.value.includes(p.id)) p.status = status;
  });
  selectedIds.value = [];
  selectAll.value = false;
}

// ── Multi Sort ──
const sortFields = ref([]);

function sort(field) {
  const existing = sortFields.value.findIndex(s => s.field === field);
  if (existing !== -1) {
    if (sortFields.value[existing].dir === 'asc') {
      sortFields.value[existing].dir = 'desc';
    } else {
      sortFields.value.splice(existing, 1);
    }
  } else {
    sortFields.value.push({ field, dir: 'asc' });
  }
}

function sortIcon(field) {
  const s = sortFields.value.find(s => s.field === field);
  if (!s) return 'fa-sort';
  return s.dir === 'asc' ? 'fa-sort-up' : 'fa-sort-down';
}

function sortOrder(field) {
  const idx = sortFields.value.findIndex(s => s.field === field);
  return idx !== -1 ? idx + 1 : null;
}

// ── Pagination ──
const currentPage = ref(1);
const perPage = ref(5);
const perPageOptions = [5, 10, 25, 50, 100];

function changePerPage(n) {
  perPage.value = n;
  currentPage.value = 1;
}

// ── Computed ──
const filteredPerusahaans = computed(() => {
  let result = [...perusahaans.value];

  if (search.value) {
    const q = search.value.toLowerCase();
    result = result.filter(p =>
      p.nama_perusahaan.toLowerCase().includes(q) ||
      p.email.toLowerCase().includes(q) ||
      p.alamat.toLowerCase().includes(q)
    );
  }

  if (statusFilter.value) {
    result = result.filter(p => p.status === statusFilter.value);
  }

  if (sortFields.value.length > 0) {
    result.sort((a, b) => {
      for (const s of sortFields.value) {
        const aVal = a[s.field];
        const bVal = b[s.field];
        let cmp = 0;
        if (typeof aVal === 'string') {
          cmp = aVal.localeCompare(bVal);
        } else {
          cmp = aVal - bVal;
        }
        if (cmp !== 0) return s.dir === 'asc' ? cmp : -cmp;
      }
      return 0;
    });
  }

  return result;
});

const totalPages = computed(() => Math.ceil(filteredPerusahaans.value.length / perPage.value) || 1);

const paginatedPerusahaans = computed(() => {
  const start = (currentPage.value - 1) * perPage.value;
  return filteredPerusahaans.value.slice(start, start + perPage.value);
});

const visiblePages = computed(() => {
  const pages = [];
  const total = totalPages.value;
  const current = currentPage.value;
  let start = Math.max(1, current - 2);
  let end = Math.min(total, current + 2);
  if (end - start < 4) {
    if (start === 1) end = Math.min(total, start + 4);
    else start = Math.max(1, end - 4);
  }
  for (let i = start; i <= end; i++) pages.push(i);
  return pages;
});

// ── CRUD State ──
const selectedPerusahaan = ref(null);
const showCreateModal = ref(false);
const showDetailModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const form = ref({ id: null, nama_perusahaan: '', alamat: '', email: '', kode_negara: '+62', no_telp: '', status: 'Aktif' });
const formErrors = ref({});
const nextId = ref(6);

const kodeNegaraList = ['+62', '+60', '+65', '+66', '+84', '+1', '+44', '+81', '+86'];

// ── Helpers ──
function statusBadge(status) {
  return status === 'Aktif'
    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
    : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
}

function formatTelepon(p) {
  return p.kode_negara + ' ' + p.no_telp;
}

// ── CRUD Actions ──
function openCreate() {
  form.value = { id: null, nama_perusahaan: '', alamat: '', email: '', kode_negara: '+62', no_telp: '', status: 'Aktif' };
  formErrors.value = {};
  showCreateModal.value = true;
}

function openDetail(p) {
  selectedPerusahaan.value = p;
  showDetailModal.value = true;
}

function openEdit(p) {
  form.value = { ...p };
  formErrors.value = {};
  showEditModal.value = true;
}

function openDelete(p) {
  selectedPerusahaan.value = p;
  showDeleteModal.value = true;
}

function validateForm() {
  const errors = {};
  if (!form.value.nama_perusahaan.trim()) errors.nama_perusahaan = 'Nama perusahaan wajib diisi';
  if (!form.value.email.trim()) errors.email = 'Email wajib diisi';
  else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)) errors.email = 'Format email tidak valid';
  if (!form.value.alamat.trim()) errors.alamat = 'Alamat wajib diisi';
  if (!form.value.no_telp.trim()) errors.no_telp = 'No. telepon wajib diisi';
  formErrors.value = errors;
  return Object.keys(errors).length === 0;
}

function saveCreate() {
  if (!validateForm()) return;
  perusahaans.value.unshift({
    id: nextId.value++,
    ...form.value,
    created_at: new Date().toISOString().split('T')[0],
  });
  showCreateModal.value = false;
}

function saveEdit() {
  if (!validateForm()) return;
  const idx = perusahaans.value.findIndex(p => p.id === form.value.id);
  if (idx !== -1) {
    perusahaans.value[idx] = { ...perusahaans.value[idx], ...form.value };
  }
  showEditModal.value = false;
}

function confirmDelete() {
  perusahaans.value = perusahaans.value.filter(p => p.id !== selectedPerusahaan.value.id);
  showDeleteModal.value = false;
  if (paginatedPerusahaans.value.length === 0 && currentPage.value > 1) {
    currentPage.value--;
  }
}

function goToPage(page) {
  currentPage.value = Math.max(1, Math.min(page, totalPages.value));
}
</script>

<template>
  <div>
    <Head title="Perusahaan | Operator SaaS" />

    <div class="space-y-6">
      <!-- Breadcrumbs -->
      <nav class="flex items-center gap-1.5 text-sm">
        <Link href="/operator-saas/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
          <i class="fas fa-home"></i>
        </Link>
        <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
        <Link href="/operator-saas/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
          Dashboard
        </Link>
        <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
        <span class="text-gray-900 dark:text-white font-medium">Perusahaan</span>
      </nav>

      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Perusahaan</h2>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Kelola data perusahaan tenant RT/RW Net.</p>
        </div>
        <button
          @click="openCreate"
          class="inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm"
        >
          <i class="fas fa-plus mr-1.5"></i> Tambah Perusahaan
        </button>
      </div>

      <!-- Filters Bar -->
      <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
          <div class="relative w-full sm:w-72">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <i class="fas fa-search text-gray-400 text-sm"></i>
            </div>
            <input
              v-model="searchInput"
              type="text"
              placeholder="Cari perusahaan..."
              class="w-full pl-10 pr-16 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors"
              @keydown.enter="applySearch"
            />
            <div class="absolute inset-y-0 right-0 flex items-center gap-1 pr-1.5">
              <button
                v-if="searchInput"
                @click="clearSearch"
                class="p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-white transition-colors"
                title="Clear"
              >
                <i class="fas fa-times text-xs"></i>
              </button>
              <button
                @click="applySearch"
                class="px-2 py-1 rounded bg-indigo-600 text-white hover:bg-indigo-700 transition-colors"
                title="Cari"
              >
                <i class="fas fa-search text-xs"></i>
              </button>
            </div>
          </div>

          <div class="flex gap-1 flex-wrap">
            <button
              v-for="s in [{v:'',l:'Semua'},{v:'Aktif',l:'Aktif'},{v:'Nonaktif',l:'Nonaktif'}]"
              :key="s.v"
              @click="applyStatusFilter(s.v)"
              :class="[
                'px-3 py-2 rounded-lg text-xs font-medium border transition-colors whitespace-nowrap',
                statusFilter === s.v
                  ? s.v === 'Aktif' ? 'bg-emerald-50 dark:bg-emerald-900/30 border-emerald-300 dark:border-emerald-700 text-emerald-700 dark:text-emerald-400'
                  : s.v === 'Nonaktif' ? 'bg-red-50 dark:bg-red-900/30 border-red-300 dark:border-red-700 text-red-700 dark:text-red-400'
                  : 'bg-indigo-50 dark:bg-indigo-900/30 border-indigo-300 dark:border-indigo-700 text-indigo-700 dark:text-indigo-400'
                  : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700'
              ]"
            >
              <i v-if="s.v === 'Aktif'" class="fas fa-check-circle mr-1"></i>
              <i v-else-if="s.v === 'Nonaktif'" class="fas fa-times-circle mr-1"></i>
              {{ s.l }}
            </button>
          </div>
        </div>

        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
          <span>{{ filteredPerusahaans.length }} data</span>
          <button
            v-if="search || statusFilter"
            @click="searchInput = ''; search = ''; applyStatusFilter('')"
            class="text-xs text-red-500 hover:text-red-700 dark:hover:text-red-400 underline"
          >
            Reset filter
          </button>
        </div>
      </div>

      <!-- Bulk Action Bar -->
      <div
        v-if="selectedIds.length > 0"
        class="flex items-center justify-between px-4 py-3 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-xl shadow-sm"
      >
        <span class="text-sm font-medium text-indigo-700 dark:text-indigo-300">
          <i class="fas fa-check-circle mr-1.5"></i> {{ selectedIds.length }} data dipilih
        </span>
        <div class="flex items-center gap-2">
          <button
            @click="bulkSetStatus('Aktif')"
            class="px-3 py-1.5 text-xs font-medium rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition-colors"
          >
            <i class="fas fa-check mr-1"></i> Aktifkan
          </button>
          <button
            @click="bulkSetStatus('Nonaktif')"
            class="px-3 py-1.5 text-xs font-medium rounded-lg bg-amber-600 text-white hover:bg-amber-700 transition-colors"
          >
            <i class="fas fa-ban mr-1"></i> Nonaktifkan
          </button>
          <button
            @click="bulkDelete()"
            class="px-3 py-1.5 text-xs font-medium rounded-lg bg-red-600 text-white hover:bg-red-700 transition-colors"
          >
            <i class="fas fa-trash-alt mr-1"></i> Hapus
          </button>
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm min-w-[700px]">
            <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
              <tr>
                <th class="px-4 py-3 w-10">
                  <input
                    v-model="selectAll"
                    type="checkbox"
                    @change="toggleSelectAll"
                    class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500"
                  />
                </th>
                <th @click="sort('nama_perusahaan')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none hover:text-gray-900 dark:hover:text-white transition-colors">
                  <span class="inline-flex items-center gap-1">
                    Nama Perusahaan
                    <span v-if="sortOrder('nama_perusahaan')" class="inline-flex items-center gap-0.5 text-indigo-500 dark:text-indigo-400">
                      <i :class="['fas', sortIcon('nama_perusahaan'), 'text-[10px]']"></i>
                      <span class="text-[10px] font-bold leading-none">{{ sortOrder('nama_perusahaan') }}</span>
                    </span>
                    <i v-else class="fas fa-sort text-[10px] text-gray-400 dark:text-gray-500"></i>
                  </span>
                </th>
                <th @click="sort('alamat')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none hover:text-gray-900 dark:hover:text-white transition-colors hidden md:table-cell">
                  <span class="inline-flex items-center gap-1">
                    Alamat
                    <span v-if="sortOrder('alamat')" class="inline-flex items-center gap-0.5 text-indigo-500 dark:text-indigo-400">
                      <i :class="['fas', sortIcon('alamat'), 'text-[10px]']"></i>
                      <span class="text-[10px] font-bold leading-none">{{ sortOrder('alamat') }}</span>
                    </span>
                    <i v-else class="fas fa-sort text-[10px] text-gray-400 dark:text-gray-500"></i>
                  </span>
                </th>
                <th @click="sort('email')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none hover:text-gray-900 dark:hover:text-white transition-colors">
                  <span class="inline-flex items-center gap-1">
                    Email
                    <span v-if="sortOrder('email')" class="inline-flex items-center gap-0.5 text-indigo-500 dark:text-indigo-400">
                      <i :class="['fas', sortIcon('email'), 'text-[10px]']"></i>
                      <span class="text-[10px] font-bold leading-none">{{ sortOrder('email') }}</span>
                    </span>
                    <i v-else class="fas fa-sort text-[10px] text-gray-400 dark:text-gray-500"></i>
                  </span>
                </th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Telepon</th>
                <th @click="sort('status')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none hover:text-gray-900 dark:hover:text-white transition-colors">
                  <span class="inline-flex items-center gap-1">
                    Status
                    <span v-if="sortOrder('status')" class="inline-flex items-center gap-0.5 text-indigo-500 dark:text-indigo-400">
                      <i :class="['fas', sortIcon('status'), 'text-[10px]']"></i>
                      <span class="text-[10px] font-bold leading-none">{{ sortOrder('status') }}</span>
                    </span>
                    <i v-else class="fas fa-sort text-[10px] text-gray-400 dark:text-gray-500"></i>
                  </span>
                </th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-400 w-32">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-if="paginatedPerusahaans.length === 0">
                <td colspan="7" class="px-4 py-16 text-center text-gray-500 dark:text-gray-400">
                  <i class="fas fa-building text-3xl mb-2 block opacity-40"></i>
                  Tidak ada data perusahaan.
                </td>
              </tr>
              <tr
                v-for="p in paginatedPerusahaans"
                :key="p.id"
                class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
              >
                <td class="px-4 py-3">
                  <input
                    :checked="selectedIds.includes(p.id)"
                    type="checkbox"
                    @change="toggleSelect(p.id)"
                    class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500"
                  />
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-sky-500 to-indigo-600 flex items-center justify-center text-white text-xs font-bold shrink-0">
                      <i class="fas fa-building text-[10px]"></i>
                    </div>
                    <span class="font-medium text-gray-900 dark:text-white whitespace-nowrap">{{ p.nama_perusahaan }}</span>
                  </div>
                </td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 hidden md:table-cell max-w-48 truncate">{{ p.alamat }}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap">{{ p.email }}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap">{{ formatTelepon(p) }}</td>
                <td class="px-4 py-3 whitespace-nowrap">
                  <span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium', statusBadge(p.status)]">
                    {{ p.status }}
                  </span>
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                  <div class="flex items-center justify-end gap-1">
                    <button @click="openDetail(p)" class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors" title="Detail">
                      <i class="fas fa-eye text-sm"></i>
                    </button>
                    <button @click="openEdit(p)" class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-sky-600 dark:hover:text-sky-400 transition-colors" title="Edit">
                      <i class="fas fa-edit text-sm"></i>
                    </button>
                    <button @click="openDelete(p)" class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-red-600 dark:hover:text-red-400 transition-colors" title="Hapus">
                      <i class="fas fa-trash-alt text-sm"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-3">
          <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
            <span>Tampilkan</span>
            <select
              :value="perPage"
              @change="changePerPage(Number($event.target.value))"
              class="min-w-[65px] px-2 py-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
            >
              <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }}</option>
            </select>
            <span>dari {{ filteredPerusahaans.length }} data</span>
          </div>

          <div class="flex items-center gap-1 flex-wrap justify-center sm:justify-start">
            <button @click="goToPage(1)" :disabled="currentPage === 1" class="px-2.5 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-30 disabled:cursor-not-allowed transition-colors">
              <i class="fas fa-chevron-double-left text-xs"></i>
            </button>
            <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1" class="px-2.5 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-30 disabled:cursor-not-allowed transition-colors">
              <i class="fas fa-chevron-left text-xs"></i>
            </button>
            <button
              v-for="page in visiblePages" :key="page" @click="goToPage(page)"
              :class="['px-3 py-1.5 rounded-lg text-sm font-medium transition-colors', page === currentPage ? 'bg-indigo-600 text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700']"
            >
              {{ page }}
            </button>
            <button @click="goToPage(currentPage + 1)" :disabled="currentPage === totalPages" class="px-2.5 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-30 disabled:cursor-not-allowed transition-colors">
              <i class="fas fa-chevron-right text-xs"></i>
            </button>
            <button @click="goToPage(totalPages)" :disabled="currentPage === totalPages" class="px-2.5 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-30 disabled:cursor-not-allowed transition-colors">
              <i class="fas fa-chevron-double-right text-xs"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══════════ DETAIL MODAL ═══════════ -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showDetailModal = false">
          <div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
          <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                <i class="fas fa-eye mr-2 text-indigo-500"></i>Detail Perusahaan
              </h3>
              <button @click="showDetailModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                <i class="fas fa-times"></i>
              </button>
            </div>
            <div class="px-6 py-5 space-y-4">
              <div class="flex items-center gap-4 pb-4 border-b border-gray-100 dark:border-gray-700">
                <div class="w-16 h-16 rounded-lg bg-gradient-to-br from-sky-500 to-indigo-600 flex items-center justify-center text-white text-xl shrink-0">
                  <i class="fas fa-building"></i>
                </div>
                <div>
                  <h4 class="text-xl font-bold text-gray-900 dark:text-white">{{ selectedPerusahaan?.nama_perusahaan }}</h4>
                  <span :class="['inline-flex mt-1 px-2.5 py-0.5 rounded-full text-xs font-medium', statusBadge(selectedPerusahaan?.status)]">
                    {{ selectedPerusahaan?.status }}
                  </span>
                </div>
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Email</label>
                  <p class="text-sm text-gray-900 dark:text-white mt-1">{{ selectedPerusahaan?.email }}</p>
                </div>
                <div>
                  <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kode Negara</label>
                  <p class="text-sm text-gray-900 dark:text-white mt-1">{{ selectedPerusahaan?.kode_negara }}</p>
                </div>
                <div>
                  <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">No. Telepon</label>
                  <p class="text-sm text-gray-900 dark:text-white mt-1">{{ selectedPerusahaan?.no_telp }}</p>
                </div>
                <div>
                  <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tanggal Dibuat</label>
                  <p class="text-sm text-gray-900 dark:text-white mt-1">{{ selectedPerusahaan?.created_at }}</p>
                </div>
              </div>
              <div>
                <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Alamat</label>
                <p class="text-sm text-gray-900 dark:text-white mt-1">{{ selectedPerusahaan?.alamat }}</p>
              </div>
            </div>
            <div class="flex justify-end gap-2 px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
              <button @click="showDetailModal = false; openEdit(selectedPerusahaan)" class="px-4 py-2 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors">
                <i class="fas fa-edit mr-1.5"></i> Edit
              </button>
              <button @click="showDetailModal = false" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                Tutup
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ═══════════ CREATE / EDIT MODAL ═══════════ -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showCreateModal || showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showCreateModal = false; showEditModal = false">
          <div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
          <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 z-10 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-t-2xl">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                <i :class="['fas mr-2', showCreateModal ? 'fa-plus text-emerald-500' : 'fa-edit text-sky-500']"></i>
                {{ showCreateModal ? 'Tambah Perusahaan' : 'Edit Perusahaan' }}
              </h3>
              <button @click="showCreateModal = false; showEditModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                <i class="fas fa-times"></i>
              </button>
            </div>
            <form class="px-6 py-5 space-y-4" @submit.prevent="showCreateModal ? saveCreate() : saveEdit()">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nama Perusahaan <span class="text-red-500">*</span></label>
                <input
                  v-model="form.nama_perusahaan"
                  type="text"
                  placeholder="PT/CV/UD Nama Perusahaan"
                  :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', formErrors.nama_perusahaan ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500']"
                />
                <p v-if="formErrors.nama_perusahaan" class="text-red-500 text-xs mt-1">{{ formErrors.nama_perusahaan }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email <span class="text-red-500">*</span></label>
                <input
                  v-model="form.email"
                  type="email"
                  placeholder="email@perusahaan.id"
                  :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', formErrors.email ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500']"
                />
                <p v-if="formErrors.email" class="text-red-500 text-xs mt-1">{{ formErrors.email }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Alamat <span class="text-red-500">*</span></label>
                <textarea
                  v-model="form.alamat"
                  rows="2"
                  placeholder="Alamat lengkap perusahaan"
                  :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white resize-none', formErrors.alamat ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500']"
                ></textarea>
                <p v-if="formErrors.alamat" class="text-red-500 text-xs mt-1">{{ formErrors.alamat }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Telepon <span class="text-red-500">*</span></label>
                <div class="flex gap-2">
                  <select v-model="form.kode_negara" class="w-24 px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                    <option v-for="kode in kodeNegaraList" :key="kode" :value="kode">{{ kode }}</option>
                  </select>
                  <input
                    v-model="form.no_telp"
                    type="text"
                    placeholder="81234567890"
                    :class="['flex-1 px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', formErrors.no_telp ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500']"
                  />
                </div>
                <p v-if="formErrors.no_telp" class="text-red-500 text-xs mt-1">{{ formErrors.no_telp }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Status</label>
                <select v-model="form.status" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                  <option value="Aktif">Aktif</option>
                  <option value="Nonaktif">Nonaktif</option>
                </select>
              </div>
              <div class="flex justify-end gap-2 pt-2">
                <button type="button" @click="showCreateModal = false; showEditModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                  Batal
                </button>
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
                  <i :class="['fas mr-1.5', showCreateModal ? 'fa-save' : 'fa-check']"></i>
                  {{ showCreateModal ? 'Simpan' : 'Update' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ═══════════ DELETE CONFIRMATION MODAL ═══════════ -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showDeleteModal = false">
          <div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
          <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
            <div class="px-6 py-5 text-center">
              <div class="w-14 h-14 mx-auto rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-4">
                <i class="fas fa-exclamation-triangle text-red-500 text-xl"></i>
              </div>
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Hapus Perusahaan?</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                Anda akan menghapus <strong class="text-gray-700 dark:text-gray-300">{{ selectedPerusahaan?.nama_perusahaan }}</strong>. Data yang dihapus tidak dapat dikembalikan.
              </p>
            </div>
            <div class="flex justify-center gap-3 px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
              <button @click="showDeleteModal = false" class="px-5 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                Batal
              </button>
              <button @click="confirmDelete" class="px-5 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                <i class="fas fa-trash-alt mr-1.5"></i> Hapus
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}
.modal-enter-active > div:last-child,
.modal-leave-active > div:last-child {
  transition: transform 0.2s ease, opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
.modal-enter-from > div:last-child {
  transform: scale(0.95) translateY(10px);
  opacity: 0;
}
.modal-leave-to > div:last-child {
  transform: scale(0.95) translateY(10px);
  opacity: 0;
}
</style>
