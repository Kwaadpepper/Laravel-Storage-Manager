import { beforeEach, describe, expect, it } from 'bun:test';
import { createPinia, setActivePinia } from 'pinia';
import { t, useI18n } from '../../src/ts/i18n';
import { useConfigStore } from '../../src/ts/stores/config-store';

describe('i18n helper (t and useI18n)', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  describe('English default fallback', () => {
    it('returns default English translation when translations store is empty', () => {
      expect(t('reload')).toBe('Reload');
      expect(t('new_file')).toBe('New File');
      expect(t('delete')).toBe('Delete');
      expect(t('trash_modal_title')).toBe('Trash');
    });

    it('returns the key itself if no English translation exists', () => {
      expect(t('unknown_non_existent_key')).toBe('unknown_non_existent_key');
    });
  });

  describe('Override via configStore.translations', () => {
    it('overrides translations when configStore provides dictionary', () => {
      const configStore = useConfigStore();
      configStore.translations = {
        reload: 'Actualiser',
        new_file: 'Nouveau fichier',
        delete: 'Supprimer',
      };

      expect(t('reload')).toBe('Actualiser');
      expect(t('new_file')).toBe('Nouveau fichier');
      expect(t('delete')).toBe('Supprimer');
      expect(t('trash_modal_title')).toBe('Trash');
    });
  });

  describe('Parameter interpolation', () => {
    it('interpolates placeholders using {param} syntax', () => {
      const configStore = useConfigStore();
      configStore.translations = {
        welcome_user: 'Hello {name}, you have {count} messages.',
      };

      expect(t('welcome_user', { name: 'Alice', count: 3 })).toBe('Hello Alice, you have 3 messages.');
      expect(t('folders_scanned', { count: 12 })).toBe('12 folders scanned');
    });

    it('interpolates placeholders using :param syntax', () => {
      const configStore = useConfigStore();
      configStore.translations = {
        items_total: 'Total: :count items for :user',
      };

      expect(t('items_total', { count: 5, user: 'Bob' })).toBe('Total: 5 items for Bob');
    });

    it('supports numeric and string parameters seamlessly', () => {
      const configStore = useConfigStore();
      configStore.translations = {
        summary: 'Deleted {count} files from disk {disk}',
      };

      expect(t('summary', { count: 0, disk: 's3' })).toBe('Deleted 0 files from disk s3');
    });
  });

  describe('useI18n hook', () => {
    it('exposes the t function via useI18n', () => {
      const { t: translate } = useI18n();
      expect(typeof translate).toBe('function');
      expect(translate('reload')).toBe('Reload');
    });
  });
});
