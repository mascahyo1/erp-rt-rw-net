<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  modelValue: { type: Object, default: null },
  placeholder: { type: String, default: 'Cari perusahaan...' },
  disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const open = ref(false);
const search = ref('');
const items = ref([]);
const page = ref(1);
const loading = ref(false);
const loadingMore = ref(false);
const hasMore = ref(true);
const listRef = ref(null);
const dropdownRef = ref(null);
const searchInputRef = ref(null);

const allCompanies = [
  { id: 1, nama: 'PT Net Sejahtera Abadi', email: 'admin@netsejahtera.com', kota: 'Jakarta' },
  { id: 2, nama: 'CV Digital Media Nusantara', email: 'info@digitalmedia.id', kota: 'Bandung' },
  { id: 3, nama: 'UD Net Mandiri Global', email: 'support@netmandiri.com', kota: 'Surabaya' },
  { id: 4, nama: 'PT Teknologi Nusantara', email: 'hello@teknus.co.id', kota: 'Jakarta' },
  { id: 5, nama: 'CV Media Connect Indo', email: 'cs@mediaconnect.id', kota: 'Yogyakarta' },
  { id: 6, nama: 'PT Jaringan Prima Solusi', email: 'info@jaringanprima.com', kota: 'Bandung' },
  { id: 7, nama: 'CV Angkasa Netindo', email: 'support@angkasa.net', kota: 'Semarang' },
  { id: 8, nama: 'UD Bumi Aksara Network', email: 'cs@bumiaksara.id', kota: 'Surabaya' },
  { id: 9, nama: 'PT Cakra Buana Internet', email: 'admin@cakrabuana.net', kota: 'Medan' },
  { id: 10, nama: 'CV Dwipa Nusantara Net', email: 'info@dwipanusantara.com', kota: 'Makassar' },
  { id: 11, nama: 'PT Eka Daya Solusindo', email: 'contact@ekadaya.co.id', kota: 'Jakarta' },
  { id: 12, nama: 'CV Fajar Mitra Abadi', email: 'cs@fajarmitra.com', kota: 'Bandung' },
  { id: 13, nama: 'UD Guna Karya Network', email: 'info@gunakarya.net', kota: 'Yogyakarta' },
  { id: 14, nama: 'PT Hasta Karya Teknologi', email: 'admin@hastakarya.id', kota: 'Semarang' },
  { id: 15, nama: 'CV Indah Permata Net', email: 'support@indahpermata.com', kota: 'Surabaya' },
  { id: 16, nama: 'PT Jaya Abadi Networks', email: 'cs@jayaabadi.co.id', kota: 'Medan' },
  { id: 17, nama: 'CV Kencana Sakti Indo', email: 'info@kencanasakti.net', kota: 'Makassar' },
  { id: 18, nama: 'UD Lestari Jaya Network', email: 'admin@lestarijaya.com', kota: 'Jakarta' },
  { id: 19, nama: 'PT Murni Kreasi Digital', email: 'hello@murnikreasi.id', kota: 'Bandung' },
  { id: 20, nama: 'CV Nusantara Cipta Media', email: 'cs@nusantaracipta.com', kota: 'Yogyakarta' },
  { id: 21, nama: 'PT Orbit Teknologi Indo', email: 'info@orbit-tekno.id', kota: 'Semarang' },
  { id: 22, nama: 'CV Prima Jaringan Nusantara', email: 'support@primajaringan.net', kota: 'Surabaya' },
  { id: 23, nama: 'UD Qolbu Networks', email: 'admin@qolbu.net', kota: 'Medan' },
  { id: 24, nama: 'PT Raya Cipta Solusi', email: 'cs@rayacipta.co.id', kota: 'Makassar' },
  { id: 25, nama: 'CV Sinar Abadi Teknologi', email: 'info@sinarabadi.com', kota: 'Jakarta' },
  { id: 26, nama: 'PT Terang Benderang Net', email: 'hello@terangbenderang.id', kota: 'Bandung' },
  { id: 27, nama: 'CV Utama Jaringan Kita', email: 'cs@utamajaringan.com', kota: 'Yogyakarta' },
  { id: 28, nama: 'UD Varia Net Indonesia', email: 'info@varianet.co.id', kota: 'Semarang' },
  { id: 29, nama: 'PT Wahana Teknologi Nusa', email: 'support@wahanatekno.net', kota: 'Surabaya' },
  { id: 30, nama: 'CV Citra Mandiri Abadi', email: 'admin@citramandiri.com', kota: 'Medan' },
  { id: 31, nama: 'PT Bukit Indah Networks', email: 'cs@bukitindah.co.id', kota: 'Makassar' },
  { id: 32, nama: 'CV Surya Gemilang Net', email: 'info@suryagemilang.id', kota: 'Jakarta' },
  { id: 33, nama: 'UD Mega Jaya Solusindo', email: 'hello@megajaya.com', kota: 'Bandung' },
  { id: 34, nama: 'PT Bina Karya Nusantara', email: 'cs@binakarya.net', kota: 'Yogyakarta' },
  { id: 35, nama: 'CV Karya Anak Negeri', email: 'info@karyaanaknegeri.id', kota: 'Semarang' },
  { id: 36, nama: 'PT Sinergi Digital Indo', email: 'admin@sinergidigital.com', kota: 'Surabaya' },
  { id: 37, nama: 'CV Cahaya Network Solutions', email: 'support@cahayanet.co.id', kota: 'Medan' },
  { id: 38, nama: 'UD Sahabat Net Sejahtera', email: 'cs@sahabatnet.id', kota: 'Makassar' },
  { id: 39, nama: 'PT Multi Jaringan Nusantara', email: 'info@multijaringan.net', kota: 'Jakarta' },
  { id: 40, nama: 'CV Anugerah Teknologi Indo', email: 'hello@anugerahtekno.com', kota: 'Bandung' },
  { id: 41, nama: 'PT Pelangi Networks Indonesia', email: 'admin@pelanginetworks.id', kota: 'Yogyakarta' },
  { id: 42, nama: 'CV Sumber Rejeki Net', email: 'cs@sumberrejeki.co.id', kota: 'Semarang' },
  { id: 43, nama: 'UD Berkah Jaya Teknologi', email: 'info@berkahjaya.net', kota: 'Surabaya' },
  { id: 44, nama: 'PT Mandiri Sejahtera Abadi', email: 'support@mandirisejahtera.com', kota: 'Medan' },
  { id: 45, nama: 'CV Karya Cipta Nusantara', email: 'admin@karyacipta.id', kota: 'Makassar' },
  { id: 46, nama: 'PT Arjuna Network Solutions', email: 'cs@arjunanet.com', kota: 'Jakarta' },
  { id: 47, nama: 'CV Bimasakti Teknologi Indo', email: 'info@bimasaktitekno.id', kota: 'Bandung' },
  { id: 48, nama: 'UD Citra Jaya Networks', email: 'hello@citrajaya.net', kota: 'Yogyakarta' },
  { id: 49, nama: 'PT Dewata Nusantara Net', email: 'admin@dewatanusantara.com', kota: 'Semarang' },
  { id: 50, nama: 'CV Era Digital Solusindo', email: 'cs@eradigital.co.id', kota: 'Surabaya' },
];

let debounceTimer = null;

const filteredAll = computed(() => {
  if (!search.value) return allCompanies;
  const q = search.value.toLowerCase();
  return allCompanies.filter(c =>
    c.nama.toLowerCase().includes(q) ||
    c.kota.toLowerCase().includes(q)
  );
});

const displayItems = computed(() => {
  return items.value;
});

function simulateAjax(reset) {
  if (reset) {
    page.value = 1;
    items.value = [];
    hasMore.value = true;
  }

  loading.value = reset;
  loadingMore.value = !reset;

  setTimeout(() => {
    const data = filteredAll.value;
    const perPage = 10;
    const start = (page.value - 1) * perPage;
    const chunk = data.slice(start, start + perPage);

    if (reset) {
      items.value = chunk;
    } else {
      items.value = [...items.value, ...chunk];
    }

    hasMore.value = start + perPage < data.length;
    loading.value = false;
    loadingMore.value = false;
  }, 400);
}

function onSearchInput() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    simulateAjax(true);
  }, 300);
}

function loadMore() {
  if (loadingMore.value || !hasMore.value) return;
  page.value++;
  simulateAjax(false);
}

function onScroll(e) {
  const el = e.target;
  if (el.scrollHeight - el.scrollTop - el.clientHeight < 60 && !loadingMore.value && hasMore.value) {
    loadMore();
  }
}

function toggle() {
  if (props.disabled) return;
  open.value = !open.value;
  if (open.value) {
    search.value = '';
    simulateAjax(true);
    setTimeout(() => searchInputRef.value?.focus(), 100);
  }
}

function select(company) {
  emit('update:modelValue', company);
  open.value = false;
  search.value = '';
}

function clearSelection() {
  emit('update:modelValue', null);
}

function handleClickOutside(e) {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
    open.value = false;
  }
}

onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => document.removeEventListener('click', handleClickOutside));
</script>

<template>
  <div ref="dropdownRef" class="relative">
    <button
      type="button"
      @click="toggle"
      :disabled="disabled"
      class="flex items-center justify-between gap-1.5 w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm transition-colors hover:border-gray-400 dark:hover:border-gray-500 disabled:opacity-50 disabled:cursor-not-allowed"
      :class="[open ? 'ring-2 ring-indigo-500 border-indigo-500' : '', modelValue ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-500']"
    >
      <div class="flex items-center gap-2 min-w-0">
        <i class="fas fa-building text-gray-400 text-xs"></i>
        <span class="truncate">{{ modelValue ? modelValue.nama : placeholder }}</span>
      </div>
      <div class="flex items-center gap-1 shrink-0">
        <button
          v-if="modelValue"
          type="button"
          @click.stop="clearSelection"
          class="p-0.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
        >
          <i class="fas fa-times text-[10px]"></i>
        </button>
        <i :class="['fas text-xs text-gray-400 transition-transform', open ? 'fa-chevron-up' : 'fa-chevron-down']"></i>
      </div>
    </button>

    <Transition name="dropdown">
      <div
        v-show="open"
        class="absolute z-50 mt-1 w-full min-w-[280px] rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-xl overflow-hidden"
      >
        <div class="p-2 border-b border-gray-200 dark:border-gray-700">
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
              <i class="fas fa-search text-gray-400 text-xs"></i>
            </div>
            <input
              ref="searchInputRef"
              v-model="search"
              type="text"
              placeholder="Cari perusahaan..."
              @input="onSearchInput"
              @click.stop
              class="w-full pl-8 pr-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors"
            />
          </div>
        </div>

        <div
          ref="listRef"
          @scroll="onScroll"
          class="max-h-56 overflow-y-auto"
        >
          <div v-if="loading && items.length === 0" class="px-4 py-8 text-center text-sm text-gray-400">
            <i class="fas fa-spinner fa-spin text-lg mb-2 block"></i>
            Mencari...
          </div>
          <div v-else-if="!loading && items.length === 0" class="px-4 py-8 text-center text-sm text-gray-400">
            <i class="fas fa-search text-lg mb-2 block opacity-50"></i>
            Perusahaan tidak ditemukan
          </div>
          <button
            v-for="item in displayItems"
            :key="item.id"
            type="button"
            @click="select(item)"
            class="w-full text-left px-4 py-3 transition-colors hover:bg-indigo-50 dark:hover:bg-indigo-900/20 border-b border-gray-50 dark:border-gray-700/50 last:border-0"
            :class="modelValue?.id === item.id ? 'bg-indigo-50 dark:bg-indigo-900/20' : ''"
          >
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-sky-500 flex items-center justify-center text-white text-xs font-bold shrink-0">
                {{ item.nama.charAt(0) }}
              </div>
              <div class="min-w-0">
                <div class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ item.nama }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ item.kota }} · {{ item.email }}</div>
              </div>
            </div>
          </button>

          <div v-if="loadingMore" class="px-4 py-3 text-center border-t border-gray-100 dark:border-gray-700">
            <i class="fas fa-spinner fa-spin text-indigo-500 text-sm"></i>
            <span class="text-xs text-gray-400 ml-2">Memuat...</span>
          </div>
          <div v-else-if="!hasMore && items.length > 0" class="px-4 py-3 text-center border-t border-gray-100 dark:border-gray-700">
            <span class="text-xs text-gray-400">Semua perusahaan telah dimuat</span>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.dropdown-enter-active { transition: opacity 0.15s ease, transform 0.15s ease; }
.dropdown-leave-active { transition: opacity 0.1s ease, transform 0.1s ease; }
.dropdown-enter-from, .dropdown-leave-to { opacity: 0; transform: translateY(-4px) scale(0.97); }
</style>
