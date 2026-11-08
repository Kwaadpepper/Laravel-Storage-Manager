<script setup lang="ts">
defineOptions({
  name: 'SearchBar',
});
import { useNavigation } from '@ts/composables';
import { useFileManagerStore, useSearchStore } from '@ts/stores';
import { Path } from '@ts/types';
import { t } from '@ts/i18n';
import { Filter, Search, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

function parseSizeToUnit(sizeInBytes?: number): { value: string; unit: number } {
  if (sizeInBytes === undefined || isNaN(sizeInBytes)) return { value: '', unit: 1 };
  if (sizeInBytes === 0) return { value: '0', unit: 1 };

  if (sizeInBytes % (1024 ** 3) === 0)
    return { value: (sizeInBytes / (1024 ** 3)).toString(), unit: 1024 ** 3 };
  if (sizeInBytes % (1024 ** 2) === 0)
    return { value: (sizeInBytes / (1024 ** 2)).toString(), unit: 1024 ** 2 };
  if (sizeInBytes % 1024 === 0)
    return { value: (sizeInBytes / 1024).toString(), unit: 1024 };

  return { value: sizeInBytes.toString(), unit: 1 };
}

const navigation = useNavigation();
const fileManagerStore = useFileManagerStore();
const searchStore = useSearchStore();

const isSearchMode = computed(() => fileManagerStore.currentPath === '/:search');

const localQuery = ref('');
const localExt = ref('');
const localMinSize = ref('');
const localMaxSize = ref('');
const localMinUnit = ref(1);
const localMaxUnit = ref(1);

const dropdownRef = ref<HTMLDetailsElement | null>(null);

watch(
  [
    isSearchMode,
    () => searchStore.query,
    () => searchStore.extension,
    () => searchStore.minSize,
    () => searchStore.maxSize,
  ],
  () => {
    if (isSearchMode.value) {
      localQuery.value = searchStore.query || '';
      localExt.value = searchStore.extension || '';

      const parsedMin = parseSizeToUnit(searchStore.minSize);
      localMinSize.value = parsedMin.value;
      localMinUnit.value = parsedMin.unit;

      const parsedMax = parseSizeToUnit(searchStore.maxSize);
      localMaxSize.value = parsedMax.value;
      localMaxUnit.value = parsedMax.unit;
    } else {
      localQuery.value = '';
      localExt.value = '';
      localMinSize.value = '';
      localMaxSize.value = '';
      localMinUnit.value = 1;
      localMaxUnit.value = 1;
    }
  },
  { immediate: true }
);

function onSearch(e?: Event): void {
  if (e) e.preventDefault();

  if (dropdownRef.value) {
    dropdownRef.value.removeAttribute('open');
  }

  if (
    !localQuery.value &&
    !localExt.value &&
    !localMinSize.value &&
    !localMaxSize.value
  ) {
    if (isSearchMode.value) {
      navigation.navigatePrevious();
    }
    return;
  }

  const searchParams = new URLSearchParams();
  if (localQuery.value) searchParams.set('q', localQuery.value);
  if (localExt.value) searchParams.set('ext', localExt.value);

  if (localMinSize.value) {
    const minBytes = Math.floor(parseFloat(localMinSize.value) * localMinUnit.value);
    if (!isNaN(minBytes)) searchParams.set('minSize', minBytes.toString());
  }

  if (localMaxSize.value) {
    const maxBytes = Math.floor(parseFloat(localMaxSize.value) * localMaxUnit.value);
    if (!isNaN(maxBytes)) searchParams.set('maxSize', maxBytes.toString());
  }

  navigation.navigateTo('/:search' as Path, undefined, searchParams);
}

function onResetClick(): void {
  if (isSearchMode.value) {
    navigation.navigatePrevious();
  }
}
</script>

<template>
  <div class="flex items-center gap-1 relative w-full sm:w-auto">
    <form
      class="join flex items-center w-full sm:w-auto"
      @submit="onSearch"
    >
      <input
        v-model="localQuery"
        type="text"
        :placeholder="t('search_placeholder')"
        class="input input-bordered input-sm join-item w-28 sm:w-36 md:w-48 lg:w-56 xl:w-64 flex-1 sm:flex-none"
      >
      <details
        ref="dropdownRef"
        class="dropdown dropdown-bottom dropdown-end"
      >
        <summary
          class="btn btn-sm join-item border-l-0"
          :title="t('advanced_filters')"
        >
          <Filter
            :size="16"
            :class="localExt || localMinSize || localMaxSize ? 'text-primary' : ''"
          />
        </summary>
        <ul class="dropdown-content z-[100] menu p-4 shadow bg-base-100 rounded-box w-80 sm:w-[22rem] max-w-[calc(100vw-2rem)] mt-1">
          <h3 class="font-bold mb-3">
            {{ t('advanced_filters') }}
          </h3>

          <div class="form-control mb-2">
            <label class="label py-1"><span class="label-text">{{ t('extension') }}</span></label>
            <input
              v-model="localExt"
              type="text"
              placeholder="ex: pdf, jpg"
              class="input input-bordered input-sm w-full"
              @input="(e) => (localExt = (e.target as HTMLInputElement).value.replace('.', ''))"
            >
          </div>

          <div class="form-control mb-2">
            <label class="label py-1"><span class="label-text">{{ t('min_size') }}</span></label>
            <div class="join w-full">
              <input
                v-model="localMinSize"
                type="number"
                min="0"
                step="any"
                class="input input-bordered input-sm join-item w-full"
              >
              <select
                v-model.number="localMinUnit"
                class="select select-bordered select-sm join-item"
              >
                <option :value="1">
                  {{ t('bytes') }}
                </option>
                <option :value="1024">
                  {{ t('kb') }}
                </option>
                <option :value="1024 * 1024">
                  {{ t('mb') }}
                </option>
                <option :value="1024 * 1024 * 1024">
                  {{ t('gb') }}
                </option>
              </select>
            </div>
          </div>

          <div class="form-control mb-4">
            <label class="label py-1"><span class="label-text">{{ t('max_size') }}</span></label>
            <div class="join w-full">
              <input
                v-model="localMaxSize"
                type="number"
                min="0"
                step="any"
                class="input input-bordered input-sm join-item w-full"
              >
              <select
                v-model.number="localMaxUnit"
                class="select select-bordered select-sm join-item"
              >
                <option :value="1">
                  {{ t('bytes') }}
                </option>
                <option :value="1024">
                  {{ t('kb') }}
                </option>
                <option :value="1024 * 1024">
                  {{ t('mb') }}
                </option>
                <option :value="1024 * 1024 * 1024">
                  {{ t('gb') }}
                </option>
              </select>
            </div>
          </div>

          <button
            type="button"
            class="btn btn-primary btn-sm w-full"
            @click="() => onSearch()"
          >
            {{ t('apply') }}
          </button>
        </ul>
      </details>
      <button
        type="submit"
        class="btn btn-primary btn-sm join-item"
        :title="t('search')"
      >
        <Search :size="16" />
      </button>
    </form>

    <button
      v-if="isSearchMode"
      class="btn btn-ghost btn-sm btn-circle ml-1 text-error"
      :title="t('close_search')"
      @click="onResetClick"
    >
      <X :size="16" />
    </button>
  </div>
</template>
