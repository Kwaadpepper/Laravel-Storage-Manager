import { isValidTheme, Theme } from '@ts/services';
import { Path } from '@ts/types';
import { create } from 'zustand';

export enum ModalState {
  Closed = 'closed',
  Opened = 'opened',
}

const LOCAL_STORAGE_KEY = 'sm-theme';

function getLocalStorageTheme(): Theme {
    const storedTheme = localStorage[LOCAL_STORAGE_KEY];

    return isValidTheme(storedTheme) ?
        storedTheme : 'auto';
}

function setLocalStorageTheme(theme: Theme): void {
    localStorage[LOCAL_STORAGE_KEY] = theme;
}

type UiState = {
    theme: Theme;
    setTheme: (theme: UiState['theme']) => void;
}

export const useUiStore = create<UiState>((set) => ({
    theme: getLocalStorageTheme(),
    setTheme: (theme) => {
        console.debug(`Setting theme to ${theme}`)
        set({ theme })
        setLocalStorageTheme(theme)
    },
}))
