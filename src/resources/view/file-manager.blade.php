@extends('storage-manager::layout')

@php
  $useDevServer = (bool) config('storage-manager.frontend.use_dev_server', false);
  $devServerUrl = rtrim((string) config('storage-manager.frontend.dev_server_url', 'http://localhost:5173'), '/');
  $cssFilePublished = file_exists(public_path('vendor/storage-manager/css/main.css'));
  $jsFilePublished = file_exists(public_path('vendor/storage-manager/js/main.js'));
@endphp

@if ($useDevServer)
  @section('content')
    <div id="file-manager"></div>
  @endsection

  @push('styles')
    <script type="module">
      import RefreshRuntime from '{{ $devServerUrl }}/@@react-refresh'
      RefreshRuntime.injectIntoGlobalHook(window)
      window.$RefreshReg$ = () => {}
      window.$RefreshSig$ = () => (type) => type
      window.__vite_plugin_react_preamble_installed__ = true
    </script>
  @endpush

  @push('scripts')
    <script type="module" src="{{ $devServerUrl }}/@@vite/client"></script>
    <script type="module" src="{{ $devServerUrl }}/src/ts/main.tsx"></script>
  @endpush
@elseif (!$cssFilePublished || !$jsFilePublished)
  @section('content')
    <div class="missing-assets-warning">
      Warning: Assets not published. Please run <code>php artisan vendor:publish --tag=storage-manager:assets</code>.
    </div>
    <div id="file-manager"></div>
  @endsection
  @push('styles')
    <style>
      .missing-assets-warning {
        background-color: #ffcc00;
        color: #333;
        padding: 10px;
        text-align: center;
        font-weight: bold;
      }
    </style>
  @endpush
@else
  @section('content')
    <div id="file-manager"></div>
  @endsection

  @push('styles')
    <link href="{{ asset('vendor/storage-manager/css/main.css') }}" rel="stylesheet">
  @endpush

  @push('scripts')
    <script type="module" src="{{ asset('vendor/storage-manager/js/main.js') }}"></script>
  @endpush
@endif
