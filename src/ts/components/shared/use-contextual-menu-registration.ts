import { useService } from '@ts/service-container';
import { AnchorName, ContextualMenuEntry } from '@ts/stores';
import type { MaybeRefOrGetter } from 'vue';
import { onMounted, onUnmounted, toValue, watch } from 'vue';

export function useContextualMenuRegistration(
  anchorName: MaybeRefOrGetter<AnchorName>,
  entries: MaybeRefOrGetter<ContextualMenuEntry[]>
): void {
  const contextualMenuService = useService('contextualMenuService');

  let currentAnchor: AnchorName | null = null;

  function updateRegistration(): void {
    if (currentAnchor) {
      contextualMenuService.unregister(currentAnchor);
    }
    const anchor = toValue(anchorName);
    const list = toValue(entries);
    contextualMenuService.register(anchor, list);
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
      contextualMenuService.unregister(currentAnchor);
      currentAnchor = null;
    }
  });
}
