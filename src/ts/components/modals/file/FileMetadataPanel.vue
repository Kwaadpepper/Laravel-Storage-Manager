<script setup lang="ts">
defineOptions({
  name: 'FileMetadataPanel',
});

import { computed, ref } from 'vue';
import { useService } from '@ts/service-container';
import { FileMetadata } from '@ts/types';
import FileIcon from '@ts/components/shared/FileIcon.vue';
import FileSize from '@ts/components/shared/FileSize.vue';
import {
  Camera,
  Check,
  Copy,
  ExternalLink,
  FileText,
  Globe,
  Info,
  Layers,
  MapPin,
  Search,
} from 'lucide-vue-next';

const props = defineProps<{
  metadata: FileMetadata;
}>();

const toastService = useService('toastService');

type TabType = 'general' | 'exif' | 'raw';
const activeTab = ref<TabType>('general');
const rawSearch = ref<string>('');
const copiedField = ref<string | null>(null);

const hasExif = computed(() => {
  if (!props.metadata.exif) return false;
  const e = props.metadata.exif;
  return Boolean(
    e.make ||
      e.model ||
      e.lens ||
      e.aperture ||
      e.exposureTime ||
      e.iso ||
      e.focalLength ||
      e.gps ||
      e.dateTaken
  );
});

const hasRawExif = computed(() => {
  return props.metadata.rawExif !== null && Object.keys(props.metadata.rawExif).length > 0;
});

const filteredRawExif = computed(() => {
  if (!props.metadata.rawExif) return [];
  const query = rawSearch.value.trim().toLowerCase();
  const entries = Object.entries(props.metadata.rawExif);
  if (!query) {
    return entries;
  }
  return entries.filter(
    ([k, v]) => k.toLowerCase().includes(query) || String(v).toLowerCase().includes(query)
  );
});

function copyToClipboard(text: string, fieldName: string): void {
  navigator.clipboard
    .writeText(text)
    .then(() => {
      copiedField.value = fieldName;
      toastService.pushToast({ message: `${fieldName} copied to clipboard.`, type: 'success' });
      setTimeout(() => {
        if (copiedField.value === fieldName) {
          copiedField.value = null;
        }
      }, 2000);
    })
    .catch(() => {
      toastService.pushToast({ message: 'Failed to copy to clipboard.', type: 'error' });
    });
}

function formatDate(dateStr: string): string {
  try {
    const d = new Date(dateStr);
    return isNaN(d.getTime()) ? dateStr : d.toLocaleString();
  } catch {
    return dateStr;
  }
}
</script>

<template>
  <div class="flex flex-col h-full overflow-hidden text-sm">
    <!-- Header Summary -->
    <div class="p-4 border-b border-base-content/10 flex items-start gap-3 shrink-0 bg-base-200/40">
      <FileIcon
        :name="metadata.basename"
        :extension="metadata.extension"
        :size="36"
        class="shrink-0 mt-0.5"
      />
      <div class="flex-1 min-w-0">
        <h4
          class="font-semibold text-base truncate select-all"
          :title="metadata.basename"
        >
          {{ metadata.basename }}
        </h4>
        <div class="flex items-center gap-2 mt-1 flex-wrap text-xs text-base-content/70">
          <span
            v-if="metadata.dimensions"
            class="badge badge-sm badge-outline gap-1 font-mono"
          >
            <Layers :size="12" />
            {{ metadata.dimensions.width }} × {{ metadata.dimensions.height }} ({{ metadata.dimensions.aspectRatio }})
          </span>
          <span
            v-if="metadata.size !== null"
            class="badge badge-sm badge-ghost"
          >
            <FileSize :bytes="metadata.size" />
          </span>
          <span
            v-if="metadata.mimeType"
            class="badge badge-sm badge-ghost font-mono truncate max-w-44"
            :title="metadata.mimeType"
          >
            {{ metadata.mimeType }}
          </span>
        </div>
      </div>
    </div>

    <!-- Navigation Tabs -->
    <div
      v-if="hasExif || hasRawExif"
      class="tabs tabs-bordered px-4 shrink-0 bg-base-100"
    >
      <button
        type="button"
        class="tab tab-sm gap-1"
        :class="{ 'tab-active font-semibold': activeTab === 'general' }"
        @click="activeTab = 'general'"
      >
        <Info :size="14" />
        <span>General</span>
      </button>

      <button
        v-if="hasExif"
        type="button"
        class="tab tab-sm gap-1"
        :class="{ 'tab-active font-semibold': activeTab === 'exif' }"
        @click="activeTab = 'exif'"
      >
        <Camera :size="14" />
        <span>EXIF / Camera</span>
      </button>

      <button
        v-if="hasRawExif"
        type="button"
        class="tab tab-sm gap-1"
        :class="{ 'tab-active font-semibold': activeTab === 'raw' }"
        @click="activeTab = 'raw'"
      >
        <FileText :size="14" />
        <span>Raw Tags</span>
        <span class="badge badge-xs badge-neutral ml-1">{{ Object.keys(metadata.rawExif || {}).length }}</span>
      </button>
    </div>

    <!-- Content Area -->
    <div class="flex-1 overflow-y-auto p-4 space-y-4">
      <!-- General Tab -->
      <template v-if="activeTab === 'general'">
        <div class="space-y-3">
          <!-- Path -->
          <div>
            <span class="text-xs text-base-content/50 block mb-0.5">Path</span>
            <div class="flex items-center gap-1.5 bg-base-200/60 p-2 rounded-lg text-xs font-mono break-all select-all">
              <span class="flex-1">{{ metadata.path }}</span>
              <button
                type="button"
                class="btn btn-ghost btn-xs btn-square shrink-0"
                title="Copy Path"
                @click="copyToClipboard(metadata.path, 'Path')"
              >
                <Check
                  v-if="copiedField === 'Path'"
                  :size="14"
                  class="text-success"
                />
                <Copy
                  v-else
                  :size="14"
                />
              </button>
            </div>
          </div>

          <!-- Size -->
          <div
            v-if="metadata.size !== null"
            class="flex items-center justify-between py-1.5 border-b border-base-content/5"
          >
            <span class="text-base-content/60">Size</span>
            <span class="font-mono text-right">
              <FileSize :bytes="metadata.size" />
              <span class="text-xs text-base-content/50 ml-1">({{ metadata.size.toLocaleString() }} bytes)</span>
            </span>
          </div>

          <!-- MIME Type -->
          <div
            v-if="metadata.mimeType"
            class="flex items-center justify-between py-1.5 border-b border-base-content/5"
          >
            <span class="text-base-content/60">MIME Type</span>
            <span class="font-mono text-xs">{{ metadata.mimeType }}</span>
          </div>

          <!-- Dimensions -->
          <div
            v-if="metadata.dimensions"
            class="flex items-center justify-between py-1.5 border-b border-base-content/5"
          >
            <span class="text-base-content/60">Dimensions</span>
            <span class="font-mono text-xs">
              {{ metadata.dimensions.width }} × {{ metadata.dimensions.height }} px
              <span class="badge badge-xs badge-neutral ml-1">{{ metadata.dimensions.aspectRatio }}</span>
            </span>
          </div>

          <!-- Visibility -->
          <div
            v-if="metadata.visibility"
            class="flex items-center justify-between py-1.5 border-b border-base-content/5"
          >
            <span class="text-base-content/60">Visibility</span>
            <span
              class="badge badge-sm font-semibold capitalize"
              :class="metadata.visibility === 'public' ? 'badge-success badge-outline' : 'badge-neutral badge-outline'"
            >
              {{ metadata.visibility }}
            </span>
          </div>

          <!-- Last Modified -->
          <div
            v-if="metadata.timestamp"
            class="flex items-center justify-between py-1.5 border-b border-base-content/5"
          >
            <span class="text-base-content/60">Last Modified</span>
            <span class="text-xs text-right font-mono">{{ formatDate(metadata.timestamp) }}</span>
          </div>

          <!-- Date Taken -->
          <div
            v-if="metadata.exif?.dateTaken"
            class="flex items-center justify-between py-1.5 border-b border-base-content/5"
          >
            <span class="text-base-content/60">Date Taken</span>
            <span class="text-xs text-right font-mono">{{ formatDate(metadata.exif.dateTaken) }}</span>
          </div>

          <!-- Public URL -->
          <div v-if="metadata.publicUrl">
            <span class="text-xs text-base-content/50 block mb-0.5">Public URL</span>
            <div class="flex items-center gap-1.5 bg-base-200/60 p-2 rounded-lg text-xs break-all">
              <a
                :href="metadata.publicUrl"
                target="_blank"
                rel="noopener noreferrer"
                class="flex-1 link link-primary truncate font-mono text-xs flex items-center gap-1"
              >
                <span>{{ metadata.publicUrl }}</span>
                <ExternalLink
                  :size="12"
                  class="shrink-0"
                />
              </a>
              <button
                type="button"
                class="btn btn-ghost btn-xs btn-square shrink-0"
                title="Copy Public URL"
                @click="copyToClipboard(metadata.publicUrl, 'Public URL')"
              >
                <Check
                  v-if="copiedField === 'Public URL'"
                  :size="14"
                  class="text-success"
                />
                <Copy
                  v-else
                  :size="14"
                />
              </button>
            </div>
          </div>

          <!-- Checksum MD5 -->
          <div v-if="metadata.checksum">
            <span class="text-xs text-base-content/50 block mb-0.5">MD5 Checksum</span>
            <div class="flex items-center gap-1.5 bg-base-200/60 p-2 rounded-lg text-xs font-mono break-all select-all">
              <span class="flex-1">{{ metadata.checksum }}</span>
              <button
                type="button"
                class="btn btn-ghost btn-xs btn-square shrink-0"
                title="Copy Checksum"
                @click="copyToClipboard(metadata.checksum, 'MD5')"
              >
                <Check
                  v-if="copiedField === 'MD5'"
                  :size="14"
                  class="text-success"
                />
                <Copy
                  v-else
                  :size="14"
                />
              </button>
            </div>
          </div>
        </div>
      </template>

      <!-- EXIF / Camera Tab -->
      <template v-else-if="activeTab === 'exif' && metadata.exif">
        <div class="space-y-4">
          <!-- Camera & Lens -->
          <div
            v-if="metadata.exif.make || metadata.exif.model || metadata.exif.lens"
            class="bg-base-200/50 p-3 rounded-lg space-y-1.5"
          >
            <div class="flex items-center gap-1.5 font-medium text-xs text-base-content/70">
              <Camera :size="14" />
              <span>Camera & Equipment</span>
            </div>
            <div
              v-if="metadata.exif.make || metadata.exif.model"
              class="font-semibold text-sm"
            >
              {{ [metadata.exif.make, metadata.exif.model].filter(Boolean).join(' ') }}
            </div>
            <div
              v-if="metadata.exif.lens"
              class="text-xs text-base-content/80 font-mono"
            >
              {{ metadata.exif.lens }}
            </div>
          </div>

          <!-- Exposure Grid -->
          <div>
            <span class="text-xs font-semibold text-base-content/70 block mb-2">Exposure Settings</span>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
              <div
                v-if="metadata.exif.aperture"
                class="bg-base-200/40 p-2.5 rounded-lg flex flex-col"
              >
                <span class="text-xs text-base-content/50">Aperture</span>
                <span class="font-mono font-medium text-sm">{{ metadata.exif.aperture }}</span>
              </div>

              <div
                v-if="metadata.exif.exposureTime"
                class="bg-base-200/40 p-2.5 rounded-lg flex flex-col"
              >
                <span class="text-xs text-base-content/50">Shutter Speed</span>
                <span class="font-mono font-medium text-sm">{{ metadata.exif.exposureTime }}</span>
              </div>

              <div
                v-if="metadata.exif.iso"
                class="bg-base-200/40 p-2.5 rounded-lg flex flex-col"
              >
                <span class="text-xs text-base-content/50">ISO</span>
                <span class="font-mono font-medium text-sm">{{ metadata.exif.iso }}</span>
              </div>

              <div
                v-if="metadata.exif.focalLength"
                class="bg-base-200/40 p-2.5 rounded-lg flex flex-col"
              >
                <span class="text-xs text-base-content/50">Focal Length</span>
                <span class="font-mono font-medium text-sm">
                  {{ metadata.exif.focalLength }}
                  <span
                    v-if="metadata.exif.focalLengthIn35mm"
                    class="text-xs text-base-content/50 font-normal"
                  >
                    ({{ metadata.exif.focalLengthIn35mm }} eq)
                  </span>
                </span>
              </div>

              <div
                v-if="metadata.exif.flash"
                class="bg-base-200/40 p-2.5 rounded-lg flex flex-col"
              >
                <span class="text-xs text-base-content/50">Flash</span>
                <span class="font-medium text-sm">{{ metadata.exif.flash }}</span>
              </div>

              <div
                v-if="metadata.exif.whiteBalance"
                class="bg-base-200/40 p-2.5 rounded-lg flex flex-col"
              >
                <span class="text-xs text-base-content/50">White Balance</span>
                <span class="font-medium text-sm">{{ metadata.exif.whiteBalance }}</span>
              </div>

              <div
                v-if="metadata.exif.meteringMode"
                class="bg-base-200/40 p-2.5 rounded-lg flex flex-col"
              >
                <span class="text-xs text-base-content/50">Metering</span>
                <span class="font-medium text-sm">{{ metadata.exif.meteringMode }}</span>
              </div>
            </div>
          </div>

          <!-- GPS Location -->
          <div
            v-if="metadata.exif.gps"
            class="bg-base-200/50 p-3 rounded-lg space-y-2"
          >
            <div class="flex items-center gap-1.5 font-medium text-xs text-base-content/70">
              <MapPin
                :size="14"
                class="text-error"
              />
              <span>Location (GPS)</span>
            </div>
            <div class="font-mono text-xs">
              {{ metadata.exif.gps.latitude.toFixed(6) }}°, {{ metadata.exif.gps.longitude.toFixed(6) }}°
              <span
                v-if="metadata.exif.gps.altitude !== null"
                class="text-base-content/60 ml-1"
              >
                ({{ metadata.exif.gps.altitude }} m alt)
              </span>
            </div>
            <div class="flex items-center gap-2 pt-1">
              <a
                :href="metadata.exif.gps.mapsUrl"
                target="_blank"
                rel="noopener noreferrer"
                class="btn btn-xs btn-outline gap-1"
              >
                <Globe :size="12" />
                <span>OpenStreetMap</span>
              </a>
              <a
                :href="metadata.exif.gps.googleMapsUrl"
                target="_blank"
                rel="noopener noreferrer"
                class="btn btn-xs btn-outline gap-1"
              >
                <ExternalLink :size="12" />
                <span>Google Maps</span>
              </a>
            </div>
          </div>

          <!-- Software & Author -->
          <div
            v-if="metadata.exif.software || metadata.exif.artist || metadata.exif.copyright"
            class="space-y-2 border-t border-base-content/10 pt-3"
          >
            <div
              v-if="metadata.exif.software"
              class="flex justify-between py-1 text-xs"
            >
              <span class="text-base-content/50">Software</span>
              <span class="font-mono text-right">{{ metadata.exif.software }}</span>
            </div>
            <div
              v-if="metadata.exif.artist"
              class="flex justify-between py-1 text-xs"
            >
              <span class="text-base-content/50">Artist</span>
              <span class="text-right">{{ metadata.exif.artist }}</span>
            </div>
            <div
              v-if="metadata.exif.copyright"
              class="flex justify-between py-1 text-xs"
            >
              <span class="text-base-content/50">Copyright</span>
              <span class="text-right">{{ metadata.exif.copyright }}</span>
            </div>
          </div>
        </div>
      </template>

      <!-- Raw Tags Tab -->
      <template v-else-if="activeTab === 'raw' && metadata.rawExif">
        <div class="space-y-3">
          <div class="relative">
            <input
              v-model="rawSearch"
              type="text"
              placeholder="Search EXIF tag..."
              class="input input-sm input-bordered w-full pr-8 text-xs font-mono"
            >
            <Search
              :size="14"
              class="absolute right-2.5 top-2.5 text-base-content/40 pointer-events-none"
            />
          </div>

          <div class="overflow-x-auto border border-base-content/10 rounded-lg">
            <table class="table table-xs table-pin-rows">
              <thead>
                <tr class="bg-base-200">
                  <th class="w-1/2">
                    Tag
                  </th>
                  <th class="w-1/2">
                    Value
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="[tag, val] in filteredRawExif"
                  :key="tag"
                  class="hover"
                >
                  <td class="font-mono text-xs text-base-content/80 font-semibold select-all break-all">
                    {{ tag }}
                  </td>
                  <td class="font-mono text-xs select-all break-all">
                    {{ val }}
                  </td>
                </tr>
                <tr v-if="filteredRawExif.length === 0">
                  <td
                    colspan="2"
                    class="text-center py-4 text-xs text-base-content/50"
                  >
                    No matching tags found.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>
