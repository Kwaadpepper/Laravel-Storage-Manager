import { getDiskAndPath, useFileManager, useNavigation } from '@ts/composables';
import { rootPath } from '@ts/types';
import { onMounted } from 'vue';

export function useAppInitialization(): void {
  const fileManager = useFileManager();
  const navigation = useNavigation();

  onMounted(() => {
    fileManager
      .initialize()
      .then(async () => {
        const { path: initialPath, searchParams } = getDiskAndPath();
        try {
          if (initialPath === '/:search') {
            navigation.navigateTo(initialPath, undefined, searchParams);
          } else {
            const data = await fileManager.listFiles(initialPath);
            navigation.navigateTo(initialPath, data, searchParams);
          }
        } catch {
          navigation.navigateTo(rootPath());
        }
      })
      .catch(() => {
        throw new Error('Error initializing file manager');
      });
  });
}
