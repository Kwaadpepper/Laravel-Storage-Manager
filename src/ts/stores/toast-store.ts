import { uniqueId } from '@ts/tools';
import { defineStore } from 'pinia';
import { ref } from 'vue';

export type ToastId = string & { type: 'ToastId' };

export interface Toast {
  type: 'success' | 'error' | 'info' | 'warning';
  title?: string;
  message: string;
  action?: {
    label: string;
    onClick: () => void | Promise<void>;
  };
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
  const defaultTimeout = 5000;

  function pushToast(
    toast: Toast,
    options?: { timeout?: number; persistent?: boolean }
  ): InnerToast {
    const newToast: InnerToast = {
      ...toast,
      id: generateToastId(),
      persistent: options?.persistent ?? false,
    };
    toasts.value.push(newToast);

    if (!options?.persistent || options?.timeout) {
      setTimeout(() => {
        pullToast(newToast.id);
      }, options?.timeout ?? defaultTimeout);
    }

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
    push: pushToast,
    pullToast,
  };
});

export type ToastStore = ReturnType<typeof useToastStore>;
