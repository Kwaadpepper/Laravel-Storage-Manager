import { useService } from '@ts/service-container';
import { useUiStore } from '@ts/stores';
import { onMounted, onUnmounted, watch } from 'vue';

export function useThemeInitialization(): void {
  const uiStore = useUiStore();
  const themeService = useService('themeService');

  function applyTheme(theme: string): void {
    if (theme === 'auto') {
      document.documentElement.dataset.theme = themeService.getSystemTheme();
    } else {
      document.documentElement.dataset.theme = theme;
    }
  }

  watch(
    () => uiStore.theme,
    (theme) => {
      applyTheme(theme);
    },
    { immediate: true }
  );

  let cleanup: (() => void) | undefined;
  onMounted(() => {
    cleanup = themeService.listenToSystemThemeChanges(() => {
      if (uiStore.theme === 'auto') {
        document.documentElement.dataset.theme = themeService.getSystemTheme();
      }
    });
  });

  onUnmounted(() => {
    cleanup?.();
  });
}
