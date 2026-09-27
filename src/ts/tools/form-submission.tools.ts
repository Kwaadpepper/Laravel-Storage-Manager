import { isDomainValidationError, isValidationError } from '@ts/errors';
import type { Ref } from 'vue';

export interface FormSubmissionOptions<TFieldName extends string = string> {
  execute: () => Promise<void>;
  isSubmitting?: Ref<boolean>;
  formErrorMessage?: Ref<string | null>;
  fieldErrors?: Partial<Record<TFieldName, Ref<string | null>>>;
  domainErrorField?: Ref<string | null>;
  fallbackMessage?: string;
  onSuccess?: () => void;
}

export async function handleFormSubmission<TFieldName extends string = string>(
  options: FormSubmissionOptions<TFieldName>
): Promise<boolean> {
  if (options.isSubmitting?.value) return false;

  if (options.isSubmitting) options.isSubmitting.value = true;
  if (options.formErrorMessage) options.formErrorMessage.value = null;
  if (options.fieldErrors) {
    for (const fieldRef of Object.values(options.fieldErrors) as (Ref<string | null> | undefined)[]) {
      if (fieldRef) fieldRef.value = null;
    }
  }

  try {
    await options.execute();
    options.onSuccess?.();
    return true;
  } catch (error: unknown) {
    if (isValidationError<TFieldName>(error) && options.fieldErrors) {
      const serverFieldErrors = error.getFieldErrors();
      let handled = false;
      for (const [field, message] of Object.entries(serverFieldErrors)) {
        const targetRef = options.fieldErrors[field as TFieldName];
        if (targetRef && message) {
          targetRef.value = message as string;
          handled = true;
        }
      }
      if (handled) return false;
    }

    if (isDomainValidationError(error)) {
      if (options.domainErrorField) {
        options.domainErrorField.value = error.message;
        return false;
      }
      if (options.formErrorMessage) {
        options.formErrorMessage.value = error.message;
        return false;
      }
    }

    if (error instanceof Error) {
      if (options.formErrorMessage) {
        options.formErrorMessage.value = error.message;
      }
      return false;
    }

    if (options.formErrorMessage) {
      options.formErrorMessage.value = options.fallbackMessage ?? 'Operation failed';
    }
    return false;
  } finally {
    if (options.isSubmitting) options.isSubmitting.value = false;
  }
}
