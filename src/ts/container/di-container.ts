import { HttpClient } from '@ts/clients';
import { ApiService, DiskService, ErrorHandlerService, LocationService, NavigationService, ThemeService, ToastService } from '@ts/services';
import { ContextualMenuService } from '@ts/services/contextual-menu-service';
import { DownloadService } from '@ts/services/download-service';
import { FileManagerService } from '@ts/services/file-manager-service';
import { useConfigStore, useContextualMenuStore, useDiskStore, useFileManagerStore, useToastStore, useTreeStore, useUiStore } from '@ts/stores';
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

  // * ContainerContext
  const container = createContainer<AppContainer>({
    injectionMode: InjectionMode.PROXY,
  })

  // * HttpClient
  container.register({
    httpClient: asFunction(() => new HttpClient(apiBaseUrl)).singleton(),
    apiService: asFunction(({ httpClient }) => new ApiService(httpClient)).singleton(),
    diskService: asFunction(({ apiService }) => new DiskService(apiService, useConfigStore, useDiskStore)).singleton(),
    fileManagerService: asFunction(({ apiService }) => new FileManagerService(apiService, useDiskStore)).singleton(),
    navigationService: asFunction(({ fileManagerService, locationService }) => new NavigationService(
      useFileManagerStore, fileManagerService, useTreeStore, locationService)
    ).singleton(),
    toastService: asFunction(() => new ToastService(useToastStore)).singleton(),
    errorHandlerService: asFunction(({ toastService }) => new ErrorHandlerService(toastService)).singleton(),
    contextualMenuService: asFunction(() => new ContextualMenuService(useContextualMenuStore)).singleton(),
    downloadService: asFunction(() => new DownloadService()).singleton(),
    locationService: asFunction(() => new LocationService()).singleton(),
    themeService: asFunction(() => new ThemeService(useUiStore)).singleton(),
  })

  return container
}

export type DiContainer = ReturnType<typeof buildDiContainer>
