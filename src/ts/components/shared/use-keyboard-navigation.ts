import { useNavigation } from '@ts/composables';
import { onMounted, onUnmounted } from 'vue';

export function useKeyboardNavigation(): void {
  const navigation = useNavigation();

  function onDocKeyDown(e: KeyboardEvent): void {
    const target = e.target as HTMLElement | null;
    if (target?.tagName === 'INPUT' || target?.tagName === 'TEXTAREA' || target?.isContentEditable) {
      return;
    }
    if (e.key === 'Backspace') {
      e.preventDefault();
      navigation.navigatePrevious();
    }
  }

  onMounted(() => {
    document.addEventListener('keydown', onDocKeyDown);
  });

  onUnmounted(() => {
    document.removeEventListener('keydown', onDocKeyDown);
  });
}
