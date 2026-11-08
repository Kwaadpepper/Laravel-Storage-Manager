<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Storage Manager - Tiptap Integration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="{{ asset('vendor/storage-manager/js/storage-manager-picker.js') }}"></script>
    <script src="{{ asset('vendor/storage-manager/js/storage-manager-tiptap.js') }}"></script>
</head>
<body class="p-5 bg-light">
    <div class="container bg-white p-4 rounded shadow-sm" style="max-width: 900px;">
        <h2 class="mb-4">Storage Manager — Tiptap Integration</h2>

        <div class="mb-3">
            <div class="btn-toolbar mb-2 gap-2" role="toolbar">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-bold">Bold</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-italic">Italic</button>
                <button type="button" class="btn btn-sm btn-primary" id="btn-insert-image">Insert Image (Storage Manager)</button>
            </div>

            <div id="tiptap-editor" class="border rounded p-3" style="min-height: 250px;">
                <p>Hello from <strong>Tiptap</strong> editor! Click the <em>Insert Image</em> button above to pick media from Storage Manager.</p>
            </div>
        </div>
    </div>

    <script type="module">
        import { Editor } from 'https://esm.sh/@tiptap/core';
        import StarterKit from 'https://esm.sh/@tiptap/starter-kit';
        import Image from 'https://esm.sh/@tiptap/extension-image';
        import Link from 'https://esm.sh/@tiptap/extension-link';

        const editor = new Editor({
            element: document.querySelector('#tiptap-editor'),
            extensions: [
                StarterKit,
                Image,
                Link.configure({ openOnClick: false })
            ],
            content: document.querySelector('#tiptap-editor').innerHTML
        });

        document.getElementById('btn-bold').addEventListener('click', () => {
            editor.chain().focus().toggleBold().run();
        });

        document.getElementById('btn-italic').addEventListener('click', () => {
            editor.chain().focus().toggleItalic().run();
        });

        document.getElementById('btn-insert-image').addEventListener('click', () => {
            // Use openStorageManagerTiptap helper from storage-manager-tiptap.js
            window.openStorageManagerTiptap(editor, {
                url: '/storage-manager',
                type: 'image',
                mode: 'modal' // 'modal' or 'popup'
            });
        });
    </script>
</body>
</html>
