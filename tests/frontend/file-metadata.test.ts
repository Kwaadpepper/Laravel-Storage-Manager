import { describe, expect, it } from 'bun:test';
import { propertiesResponseSchema } from '../../src/ts/schemas/file-manager/properties.schema';

describe('propertiesResponseSchema', () => {
  it('parses valid rich file metadata including EXIF and GPS', () => {
    const rawData = {
      path: '/photos/paris.jpg',
      basename: 'paris.jpg',
      dirname: '/photos',
      timestamp: '2026-09-27T14:30:00+00:00',
      visibility: 'public',
      isFile: true,
      isDirectory: false,
      extension: 'jpg',
      size: 2450000,
      mimeType: 'image/jpeg',
      publicUrl: 'http://localhost/storage/photos/paris.jpg',
      checksum: 'e99a18c428cb38d5f260853678922e03',
      dimensions: {
        width: 4000,
        height: 3000,
        aspectRatio: '4:3',
      },
      exif: {
        make: 'Sony',
        model: 'ILCE-7M3',
        lens: 'FE 24-70mm F2.8 GM',
        aperture: 'f/2.8',
        exposureTime: '1/500s',
        iso: 100,
        focalLength: '50 mm',
        focalLengthIn35mm: '50 mm',
        flash: 'Did not fire',
        whiteBalance: 'Auto',
        meteringMode: 'Pattern',
        dateTaken: '2026-06-15 14:30:00',
        gps: {
          latitude: 48.856667,
          longitude: 2.351944,
          altitude: 35.0,
          mapsUrl: 'https://www.openstreetmap.org/?mlat=48.856667&mlon=2.351944#map=16/48.856667/2.351944',
          googleMapsUrl: 'https://www.google.com/maps?q=48.856667,2.351944',
        },
        software: 'Lightroom',
        artist: 'Photographer',
        copyright: '2026',
      },
      rawExif: {
        Make: 'Sony',
        Model: 'ILCE-7M3',
        ISOSpeedRatings: 100,
      },
    };

    const parsed = propertiesResponseSchema.parse(rawData);

    expect(parsed.path).toBe('/photos/paris.jpg');
    expect(parsed.basename).toBe('paris.jpg');
    expect(parsed.dimensions?.width).toBe(4000);
    expect(parsed.dimensions?.height).toBe(3000);
    expect(parsed.dimensions?.aspectRatio).toBe('4:3');
    expect(parsed.exif?.make).toBe('Sony');
    expect(parsed.exif?.model).toBe('ILCE-7M3');
    expect(parsed.exif?.gps?.latitude).toBe(48.856667);
    expect(parsed.exif?.gps?.longitude).toBe(2.351944);
    expect(parsed.checksum).toBe('e99a18c428cb38d5f260853678922e03');
    expect(parsed.rawExif?.Make).toBe('Sony');
  });

  it('parses basic directory properties gracefully', () => {
    const rawData = {
      path: '/my-folder',
      basename: 'my-folder',
      dirname: '',
      timestamp: '2026-09-27T14:30:00+00:00',
      visibility: 'public',
      isDirectory: true,
    };

    const parsed = propertiesResponseSchema.parse(rawData);

    expect(parsed.path).toBe('/my-folder');
    expect(parsed.basename).toBe('my-folder');
    expect(parsed.isDirectory).toBe(true);
    expect(parsed.dimensions).toBeUndefined();
    expect(parsed.exif).toBeUndefined();
    expect(parsed.checksum).toBeUndefined();
  });
});
