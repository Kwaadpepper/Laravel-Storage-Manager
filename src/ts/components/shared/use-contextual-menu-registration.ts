import { useContainer } from '@ts/container';
import { AnchorName, ContextualMenuEntry } from '@ts/stores';
import { useEffect } from 'react';

export function useContextualMenuRegistration(anchorName: AnchorName, entries: ContextualMenuEntry[]): void {
  const container = useContainer();
  const contextualMenuService = container.resolve('contextualMenuService');

  useEffect(() => {
    contextualMenuService.register(anchorName, entries);

    return () => {
      contextualMenuService.unregister(anchorName);
    };
  }, [anchorName, contextualMenuService, entries]);
}
