# TODO for v1.0

## File operations

- [x] Copy / paste files and directories
- [x] Move files and directories
- [x] Multi-select (mouse + Shift/Ctrl keys)
- [x] Mass actions (delete, move, copy on selection)
- [x] Bulk rename (with pattern, e.g. `image_{n}`)
- [x] Zip / unzip archives
- [x] Trash / soft delete (restore accidentally deleted files)
- [ ] Undo / redo last operation (move, rename, delete)
- [x] Delete non-empty directory with confirmation dialog

## Upload & Download

- [x] File upload: multiple files, chunked with progress bar
- [x] Drag & drop upload from OS
- [ ] Upload from URL (paste a URL, server downloads the file)
- [x] Duplicate file handling policy (overwrite / auto-rename / error with confirmation)
- [x] Filename sanitization on upload (strip special chars, configurable)
- [ ] Temporary signed download link (with configurable expiration)
- [x] Copy file path / public URL to clipboard
- [x] Upload concurrency lock (atomic locks on status.json during parallel chunk uploads)

## Navigation & UX

- [x] Reload button
- [x] Full screen button
- [ ] Favorites / "Places" (pin frequently used folders in the sidebar)
- [x] File searching (by name, extension, size)
- [x] Search / filter reset button
- [x] Column ordering (name, size, type)
- [ ] Keyboard shortcuts (Del, F2, Ctrl+C/V...)
- [ ] Drag & drop to move files and directories
- [ ] Directory content lazy loading (for large directories)
- [ ] Recent files (quick access to last viewed/modified files)

## Previews & viewers

- [x] Image thumbnails (lazy loaded and cached)
- [x] Image cropper
- [x] Audio viewer
- [x] Video viewer
- [ ] Video streaming (HLS / MPEG-Dash, optional ffmpeg transcoding)
- [x] PDF viewer
- [x] File metadata view (EXIF, size, dates...)

## Image processing (server-side)

- [ ] Auto-rotate JPEG on upload (based on EXIF Orientation)
- [ ] Auto-resize images on upload (configurable max dimensions)

## Storage & statistics

- [x] Storage usage stats (used / available space per disk)
- [ ] File visibility toggle (public / private, S3 ACL)

## Access control

- [x] ACL (read-only mode, per-disk permissions)
- [ ] Per-disk configuration (root/prefix, allowed extensions, trash, max upload size, naming rules)
- [ ] Granularité ACL & Multi-tenancy: dossier privé par utilisateur + dossier partagé (scoping dynamique du chemin racine par utilisateur / tenant)
- [x] Activity log (who uploaded, deleted, renamed and when)

## Events & integrations

- [x] Events (upload, delete, rename, move...)
- [x] Before / interceptable events (`FileCreating`, `FileDeleting`, `FileMoving` avec possibilité d'annulation)
- [ ] WYSIWYG integration (CKEditor 4 - 5, Tiptap, Tinymce 3 - 4 - 5, Standalone - provide plugins)
- [x] File type scoping when opening via integration (`type=image|audio|video|archive`)
- [ ] Multi-panel view (side by side)

## Help & documentation

- [ ] In-app help panel (keyboard shortcuts, feature overview)
- [ ] Contextual tooltips on toolbar buttons

## Technical

- [x] i18n
- [x] Refactor
- [x] Tests
- [x] CSP nonce support on embedded assets

## Community & documentation

- [ ] Demo repository (minimal Laravel app with the package pre-configured)
- [ ] GitHub Pages site (features overview, screenshots, quick-start guide)
