<script setup lang="ts">
defineOptions({
  name: 'ThemeSelector',
});
import { useUiStore } from '@ts/stores';
import { Moon, Sun, SunMoon } from 'lucide-vue-next';
import { onMounted, ref, watch } from 'vue';

const uiStore = useUiStore();

const themeChangeInputRef = ref<HTMLInputElement | null>(null);

function updateIndeterminate(theme: string) {
  if (themeChangeInputRef.value) {
    themeChangeInputRef.value.indeterminate = theme === 'auto';
  }
}

watch(() => uiStore.theme, (theme) => {
  updateIndeterminate(theme);
});

onMounted(() => {
  updateIndeterminate(uiStore.theme);
});

function rotateTheme() {
  switch (uiStore.theme) {
    case 'auto':
      uiStore.setTheme('light');
      break;
    case 'light':
      uiStore.setTheme('dark');
      break;
    case 'dark':
      uiStore.setTheme('auto');
      break;
  }
}

function onKeyDownThemeSwapInput(e: KeyboardEvent): void {
  if (e.key === 'Enter') {
    rotateTheme();
  }
}
</script>

<template>
  <div class="theme-selector">
    <button
      class="btn btn-ghost btn-sm swap swap-rotate"
      title="Toggle theme"
      @click="rotateTheme"
    >
      <input
        ref="themeChangeInputRef"
        class="hidden w-0 h-0"
        type="checkbox"
        :checked="uiStore.theme === 'light'"
        aria-label="Toggle theme"
        @change="rotateTheme"
        @keydown="onKeyDownThemeSwapInput"
      >
      <span class="flex justify-center align-middle items-center swap-indeterminate">
        <SunMoon />
      </span>
      <span class="flex justify-center align-middle items-center swap-off">
        <Moon />
      </span>
      <span class="flex justify-center align-middle items-center swap-on">
        <Sun />
      </span>
    </button>
  </div>
</template>
