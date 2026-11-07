<script setup lang="ts">
defineOptions({
  name: 'ActionBar',
});
import SearchBar from '@ts/components/layout/SearchBar.vue';
import ThemeSelector from '@ts/components/layout/ThemeSelector.vue';
import { useService } from '@ts/service-container';
import { ModalState, useConfigStore, useDiskStore, useFileManagerStore, useUiStore } from '@ts/stores';
import { t } from '@ts/i18n';
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
  Trash2,
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

function onTrashClick(): void {
  uiStore.trashModal = ModalState.Opened;
}

function onAboutClick(): void {
  uiStore.aboutModal = ModalState.Opened;
}

function onCopyPathClick(): void {
  navigator.clipboard.writeText(fileManagerStore.currentPath).catch(() => {});
  toastService.pushToast({ message: t('path_copied'), type: 'success' });
}

function onCopyLinkClick(): void {
  const url = new URL(globalThis.location.href);
  const disk = diskStore.currentDisk;
  const hashPath = `/${disk}${fileManagerStore.currentPath === '/' ? '' : fileManagerStore.currentPath}`;
  url.hash = hashPath.split('/').map(encodeURIComponent).join('/');
  navigator.clipboard.writeText(url.toString()).catch(() => {});
  toastService.pushToast({ message: t('link_copied'), type: 'success' });
}
</script>

<template>
  <div class="navbar bg-base-100 shadow-sm px-4 relative z-50">
    <!-- Navigation -->
    <div class="navbar-start gap-1">
      <button
        class="btn btn-ghost btn-sm"
        :title="uiStore.treeVisible ? t('hide_tree') : t('show_tree')"
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
        :title="t('back')"
        :disabled="!fileManagerStore.canNavigatePrevious"
        @click="onBackClick"
      >
        <ArrowLeft :size="16" />
        <span class="hidden md:inline">{{ t('back') }}</span>
      </button>
      <button
        class="btn btn-ghost btn-sm"
        :title="t('forward')"
        :disabled="!fileManagerStore.canNavigateNext"
        @click="onForwardClick"
      >
        <ArrowRight :size="16" />
        <span class="hidden md:inline">{{ t('forward') }}</span>
      </button>
      <button
        class="btn btn-ghost btn-sm"
        :title="t('up')"
        :disabled="!fileManagerStore.canNavigateUp"
        @click="onUpClick"
      >
        <ArrowUp :size="16" />
        <span class="hidden md:inline">{{ t('up') }}</span>
      </button>
      <button
        class="btn btn-ghost btn-sm"
        :title="t('reload')"
        @click="onReloadClick"
      >
        <RefreshCw :size="16" />
        <span class="hidden md:inline">{{ t('reload') }}</span>
      </button>
    </div>

    <!-- Actions -->
    <div class="navbar-center gap-1">
      <button
        class="btn btn-ghost btn-sm"
        :title="t('new_file')"
        :disabled="isReadOnly"
        @click="onNewFileClick"
      >
        <FilePlus :size="16" />
        <span class="hidden md:inline">{{ t('new_file') }}</span>
      </button>
      <button
        class="btn btn-ghost btn-sm"
        :title="t('new_directory')"
        :disabled="isReadOnly"
        @click="onNewDirectoryClick"
      >
        <FolderPlus :size="16" />
        <span class="hidden md:inline">{{ t('new_directory') }}</span>
      </button>
      <button
        class="btn btn-ghost btn-sm"
        :title="t('upload_file')"
        :disabled="isReadOnly"
        @click="onUploadFileClick"
      >
        <FileUp :size="16" />
        <span class="hidden md:inline">{{ t('upload_file') }}</span>
      </button>
      <button
        v-if="configStore.supportsTrash"
        class="btn btn-ghost btn-sm"
        :title="t('trash')"
        @click="onTrashClick"
      >
        <Trash2 :size="16" />
        <span class="hidden md:inline">{{ t('trash') }}</span>
      </button>

      <div class="dropdown dropdown-bottom">
        <div
          tabindex="0"
          role="button"
          class="btn btn-ghost btn-sm"
          :title="t('url')"
        >
          <Link :size="16" />
          <span class="hidden md:inline">{{ t('url') }}</span>
        </div>
        <ul
          tabindex="0"
          class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-52"
        >
          <li><a @click="onCopyPathClick">{{ t('copy_path') }}</a></li>
          <li><a @click="onCopyLinkClick">{{ t('copy_link') }}</a></li>
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
        :title="isFullscreen ? t('exit_full_screen') : t('full_screen')"
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
        :title="t('about')"
        @click="onAboutClick"
      >
        <CircleQuestionMark :size="16" />
        <span class="hidden md:inline">{{ t('about') }}</span>
      </button>
    </div>
  </div>
</template>
