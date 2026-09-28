# Laravel-Storage-Manager

[![CI](https://github.com/Kwaadpepper/Laravel-Storage-Manager/actions/workflows/ci.yml/badge.svg)](https://github.com/Kwaadpepper/Laravel-Storage-Manager/actions/workflows/ci.yml)
![Coverage](https://img.shields.io/endpoint?url=https://gist.githubusercontent.com/Kwaadpepper/0890b84b61269b7b02c256a956a0a157/raw/coverage-badge.json)

Laravel File Manager with multiple storages handling

## Features

- **Multi-Storage Management**: Seamlessly browse and manage files across Local, S3, FTP, SFTP, and any custom Flysystem disk.
- **Chunked Resumable Uploads**: Reliable chunked file upload pipeline with background assembly progress tracking and duplicate handling policies (`auto_rename`, `overwrite`, `error`).
- **Media Streaming & Viewers**:
  - **Audio & Video Streaming**: HTTP 206 Partial Content range request support for smooth playback and seeking of large audio and video files.
  - **Image Viewer**: Built-in full-size image viewer with quick action to crop images.
  - **Code & Text Editor**: Code and text file viewer/editor with syntax highlighting.
  - **PDF Viewer**: Embedded PDF document preview.
- **Image Thumbnails**: Lazy-loaded, cached image thumbnails generated on-the-fly with WebP format and HTTP 304 (ETag) caching.
- **Image Cropper**: Integrated image cropper powered by Cropper.js 2:
  - Aspect ratio presets: Free, 1:1, 4:3, 16:9, 3:2, 2:3.
  - Image transformations: 90° rotation, horizontal & vertical flip, zoom in/out, and reset.
  - Save options: Save as a new copy with customizable filename or overwrite original.
- **Storage Usage Stats**: Real-time disk capacity and usage metrics (partition stats, configured quota per disk, or cloud mode) with visual progress indicators and automatic cache invalidation on file changes.
- **Archive Support**: Create ZIP archives and extract existing archives in place.
- **Batch Operations**: Bulk rename, move, copy, and delete files or folders.
- **Search & Filtering**: Search files and directories with extension and name filters.


## Requirements

- PHP 8.4+
- Composer
- Bun 1.3+
- Laravel host app (for manual UI testing with routes/views)

## Local Development (Package)

Install PHP and JS dependencies:

```bash
composer install
bun install
```

Run frontend in watch/HMR mode:

```bash
bun run dev
```

Build production assets:

```bash
bun run build
```

## Development With A Laravel Host App

Recommended workflow to get HMR while rendering the package inside your host app:

1. Point your host app to this local package (path repository in host `composer.json`).
2. In the host `.env`, enable the package frontend dev server:

```dotenv
STORAGE_MANAGER_USE_DEV_SERVER=true
STORAGE_MANAGER_DEV_SERVER_URL=http://localhost:5173
```

1. Start Vite in this package:

```bash
bun run dev
```

1. Start your Laravel host app (`php artisan serve` or your usual stack).
1. Open the storage-manager page in the host app.

### Notes About Symlink (`ln -s`)

Symlinking `public/vendor/storage-manager` to built files is useful for static assets, but it does not provide HMR by itself.

- Use symlink + `bun run build` for static integration.
- Use dev server env vars above for true HMR.

## Publish Assets (Static/Production Mode)

When you want static assets served by Laravel `public/`:

```bash
bun run build
php artisan vendor:publish --tag=storage-manager:assets
```

Available publish tags:

- `storage-manager:config`
- `storage-manager:translations`
- `storage-manager:assets`

## S3 & MinIO Permissions (IAM Policy)

When using an Amazon S3 disk or S3-compatible storage (like MinIO, Ceph, Cloudflare R2), the following standard policy is supported:

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Effect": "Allow",
            "Action": [
                "s3:ListBucket",
                "s3:GetBucketLocation"
            ],
            "Resource": [
                "arn:aws:s3:::your-bucket-name"
            ]
        },
        {
            "Effect": "Allow",
            "Action": [
                "s3:DeleteObject",
                "s3:GetObject",
                "s3:PutObject"
            ],
            "Resource": [
                "arn:aws:s3:::your-bucket-name/*"
            ]
        }
    ]
}
```

> **Note regarding MinIO:**
> MinIO does not use ACLs (`s3:GetObjectAcl`, `s3:PutObjectAcl`) and will reject them as `unsupported action`. Laravel Storage Manager automatically falls back to streaming for file moves and trash operations, ensuring complete compatibility with pure IAM policies on MinIO.
>
> On AWS S3, if your bucket uses Object ACLs, adding `s3:GetObjectAcl` and `s3:PutObjectAcl` allows AWS to perform server-side `CopyObject` instead of streaming.

## Quality Commands

PHP:

```bash
composer test
composer test:coverage
composer test:type-coverage
composer analyse
composer format
```

JS/TS:

```bash
bun run lint
```

