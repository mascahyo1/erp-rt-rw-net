<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();

const user = computed(() => page.props.auth?.user);
const companyName = computed(() => user.value?.company?.name ?? 'Perusahaan');
const userName = computed(() => user.value?.name ?? 'Admin');
const userEmail = computed(() => user.value?.email ?? '');
const perms = computed(() => {
  const p = page.props.permissions;
  return Array.isArray(p) ? [...p] : [];
});

const sidebarVisible = ref(false);
const sidebarExpanded = ref(true);
const profileDropdownOpen = ref(false);

function toggleSidebar() {
  if (!sidebarVisible.value) { sidebarVisible.value = true; sidebarExpanded.value = true; }
  else if (sidebarExpanded.value) { sidebarExpanded.value = false; }
  else { sidebarVisible.value = false; }
}
function closeSidebarOnMobile() { if (window.innerWidth < 1024) sidebarVisible.value = false; }

const menuItems = computed(() => {
  const items = [
    { label: 'Dashboard', href: '/operator-perusahaan/dashboard', icon: 'fa-tachometer-alt' },
  ];
  if (perms.value.includes('perusahaan-saya.detail')) items.push({ label: 'Perusahaan Saya', href: '/operator-perusahaan/perusahaan-saya', icon: 'fa-building' });
  if (perms.value.includes('paket.list')) items.push({ label: 'Daftar Paket', href: '/operator-perusahaan/daftar-paket', icon: 'fa-box' });
  if (perms.value.includes('customer.list')) items.push({ label: 'Customer', href: '/operator-perusahaan/customer', icon: 'fa-users' });
  if (perms.value.includes('langganan.list')) items.push({ label: 'Langganan Customer', href: '/operator-perusahaan/langganan-customer', icon: 'fa-link' });
  if (perms.value.includes('tagihan.list')) items.push({ label: 'Tagihan', href: '/operator-perusahaan/tagihan', icon: 'fa-file-invoice' });
  if (perms.value.includes('insentif.list')) items.push({ label: 'Insentif', href: '/operator-perusahaan/insentif', icon: 'fa-coins' });
  if (perms.value.includes('riwayat-insentif.list')) items.push({ label: 'Riwayat Insentif', href: '/operator-perusahaan/riwayat-insentif', icon: 'fa-receipt' });
  if (perms.value.includes('riwayat-pembayaran.list')) items.push({ label: 'Riwayat Pembayaran', href: '/operator-perusahaan/riwayat-pembayaran', icon: 'fa-history' });
  if (perms.value.includes('admin-perusahaan.list')) items.push({ label: 'Admin Perusahaan', href: '/operator-perusahaan/admin-perusahaan', icon: 'fa-user-tie' });
  if (perms.value.includes('role-perusahaan-op.list')) items.push({ label: 'Role Perusahaan', href: '/operator-perusahaan/role-perusahaan', icon: 'fa-tags' });
  if (perms.value.includes('admin-role-perusahaan-op.list')) items.push({ label: 'Admin Role Perusahaan', href: '/operator-perusahaan/admin-role-perusahaan', icon: 'fa-user-gear' });
  if (perms.value.includes('karyawan.list')) items.push({ label: 'Karyawan', href: '/operator-perusahaan/karyawan', icon: 'fa-users' });
  if (perms.value.includes('role-web-karyawan.list')) items.push({ label: 'Role Web Karyawan', href: '/operator-perusahaan/role-web-karyawan', icon: 'fa-globe' });
  if (perms.value.includes('admin-role-web-karyawan.list')) items.push({ label: 'Admin Role Web Karyawan', href: '/operator-perusahaan/admin-role-web-karyawan', icon: 'fa-user-lock' });
  if (perms.value.includes('konfigurasi-perusahaan.list')) items.push({ label: 'Konfigurasi Perusahaan', href: '/operator-perusahaan/konfigurasi-perusahaan', icon: 'fa-sliders' });
  return items;
});

const currentPath = computed(() => page.url);
function isActive(href) { return currentPath.value === href; }

const theme = ref(localStorage.getItem('theme') || 'system');
const isDark = computed(() => theme.value === 'dark' || (theme.value === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches));
const themeIcon = computed(() => theme.value === 'light' ? 'fa-sun' : theme.value === 'dark' ? 'fa-moon' : 'fa-circle-half-stroke');
function applyTheme() { document.documentElement.classList.toggle('dark', isDark.value); }
function toggleTheme() { if (theme.value === 'system') theme.value = 'light'; else if (theme.value === 'light') theme.value = 'dark'; else theme.value = 'system'; localStorage.setItem('theme', theme.value); applyTheme(); }
let mediaQuery;
onMounted(() => { applyTheme(); mediaQuery = window.matchMedia('(prefers-color-scheme: dark)'); mediaQuery.addEventListener('change', applyTheme); });
onUnmounted(() => mediaQuery?.removeEventListener('change', applyTheme));
</script>

<template>
  <div class="min-h-screen flex bg-gray-100 dark:bg-gray-950 transition-colors">
    <div v-show="sidebarVisible" class="fixed inset-0 z-30 bg-black/50 lg:hidden" @click="closeSidebarOnMobile"></div>

    <aside :class="['fixed inset-y-0 left-0 z-40 flex flex-col bg-gradient-to-b from-sky-700 via-sky-800 to-blue-900 text-white transition-all duration-300', sidebarVisible ? (sidebarExpanded ? 'w-64' : 'w-16') : '-translate-x-full lg:translate-x-0 lg:w-0 lg:overflow-hidden']">
      <div class="flex items-center justify-between h-16 px-4 border-b border-white/10 shrink-0">
        <Link href="/operator-perusahaan/dashboard" class="flex items-center gap-2 overflow-hidden">
          <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center shrink-0"><i class="fas fa-building text-white text-sm"></i></div>
          <span :class="['font-bold text-sm whitespace-nowrap transition-opacity', sidebarExpanded ? 'opacity-100' : 'opacity-0 hidden']">Perusahaan</span>
        </Link>
        <button @click="sidebarExpanded = !sidebarExpanded" class="p-1.5 rounded-lg hover:bg-white/10 transition-colors shrink-0 hidden lg:block" :title="sidebarExpanded ? 'Collapse' : 'Expand'">
          <i :class="['fas', sidebarExpanded ? 'fa-chevron-left' : 'fa-chevron-right', 'text-xs']"></i>
        </button>
      </div>
      <nav class="flex-1 overflow-y-auto py-4 px-2 space-y-1 sidebar-nav">
        <Link v-for="item in menuItems" :key="item.href" :href="item.href" @click="closeSidebarOnMobile" :class="['flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors', isActive(item.href) ? 'bg-white/20 text-white' : 'text-sky-100 hover:bg-white/10 hover:text-white']" :title="!sidebarExpanded ? item.label : ''">
          <i :class="['fas', item.icon, 'w-5 text-center shrink-0']"></i>
          <span :class="['whitespace-nowrap transition-opacity', sidebarExpanded ? 'opacity-100' : 'opacity-0 hidden']">{{ item.label }}</span>
        </Link>
      </nav>
      <div class="p-4 border-t border-white/10">
        <Link href="/" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-sky-100 hover:bg-white/10 hover:text-white transition-colors" :title="!sidebarExpanded ? 'Kembali ke Landing' : ''">
          <i class="fas fa-arrow-left w-5 text-center shrink-0"></i>
          <span :class="['whitespace-nowrap transition-opacity', sidebarExpanded ? 'opacity-100' : 'opacity-0 hidden']">Kembali ke Landing</span>
        </Link>
      </div>
    </aside>

    <div :class="['flex-1 flex flex-col min-w-0 min-h-screen transition-all duration-300', sidebarVisible ? (sidebarExpanded ? 'lg:ml-64' : 'lg:ml-16') : 'lg:ml-0']">
      <header class="sticky top-0 z-30 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 shadow-sm">
        <div class="flex items-center justify-between h-16 px-4 lg:px-6">
          <div class="flex items-center gap-3">
            <button @click="toggleSidebar" class="p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors" title="Toggle sidebar"><i class="fas fa-bars text-lg"></i></button>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-white hidden sm:block"><slot name="header">Perusahaan</slot></h1>
          </div>
          <div class="flex items-center gap-2">
            <button @click="toggleTheme" class="p-2 rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors" :title="`Tema: ${theme}`"><i :class="['fas', themeIcon, 'text-lg']"></i></button>
            <div class="relative">
              <button @click="profileDropdownOpen = !profileDropdownOpen" class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                <i class="fas fa-user-circle text-lg"></i><span class="hidden sm:inline font-medium">{{ companyName }}</span><i :class="['fas text-xs transition-transform', profileDropdownOpen ? 'fa-chevron-up' : 'fa-chevron-down']"></i>
              </button>
              <Transition name="dropdown"><div v-show="profileDropdownOpen" class="absolute right-0 mt-2 w-48 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-lg py-1 z-50">
                <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700"><p class="text-sm font-medium text-gray-900 dark:text-white">{{ companyName }}</p><p class="text-xs text-gray-500 dark:text-gray-400">{{ userEmail }}</p></div>
                <Link v-if="perms.includes('perusahaan-saya.detail')" href="/operator-perusahaan/perusahaan-saya" @click.stop="profileDropdownOpen = false" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-building w-4 text-center"></i> Perusahaan Saya</Link>
                <Link href="/operator-perusahaan/dashboard" @click.stop="profileDropdownOpen = false" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-user-cog w-4 text-center"></i> Profil Saya</Link>
                <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>
                <Link href="/logout-perusahaan" method="post" as="button" @click.stop="profileDropdownOpen = false" class="flex items-center gap-2 px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors w-full text-left"><i class="fas fa-sign-out-alt w-4 text-center"></i> Logout</Link>
              </div></Transition>
            </div>
          </div>
        </div>
      </header>
      <main class="flex-1 py-6 px-4 sm:px-6 lg:px-8 w-full max-w-full overflow-x-hidden">
        <Transition name="page" mode="out-in">
          <div :key="page.url">
            <slot />
          </div>
        </Transition>
      </main>
    </div>
  </div>
</template>

<style scoped>
.page-enter-active,
.page-leave-active {
  transition: opacity 0.2s ease;
}
.page-enter-from,
.page-leave-to {
  opacity: 0;
}

.dropdown-enter-active, .dropdown-leave-active { transition: opacity 0.15s ease, transform 0.15s ease; }
.dropdown-enter-from, .dropdown-leave-to { opacity: 0; transform: scale(0.95); }

/* Sidebar scrollbar */
.sidebar-nav::-webkit-scrollbar {
  width: 6px;
}
.sidebar-nav::-webkit-scrollbar-track {
  background: transparent;
}
.sidebar-nav::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.2);
  border-radius: 9999px;
}
.sidebar-nav::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.35);
}
/* Firefox */
.sidebar-nav {
  scrollbar-width: thin;
  scrollbar-color: rgba(255, 255, 255, 0.2) transparent;
}
</style>
