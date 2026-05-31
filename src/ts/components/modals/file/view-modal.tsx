import { Loader, X } from "lucide-react";
import { useEffect, useRef } from "react";
import ViewAudio, { SUPPORTED_EXTENSIONS as AUDIO_EXTENSIONS } from "./view-audio";
import ViewImage, { SUPPORTED_EXTENSIONS as IMAGE_EXTENSIONS } from "./view-image";
import ViewText, { SUPPORTED_EXTENSIONS as TEXT_EXTENSIONS } from "./view-text";
import ViewUnsupported from "./view-unsupported";
import { useViewFileViewModel } from "./view-viewmodel";

type ViewerType = 'text' | 'image' | 'audio' | 'unsupported';

function detectViewer(extension: string | null): ViewerType {
  if (!extension) return 'unsupported';
  const ext = extension.toLowerCase();
  if (IMAGE_EXTENSIONS.includes(ext)) return 'image';
  if (AUDIO_EXTENSIONS.includes(ext)) return 'audio';
  if (TEXT_EXTENSIONS.includes(ext)) return 'text';
  return 'unsupported';
}

export default function ViewFileModal() {
  const { isOpen, close, targetFilePath, blob, loading, error } = useViewFileViewModel();

  const dialogRef = useRef<HTMLDialogElement | null>(null);
  const closeButtonRef = useRef<HTMLButtonElement | null>(null);

  const fileName = targetFilePath ? (targetFilePath.split('/').pop() ?? '') : '';
  const extension = fileName.includes('.') ? (fileName.split('.').pop()?.toLowerCase() ?? null) : null;
  const viewerType = detectViewer(extension);

  useEffect(() => {
    const current = dialogRef.current;
    if (!current) return;

    if (isOpen) {
      current.showModal();
    } else {
      current.close();
    }
  }, [isOpen]);

  return (
    <dialog className="modal" onClose={close} ref={dialogRef}>
      <div
        className="modal-box flex flex-col"
        style={{ height: '90vh', width: '90vw', maxWidth: '90vw' }}
      >
        <div className="flex items-center justify-between mb-2">
          <h3 className="font-bold text-lg truncate">{fileName || 'View File'}</h3>
          <button
            className="btn btn-sm btn-circle btn-ghost shrink-0"
            type="button"
            aria-label="Close"
            onClick={() => closeButtonRef.current?.click()}
          >
            <X size={16} />
          </button>
        </div>

        <div className="flex-1 min-h-0">
          {loading && (
            <div className="flex items-center justify-center h-full text-base-content/50 gap-2">
              <Loader size={20} className="animate-spin" />
              <span>Loading...</span>
            </div>
          )}

          {!loading && error && (
            <div className="flex items-center justify-center h-full text-error">
              {error}
            </div>
          )}

          {!loading && !error && blob && viewerType === 'text' && (
            <ViewText blob={blob} extension={extension} />
          )}

          {!loading && !error && blob && viewerType === 'image' && (
            <ViewImage blob={blob} fileName={fileName} />
          )}

          {!loading && !error && blob && viewerType === 'audio' && (
            <ViewAudio blob={blob} fileName={fileName} />
          )}

          {!loading && !error && blob && viewerType === 'unsupported' && (
            <ViewUnsupported extension={extension} />
          )}
        </div>

        <div className="flex justify-end mt-2">
          <button className="btn" type="button" onClick={() => closeButtonRef.current?.click()}>
            Close
          </button>
        </div>
      </div>

      <form method="dialog" className="modal-backdrop">
        <button ref={closeButtonRef}>Close</button>
      </form>
    </dialog>
  );
}
