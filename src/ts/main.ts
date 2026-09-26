import '@css/app.css';
import { appConfigSchema } from '@ts/schemas';
import { buildServiceContainer, serviceContainerKey } from '@ts/service-container';
import { useConfigStore, useDiskStore } from '@ts/stores';
import { createPinia } from 'pinia';
import { createApp } from 'vue';
import App from './components/App.vue';

const app = createApp(App);
const pinia = createPinia();
app.use(pinia);

const globalConfig = appConfigSchema.safeParse(globalThis.__STORAGE_MANAGER_CONFIG__);
if (!globalConfig.success) {
  console.error('Invalid global configuration:', globalConfig.error);
  throw new Error('Invalid global configuration');
}

const configStore = useConfigStore();
configStore.initialize(globalConfig.data);

const container = buildServiceContainer();
container.errorHandlerService.registerGlobalHandlers();

const initialDisks = configStore.disks;
if (initialDisks.length > 0) {
  const { disk } = container.locationService.getDiskAndPath();
  const diskStore = useDiskStore();

  if (disk && initialDisks.includes(disk)) {
    diskStore.setCurrentDisk(disk);
  } else {
    diskStore.setCurrentDisk(initialDisks[0]);
  }
}

app.provide(serviceContainerKey, container);

const rootEl = document.getElementById('file-manager');
if (rootEl === null) {
  throw new Error('Root element not found');
}

app.mount(rootEl);
