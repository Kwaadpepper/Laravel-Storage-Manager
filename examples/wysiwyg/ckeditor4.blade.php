<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Storage Manager - CKEditor 4 Integration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>
</head>
<body class="p-5 bg-light">
    <div class="container bg-white p-4 rounded shadow-sm" style="max-width: 900px;">
        <h2 class="mb-4">Storage Manager — CKEditor 4 Integration</h2>

        <div class="mb-3">
            <label class="form-label fw-bold">Editor Content:</label>
            <textarea id="editor" name="content">
                <h2>Welcome to Laravel Storage Manager</h2>
                <p>Use the standard CKEditor Image dialog. Clicking "Browse Server" will automatically open Storage Manager.</p>
            </textarea>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            CKEDITOR.replace('editor', {
                // Configure filebrowser URLs directly to Laravel Storage Manager
                filebrowserImageBrowseUrl: '/storage-manager?type=image',
                filebrowserBrowseUrl: '/storage-manager'
            });
        });
    </script>
</body>
</html>
