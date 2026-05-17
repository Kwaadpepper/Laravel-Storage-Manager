import { isValidationError } from "@ts/errors";
import { ToastService } from "./toast-service";

type HandleErrorOptions = {
  fallbackMessage?: string
  fieldMap?: Record<string, string>
  onFieldError?: (fieldName: string, message: string) => boolean
}

export class ErrorHandlerService {
  private static globalHandlersRegistered: boolean = false

  constructor(private readonly toastService: ToastService) {
    this.toastService = toastService
  }

  public registerGlobalHandlers(): void {
    if (ErrorHandlerService.globalHandlersRegistered) {
      return
    }

    globalThis.addEventListener('error', this.onGlobalError)
    globalThis.addEventListener('unhandledrejection', this.onUnhandledRejection)
    ErrorHandlerService.globalHandlersRegistered = true
  }

  public handle(error: unknown, options?: HandleErrorOptions): void {
    const fallbackMessage = options?.fallbackMessage ?? 'An unexpected error occurred. Please try again.'

    if (isValidationError(error) && options?.onFieldError) {
      const fieldErrors = error.getFieldErrors()
      let hasHandledFieldError = false

      for (const [fieldName, message] of Object.entries(fieldErrors)) {
        const mappedFieldName = options.fieldMap?.[fieldName] ?? fieldName
        hasHandledFieldError = options.onFieldError(mappedFieldName, message) || hasHandledFieldError
      }

      if (hasHandledFieldError) {
        return
      }
    }

    this.toastService.pushToast({
      message: fallbackMessage,
      type: 'error',
    })
  }

  private readonly onGlobalError = (event: ErrorEvent): void => {
    const error = event.error ?? new Error(event.message)
    this.handle(error, {
      fallbackMessage: 'An unexpected error occurred. Please try again.',
    })
  }

  private readonly onUnhandledRejection = (event: PromiseRejectionEvent): void => {
    this.handle(event.reason, {
      fallbackMessage: 'An unexpected error occurred. Please try again.',
    })
  }
}
