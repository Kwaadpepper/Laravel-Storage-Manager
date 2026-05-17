import { uniqueId } from '@ts/tools';
import { create } from 'zustand';

type ToastId = string & { type: 'ToastId' }

export interface Toast {
  type: 'success' | 'error' | 'info' | 'warning'
  title?: string
  message: string
}

interface InnerToast extends Toast {
  id: ToastId
  persistent: boolean
}

type ToastState = {
  toasts: InnerToast[]
  pushToast: (toast: Toast, options?: { persistent?: boolean }) => InnerToast
  pullToast: (id?: ToastId) => void
}

function generateToastId(): ToastId {
  return uniqueId() as ToastId
}

export const useToastStore = create<ToastState>((set) => ({
  toasts: [],

  pushToast: function (toast, options) {
    const newToast: InnerToast = {
      ...toast, id: generateToastId(),
      persistent: options?.persistent ?? false
    };
    set((state) => {
      return {
        toasts: [
          ...state.toasts,
          newToast
        ]
      };
    });

    return newToast;
  },
  pullToast: (id?: ToastId) => set((state) => ({
    toasts: id
      ? state.toasts.filter((toast) => toast.id !== id)
      : state.toasts.slice(1)
  })),
}))
