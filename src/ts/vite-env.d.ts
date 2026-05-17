/// <reference types="vite/client" />

import type { AppConfig } from '@ts/schemas';

declare global {
	var __STORAGE_MANAGER_CONFIG__: AppConfig | undefined
}

export {}
