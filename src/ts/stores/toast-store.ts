import { uniqueId } from '@ts/tools';
import { defineStore } from 'pinia';
import { ref } from 'vue';

export type ToastId = string & { type: 'ToastId' };

export interface Toast {
  type: 'success' | 'error' | 'info' | 'warning';
  title?: string;
  message: string;
}

export interface InnerToast extends Toast {
  id: ToastId;
  persistent: boolean;
}

function generateToastId(): ToastId {
  return uniqueId() as ToastId;
}

export const useToastStore = defineStore('toast', () => {
  const toasts = ref<InnerToast[]>([]);

  function pushToast(toast: Toast, options?: { persistent?: boolean }): InnerToast {
    const newToast: InnerToast = {
      ...toast,
      id: generateToastId(),
      persistent: options?.persistent ?? false,
    };
    toasts.value.push(newToast);
    return newToast;
  }

  function pullToast(id?: ToastId): void {
    if (id) {
      toasts.value = toasts.value.filter((t) => t.id !== id);
    } else {
      toasts.value.shift();
    }
  }

  return {
    toasts,
    pushToast,
    pullToast,
  };
});

export type ToastStore = ReturnType<typeof useToastStore>;
