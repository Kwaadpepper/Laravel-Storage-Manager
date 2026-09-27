import { HttpClient } from '@ts/clients';
import {
  ApiService,
  ClipboardService,
  ContextualMenuService,
  DiskService,
  DownloadService,
  ErrorHandlerService,
  EventQueueService,
  FileManagerService,
  LocationService,
  NavigationService,
  ThemeService,
  ToastService,
  UploadService,
} from '@ts/services';
import {
  useActionStore,
  useClipboardStore,
  useConfigStore,
  useContextualMenuStore,
  useDiskStore,
  useFileManagerStore,
  useToastStore,
  useTreeStore,
  useUiStore,
  useUploadStore,
} from '@ts/stores';
import type { InjectionKey } from 'vue';
import { inject } from 'vue';

export interface ServiceContainer {
  fileManagerService: FileManagerService;
  navigationService: NavigationService;
  apiService: ApiService;
  httpClient: HttpClient;
  toastService: ToastService;
  contextualMenuService: ContextualMenuService;
  errorHandlerService: ErrorHandlerService;
  downloadService: DownloadService;
  themeService: ThemeService;
  locationService: LocationService;
  diskService: DiskService;
  clipboardService: ClipboardService;
  eventQueueService: EventQueueService;
  uploadService: UploadService;
}

export const serviceContainerKey: InjectionKey<ServiceContainer> = Symbol('ServiceContainer');

function getApiBaseUrl(): URL {
  const metaTag = document.querySelector('meta[name="storage-manager-url"]');
  if (!metaTag) {
    throw new Error('Meta tag with name "storage-manager-url" not found');
  }
  const content = metaTag.getAttribute('content');
  if (!content) {
    throw new Error('Meta tag with name "storage-manager-url" has no content');
  }
  return new URL(content);
}

export function buildServiceContainer(): ServiceContainer {
  const apiBaseUrl = getApiBaseUrl();
  const configStore = useConfigStore();
  const diskStore = useDiskStore();
  const fileManagerStore = useFileManagerStore();
  const treeStore = useTreeStore();
  const toastStore = useToastStore();
  const contextualMenuStore = useContextualMenuStore();
  const uiStore = useUiStore();
  const clipboardStore = useClipboardStore();
  const actionStore = useActionStore();
  const uploadStore = useUploadStore();

  const httpClient = new HttpClient(apiBaseUrl);
  const apiService = new ApiService(httpClient);
  const locationService = new LocationService();
  const diskService = new DiskService(apiService, configStore, diskStore);
  const fileManagerService = new FileManagerService(apiService, diskStore);
  const navigationService = new NavigationService(
    fileManagerStore,
    fileManagerService,
    treeStore,
    locationService,
    diskStore,
    configStore
  );
  const toastService = new ToastService(toastStore);
  const errorHandlerService = new ErrorHandlerService(toastService);
  const contextualMenuService = new ContextualMenuService(contextualMenuStore);
  const downloadService = new DownloadService(diskStore);
  const themeService = new ThemeService(uiStore);
  const clipboardService = new ClipboardService(clipboardStore);
  const eventQueueService = new EventQueueService(navigationService, actionStore, diskService);
  const uploadService = new UploadService(
    apiService,
    navigationService,
    uploadStore,
    configStore,
    fileManagerStore,
    diskService
  );

  return {
    fileManagerService,
    navigationService,
    apiService,
    httpClient,
    toastService,
    contextualMenuService,
    errorHandlerService,
    downloadService,
    themeService,
    locationService,
    diskService,
    clipboardService,
    eventQueueService,
    uploadService,
  };
}

export function useService<K extends keyof ServiceContainer>(key: K): ServiceContainer[K] {
  const container = inject(serviceContainerKey);
  if (!container) {
    throw new Error(`ServiceContainer not provided when requesting service "${String(key)}"`);
  }
  return container[key];
}

export function useContainer(): ServiceContainer {
  const container = inject(serviceContainerKey);
  if (!container) {
    throw new Error('ServiceContainer not provided');
  }
  return container;
}
