import { ModalState, useUiStore } from '@ts/stores';
import { useDelayedUnmount } from '@ts/tools';
import AboutModal from './about-modal';
import DirectoryModal from './directory/create-modal';
import DeleteDirectoryModal from './directory/delete-modal';
import RenameDirectoryModal from './directory/rename-modal';
import CreateFileModal from './file/create-modal';
import DeleteFileModal from './file/delete-modal';
import RenameFileModal from './file/rename-modal';
import ViewFileModal from './file/view-modal';

/** Must match DaisyUI modal CSS transition duration */
const MODAL_CLOSE_DELAY = 250;

export default function ModalContainer() {
  const {
    aboutModal,
    newDirectoryModal,
    createFileModal,
    renameDirectoryModal,
    deleteDirectoryModal,
    renameFileModal,
    deleteFileModal,
    viewFileModal,
  } = useUiStore();

  const aboutOpen = aboutModal === ModalState.Opened;
  const dirCreateOpen = newDirectoryModal === ModalState.Opened;
  const fileCreateOpen = createFileModal === ModalState.Opened;
  const dirRenameOpen = renameDirectoryModal === ModalState.Opened;
  const dirDeleteOpen = deleteDirectoryModal === ModalState.Opened;
  const fileRenameOpen = renameFileModal === ModalState.Opened;
  const fileDeleteOpen = deleteFileModal === ModalState.Opened;
  const fileViewOpen = viewFileModal === ModalState.Opened;

  const aboutMounted = useDelayedUnmount(aboutOpen, MODAL_CLOSE_DELAY);
  const dirCreateMounted = useDelayedUnmount(dirCreateOpen, MODAL_CLOSE_DELAY);
  const fileCreateMounted = useDelayedUnmount(fileCreateOpen, MODAL_CLOSE_DELAY);
  const dirRenameMounted = useDelayedUnmount(dirRenameOpen, MODAL_CLOSE_DELAY);
  const dirDeleteMounted = useDelayedUnmount(dirDeleteOpen, MODAL_CLOSE_DELAY);
  const fileRenameMounted = useDelayedUnmount(fileRenameOpen, MODAL_CLOSE_DELAY);
  const fileDeleteMounted = useDelayedUnmount(fileDeleteOpen, MODAL_CLOSE_DELAY);
  const fileViewMounted = useDelayedUnmount(fileViewOpen, MODAL_CLOSE_DELAY);

  return (
    <>
      {aboutMounted && <AboutModal />}
      {dirCreateMounted && <DirectoryModal />}
      {fileCreateMounted && <CreateFileModal />}
      {dirRenameMounted && <RenameDirectoryModal />}
      {dirDeleteMounted && <DeleteDirectoryModal />}
      {fileRenameMounted && <RenameFileModal />}
      {fileDeleteMounted && <DeleteFileModal />}
      {fileViewMounted && <ViewFileModal />}
    </>
  );
}
