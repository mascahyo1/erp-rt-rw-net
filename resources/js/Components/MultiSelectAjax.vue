<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  url: { type: String, required: true },
  placeholder: { type: String, default: 'Pilih...' },
  disabled: { type: Boolean, default: false },
  error: { type: Boolean, default: false },
  pageSize: { type: Number, default: 25 },
  debounceMs: { type: Number, default: 300 },
  labelKey: { type: String, default: 'label' },
  valueKey: { type: String, default: 'value' },
  testId: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);

const open = ref(false);
const searchText = ref('');
const options = ref([]);
const total = ref(0);
const page = ref(1);
const loading = ref(false);
const dropdownRef = ref(null);
const listRef = ref(null);
let debounceTimer = null;
let abortController = null;

const hasMore = computed(() => options.value.length < total.value);

async function fetchOptions(search = '', pageNum = 1, append = false) {
  if (abortController) abortController.abort();
  abortController = new AbortController();

  loading.value = true;
  try {
    const params = new URLSearchParams({
      search: search,
      page: pageNum,
      per_page: props.pageSize,
    });

    const response = await fetch(`${props.url}?${params}`, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
      },
      signal: abortController.signal,
    });

    if (!response.ok) throw new Error('Network error');

    const data = await response.json();

    if (append) {
      options.value = [...options.value, ...(data.data || data)];
    } else {
      options.value = data.data || data;
    }
    total.value = data.total || data.meta?.total || options.value.length;
  } catch (e) {
    if (e.name !== 'AbortError') {
      console.error('MultiSelectAjax fetch error:', e);
    }
  } finally {
    loading.value = false;
  }
}

function toggle() {
  if (props.disabled) return;
  open.value = !open.value;
  if (open.value) {
    searchText.value = '';
    page.value = 1;
    fetchOptions('', 1, false);
  }
}

function isSelected(option) {
  return props.modelValue.includes(option[props.valueKey]);
}

function select(option) {
  const val = option[props.valueKey];
  const next = [...props.modelValue];
  const idx = next.indexOf(val);
  if (idx === -1) {
    next.push(val);
  } else {
    next.splice(idx, 1);
  }
  emit('update:modelValue', next);
}

function removeSelected(val) {
  emit('update:modelValue', props.modelValue.filter(v => v !== val));
}

function close() {
  open.value = false;
}

function onSearchInput() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    page.value = 1;
    fetchOptions(searchText.value, 1, false);
  }, props.debounceMs);
}

function loadMore() {
  if (!hasMore.value || loading.value) return;
  fetchOptions(searchText.value, page.value + 1, true);
  page.value++;
}

function onScroll(e) {
  const el = e.target;
  if (el.scrollHeight - el.scrollTop - el.clientHeight < 50 && hasMore.value && !loading.value) {
    loadMore();
  }
}

function handleClickOutside(e) {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
    close();
  }
}

onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => document.removeEventListener('click', handleClickOutside));
</script>

<template>
  <div ref="dropdownRef" class="relative">
    <button
      type="button"
      :data-testid="testId || 'multiselect-ajax'"
      @click="toggle"
      :disabled="disabled"
      class="flex items-center justify-between gap-1.5 w-full min-w-[160px] px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm transition-colors hover:border-gray-400 dark:hover:border-gray-500 disabled:opacity-50 disabled:cursor-not-allowed"
      :class="error ? 'border-red-400 ring-1 ring-red-300' : open ? 'ring-2 ring-sky-500 border-sky-500' : ''"
    >
      <span v-if="modelValue.length === 0" class="text-gray-400 dark:text-gray-500">
        {{ placeholder }}
      </span>
      <span v-else class="inline-flex items-center gap-1.5 flex-wrap text-gray-900 dark:text-white">
        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400">
          <i class="fas fa-check text-[9px]"></i> {{ modelValue.length }} dipilih
        </span>
      </span>
      <div class="flex items-center gap-1">
        <button
          v-if="modelValue.length > 0"
          type="button"
          @click.stop="emit('update:modelValue', [])"
          class="p-0.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
          title="Bersihkan semua pilihan"
        >
          <i class="fas fa-times text-[10px]"></i>
        </button>
        <i :class="['fas text-xs text-gray-400 transition-transform', open ? 'fa-chevron-up' : 'fa-chevron-down']"></i>
      </div>
    </button>

    <Transition name="dropdown">
      <div
        v-show="open"
        class="absolute z-50 mt-1 w-full min-w-[260px] rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-xl overflow-hidden"
      >
        <div class="p-2 border-b border-gray-200 dark:border-gray-700">
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
              <i class="fas fa-search text-gray-400 text-xs"></i>
            </div>
            <input
              v-model="searchText"
              type="text"
              placeholder="Cari..."
              @input="onSearchInput"
              @click.stop
              class="w-full pl-8 pr-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors"
            />
          </div>
        </div>

        <div ref="listRef" @scroll="onScroll" class="max-h-48 overflow-y-auto">
          <div v-if="loading && options.length === 0" class="px-4 py-6 text-center text-sm text-gray-400">
            <i class="fas fa-spinner fa-spin text-lg mb-1 block"></i>
            Memuat...
          </div>

          <div v-else-if="options.length === 0" class="px-4 py-6 text-center text-sm text-gray-400">
            <i class="fas fa-search text-lg mb-1 block opacity-50"></i>
            Tidak ada hasil
          </div>

          <button
            type="button"
            v-for="option in options"
            :key="option[valueKey]"
            @click="select(option)"
            class="w-full text-left px-4 py-2.5 text-sm transition-colors hover:bg-sky-50 dark:hover:bg-sky-900/20 flex items-start gap-2"
          >
            <span
              class="mt-0.5 w-4 h-4 shrink-0 rounded border flex items-center justify-center"
              :class="isSelected(option) ? 'bg-sky-600 border-sky-600 text-white' : 'border-gray-300 dark:border-gray-600 text-transparent'"
            >
              <i class="fas fa-check text-[9px]"></i>
            </span>
            <span>
              <span class="block font-medium" :class="isSelected(option) ? 'text-sky-700 dark:text-sky-400' : 'text-gray-700 dark:text-gray-300'">
                {{ option[labelKey] }}
              </span>
              <span v-if="option.email" class="block text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ option.email }}</span>
            </span>
          </button>

          <div v-if="hasMore && !loading" class="px-4 py-2 text-center border-t border-gray-100 dark:border-gray-700">
            <button type="button" @click.stop="loadMore" class="text-xs text-sky-600 dark:text-sky-400 hover:underline">
              Muat lebih banyak...
            </button>
          </div>

          <div v-if="loading && options.length > 0" class="px-4 py-2 text-center text-xs text-gray-400">
            <i class="fas fa-spinner fa-spin mr-1"></i> Memuat...
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
