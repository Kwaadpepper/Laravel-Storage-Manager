import '@css/app.css';
import { buildDiContainer, ContainerContext } from '@ts/container';
import { appConfigSchema } from '@ts/schemas';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import App from './components/app.tsx';
import { useConfigStore } from './stores/config-store.ts';

// * Bootstrap SM

const container = buildDiContainer()
container.cradle.errorHandlerService.registerGlobalHandlers()
const globalConfig = appConfigSchema.safeParse(globalThis.__STORAGE_MANAGER_CONFIG__)

if (!globalConfig.success) {
  console.error('Invalid global configuration:', globalConfig.error)
  throw new Error('Invalid global configuration')
}

useConfigStore.getState().initialize(globalConfig.data)

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
