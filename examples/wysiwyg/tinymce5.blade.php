<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Storage Manager - TinyMCE Integration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js"></script>
</head>
<body class="p-5 bg-light">
    <div class="container bg-white p-4 rounded shadow-sm" style="max-width: 900px;">
        <h2 class="mb-4">Storage Manager — TinyMCE Integration</h2>

        <div class="mb-3">
            <label class="form-label fw-bold">Editor Content:</label>
            <textarea id="editor" name="content">
                <h2>Welcome to Laravel Storage Manager</h2>
                <p>Open the Image or Link dialog and click the file picker icon to browse files in Storage Manager.</p>
            </textarea>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            tinymce.init({
                selector: 'textarea#editor',
                height: 400,
                plugins: 'image link media code table lists',
                toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | link image media | code',
                
                // Wire TinyMCE file_picker_callback to Laravel Storage Manager
                file_picker_callback: function (callback, value, meta) {
                    const width = window.innerWidth * 0.8;
                    const height = window.innerHeight * 0.8;
                    const type = meta.filetype === 'image' ? 'image' : (meta.filetype === 'media' ? 'video' : '');
                    const url = '/storage-manager?picker=1' + (type ? '&type=' + type : '');

                    const popup = window.open(
                        url,
                        'StorageManager',
                        'width=' + width + ',height=' + height + ',resizable=yes,scrollbars=yes'
                    );

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
        });
    </script>
</body>
</html>
