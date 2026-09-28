<script setup lang="ts">
defineOptions({
  name: 'GlobalLoader',
});
import { ActionStatus, useActionStore, useUploadStore } from '@ts/stores';
import { CheckCircle2, ListTodo, Loader2, XCircle } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface GroupedAction {
  id: string;
  type: string;
  destinationPath?: string;
  total: number;
  completed: number;
  errorCount: number;
  errors: string[];
  status: ActionStatus;
  title: string;
}

const actionStore = useActionStore();
const uploadStore = useUploadStore();

const pendingActionCount = computed(
  () => actionStore.actions.filter((a) => a.status === 'pending').length
);
const pendingUploadCount = computed(
  () =>
    uploadStore.uploads.filter((u) =>
      ['pending', 'uploading', 'assembling'].includes(u.status)
    ).length
);
const pendingCount = computed(
  () => pendingActionCount.value + pendingUploadCount.value
);
const isSpinning = computed(() => pendingCount.value > 0);

const debouncedSpinning = ref(false);
let debounceTimeout: ReturnType<typeof setTimeout> | null = null;

watch(
  isSpinning,
  (spinning) => {
    if (debounceTimeout) clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(
      () => {
        debouncedSpinning.value = spinning;
      },
      spinning ? 200 : 0
    );
  },
  { immediate: true }
);

function getActionLabel(type: string): string {
  switch (type) {
    case 'DELETE':
      return 'Deleting';
    case 'MOVE':
      return 'Moving';
    case 'RENAME':
      return 'Renaming';
    case 'CREATE':
      return 'Creating';
    case 'ZIP':
      return 'Archiving';
    case 'UNZIP':
      return 'Extracting';
    case 'RESTORE':
      return 'Restoring';
    case 'PURGE':
      return 'Permanently deleting';
    default:
      return 'Copying';
  }
}

interface UploadQueueEntry {
  kind: 'upload';
  id: string;
  isOngoing: boolean;
  upload: (typeof uploadStore.uploads)[number];
}

interface ActionQueueEntry {
  kind: 'action';
  id: string;
  isOngoing: boolean;
  group: GroupedAction;
}

type QueueEntry = UploadQueueEntry | ActionQueueEntry;

const groupedActions = computed(() => {
  const groups: GroupedAction[] = [];
  for (const action of actionStore.actions) {
    const lastGroup = groups[groups.length - 1];
    const typeStr = getActionLabel(action.type);

    if (
      lastGroup &&
      lastGroup.type === action.type &&
      lastGroup.destinationPath === action.destinationPath
    ) {
      lastGroup.total++;
      if (action.status !== 'pending') lastGroup.completed++;
      if (action.status === 'error') {
        lastGroup.errorCount++;
        if (action.error) lastGroup.errors.push(action.error);
      }

      if (lastGroup.errorCount > 0 && lastGroup.completed === lastGroup.total)
        lastGroup.status = 'error';
      else if (lastGroup.completed < lastGroup.total) lastGroup.status = 'pending';
      else lastGroup.status = 'success';

      lastGroup.title = `${typeStr} ${lastGroup.total} items`;
    } else {
      groups.push({
        id: action.id,
        type: action.type,
        destinationPath: action.destinationPath,
        total: 1,
        completed: action.status !== 'pending' ? 1 : 0,
        errorCount: action.status === 'error' ? 1 : 0,
        errors: action.status === 'error' && action.error ? [action.error] : [],
        status: action.status,
        title: `${typeStr} 1 item`,
      });
    }
  }
  return groups;
});

const queueEntries = computed<QueueEntry[]>(() => {
  const uploadEntries: UploadQueueEntry[] = uploadStore.uploads.map((u) => ({
    kind: 'upload' as const,
    id: `upload-${u.id}`,
    isOngoing: ['pending', 'uploading', 'assembling'].includes(u.status),
    upload: u,
  }));

  const actionEntries: ActionQueueEntry[] = groupedActions.value.map((g) => ({
    kind: 'action' as const,
    id: `action-${g.id}`,
    isOngoing: g.status === 'pending',
    group: g,
  }));

  const ongoingUploads = uploadEntries.filter((e) => e.isOngoing);
  const ongoingActions = actionEntries.filter((e) => e.isOngoing);
  const finishedUploads = uploadEntries.filter((e) => !e.isOngoing);
  const finishedActions = actionEntries.filter((e) => !e.isOngoing);

  // Ongoing tasks are always placed at the very top of the queue (newest ongoing first)
  const ongoing = [...ongoingUploads, ...ongoingActions].reverse();
  // Completed/errored tasks are placed below ongoing tasks (newest finished first)
  const finished = [...finishedUploads, ...finishedActions].reverse();

  return [...ongoing, ...finished];
});

const hasActionError = computed(() =>
  actionStore.actions.some((a) => a.status === 'error')
);
const hasUploadError = computed(() =>
  uploadStore.uploads.some((u) => u.status === 'error')
);
const hasError = computed(() => hasActionError.value || hasUploadError.value);

const isClearDisabled = computed(
  () => !queueEntries.value.some((e) => !e.isOngoing)
);

function clearAllCompleted(): void {
  actionStore.clearCompleted();
  uploadStore.clearCompleted();
}
</script>

<template>
  <div
    v-if="actionStore.actions.length === 0 && uploadStore.uploads.length === 0"
    class="dropdown dropdown-end"
  >
    <div
      tabindex="0"
      role="button"
      class="btn btn-ghost btn-sm"
      title="Tasks"
    >
      <ListTodo :size="16" />
    </div>
    <ul
      tabindex="0"
      class="dropdown-content z-50 menu p-4 shadow bg-base-100 rounded-box w-64 text-center text-sm text-base-content/60 border border-base-200"
    >
      No background tasks
    </ul>
  </div>

  <div
    v-else
    class="dropdown dropdown-end"
  >
    <div
      tabindex="0"
      role="button"
      class="btn btn-sm btn-ghost"
    >
      <Loader2
        v-if="debouncedSpinning"
        class="animate-spin text-primary"
        :size="16"
      />
      <XCircle
        v-else-if="hasError"
        class="text-error"
        :size="16"
      />
      <CheckCircle2
        v-else
        class="text-success"
        :size="16"
      />
      <span
        v-if="pendingCount > 0"
        class="badge badge-sm badge-primary"
      >
        {{ pendingCount }}
      </span>
    </div>
    <div
      tabindex="0"
      class="dropdown-content z-50 bg-base-100 shadow-xl rounded-lg p-4 w-80 border border-base-200 flex flex-col gap-3 max-h-96 overflow-y-auto mt-2"
    >
      <div class="flex items-center justify-between border-b border-base-200 pb-2">
        <h3 class="font-bold text-sm">
          Background Tasks
        </h3>
        <button
          class="btn btn-xs btn-ghost text-base-content/60"
          :disabled="isClearDisabled"
          @click="clearAllCompleted"
        >
          Clear Done
        </button>
      </div>

      <div class="flex flex-col gap-2">
        <template
          v-for="entry in queueEntries"
          :key="entry.id"
        >
          <!-- Upload Item -->
          <div
            v-if="entry.kind === 'upload'"
            class="flex flex-col gap-1 text-sm bg-base-200/50 p-2 rounded"
          >
            <div class="flex justify-between items-center">
              <div class="min-w-0 flex items-center gap-2 pr-2">
                <span
                  class="font-medium truncate"
                  :title="entry.upload.fileName"
                >
                  Upload: {{ entry.upload.fileName }}
                </span>
                <span
                  class="badge badge-outline badge-xs shrink-0"
                  :title="`Upload disk: ${entry.upload.disk}`"
                >
                  {{ entry.upload.disk }}
                </span>
              </div>
              <Loader2
                v-if="['pending', 'uploading', 'assembling'].includes(entry.upload.status)"
                class="animate-spin text-primary"
                :size="14"
              />
              <CheckCircle2
                v-else-if="entry.upload.status === 'success'"
                class="text-success"
                :size="14"
              />
              <XCircle
                v-else-if="entry.upload.status === 'error'"
                class="text-error"
                :size="14"
              />
            </div>
            <progress
              v-if="['pending', 'uploading', 'assembling'].includes(entry.upload.status)"
              :class="`progress w-full ${entry.upload.status === 'assembling' ? 'progress-secondary' : 'progress-primary'}`"
              :value="entry.upload.progress"
              max="100"
            />
            <span
              v-if="entry.upload.status === 'error' && entry.upload.error"
              class="text-xs text-error truncate"
            >
              {{ entry.upload.error }}
            </span>
          </div>

          <!-- Grouped Action Item -->
          <div
            v-else-if="entry.kind === 'action'"
            class="flex flex-col gap-1 text-sm bg-base-200/50 p-2 rounded"
          >
            <div class="flex justify-between items-center">
              <span class="font-medium truncate pr-2">{{ entry.group.title }}</span>
              <Loader2
                v-if="entry.group.status === 'pending'"
                class="animate-spin text-primary"
                :size="14"
              />
              <CheckCircle2
                v-else-if="entry.group.status === 'success'"
                class="text-success"
                :size="14"
              />
              <XCircle
                v-else-if="entry.group.status === 'error'"
                class="text-error"
                :size="14"
              />
            </div>
            <progress
              v-if="entry.group.status === 'pending'"
              class="progress progress-primary w-full"
              :value="entry.group.completed"
              :max="entry.group.total"
            />
            <span
              v-if="entry.group.status === 'error' && entry.group.errors.length > 0"
              class="text-xs text-error truncate"
            >
              {{ entry.group.errors[0] }}
            </span>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>
