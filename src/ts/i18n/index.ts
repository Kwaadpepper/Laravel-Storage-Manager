import { useConfigStore } from '@ts/stores';
import { en } from './en';

export function t(key: string, params?: Record<string, string | number>): string {
  let message: string | undefined;

  try {
    const configStore = useConfigStore();
    message = configStore.translations[key];
  } catch {
    // Config store or pinia might not be initialized
  }

  if (typeof message !== 'string' || message === '') {
    message = en[key] ?? key;
  }

  if (params) {
    for (const [k, v] of Object.entries(params)) {
      message = message.replaceAll(`{${k}}`, String(v));
      message = message.replaceAll(`:${k}`, String(v));
    }
  }

  return message;
}

export function useI18n(): { t: typeof t } {
  return {
    t,
  };
}

export { en };
