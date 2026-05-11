@extends('storage-manager::layout')

@php
  $cssFilePublished = file_exists(public_path('vendor/storage-manager/css/main.css'));
  $jsFilePublished = file_exists(public_path('vendor/storage-manager/js/main.js'));
@endphp

@if (!$cssFilePublished || !$jsFilePublished)
  @section('content')
    <div class="missing-assets-warning">
      Warning: Assets not published. Please run <code>php artisan vendor:publish --tag=storage-manager:assets</code>.
    </div>
    <div id="file-manager"></div>
  @endsection
  @push('styles')
    <style @if (Vite::cspNonce()) nonce="{{ Vite::cspNonce() }}" @endif>
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
    <link href="{{ asset('vendor/storage-manager/css/main.css') }}" @if (Vite::cspNonce()) nonce="{{ Vite::cspNonce() }}" @endif
      rel="stylesheet">
  @endpush

  @push('scripts')
    <script @if (Vite::cspNonce()) nonce="{{ Vite::cspNonce() }}" @endif type="module"
      src="{{ asset('vendor/storage-manager/js/main.js') }}"></script>
  @endpush
@endif
