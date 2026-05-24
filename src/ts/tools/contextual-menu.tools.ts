import { AnchorName } from '@ts/stores';

const CONTEXTUAL_MENU_ATTR = 'data-contextual-menu';
export const CONTEXTUAL_MENU_SELECTOR = `[${CONTEXTUAL_MENU_ATTR}]`;

export function isContextMenuKeyboardShortcut(event: KeyboardEvent): boolean {
  return event.key === 'ContextMenu' || (event.shiftKey && event.key === 'F10');
}

export function resolveContextualMenuAnchor(source: HTMLElement): AnchorName | null {
  const directAnchor = source.closest(CONTEXTUAL_MENU_SELECTOR) as HTMLElement | null;
  if (directAnchor?.dataset.contextualMenu) {
    return directAnchor.dataset.contextualMenu as AnchorName;
  }

  const childAnchor = source.querySelector(CONTEXTUAL_MENU_SELECTOR) as HTMLElement | null;
  if (childAnchor?.dataset.contextualMenu) {
    return childAnchor.dataset.contextualMenu as AnchorName;
  }

  return null;
}

export function getContextualMenuAnchorSelector(anchorName: string): string {
  return `[${CONTEXTUAL_MENU_ATTR}="${CSS.escape(anchorName)}"]`;
}
