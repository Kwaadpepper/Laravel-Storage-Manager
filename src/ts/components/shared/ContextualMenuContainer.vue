<script setup lang="ts">
defineOptions({
  name: 'ContextualMenuContainer',
});
import { useContextualMenuStore } from '@ts/stores';
import { createFocusTrap, FocusTrap as VanillaFocusTrap } from 'focus-trap';
import { computed, nextTick, onUnmounted, ref, watch } from 'vue';

const contextualMenuStore = useContextualMenuStore();
const popoverRef = ref<HTMLDivElement | null>(null);
let trap: VanillaFocusTrap | null = null;

const isActive = computed(
  () => contextualMenuStore.visible && contextualMenuStore.entries.length > 0
);

watch(isActive, async (active) => {
  await nextTick();
  const popover = popoverRef.value;
  if (!popover) return;

  const isOpen = popover.matches(':popover-open');
  if (active) {
    if (!isOpen) {
      popover.showPopover();
    }
    if (!trap) {
      trap = createFocusTrap(popover, {
        escapeDeactivates: true,
        onDeactivate: () => contextualMenuStore.closeMenu(),
        clickOutsideDeactivates: true,
        returnFocusOnDeactivate: true,
        fallbackFocus: () => popoverRef.value ?? document.body,
      });
    }
    trap.activate();
  } else {
    if (trap) {
      trap.deactivate();
      trap = null;
    }
    if (isOpen) {
      popover.hidePopover();
    }
  }
});

onUnmounted(() => {
  if (trap) {
    trap.deactivate();
    trap = null;
  }
});
</script>

<template>
  <div
    :id="contextualMenuStore.id"
    ref="popoverRef"
    tabindex="-1"
    :style="{
      position: 'fixed',
      positionAnchor: contextualMenuStore.anchorName,
      top: 'calc(anchor(bottom) + 0.25rem)',
      left: 'anchor(left)',
    }"
    class="dropdown menu z-50 w-52 rounded-box border border-base-300 bg-base-100/95 shadow-lg ring-1 ring-base-content/10 backdrop-blur-sm"
    popover="manual"
  >
    <ul>
      <template
        v-for="item in contextualMenuStore.entries"
        :key="item.id"
      >
        <li
          v-if="'separator' in item"
          class="divider"
        />
        <li v-else>
          <button
            class="btn btn-ghost"
            :title="item.label"
            @click="
              item.onClick();
              contextualMenuStore.closeMenu();
            "
          >
            {{ item.label }}
          </button>
        </li>
      </template>
    </ul>
  </div>
</template>
