import { HttpClient } from '@ts/clients';
import { ApiService } from '@ts/services';
import { FileManagerService } from '@ts/services/file-manager-service';
import { asClass, asValue, createContainer, InjectionMode } from 'awilix';

export type AppContainer = {
  fileManagerService: FileManagerService
  apiService: ApiService
  httpClient: HttpClient
  apiBaseUrl: string
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
    injectionMode: InjectionMode.CLASSIC,
  })

  // * HttpClient
  container.register({
    endpointHost: asValue(apiBaseUrl),
    httpClient: asClass(HttpClient).singleton(),
  })

  // * ApiService
  container.register({
    httpClient: asValue(container.resolve('httpClient')),
    apiService: asClass(ApiService).singleton(),
  })

  // * FileManagerService
  container.register({
    apiService: asValue(container.resolve('apiService')),
    fileManagerService: asClass(FileManagerService).singleton(),
  })

  return container
}

export type DiContainer = ReturnType<typeof buildDiContainer>
