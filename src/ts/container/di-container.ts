import { HttpClient } from '@ts/clients';
import { ApiService, ClipboardService, DiskService, ErrorHandlerService, EventQueueService, LocationService, NavigationService, ThemeService, ToastService, UploadService } from '@ts/services';
import { ContextualMenuService } from '@ts/services/contextual-menu-service';
import { DownloadService } from '@ts/services/download-service';
import { FileManagerService } from '@ts/services/file-manager-service';
import { useClipboardStore, useConfigStore, useContextualMenuStore, useDiskStore, useFileManagerStore, useToastStore, useTreeStore, useUiStore } from '@ts/stores';
import { asFunction, createContainer, InjectionMode } from 'awilix';

export type AppContainer = {
  fileManagerService: FileManagerService
  navigationService: NavigationService
  apiService: ApiService
  httpClient: HttpClient
  toastService: ToastService
  contextualMenuService: ContextualMenuService
  errorHandlerService: ErrorHandlerService
  downloadService: DownloadService
  themeService: ThemeService
  locationService: LocationService
  diskService: DiskService
  clipboardService: ClipboardService
  eventQueueService: EventQueueService
  uploadService: UploadService
}

const apiBaseUrl: URL =
  (() => {
    const metaTag = document.querySelector('meta[name="storage-manager-url"]')
    if (!metaTag) {
      throw new Error('Meta tag with name "storage-manager-url" not found')
    }
    const content = metaTag.getAttribute('content')
    if (!content) {
      throw new Error('Meta tag with name "storage-manager-url" has no content')
    }
    return new URL(content)
  })()

if (apiBaseUrl === undefined) {
  throw new Error('API base URL not found in meta tag')
}

export function buildDiContainer() {

  const container = createContainer<AppContainer>({
    injectionMode: InjectionMode.PROXY,
  })

  container.register({
    httpClient: asFunction(() => new HttpClient(apiBaseUrl)).singleton(),
    apiService: asFunction(({ httpClient }) => new ApiService(httpClient)).singleton(),
    diskService: asFunction(({ apiService }) => new DiskService(apiService, useConfigStore, useDiskStore)).singleton(),
    fileManagerService: asFunction(({ apiService }) => new FileManagerService(apiService, useDiskStore)).singleton(),
    navigationService: asFunction(({ fileManagerService, locationService }) => new NavigationService(
      useFileManagerStore, fileManagerService, useTreeStore, locationService, useDiskStore)
    ).singleton(),
    toastService: asFunction(() => new ToastService(useToastStore)).singleton(),
    errorHandlerService: asFunction(({ toastService }) => new ErrorHandlerService(toastService)).singleton(),
    contextualMenuService: asFunction(() => new ContextualMenuService(useContextualMenuStore)).singleton(),
    downloadService: asFunction(() => new DownloadService(useDiskStore)).singleton(),
    locationService: asFunction(() => new LocationService()).singleton(),
    themeService: asFunction(() => new ThemeService(useUiStore)).singleton(),
    clipboardService: asFunction(() => new ClipboardService(useClipboardStore)).singleton(),
    eventQueueService: asFunction(({ navigationService }) => new EventQueueService(navigationService)).singleton(),
    uploadService: asFunction(({ apiService, navigationService }) => new UploadService(apiService, navigationService)).singleton(),
  })

  return container
}

export type DiContainer = ReturnType<typeof buildDiContainer>
