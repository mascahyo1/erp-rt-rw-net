<script setup>
import { useToast } from '@/Composables/useToast';

const { toasts, remove } = useToast();

function iconClass(type) {
  switch (type) {
    case 'success': return 'fa-check-circle';
    case 'error': return 'fa-exclamation-circle';
    case 'warning': return 'fa-exclamation-triangle';
    case 'info': return 'fa-info-circle';
    default: return 'fa-info-circle';
  }
}

function borderColor(type) {
  switch (type) {
    case 'success': return 'border-emerald-400 dark:border-emerald-500';
    case 'error': return 'border-red-400 dark:border-red-500';
    case 'warning': return 'border-amber-400 dark:border-amber-500';
    case 'info': return 'border-sky-400 dark:border-sky-500';
    default: return 'border-gray-400';
  }
}

function bgColor(type) {
  switch (type) {
    case 'success': return 'bg-emerald-50 dark:bg-emerald-900/40';
    case 'error': return 'bg-red-50 dark:bg-red-900/40';
    case 'warning': return 'bg-amber-50 dark:bg-amber-900/40';
    case 'info': return 'bg-sky-50 dark:bg-sky-900/40';
    default: return 'bg-white dark:bg-gray-800';
  }
}

function iconTextColor(type) {
  switch (type) {
    case 'success': return 'text-emerald-500';
    case 'error': return 'text-red-500';
    case 'warning': return 'text-amber-500';
    case 'info': return 'text-sky-500';
    default: return 'text-gray-500';
  }
}
</script>

<template>
  <Teleport to="body">
    <div class="fixed top-4 right-4 z-[100] flex flex-col gap-2 max-w-sm w-full pointer-events-none">
      <TransitionGroup name="toast">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          :class="[
            'pointer-events-auto flex items-start gap-3 px-4 py-3 rounded-xl shadow-lg border-l-4 backdrop-blur-sm transition-all duration-300',
            bgColor(toast.type),
            borderColor(toast.type),
          ]"
        >
          <div class="shrink-0 mt-0.5">
            <i :class="['fas', iconClass(toast.type), 'text-lg', iconTextColor(toast.type)]"></i>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ toast.message }}</p>
          </div>
          <button
            @click="remove(toast.id)"
            class="shrink-0 p-0.5 rounded text-gray-400 hover:text-gray-600 dark:hover:text-white transition-colors"
          >
            <i class="fas fa-times text-xs"></i>
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<style scoped>
.toast-enter-active {
  transition: all 0.3s ease;
}
.toast-leave-active {
  transition: all 0.2s ease;
}
.toast-enter-from {
  opacity: 0;
  transform: translateX(100%);
}
.toast-leave-to {
  opacity: 0;
  transform: translateX(100%);
}
</style>
