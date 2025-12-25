import { create } from 'zustand';

export type ToastType = 'success' | 'error' | 'warning' | 'info';

export interface Toast {
  id: string;
  type: ToastType;
  message: string;
  duration?: number;
}

interface ToastState {
  toasts: Toast[];
  addToast: (toast: Omit<Toast, 'id'>) => void;
  removeToast: (id: string) => void;
  clearToasts: () => void;
}

export const useToastStore = create<ToastState>((set) => ({
  toasts: [],

  addToast: (toast) => {
    const id = `toast-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
    const newToast: Toast = {
      ...toast,
      id,
      duration: toast.duration ?? 5000,
    };

    set((state) => ({
      toasts: [...state.toasts, newToast],
    }));

    // Auto-dismiss after duration
    const duration = newToast.duration ?? 5000;
    if (duration > 0) {
      setTimeout(() => {
        set((state) => ({
          toasts: state.toasts.filter((t) => t.id !== id),
        }));
      }, duration);
    }
  },

  removeToast: (id) =>
    set((state) => ({
      toasts: state.toasts.filter((t) => t.id !== id),
    })),

  clearToasts: () => set({ toasts: [] }),
}));

// Helper functions for easier toast creation
export const toast = {
  success: (message: string, duration?: number) =>
    useToastStore.getState().addToast({ type: 'success', message, duration }),

  error: (message: string, duration?: number) =>
    useToastStore.getState().addToast({ type: 'error', message, duration }),

  warning: (message: string, duration?: number) =>
    useToastStore.getState().addToast({ type: 'warning', message, duration }),

  info: (message: string, duration?: number) =>
    useToastStore.getState().addToast({ type: 'info', message, duration }),
};
