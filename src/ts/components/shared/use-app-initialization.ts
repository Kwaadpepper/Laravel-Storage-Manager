import { useContainer } from '@ts/container';
import { rootPath } from '@ts/types';
import { useEffect } from 'react';

export function useAppInitialization(): void {
  const { fileManagerService, navigationService } = useContainer().cradle;

  useEffect(() => {
    fileManagerService.initialize().then(() => {
      navigationService.navigateTo(rootPath());
    }).catch(() => {
      console.error('Error initializing file manager');
    });
  }, [fileManagerService, navigationService]);
}
