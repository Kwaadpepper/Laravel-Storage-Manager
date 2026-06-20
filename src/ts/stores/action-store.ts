import { create } from 'zustand'

export type ActionStatus = 'pending' | 'success' | 'error'
export type ActionType = 'DELETE' | 'MOVE' | 'COPY'

export interface ActionItem {
  id: string
  type: ActionType
  sourcePath: string
  destinationPath?: string
  status: ActionStatus
  error?: string
}

interface ActionState {
  actions: ActionItem[]
  addAction: (action: Omit<ActionItem, 'status'>) => void
  markActionComplete: (id: string, status: 'success' | 'error', error?: string) => void
  removeAction: (id: string) => void
  clearCompleted: () => void
}

export const useActionStore = create<ActionState>((set) => ({
  actions: [],

  addAction: (action) => set((state) => ({
    actions: [
      ...state.actions,
      { ...action, status: 'pending' }
    ]
  })),

  markActionComplete: (id, status, error) => set((state) => ({
    actions: state.actions.map(a => 
      a.id === id ? { ...a, status, error } : a
    )
  })),

  removeAction: (id) => set((state) => ({
    actions: state.actions.filter(a => a.id !== id)
  })),

  clearCompleted: () => set((state) => ({
    actions: state.actions.filter(a => a.status === 'pending')
  })),
}))
