<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Storage Manager - CKEditor 5 Integration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.ckeditor.com/ckeditor5/41.2.0/classic/ckeditor.js"></script>
    <script src="{{ asset('vendor/storage-manager/js/storage-manager-picker.js') }}"></script>
    <script src="{{ asset('vendor/storage-manager/js/storage-manager-ckeditor5.js') }}"></script>
</head>
<body class="p-5 bg-light">
    <div class="container bg-white p-4 rounded shadow-sm" style="max-width: 900px;">
        <h2 class="mb-4">Storage Manager — CKEditor 5 Integration</h2>

        <div class="mb-3">
            <label class="form-label fw-bold">Editor Content:</label>
            <textarea id="editor" name="content">
                <h2>Welcome to Laravel Storage Manager</h2>
                <p>Click the <strong>Storage Manager</strong> toolbar button to insert images or media files directly into your content.</p>
            </textarea>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            ClassicEditor
                .create(document.querySelector('#editor'), {
                    extraPlugins: [
                        window.StorageManagerCKEditor5
                    ],
                    toolbar: {
                        items: [
                            'heading', '|',
                            'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|',
                            'storageManager', '|', // Custom Storage Manager button
                            'blockQuote', 'undo', 'redo'
                        ]
                    },
                    storageManager: {
                        url: '/storage-manager',
                        type: 'image', // Scopes to images; omit to allow any file
                        mode: 'modal'  // 'modal' or 'popup'
                    }
                })
                .catch(error => {
                    console.error('CKEditor 5 init error:', error);
                });
        });
    </script>
</body>
</html>
