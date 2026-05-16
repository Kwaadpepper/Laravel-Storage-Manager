import { HttpClient } from '@ts/clients';
import { ApiService, NavigationService } from '@ts/services';
import { FileManagerService } from '@ts/services/file-manager-service';
import { useFileManagerStore } from '@ts/stores';
import { asFunction, createContainer, InjectionMode } from 'awilix';

export type AppContainer = {
  fileManagerService: FileManagerService
  navigationService: NavigationService
  apiService: ApiService
  httpClient: HttpClient
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
    fileManagerService: asFunction(({ apiService }) => new FileManagerService(apiService)).singleton(),
    navigationService: asFunction(({ fileManagerService }) => new NavigationService(useFileManagerStore, fileManagerService)).singleton(),
  })

  return container
}

export type DiContainer = ReturnType<typeof buildDiContainer>
