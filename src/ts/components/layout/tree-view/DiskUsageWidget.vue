<script setup lang="ts">
defineOptions({
  name: 'DiskUsageWidget',
});

import { computed } from 'vue';
import { Cloud, HardDrive, RefreshCw } from 'lucide-vue-next';
import FileSize from '@ts/components/shared/FileSize.vue';
import { useService } from '@ts/service-container';
import { useConfigStore, useDiskStore } from '@ts/stores';

const diskService = useService('diskService');
const configStore = useConfigStore();
const diskStore = useDiskStore();

const isVisible = computed(() =>
  configStore.supportsDiskStats && (diskStore.stats?.supported ?? true)
);

const stats = computed(() => diskStore.stats);
const isLoading = computed(() => diskStore.isLoadingStats);

const percentage = computed(() => {
  if (!stats.value || stats.value.percentage === null || stats.value.percentage === undefined) {
    return 0;
  }
  return Math.min(100, Math.max(0, stats.value.percentage));
});

const hasCapacity = computed(() =>
  stats.value?.total !== null && stats.value?.total !== undefined && stats.value.total > 0
);

const progressClass = computed(() => {
  if (percentage.value >= 90) return 'progress-error';
  if (percentage.value >= 75) return 'progress-warning';
  return 'progress-primary';
});

const modeLabel = computed(() => {
  if (!stats.value) return '';
  switch (stats.value.mode) {
    case 'quota':
      return 'Quota';
    case 'partition':
      return 'Volume';
    case 'unlimited':
      return 'Cloud';
    default:
      return '';
  }
});

function onRefresh(): void {
  void diskService.fetchDiskStats(undefined, true);
}
</script>

<template>
  <div
    v-if="isVisible"
    class="p-3 border-t border-base-200 bg-base-100 shrink-0 text-xs select-none"
  >
    <!-- Header with disk name and refresh button -->
    <div class="flex items-center justify-between gap-1 mb-1.5">
      <div class="flex items-center gap-1.5 min-w-0">
        <Cloud
          v-if="stats?.mode === 'unlimited'"
          :size="13"
          class="shrink-0 text-base-content/60"
        />
        <HardDrive
          v-else
          :size="13"
          class="shrink-0 text-base-content/60"
        />
        <span
          class="font-semibold text-xs truncate max-w-[110px]"
          :title="diskStore.currentDisk ?? 'Storage'"
        >
          {{ diskStore.currentDisk ?? 'Storage' }}
        </span>
        <span
          v-if="modeLabel"
          class="badge badge-xs badge-ghost text-[10px] px-1 py-0"
        >
          {{ modeLabel }}
        </span>
      </div>

      <button
        type="button"
        class="btn btn-ghost btn-xs btn-circle h-5 w-5 min-h-0"
        title="Refresh storage usage"
        :disabled="isLoading"
        @click="onRefresh"
      >
        <RefreshCw
          :size="11"
          :class="{ 'animate-spin': isLoading }"
        />
      </button>
    </div>

    <!-- Loading skeleton -->
    <div
      v-if="isLoading && !stats"
      class="flex flex-col gap-1.5"
    >
      <div class="skeleton h-1.5 w-full rounded" />
      <div class="flex justify-between">
        <div class="skeleton h-3 w-16" />
        <div class="skeleton h-3 w-12" />
      </div>
    </div>

    <!-- Error message fallback -->
    <div
      v-else-if="diskStore.statsError"
      class="text-[11px] text-error/80 flex items-center justify-between"
    >
      <span>Stats unavailable</span>
      <button
        type="button"
        class="link link-hover text-[10px]"
        @click="onRefresh"
      >
        Retry
      </button>
    </div>

    <!-- Stats display -->
    <div
      v-else-if="stats"
      class="flex flex-col gap-1"
    >
      <!-- Progress Bar (for partition or quota with known total) -->
      <progress
        v-if="hasCapacity"
        class="progress w-full h-1.5"
        :class="progressClass"
        :value="percentage"
        max="100"
      />

      <!-- Detailed text metrics -->
      <div class="flex items-center justify-between text-[11px] text-base-content/70">
        <span v-if="stats.used !== null && stats.used !== undefined">
          <FileSize :bytes="stats.used" />
          <span
            v-if="hasCapacity"
            class="text-base-content/50"
          >
            / <FileSize :bytes="stats.total!" />
          </span>
        </span>

        <span
          v-if="hasCapacity && stats.free !== null && stats.free !== undefined"
          class="text-base-content/60"
        >
          <FileSize :bytes="stats.free" /> free
        </span>
        <span
          v-else-if="stats.mode === 'unlimited'"
          class="text-base-content/40 text-[10px]"
        >
          unlimited
        </span>
      </div>
    </div>
  </div>
</template>
