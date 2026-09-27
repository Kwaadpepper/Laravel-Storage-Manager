<script setup lang="ts">
defineOptions({
  name: 'ActionBar',
});
import SearchBar from '@ts/components/layout/SearchBar.vue';
import ThemeSelector from '@ts/components/layout/ThemeSelector.vue';
import { useService } from '@ts/service-container';
import { ModalState, useConfigStore, useDiskStore, useFileManagerStore, useUiStore } from '@ts/stores';
import {
  ArrowLeft,
  ArrowRight,
  ArrowUp,
  CircleQuestionMark,
  FilePlus,
  FileUp,
  FolderPlus,
  Link,
  Maximize,
  Minimize,
  PanelLeft,
  PanelLeftClose,
  RefreshCw,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';

function toggleFullscreen(): void {
  if (globalThis.document.fullscreenElement) {
    globalThis.document.exitFullscreen();
  } else {
    globalThis.document.documentElement.requestFullscreen().catch((err) => {
      console.error(`Error attempting to enable fullscreen: ${err.message}`);
    });
  }
}

function isFullscreenActive(): boolean {
  return !!globalThis.document.fullscreenElement;
}

const uiStore = useUiStore();
const fileManagerStore = useFileManagerStore();
const configStore = useConfigStore();
const diskStore = useDiskStore();
const navigationService = useService('navigationService');
const toastService = useService('toastService');

const isFullscreen = ref(false);

const isReadOnly = computed(() =>
  diskStore.currentDisk ? configStore.readOnlyDisks.includes(diskStore.currentDisk) : false
);

function onFullscreenChange(): void {
  isFullscreen.value = isFullscreenActive();
}

onMounted(() => {
  globalThis.document.addEventListener('fullscreenchange', onFullscreenChange);
});

onUnmounted(() => {
  globalThis.document.removeEventListener('fullscreenchange', onFullscreenChange);
});

function onReloadClick(): void {
  navigationService.refreshCurrentPath();
}

function onBackClick(): void {
  navigationService.navigatePrevious();
}

function onForwardClick(): void {
  navigationService.navigateNext();
}

function onUpClick(): void {
  navigationService.navigateToParent();
}

function onNewFileClick(): void {
  uiStore.createFileModal = ModalState.Opened;
}

function onNewDirectoryClick(): void {
  uiStore.newDirectoryModal = ModalState.Opened;
}

function onUploadFileClick(): void {
  uiStore.uploadFileModal = ModalState.Opened;
}

function onAboutClick(): void {
  uiStore.aboutModal = ModalState.Opened;
}

function onCopyPathClick(): void {
  navigator.clipboard.writeText(fileManagerStore.currentPath).catch(() => {});
  toastService.pushToast({ message: 'Path copied to clipboard.', type: 'success' });
}

function onCopyLinkClick(): void {
  const url = new URL(globalThis.location.href);
  const disk = diskStore.currentDisk;
  const hashPath = `/${disk}${fileManagerStore.currentPath === '/' ? '' : fileManagerStore.currentPath}`;
  url.hash = hashPath.split('/').map(encodeURIComponent).join('/');
  navigator.clipboard.writeText(url.toString()).catch(() => {});
  toastService.pushToast({ message: 'Link copied to clipboard.', type: 'success' });
}
</script>

<template>
  <div class="navbar bg-base-100 shadow-sm px-4 relative z-50">
    <!-- Navigation -->
    <div class="navbar-start gap-1">
      <button
        class="btn btn-ghost btn-sm"
        :title="uiStore.treeVisible ? 'Hide tree' : 'Show tree'"
        @click="uiStore.setTreeVisible(!uiStore.treeVisible)"
      >
        <PanelLeftClose
          v-if="uiStore.treeVisible"
          :size="16"
        />
        <PanelLeft
          v-else
          :size="16"
        />
      </button>
      <button
        class="btn btn-ghost btn-sm"
        title="Back"
        :disabled="!fileManagerStore.canNavigatePrevious"
        @click="onBackClick"
      >
        <ArrowLeft :size="16" />
        <span class="hidden md:inline">Back</span>
      </button>
      <button
        class="btn btn-ghost btn-sm"
        title="Forward"
        :disabled="!fileManagerStore.canNavigateNext"
        @click="onForwardClick"
      >
        <ArrowRight :size="16" />
        <span class="hidden md:inline">Forward</span>
      </button>
      <button
        class="btn btn-ghost btn-sm"
        title="Up"
        :disabled="!fileManagerStore.canNavigateUp"
        @click="onUpClick"
      >
        <ArrowUp :size="16" />
        <span class="hidden md:inline">Up</span>
      </button>
      <button
        class="btn btn-ghost btn-sm"
        title="Reload"
        @click="onReloadClick"
      >
        <RefreshCw :size="16" />
        <span class="hidden md:inline">Reload</span>
      </button>
    </div>

    <!-- Actions -->
    <div class="navbar-center gap-1">
      <button
        class="btn btn-ghost btn-sm"
        title="New File"
        :disabled="isReadOnly"
        @click="onNewFileClick"
      >
        <FilePlus :size="16" />
        <span class="hidden md:inline">New File</span>
      </button>
      <button
        class="btn btn-ghost btn-sm"
        title="New Directory"
        :disabled="isReadOnly"
        @click="onNewDirectoryClick"
      >
        <FolderPlus :size="16" />
        <span class="hidden md:inline">New Directory</span>
      </button>
      <button
        class="btn btn-ghost btn-sm"
        title="Upload File"
        :disabled="isReadOnly"
        @click="onUploadFileClick"
      >
        <FileUp :size="16" />
        <span class="hidden md:inline">Upload File</span>
      </button>

      <div class="dropdown dropdown-bottom">
        <div
          tabindex="0"
          role="button"
          class="btn btn-ghost btn-sm"
          title="Copy Links"
        >
          <Link :size="16" />
          <span class="hidden md:inline">URL</span>
        </div>
        <ul
          tabindex="0"
          class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-52"
        >
          <li><a @click="onCopyPathClick">Copy Path</a></li>
          <li><a @click="onCopyLinkClick">Copy Link</a></li>
        </ul>
      </div>
    </div>

    <!-- Search Bar -->
    <div class="navbar-center hidden lg:flex ml-4 mr-auto">
      <SearchBar />
    </div>

    <!-- View Mode and Theme -->
    <div class="navbar-end gap-1">
      <button
        class="btn btn-ghost btn-sm"
        :title="isFullscreen ? 'Exit full screen' : 'Full screen'"
        @click="toggleFullscreen"
      >
        <Minimize
          v-if="isFullscreen"
          :size="16"
        />
        <Maximize
          v-else
          :size="16"
        />
      </button>
      <ThemeSelector />
      <button
        class="btn btn-ghost btn-sm"
        title="About"
        @click="onAboutClick"
      >
        <CircleQuestionMark :size="16" />
        <span class="hidden md:inline">About</span>
      </button>
    </div>
  </div>
</template>
