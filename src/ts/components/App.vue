<script setup lang="ts">
defineOptions({
  name: 'App',
});
import { ref } from 'vue';
import { LayoutGrid, List } from 'lucide-vue-next';
import { useUiStore } from '@ts/stores';
import ActionBar from './layout/ActionBar.vue';
import BreadcrumbBar from './layout/BreadcrumbBar.vue';
import ContentView from './layout/ContentView.vue';
import GlobalLoader from './layout/GlobalLoader.vue';
import SelectionBar from './layout/selection-bar/SelectionBar.vue';
import TreeView from './layout/tree-view/TreeView.vue';
import ModalContainer from './modals/ModalContainer.vue';
import ContextualMenuContainer from './shared/ContextualMenuContainer.vue';
import ToastContainer from './shared/ToastContainer.vue';
import { useAppInitialization } from './shared/use-app-initialization';
import { useContextMenuTrigger } from './shared/use-contextual-menu-trigger';
import { useKeyboardNavigation } from './shared/use-keyboard-navigation';
import { useSearchWorker } from './shared/use-search-worker';
import { useThemeInitialization } from './shared/use-theme-initialization';

const appRef = ref<HTMLDivElement | null>(null);
const uiStore = useUiStore();

useAppInitialization();
useThemeInitialization();
useKeyboardNavigation();
useContextMenuTrigger(appRef);
useSearchWorker();
</script>

<template>
  <div
    ref="appRef"
    class="h-screen bg-base-200 flex flex-col overflow-hidden"
  >
    <!-- Navbar + action bar -->
    <ActionBar />

    <!-- Selection bar -->
    <SelectionBar />

    <!-- Main layout -->
    <div class="flex flex-row gap-4 p-4 flex-1 min-h-0">
      <div
        v-if="uiStore.treeVisible"
        class="w-64 overflow-hidden flex flex-col"
      >
        <TreeView />
      </div>

      <div class="flex flex-col gap-2 flex-1 min-w-0 overflow-hidden">
        <div class="bg-base-100 rounded-box px-4 py-2 shadow-sm shrink-0 flex items-center justify-between gap-2 z-20 relative">
          <div class="flex-1 min-w-0 overflow-hidden">
            <BreadcrumbBar />
          </div>
          <div class="shrink-0 flex items-center gap-1">
            <button
              class="btn btn-ghost btn-sm"
              :title="uiStore.viewMode === 'list' ? 'List view' : 'Tiles view'"
              type="button"
              @click="uiStore.setViewMode(uiStore.viewMode === 'list' ? 'tiles' : 'list')"
            >
              <List
                v-if="uiStore.viewMode === 'list'"
                :size="16"
              />
              <LayoutGrid
                v-else
                :size="16"
              />
            </button>
            <GlobalLoader />
          </div>
        </div>
        <div class="bg-base-100 rounded-box shadow-sm flex-1 overflow-hidden">
          <ContentView />
        </div>
      </div>
    </div>

    <ToastContainer
      :top="true"
      :right="true"
    />
    <ContextualMenuContainer />

    <!-- Modals -->
    <ModalContainer />
  </div>
</template>
