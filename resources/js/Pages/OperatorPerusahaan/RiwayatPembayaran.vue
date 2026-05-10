<script setup>
import { ref, computed } from 'vue';
import OperatorPerusahaanLayout from '@/Layouts/OperatorPerusahaanLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import SearchableSelect from '@/Components/SearchableSelect.vue';

defineOptions({ layout: OperatorPerusahaanLayout });

const providers = [
  {
    slug: 'internal', name: 'Internal', methods: [
      { slug: 'tunai', name: 'Tunai', wajibInputBukti: false },
      { slug: 'bank_transfer', name: 'Bank Transfer', wajibInputBukti: true },
    ]
  },
  {
    slug: 'midtrans', name: 'Midtrans', methods: [
      { slug: 'gopay', name: 'Gopay', wajibInputBukti: false },
      { slug: 'gopay_dynamic_qris', name: 'Gopay Dynamic QRIS', wajibInputBukti: false },
      { slug: 'cimb_niaga_va', name: 'Bank CIMB Niaga VA', wajibInputBukti: false },
      { slug: 'bsi_va', name: 'Bank BSI VA', wajibInputBukti: false },
      { slug: 'bni_va', name: 'Bank BNI VA', wajibInputBukti: false },
      { slug: 'bri_va', name: 'Bank BRI VA', wajibInputBukti: false },
      { slug: 'permata_va', name: 'Bank Permata VA', wajibInputBukti: false },
      { slug: 'mandiri_va', name: 'Bank Mandiri VA', wajibInputBukti: false },
    ]
  }
];

const statuses = ['menunggu', 'lunas', 'dibatalkan', 'expired'];

const pembayarans = ref([
  { id: 1, kode_tagihan: 'INV-202505-001', pelanggan: 'Pak Sugeng', periode: 'Mei 2025', jumlah: 250000, provider: 'internal', metode: 'tunai', status: 'lunas', tgl_bayar: '2025-05-08', kolektor: 'Ahmad', bukti: null, created_at: '2025-05-08' },
  { id: 2, kode_tagihan: 'INV-202505-002', pelanggan: 'Bu Rini', periode: 'April 2025', jumlah: 400000, provider: 'midtrans', metode: 'bri_va', status: 'lunas', tgl_bayar: '2025-04-12', kolektor: 'Siti', bukti: null, created_at: '2025-04-12' },
  { id: 3, kode_tagihan: 'INV-202505-003', pelanggan: 'Mbak Dewi', periode: 'Mei 2025', jumlah: 750000, provider: 'midtrans', metode: 'gopay', status: 'menunggu', tgl_bayar: '2025-05-01', kolektor: 'Budi', bukti: null, created_at: '2025-05-01' },
  { id: 4, kode_tagihan: 'INV-202505-004', pelanggan: 'Pak Slamet', periode: 'April 2025', jumlah: 150000, provider: 'internal', metode: 'bank_transfer', status: 'dibatalkan', tgl_bayar: '2025-04-20', kolektor: 'Rudi', bukti: 'bukti_slamet.jpg', created_at: '2025-04-20' },
  { id: 5, kode_tagihan: 'INV-202504-001', pelanggan: 'Pak Herman', periode: 'Maret 2025', jumlah: 150000, provider: 'internal', metode: 'tunai', status: 'expired', tgl_bayar: '2025-03-25', kolektor: 'Ahmad', bukti: null, created_at: '2025-03-25' },
]);

const searchInput = ref('');
const search = ref('');
const providerFilter = ref('');
const metodeFilter = ref('');
const statusFilter = ref('');
const tglDari = ref('');
const tglSampai = ref('');
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

const form = ref({
  id: null, kode_tagihan: '', pelanggan: '', periode: '', jumlah: '',
  provider: 'internal', metode: '', status: 'menunggu', tgl_bayar: '', kolektor: '', bukti: null
});
const formErrors = ref({});
const nextId = ref(6);

const metodeOptions = computed(() => {
  const p = providers.find(x => x.slug === form.value.provider);
  return p ? p.methods : [];
});

const wajibInputBukti = computed(() => {
  const p = providers.find(x => x.slug === form.value.provider);
  if (!p) return false;
  const m = p.methods.find(x => x.slug === form.value.metode);
  return m ? m.wajibInputBukti : false;
});

const filteredMetodeOptions = computed(() => {
  return providers.reduce((acc, p) => {
    p.methods.forEach(m => acc.push({ providerSlug: p.slug, providerName: p.name, ...m }));
    return acc;
  }, []);
});

const providerOptions = computed(() => {
  return [{ value: '', label: 'Semua Provider' }, ...providers.map(p => ({ value: p.slug, label: p.name }))];
});

const metodeOptionsForFilter = computed(() => {
  const all = providers.reduce((acc, p) => {
    p.methods.forEach(m => acc.push({ value: m.slug, label: `${m.name} (${p.name})` }));
    return acc;
  }, []);
  if (!providerFilter.value) return [{ value: '', label: 'Semua Metode' }, ...all];
  const p = providers.find(x => x.slug === providerFilter.value);
  if (!p) return [{ value: '', label: 'Semua Metode' }, ...all];
  const filtered = p.methods.map(m => ({ value: m.slug, label: m.name }));
  return [{ value: '', label: 'Semua Metode' }, ...filtered];
});

function applySearch() { search.value = searchInput.value; currentPage.value = 1; }
function clearSearch() { searchInput.value = ''; search.value = ''; currentPage.value = 1; }
function applyProviderFilter(v) { providerFilter.value = v; metodeFilter.value = ''; currentPage.value = 1; }
function applyMetodeFilter(v) { metodeFilter.value = v; currentPage.value = 1; }
function applyStatusFilter(v) { statusFilter.value = v; currentPage.value = 1; }
function changePerPage(n) { perPage.value = n; currentPage.value = 1; }

function toggleSelectAll() { selectedIds.value = selectAll.value ? paginatedPembayarans.value.map(p => p.id) : []; }
function toggleSelect(id) { const i = selectedIds.value.indexOf(id); i === -1 ? selectedIds.value.push(id) : selectedIds.value.splice(i, 1); selectAll.value = selectedIds.value.length === paginatedPembayarans.value.length; }
function bulkDelete() { pembayarans.value = pembayarans.value.filter(p => !selectedIds.value.includes(p.id)); selectedIds.value = []; selectAll.value = false; }

function sort(field) { const e = sortFields.value.findIndex(s => s.field === field); if (e !== -1) { if (sortFields.value[e].dir === 'asc') sortFields.value[e].dir = 'desc'; else sortFields.value.splice(e, 1); } else sortFields.value.push({ field, dir: 'asc' }); }
function sortIcon(f) { const s = sortFields.value.find(s => s.field === f); if (!s) return 'fa-sort'; return s.dir === 'asc' ? 'fa-sort-up' : 'fa-sort-down'; }
function sortOrder(f) { const i = sortFields.value.findIndex(s => s.field === f); return i !== -1 ? i + 1 : null; }

const filteredPembayarans = computed(() => {
  let r = [...pembayarans.value];
  if (search.value) { const q = search.value.toLowerCase(); r = r.filter(x => x.kode_tagihan.toLowerCase().includes(q) || x.pelanggan.toLowerCase().includes(q) || x.periode.toLowerCase().includes(q) || x.kolektor.toLowerCase().includes(q) || metodeName(x.metode).toLowerCase().includes(q) || providerName(x.provider).toLowerCase().includes(q) || x.status.toLowerCase().includes(q)); }
  if (providerFilter.value) r = r.filter(x => x.provider === providerFilter.value);
  if (metodeFilter.value) r = r.filter(x => x.metode === metodeFilter.value);
  if (statusFilter.value) r = r.filter(x => x.status === statusFilter.value);
  if (tglDari.value) r = r.filter(x => x.tgl_bayar >= tglDari.value);
  if (tglSampai.value) r = r.filter(x => x.tgl_bayar <= tglSampai.value);
  if (sortFields.value.length > 0) r.sort((a, b) => { for (const s of sortFields.value) { let av = a[s.field], bv = b[s.field]; if (s.field === 'jumlah') { av = Number(av); bv = Number(bv); } if (av != bv) return (typeof av === 'string' ? av.localeCompare(bv) : av - bv) * (s.dir === 'asc' ? 1 : -1); } return 0; });
  return r;
});

const totalPages = computed(() => Math.ceil(filteredPembayarans.value.length / perPage.value) || 1);
const paginatedPembayarans = computed(() => { const s = (currentPage.value - 1) * perPage.value; return filteredPembayarans.value.slice(s, s + perPage.value); });
const visiblePages = computed(() => { const p = []; const t = totalPages.value; const c = currentPage.value; let st = Math.max(1, c - 2); let en = Math.min(t, c + 2); if (en - st < 4) { if (st === 1) en = Math.min(t, st + 4); else st = Math.max(1, en - 4); } for (let i = st; i <= en; i++) p.push(i); return p; });

function openCreate() { form.value = { id: null, kode_tagihan: '', pelanggan: '', periode: '', jumlah: '', provider: 'internal', metode: '', status: 'menunggu', tgl_bayar: '', kolektor: '', bukti: null }; formErrors.value = {}; showCreateModal.value = true; }
function openDetail(p) { selectedItem.value = p; showDetailModal.value = true; }
function openEdit(p) { form.value = { ...p }; formErrors.value = {}; showEditModal.value = true; }
function openDelete(p) { selectedItem.value = p; showDeleteModal.value = true; }

function onProviderChange() { form.value.metode = metodeOptions.value.length > 0 ? metodeOptions.value[0].slug : ''; }

function validateForm() { const e = {}; if (!form.value.kode_tagihan.trim()) e.kode_tagihan = 'Kode tagihan wajib diisi'; if (!form.value.pelanggan.trim()) e.pelanggan = 'Pelanggan wajib diisi'; if (!form.value.jumlah || Number(form.value.jumlah) <= 0) e.jumlah = 'Jumlah tidak valid'; if (!form.value.tgl_bayar) e.tgl_bayar = 'Tanggal bayar wajib diisi'; if (!form.value.metode) e.metode = 'Metode pembayaran wajib dipilih'; if (wajibInputBukti.value && !form.value.bukti) e.bukti = 'Bukti pembayaran wajib diupload'; formErrors.value = e; return Object.keys(e).length === 0; }

function saveCreate() { if (!validateForm()) return; pembayarans.value.unshift({ id: nextId.value++, ...form.value, jumlah: Number(form.value.jumlah), created_at: new Date().toISOString().split('T')[0] }); showCreateModal.value = false; }
function saveEdit() { if (!validateForm()) return; const i = pembayarans.value.findIndex(p => p.id === form.value.id); if (i !== -1) pembayarans.value[i] = { ...pembayarans.value[i], ...form.value, jumlah: Number(form.value.jumlah) }; showEditModal.value = false; }
function confirmDelete() { pembayarans.value = pembayarans.value.filter(p => p.id !== selectedItem.value.id); showDeleteModal.value = false; if (paginatedPembayarans.value.length === 0 && currentPage.value > 1) currentPage.value--; }
function goToPage(p) { currentPage.value = Math.max(1, Math.min(p, totalPages.value)); }

function providerName(slug) { const p = providers.find(x => x.slug === slug); return p ? p.name : slug; }
function metodeName(slug) { const all = filteredMetodeOptions.value; const m = all.find(x => x.slug === slug); return m ? m.name : slug; }
function formatRupiah(n) { return 'Rp ' + Number(n).toLocaleString('id-ID'); }

function statusBadge(s) {
  if (s === 'lunas') return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
  if (s === 'menunggu') return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
  if (s === 'dibatalkan') return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
  if (s === 'expired') return 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400';
  return 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400';
}
function statusIcon(s) {
  if (s === 'lunas') return 'fa-check-circle';
  if (s === 'menunggu') return 'fa-clock';
  if (s === 'dibatalkan') return 'fa-times-circle';
  if (s === 'expired') return 'fa-hourglass-end';
  return 'fa-circle';
}
function getFileIcon(file) {
  if (!file) return 'fa-file';
  const ext = file.split('.').pop().toLowerCase();
  if (['jpg','jpeg','png','gif','webp','svg'].includes(ext)) return 'fa-file-image';
  if (['pdf'].includes(ext)) return 'fa-file-pdf';
  return 'fa-file-alt';
}
</script>

<template>
  <div>
    <Head title="Riwayat Pembayaran | Perusahaan" />
    <div class="space-y-6">
      <!-- Breadcrumb & Header -->
      <nav class="flex items-center gap-1.5 text-sm">
        <Link href="/operator-perusahaan/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-sky-600 dark:hover:text-sky-400 transition-colors"><i class="fas fa-home"></i></Link>
        <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
        <Link href="/operator-perusahaan/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-sky-600 dark:hover:text-sky-400 transition-colors">Dashboard</Link>
        <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
        <span class="text-gray-900 dark:text-white font-medium">Riwayat Pembayaran</span>
      </nav>

      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Riwayat Pembayaran</h2>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Kelola riwayat pembayaran dari semua pelanggan.</p>
        </div>
        <button @click="openCreate" class="inline-flex items-center px-4 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm">
          <i class="fas fa-plus mr-1.5"></i> Tambah Pembayaran
        </button>
      </div>

      <!-- Filters -->
      <div class="flex flex-col gap-3">
        <!-- Row 1: Search + Date Range -->
        <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
          <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
            <div class="relative w-full sm:w-72">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fas fa-search text-gray-400 text-sm"></i>
              </div>
              <input v-model="searchInput" type="text" placeholder="Cari kode tagihan/pelanggan..." class="w-full pl-10 pr-16 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors" @keydown.enter="applySearch" />
              <div class="absolute inset-y-0 right-0 flex items-center gap-1 pr-1.5">
                <button v-if="searchInput" @click="clearSearch" class="p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-white transition-colors" title="Clear"><i class="fas fa-times text-xs"></i></button>
                <button @click="applySearch" class="px-2 py-1 rounded bg-sky-600 text-white hover:bg-sky-700 transition-colors" title="Cari"><i class="fas fa-search text-xs"></i></button>
              </div>
            </div>
          </div>
          <div class="flex items-center gap-2 w-full sm:w-auto">
            <input v-model="tglDari" type="date" class="w-full sm:w-40 px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors" />
            <span class="text-gray-400 text-xs">s/d</span>
            <input v-model="tglSampai" type="date" class="w-full sm:w-40 px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors" />
          </div>
        </div>

        <!-- Row 2: Provider + Metode + Status Selects -->
        <div class="flex flex-wrap gap-4 items-end">
          <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Provider</label>
            <SearchableSelect
              v-model="providerFilter"
              :options="providerOptions"
              placeholder="Semua Provider"
              @update:model-value="applyProviderFilter"
            />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Metode</label>
            <SearchableSelect
              v-model="metodeFilter"
              :options="metodeOptionsForFilter"
              placeholder="Semua Metode"
              @update:model-value="applyMetodeFilter"
            />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Status</label>
            <select v-model="statusFilter" @change="applyStatusFilter(statusFilter)" class="w-40 px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors capitalize">
              <option value="">Semua Status</option>
              <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Bulk Action -->
      <div v-if="selectedIds.length > 0" class="flex items-center justify-between px-4 py-3 bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-800 rounded-xl shadow-sm">
        <span class="text-sm font-medium text-sky-700 dark:text-sky-300"><i class="fas fa-check-circle mr-1.5"></i> {{ selectedIds.length }} data dipilih</span>
        <div class="flex items-center gap-2">
          <button @click="bulkDelete()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-red-600 text-white hover:bg-red-700 transition-colors"><i class="fas fa-trash-alt mr-1"></i> Hapus</button>
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm min-w-[1050px]">
            <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
              <tr>
                <th class="px-4 py-3 w-10">
                  <input v-model="selectAll" type="checkbox" @change="toggleSelectAll" class="rounded border-gray-300 dark:border-gray-600 text-sky-600 focus:ring-sky-500" />
                </th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Kode Tagihan</th>
                <th @click="sort('pelanggan')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none hover:text-gray-900 dark:hover:text-white transition-colors">
                  <span class="inline-flex items-center gap-1">
                    Pelanggan
                    <span v-if="sortOrder('pelanggan')" class="inline-flex items-center gap-0.5 text-sky-500 dark:text-sky-400">
                      <i :class="['fas', sortIcon('pelanggan'), 'text-[10px]']"></i>
                      <span class="text-[10px] font-bold leading-none">{{ sortOrder('pelanggan') }}</span>
                    </span>
                    <i v-else class="fas fa-sort text-[10px] text-gray-400 dark:text-gray-500"></i>
                  </span>
                </th>
                <th @click="sort('jumlah')" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none hover:text-gray-900 dark:hover:text-white transition-colors">
                  <span class="inline-flex items-center gap-1">
                    Jumlah
                    <span v-if="sortOrder('jumlah')" class="inline-flex items-center gap-0.5 text-sky-500 dark:text-sky-400">
                      <i :class="['fas', sortIcon('jumlah'), 'text-[10px]']"></i>
                      <span class="text-[10px] font-bold leading-none">{{ sortOrder('jumlah') }}</span>
                    </span>
                    <i v-else class="fas fa-sort text-[10px] text-gray-400 dark:text-gray-500"></i>
                  </span>
                </th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Provider</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Metode</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Status</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Bukti</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-400 w-32">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-if="paginatedPembayarans.length === 0">
                <td colspan="10" class="px-4 py-16 text-center text-gray-500 dark:text-gray-400">
                  <i class="fas fa-history text-3xl mb-2 block opacity-40"></i>
                  Tidak ada riwayat pembayaran.
                </td>
              </tr>
              <tr v-for="p in paginatedPembayarans" :key="p.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                <td class="px-4 py-3">
                  <input :checked="selectedIds.includes(p.id)" type="checkbox" @change="toggleSelect(p.id)" class="rounded border-gray-300 dark:border-gray-600 text-sky-600 focus:ring-sky-500" />
                </td>
                <td class="px-4 py-3">
                  <code class="text-sm font-mono text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">{{ p.kode_tagihan }}</code>
                </td>
                <td class="px-4 py-3">
                  <span class="font-medium text-gray-900 dark:text-white whitespace-nowrap">{{ p.pelanggan }}</span>
                </td>
                <td class="px-4 py-3 text-gray-900 dark:text-white whitespace-nowrap font-medium">{{ formatRupiah(p.jumlah) }}</td>
                <td class="px-4 py-3 whitespace-nowrap text-gray-700 dark:text-gray-300">{{ providerName(p.provider) }}</td>
                <td class="px-4 py-3 whitespace-nowrap text-gray-700 dark:text-gray-300">{{ metodeName(p.metode) }}</td>
                <td class="px-4 py-3 whitespace-nowrap">
                  <span :class="['inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium capitalize', statusBadge(p.status)]">
                    <i :class="['fas', statusIcon(p.status), 'text-[10px]']"></i>
                    {{ p.status }}
                  </span>
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                  <span v-if="p.bukti" class="inline-flex items-center gap-1 text-xs text-sky-600 dark:text-sky-400">
                    <i :class="['fas', getFileIcon(p.bukti)]"></i>
                    {{ p.bukti }}
                  </span>
                  <span v-else class="text-xs text-gray-400">-</span>
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                  <div class="flex items-center justify-end gap-1">
                    <button @click="openDetail(p)" class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-sky-600 dark:hover:text-sky-400 transition-colors" title="Detail">
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
            <select :value="perPage" @change="changePerPage(Number($event.target.value))" class="min-w-[65px] px-2 py-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none">
              <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }}</option>
            </select>
            <span>dari {{ filteredPembayarans.length }} data</span>
          </div>
          <div class="flex items-center gap-1 flex-wrap justify-center sm:justify-start">
            <button @click="goToPage(1)" :disabled="currentPage === 1" class="px-2.5 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-30 disabled:cursor-not-allowed transition-colors">
              <i class="fas fa-chevron-double-left text-xs"></i>
            </button>
            <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1" class="px-2.5 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-30 disabled:cursor-not-allowed transition-colors">
              <i class="fas fa-chevron-left text-xs"></i>
            </button>
            <button v-for="page in visiblePages" :key="page" @click="goToPage(page)" :class="['px-3 py-1.5 rounded-lg text-sm font-medium transition-colors', page === currentPage ? 'bg-sky-600 text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700']">{{ page }}</button>
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

    <!-- Detail Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showDetailModal = false">
          <div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
          <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-eye mr-2 text-sky-500"></i>Detail Pembayaran</h3>
              <button @click="showDetailModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button>
            </div>
            <div class="px-6 py-5 space-y-4">
              <div class="flex items-center gap-4 pb-4 border-b border-gray-100 dark:border-gray-700">
                <div class="w-16 h-16 rounded-lg bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white text-xl shrink-0">
                  <i :class="['fas', statusIcon(selectedItem?.status)]"></i>
                </div>
                <div>
                  <h4 class="text-xl font-bold text-gray-900 dark:text-white">{{ selectedItem?.kode_tagihan }}</h4>
                  <span :class="['inline-flex items-center gap-1 mt-1 px-2.5 py-0.5 rounded-full text-xs font-medium capitalize', statusBadge(selectedItem?.status)]">
                    <i :class="['fas', statusIcon(selectedItem?.status), 'text-[10px]']"></i>
                    {{ selectedItem?.status }}
                  </span>
                </div>
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pelanggan</label>
                  <p class="text-sm text-gray-900 dark:text-white mt-1">{{ selectedItem?.pelanggan }}</p>
                </div>
                <div>
                  <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Periode</label>
                  <p class="text-sm text-gray-900 dark:text-white mt-1">{{ selectedItem?.periode }}</p>
                </div>
                <div>
                  <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Jumlah</label>
                  <p class="text-sm text-gray-900 dark:text-white mt-1 font-semibold">{{ formatRupiah(selectedItem?.jumlah) }}</p>
                </div>
                <div>
                  <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Provider</label>
                  <p class="text-sm text-gray-900 dark:text-white mt-1">{{ providerName(selectedItem?.provider) }}</p>
                </div>
                <div>
                  <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Metode Pembayaran</label>
                  <p class="text-sm text-gray-900 dark:text-white mt-1">{{ metodeName(selectedItem?.metode) }}</p>
                </div>
                <div>
                  <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tanggal Bayar</label>
                  <p class="text-sm text-gray-900 dark:text-white mt-1">{{ selectedItem?.tgl_bayar }}</p>
                </div>
                <div>
                  <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kolektor</label>
                  <p class="text-sm text-gray-900 dark:text-white mt-1">{{ selectedItem?.kolektor || '-' }}</p>
                </div>
                <div>
                  <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Bukti Pembayaran</label>
                  <p v-if="selectedItem?.bukti" class="text-sm text-sky-600 dark:text-sky-400 mt-1 inline-flex items-center gap-1">
                    <i :class="['fas', getFileIcon(selectedItem.bukti)]"></i>
                    {{ selectedItem.bukti }}
                  </p>
                  <p v-else class="text-sm text-gray-400 mt-1">-</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Create / Edit Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showCreateModal || showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showCreateModal = false; showEditModal = false">
          <div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
          <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 z-10 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-t-2xl">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                <i :class="['fas mr-2', showCreateModal ? 'fa-plus text-emerald-500' : 'fa-edit text-sky-500']"></i>
                {{ showCreateModal ? 'Tambah Pembayaran' : 'Edit Pembayaran' }}
              </h3>
              <button @click="showCreateModal = false; showEditModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-times"></i></button>
            </div>
            <form class="px-6 py-5 space-y-4" @submit.prevent="showCreateModal ? saveCreate() : saveEdit()">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Kode Tagihan <span class="text-red-500">*</span></label>
                  <input v-model="form.kode_tagihan" type="text" placeholder="INV-202505-001" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white font-mono', formErrors.kode_tagihan ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500 focus:border-sky-500']" />
                  <p v-if="formErrors.kode_tagihan" class="text-red-500 text-xs mt-1">{{ formErrors.kode_tagihan }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Pelanggan <span class="text-red-500">*</span></label>
                  <input v-model="form.pelanggan" type="text" placeholder="Nama pelanggan" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', formErrors.pelanggan ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500 focus:border-sky-500']" />
                  <p v-if="formErrors.pelanggan" class="text-red-500 text-xs mt-1">{{ formErrors.pelanggan }}</p>
                </div>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Periode</label>
                  <input v-model="form.periode" type="text" placeholder="Mei 2025" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Jumlah (Rp) <span class="text-red-500">*</span></label>
                  <input v-model="form.jumlah" type="number" min="0" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', formErrors.jumlah ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500 focus:border-sky-500']" />
                  <p v-if="formErrors.jumlah" class="text-red-500 text-xs mt-1">{{ formErrors.jumlah }}</p>
                </div>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Tanggal Bayar <span class="text-red-500">*</span></label>
                  <input v-model="form.tgl_bayar" type="date" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', formErrors.tgl_bayar ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500 focus:border-sky-500']" />
                  <p v-if="formErrors.tgl_bayar" class="text-red-500 text-xs mt-1">{{ formErrors.tgl_bayar }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Status</label>
                  <select v-model="form.status" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors capitalize">
                    <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
                  </select>
                </div>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Provider</label>
                  <select v-model="form.provider" @change="onProviderChange" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors">
                    <option v-for="p in providers" :key="p.slug" :value="p.slug">{{ p.name }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Metode Pembayaran <span class="text-red-500">*</span></label>
                  <select v-model="form.metode" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors" :class="formErrors.metode ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : ''">
                    <option value="" disabled>Pilih metode</option>
                    <option v-for="m in metodeOptions" :key="m.slug" :value="m.slug">{{ m.name }}</option>
                  </select>
                  <p v-if="formErrors.metode" class="text-red-500 text-xs mt-1">{{ formErrors.metode }}</p>
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                  Kolektor
                </label>
                <input v-model="form.kolektor" type="text" placeholder="Nama kolektor" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                  Bukti Pembayaran
                  <span v-if="wajibInputBukti" class="text-red-500">*</span>
                  <span v-else class="text-gray-400 text-xs font-normal">(opsional)</span>
                </label>
                <div class="flex items-center gap-3">
                  <label class="flex-1 flex items-center gap-2 px-3 py-2.5 rounded-lg border border-dashed border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-900 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                    <i class="fas fa-upload text-gray-400 text-sm"></i>
                    <span class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ form.bukti ? form.bukti : 'Pilih file...' }}</span>
                    <input type="file" accept="image/*,.pdf" class="hidden" @change="form.bukti = $event.target.files[0]?.name || null" />
                  </label>
                  <button v-if="form.bukti" type="button" @click="form.bukti = null" class="p-2 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
                <p v-if="formErrors.bukti" class="text-red-500 text-xs mt-1">{{ formErrors.bukti }}</p>
              </div>
              <div class="flex justify-end gap-2 pt-2">
                <button type="button" @click="showCreateModal = false; showEditModal = false" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button>
                <button type="submit" class="px-6 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm">
                  <i :class="['fas mr-1.5', showCreateModal ? 'fa-save' : 'fa-check']"></i>
                  {{ showCreateModal ? 'Simpan' : 'Update' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Delete Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showDeleteModal = false">
          <div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
          <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
            <div class="px-6 py-5 text-center">
              <div class="w-14 h-14 mx-auto rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-4">
                <i class="fas fa-exclamation-triangle text-red-500 text-xl"></i>
              </div>
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Hapus Riwayat?</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                Anda akan menghapus <strong class="text-gray-700 dark:text-gray-300">{{ selectedItem?.kode_tagihan }} — {{ selectedItem?.pelanggan }}</strong>.
              </p>
            </div>
            <div class="flex justify-center gap-3 px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
              <button @click="showDeleteModal = false" class="px-5 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button>
              <button @click="confirmDelete" class="px-5 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors"><i class="fas fa-trash-alt mr-1.5"></i> Hapus</button>
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
