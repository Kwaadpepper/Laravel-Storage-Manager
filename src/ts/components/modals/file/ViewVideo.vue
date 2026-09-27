<script setup lang="ts">
defineOptions({
  name: 'ViewVideo',
});
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import {
  Download,
  ExternalLink,
  FileX,
  Maximize,
} from 'lucide-vue-next';
import { ensureMediaBlob } from '../../../utils/media-mime';

const props = defineProps<{
  blob: Blob;
  fileName: string;
}>();

const videoRef = ref<HTMLVideoElement | null>(null);
const objectUrl = ref<string | null>(null);
const playbackRate = ref(1);
const duration = ref(0);
const videoWidth = ref(0);
const videoHeight = ref(0);
const hasError = ref(false);

const playbackRates = [0.5, 0.75, 1, 1.25, 1.5, 2];

const extension = computed(() => {
  const parts = props.fileName.split('.');
  return parts.length > 1 ? parts.pop()?.toLowerCase() ?? null : null;
});

function formatDuration(seconds: number): string {
  if (isNaN(seconds) || !isFinite(seconds) || seconds <= 0) return '00:00';
  const hours = Math.floor(seconds / 3600);
  const mins = Math.floor((seconds % 3600) / 60);
  const secs = Math.floor(seconds % 60);

  if (hours > 0) {
    return `${hours}:${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
  }
  return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
}

watch(
  () => props.blob,
  (newBlob) => {
    if (objectUrl.value) {
      URL.revokeObjectURL(objectUrl.value);
    }
    hasError.value = false;
    duration.value = 0;
    videoWidth.value = 0;
    videoHeight.value = 0;

    const typedBlob = ensureMediaBlob(newBlob, extension.value, 'video');
    objectUrl.value = URL.createObjectURL(typedBlob);
  },
  { immediate: true }
);

function setPlaybackRate(rate: number): void {
  playbackRate.value = rate;
  if (videoRef.value) {
    videoRef.value.playbackRate = rate;
  }
}

function requestFullscreen(): void {
  if (videoRef.value) {
    if (videoRef.value.requestFullscreen) {
      void videoRef.value.requestFullscreen();
    }
  }
}

function onLoadedMetadata(): void {
  if (videoRef.value) {
    duration.value = videoRef.value.duration;
    videoWidth.value = videoRef.value.videoWidth;
    videoHeight.value = videoRef.value.videoHeight;
    videoRef.value.playbackRate = playbackRate.value;
  }
}

function onVideoError(): void {
  hasError.value = true;
}

onBeforeUnmount(() => {
  if (videoRef.value) {
    videoRef.value.pause();
  }
  if (objectUrl.value) {
    URL.revokeObjectURL(objectUrl.value);
  }
});
</script>

<template>
  <div
    v-if="objectUrl"
    class="flex flex-col h-full gap-2"
  >
    <!-- Top action bar -->
    <div class="flex items-center justify-between gap-2 shrink-0">
      <!-- Resolution & Metadata badge -->
      <div class="flex items-center gap-2 text-xs text-base-content/70">
        <span
          v-if="extension"
          class="badge badge-sm badge-outline uppercase tracking-wider font-mono text-[10px]"
        >
          {{ extension }}
        </span>
        <span
          v-if="videoWidth > 0 && videoHeight > 0"
          class="font-mono text-xs"
        >
          {{ videoWidth }} &times; {{ videoHeight }}
        </span>
        <span
          v-if="duration > 0"
          class="font-mono text-xs opacity-70"
        >
          ({{ formatDuration(duration) }})
        </span>
      </div>

      <!-- Action buttons -->
      <div class="flex items-center gap-2">
        <!-- Playback speed -->
        <div class="dropdown dropdown-end">
          <button
            tabindex="0"
            class="btn btn-xs btn-ghost font-mono text-[11px]"
            type="button"
          >
            {{ playbackRate }}x
          </button>
          <ul
            tabindex="0"
            class="dropdown-content z-10 menu p-1 shadow-md bg-base-100 rounded-box w-20 text-xs mt-1"
          >
            <li
              v-for="rate in playbackRates"
              :key="rate"
            >
              <button
                type="button"
                :class="{ active: playbackRate === rate }"
                @click="setPlaybackRate(rate)"
              >
                {{ rate }}x
              </button>
            </li>
          </ul>
        </div>

        <button
          class="btn btn-xs btn-ghost gap-1"
          type="button"
          title="Fullscreen"
          @click="requestFullscreen"
        >
          <Maximize :size="14" />
          <span class="hidden sm:inline">Fullscreen</span>
        </button>

        <a
          :href="objectUrl"
          target="_blank"
          rel="noopener noreferrer"
          class="btn btn-xs btn-ghost gap-1"
          title="Open in new tab"
        >
          <ExternalLink :size="14" />
          <span>Open in new tab</span>
        </a>
        <a
          :href="objectUrl"
          :download="fileName"
          class="btn btn-xs btn-ghost gap-1"
          title="Download"
        >
          <Download :size="14" />
          <span>Download</span>
        </a>
      </div>
    </div>

    <!-- Error State -->
    <div
      v-if="hasError"
      class="flex-1 min-h-0 flex flex-col items-center justify-center p-6 text-center gap-3 text-base-content/70 bg-base-200 rounded-lg border border-base-content/10"
    >
      <FileX
        :size="48"
        class="text-error"
      />
      <p class="font-semibold text-base">
        Video playback is not supported
      </p>
      <p class="text-xs text-base-content/60 max-w-sm">
        Your browser does not support playing this video format or codec directly. You can download the file to watch it in a standalone media player.
      </p>
      <div class="flex items-center gap-2 mt-2">
        <a
          :href="objectUrl"
          target="_blank"
          rel="noopener noreferrer"
          class="btn btn-sm btn-outline gap-1"
        >
          <ExternalLink :size="16" />
          <span>Open in new tab</span>
        </a>
        <a
          :href="objectUrl"
          :download="fileName"
          class="btn btn-sm btn-primary gap-1"
        >
          <Download :size="16" />
          <span>Download Video</span>
        </a>
      </div>
    </div>

    <!-- Video Display Area -->
    <div
      v-else
      class="flex-1 min-h-0 w-full flex items-center justify-center bg-black/90 rounded-lg overflow-hidden relative shadow-inner border border-base-content/10"
    >
      <video
        ref="videoRef"
        controls
        playsinline
        preload="metadata"
        :src="objectUrl"
        class="max-w-full max-h-full object-contain"
        @loadedmetadata="onLoadedMetadata"
        @error="onVideoError"
      />
    </div>
  </div>
</template>
