import { Theme, useUiStore } from "@ts/stores";

const AVAILABLE_THEMES: Theme[] = ['auto', 'light', 'dark'];

export function isValidTheme(theme: string): theme is Theme {
  return AVAILABLE_THEMES.includes(theme as Theme)
}

export class ThemeService {

  private readonly matchMedia = globalThis.matchMedia?.('(prefers-color-scheme: dark)');

  constructor(
    private readonly uiStore: typeof useUiStore,
  ) {
  }

  public availableThemes(): readonly Theme[] {
    return AVAILABLE_THEMES;
  }

  public getSystemTheme(): Theme {
    return this.matchMedia?.matches ? 'dark' : 'light';
  }

  public getTheme(): Theme {
    return this.uiStore.getState().theme;
  }

  public setTheme(theme: Theme): void {
    this.uiStore.getState().setTheme(theme);
  }

  public listenToSystemThemeChanges(handler: (prefersDark: boolean) => void): (() => void) | undefined {
    if (!this.matchMedia) {
      console.warn('matchMedia is not supported in this environment. System theme changes will not be detected.');
      return;
    }

    this.matchMedia.addEventListener('change', (e) => handler(e.matches));

    return () => {
      this.matchMedia?.removeEventListener('change', (e) => handler(e.matches));
    };
  }
}
