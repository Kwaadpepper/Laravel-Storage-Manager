import { useUiStore } from '@ts/stores';
import { onMounted, onUnmounted, watch } from 'vue';

export function useThemeInitialization(): void {
  const uiStore = useUiStore();

  function applyTheme(theme: string): void {
    if (theme === 'auto') {
      document.documentElement.dataset.theme = uiStore.getSystemTheme();
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
    cleanup = uiStore.listenToSystemThemeChanges(() => {
      if (uiStore.theme === 'auto') {
        document.documentElement.dataset.theme = uiStore.getSystemTheme();
      }
    });
  });

  onUnmounted(() => {
    cleanup?.();
  });
}
