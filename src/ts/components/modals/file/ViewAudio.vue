<script setup lang="ts">
defineOptions({
  name: 'ViewAudio',
});
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import {
  Download,
  ExternalLink,
  FileX,
  Music,
  Repeat,
  RotateCcw,
  RotateCw,
} from 'lucide-vue-next';
import { ensureMediaBlob } from '../../../utils/media-mime';

const props = defineProps<{
  blob: Blob;
  fileName: string;
}>();

const audioRef = ref<HTMLAudioElement | null>(null);
const objectUrl = ref<string | null>(null);
const isPlaying = ref(false);
const isLooping = ref(false);
const playbackRate = ref(1);
const currentTime = ref(0);
const duration = ref(0);
const hasError = ref(false);

const playbackRates = [0.5, 0.75, 1, 1.25, 1.5, 2];

const extension = computed(() => {
  const parts = props.fileName.split('.');
  return parts.length > 1 ? parts.pop()?.toLowerCase() ?? null : null;
});

function formatDuration(seconds: number): string {
  if (isNaN(seconds) || !isFinite(seconds) || seconds <= 0) return '00:00';
  const mins = Math.floor(seconds / 60);
  const secs = Math.floor(seconds % 60);
  return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
}

watch(
  () => props.blob,
  (newBlob) => {
    if (objectUrl.value) {
      URL.revokeObjectURL(objectUrl.value);
    }
    hasError.value = false;
    isPlaying.value = false;
    currentTime.value = 0;
    duration.value = 0;

    const typedBlob = ensureMediaBlob(newBlob, extension.value, 'audio');
    objectUrl.value = URL.createObjectURL(typedBlob);
  },
  { immediate: true }
);

function toggleLoop(): void {
  isLooping.value = !isLooping.value;
  if (audioRef.value) {
    audioRef.value.loop = isLooping.value;
  }
}

function setPlaybackRate(rate: number): void {
  playbackRate.value = rate;
  if (audioRef.value) {
    audioRef.value.playbackRate = rate;
  }
}

function seekBy(seconds: number): void {
  if (audioRef.value) {
    audioRef.value.currentTime = Math.max(0, audioRef.value.currentTime + seconds);
  }
}

function onLoadedMetadata(): void {
  if (audioRef.value) {
    duration.value = audioRef.value.duration;
    audioRef.value.playbackRate = playbackRate.value;
    audioRef.value.loop = isLooping.value;
  }
}

function onTimeUpdate(): void {
  if (audioRef.value) {
    currentTime.value = audioRef.value.currentTime;
  }
}

function onAudioError(): void {
  hasError.value = true;
}

onBeforeUnmount(() => {
  if (audioRef.value) {
    audioRef.value.pause();
  }
  if (objectUrl.value) {
    URL.revokeObjectURL(objectUrl.value);
  }
});
</script>

<template>
  <div
    v-if="objectUrl"
    class="flex flex-col h-full gap-3"
  >
    <!-- Top action bar -->
    <div class="flex items-center justify-end gap-2 shrink-0">
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

    <!-- Error State -->
    <div
      v-if="hasError"
      class="flex-1 flex flex-col items-center justify-center p-6 text-center gap-3 text-base-content/70"
    >
      <FileX
        :size="48"
        class="text-error"
      />
      <p class="font-semibold text-base">
        Audio playback is not supported
      </p>
      <p class="text-xs text-base-content/60 max-w-sm">
        Your browser does not support playing this audio format directly. You can download the file to play it locally.
      </p>
      <a
        :href="objectUrl"
        :download="fileName"
        class="btn btn-sm btn-primary gap-1 mt-2"
      >
        <Download :size="16" />
        <span>Download Audio</span>
      </a>
    </div>

    <!-- Main Player Card -->
    <div
      v-else
      class="flex-1 min-h-0 flex items-center justify-center p-4"
    >
      <div class="card bg-base-200 border border-base-content/10 shadow-lg p-6 max-w-md w-full flex flex-col items-center gap-5">
        <!-- Visual Art / Icon Container -->
        <div
          class="w-28 h-28 rounded-2xl bg-primary/10 text-primary flex items-center justify-center shadow-inner transition-transform duration-300"
          :class="{ 'scale-105': isPlaying }"
        >
          <Music
            :size="48"
            class="transition-opacity duration-300"
            :class="{ 'opacity-90 animate-pulse': isPlaying, 'opacity-60': !isPlaying }"
          />
        </div>

        <!-- Track & Format Info -->
        <div class="text-center w-full min-w-0">
          <h4
            class="font-semibold text-base truncate"
            :title="fileName"
          >
            {{ fileName }}
          </h4>
          <div class="flex items-center justify-center gap-2 mt-1">
            <span
              v-if="extension"
              class="badge badge-sm badge-outline uppercase tracking-wider font-mono text-[10px]"
            >
              {{ extension }}
            </span>
            <span class="text-xs text-base-content/60 font-mono">
              {{ formatDuration(currentTime) }} / {{ formatDuration(duration) }}
            </span>
          </div>
        </div>

        <!-- Native Audio Controls -->
        <audio
          ref="audioRef"
          controls
          :src="objectUrl"
          class="w-full"
          @play="isPlaying = true"
          @pause="isPlaying = false"
          @ended="isPlaying = false"
          @timeupdate="onTimeUpdate"
          @loadedmetadata="onLoadedMetadata"
          @error="onAudioError"
        />

        <!-- Secondary Controls Bar -->
        <div class="flex items-center justify-between w-full pt-1 border-t border-base-content/10 text-xs">
          <!-- Quick Skip -->
          <div class="flex items-center gap-1">
            <button
              class="btn btn-xs btn-ghost btn-circle"
              type="button"
              title="Rewind 10s"
              @click="seekBy(-10)"
            >
              <RotateCcw :size="14" />
            </button>
            <button
              class="btn btn-xs btn-ghost btn-circle"
              type="button"
              title="Forward 10s"
              @click="seekBy(10)"
            >
              <RotateCw :size="14" />
            </button>
          </div>

          <!-- Playback Speed -->
          <div class="dropdown dropdown-top dropdown-end">
            <button
              tabindex="0"
              class="btn btn-xs btn-ghost font-mono text-[11px]"
              type="button"
            >
              {{ playbackRate }}x
            </button>
            <ul
              tabindex="0"
              class="dropdown-content z-10 menu p-1 shadow-md bg-base-100 rounded-box w-20 text-xs mb-1"
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

          <!-- Loop Toggle -->
          <button
            class="btn btn-xs btn-ghost gap-1"
            :class="{ 'btn-active text-primary': isLooping }"
            type="button"
            title="Loop audio"
            @click="toggleLoop"
          >
            <Repeat :size="13" />
            <span class="text-[11px]">Loop</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
