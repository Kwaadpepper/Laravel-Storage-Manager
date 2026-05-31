# TODO for v1.0

## File operations

- [ ] Copy / paste files and directories
- [ ] Move files and directories
- [ ] Multi-select (mouse + Shift/Ctrl keys)
- [ ] Mass actions (delete, move, copy on selection)
- [ ] Bulk rename (with pattern, e.g. `image_{n}`)
- [ ] Zip / unzip archives
- [ ] Trash / soft delete (restore accidentally deleted files)
- [ ] Undo / redo last operation (move, rename, delete)

## Upload & Download

- [ ] File upload: multiple files, chunked with progress bar
- [ ] Drag & drop upload from OS
- [ ] Upload from URL (paste a URL, server downloads the file)
- [ ] Duplicate file handling policy (overwrite / auto-rename / error with confirmation)
- [ ] Filename sanitization on upload (strip special chars, configurable)
- [ ] Temporary signed download link (with configurable expiration)
- [ ] Copy file path / public URL to clipboard

## Navigation & UX

- [ ] Reload button
- [ ] Full screen button
- [ ] Favorites / "Places" (pin frequently used folders in the sidebar)
- [ ] File searching (by name, extension, size)
- [ ] Column ordering (name, size, type)
- [ ] Keyboard shortcuts (Del, F2, Ctrl+C/V...)
- [ ] Drag & drop to move files and directories
- [ ] Directory content lazy loading (for large directories)
- [ ] Recent files (quick access to last viewed/modified files)

## Previews & viewers

- [ ] Image thumbnails (lazy loaded and cached)
- [ ] Image cropper
- [ ] Audio viewer
- [ ] Video viewer
- [ ] PDF viewer
- [ ] File metadata view (EXIF, size, dates...)

## Image processing (server-side)

- [ ] Auto-rotate JPEG on upload (based on EXIF Orientation)
- [ ] Auto-resize images on upload (configurable max dimensions)

## Storage & statistics

- [ ] Storage usage stats (used / available space per disk)
- [ ] File visibility toggle (public / private, S3 ACL)

## Access control

- [ ] ACL (read-only mode, per-disk permissions)
- [ ] Per-disk configuration (allowed extensions, max upload size...)
- [ ] Multi-user mode: private folder per user + shared folder (configurable)
- [ ] Activity log (who uploaded, deleted, renamed and when)

## Events & integrations

- [ ] Events (upload, delete, rename, move...)
- [ ] WYSIWYG integration (CKEditor 4 - 5, Tiptap, Tinymce 3 - 4 - 5, Standalone - provide plugins)
- [ ] Multi-panel view (side by side)

## Help & documentation

- [ ] In-app help panel (keyboard shortcuts, feature overview)
- [ ] Contextual tooltips on toolbar buttons

## Technical

- [ ] i18n
- [ ] Refactor
- [ ] Tests

## Community & documentation

- [ ] Demo repository (minimal Laravel app with the package pre-configured)
- [ ] GitHub Pages site (features overview, screenshots, quick-start guide)
