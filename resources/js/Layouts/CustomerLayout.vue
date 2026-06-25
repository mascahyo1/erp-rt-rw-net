<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();

const user = computed(() => page.props.auth?.user);
const userName = computed(() => user.value?.name ?? 'Pelanggan');
const userEmail = computed(() => user.value?.email ?? '');

const sidebarOpen = ref(false);
const profileDropdownOpen = ref(false);

const menuItems = [
  { label: 'Dashboard', href: '/customer/dashboard', icon: 'fa-tachometer-alt' },
  { label: 'Profil Saya', href: '/customer/profil-saya', icon: 'fa-user' },
  { label: 'Daftar Paket', href: '/customer/daftar-paket', icon: 'fa-th-large' },
  { label: 'Paket Saya', href: '/customer/paket-saya', icon: 'fa-box' },
  { label: 'Tagihan Saya', href: '/customer/tagihan-saya', icon: 'fa-file-invoice' },
  { label: 'Riwayat Pembayaran', href: '/customer/riwayat-pembayaran', icon: 'fa-history' },
  { label: 'Lapor Gangguan', href: '/customer/gangguan', icon: 'fa-triangle-exclamation' },
];

const currentPath = computed(() => page.url);
function isActive(href) { return currentPath.value === href; }
function closeMobileMenu() { sidebarOpen.value = false; }

const theme = ref(localStorage.getItem('theme') || 'system');
const isDark = computed(() => theme.value === 'dark' || (theme.value === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches));
const themeIcon = computed(() => theme.value === 'light' ? 'fa-sun' : theme.value === 'dark' ? 'fa-moon' : 'fa-circle-half-stroke');
function applyTheme() { document.documentElement.classList.toggle('dark', isDark.value); }
function toggleTheme() { if (theme.value === 'light') theme.value = 'dark'; else if (theme.value === 'dark') theme.value = 'system'; else theme.value = 'light'; localStorage.setItem('theme', theme.value); applyTheme(); }
let mediaQuery;
onMounted(() => { applyTheme(); mediaQuery = window.matchMedia('(prefers-color-scheme: dark)'); mediaQuery.addEventListener('change', applyTheme); });
onUnmounted(() => mediaQuery?.removeEventListener('change', applyTheme));
</script>

<template>
  <div class="min-h-screen flex bg-gray-100 dark:bg-gray-950 transition-colors">
    <!-- Mobile overlay -->
    <div v-show="sidebarOpen" class="fixed inset-0 z-30 bg-black/50 lg:hidden" @click="closeMobileMenu"></div>

    <!-- Sidebar -->
    <aside :class="['fixed inset-y-0 left-0 z-40 flex flex-col bg-gradient-to-b from-emerald-700 via-emerald-800 to-teal-900 text-white transition-all duration-300', sidebarOpen ? 'w-64' : '-translate-x-full lg:translate-x-0 lg:w-64']">
      <div class="flex items-center justify-between h-16 px-4 border-b border-white/10 shrink-0">
        <Link href="/customer/dashboard" class="flex items-center gap-2">
          <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center shrink-0"><i class="fas fa-user text-white text-sm"></i></div>
          <span class="font-bold text-sm whitespace-nowrap">Pelanggan</span>
        </Link>
        <button @click="sidebarOpen = false" class="p-1.5 rounded-lg hover:bg-white/10 transition-colors lg:hidden"><i class="fas fa-times text-xs"></i></button>
      </div>
      <nav class="flex-1 overflow-y-auto py-4 px-2 space-y-1 sidebar-nav">
        <Link v-for="item in menuItems" :key="item.href" :href="item.href" @click="closeMobileMenu" :class="['flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors', isActive(item.href) ? 'bg-white/20 text-white' : 'text-emerald-100 hover:bg-white/10 hover:text-white']">
          <i :class="['fas', item.icon, 'w-5 text-center shrink-0']"></i>
          <span>{{ item.label }}</span>
        </Link>
      </nav>
      <div class="p-4 border-t border-white/10">
        <Link href="/" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-emerald-100 hover:bg-white/10 hover:text-white transition-colors">
          <i class="fas fa-arrow-left w-5 text-center shrink-0"></i><span>Kembali ke Landing</span>
        </Link>
      </div>
    </aside>

    <!-- Main -->
    <div class="flex-1 flex flex-col min-w-0 min-h-screen lg:ml-64">
      <!-- Topbar -->
      <header class="sticky top-0 z-20 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 shadow-sm">
        <div class="flex items-center justify-between h-16 px-4 lg:px-6">
          <div class="flex items-center gap-3">
            <button @click="sidebarOpen = true" class="p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors lg:hidden"><i class="fas fa-bars text-lg"></i></button>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-white"><slot name="header" /></h1>
          </div>
          <div class="flex items-center gap-2">
            <button @click="toggleTheme" class="p-2 rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors" :title="`Tema: ${theme}`"><i :class="['fas', themeIcon, 'text-lg']"></i></button>
            <div class="relative">
              <button @click="profileDropdownOpen = !profileDropdownOpen" class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                <i class="fas fa-user-circle text-lg"></i><span class="hidden sm:inline font-medium">{{ userName }}</span><i :class="['fas text-xs transition-transform', profileDropdownOpen ? 'fa-chevron-up' : 'fa-chevron-down']"></i>
              </button>
              <Transition name="dropdown"><div v-show="profileDropdownOpen" class="absolute right-0 mt-2 w-48 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-lg py-1 z-50">
                <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700"><p class="text-sm font-medium text-gray-900 dark:text-white">{{ userName }}</p><p class="text-xs text-gray-500 dark:text-gray-400">{{ userEmail }}</p></div>
                <Link href="/customer/profil-saya" @click.stop="profileDropdownOpen = false" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fas fa-user w-4 text-center"></i> Profil Saya</Link>
                <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>
                <Link href="/logout-pelanggan" method="post" as="button" @click.stop="profileDropdownOpen = false" class="flex items-center gap-2 px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors w-full text-left"><i class="fas fa-sign-out-alt w-4 text-center"></i> Logout</Link>
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
