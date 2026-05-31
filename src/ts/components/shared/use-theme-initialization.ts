import { useContainer } from "@ts/container";
import { useUiStore } from "@ts/stores";
import { useEffect } from "react";

export function useThemeInitialization(): void {
  const { theme } = useUiStore()
  const container = useContainer();
  const themeService = container.resolve('themeService');

  useEffect(() => {
    if (theme === 'auto') {
      document.documentElement.dataset.theme = themeService.getSystemTheme();
    } else {
      document.documentElement.dataset.theme = theme;
    }
  }, [theme, themeService]);

  useEffect(() => {
    const cleanup = themeService.listenToSystemThemeChanges(() => {
      if (themeService.getTheme() === 'auto') {
        document.documentElement.dataset.theme = themeService.getSystemTheme();
      }
    });
    return () => {
      cleanup?.();
    };
  }, [themeService]);

}
