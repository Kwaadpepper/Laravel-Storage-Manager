<script setup lang="ts">
defineOptions({
  name: 'ActionBar',
});
import SearchBar from '@ts/components/layout/SearchBar.vue';
import ThemeSelector from '@ts/components/layout/ThemeSelector.vue';
import { useNavigation } from '@ts/composables';
import { ModalState, useConfigStore, useDiskStore, useFileManagerStore, useToastStore, useUiStore } from '@ts/stores';
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
  Search,
  Trash2,
  X,
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
const toastStore = useToastStore();
const navigation = useNavigation();

const isFullscreen = ref(false);
const isMobileSearchOpen = ref(false);

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
  navigation.refreshCurrentPath();
}

function onBackClick(): void {
  navigation.navigatePrevious();
}

function onForwardClick(): void {
  navigation.navigateNext();
}

function onUpClick(): void {
  navigation.navigateToParent();
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
  toastStore.pushToast({ message: t('path_copied'), type: 'success' });
}

function onCopyLinkClick(): void {
  const url = new URL(globalThis.location.href);
  const disk = diskStore.currentDisk;
  const hashPath = `/${disk}${fileManagerStore.currentPath === '/' ? '' : fileManagerStore.currentPath}`;
  url.hash = hashPath.split('/').map(encodeURIComponent).join('/');
  navigator.clipboard.writeText(url.toString()).catch(() => {});
  toastStore.pushToast({ message: t('link_copied'), type: 'success' });
}
</script>

<template>
  <div class="bg-base-100 shadow-sm px-2 sm:px-4 py-1.5 relative z-50 flex items-center justify-between gap-1 sm:gap-2 min-h-12 flex-nowrap">
    <!-- Mobile full-width search overlay when toggled -->
    <div
      v-if="isMobileSearchOpen"
      class="flex items-center gap-1 w-full md:hidden"
    >
      <div class="flex-1 min-w-0">
        <SearchBar />
      </div>
      <button
        class="btn btn-ghost btn-sm btn-circle shrink-0"
        :title="t('close')"
        type="button"
        @click="isMobileSearchOpen = false"
      >
        <X :size="16" />
      </button>
    </div>

    <!-- Normal bar when mobile search is closed -->
    <template v-else>
      <!-- Left / Navigation -->
      <div class="flex items-center gap-0.5 sm:gap-1 shrink-0">
        <button
          class="btn btn-ghost btn-sm btn-square xl:w-auto xl:px-2.5"
          :title="uiStore.treeVisible ? t('hide_tree') : t('show_tree')"
          type="button"
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
          class="btn btn-ghost btn-sm btn-square xl:w-auto xl:px-2.5"
          :title="t('back')"
          :disabled="!fileManagerStore.canNavigatePrevious"
          type="button"
          @click="onBackClick"
        >
          <ArrowLeft :size="16" />
          <span class="hidden 2xl:inline">{{ t('back') }}</span>
        </button>
        <button
          class="btn btn-ghost btn-sm btn-square xl:w-auto xl:px-2.5"
          :title="t('forward')"
          :disabled="!fileManagerStore.canNavigateNext"
          type="button"
          @click="onForwardClick"
        >
          <ArrowRight :size="16" />
          <span class="hidden 2xl:inline">{{ t('forward') }}</span>
        </button>
        <button
          class="btn btn-ghost btn-sm btn-square xl:w-auto xl:px-2.5"
          :title="t('up')"
          :disabled="!fileManagerStore.canNavigateUp"
          type="button"
          @click="onUpClick"
        >
          <ArrowUp :size="16" />
          <span class="hidden 2xl:inline">{{ t('up') }}</span>
        </button>
        <button
          class="btn btn-ghost btn-sm btn-square xl:w-auto xl:px-2.5"
          :title="t('reload')"
          type="button"
          @click="onReloadClick"
        >
          <RefreshCw :size="16" />
          <span class="hidden 2xl:inline">{{ t('reload') }}</span>
        </button>
      </div>

      <!-- Center / Actions -->
      <div class="flex items-center gap-0.5 sm:gap-1 shrink-0">
        <button
          class="btn btn-ghost btn-sm btn-square xl:w-auto xl:px-2.5"
          :title="t('new_file')"
          :disabled="isReadOnly"
          type="button"
          @click="onNewFileClick"
        >
          <FilePlus :size="16" />
          <span class="hidden 2xl:inline">{{ t('new_file') }}</span>
        </button>
        <button
          class="btn btn-ghost btn-sm btn-square xl:w-auto xl:px-2.5"
          :title="t('new_directory')"
          :disabled="isReadOnly"
          type="button"
          @click="onNewDirectoryClick"
        >
          <FolderPlus :size="16" />
          <span class="hidden 2xl:inline">{{ t('new_directory') }}</span>
        </button>
        <button
          class="btn btn-ghost btn-sm btn-square xl:w-auto xl:px-2.5"
          :title="t('upload_file')"
          :disabled="isReadOnly"
          type="button"
          @click="onUploadFileClick"
        >
          <FileUp :size="16" />
          <span class="hidden 2xl:inline">{{ t('upload_file') }}</span>
        </button>
        <button
          v-if="configStore.supportsTrash"
          class="btn btn-ghost btn-sm btn-square xl:w-auto xl:px-2.5"
          :title="t('trash')"
          type="button"
          @click="onTrashClick"
        >
          <Trash2 :size="16" />
          <span class="hidden 2xl:inline">{{ t('trash') }}</span>
        </button>

        <!-- URL dropdown -->
        <div class="dropdown dropdown-bottom">
          <div
            tabindex="0"
            role="button"
            class="btn btn-ghost btn-sm btn-square xl:w-auto xl:px-2.5"
            :title="t('url')"
          >
            <Link :size="16" />
            <span class="hidden 2xl:inline">{{ t('url') }}</span>
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

      <!-- Search Bar (displayed from lg to prevent tablet overlap) -->
      <div class="hidden lg:flex flex-1 min-w-0 max-w-xs xl:max-w-sm justify-center px-2">
        <SearchBar />
      </div>

      <!-- Right controls -->
      <div class="flex items-center gap-0.5 sm:gap-1 shrink-0 ml-auto">
        <!-- Search icon toggle when search bar is hidden (< lg) -->
        <button
          class="btn btn-ghost btn-sm btn-square lg:hidden"
          :title="t('search')"
          type="button"
          @click="isMobileSearchOpen = true"
        >
          <Search :size="16" />
        </button>

        <button
          class="btn btn-ghost btn-sm btn-square"
          :title="isFullscreen ? t('exit_full_screen') : t('full_screen')"
          type="button"
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
          class="btn btn-ghost btn-sm btn-square xl:w-auto xl:px-2.5"
          :title="t('about')"
          type="button"
          @click="onAboutClick"
        >
          <CircleQuestionMark :size="16" />
          <span class="hidden 2xl:inline">{{ t('about') }}</span>
        </button>
      </div>
    </template>
  </div>
</template>
