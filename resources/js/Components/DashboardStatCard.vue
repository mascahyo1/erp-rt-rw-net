<!--
  DashboardStatCard — Reusable stat card untuk 4 portal dashboard.
  Props:
    - icon: FA class (e.g. 'fa-users')
    - label: judul card
    - value: angka/label yang ditampilkan (string|number)
    - color: 'sky' | 'emerald' | 'amber' | 'violet' | 'rose' | 'fuchsia' | 'red' | 'orange' | 'blue' | 'teal' | 'green' | 'indigo' | 'pink' | 'purple' | 'yellow' | 'slate'
    - href: optional Link href (kalau ada → card clickable + "Lihat detail" hint)
    - sublabel: optional small text di bawah label (mis. "Bulan ini")
    - size: 'sm' | 'md' | 'lg' (default 'md')
    - gradientText: tampilkan value dengan gradient text (default true, set false untuk plain text)
  Behavior:
    - Jika href → render sebagai <Link> (Inertia client navigation)
    - Jika tidak → render sebagai <div> static
    - Hover lift + shadow + border accent (color-themed)
    - Dark mode support
-->
<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    icon: { type: String, required: true },
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    color: { type: String, default: 'sky' },
    href: { type: String, default: null },
    sublabel: { type: String, default: null },
    size: { type: String, default: 'md' }, // 'sm' | 'md' | 'lg'
    gradientText: { type: Boolean, default: true },
});

const palette = {
    sky: { bg: 'bg-sky-100 dark:bg-sky-900/30', icon: 'text-sky-600 dark:text-sky-400', border: 'hover:border-sky-300 dark:hover:border-sky-700', gradient: 'from-sky-500 to-blue-600', hint: 'group-hover:text-sky-500 dark:group-hover:text-sky-400' },
    emerald: { bg: 'bg-emerald-100 dark:bg-emerald-900/30', icon: 'text-emerald-600 dark:text-emerald-400', border: 'hover:border-emerald-300 dark:hover:border-emerald-700', gradient: 'from-emerald-500 to-teal-600', hint: 'group-hover:text-emerald-500 dark:group-hover:text-emerald-400' },
    teal: { bg: 'bg-teal-100 dark:bg-teal-900/30', icon: 'text-teal-600 dark:text-teal-400', border: 'hover:border-teal-300 dark:hover:border-teal-700', gradient: 'from-teal-500 to-emerald-600', hint: 'group-hover:text-teal-500 dark:group-hover:text-teal-400' },
    amber: { bg: 'bg-amber-100 dark:bg-amber-900/30', icon: 'text-amber-600 dark:text-amber-400', border: 'hover:border-amber-300 dark:hover:border-amber-700', gradient: 'from-amber-500 to-orange-600', hint: 'group-hover:text-amber-500 dark:group-hover:text-amber-400' },
    orange: { bg: 'bg-orange-100 dark:bg-orange-900/30', icon: 'text-orange-600 dark:text-orange-400', border: 'hover:border-orange-300 dark:hover:border-orange-700', gradient: 'from-orange-500 to-red-600', hint: 'group-hover:text-orange-500 dark:group-hover:text-orange-400' },
    violet: { bg: 'bg-violet-100 dark:bg-violet-900/30', icon: 'text-violet-600 dark:text-violet-400', border: 'hover:border-violet-300 dark:hover:border-violet-700', gradient: 'from-violet-500 to-purple-600', hint: 'group-hover:text-violet-500 dark:group-hover:text-violet-400' },
    purple: { bg: 'bg-purple-100 dark:bg-purple-900/30', icon: 'text-purple-600 dark:text-purple-400', border: 'hover:border-purple-300 dark:hover:border-purple-700', gradient: 'from-purple-500 to-fuchsia-600', hint: 'group-hover:text-purple-500 dark:group-hover:text-purple-400' },
    rose: { bg: 'bg-rose-100 dark:bg-rose-900/30', icon: 'text-rose-600 dark:text-rose-400', border: 'hover:border-rose-300 dark:hover:border-rose-700', gradient: 'from-rose-500 to-red-600', hint: 'group-hover:text-rose-500 dark:group-hover:text-rose-400' },
    red: { bg: 'bg-red-100 dark:bg-red-900/30', icon: 'text-red-600 dark:text-red-400', border: 'hover:border-red-300 dark:hover:border-red-700', gradient: 'from-red-500 to-rose-600', hint: 'group-hover:text-red-500 dark:group-hover:text-red-400' },
    fuchsia: { bg: 'bg-fuchsia-100 dark:bg-fuchsia-900/30', icon: 'text-fuchsia-600 dark:text-fuchsia-400', border: 'hover:border-fuchsia-300 dark:hover:border-fuchsia-700', gradient: 'from-fuchsia-500 to-pink-600', hint: 'group-hover:text-fuchsia-500 dark:group-hover:text-fuchsia-400' },
    pink: { bg: 'bg-pink-100 dark:bg-pink-900/30', icon: 'text-pink-600 dark:text-pink-400', border: 'hover:border-pink-300 dark:hover:border-pink-700', gradient: 'from-pink-500 to-rose-600', hint: 'group-hover:text-pink-500 dark:group-hover:text-pink-400' },
    blue: { bg: 'bg-blue-100 dark:bg-blue-900/30', icon: 'text-blue-600 dark:text-blue-400', border: 'hover:border-blue-300 dark:hover:border-blue-700', gradient: 'from-blue-500 to-indigo-600', hint: 'group-hover:text-blue-500 dark:group-hover:text-blue-400' },
    indigo: { bg: 'bg-indigo-100 dark:bg-indigo-900/30', icon: 'text-indigo-600 dark:text-indigo-400', border: 'hover:border-indigo-300 dark:hover:border-indigo-700', gradient: 'from-indigo-500 to-blue-600', hint: 'group-hover:text-indigo-500 dark:group-hover:text-indigo-400' },
    green: { bg: 'bg-green-100 dark:bg-green-900/30', icon: 'text-green-600 dark:text-green-400', border: 'hover:border-green-300 dark:hover:border-green-700', gradient: 'from-green-500 to-emerald-600', hint: 'group-hover:text-green-500 dark:group-hover:text-green-400' },
    yellow: { bg: 'bg-yellow-100 dark:bg-yellow-900/30', icon: 'text-yellow-600 dark:text-yellow-400', border: 'hover:border-yellow-300 dark:hover:border-yellow-700', gradient: 'from-yellow-500 to-amber-600', hint: 'group-hover:text-yellow-500 dark:group-hover:text-yellow-400' },
    slate: { bg: 'bg-slate-100 dark:bg-slate-900/30', icon: 'text-slate-600 dark:text-slate-400', border: 'hover:border-slate-300 dark:hover:border-slate-700', gradient: 'from-slate-500 to-gray-600', hint: 'group-hover:text-slate-500 dark:group-hover:text-slate-400' },
};

const c = computed(() => palette[props.color] || palette.sky);

const sizeClass = computed(() => {
    switch (props.size) {
        case 'sm': return 'p-4';
        case 'lg': return 'p-8';
        default: return 'p-6';
    }
});

const iconBoxClass = computed(() => {
    switch (props.size) {
        case 'sm': return 'w-9 h-9';
        case 'lg': return 'w-14 h-14';
        default: return 'w-10 h-10';
    }
});

const valueClass = computed(() => {
    const base = 'font-bold text-gray-900 dark:text-white';
    switch (props.size) {
        case 'sm': return `${base} text-lg`;
        case 'lg': return `${base} text-4xl`;
        default: return `${base} text-2xl`;
    }
});

const labelClass = computed(() => {
    return props.size === 'sm' ? 'text-xs text-gray-500 dark:text-gray-400 mt-0.5' : 'text-sm text-gray-500 dark:text-gray-400 mt-1';
});
</script>

<template>
    <component
        :is="href ? Link : 'div'"
        :href="href || undefined"
        :class="[
            'group rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm transition-all duration-300',
            'hover:shadow-lg hover:-translate-y-0.5',
            href ? 'cursor-pointer' : 'cursor-default',
            c.border,
            sizeClass,
        ]"
    >
        <div class="flex items-start justify-between mb-3">
            <div :class="['rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform duration-300', iconBoxClass, c.bg]">
                <i :class="['fas', icon, c.icon]"></i>
            </div>
            <div :class="['w-2 h-2 rounded-full bg-gradient-to-br opacity-60 group-hover:opacity-100 transition-opacity', c.gradient]"></div>
        </div>
        <div
            :class="[
                valueClass,
                gradientText ? `text-transparent bg-clip-text bg-gradient-to-br ${c.gradient}` : '',
            ]"
        >
            {{ value }}
        </div>
        <div :class="labelClass">{{ label }}</div>
        <div v-if="sublabel" class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ sublabel }}</div>
        <div v-if="href" :class="['flex items-center gap-1 mt-3 text-xs text-gray-400 dark:text-gray-500 transition-colors', c.hint]">
            <span>Lihat detail</span>
            <i class="fas fa-arrow-right text-[9px] group-hover:translate-x-0.5 transition-transform"></i>
        </div>
    </component>
</template>
