<script setup lang="ts">
defineOptions({
  name: 'TextEditor',
});
import { useService } from '@ts/service-container';
import { Theme, useUiStore } from '@ts/stores';
import { SUPPORTED_LANGUAGES } from '@ts/utils/supported-languages';
import { createEditor, PrismEditor } from 'prism-code-editor';
import { defaultCommands } from 'prism-code-editor/commands';
import { copyButton } from 'prism-code-editor/copy-button';
import { indentGuides } from 'prism-code-editor/guides';
import { highlightBracketPairs } from 'prism-code-editor/highlight-brackets';
import 'prism-code-editor/languages';
import { matchBrackets } from 'prism-code-editor/match-brackets';
import { searchWidget } from 'prism-code-editor/search';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

import copyBtnCss from 'prism-code-editor/copy-button.css?inline';
import layoutCss from 'prism-code-editor/layout.css?inline';
import searchCss from 'prism-code-editor/search.css?inline';
import darkThemeCss from 'prism-code-editor/themes/vs-code-dark.css?inline';
import lightThemeCss from 'prism-code-editor/themes/vs-code-light.css?inline';

const props = withDefaults(
  defineProps<{
    modelValue?: string;
    value?: string;
    language?: keyof typeof SUPPORTED_LANGUAGES;
    readOnly?: boolean;
  }>(),
  {
    modelValue: '',
    value: '',
    language: 'XML doc (.net)' as keyof typeof SUPPORTED_LANGUAGES,
    readOnly: false,
  }
);

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void;
  (e: 'update:value', value: string): void;
  (e: 'update:language', language: keyof typeof SUPPORTED_LANGUAGES): void;
  (e: 'change', value: string): void;
  (e: 'languageChange', language: keyof typeof SUPPORTED_LANGUAGES): void;
}>();

const uiStore = useUiStore();
const themeService = useService('themeService');

const currentLanguage = ref<keyof typeof SUPPORTED_LANGUAGES>(props.language);
const editorTheme = ref<Theme>(themeService.getSystemTheme());
const editorContainer = ref<HTMLDivElement | null>(null);
let editor: PrismEditor | null = null;

const prismLang = computed(() => {
  const langs = SUPPORTED_LANGUAGES[currentLanguage.value];
  return langs ? langs[0] : 'text';
});

watch(
  () => uiStore.theme,
  (theme) => {
    if (theme !== 'auto') {
      editorTheme.value = theme;
    } else {
      editorTheme.value = themeService.getSystemTheme();
    }
  },
  { immediate: true }
);

let cleanupThemeListener: (() => void) | undefined;
onMounted(() => {
  cleanupThemeListener = themeService.listenToSystemThemeChanges((prefersDark) => {
    if (uiStore.theme === 'auto') {
      editorTheme.value = prefersDark ? 'dark' : 'light';
    }
  });

  if (editorContainer.value) {
    editor = createEditor(
      editorContainer.value,
      {
        language: prismLang.value,
        value: props.modelValue || props.value,
        readOnly: props.readOnly,
        wordWrap: true,
        lineNumbers: true,
        insertSpaces: true,
        onUpdate(newVal) {
          emit('update:modelValue', newVal);
          emit('update:value', newVal);
          emit('change', newVal);
        },
      },
      defaultCommands(),
      matchBrackets(),
      highlightBracketPairs(),
      indentGuides(),
      searchWidget(),
      copyButton()
    );
  }
});

watch(
  () => props.modelValue || props.value,
  (newVal) => {
    if (editor && editor.value !== newVal) {
      editor.setOptions({ value: newVal });
    }
  }
);

watch(
  () => props.readOnly,
  (newVal) => {
    editor?.setOptions({ readOnly: newVal });
  }
);

watch(prismLang, (newLang) => {
  editor?.setOptions({ language: newLang });
});

onUnmounted(() => {
  cleanupThemeListener?.();
  editor?.remove();
  editor = null;
});

function handleLanguageChange(e: Event) {
  const target = e.target as HTMLSelectElement;
  const lang = target.value as keyof typeof SUPPORTED_LANGUAGES;
  currentLanguage.value = lang;
  emit('languageChange', lang);
  emit('update:language', lang);
}
</script>

<template>
  <div class="flex flex-col h-full gap-1">
    <div class="flex items-center gap-2 shrink-0">
      <label
        for="text-editor-language"
        class="text-xs text-base-content/60 shrink-0"
      >
        Language
      </label>
      <select
        id="text-editor-language"
        class="select select-xs select-bordered"
        :value="currentLanguage"
        @change="handleLanguageChange"
      >
        <option
          v-for="lang in Object.keys(SUPPORTED_LANGUAGES)"
          :key="lang"
          :value="lang"
        >
          {{ lang }}
        </option>
      </select>
    </div>

    <div class="flex-1 min-h-0 relative rounded-lg border border-base-content/10 overflow-hidden">
      <component :is="'style'">
        {{ layoutCss }}
        .prism-code-editor { height: 100%; width: 100%; }
      </component>
      <component :is="'style'">
        {{ searchCss }}
      </component>
      <component :is="'style'">
        {{ copyBtnCss }}
      </component>
      <component
        :is="'style'"
        v-if="editorTheme === 'light'"
      >
        {{ lightThemeCss }}
      </component>
      <component
        :is="'style'"
        v-else
      >
        {{ darkThemeCss }}
      </component>
      <div
        ref="editorContainer"
        class="h-full w-full"
      />
    </div>
  </div>
</template>
