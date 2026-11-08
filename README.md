# Laravel Storage Manager

[![CI](https://github.com/Kwaadpepper/Laravel-Storage-Manager/actions/workflows/ci.yml/badge.svg)](https://github.com/Kwaadpepper/Laravel-Storage-Manager/actions/workflows/ci.yml)
![Coverage](https://img.shields.io/endpoint?url=https://gist.githubusercontent.com/Kwaadpepper/0890b84b61269b7b02c256a956a0a157/raw/coverage-badge.json)
![PHP Version](https://img.shields.io/badge/php-8.4%2B-blue)
![Laravel Version](https://img.shields.io/badge/laravel-12%20%7C%2013-red)
![License](https://img.shields.io/badge/license-MIT-green)

A modern, embeddable, multi-storage file manager for Laravel applications with Vue 3, Tailwind CSS v4, DaisyUI 5, and full support for Local, Amazon S3, MinIO, FTP, and SFTP.

---

## ✨ Features

- **Multi-Storage Driver Abstraction**: Seamlessly manage files across Local, S3, MinIO, Cloudflare R2, FTP, SFTP, or custom Flysystem adapters via dedicated domain strategies.
- **Chunked Resumable Uploads**: Multi-part chunked upload pipeline with client-side MD5 hashing, atomic locks on upload sessions, progress tracking, and conflict policies (`auto_rename`, `overwrite`, `error`).
- **WYSIWYG & Form Integrations**: Ready-to-use plugins, helpers, and zero-dependency callbacks for:
  - **Standalone Button / Form Input** (Simple `fmSetLink` callback or `StorageManagerPicker.attach()`)
  - **CKEditor 5** (Official toolbar plugin)
  - **CKEditor 4** (`filebrowserImageBrowseUrl` connector)
  - **TinyMCE 5 & 6** (`file_picker_callback` integration)
  - **Tiptap** (Custom extension & `openStorageManagerTiptap()` helper)
- **Media Streaming & Preview Viewers**:
  - **Audio & Video Streaming**: HTTP 206 Partial Content range requests for smooth seeking and playback.
  - **Image Viewer & Cropper**: Integrated image viewer with Cropper.js 2 (aspect ratio presets, 90° rotation, flip, zoom, save copy or overwrite).
  - **Code & Text Editor**: In-browser code editing with Prism syntax highlighting and auto-save.
  - **PDF Preview**: Full-screen inline PDF document viewer.
- **Lazy-Loaded Thumbnails**: On-the-fly cached WebP thumbnails with HTTP 304 (ETag) browser caching.
- **Trash & Soft Delete**: Soft-delete files and directories to trash with restoration, expiration pruning, and permanent purge.
- **Storage Usage Statistics**: Real-time capacity metrics (partition stats, configured per-disk quotas, or unlimited cloud mode) with visual storage bars and cache invalidation.
- **Archive Support**: Create ZIP archives and safely extract existing archives in place with zip-slip protection.
- **Batch Operations**: Bulk rename (with patterns like `image_{n}`), copy, move, and recursive delete with confirmation.
- **Domain Interceptable Events**: Cancellable `FileCreating`, `FileDeleting`, `FileMoving`, etc. events enabling custom validation or business rules before operations execute.
- **Security & CSP**: Automatic CSP nonce propagation in embedded layouts, filename sanitization, and strict MIME type enforcement.

---

## 📋 Requirements

- **PHP**: `8.4+`
- **Laravel**: `12.0+` or `13.0+`
- **Composer**: `2.x+`
- **Bun** (for frontend development only): `1.3+`

---

## 🚀 Installation

### 1. Install via Composer

```bash
composer require kwaadpepper/laravel-storage-manager
```

### 2. Publish Assets & Configuration

Publish the pre-compiled frontend assets and default configuration file:

```bash
# Publish built assets (resources/js, resources/css) to public/vendor/storage-manager
php artisan vendor:publish --tag=storage-manager:assets

# Publish config file to config/storage-manager.php
php artisan vendor:publish --tag=storage-manager:config
```

Optional tags available:
```bash
# Publish translations to lang/vendor/storage-manager
php artisan vendor:publish --tag=storage-manager:translations

# Publish example blade templates for WYSIWYG editors
php artisan vendor:publish --tag=storage-manager:examples
```

### 3. Open Storage Manager

Navigate to `/storage-manager` in your browser. (The route prefix can be customized in `config/storage-manager.php`).

---

## 🎨 WYSIWYG & Standalone Integrations

Laravel Storage Manager supports two approaches for integration:
1. **Zero-dependency callback (`fmSetLink`)**: Simple, fast, no JavaScript dependencies needed.
2. **Dedicated integration bundles**: Included in `public/vendor/storage-manager/js/` for rich modals and editors.

Ready-to-use example templates are located in `examples/wysiwyg/` (publishable via `php artisan vendor:publish --tag=storage-manager:examples`).

---

### 1. Standalone Button / Form Input

#### Method A: Zero-Dependency Callback (`fmSetLink`)
No extra JS library needed. The popup calls `window.opener.fmSetLink(url, file)` automatically upon selection:

```html
<div class="input-group">
    <input type="text" id="image_url" class="form-control" name="thumbnail">
    <button type="button" class="btn btn-primary" onclick="openPicker()">Choose Image</button>
</div>

<script>
    function openPicker() {
        window.open('/storage-manager?picker=1&type=image', 'StorageManager', 'width=1200,height=700');
    }

    // Automatically invoked by Storage Manager
    function fmSetLink(url, file) {
        document.getElementById('image_url').value = url;
    }
</script>
```

#### Method B: Standalone Picker Helper (`StorageManagerPicker.attach`)
Using the published helper bundle (`storage-manager-picker.js`), you can open as a popup or an embedded iframe modal:

```html
<input type="text" id="avatar_input">
<button id="btn_avatar" type="button">Select Avatar</button>
<img id="avatar_preview" src="" alt="Preview">

<script src="{{ asset('vendor/storage-manager/js/storage-manager-picker.js') }}"></script>
<script>
    StorageManagerPicker.attach('#btn_avatar', '#avatar_input', {
        url: '/storage-manager',
        type: 'image',
        mode: 'modal', // 'modal' (iframe) or 'popup'
        preview: '#avatar_preview'
    });
</script>
```

---

### 2. CKEditor 5 Integration

Include the published integration bundle in your blade view:

```html
<textarea id="editor" name="content"></textarea>

<script src="https://cdn.ckeditor.com/ckeditor5/41.2.0/classic/ckeditor.js"></script>
<script src="{{ asset('vendor/storage-manager/js/storage-manager-picker.js') }}"></script>
<script src="{{ asset('vendor/storage-manager/js/storage-manager-ckeditor5.js') }}"></script>

<script>
    ClassicEditor.create(document.querySelector('#editor'), {
        extraPlugins: [window.StorageManagerCKEditor5],
        toolbar: {
            items: [
                'heading', '|', 'bold', 'italic', 'link', '|',
                'storageManager', '|', // Storage Manager toolbar button
                'undo', 'redo'
            ]
        },
        storageManager: {
            url: '/storage-manager',
            type: 'image', // 'image', 'video', 'pdf', 'archive' or omit for all
            mode: 'modal'  // 'modal' or 'popup'
        }
    });
</script>
```

---

### 3. CKEditor 4 Integration

Point CKEditor's file browser directly to the Storage Manager route:

```javascript
CKEDITOR.replace('editor', {
    filebrowserImageBrowseUrl: '/storage-manager?type=image',
    filebrowserBrowseUrl: '/storage-manager'
});
```

Storage Manager captures `CKEditorFuncNum` and sends the selected URL straight to `CKEDITOR.tools.callFunction()`.

---

### 4. TinyMCE 5 & 6 Integration

Configure TinyMCE's `file_picker_callback`:

```javascript
tinymce.init({
    selector: 'textarea#editor',
    plugins: 'image link media code',
    toolbar: 'undo redo | bold italic | link image media | code',
    file_picker_callback: function (callback, value, meta) {
        const type = meta.filetype === 'image' ? 'image' : (meta.filetype === 'media' ? 'video' : '');
        const url = '/storage-manager?picker=1' + (type ? '&type=' + type : '');

        window.open(url, 'StorageManager', 'width=1200,height=700,resizable=yes');

        window.addEventListener('message', function onMessage(event) {
            if (event.data && event.data.file) {
                callback(event.data.file.url, {
                    alt: event.data.file.name,
                    text: event.data.file.name
                });
                window.removeEventListener('message', onMessage);
            }
        });
    }
});
```

---

### 5. Tiptap Integration

Use the helper `openStorageManagerTiptap()` from `storage-manager-tiptap.js`:

```html
<button type="button" id="btn-insert-image">Insert Image</button>
<div id="tiptap-editor"></div>

<script src="{{ asset('vendor/storage-manager/js/storage-manager-picker.js') }}"></script>
<script src="{{ asset('vendor/storage-manager/js/storage-manager-tiptap.js') }}"></script>

<script type="module">
    import { Editor } from 'https://esm.sh/@tiptap/core';
    import StarterKit from 'https://esm.sh/@tiptap/starter-kit';
    import Image from 'https://esm.sh/@tiptap/extension-image';

    const editor = new Editor({
        element: document.querySelector('#tiptap-editor'),
        extensions: [StarterKit, Image]
    });

    document.getElementById('btn-insert-image').addEventListener('click', () => {
        window.openStorageManagerTiptap(editor, {
            url: '/storage-manager',
            type: 'image',
            mode: 'modal'
        });
    });
</script>
```

---

## ⚙️ Configuration Overview

In `config/storage-manager.php`, you can customize:

```php
return [
    'auth' => [
        'enabled' => true,
        'guard' => 'web',
        'allow_by_default' => false,
    ],

    'route' => [
        'prefix' => 'storage-manager',
        'middleware' => ['web', 'auth'],
    ],

    'disks' => [
        // Available disks in the file manager
        'available' => ['public', 's3', 'ftp'],
        'default' => 'public',

        // Per-disk fine-grained configuration
        'configs' => [
            'public' => [
                'root' => '',
                'allowed_extensions' => ['jpg', 'png', 'pdf', 'zip'],
                'trash' => true,
                'quota' => 1073741824, // 1 GB
                'max_file_size' => 52428800, // 50 MB
                'filename_policy' => 'strict',
            ],
            's3' => [
                'root' => 'uploads',
                'trash' => false,
            ],
        ],
    ],

    'upload' => [
        'max_file_size' => 104857600, // 100 MB fallback
        'chunk_size' => 5242880,      // 5 MB chunks
        'duplicate_policy' => 'auto_rename', // 'auto_rename', 'overwrite', 'error'
        'concurrency_lock' => [
            'driver' => 'auto', // 'cache', 'flock', or 'auto'
        ],
    ],

    'trash' => [
        'enabled' => true,
        'folder' => '.trash',
        'retention_days' => 30,
    ],
];
```

> **Authorization hardening:** with `allow_by_default=false` (default), every Storage Manager ability must be explicitly authorized by your app (via `Gate::define(...)` / policies). Set `allow_by_default=true` only if you intentionally accept permissive fallback authorization.

---

## ☁️ Cloud Storage & S3 IAM Permissions

When using Amazon S3, MinIO, or Cloudflare R2, configure your IAM policy as follows:

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

> **MinIO Compatibility Note:**
> MinIO does not use S3 ACLs (`s3:GetObjectAcl`, `s3:PutObjectAcl`). Laravel Storage Manager automatically detects compatibility and falls back to stream-based operations, ensuring full compatibility without requiring ACL policies.

---

## 🛠️ Local Development & HMR Workflow

To develop the package with true Hot Module Replacement (HMR) inside your Laravel host application:

1. Configure your host `.env`:
   ```dotenv
   STORAGE_MANAGER_USE_DEV_SERVER=true
   STORAGE_MANAGER_DEV_SERVER_URL=http://localhost:5173
   ```
2. Start the Vite dev server in the package repository:
   ```bash
   bun run dev
   ```
3. Start your Laravel host app (`php artisan serve`).

---

## 🚦 Quality Gates

This package strictly enforces 100% type coverage, PHPStan Level 10, and PSR-12 coding standards:

```bash
# 1. PHP Code Style (0 errors, 0 warnings)
vendor/bin/phpcs

# 2. PHP Static Analysis (Level 10 strict)
composer analyse

# 3. Pest Test Suite (100% pass)
composer test

# 4. PHP Type Coverage (100.0% enforced)
composer test:type-coverage

# 5. PHP Code Formatting (Pint)
composer format

# 6. Frontend Linting & Build
bun run lint
bun test
bun run build
```

---

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
