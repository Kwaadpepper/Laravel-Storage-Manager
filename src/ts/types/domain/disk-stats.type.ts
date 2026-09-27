export type DiskStatsMode = 'partition' | 'quota' | 'folder' | 'unlimited' | 'disabled';

export interface DiskStats {
  disk: string;
  driver: string;
  supported: boolean;
  total: number | null;
  free: number | null;
  used: number | null;
  percentage: number | null;
  mode: DiskStatsMode;
}
