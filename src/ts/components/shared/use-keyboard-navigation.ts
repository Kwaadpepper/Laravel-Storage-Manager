import { useService } from "@ts/service-container";
import { onMounted, onUnmounted } from "vue";

export function useKeyboardNavigation(): void {
  const navigationService = useService('navigationService');

  function onDocKeyDown(e: KeyboardEvent): void {
    const target = e.target as HTMLElement | null;
    if (target?.tagName === 'INPUT' || target?.tagName === 'TEXTAREA' || target?.isContentEditable) {
      return;
    }
    if (e.key === 'Backspace') {
      e.preventDefault();
      navigationService.navigatePrevious();
    }
  }

  onMounted(() => {
    document.addEventListener('keydown', onDocKeyDown);
  });

  onUnmounted(() => {
    document.removeEventListener('keydown', onDocKeyDown);
  });
}
