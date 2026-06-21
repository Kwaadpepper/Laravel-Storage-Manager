import '@css/app.css';
import { buildDiContainer, ContainerContext } from '@ts/container';
import { appConfigSchema } from '@ts/schemas';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import App from './components/app.tsx';
import { useConfigStore } from './stores/config-store.ts';
import { useDiskStore } from './stores/disk-store.ts';

const container = buildDiContainer()
container.resolve('errorHandlerService').registerGlobalHandlers()
const globalConfig = appConfigSchema.safeParse(globalThis.__STORAGE_MANAGER_CONFIG__)

if (!globalConfig.success) {
  console.error('Invalid global configuration:', globalConfig.error)
  throw new Error('Invalid global configuration')
}

useConfigStore.getState().initialize(globalConfig.data)

const initialDisks = useConfigStore.getState().disks
if (initialDisks.length > 0) {
  const locationService = container.resolve('locationService')
  const { disk } = locationService.getDiskAndPath()
  
  if (disk && initialDisks.includes(disk)) {
    useDiskStore.getState().setCurrentDisk(disk)
  } else {
    useDiskStore.getState().setCurrentDisk(initialDisks[0])
    // The NavigationService will eventually call replace when it does initial navigation,
    // or locationService can just wait for the first navigateToRoot.
  }
}

const rootEl = document.getElementById('file-manager')

if (rootEl === null) {
  throw new Error('Root element not found');
}

createRoot(rootEl).render(
  <StrictMode>
    <ContainerContext.Provider value={container}>
      <App />
    </ContainerContext.Provider>
  </StrictMode>,
)
