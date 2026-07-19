import { ModalState, useUiStore } from '@ts/stores';
import { useDelayedUnmount } from '@ts/tools';
import AboutModal from './about-modal';
import DirectoryModal from './directory/create-modal';
import RenameDirectoryModal from './directory/rename-modal';
import DeleteModal from './shared/delete-modal';
import CreateFileModal from './file/create-modal';
import RenameFileModal from './file/rename-modal';
import ViewFileModal from './file/view-modal';
import MoveModal from './directory/move-modal';
import CopyModal from './directory/copy-modal';
import UploadModal from './upload-modal';

/** Must match DaisyUI modal CSS transition duration */
const MODAL_CLOSE_DELAY = 250;

export default function ModalContainer() {
  const {
    aboutModal,
    newDirectoryModal,
    createFileModal,
    renameDirectoryModal,
    deleteModal,
    renameFileModal,
    viewFileModal,
    moveModal,
    copyModal,
    uploadFileModal,
  } = useUiStore();

  const aboutOpen = aboutModal === ModalState.Opened;
  const dirCreateOpen = newDirectoryModal === ModalState.Opened;
  const fileCreateOpen = createFileModal === ModalState.Opened;
  const dirRenameOpen = renameDirectoryModal === ModalState.Opened;
  const deleteOpen = deleteModal === ModalState.Opened;
  const fileRenameOpen = renameFileModal === ModalState.Opened;
  const fileViewOpen = viewFileModal === ModalState.Opened;
  const moveOpen = moveModal === ModalState.Opened;
  const copyOpen = copyModal === ModalState.Opened;
  const uploadOpen = uploadFileModal === ModalState.Opened;

  const aboutMounted = useDelayedUnmount(aboutOpen, MODAL_CLOSE_DELAY);
  const dirCreateMounted = useDelayedUnmount(dirCreateOpen, MODAL_CLOSE_DELAY);
  const fileCreateMounted = useDelayedUnmount(fileCreateOpen, MODAL_CLOSE_DELAY);
  const dirRenameMounted = useDelayedUnmount(dirRenameOpen, MODAL_CLOSE_DELAY);
  const deleteMounted = useDelayedUnmount(deleteOpen, MODAL_CLOSE_DELAY);
  const fileRenameMounted = useDelayedUnmount(fileRenameOpen, MODAL_CLOSE_DELAY);
  const fileViewMounted = useDelayedUnmount(fileViewOpen, MODAL_CLOSE_DELAY);
  const moveMounted = useDelayedUnmount(moveOpen, MODAL_CLOSE_DELAY);
  const copyMounted = useDelayedUnmount(copyOpen, MODAL_CLOSE_DELAY);
  const uploadMounted = useDelayedUnmount(uploadOpen, MODAL_CLOSE_DELAY);

  return (
    <>
      {aboutMounted && <AboutModal />}
      {dirCreateMounted && <DirectoryModal />}
      {fileCreateMounted && <CreateFileModal />}
      {dirRenameMounted && <RenameDirectoryModal />}
      {deleteMounted && <DeleteModal />}
      {fileRenameMounted && <RenameFileModal />}
      {fileViewMounted && <ViewFileModal />}
      {moveMounted && <MoveModal />}
      {copyMounted && <CopyModal />}
      {uploadMounted && <UploadModal />}
    </>
  );
}
