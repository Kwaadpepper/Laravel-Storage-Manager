import { useFileManager } from '@ts/composables';
import { useFileManagerStore, useSearchStore } from '@ts/stores';
import { Path, rootPath } from '@ts/types';
import { onUnmounted, watch } from 'vue';

export function useSearchWorker(): void {
  const fileManager = useFileManager();
  const fileManagerStore = useFileManagerStore();
  const searchStore = useSearchStore();

  let searchId = 0;

  watch(
    [() => fileManagerStore.currentPath, () => fileManagerStore.currentSearchParams],
    ([currentPath, searchParams]) => {
      if (currentPath !== '/:search') {
        searchId++;
        searchStore.resetSearch();
        return;
      }

      const query = searchParams?.get('q') || undefined;
      const extension = searchParams?.get('ext') || undefined;
      const minSize = searchParams?.has('minSize')
        ? Number.parseInt(searchParams.get('minSize')!, 10)
        : undefined;
      const maxSize = searchParams?.has('maxSize')
        ? Number.parseInt(searchParams.get('maxSize')!, 10)
        : undefined;

      searchStore.resetSearch();
      searchStore.setSearchParams({ query, extension, minSize, maxSize });
      searchStore.setIsSearching(true);

      const currentSearchId = ++searchId;
      const queue: Path[] = [rootPath()];
      let scannedPathsCount = 0;

      const processQueue = async () => {
        if (searchId !== currentSearchId) return; // Cancelled

        if (queue.length === 0) {
          searchStore.setHasFinished(true);
          searchStore.setIsSearching(false);
          return;
        }

        const batchSize = 5;
        const currentBatch = queue.splice(0, batchSize);

        try {
          const result = await fileManager.search(
            currentBatch,
            query,
            extension,
            minSize,
            maxSize
          );

          if (searchId !== currentSearchId) return; // Cancelled

          queue.push(...result.directoriesToScan);
          searchStore.addResults(result.matchedFiles, result.matchedDirectories);

          scannedPathsCount += currentBatch.length;
          searchStore.updateProgress(scannedPathsCount, queue.length);

          setTimeout(processQueue, 10);
        } catch (e) {
          console.error('Search worker error:', e);
          searchStore.setIsSearching(false);
        }
      };

      processQueue();
    },
    { immediate: true }
  );

  onUnmounted(() => {
    searchId++;
  });
}
