import { describe, expect, it } from 'bun:test';
import { diskStatsSchema } from '../../src/ts/schemas/disk-stats.schema';

describe('diskStatsSchema', () => {
  it('parses valid partition disk statistics', () => {
    const rawData = {
      disk: 'local',
      driver: 'local',
      supported: true,
      total: 500000000000,
      free: 200000000000,
      used: 300000000000,
      percentage: 60,
      mode: 'partition',
    };

    const parsed = diskStatsSchema.parse(rawData);

    expect(parsed.disk).toBe('local');
    expect(parsed.driver).toBe('local');
    expect(parsed.supported).toBe(true);
    expect(parsed.total).toBe(500000000000);
    expect(parsed.free).toBe(200000000000);
    expect(parsed.used).toBe(300000000000);
    expect(parsed.percentage).toBe(60);
    expect(parsed.mode).toBe('partition');
  });

  it('parses quota disk statistics', () => {
    const rawData = {
      disk: 'public',
      driver: 'local',
      supported: true,
      total: 104857600,
      free: 52428800,
      used: 52428800,
      percentage: 50,
      mode: 'quota',
    };

    const parsed = diskStatsSchema.parse(rawData);

    expect(parsed.disk).toBe('public');
    expect(parsed.total).toBe(104857600);
    expect(parsed.percentage).toBe(50);
    expect(parsed.mode).toBe('quota');
  });

  it('parses unlimited / cloud disk statistics with null total and free', () => {
    const rawData = {
      disk: 's3',
      driver: 's3',
      supported: true,
      total: null,
      free: null,
      used: 1234567,
      percentage: null,
      mode: 'unlimited',
    };

    const parsed = diskStatsSchema.parse(rawData);

    expect(parsed.disk).toBe('s3');
    expect(parsed.driver).toBe('s3');
    expect(parsed.total).toBeNull();
    expect(parsed.free).toBeNull();
    expect(parsed.used).toBe(1234567);
    expect(parsed.percentage).toBeNull();
    expect(parsed.mode).toBe('unlimited');
  });

  it('parses disabled statistics payload', () => {
    const rawData = {
      disk: 'backup',
      driver: 'local',
      supported: false,
      total: null,
      free: null,
      used: null,
      percentage: null,
      mode: 'disabled',
    };

    const parsed = diskStatsSchema.parse(rawData);

    expect(parsed.supported).toBe(false);
    expect(parsed.mode).toBe('disabled');
  });
});
