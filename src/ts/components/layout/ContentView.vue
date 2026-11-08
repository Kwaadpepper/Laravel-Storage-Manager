<script setup lang="ts">
defineOptions({
  name: 'ContentView',
});
import ContentWrapper from '@ts/components/layout/content/ContentWrapper.vue';
import SortHeader from '@ts/components/shared/SortHeader.vue';
import { useContextualMenuRegistration } from '@ts/components/shared/use-contextual-menu-registration';
import { useEventQueue, useFileManager, useNavigation, useUploadQueue } from '@ts/composables';
import {
  ContextualMenuEntry,
  ModalState,
  toAnchorName,
  useClipboardStore,
  useDiskStore,
  useFileManagerStore,
  usePickerStore,
  useSearchStore,
  useToastStore,
  useUiStore,
} from '@ts/stores';
import { isDirectory, TreeNodeDirectory, TreeNodeFile } from '@ts/types';
import { t } from '@ts/i18n';
import SelectionArea from '@viselect/vanilla';
import { FolderOpen } from 'lucide-vue-next';
import { computed, nextTick, onUnmounted, ref, watch } from 'vue';

type SortColumn = 'name' | 'type' | 'size' | 'extension' | 'visibility';

const fileManagerStore = useFileManagerStore();
const uiStore = useUiStore();
const searchStore = useSearchStore();
const clipboardStore = useClipboardStore();
const diskStore = useDiskStore();
const pickerStore = usePickerStore();
const toastStore = useToastStore();

const navigation = useNavigation();
const fileManager = useFileManager();
const uploadQueue = useUploadQueue();
const eventQueue = useEventQueue();

const isSearchMode = computed(() => fileManagerStore.currentPath === '/:search');
const directoryNodes = computed(() =>
  isSearchMode.value ? searchStore.directories : fileManagerStore.directoryNodes
);
const rawFileNodes = computed(() =>
  isSearchMode.value ? searchStore.files : fileManagerStore.fileNodes
);
const fileNodes = computed(() => {
  if (!pickerStore.scopedType) {
    return rawFileNodes.value;
  }
  return rawFileNodes.value.filter((file) => pickerStore.isFileAllowed(file));
});

const sortColumn = ref<SortColumn | null>(null);
const sortDirection = ref<'asc' | 'desc' | null>(null);
const isDraggingOver = ref(false);

const items = computed(() => {
  if (!sortColumn.value || !sortDirection.value) {
    return [...directoryNodes.value, ...fileNodes.value];
  }

  const dirFactor = sortDirection.value === 'asc' ? 1 : -1;
  const col = sortColumn.value;

  const sortFn = (
    a: TreeNodeDirectory | TreeNodeFile,
    b: TreeNodeDirectory | TreeNodeFile
  ) => {
    if (col === 'name') {
      return ('name' in a && 'name' in b ? a.name.localeCompare(b.name) : 0) * dirFactor;
    }
    if (col === 'size') {
      const sizeA = 'size' in a && a.size != null ? a.size : 0;
      const sizeB = 'size' in b && b.size != null ? b.size : 0;
      return (sizeA - sizeB) * dirFactor;
    }
    if (col === 'extension' || col === 'type') {
      const extA = 'extension' in a && a.extension ? a.extension : '';
      const extB = 'extension' in b && b.extension ? b.extension : '';
      return extA.localeCompare(extB) * dirFactor;
    }
    if (col === 'visibility') {
      const visA = a.visibility || '';
      const visB = b.visibility || '';
      return visA.localeCompare(visB) * dirFactor;
    }
    return 0;
  };

  const dirs = [...directoryNodes.value].sort(sortFn);
  const files = [...fileNodes.value].sort(sortFn);
  return [...dirs, ...files];
});

const isEmpty = computed(() => items.value.length === 0);
const listElementRef = ref<HTMLDivElement | null>(null);
const listWrapperRef = ref<HTMLDivElement | null>(null);
const tilesWrapperRef = ref<HTMLDivElement | null>(null);

const lastSelectedIndex = ref<number | null>(null);
let dragStartSelection: (TreeNodeDirectory | TreeNodeFile)[] = [];
let hasDragged = false;

function handleSort(column: SortColumn): void {
  if (sortColumn.value === column) {
    if (sortDirection.value === 'asc') {
      sortDirection.value = 'desc';
    } else if (sortDirection.value === 'desc') {
      sortColumn.value = null;
      sortDirection.value = null;
    }
  } else {
    sortColumn.value = column;
    sortDirection.value = 'asc';
  }

  if (!isSearchMode.value) {
    const backendSortBy = sortColumn.value === 'type' ? 'extension' : (sortColumn.value ?? undefined);
    navigation.setSort(backendSortBy, sortDirection.value ?? undefined);
  }
}

const bgAnchorName = computed(() => toAnchorName(`bg-${fileManagerStore.currentPath}`));

const bgEntries = computed<ContextualMenuEntry[]>(() => [
  ...(clipboardStore.hasEntries
    ? [
        {
          label: t('paste'),
          onClick: async () => {
            const isCut = clipboardStore.getIsConsumingMode();
            const entry = clipboardStore.getLastEntry();
            if (!entry || entry.length === 0) {
              toastStore.pushToast({ message: t('clipboard_empty'), type: 'info' });
              return;
            }

            const events = entry.map((path) => ({
              id: `${isCut ? 'move' : 'copy'}-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`,
              type: (isCut ? 'MOVE' : 'COPY') as 'MOVE' | 'COPY',
              sourcePath: path,
              destinationPath: fileManagerStore.currentPath,
              execute: async () => {
                if (isCut) {
                  await fileManager.move(path, fileManagerStore.currentPath);
                } else {
                  await fileManager.copy(path, fileManagerStore.currentPath);
                }
              },
            }));

            eventQueue.pushBatch(events);
            clipboardStore.clearEntries();
          },
        },
      ]
    : []),
]);

useContextualMenuRegistration(bgAnchorName, bgEntries);

const selectedFile = computed(() => Object.values(fileManagerStore.selectedNodes)[0] || null);

const rovingIdx = computed(() => {
  if (selectedFile.value === null) {
    return items.value.length > 0 ? 0 : -1;
  }
  return items.value.findIndex((i) => i.path === selectedFile.value?.path);
});

watch(
  [() => fileManagerStore.selectedNodes, () => uiStore.viewMode],
  async ([selectedNodes, viewMode]) => {
    const selectedNodesArray = Object.values(selectedNodes);
    const firstItem = selectedNodesArray[0];

    if (selectedNodesArray.length !== 1 || viewMode !== 'list') {
      return;
    }

    await nextTick();
    const el = listElementRef.value?.querySelector<HTMLElement>(`[data-path="${firstItem.path}"]`);
    if (el) {
      el.scrollIntoView({ block: 'nearest' });
      el.focus({ preventScroll: true });
    }
  }
);

function onListKeyDown(e: KeyboardEvent): void {
  if (items.value.length === 0) {
    return;
  }

  const currentIndex = selectedFile.value
    ? items.value.findIndex((i) => i.path === selectedFile.value?.path)
    : -1;

  switch (e.key) {
    case 'ArrowDown':
      e.preventDefault();
      fileManagerStore.selectNodes(
        items.value[currentIndex < items.value.length - 1 ? currentIndex + 1 : 0]
      );
      break;

    case 'ArrowUp':
      e.preventDefault();
      fileManagerStore.selectNodes(
        items.value[currentIndex > 0 ? currentIndex - 1 : items.value.length - 1]
      );
      break;

    case 'Enter':
      if (selectedFile.value === null) return;
      e.preventDefault();
      e.stopPropagation();

      if (isDirectory(selectedFile.value)) {
        navigation.navigateTo(selectedFile.value.path);
      } else if (pickerStore.isPickerMode) {
        pickerStore.selectFile(selectedFile.value);
      } else {
        uiStore.targetFilePath = selectedFile.value.path;
        requestAnimationFrame(() => {
          uiStore.viewFileModal = ModalState.Opened;
        });
      }
      break;

    case 'Escape':
      e.preventDefault();
      fileManagerStore.selectNodes();
      lastSelectedIndex.value = null;
      break;

    case 'a':
    case 'A':
      if (e.ctrlKey || e.metaKey) {
        e.preventDefault();
        fileManagerStore.selectNodes(...items.value);
      }
      break;
  }
}

function handleItemClick(
  item: TreeNodeDirectory | TreeNodeFile,
  index: number,
  e: MouseEvent
): void {
  e.stopPropagation();
  const isMac = navigator.userAgent.includes('Mac');
  const isMultiToggle = isMac ? e.metaKey : e.ctrlKey;
  const isRangeSelect = e.shiftKey;
  const selectedNodesArray = Object.values(fileManagerStore.selectedNodes);

  if (isRangeSelect && lastSelectedIndex.value !== null) {
    const start = Math.min(lastSelectedIndex.value, index);
    const end = Math.max(lastSelectedIndex.value, index);
    const range = items.value.slice(start, end + 1);

    if (isMultiToggle) {
      const newSelection = [...selectedNodesArray];
      for (const node of range) {
        if (!newSelection.some((n) => n.path === node.path)) {
          newSelection.push(node);
        }
      }
      fileManagerStore.selectNodes(...newSelection);
    } else {
      fileManagerStore.selectNodes(...range);
    }
  } else if (isMultiToggle) {
    const isSelected = selectedNodesArray.some((n) => n.path === item.path);
    if (isSelected) {
      fileManagerStore.selectNodes(...selectedNodesArray.filter((n) => n.path !== item.path));
    } else {
      fileManagerStore.selectNodes(...selectedNodesArray, item);
    }
    lastSelectedIndex.value = index;
  } else {
    const isSelected = selectedNodesArray.some((n) => n.path === item.path);
    if (isSelected) {
      handleItemDoubleClick(item);
    } else {
      fileManagerStore.selectNodes(item);
      lastSelectedIndex.value = index;
    }
  }
}

function handleItemDoubleClick(item: TreeNodeDirectory | TreeNodeFile): void {
  if (isDirectory(item)) {
    navigation.navigateTo(item.path);
  } else if (pickerStore.isPickerMode) {
    pickerStore.selectFile(item);
  } else {
    uiStore.targetFilePath = item.path;
    uiStore.viewFileModal = ModalState.Opened;
  }
}

function handleItemContextMenu(item: TreeNodeDirectory | TreeNodeFile): void {
  const isSelected = Object.values(fileManagerStore.selectedNodes).some(
    (n) => n.path === item.path
  );
  if (!isSelected) {
    fileManagerStore.selectNodes(item);
    const index = items.value.findIndex((i) => i.path === item.path);
    lastSelectedIndex.value = index !== -1 ? index : null;
  }
}

function onClickOutside(e: MouseEvent): void {
  if (hasDragged) return;
  const target = e.target as HTMLElement | null;
  if (target?.closest('.selectable')) return;
  fileManagerStore.selectNodes();
  lastSelectedIndex.value = null;
}

const onDragOver = (e: DragEvent) => {
  e.preventDefault();
  if (e.dataTransfer?.types.includes('Files')) {
    isDraggingOver.value = true;
  }
};

const onDragLeave = (e: DragEvent) => {
  e.preventDefault();
  isDraggingOver.value = false;
};

const onDrop = (e: DragEvent) => {
  e.preventDefault();
  isDraggingOver.value = false;

  if (e.dataTransfer?.files && e.dataTransfer.files.length > 0) {
    const files = Array.from(e.dataTransfer.files);
    const currentDisk = diskStore.currentDisk;
    if (currentDisk) {
      uploadQueue.enqueueUploads(files, fileManagerStore.currentPath, currentDisk);
    }
  }
};

// SelectionArea instances
let listSelection: SelectionArea | null = null;
let tilesSelection: SelectionArea | null = null;

function setupSelection(container: HTMLElement) {
  const selection = new SelectionArea({
    selectables: ['.selectable'],
    boundaries: [container],
    startAreas: [container],
  });

  selection.on('beforestart', ({ event }) => {
    const target = event?.target as Element | null;
    if (target?.closest('.selectable')) {
      return false;
    }
    return true;
  });

  selection.on('start', ({ event, selection: sel }) => {
    hasDragged = false;
    const isMultiToggle = event?.ctrlKey || event?.metaKey || event?.shiftKey;
    sel.clearSelection();

    if (isMultiToggle) {
      dragStartSelection = Object.values(fileManagerStore.selectedNodes);
    } else {
      dragStartSelection = [];
      fileManagerStore.selectNodes();
      lastSelectedIndex.value = null;
    }
  });

  selection.on('move', ({ store, event }) => {
    hasDragged = true;
    const isMultiToggle = event?.ctrlKey || event?.metaKey || event?.shiftKey;
    const currentItems = items.value;

    const boxPaths = store.selected
      .map((el) => el.getAttribute('data-path'))
      .filter(Boolean) as string[];

    const boxNodes = boxPaths
      .map((path) => currentItems.find((i) => i.path === path))
      .filter(Boolean) as (TreeNodeDirectory | TreeNodeFile)[];

    if (isMultiToggle) {
      const newSelection = [...dragStartSelection];
      for (const node of boxNodes) {
        if (!newSelection.some((n) => n.path === node.path)) {
          newSelection.push(node);
        }
      }
      fileManagerStore.selectNodes(...newSelection);
    } else {
      fileManagerStore.selectNodes(...boxNodes);
    }
  });

  return selection;
}

const listSentinelRef = ref<HTMLDivElement | null>(null);
const tilesSentinelRef = ref<HTMLDivElement | null>(null);
let listObserver: IntersectionObserver | null = null;
let tilesObserver: IntersectionObserver | null = null;

function setupObserver(
  el: HTMLElement,
  wrapper: HTMLElement | null,
  onIntersect: () => void
): IntersectionObserver {
  const observer = new IntersectionObserver(
    (entries) => {
      if (entries.some((entry) => entry.isIntersecting)) {
        onIntersect();
      }
    },
    {
      root: wrapper,
      rootMargin: '200px',
      threshold: 0.1,
    }
  );
  observer.observe(el);
  return observer;
}

watch(
  [listWrapperRef, tilesWrapperRef, listSentinelRef, tilesSentinelRef],
  ([listWrapper, tilesWrapper, listSentinel, tilesSentinel]) => {
    if (listWrapper && !listSelection) {
      listSelection = setupSelection(listWrapper);
    }
    if (tilesWrapper && !tilesSelection) {
      tilesSelection = setupSelection(tilesWrapper);
    }
    if (listSentinel && listWrapper && !listObserver) {
      listObserver = setupObserver(listSentinel, listWrapper, () => {
        if (!isSearchMode.value && fileManagerStore.pagination?.hasMore) {
          navigation.loadMoreFiles();
        }
      });
    }
    if (tilesSentinel && tilesWrapper && !tilesObserver) {
      tilesObserver = setupObserver(tilesSentinel, tilesWrapper, () => {
        if (!isSearchMode.value && fileManagerStore.pagination?.hasMore) {
          navigation.loadMoreFiles();
        }
      });
    }
  },
  { flush: 'post' }
);

onUnmounted(() => {
  listSelection?.destroy();
  tilesSelection?.destroy();
  listSelection = null;
  tilesSelection = null;
  listObserver?.disconnect();
  tilesObserver?.disconnect();
  listObserver = null;
  tilesObserver = null;
});
</script>

<template>
  <div
    class="relative h-full"
    @dragover="onDragOver"
    @dragleave="onDragLeave"
    @drop="onDrop"
  >
    <div
      v-if="isDraggingOver"
      class="absolute inset-0 bg-primary/20 backdrop-blur-sm z-50 flex items-center justify-center pointer-events-none rounded-box border-2 border-primary border-dashed m-2"
    >
      <div class="bg-base-100 p-8 rounded-xl shadow-2xl flex flex-col items-center gap-4 text-primary">
        <FolderOpen :size="64" />
        <h2 class="text-2xl font-bold">
          Drop files to upload to {{ fileManagerStore.currentPath }}
        </h2>
      </div>
    </div>

    <!-- List View -->
    <div
      ref="listWrapperRef"
      :class="`overflow-auto h-full pb-20 ${uiStore.viewMode === 'list' ? '' : 'hidden'}`"
    >
      <div
        ref="listElementRef"
        :data-contextual-menu="bgAnchorName"
        :style="{ anchorName: bgAnchorName }"
        class="h-full"
        role="grid"
        aria-label="File list"
        :tabindex="rovingIdx === -1 ? 0 : -1"
        @keydown="onListKeyDown"
        @click="onClickOutside"
      >
        <div class="table table-zebra w-full">
          <div class="table-header-group">
            <div class="table-row">
              <SortHeader
                :label="t('name')"
                :direction="sortColumn === 'name' ? sortDirection : null"
                @click="handleSort('name')"
              />
              <SortHeader
                :label="t('type')"
                :direction="sortColumn === 'type' ? sortDirection : null"
                @click="handleSort('type')"
              />
              <SortHeader
                :label="t('size')"
                :direction="sortColumn === 'size' ? sortDirection : null"
                @click="handleSort('size')"
              />
              <SortHeader
                :label="t('extension')"
                :direction="sortColumn === 'extension' ? sortDirection : null"
                @click="handleSort('extension')"
              />
              <SortHeader
                :label="t('visibility')"
                :direction="sortColumn === 'visibility' ? sortDirection : null"
                @click="handleSort('visibility')"
              />
            </div>
          </div>
          <div class="table-row-group">
            <ContentWrapper
              v-for="(item, i) in items"
              :key="item.path"
              :item="item"
              :tab-index="rovingIdx === i ? 0 : -1"
              is-list-view
              @click="(e) => handleItemClick(item, i, e)"
              @dblclick="() => handleItemDoubleClick(item)"
              @contextmenu="() => handleItemContextMenu(item)"
            />
          </div>
        </div>

        <div
          ref="listSentinelRef"
          class="w-full py-4 flex justify-center items-center"
        >
          <span
            v-if="fileManagerStore.isLoadingMore"
            class="loading loading-spinner loading-md text-primary"
          />
        </div>

        <div
          v-if="isEmpty"
          class="flex flex-col items-center justify-center py-16 text-base-content/40 pointer-events-none"
        >
          <FolderOpen
            :size="48"
            class="mb-2"
          />
          <template v-if="isSearchMode">
            <p>{{ searchStore.isSearching ? t('searching') : t('no_results') }}</p>
            <p class="text-sm mt-1">
              {{ t('folders_scanned', { count: searchStore.scannedPathsCount }) }}
            </p>
          </template>
          <p v-else>
            {{ t('empty_folder') }}
          </p>
        </div>
      </div>
    </div>

    <!-- Tiles View -->
    <div
      ref="tilesWrapperRef"
      :class="`p-2 overflow-y-auto h-full ${uiStore.viewMode === 'tiles' ? '' : 'hidden'}`"
    >
      <div
        ref="tilesElementRef"
        :data-contextual-menu="bgAnchorName"
        :style="{ anchorName: bgAnchorName }"
        class="h-full min-h-[50vh]"
        role="grid"
        aria-label="File tiles"
        :tabindex="rovingIdx === -1 ? 0 : -1"
        @keydown="onListKeyDown"
        @click="onClickOutside"
      >
        <div
          v-if="isEmpty"
          class="flex flex-col items-center justify-center py-16 text-base-content/40 pointer-events-none w-full"
        >
          <FolderOpen
            :size="48"
            class="mb-2"
          />
          <template v-if="isSearchMode">
            <p>{{ searchStore.isSearching ? t('searching') : t('no_results') }}</p>
            <p class="text-sm mt-1">
              {{ t('folders_scanned', { count: searchStore.scannedPathsCount }) }}
            </p>
          </template>
          <p v-else>
            {{ t('empty_folder') }}
          </p>
        </div>
        <div
          v-else
          class="flex flex-wrap gap-2"
        >
          <ContentWrapper
            v-for="(item, i) in items"
            :key="item.path"
            :item="item"
            :tab-index="rovingIdx === i ? 0 : -1"
            @click="(e) => handleItemClick(item, i, e)"
            @dblclick="() => handleItemDoubleClick(item)"
            @contextmenu="() => handleItemContextMenu(item)"
          />
        </div>

        <div
          ref="tilesSentinelRef"
          class="w-full py-4 flex justify-center items-center"
        >
          <span
            v-if="fileManagerStore.isLoadingMore"
            class="loading loading-spinner loading-md text-primary"
          />
        </div>
      </div>
    </div>
  </div>
</template>
