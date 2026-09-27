import { defineStore } from 'pinia';
import { ref } from 'vue';

export type ActionStatus = 'pending' | 'success' | 'error';
export type ActionType = 'DELETE' | 'MOVE' | 'COPY' | 'RENAME' | 'CREATE' | 'ZIP' | 'UNZIP';

export interface ActionItem {
  id: string;
  type: ActionType;
  sourcePath: string;
  destinationPath?: string;
  status: ActionStatus;
  error?: string;
}

export const useActionStore = defineStore('action', () => {
  const actions = ref<ActionItem[]>([]);

  function addAction(action: Omit<ActionItem, 'status'>): void {
    actions.value.push({ ...action, status: 'pending' });
  }

  function markActionComplete(id: string, status: 'success' | 'error', error?: string): void {
    actions.value = actions.value.map(a =>
      a.id === id ? { ...a, status, error } : a
    );
  }

  function removeAction(id: string): void {
    actions.value = actions.value.filter(a => a.id !== id);
  }

  function clearCompleted(): void {
    actions.value = actions.value.filter(a => a.status === 'pending');
  }

  return {
    actions,
    addAction,
    markActionComplete,
    removeAction,
    clearCompleted,
  };
});

export type ActionStore = ReturnType<typeof useActionStore>;
