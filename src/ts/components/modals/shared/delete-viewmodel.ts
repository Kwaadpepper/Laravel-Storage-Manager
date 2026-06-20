import { useContainer } from "@ts/container";
import { ModalState, useFileManagerStore, useUiStore } from "@ts/stores";

export function useDeleteViewModel() {
  const fileManagerService = useContainer().resolve('fileManagerService');
  const eventQueueService = useContainer().resolve('eventQueueService');
  const { deleteModal, setDeleteModal } = useUiStore();
  const { selectedNodes, selectNodes } = useFileManagerStore();

  const selectedPaths = Object.keys(selectedNodes);
  const itemCount = selectedPaths.length;
  const singleItemName = itemCount === 1 ? selectedNodes[selectedPaths[0]].name : '';

  const isOpen = deleteModal === ModalState.Opened;

  function close() {
    setDeleteModal(ModalState.Closed);
  }

  function submit() {
    if (itemCount === 0) return;
    
    const events = selectedPaths.map(path => ({
      id: `del-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`,
      type: 'DELETE' as const,
      sourcePath: path,
      execute: async () => {
        await fileManagerService.deleteItem(path, true);
      }
    }));
    
    eventQueueService.pushBatch(events);
    
    // Clear selection since items are being deleted
    selectNodes();
    close();
  }

  return {
    isOpen,
    itemCount,
    singleItemName,
    close,
    submit,
  };
}
