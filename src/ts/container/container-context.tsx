import type { DiContainer } from '@ts/container/di-container';
import { createContext, useContext } from 'react';

export const ContainerContext = createContext<DiContainer | null>(null)

export function useContainer(): DiContainer {
    const container = useContext(ContainerContext)
    if (!container) {
        throw new Error('useContainer must be used within <ContainerContext.Provider>')
    }
    return container
}
