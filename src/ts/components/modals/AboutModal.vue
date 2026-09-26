<script setup lang="ts">
defineOptions({
  name: 'AboutModal',
});
import { ModalState, useConfigStore, useUiStore } from '@ts/stores';
import { X } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted } from 'vue';

const uiStore = useUiStore();
const configStore = useConfigStore();

const isOpen = computed(() => uiStore.aboutModal === ModalState.Opened);

function closeModal(): void {
  uiStore.setAboutModal(ModalState.Closed);
}

onMounted(() => {
  const handleKeydown = (e: KeyboardEvent): void => {
    if (e.key === 'Escape') {
      closeModal();
    }
  };
  window.addEventListener('keydown', handleKeydown);
  onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
  });
});
</script>

<template>
  <dialog
    class="modal"
    :class="{ 'modal-open': isOpen }"
    @close="closeModal"
  >
    <div class="modal-box">
      <button
        type="button"
        class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2"
        @click="closeModal"
      >
        <X :size="16" />
      </button>
      <h3 class="font-bold text-lg">
        About
      </h3>
      <div class="flex items-center justify-center py-4">
        <img
          :src="configStore.packageLogo"
          :alt="`${configStore.packageName} logo`"
          class="w-16 h-16"
        >
      </div>
      <p class="py-4">
        <span class="font-bold">
          <span class="text-sm">{{ configStore.packageName }}</span>
          <span class="text-sm text-base-content/40"> ({{ configStore.composerPackageName }})</span>
        </span>
        <br>
        <span class="text-sm">
          <span class="italic">Version&nbsp;:&nbsp;</span>{{ configStore.packageVersion }}
        </span>
        <br>
      </p>
      <p class="py-2">
        {{ configStore.appDescription }}
      </p>
      <em class="ms-5 pb-4 block">
        <span class="italic">Contributors</span>
        <ul>
          <li
            v-for="(author, index) in configStore.appAuthors"
            :key="`author-${author.email}-${index}`"
          >
            <span class="font-bold">{{ author.name }}</span> -
            <a
              :href="`mailto:${author.email}`"
              class="link link-hover"
            >
              <span class="text-sm">{{ author.email }}</span>
            </a>
          </li>
        </ul>
      </em>
    </div>

    <form
      method="dialog"
      class="modal-backdrop"
    >
      <button
        type="button"
        @click="closeModal"
      >
        Close
      </button>
    </form>
  </dialog>
</template>
