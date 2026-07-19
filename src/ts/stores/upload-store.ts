import { create } from 'zustand'
import { persist } from 'zustand/middleware'

export type UploadStatus = 'pending' | 'uploading' | 'assembling' | 'success' | 'error'

export interface UploadItem {
  id: string
  file?: File
  fileName: string
  destinationPath: string
  disk: string
  totalSize: number
  uploadedBytes: number
  progress: number // 0 to 100
  status: UploadStatus
  error?: string
}

interface UploadState {
  uploads: UploadItem[]
  addUpload: (upload: Omit<UploadItem, 'uploadedBytes' | 'progress' | 'status'>) => void
  updateProgress: (id: string, uploadedBytes: number, totalSize: number, status?: UploadStatus) => void
  setStatus: (id: string, status: UploadStatus, error?: string) => void
  removeUpload: (id: string) => void
  clearCompleted: () => void
}

export const useUploadStore = create<UploadState>()(
  persist(
    (set) => ({
      uploads: [],

      addUpload: (upload) => set((state) => ({
        uploads: [
          ...state.uploads,
          { ...upload, uploadedBytes: 0, progress: 0, status: 'pending' }
        ]
      })),

      updateProgress: (id, uploadedBytes, totalSize, status) => set((state) => ({
        uploads: state.uploads.map(u => {
          if (u.id === id) {
            // Upload phase maps to 0-50% overall progress. Assembly will map to 50-100%.
            const chunkProgress = Math.round((uploadedBytes / totalSize) * 50);
            return {
              ...u,
              uploadedBytes,
              progress: status === 'assembling' ? u.progress : chunkProgress,
              ...(status && { status })
            }
          }
          return u
        })
      })),

      setStatus: (id, status, error) => set((state) => ({
        uploads: state.uploads.map(u => {
          if (u.id === id) {
            // When success, ensure progress is 100%
            const progress = status === 'success' ? 100 : u.progress;
            return { ...u, status, error, progress }
          }
          return u
        })
      })),

      removeUpload: (id) => set((state) => ({
        uploads: state.uploads.filter(u => u.id !== id)
      })),

      clearCompleted: () => set((state) => ({
        uploads: state.uploads.filter(u => u.status === 'pending' || u.status === 'uploading' || u.status === 'assembling')
      })),
    }),
    {
      name: 'sm-upload-store',
      partialize: (state) => ({
        // We cannot serialize `File` objects to JSON.
        // We omit the file, and mark any unfinished uploads as 'error'
        // since they were interrupted by the page reload.
        uploads: state.uploads.map(u => {
          const { file: _file, ...rest } = u;
          if (['pending', 'uploading', 'assembling'].includes(rest.status)) {
            return { ...rest, status: 'error' as const, error: 'Upload interrupted by page reload' };
          }
          return rest;
        })
      })
    }
  )
)
