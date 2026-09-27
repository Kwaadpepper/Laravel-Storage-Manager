import type { Ref } from 'vue';
import { getCurrentInstance, onUnmounted, ref, toValue, watch } from 'vue';

export function useDelayedUnmount(
  value: Ref<boolean> | (() => boolean) | boolean,
  delay: number
): Ref<boolean> {
  const isCurrentlyMounted = ref(toValue(value));
  let timeoutId: ReturnType<typeof setTimeout> | null = null;

  watch(
    () => toValue(value),
    (val) => {
      if (timeoutId) {
        clearTimeout(timeoutId);
        timeoutId = null;
      }

      if (val) {
        isCurrentlyMounted.value = true;
      } else {
        timeoutId = setTimeout(() => {
          isCurrentlyMounted.value = false;
        }, delay);
      }
    },
    { immediate: true }
  );

  if (getCurrentInstance()) {
    onUnmounted(() => {
      if (timeoutId) {
        clearTimeout(timeoutId);
        timeoutId = null;
      }
    });
  }

  return isCurrentlyMounted;
}
