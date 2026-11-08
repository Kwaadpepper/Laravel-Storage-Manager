<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Storage Manager - Standalone Button Examples</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-5 bg-light">
    <div class="container bg-white p-4 rounded shadow-sm" style="max-width: 800px;">
        <h2 class="mb-4">Storage Manager — Standalone Button Integration</h2>

        <!-- Method 1: Ultra-simple zero-dependency fmSetLink callback (alexusmai compatible) -->
        <div class="card mb-4">
            <div class="card-header fw-bold">Method 1: Zero-Dependency Callback (fmSetLink)</div>
            <div class="card-body">
                <p class="text-muted">No external JavaScript library required. Simply opens a popup window and defines a global <code>fmSetLink</code> callback.</p>
                
                <div class="input-group mb-3">
                    <input type="text" id="image_url_simple" class="form-control" placeholder="Select an image...">
                    <button class="btn btn-primary" type="button" onclick="openStorageManagerPopup()">Select Image</button>
                </div>
                <div>
                    <img id="image_preview_simple" src="" alt="Preview" class="img-thumbnail d-none" style="max-height: 150px;">
                </div>
            </div>
        </div>

        <!-- Method 2: Modern Vanilla JS Helper (StorageManagerPicker.attach) -->
        <div class="card">
            <div class="card-header fw-bold">Method 2: StorageManagerPicker Helper (Popup or Modal)</div>
            <div class="card-body">
                <p class="text-muted">Uses the pre-built <code>storage-manager-picker.js</code> bundle with automatic input binding and modal/popup support.</p>
                
                <div class="input-group mb-3">
                    <input type="text" id="image_url_helper" class="form-control" placeholder="Choose image via modal...">
                    <button id="btn_open_modal" class="btn btn-outline-secondary" type="button">Browse Modal</button>
                </div>
                <div>
                    <img id="image_preview_helper" src="" alt="Preview" class="img-thumbnail d-none" style="max-height: 150px;">
                </div>
            </div>
        </div>
    </div>

    <!-- Script for Method 1 -->
    <script>
        function openStorageManagerPopup() {
            window.open('/storage-manager?picker=1&type=image', 'StorageManager', 'width=1200,height=700,resizable=yes,scrollbars=yes');
        }

        // Called automatically by Storage Manager when user selects a file
        function fmSetLink(url, file) {
            const input = document.getElementById('image_url_simple');
            const preview = document.getElementById('image_preview_simple');
            input.value = url;
            if (preview) {
                preview.src = url;
                preview.classList.remove('d-none');
            }
        }
    </script>

    <!-- Script for Method 2 (using published package asset) -->
    <script src="{{ asset('vendor/storage-manager/js/storage-manager-picker.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.StorageManagerPicker) {
                window.StorageManagerPicker.attach('#btn_open_modal', '#image_url_helper', {
                    url: '/storage-manager',
                    type: 'image',
                    mode: 'modal', // 'modal' (iframe) or 'popup'
                    preview: '#image_preview_helper',
                    onSelect: function (file) {
                        const preview = document.getElementById('image_preview_helper');
                        if (preview) {
                            preview.classList.remove('d-none');
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>
