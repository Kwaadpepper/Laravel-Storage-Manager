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
  routes: {
    fmInit: string
    fmTree: string
    fmContent: string
    fmProperties: string
    fmCreateDirectory: string
    fmCreateFile: string
    fmDelete: string
    fmRename: string
    disksList: string
    disksSelect: string
  },
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
  routes: {
    fmInit: '',
    fmTree: '',
    fmContent: '',
    fmProperties: '',
    fmCreateDirectory: '',
    fmCreateFile: '',
    fmDelete: '',
    fmRename: '',
    disksList: '',
    disksSelect: ''
  },
  initialize: (config: AppConfig) => set(config)
}))
