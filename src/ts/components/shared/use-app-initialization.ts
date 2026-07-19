import { useContainer } from '@ts/container';
import { rootPath } from '@ts/types';
import { useEffect } from 'react';

export function useAppInitialization(): void {
  const { fileManagerService, navigationService, locationService } = useContainer().cradle;

  useEffect(() => {
    fileManagerService.initialize().then(async () => {
      const { path: initialPath, searchParams } = locationService.getDiskAndPath()
      try {
        if (initialPath === '/:search') {
          navigationService.navigateTo(initialPath, undefined, searchParams)
        } else {
          const data = await fileManagerService.listFiles(initialPath)
          navigationService.navigateTo(initialPath, data, searchParams)
        }
      } catch {
        navigationService.navigateTo(rootPath())
      }
    }).catch(() => {
      throw new Error('Error initializing file manager');
    });
  }, [fileManagerService, navigationService, locationService]);
}
