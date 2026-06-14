<!--
  DashboardHero — Reusable welcome banner dengan gradient + blur orbs.
  Dipakai di dashboard Perusahaan (sky-blue), Karyawan (amber-orange),
  Pelanggan (emerald-teal). SaaS tidak pakai (pakai pola 6 cards grid langsung).

  Props:
    - title: string (mis. "Selamat datang, {{name}}!")
    - subtitle: string (deskripsi di bawah title)
    - icon: FA class (mis. 'fa-building')
    - gradient: 'sky' | 'amber' | 'emerald' | 'violet' | 'rose'
    - extra: optional slot di kanan (mis. tombol "Aksi Cepat", info tanggal, dll)
-->
<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    icon: { type: String, required: true },
    gradient: { type: String, default: 'sky' },
});

const palette = {
    sky: { gradient: 'from-sky-500 to-blue-600', subText: 'text-sky-100' },
    amber: { gradient: 'from-amber-500 to-orange-600', subText: 'text-amber-100' },
    emerald: { gradient: 'from-emerald-500 to-teal-600', subText: 'text-emerald-100' },
    violet: { gradient: 'from-violet-500 to-purple-600', subText: 'text-violet-100' },
    rose: { gradient: 'from-rose-500 to-pink-600', subText: 'text-rose-100' },
};

const p = computed(() => palette[props.gradient] || palette.sky);
</script>

<template>
    <div :class="['relative overflow-hidden rounded-2xl bg-gradient-to-br p-8 md:p-10 shadow-lg', p.gradient]">
        <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-16 -left-16 w-48 h-48 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative flex flex-col md:flex-row items-start md:items-center gap-6">
            <div class="w-16 h-16 rounded-2xl bg-white/15 backdrop-blur-sm flex items-center justify-center shrink-0 shadow-lg">
                <i :class="['fas text-white text-2xl', icon]"></i>
            </div>
            <div class="flex-1 min-w-0 text-white">
                <h3 class="text-2xl font-bold mb-1">{{ title }}</h3>
                <p v-if="subtitle" :class="['text-sm md:text-base', p.subText]">{{ subtitle }}</p>
            </div>
            <div v-if="$slots.extra" class="shrink-0">
                <slot name="extra" />
            </div>
        </div>
    </div>
</template>
