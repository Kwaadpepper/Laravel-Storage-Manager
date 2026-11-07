import { AnchorName, ContextualMenuEntry, useContextualMenuStore } from '@ts/stores';
import type { MaybeRefOrGetter } from 'vue';
import { onMounted, onUnmounted, toValue, watch } from 'vue';

export function useContextualMenuRegistration(
  anchorName: MaybeRefOrGetter<AnchorName>,
  entries: MaybeRefOrGetter<ContextualMenuEntry[]>
): void {
  const contextualMenuStore = useContextualMenuStore();

  let currentAnchor: AnchorName | null = null;

  function updateRegistration(): void {
    if (currentAnchor) {
      contextualMenuStore.unregister(currentAnchor);
    }
    const anchor = toValue(anchorName);
    const list = toValue(entries);
    contextualMenuStore.register(anchor, list);
    currentAnchor = anchor;
  }

  onMounted(() => {
    updateRegistration();
  });

  watch(
    [() => toValue(anchorName), () => toValue(entries)],
    () => {
      updateRegistration();
    },
    { deep: true }
  );

  onUnmounted(() => {
    if (currentAnchor) {
      contextualMenuStore.unregister(currentAnchor);
      currentAnchor = null;
    }
  });
}
