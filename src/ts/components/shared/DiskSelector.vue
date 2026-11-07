<script setup lang="ts">
defineOptions({
  name: 'DiskSelector',
});
import { useNavigation } from '@ts/composables';
import { useConfigStore, useDiskStore } from '@ts/stores';
import { Disk } from '@ts/types';
import { HardDrive } from 'lucide-vue-next';

defineProps<{
  className?: string;
}>();

const navigation = useNavigation();
const configStore = useConfigStore();
const diskStore = useDiskStore();

async function onDiskChange(e: Event) {
  const target = e.target as HTMLSelectElement;
  const disk = target.value as Disk;
  await diskStore.selectDisk(disk);
  navigation.switchDisk();
}
</script>

<template>
  <label
    v-if="configStore.disks.length > 1"
    :class="`flex items-center gap-1 ${className || ''}`"
  >
    <HardDrive
      :size="14"
      class="shrink-0 text-base-content/60"
      aria-hidden="true"
    />
    <select
      class="select select-xs"
      :value="diskStore.currentDisk ?? ''"
      aria-label="Storage disk"
      @change="onDiskChange"
    >
      <option
        v-for="disk in configStore.disks"
        :key="disk"
        :value="disk"
      >
        {{ disk }}
      </option>
    </select>
  </label>
</template>
