import { AppConfig } from '@ts/schemas';
import { Disk } from '@ts/types';
import { defineStore } from 'pinia';
import { ref } from 'vue';

export interface AppAuthor {
  name: string;
  email: string;
}

export interface RoutesConfig {
  fmInit: string;
  fmTree: string;
  fmContent: string;
  fmProperties: string;
  fmCreateDirectory: string;
  fmCreateFile: string;
  fmDelete: string;
  fmRename: string;
  fmUploadInit: string;
  fmUploadChunk: string;
  fmUploadComplete: string;
  fmUploadStatus: string;
  disksList: string;
  disksSelect: string;
}

export const useConfigStore = defineStore('config', () => {
  const packageName = ref('');
  const packageVersion = ref('');
  const packageLogo = ref('');
  const composerPackageName = ref('');
  const appDescription = ref('');
  const appAuthors = ref<AppAuthor[]>([]);
  const disks = ref<Disk[]>([]);
  const readOnlyDisks = ref<Disk[]>([]);
  const routes = ref<RoutesConfig>({
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
    disksSelect: '',
  });
  const chunkMinSize = ref(1048576 * 2);
  const chunkMaxSize = ref(1048576 * 20);
  const duplicatePolicy = ref<'overwrite' | 'auto_rename' | 'error'>('auto_rename');
  const sanitizeFilenames = ref(true);

  function initialize(config: AppConfig): void {
    packageName.value = config.packageName;
    packageVersion.value = config.packageVersion;
    packageLogo.value = config.packageLogo;
    composerPackageName.value = config.composerPackageName;
    appDescription.value = config.appDescription;
    appAuthors.value = config.appAuthors;
    disks.value = config.disks;
    readOnlyDisks.value = config.readOnlyDisks;
    routes.value = config.routes;
    chunkMinSize.value = config.chunkMinSize;
    chunkMaxSize.value = config.chunkMaxSize;
    duplicatePolicy.value = config.duplicatePolicy;
    sanitizeFilenames.value = config.sanitizeFilenames;
  }

  return {
    packageName,
    packageVersion,
    packageLogo,
    composerPackageName,
    appDescription,
    appAuthors,
    disks,
    readOnlyDisks,
    routes,
    chunkMinSize,
    chunkMaxSize,
    duplicatePolicy,
    sanitizeFilenames,
    initialize,
  };
});

export type ConfigStore = ReturnType<typeof useConfigStore>;
