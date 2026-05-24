import { uniqueId } from '@ts/tools';
import slugify from 'slugify';
import { create } from 'zustand';

export type AnchorName = string & { type: 'AnchorName' }

export function toAnchorName(name: string): AnchorName {
  const slug = slugify(name, { lower: true, strict: true }) || 'root'
  return `--cm-${slug}` as AnchorName
}

export interface ContextualMenuItem {
  label: string
  onClick: () => void
}

export interface ContextualMenuSeparator {
  separator: true
}

export type ContextualMenuEntry = ContextualMenuItem | ContextualMenuSeparator

interface ContextMenuState {
  id: string,
  anchorName: AnchorName,
  visible: boolean,
  entries: (ContextualMenuEntry & { id: string })[]

  setMenu: (
    anchorName: AnchorName,
    entries: ContextualMenuEntry[]
  ) => void

  closeMenu: () => void
}

export const useContextualMenuStore = create<ContextMenuState>((set) => ({
  id: uniqueId(),
  anchorName: toAnchorName(''),
  visible: false,
  entries: [],

  setMenu: (
    anchorName: AnchorName,
    forEntries: ContextualMenuEntry[]
  ): void => {
    const entries: (ContextualMenuEntry & { id: string })[] = forEntries.map((entry) => ({
      ...entry, id: uniqueId()
    }));

    set(() => ({
      anchorName,
      visible: true,
      entries
    }));
  },

  closeMenu: (): void => {
    set({ visible: false, entries: [] });
  },
}))
