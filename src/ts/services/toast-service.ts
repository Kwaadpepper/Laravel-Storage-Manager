import { Toast, ToastStore } from "@ts/stores";

export class ToastService {
  private readonly defaultTimeout: number = 5000;

  constructor(
    private readonly toastStore: ToastStore,
  ) {}

  public pushToast(toast: Toast, options?: { timeout?: number; persistent?: boolean }): void {
    const insertedToast = this.toastStore.pushToast(toast, {
      persistent: options?.persistent,
    });

    if (!options?.persistent || options?.timeout) {
      setTimeout(() => {
        this.toastStore.pullToast(insertedToast.id);
      }, options?.timeout ?? this.defaultTimeout);
    }
  }
}
