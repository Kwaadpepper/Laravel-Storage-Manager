import type { Path } from './path.type';

export type TrashedItemStatus = 'trashed' | 'restoring' | 'purging';

export interface TrashedItem {
  id: string;
  name: string;
  originalPath: Path;
  type: 'file' | 'directory';
  size: number;
  deletedAt: string;
  disk: string;
  status?: TrashedItemStatus;
}

export interface TrashListResponse {
  items: TrashedItem[];
  total: number;
}

export interface RestoredTrashResponse {
  restored: Array<{ id: string; path: string }>;
}

export interface PurgedTrashResponse {
  ids: string[];
  count: number;
}
