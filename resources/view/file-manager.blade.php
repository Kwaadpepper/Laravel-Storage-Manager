@extends('storage-manager::layout')

@push('scripts')
  <script @if (!empty($cspNonce)) nonce="{{ $cspNonce }}" @endif>
    window.__STORAGE_MANAGER_CONFIG__ = @json($storageManagerConfig);
  </script>
@endpush

@php
  $useDevServer = (bool) ($useDevServer ?? false);
  $devServerUrl = rtrim((string) ($devServerUrl ?? 'http://localhost:5173'), '/');
  $cssFilePublished = file_exists(public_path('vendor/storage-manager/css/main.css'));
  $jsFilePublished = file_exists(public_path('vendor/storage-manager/js/main.js'));
@endphp

{{-- DEV SERVER --}}
@if ($useDevServer)
  @include('storage-manager::partials.sm-dev-runtime', [
      'devServerUrl' => $devServerUrl,
  ])

  {{-- MISSING ASSETS --}}
@elseif (!$cssFilePublished || !$jsFilePublished)
  @include('storage-manager::partials.missing-assets')

  {{-- PRODUCTION --}}
@else
  @include('storage-manager::partials.sm-runtime')
@endif
