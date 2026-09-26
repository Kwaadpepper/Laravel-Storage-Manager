import { useService } from '@ts/service-container';
import { rootPath } from '@ts/types';
import { onMounted } from 'vue';

export function useAppInitialization(): void {
  const fileManagerService = useService('fileManagerService');
  const navigationService = useService('navigationService');
  const locationService = useService('locationService');

  onMounted(() => {
    fileManagerService
      .initialize()
      .then(async () => {
        const { path: initialPath, searchParams } = locationService.getDiskAndPath();
        try {
          if (initialPath === '/:search') {
            navigationService.navigateTo(initialPath, undefined, searchParams);
          } else {
            const data = await fileManagerService.listFiles(initialPath);
            navigationService.navigateTo(initialPath, data, searchParams);
          }
        } catch {
          navigationService.navigateTo(rootPath());
        }
      })
      .catch(() => {
        throw new Error('Error initializing file manager');
      });
  });
}
