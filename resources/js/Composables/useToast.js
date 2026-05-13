import { ref } from 'vue';

const toasts = ref([]);
let nextId = 1;

const defaultDuration = 4000;

export function useToast() {
  function addToast(message, type = 'success', duration = defaultDuration) {
    const id = nextId++;
    toasts.value.push({ id, message, type, createdAt: Date.now() });

    if (duration > 0) {
      setTimeout(() => {
        remove(id);
      }, duration);
    }

    return id;
  }

  function remove(id) {
    const idx = toasts.value.findIndex(t => t.id === id);
    if (idx !== -1) {
      toasts.value.splice(idx, 1);
    }
  }

  function success(message, duration) {
    return addToast(message, 'success', duration);
  }

  function error(message, duration) {
    return addToast(message, 'error', duration);
  }

  function warning(message, duration) {
    return addToast(message, 'warning', duration);
  }

  function info(message, duration) {
    return addToast(message, 'info', duration);
  }

  return {
    toasts,
    addToast,
    remove,
    success,
    error,
    warning,
    info,
  };
}
