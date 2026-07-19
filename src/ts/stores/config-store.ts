import { AppConfig } from '@ts/schemas';
import { Disk } from '@ts/types';
import { create } from 'zustand';

interface AppAuthor {
  name: string
  email: string
}

interface ConfigState {
  packageName: string
  packageVersion: string
  packageLogo: string
  composerPackageName: string
  appDescription: string
  appAuthors: AppAuthor[]
  disks: Disk[]
  readOnlyDisks: Disk[]
  routes: {
    fmInit: string
    fmTree: string
    fmContent: string
    fmProperties: string
    fmCreateDirectory: string
    fmCreateFile: string
    fmDelete: string
    fmRename: string
    fmUploadInit: string
    fmUploadChunk: string
    fmUploadComplete: string
    fmUploadStatus: string
    disksList: string
    disksSelect: string
  },
  chunkMinSize: number
  chunkMaxSize: number
  initialize: (config: AppConfig) => void
}

export const useConfigStore = create<ConfigState>((set) => ({
  packageName: '',
  packageVersion: '',
  packageLogo: '',
  composerPackageName: '',
  appDescription: '',
  appAuthors: [],
  disks: [],
  readOnlyDisks: [],
  routes: {
    fmInit: '',
    fmTree: '',
    fmContent: '',
    fmProperties: '',
    fmCreateDirectory: '',
    fmCreateFile: '',
    fmDelete: '',
    fmRename: '',
    fmUploadInit: '',
    fmUploadChunk: '',
    fmUploadComplete: '',
    fmUploadStatus: '',
    disksList: '',
    disksSelect: ''
  },
  chunkMinSize: 1048576 * 2, // Default 2MB
  chunkMaxSize: 1048576 * 20, // Default 20MB
  initialize: (config: AppConfig) => set(config)
}))
