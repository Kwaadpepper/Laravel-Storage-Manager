import { useContainer } from '@ts/container';
import { rootPath } from '@ts/types';
import { useEffect } from 'react';

export function useAppInitialization(): void {
  const { fileManagerService, navigationService, locationService } = useContainer().cradle;

  useEffect(() => {
    fileManagerService.initialize().then(async () => {
      const initialPath = locationService.getCurrentPath()
      try {
        const data = await fileManagerService.listFiles(initialPath)
        navigationService.navigateTo(initialPath, data)
      } catch {
        navigationService.navigateTo(rootPath())
      }
    }).catch(() => {
      throw new Error('Error initializing file manager');
    });
  }, [fileManagerService, navigationService, locationService]);
}
