<script setup lang="ts">
defineOptions({
  name: 'ModalContainer',
});
import { ModalState, useUiStore } from '@ts/stores';
import { useDelayedUnmount } from '@ts/tools/use-delayed-unmount';
import AboutModal from './AboutModal.vue';
import CreateDirectoryModal from './directory/CreateDirectoryModal.vue';
import RenameDirectoryModal from './directory/RenameDirectoryModal.vue';
import TransferModal from './directory/TransferModal.vue';
import DeleteModal from './shared/DeleteModal.vue';
import CreateFileModal from './file/CreateFileModal.vue';
import RenameFileModal from './file/RenameFileModal.vue';
import ViewFileModal from './file/ViewFileModal.vue';
import UploadModal from './UploadModal.vue';
import BulkRenameModal from './shared/BulkRenameModal.vue';
import ZipModal from './shared/ZipModal.vue';
import UnzipModal from './shared/UnzipModal.vue';
import CropImageModal from './file/CropImageModal.vue';

const MODAL_CLOSE_DELAY = 250;

const uiStore = useUiStore();

const aboutMounted = useDelayedUnmount(() => uiStore.aboutModal === ModalState.Opened, MODAL_CLOSE_DELAY);
const dirCreateMounted = useDelayedUnmount(() => uiStore.newDirectoryModal === ModalState.Opened, MODAL_CLOSE_DELAY);
const fileCreateMounted = useDelayedUnmount(() => uiStore.createFileModal === ModalState.Opened, MODAL_CLOSE_DELAY);
const dirRenameMounted = useDelayedUnmount(() => uiStore.renameDirectoryModal === ModalState.Opened, MODAL_CLOSE_DELAY);
const deleteMounted = useDelayedUnmount(() => uiStore.deleteModal === ModalState.Opened, MODAL_CLOSE_DELAY);
const fileRenameMounted = useDelayedUnmount(() => uiStore.renameFileModal === ModalState.Opened, MODAL_CLOSE_DELAY);
const fileViewMounted = useDelayedUnmount(() => uiStore.viewFileModal === ModalState.Opened, MODAL_CLOSE_DELAY);
const moveMounted = useDelayedUnmount(() => uiStore.moveModal === ModalState.Opened, MODAL_CLOSE_DELAY);
const copyMounted = useDelayedUnmount(() => uiStore.copyModal === ModalState.Opened, MODAL_CLOSE_DELAY);
const uploadMounted = useDelayedUnmount(() => uiStore.uploadFileModal === ModalState.Opened, MODAL_CLOSE_DELAY);
const bulkRenameMounted = useDelayedUnmount(() => uiStore.bulkRenameModal === ModalState.Opened, MODAL_CLOSE_DELAY);
const zipMounted = useDelayedUnmount(() => uiStore.zipModal === ModalState.Opened, MODAL_CLOSE_DELAY);
const unzipMounted = useDelayedUnmount(() => uiStore.unzipModal === ModalState.Opened, MODAL_CLOSE_DELAY);
const cropImageMounted = useDelayedUnmount(() => uiStore.cropImageModal === ModalState.Opened, MODAL_CLOSE_DELAY);
</script>

<template>
  <AboutModal v-if="aboutMounted" />
  <CreateDirectoryModal v-if="dirCreateMounted" />
  <CreateFileModal v-if="fileCreateMounted" />
  <RenameDirectoryModal v-if="dirRenameMounted" />
  <DeleteModal v-if="deleteMounted" />
  <RenameFileModal v-if="fileRenameMounted" />
  <ViewFileModal v-if="fileViewMounted" />
  <TransferModal
    v-if="moveMounted"
    mode="move"
  />
  <TransferModal
    v-if="copyMounted"
    mode="copy"
  />
  <UploadModal v-if="uploadMounted" />
  <BulkRenameModal v-if="bulkRenameMounted" />
  <ZipModal v-if="zipMounted" />
  <UnzipModal v-if="unzipMounted" />
  <CropImageModal v-if="cropImageMounted" />
</template>
