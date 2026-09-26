import { uniqueId } from '@ts/tools';
import { defineStore } from 'pinia';
import slugify from 'slugify';
import { ref } from 'vue';

export type AnchorName = string & { type: 'AnchorName' };

export function toAnchorName(name: string): AnchorName {
  const slug = slugify(name, { lower: true, strict: true }) || 'root';
  return `--cm-${slug}` as AnchorName;
}

export interface ContextualMenuItem {
  label: string;
  onClick: () => void;
}

export interface ContextualMenuSeparator {
  separator: true;
}

export type ContextualMenuEntry = ContextualMenuItem | ContextualMenuSeparator;

export const useContextualMenuStore = defineStore('contextualMenu', () => {
  const id = ref(uniqueId());
  const anchorName = ref<AnchorName>(toAnchorName(''));
  const visible = ref(false);
  const entries = ref<(ContextualMenuEntry & { id: string })[]>([]);

  function setMenu(
    newAnchorName: AnchorName,
    forEntries: ContextualMenuEntry[]
  ): void {
    entries.value = forEntries.map((entry) => ({
      ...entry,
      id: uniqueId(),
    }));
    anchorName.value = newAnchorName;
    visible.value = true;
  }

  function closeMenu(): void {
    visible.value = false;
    entries.value = [];
  }

  return {
    id,
    anchorName,
    visible,
    entries,
    setMenu,
    closeMenu,
  };
});

export type ContextualMenuStore = ReturnType<typeof useContextualMenuStore>;
