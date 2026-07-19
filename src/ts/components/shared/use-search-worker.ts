import { useContainer } from '@ts/container';
import { useFileManagerStore, useSearchStore } from '@ts/stores';
import { Path, rootPath } from '@ts/types';
import { useEffect, useRef } from 'react';

export function useSearchWorker(): void {
  const { fileManagerService } = useContainer().cradle;
  const currentPath = useFileManagerStore(state => state.currentPath)
  const searchParams = useFileManagerStore(state => state.currentSearchParams)

  const searchIdRef = useRef<number>(0)

  useEffect(() => {
    if (currentPath !== '/:search') {
      searchIdRef.current++
      useSearchStore.getState().resetSearch()
      return
    }

    const query = searchParams?.get('q') || undefined
    const extension = searchParams?.get('ext') || undefined
    const minSize = searchParams?.has('minSize') ? Number.parseInt(searchParams.get('minSize')!, 10) : undefined
    const maxSize = searchParams?.has('maxSize') ? Number.parseInt(searchParams.get('maxSize')!, 10) : undefined

    useSearchStore.getState().resetSearch()
    useSearchStore.getState().setSearchParams({ query, extension, minSize, maxSize })
    useSearchStore.getState().setIsSearching(true)

    const searchId = ++searchIdRef.current
    const queue: Path[] = [rootPath()]
    let scannedPathsCount = 0

    const processQueue = async () => {
      if (searchIdRef.current !== searchId) return // Cancelled

      if (queue.length === 0) {
        useSearchStore.getState().setHasFinished(true)
        useSearchStore.getState().setIsSearching(false)
        return
      }

      const batchSize = 5
      const currentBatch = queue.splice(0, batchSize)

      try {
        const result = await fileManagerService.search(
          currentBatch,
          query,
          extension,
          minSize,
          maxSize
        )

        if (searchIdRef.current !== searchId) return // Cancelled

        queue.push(...result.directoriesToScan)

        useSearchStore.getState().addResults(result.matchedFiles, result.matchedDirectories)

        scannedPathsCount += currentBatch.length
        useSearchStore.getState().updateProgress(scannedPathsCount, queue.length)

        setTimeout(processQueue, 10)
      } catch (e) {
        console.error('Search worker error:', e)
        useSearchStore.getState().setIsSearching(false)
      }
    }

    processQueue()

    return () => {
      // eslint-disable-next-line react-hooks/exhaustive-deps
      searchIdRef.current++
    }
  }, [currentPath, searchParams, fileManagerService])
}
