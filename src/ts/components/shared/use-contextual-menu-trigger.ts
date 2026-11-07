import { useContextualMenuStore } from '@ts/stores';
import { isContextMenuKeyboardShortcut, resolveContextualMenuAnchor } from '@ts/tools';
import type { Ref } from 'vue';
import { onMounted, onUnmounted } from 'vue';

export function useContextMenuTrigger(containerRef: Ref<HTMLElement | null>): void {
  const contextualMenuStore = useContextualMenuStore();

  function onContextMenu(event: MouseEvent): void {
    if (!(event.target instanceof HTMLElement)) {
      return;
    }

    const menuAnchor = resolveContextualMenuAnchor(event.target);
    if (!menuAnchor) {
      return;
    }

    event.preventDefault();
    contextualMenuStore.open(menuAnchor);
  }

  function onKeyDown(event: KeyboardEvent): void {
    if (!isContextMenuKeyboardShortcut(event)) {
      return;
    }

    const activeElement = document.activeElement;
    if (!(activeElement instanceof HTMLElement)) {
      return;
    }

    const menuAnchor = resolveContextualMenuAnchor(activeElement);
    if (!menuAnchor) {
      return;
    }

    event.preventDefault();
    contextualMenuStore.open(menuAnchor);
  }

  onMounted(() => {
    const container = containerRef.value;
    container?.addEventListener('contextmenu', onContextMenu);
    container?.addEventListener('keydown', onKeyDown);
  });

  onUnmounted(() => {
    const container = containerRef.value;
    container?.removeEventListener('contextmenu', onContextMenu);
    container?.removeEventListener('keydown', onKeyDown);
  });
}
