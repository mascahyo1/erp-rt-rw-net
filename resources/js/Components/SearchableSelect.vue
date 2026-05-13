<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  modelValue: { type: String, default: '' },
  options: { type: Array, default: () => [] },
  placeholder: { type: String, default: 'Pilih...' },
  searchable: { type: Boolean, default: true },
  disabled: { type: Boolean, default: false },
  pageSize: { type: Number, default: 25 },
  debounceMs: { type: Number, default: 300 },  // delay hemat resource
});

const emit = defineEmits(['update:modelValue', 'search', 'load-more']);

const open = ref(false);
const searchText = ref('');
const visibleCount = ref(props.pageSize);
const listRef = ref(null);
const dropdownRef = ref(null);
let debounceTimer = null;

const selectedLabel = computed(() => {
  const found = props.options.find(o => o.value === props.modelValue);
  return found ? found.label : '';
});

const filtered = computed(() => {
  if (!props.searchable || !searchText.value) return props.options;
  const q = searchText.value.toLowerCase();
  return props.options.filter(o => o.label.toLowerCase().includes(q));
});

const visibleOptions = computed(() => {
  return filtered.value.slice(0, visibleCount.value);
});

const hasMore = computed(() => visibleCount.value < filtered.value.length);

function toggle() {
  if (props.disabled) return;
  open.value = !open.value;
  if (open.value) {
    visibleCount.value = props.pageSize;
    searchText.value = '';
    nextTick(() => searchInputRef.value?.focus());
  }
}

function select(option) {
  emit('update:modelValue', option.value);
  open.value = false;
  searchText.value = '';
}

function close() {
  open.value = false;
  searchText.value = '';
}

function onSearchInput() {
  visibleCount.value = props.pageSize;
  clearTimeout(debounceTimer);
  // Delay untuk hemat resource server saat nanti pakai AJAX
  debounceTimer = setTimeout(() => {
    emit('search', searchText.value);
  }, props.debounceMs);
}

function loadMore() {
  if (!hasMore.value) return;
  // Saat pakai AJAX, emit event load-more untuk fetch halaman berikutnya
  emit('load-more', visibleCount.value);
  visibleCount.value += props.pageSize;
}

function onScroll(e) {
  const el = e.target;
  if (el.scrollHeight - el.scrollTop - el.clientHeight < 50 && hasMore.value) {
    loadMore();
  }
}

function handleClickOutside(e) {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
    close();
  }
}

const searchInputRef = ref(null);

function nextTick(fn) {
  setTimeout(fn, 50);
}

onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => document.removeEventListener('click', handleClickOutside));

watch(() => props.modelValue, () => { searchText.value = ''; });
</script>

<template>
  <div ref="dropdownRef" class="relative">
    <!-- Trigger -->
    <button
      type="button"
      @click="toggle"
      :disabled="disabled"
      class="flex items-center justify-between gap-1.5 w-full min-w-[160px] px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm transition-colors hover:border-gray-400 dark:hover:border-gray-500 disabled:opacity-50 disabled:cursor-not-allowed"
      :class="open ? 'ring-2 ring-indigo-500 border-indigo-500' : ''"
    >
      <span :class="modelValue ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-500'">
        {{ modelValue ? selectedLabel : placeholder }}
      </span>
      <div class="flex items-center gap-1">
        <button
          v-if="modelValue"
          @click.stop="select({ value: '', label: '' })"
          class="p-0.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
          title="Hapus pilihan"
        >
          <i class="fas fa-times text-[10px]"></i>
        </button>
        <i :class="['fas text-xs text-gray-400 transition-transform', open ? 'fa-chevron-up' : 'fa-chevron-down']"></i>
      </div>
    </button>

    <!-- Dropdown -->
    <Transition name="dropdown">
      <div
        v-show="open"
        class="absolute z-50 mt-1 w-full min-w-[200px] rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-xl overflow-hidden"
      >
        <!-- Search Input -->
        <div v-if="searchable" class="p-2 border-b border-gray-200 dark:border-gray-700">
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
              <i class="fas fa-search text-gray-400 text-xs"></i>
            </div>
            <input
              ref="searchInputRef"
              v-model="searchText"
              type="text"
              placeholder="Cari..."
              @input="onSearchInput"
              @click.stop
              class="w-full pl-8 pr-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors"
            />
          </div>
        </div>

        <!-- Options List (infinite scroll) -->
        <div
          ref="listRef"
          @scroll="onScroll"
          class="max-h-48 overflow-y-auto"
        >
          <div v-if="visibleOptions.length === 0" class="px-4 py-6 text-center text-sm text-gray-400">
            <i class="fas fa-search text-lg mb-1 block opacity-50"></i>
            Tidak ada hasil
          </div>
          <button
            type="button"
            v-for="option in visibleOptions"
            :key="option.value"
            @click="select(option)"
            class="w-full text-left px-4 py-2.5 text-sm transition-colors hover:bg-indigo-50 dark:hover:bg-indigo-900/20"
            :class="modelValue === option.value ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400 font-medium' : 'text-gray-700 dark:text-gray-300'"
          >
            {{ option.label }}
          </button>

          <!-- Load more indicator -->
          <div v-if="hasMore" class="px-4 py-2 text-center border-t border-gray-100 dark:border-gray-700">
            <button type="button" @click.stop="loadMore" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
              <i class="fas fa-chevron-down mr-1"></i> Muat lebih banyak ({{ filtered.length - visibleCount }} tersisa)
            </button>
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
