import { useContainer } from "@ts/container";
import { ModalState, useUiStore } from "@ts/stores";
import { useEffect, useRef, useState } from "react";

type FetchState =
  | { status: 'pending' }
  | { status: 'success'; blob: Blob }
  | { status: 'error'; message: string };

export function useViewFileViewModel() {
  const { viewFileModal, setViewFileModal, targetFilePath } = useUiStore();
  const container = useContainer();
  const downloadService = container.resolve('downloadService');
  const downloadServiceRef = useRef(downloadService);

  const [fetchState, setFetchState] = useState<FetchState>({ status: 'pending' });

  const isOpen = viewFileModal === ModalState.Opened;

  useEffect(() => {
    if (!isOpen || !targetFilePath) return;

    let cancelled = false;

    downloadServiceRef.current.downloadFile(targetFilePath)
      .then(blob => { if (!cancelled) setFetchState({ status: 'success', blob }); })
      .catch(() => { if (!cancelled) setFetchState({ status: 'error', message: 'Failed to load file.' }); });

    return () => { cancelled = true; };
  }, [isOpen, targetFilePath]);

  function close() {
    setFetchState({ status: 'pending' });
    setViewFileModal(ModalState.Closed);
  }

  return {
    isOpen,
    close,
    targetFilePath,
    blob: fetchState.status === 'success' ? fetchState.blob : null,
    loading: isOpen && fetchState.status === 'pending',
    error: fetchState.status === 'error' ? fetchState.message : null,
  };
}
