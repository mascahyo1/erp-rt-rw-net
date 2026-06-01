<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    permissions: { type: Array, required: true }, // [{ id, nama, deskripsi }]
    modelValue: { type: Array, required: true },    // selected permission ids
    color: { type: String, default: 'sky' },        // accent: sky | indigo
});

const emit = defineEmits(['update:modelValue']);

const search = ref('');
const collapsed = ref({}); // { moduleKey: bool }

const colorMap = {
    sky: {
        ring: 'focus:ring-sky-500 focus:border-sky-500',
        check: 'text-sky-600 dark:text-sky-500 focus:ring-sky-500',
        groupBg: 'bg-sky-50 dark:bg-sky-900/20',
        groupBorder: 'border-sky-200 dark:border-sky-800',
        groupText: 'text-sky-700 dark:text-sky-300',
        badgeAll: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
        badgeSome: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
        badgeNone: 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
    },
    indigo: {
        ring: 'focus:ring-indigo-500 focus:border-indigo-500',
        check: 'text-indigo-600 dark:text-indigo-500 focus:ring-indigo-500',
        groupBg: 'bg-indigo-50 dark:bg-indigo-900/20',
        groupBorder: 'border-indigo-200 dark:border-indigo-800',
        groupText: 'text-indigo-700 dark:text-indigo-300',
        badgeAll: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
        badgeSome: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
        badgeNone: 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
    },
};
const c = computed(() => colorMap[props.color] || colorMap.sky);

const moduleLabels = {
    'admin-perusahaan': 'Admin Perusahaan',
    'perusahaan': 'Perusahaan',
    'role-perusahaan': 'Role Perusahaan',
    'role-admin-perusahaan': 'Role Admin Perusahaan',
    'konfigurasi': 'Konfigurasi',
    'role-saas': 'Role SaaS',
    'admin-saas': 'Admin SaaS',
    'admin-role-saas': 'Admin Role SaaS',
    'admin-role-perusahaan': 'Admin Role Perusahaan',
    'admin-role-web-karyawan': 'Admin Role Web Karyawan',
    'role-perusahaan-op': 'Role Perusahaan (Operator)',
    'role-web-karyawan': 'Role Web Karyawan',
    'karyawan': 'Karyawan',
    'customer': 'Customer',
    'langganan': 'Langganan',
    'tagihan': 'Tagihan',
    'insentif': 'Insentif',
    'riwayat-insentif': 'Riwayat Insentif',
    'riwayat-pembayaran': 'Riwayat Pembayaran',
    'konfigurasi-perusahaan': 'Konfigurasi Perusahaan',
    'perusahaan-saya': 'Perusahaan Saya',
    'profil-saya': 'Profil Saya',
    'karyawan-customer': 'Karyawan - Customer',
    'karyawan-langganan': 'Karyawan - Langganan',
    'karyawan-tagihan': 'Karyawan - Tagihan',
    'karyawan-insentif': 'Karyawan - Insentif',
    'karyawan-riwayat-pembayaran': 'Karyawan - Riwayat Pembayaran',
    'paket': 'Paket',
};
const actionLabels = {
    list: 'Lihat Daftar', create: 'Tambah', edit: 'Ubah',
    detail: 'Detail', delete: 'Hapus', import: 'Import', export: 'Export',
    generate: 'Generate', persetujuan: 'Persetujuan',
};

function extractModule(name) {
    const lastDot = String(name).lastIndexOf('.');
    return lastDot !== -1 ? name.substring(0, lastDot) : name;
}
function extractAction(name) {
    const lastDot = String(name).lastIndexOf('.');
    return lastDot !== -1 ? name.substring(lastDot + 1) : '';
}
function getModuleLabel(module) { return moduleLabels[module] || module; }
function getActionLabel(action) { return actionLabels[action] || action; }
function getPermLabel(perm) {
    const module = extractModule(perm.nama);
    const action = extractAction(perm.nama);
    return { module: getModuleLabel(module), action: getActionLabel(action), key: module };
}

const groups = computed(() => {
    const map = {};
    props.permissions.forEach(p => {
        const module = extractModule(p.nama);
        if (!map[module]) map[module] = { module, label: getModuleLabel(module), perms: [] };
        map[module].perms.push(p);
    });
    return Object.values(map).sort((a, b) => a.label.localeCompare(b.label));
});

const filteredGroups = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return groups.value;
    return groups.value
        .map(g => {
            const perms = g.perms.filter(p => {
                const info = getPermLabel(p);
                return p.nama.toLowerCase().includes(q)
                    || info.module.toLowerCase().includes(q)
                    || info.action.toLowerCase().includes(q)
                    || (p.deskripsi || '').toLowerCase().includes(q);
            });
            return perms.length ? { ...g, perms } : null;
        })
        .filter(Boolean);
});

function isChecked(id) { return props.modelValue.includes(id); }
function toggle(id) {
    const next = isChecked(id)
        ? props.modelValue.filter(x => x !== id)
        : [...props.modelValue, id];
    emit('update:modelValue', next);
}
function groupCheckedCount(g) { return g.perms.filter(p => isChecked(p.id)).length; }
function isGroupAllChecked(g) { return groupCheckedCount(g) === g.perms.length; }
function isGroupPartial(g) { const c = groupCheckedCount(g); return c > 0 && c < g.perms.length; }
function toggleGroup(g) {
    const ids = g.perms.map(p => p.id);
    const all = isGroupAllChecked(g);
    const next = all
        ? props.modelValue.filter(id => !ids.includes(id))
        : Array.from(new Set([...props.modelValue, ...ids]));
    emit('update:modelValue', next);
}
function toggleAllInView() {
    const ids = filteredGroups.value.flatMap(g => g.perms.map(p => p.id));
    const allChecked = ids.length > 0 && ids.every(id => isChecked(id));
    emit('update:modelValue', allChecked ? props.modelValue.filter(id => !ids.includes(id)) : Array.from(new Set([...props.modelValue, ...ids])));
}
function isAllViewChecked() {
    const ids = filteredGroups.value.flatMap(g => g.perms.map(p => p.id));
    return ids.length > 0 && ids.every(id => isChecked(id));
}
function toggleCollapse(module) { collapsed.value[module] = !collapsed.value[module]; }
function isCollapsed(module) { return !!collapsed.value[module]; }

const totalChecked = computed(() => props.modelValue.length);
const totalAll = computed(() => props.permissions.length);
</script>

<template>
    <div class="space-y-3">
        <!-- Toolbar: search + select all -->
        <div class="flex flex-col sm:flex-row gap-2">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400 text-sm"></i>
                </div>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Cari permission (modul / aksi / deskripsi)..."
                    class="w-full pl-9 pr-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 outline-none transition-colors"
                    :class="c.ring"
                />
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-600 dark:text-gray-400 whitespace-nowrap">
                    <span class="font-semibold text-gray-900 dark:text-white">{{ totalChecked }}</span> / {{ totalAll }} dipilih
                </span>
                <button type="button" @click="toggleAllInView"
                    :class="['px-3 py-2 text-xs font-medium rounded-lg border transition-colors whitespace-nowrap',
                        isAllViewChecked()
                            ? 'bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600'
                            : 'bg-gray-900 dark:bg-gray-200 border-gray-900 dark:border-gray-200 text-white dark:text-gray-900 hover:bg-gray-700 dark:hover:bg-white']">
                    <i :class="['fas text-[10px] mr-1', isAllViewChecked() ? 'fa-square' : 'fa-check-square']"></i>
                    {{ isAllViewChecked() ? 'Bersihkan' : 'Pilih Semua' }}
                </button>
            </div>
        </div>

        <!-- Groups -->
        <div class="border border-gray-200 dark:border-gray-700 rounded-xl divide-y divide-gray-200 dark:divide-gray-700 max-h-96 overflow-y-auto">
            <div v-if="filteredGroups.length === 0" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                <i class="fas fa-search-minus text-2xl mb-2 block opacity-50"></i>
                Tidak ada permission yang cocok dengan "{{ search }}".
            </div>
            <div v-for="g in filteredGroups" :key="g.module" :class="['p-3', c.groupBg]">
                <div class="flex items-center justify-between gap-2">
                    <button type="button" @click="toggleCollapse(g.module)"
                        class="flex items-center gap-2 flex-1 text-left group/header">
                        <i :class="['fas text-xs transition-transform', isCollapsed(g.module) ? 'fa-chevron-right' : 'fa-chevron-down', c.groupText]"></i>
                        <span :class="['text-sm font-semibold', c.groupText]">{{ g.label }}</span>
                        <span :class="['inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold',
                            isGroupAllChecked(g) ? c.badgeAll : isGroupPartial(g) ? c.badgeSome : c.badgeNone]">
                            {{ groupCheckedCount(g) }} / {{ g.perms.length }}
                        </span>
                    </button>
                    <label class="flex items-center gap-1.5 cursor-pointer select-none text-xs text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                        <input
                            type="checkbox"
                            :checked="isGroupAllChecked(g)"
                            :indeterminate.prop="isGroupPartial(g)"
                            @change="toggleGroup(g)"
                            class="rounded border-gray-300 dark:border-gray-600"
                            :class="c.check"
                        />
                        <span class="hidden sm:inline">Semua</span>
                    </label>
                </div>
                <div v-show="!isCollapsed(g.module)" class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                    <label v-for="p in g.perms" :key="p.id"
                        :class="['flex items-start gap-2 px-2 py-1.5 rounded-md cursor-pointer text-xs transition-colors border',
                            isChecked(p.id)
                                ? 'bg-white dark:bg-gray-800 border-sky-300 dark:border-sky-700 shadow-sm'
                                : 'bg-white/60 dark:bg-gray-800/60 border-transparent hover:bg-white dark:hover:bg-gray-800 hover:border-gray-300 dark:hover:border-gray-600']">
                        <input
                            type="checkbox"
                            :checked="isChecked(p.id)"
                            @change="toggle(p.id)"
                            class="mt-0.5 rounded border-gray-300 dark:border-gray-600 shrink-0"
                            :class="c.check"
                        />
                        <div class="flex-1 min-w-0">
                            <div :class="['font-medium', isChecked(p.id) ? 'text-gray-900 dark:text-white' : 'text-gray-700 dark:text-gray-300']">
                                {{ getActionLabel(extractAction(p.nama)) }}
                            </div>
                            <div class="text-[10px] text-gray-500 dark:text-gray-400 truncate font-mono" :title="p.nama">{{ p.nama }}</div>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <p class="text-[11px] text-gray-500 dark:text-gray-400">
            <i class="fas fa-info-circle mr-1"></i>
            Klik nama modul untuk collapse/expand. Centang "Semua" di tiap grup untuk memilih semua permission dalam modul.
        </p>
    </div>
</template>
