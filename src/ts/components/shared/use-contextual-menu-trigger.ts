import { useContainer } from '@ts/container';
import { isContextMenuKeyboardShortcut, resolveContextualMenuAnchor } from '@ts/tools';
import { RefObject, useEffect } from 'react';

export function useContextMenuTrigger(containerRef: RefObject<HTMLElement | null>): void {
  const { contextualMenuService } = useContainer().cradle;

  useEffect(() => {
    function onContextMenu(event: MouseEvent): void {
      if (!(event.target instanceof HTMLElement)) {
        return;
      }

      const menuAnchor = resolveContextualMenuAnchor(event.target);
      if (!menuAnchor) {
        return;
      }

      event.preventDefault();
      contextualMenuService.open(menuAnchor);
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
      contextualMenuService.open(menuAnchor);
    }

    const container = containerRef.current;
    container?.addEventListener('contextmenu', onContextMenu);
    container?.addEventListener('keydown', onKeyDown);

    return () => {
      container?.removeEventListener('contextmenu', onContextMenu);
      container?.removeEventListener('keydown', onKeyDown);
    };
  }, [contextualMenuService, containerRef]);
}
