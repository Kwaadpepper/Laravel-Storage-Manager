import { isValidationError } from '@ts/errors';
import { useToastStore } from '@ts/stores';

export interface HandleErrorOptions {
  fallbackMessage?: string;
  fieldMap?: Record<string, string>;
  onFieldError?: (fieldName: string, message: string) => boolean;
}

export function handleApiError(error: unknown, options?: HandleErrorOptions): void {
  const fallbackMessage =
    options?.fallbackMessage ?? 'An unexpected error occurred. Please try again.';

  if (isValidationError(error) && options?.onFieldError) {
    const fieldErrors = error.getFieldErrors();
    let hasHandledFieldError = false;

    for (const [fieldName, message] of Object.entries(fieldErrors)) {
      const mappedFieldName = options.fieldMap?.[fieldName] ?? fieldName;
      hasHandledFieldError = options.onFieldError(mappedFieldName, message) || hasHandledFieldError;
    }

    if (hasHandledFieldError) {
      return;
    }
  }

  const toastStore = useToastStore();
  toastStore.pushToast({
    message: fallbackMessage,
    type: 'error',
  });
}

let globalHandlersRegistered = false;

function onGlobalError(event: ErrorEvent): void {
  const error = event.error ?? new Error(event.message);
  handleApiError(error, {
    fallbackMessage: 'An unexpected error occurred. Please try again.',
  });
}

function onUnhandledRejection(event: PromiseRejectionEvent): void {
  handleApiError(event.reason, {
    fallbackMessage: 'An unexpected error occurred. Please try again.',
  });
}

export function registerGlobalErrorHandlers(): void {
  if (globalHandlersRegistered) {
    return;
  }

  globalThis.addEventListener('error', onGlobalError);
  globalThis.addEventListener('unhandledrejection', onUnhandledRejection);
  globalHandlersRegistered = true;
}
