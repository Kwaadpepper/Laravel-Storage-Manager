import type { PickerFilePayload, PickerMessageEventData, ScopedType } from '../stores/picker-store';

export type PickerDisplayMode = 'popup' | 'modal';

export interface StorageManagerPickerOptions {
  url: string;
  sender?: string;
  type?: ScopedType;
  mode?: PickerDisplayMode;
  onSelect: (file: PickerFilePayload) => void;
  onClose?: () => void;
  title?: string;
  popupWidth?: number;
  popupHeight?: number;
}

export interface AttachOptions extends Omit<StorageManagerPickerOptions, 'onSelect'> {
  preview?: HTMLElement | HTMLImageElement | string;
  onSelect?: (file: PickerFilePayload) => void;
}

export class StorageManagerPicker {
  private activePopup: Window | null = null;
  private activeModal: HTMLElement | null = null;
  private messageHandler: ((event: MessageEvent) => void) | null = null;

  constructor(private readonly options: StorageManagerPickerOptions) {}

  public static open(options: StorageManagerPickerOptions): StorageManagerPicker {
    const picker = new StorageManagerPicker(options);
    picker.open();
    return picker;
  }

  public static attach(
    trigger: HTMLElement | string,
    targetInput?: HTMLInputElement | string,
    options?: Partial<AttachOptions>
  ): () => void {
    const triggerEl = typeof trigger === 'string' ? document.querySelector<HTMLElement>(trigger) : trigger;
    const inputEl = typeof targetInput === 'string'
      ? document.querySelector<HTMLInputElement>(targetInput)
      : targetInput;

    if (!triggerEl) {
      throw new Error('[StorageManager] Attach trigger element not found.');
    }

    const clickListener = (event: MouseEvent): void => {
      event.preventDefault();

      const pickerUrl = options?.url ?? '/storage-manager';

      StorageManagerPicker.open({
        url: pickerUrl,
        sender: options?.sender,
        type: options?.type,
        mode: options?.mode ?? 'popup',
        popupWidth: options?.popupWidth,
        popupHeight: options?.popupHeight,
        title: options?.title,
        onClose: options?.onClose,
        onSelect: (file: PickerFilePayload): void => {
          if (inputEl) {
            inputEl.value = file.url;
            inputEl.dispatchEvent(new Event('input', { bubbles: true }));
            inputEl.dispatchEvent(new Event('change', { bubbles: true }));
          }

          if (options?.preview) {
            const previewEl = typeof options.preview === 'string'
              ? document.querySelector<HTMLElement>(options.preview)
              : options.preview;

            if (previewEl instanceof HTMLImageElement) {
              previewEl.src = file.url;
            } else if (previewEl) {
              previewEl.style.backgroundImage = `url("${file.url}")`;
            }
          }

          if (typeof options?.onSelect === 'function') {
            options.onSelect(file);
          }
        },
      });
    };

    triggerEl.addEventListener('click', clickListener);

    return (): void => {
      triggerEl.removeEventListener('click', clickListener);
    };
  }

  public open(): void {
    const sender = this.options.sender ?? 'storage-manager-picker';
    const pickerUrl = this.buildUrl(sender);

    this.cleanup();

    this.messageHandler = (event: MessageEvent): void => {
      const data = event.data as Partial<PickerMessageEventData> | undefined;
      if (!data || data.sender !== sender || !data.file) {
        return;
      }

      this.options.onSelect(data.file);
      this.close();
    };

    globalThis.addEventListener('message', this.messageHandler);

    if (this.options.mode === 'modal') {
      this.openModal(pickerUrl);
    } else {
      this.openPopup(pickerUrl);
    }
  }

  public close(): void {
    this.cleanup();
    if (this.options.onClose) {
      this.options.onClose();
    }
  }

  private buildUrl(sender: string): string {
    const url = new URL(this.options.url, globalThis.location?.origin ?? 'http://localhost');
    url.searchParams.set('picker', '1');
    url.searchParams.set('sender', sender);
    if (this.options.type) {
      url.searchParams.set('type', this.options.type);
    }
    return url.toString();
  }

  private openPopup(url: string): void {
    const width = this.options.popupWidth ?? 1080;
    const height = this.options.popupHeight ?? 700;
    const left = globalThis.screen ? Math.max(0, (globalThis.screen.width - width) / 2) : 50;
    const top = globalThis.screen ? Math.max(0, (globalThis.screen.height - height) / 2) : 50;

    const features = `width=${width},height=${height},top=${top},left=${left},resizable=yes,scrollbars=yes,status=no`;
    this.activePopup = globalThis.open(url, this.options.title ?? 'Storage Manager', features);

    if (this.activePopup && typeof this.activePopup.focus === 'function') {
      this.activePopup.focus();
    }
  }

  private openModal(url: string): void {
    if (typeof document === 'undefined') {
      return;
    }

    const container = document.createElement('div');
    container.setAttribute('data-storage-manager-modal', 'true');
    container.style.position = 'fixed';
    container.style.inset = '0';
    container.style.zIndex = '999999';
    container.style.display = 'flex';
    container.style.alignItems = 'center';
    container.style.justifyContent = 'center';
    container.style.backgroundColor = 'rgba(0, 0, 0, 0.6)';

    const wrapper = document.createElement('div');
    wrapper.style.position = 'relative';
    wrapper.style.width = '90vw';
    wrapper.style.height = '85vh';
    wrapper.style.maxWidth = '1200px';
    wrapper.style.backgroundColor = '#ffffff';
    wrapper.style.borderRadius = '8px';
    wrapper.style.overflow = 'hidden';
    wrapper.style.boxShadow = '0 20px 25px -5px rgba(0, 0, 0, 0.2)';

    const closeBtn = document.createElement('button');
    closeBtn.textContent = '✕';
    closeBtn.setAttribute('aria-label', 'Close');
    closeBtn.style.position = 'absolute';
    closeBtn.style.top = '10px';
    closeBtn.style.right = '12px';
    closeBtn.style.zIndex = '10';
    closeBtn.style.border = 'none';
    closeBtn.style.background = 'rgba(0, 0, 0, 0.5)';
    closeBtn.style.color = '#ffffff';
    closeBtn.style.borderRadius = '50%';
    closeBtn.style.width = '28px';
    closeBtn.style.height = '28px';
    closeBtn.style.cursor = 'pointer';
    closeBtn.style.fontWeight = 'bold';
    closeBtn.onclick = (): void => this.close();

    const iframe = document.createElement('iframe');
    iframe.src = url;
    iframe.style.width = '100%';
    iframe.style.height = '100%';
    iframe.style.border = 'none';

    wrapper.appendChild(closeBtn);
    wrapper.appendChild(iframe);
    container.appendChild(wrapper);
    document.body.appendChild(container);

    this.activeModal = container;
  }

  private cleanup(): void {
    if (this.messageHandler) {
      globalThis.removeEventListener('message', this.messageHandler);
      this.messageHandler = null;
    }

    if (this.activePopup && !this.activePopup.closed) {
      if (typeof this.activePopup.close === 'function') {
        this.activePopup.close();
      }
      this.activePopup = null;
    }

    if (this.activeModal) {
      this.activeModal.remove();
      this.activeModal = null;
    }
  }
}

// Global browser window attachment
if (typeof window !== 'undefined') {
  (window as unknown as { StorageManagerPicker: typeof StorageManagerPicker }).StorageManagerPicker = StorageManagerPicker;
}
