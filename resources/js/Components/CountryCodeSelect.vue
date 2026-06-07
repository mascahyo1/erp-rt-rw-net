<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import { COUNTRY_CODES, searchCountries, findCountryByDial } from '@/Composables/useCountryCodes';

const props = defineProps({
    modelValue: { type: String, default: '+62' },
    placeholder: { type: String, default: 'Pilih negara' },
    size: { type: String, default: 'md' }, // 'sm' | 'md' | 'lg'
    accent: { type: String, default: 'indigo' }, // color accent for focus ring
    inputId: { type: String, default: null },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'change']);

const isOpen = ref(false);
const searchQuery = ref('');
const containerRef = ref(null);
const searchInputRef = ref(null);
const triggerRef = ref(null);
const highlightedIndex = ref(0);

const currentCountry = computed(() => findCountryByDial(props.modelValue) || findCountryByDial('+62') || COUNTRY_CODES[0]);

const filteredCountries = computed(() => searchCountries(searchQuery.value, 500));

// Reset highlighted index when search changes
watch(searchQuery, () => {
    highlightedIndex.value = 0;
});

const sizeClasses = computed(() => {
    switch (props.size) {
        case 'sm': return { trigger: 'px-2.5 py-1.5 text-xs', flag: 'w-4 h-3', label: 'text-xs', dropdown: 'text-sm', search: 'px-3 py-2 text-sm' };
        case 'lg': return { trigger: 'px-4 py-3 text-base', flag: 'w-7 h-5', label: 'text-base', dropdown: 'text-base', search: 'px-4 py-3 text-base' };
        default: return { trigger: 'px-3 py-2.5 text-sm', flag: 'w-5 h-3.5', label: 'text-sm', dropdown: 'text-sm', search: 'px-3 py-2.5 text-sm' };
    }
});

const accentClasses = computed(() => {
    const accents = {
        indigo: { focus: 'focus:ring-indigo-500', focusBorder: 'focus:border-indigo-500' },
        sky: { focus: 'focus:ring-sky-500', focusBorder: 'focus:border-sky-500' },
        amber: { focus: 'focus:ring-amber-500', focusBorder: 'focus:border-amber-500' },
        emerald: { focus: 'focus:ring-emerald-500', focusBorder: 'focus:border-emerald-500' },
    };
    return accents[props.accent] || accents.indigo;
});

function open() {
    if (props.disabled) return;
    isOpen.value = true;
    searchQuery.value = '';
    highlightedIndex.value = 0;
    nextTick(() => searchInputRef.value?.focus());
}

function close() {
    isOpen.value = false;
    searchQuery.value = '';
    highlightedIndex.value = 0;
}

function toggle() {
    isOpen.value ? close() : open();
}

function selectCountry(country) {
    emit('update:modelValue', country.dial);
    emit('change', country);
    close();
    nextTick(() => triggerRef.value?.focus());
}

function onClickOutside(e) {
    // The dropdown is teleported to body (outside componentRef), so we must
    // also check if the click was inside the dropdown panel itself.
    if (containerRef.value && containerRef.value.contains(e.target)) return;
    if (e.target.closest('[data-countrycode-dropdown]')) return;
    close();
}

function onEscape(e) {
    if (e.key === 'Escape' && isOpen.value) {
        e.preventDefault();
        close();
        triggerRef.value?.focus();
    }
}

function onArrowDown(e) {
    if (!isOpen.value) {
        if (e.key === 'ArrowDown' || e.key === 'Enter') {
            e.preventDefault();
            open();
        }
        return;
    }
    e.preventDefault();
    if (e.key === 'ArrowDown') {
        highlightedIndex.value = Math.min(highlightedIndex.value + 1, filteredCountries.value.length - 1);
        scrollToHighlighted();
    } else if (e.key === 'ArrowUp') {
        highlightedIndex.value = Math.max(highlightedIndex.value - 1, 0);
        scrollToHighlighted();
    } else if (e.key === 'Enter') {
        if (filteredCountries.value[highlightedIndex.value]) {
            selectCountry(filteredCountries.value[highlightedIndex.value]);
        }
    } else if (e.key === 'Tab') {
        close();
    }
}

function scrollToHighlighted() {
    nextTick(() => {
        const el = containerRef.value?.querySelector('[data-highlighted="true"]');
        if (el) el.scrollIntoView({ block: 'nearest' });
    });
}

onMounted(() => {
    document.addEventListener('click', onClickOutside);
    document.addEventListener('keydown', onArrowDown);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', onClickOutside);
    document.removeEventListener('keydown', onArrowDown);
});
</script>

<template>
    <div ref="containerRef" class="relative w-full">
        <!-- Trigger button (looks like a select but with flag) -->
        <button
            ref="triggerRef"
            type="button"
            :id="inputId"
            @click="toggle"
            @keydown="onArrowDown"
            :disabled="disabled"
            :class="[
                'flex items-center gap-2 w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-left transition-colors outline-none',
                sizeClasses.trigger,
                accentClasses.focus,
                accentClasses.focusBorder,
                'focus:ring-2',
                isOpen ? 'ring-2 ring-' + accent + '-500 border-' + accent + '-500' : '',
                disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer hover:border-gray-400 dark:hover:border-gray-500',
            ]"
        >
            <span
                :class="['fi', 'fi-' + currentCountry.code.toLowerCase(), 'rounded-sm shadow-sm shrink-0', sizeClasses.flag]"
                :title="currentCountry.name + ' (' + currentCountry.dial + ')'"
            ></span>
            <span :class="['font-medium', sizeClasses.label]">{{ currentCountry.dial }}</span>
            <span :class="['text-gray-500 dark:text-gray-400 truncate flex-1', sizeClasses.label]">{{ currentCountry.name }}</span>
            <i :class="['fas fa-chevron-down text-gray-400 transition-transform shrink-0', sizeClasses.label, isOpen ? 'rotate-180' : '']"></i>
        </button>

        <!-- Dropdown -->
        <Teleport to="body">
            <Transition name="dropdown">
                <div
                    v-if="isOpen"
                    data-countrycode-dropdown
                    class="fixed inset-0 z-[60] flex items-start justify-center pt-[12vh] px-4"
                    @click.self="close"
                >
                    <div class="fixed inset-0 bg-black/30 backdrop-blur-[1px]" @click="close"></div>
                    <div
                        :class="[
                            'relative w-full max-w-md max-h-[60vh] flex flex-col rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-2xl overflow-hidden',
                        ]"
                    >
                        <!-- Search input -->
                        <div class="shrink-0 p-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                            <div class="relative">
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                                <input
                                    ref="searchInputRef"
                                    v-model="searchQuery"
                                    type="text"
                                    :placeholder="placeholder"
                                    :class="[
                                        'w-full pl-9 pr-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white outline-none',
                                        sizeClasses.search,
                                        accentClasses.focus,
                                        accentClasses.focusBorder,
                                        'focus:ring-2',
                                    ]"
                                />
                            </div>
                            <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                <span v-if="searchQuery">{{ filteredCountries.length }} hasil untuk "{{ searchQuery }}"</span>
                                <span v-else>Total {{ COUNTRY_CODES.length }} negara</span>
                            </div>
                        </div>

                        <!-- Country list -->
                        <div class="flex-1 overflow-y-auto">
                            <button
                                v-for="(country, index) in filteredCountries"
                                :key="country.code"
                                type="button"
                                :data-highlighted="index === highlightedIndex"
                                @click="selectCountry(country)"
                                @mouseenter="highlightedIndex = index"
                                :class="[
                                    'w-full flex items-center gap-3 px-3 py-2.5 text-left transition-colors',
                                    sizeClasses.dropdown,
                                    country.dial === modelValue ? 'bg-' + accent + '-50 dark:bg-' + accent + '-900/20' : '',
                                    index === highlightedIndex ? 'bg-gray-100 dark:bg-gray-700/50' : 'hover:bg-gray-50 dark:hover:bg-gray-700/30',
                                ]"
                            >
                                <span
                                    :class="['fi', 'fi-' + country.code.toLowerCase(), 'rounded-sm shadow-sm shrink-0', sizeClasses.flag]"
                                    :title="country.name + ' (' + country.dial + ')'"
                                ></span>
                                <div class="flex-1 min-w-0">
                                    <div class="text-gray-900 dark:text-white font-medium truncate">{{ country.name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ country.name_id }} • {{ country.code }}</div>
                                </div>
                                <span :class="['font-mono text-gray-700 dark:text-gray-300 shrink-0', sizeClasses.label]">{{ country.dial }}</span>
                                <i v-if="country.dial === modelValue" class="fas fa-check text-emerald-500 ml-1"></i>
                            </button>
                            <div v-if="filteredCountries.length === 0" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                <i class="fas fa-search text-3xl mb-2 block text-gray-300 dark:text-gray-600"></i>
                                <p class="text-sm">Tidak ada negara yang cocok dengan "{{ searchQuery }}"</p>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<style scoped>
.dropdown-enter-active,
.dropdown-leave-active {
    transition: opacity 0.15s ease;
}
.dropdown-enter-active > div:last-child,
.dropdown-leave-active > div:last-child {
    transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.15s ease;
}
.dropdown-enter-from,
.dropdown-leave-to {
    opacity: 0;
}
.dropdown-enter-from > div:last-child {
    transform: scale(0.95) translateY(-8px);
    opacity: 0;
}
.dropdown-leave-to > div:last-child {
    transform: scale(0.95) translateY(-8px);
    opacity: 0;
}
</style>
