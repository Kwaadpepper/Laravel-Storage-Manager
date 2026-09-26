<script setup lang="ts">
defineOptions({
  name: 'ContentWrapper',
});
import ContentDirectory from '@ts/components/layout/content/ContentDirectory.vue';
import ContentFile from '@ts/components/layout/content/ContentFile.vue';
import { useFileManagerStore } from '@ts/stores';
import { isDirectory, TreeNodeDirectory, TreeNodeFile } from '@ts/types';
import { computed } from 'vue';

type Item = TreeNodeDirectory | TreeNodeFile;

const props = withDefaults(
  defineProps<{
    item: Item;
    isListView?: boolean;
    tabIndex?: number;
  }>(),
  {
    isListView: false,
    tabIndex: -1,
  }
);

const emit = defineEmits<{
  (e: 'click', event: MouseEvent): void;
  (e: 'dblclick', event: MouseEvent): void;
  (e: 'contextmenu', event: MouseEvent): void;
}>();

const fileManagerStore = useFileManagerStore();

const isSelected = computed(() =>
  Object.values(fileManagerStore.selectedNodes).some(
    (node) => node.path === props.item.path
  )
);
</script>

<template>
  <div
    v-if="isListView"
    :class="`table-row selectable hover:cursor-pointer hover:bg-accent/30 ${isSelected ? 'bg-primary/20 ring-1 ring-primary' : ''}`"
    :title="item.name"
    role="row"
    :aria-selected="isSelected"
    :tabindex="tabIndex"
    :data-path="item.path"
    @click="(e) => emit('click', e)"
    @dblclick="(e) => emit('dblclick', e)"
    @contextmenu="(e) => emit('contextmenu', e)"
  >
    <ContentDirectory
      v-if="isDirectory(item)"
      :item="item"
    />
    <ContentFile
      v-else
      :item="item"
    />
  </div>

  <button
    v-else
    :class="`selectable hover:cursor-pointer rounded-lg hover:bg-accent/30 ${isSelected ? 'bg-primary/20 ring-1 ring-primary' : ''}`"
    :title="item.name"
    :aria-pressed="isSelected"
    :tabindex="tabIndex"
    :data-path="item.path"
    @click="(e) => emit('click', e)"
    @dblclick="(e) => emit('dblclick', e)"
    @contextmenu="(e) => emit('contextmenu', e)"
  >
    <ContentDirectory
      v-if="isDirectory(item)"
      :item="item"
      as-tile
    />
    <ContentFile
      v-else
      :item="item"
      as-tile
    />
  </button>
</template>
