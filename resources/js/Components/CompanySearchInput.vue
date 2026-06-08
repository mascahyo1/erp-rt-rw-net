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
const error = ref(null);

let debounceTimer = null;

const displayItems = computed(() => items.value);

async function fetchPage(reset) {
  if (reset) {
    page.value = 1;
    items.value = [];
    hasMore.value = true;
    error.value = null;
  }

  loading.value = reset;
  loadingMore.value = !reset;

  try {
    const url = new URL('/api/companies/search', window.location.origin);
    url.searchParams.set('q', search.value);
    url.searchParams.set('page', String(page.value));

    const res = await fetch(url, {
      headers: { 'Accept': 'application/json' },
      credentials: 'same-origin',
    });
    if (!res.ok) throw new Error('HTTP ' + res.status);
    const data = await res.json();

    if (reset) {
      items.value = data.data || [];
    } else {
      items.value = [...items.value, ...(data.data || [])];
    }
    hasMore.value = !!data.hasMore;
  } catch (e) {
    error.value = 'Gagal memuat daftar perusahaan';
    if (reset) items.value = [];
  } finally {
    loading.value = false;
    loadingMore.value = false;
  }
}

function onSearchInput() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    fetchPage(true);
  }, 300);
}

function loadMore() {
  if (loadingMore.value || !hasMore.value) return;
  page.value++;
  fetchPage(false);
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
    fetchPage(true);
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
            :data-testid="'company-item-' + item.id"
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
