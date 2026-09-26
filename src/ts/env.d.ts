/// <reference types="vite/client" />

import type { AppConfig } from '@ts/schemas';

declare module '*.vue' {
  import type { DefineComponent } from 'vue';
  const component: DefineComponent<Record<string, never>, Record<string, never>, unknown>;
  export default component;
}

declare global {
  var __STORAGE_MANAGER_CONFIG__: AppConfig | undefined;
}

export {};
