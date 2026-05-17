import { Toast, useToastStore } from "@ts/stores";

export class ToastService {
  private readonly defaultTimeout: number = 5000;

  constructor(
    private readonly toastStore: typeof useToastStore,
  ) {
  }

  public pushToast(toast: Toast, options?: { timeout?: number, persistent?: boolean }): void {
    const toastState = this.toastStore.getState();
    const insertedToast = toastState.pushToast(toast, {
      persistent: options?.persistent
    });

    if (!options?.persistent || options?.timeout) {
      setTimeout(() => {
        this.toastStore.getState().pullToast(insertedToast.id);
      }, options?.timeout ?? this.defaultTimeout);
    }
  }
}
