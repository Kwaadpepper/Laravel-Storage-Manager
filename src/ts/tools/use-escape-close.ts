import { onMounted, onUnmounted } from 'vue';

export function useEscapeClose(onClose: () => void): void {
  onMounted(() => {
    const handleKeydown = (e: KeyboardEvent): void => {
      if (e.key === 'Escape') {
        onClose();
      }
    };
    window.addEventListener('keydown', handleKeydown);
    onUnmounted(() => {
      window.removeEventListener('keydown', handleKeydown);
    });
  });
}
