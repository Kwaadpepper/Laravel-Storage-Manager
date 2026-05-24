import { useContainer } from '@ts/container';
import { AnchorName, ContextualMenuEntry } from '@ts/stores';
import { useEffect } from 'react';

export function useContextualMenuRegistration(anchorName: AnchorName, entries: ContextualMenuEntry[]): void {
    const { cradle } = useContainer();

    useEffect(() => {
        cradle.contextualMenuService.register(anchorName, entries);

        return () => {
            cradle.contextualMenuService.unregister(anchorName);
        };
    }, [anchorName, cradle.contextualMenuService, entries]);
}
