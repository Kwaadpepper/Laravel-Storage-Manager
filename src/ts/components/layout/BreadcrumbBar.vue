<script setup lang="ts">
defineOptions({
  name: 'BreadcrumbBar',
});
import DiskSelector from '@ts/components/shared/DiskSelector.vue';
import { useService } from '@ts/service-container';
import { useConfigStore, useFileManagerStore } from '@ts/stores';
import { Path, rootPath } from '@ts/types';
import { computed } from 'vue';

interface BreadcrumbPathPart {
  name: string;
  path: Path;
}

const navigationService = useService('navigationService');
const fileManagerStore = useFileManagerStore();
const configStore = useConfigStore();

const breadcrumbs = computed<BreadcrumbPathPart[]>(() => {
  const parts = fileManagerStore.currentPath.split('/').filter(Boolean);
  const breadcrumbParts: BreadcrumbPathPart[] = [];

  breadcrumbParts.push({ name: 'root', path: rootPath() });

  for (const [i, part] of parts.entries()) {
    const path = ('/' + parts.slice(0, i + 1).join('/')) as Path;
    breadcrumbParts.push({ name: decodeURIComponent(part), path });
  }

  return breadcrumbParts;
});

function onBreadcrumbClick(part: BreadcrumbPathPart): void {
  navigationService.navigateTo(part.path);
}
</script>

<template>
  <div class="breadcrumbs text-sm">
    <ul>
      <li
        v-if="configStore.disks.length > 1"
        key="disk-selector"
      >
        <DiskSelector />
      </li>
      <li
        v-for="(part, partIndex) in breadcrumbs"
        :key="`breadcrumb-part-${part.path}-${partIndex}`"
      >
        <button
          class="link link-hover"
          @click="onBreadcrumbClick(part)"
        >
          {{ part.name }}
        </button>
      </li>
    </ul>
  </div>
</template>
