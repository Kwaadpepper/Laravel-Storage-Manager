import { describe, expect, it } from 'bun:test';

// Mock DOM elements for server/headless test environment
if (typeof globalThis.HTMLElement === 'undefined') {
  // @ts-expect-error mock for headless test
  globalThis.HTMLElement = class {};
  // @ts-expect-error mock for headless test
  globalThis.customElements = { define: () => {} };
}

const {
  ASPECT_RATIOS,
  getMimeTypeFromExtension,
} = await import('../../src/ts/components/modals/file/useCropImageViewModel');

describe('useCropImageViewModel utilities', () => {
  it('correctly maps file extensions to image MIME types', () => {
    expect(getMimeTypeFromExtension('jpg')).toBe('image/jpeg');
    expect(getMimeTypeFromExtension('JPEG')).toBe('image/jpeg');
    expect(getMimeTypeFromExtension('png')).toBe('image/png');
    expect(getMimeTypeFromExtension('webp')).toBe('image/webp');
    expect(getMimeTypeFromExtension('gif')).toBe('image/gif');
    expect(getMimeTypeFromExtension('bmp')).toBe('image/bmp');
    expect(getMimeTypeFromExtension('unknown')).toBe('image/jpeg');
  });

  it('provides standard aspect ratio presets', () => {
    expect(Number.isNaN(ASPECT_RATIOS.free.value)).toBe(true);
    expect(ASPECT_RATIOS['1:1'].value).toBe(1);
    expect(ASPECT_RATIOS['4:3'].value).toBeCloseTo(1.333, 2);
    expect(ASPECT_RATIOS['16:9'].value).toBeCloseTo(1.777, 2);
    expect(ASPECT_RATIOS['3:2'].value).toBe(1.5);
    expect(ASPECT_RATIOS['2:3'].value).toBeCloseTo(0.666, 2);
  });
});
