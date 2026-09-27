import Cropper from 'cropperjs';
import { computed, ref, shallowRef, watch, type ComputedRef, type Ref } from 'vue';
import { useService } from '@ts/service-container';
import { ModalState, useDiskStore, useUiStore } from '@ts/stores';

export type AspectRatioKey = 'free' | '1:1' | '4:3' | '16:9' | '3:2' | '2:3';

export const ASPECT_RATIOS: Record<AspectRatioKey, { label: string; value: number }> = {
  free: { label: 'Free', value: NaN },
  '1:1': { label: '1:1', value: 1 },
  '4:3': { label: '4:3', value: 4 / 3 },
  '16:9': { label: '16:9', value: 16 / 9 },
  '3:2': { label: '3:2', value: 3 / 2 },
  '2:3': { label: '2:3', value: 2 / 3 },
};

export type SaveMode = 'copy' | 'overwrite';

export interface CropImageViewModel {
  isOpen: ComputedRef<boolean>;
  targetFilePath: ComputedRef<string | null>;
  fileName: ComputedRef<string>;
  baseName: ComputedRef<string>;
  extension: ComputedRef<string>;
  imageSrc: ComputedRef<string | null>;
  aspectRatio: Ref<AspectRatioKey>;
  saveMode: Ref<SaveMode>;
  newFileName: Ref<string>;
  isSubmitting: Ref<boolean>;
  errorMessage: Ref<string | null>;
  isImageLoaded: Ref<boolean>;
  initCropper: (imageElement: HTMLImageElement) => void;
  destroyCropper: () => void;
  setAspectRatio: (key: AspectRatioKey) => void;
  rotate: (degrees: number) => void;
  flipHorizontal: () => void;
  flipVertical: () => void;
  zoom: (delta: number) => void;
  reset: () => void;
  submit: () => Promise<boolean>;
  close: () => void;
}

export function getMimeTypeFromExtension(ext: string): string {
  const cleanExt = ext.toLowerCase().trim();
  switch (cleanExt) {
    case 'jpg':
    case 'jpeg':
      return 'image/jpeg';
    case 'png':
      return 'image/png';
    case 'webp':
      return 'image/webp';
    case 'gif':
      return 'image/gif';
    case 'bmp':
      return 'image/bmp';
    default:
      return 'image/jpeg';
  }
}

export function useCropImageViewModel(): CropImageViewModel {
  const uiStore = useUiStore();
  const diskStore = useDiskStore();
  const downloadService = useService('downloadService');
  const uploadService = useService('uploadService');
  const toastService = useService('toastService');

  const isOpen = computed(() => uiStore.cropImageModal === ModalState.Opened);
  const targetFilePath = computed(() => uiStore.targetFilePath);

  const fileName = computed(() => targetFilePath.value?.split('/').pop() ?? '');
  const lastDot = computed(() => fileName.value.lastIndexOf('.'));
  const baseName = computed(() =>
    lastDot.value > 0 ? fileName.value.slice(0, lastDot.value) : fileName.value
  );
  const extension = computed(() =>
    lastDot.value > 0 ? fileName.value.slice(lastDot.value + 1) : ''
  );

  const destinationDirectory = computed(() => {
    if (!targetFilePath.value) return '/';
    const lastSlash = targetFilePath.value.lastIndexOf('/');
    return lastSlash > 0 ? targetFilePath.value.substring(0, lastSlash) : '/';
  });

  const imageSrc = computed(() => {
    if (!targetFilePath.value) return null;
    return downloadService.getStreamUrl(targetFilePath.value);
  });

  const aspectRatio = ref<AspectRatioKey>('free');
  const saveMode = ref<SaveMode>('copy');
  const newFileName = ref<string>('');
  const isSubmitting = ref(false);
  const errorMessage = ref<string | null>(null);
  const isImageLoaded = ref(false);

  const cropper = shallowRef<Cropper | null>(null);
  const scaleX = ref(1);
  const scaleY = ref(1);

  watch(
    () => uiStore.cropImageModal,
    (state) => {
      if (state === ModalState.Opened) {
        destroyCropper();
        isImageLoaded.value = false;
        aspectRatio.value = 'free';
        saveMode.value = 'copy';
        scaleX.value = 1;
        scaleY.value = 1;
        errorMessage.value = null;
        const ext = extension.value ? `.${extension.value}` : '';
        newFileName.value = `${baseName.value}-cropped${ext}`;
      } else {
        destroyCropper();
      }
    }
  );

  function destroyCropper(): void {
    if (cropper.value) {
      try {
        cropper.value.destroy();
      } catch {
        // ignore cleanup error
      }
      cropper.value = null;
    }
  }

  const CROPPER_TEMPLATE = (
    '<cropper-canvas background style="width: 100%; height: 100%; display: block;">'
    + '<cropper-image rotatable scalable skewable translatable initial-fit="contain"></cropper-image>'
    + '<cropper-shade></cropper-shade>'
    + '<cropper-handle action="select" plain></cropper-handle>'
    + '<cropper-selection initial-coverage="0.85" movable resizable>'
    + '<cropper-grid role="grid" bordered covered></cropper-grid>'
    + '<cropper-crosshair centered></cropper-crosshair>'
    + '<cropper-handle action="move" theme-color="rgba(255, 255, 255, 0.35)"></cropper-handle>'
    + '<cropper-handle action="n-resize"></cropper-handle>'
    + '<cropper-handle action="e-resize"></cropper-handle>'
    + '<cropper-handle action="s-resize"></cropper-handle>'
    + '<cropper-handle action="w-resize"></cropper-handle>'
    + '<cropper-handle action="ne-resize"></cropper-handle>'
    + '<cropper-handle action="nw-resize"></cropper-handle>'
    + '<cropper-handle action="se-resize"></cropper-handle>'
    + '<cropper-handle action="sw-resize"></cropper-handle>'
    + '</cropper-selection>'
    + '</cropper-canvas>'
  );

  function initCropper(imageElement: HTMLImageElement): void {
    destroyCropper();
    try {
      const instance = new Cropper(imageElement, {
        template: CROPPER_TEMPLATE,
      });
      cropper.value = instance;
      isImageLoaded.value = true;

      requestAnimationFrame(() => {
        const image = instance.getCropperImage();
        if (image) {
          image.$center('contain');
        }
      });
    } catch (err) {
      errorMessage.value = err instanceof Error ? err.message : 'Failed to initialize cropper';
    }
  }

  function setAspectRatio(key: AspectRatioKey): void {
    aspectRatio.value = key;
    const selection = cropper.value?.getCropperSelection();
    if (selection) {
      selection.aspectRatio = ASPECT_RATIOS[key].value;
    }
  }

  function rotate(degrees: number): void {
    const image = cropper.value?.getCropperImage();
    if (image) {
      image.$rotate(`${degrees}deg`);
    }
  }

  function flipHorizontal(): void {
    const image = cropper.value?.getCropperImage();
    if (image) {
      scaleX.value = scaleX.value === 1 ? -1 : 1;
      image.$scale(scaleX.value, scaleY.value);
    }
  }

  function flipVertical(): void {
    const image = cropper.value?.getCropperImage();
    if (image) {
      scaleY.value = scaleY.value === 1 ? -1 : 1;
      image.$scale(scaleX.value, scaleY.value);
    }
  }

  function zoom(delta: number): void {
    const image = cropper.value?.getCropperImage();
    if (image) {
      image.$zoom(delta);
    }
  }

  function reset(): void {
    scaleX.value = 1;
    scaleY.value = 1;
    const image = cropper.value?.getCropperImage();
    if (image) {
      image.$resetTransform();
    }
    const selection = cropper.value?.getCropperSelection();
    if (selection) {
      selection.$reset();
      selection.aspectRatio = ASPECT_RATIOS[aspectRatio.value].value;
    }
  }

  function close(): void {
    destroyCropper();
    uiStore.cropImageModal = ModalState.Closed;
  }

  async function submit(): Promise<boolean> {
    if (!cropper.value || !targetFilePath.value) return false;

    isSubmitting.value = true;
    errorMessage.value = null;

    try {
      const selection = cropper.value.getCropperSelection();
      if (!selection) {
        throw new Error('No crop selection found');
      }

      const canvas = await selection.$toCanvas();
      if (!canvas) {
        throw new Error('Failed to generate cropped canvas');
      }

      const mime = getMimeTypeFromExtension(extension.value);
      const blob: Blob | null = await new Promise((resolve) => {
        canvas.toBlob((b) => resolve(b), mime, 0.92);
      });

      if (!blob) {
        throw new Error('Failed to create image blob');
      }

      let finalName = '';
      if (saveMode.value === 'overwrite') {
        finalName = fileName.value;
      } else {
        const trimmed = newFileName.value.trim();
        const ext = extension.value ? `.${extension.value}` : '';
        if (!trimmed) {
          finalName = `${baseName.value}-cropped${ext}`;
        } else if (trimmed.toLowerCase().endsWith(ext.toLowerCase())) {
          finalName = trimmed;
        } else {
          finalName = `${trimmed}${ext}`;
        }
      }

      const file = new File([blob], finalName, { type: mime });
      const dir = destinationDirectory.value;
      const currentDisk = diskStore.currentDisk ?? 'public';
      const duplicatePolicy = saveMode.value === 'overwrite' ? 'overwrite' : 'auto_rename';

      uploadService.enqueueUploads([file], dir, currentDisk, duplicatePolicy);

      toastService.pushToast({
        message: `Cropped image "${finalName}" queued for upload.`,
        type: 'info',
      });

      close();
      return true;
    } catch (err) {
      errorMessage.value = err instanceof Error ? err.message : 'Failed to crop image';
      return false;
    } finally {
      isSubmitting.value = false;
    }
  }

  return {
    isOpen,
    targetFilePath,
    fileName,
    baseName,
    extension,
    imageSrc,
    aspectRatio,
    saveMode,
    newFileName,
    isSubmitting,
    errorMessage,
    isImageLoaded,
    initCropper,
    destroyCropper,
    setAspectRatio,
    rotate,
    flipHorizontal,
    flipVertical,
    zoom,
    reset,
    submit,
    close,
  };
}
