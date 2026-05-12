@extends('storage-manager::layout')

@php
  $useDevServer = (bool) config('storage-manager.frontend.dev_server.enabled', false);
  $devServerUrl = rtrim((string) config('storage-manager.frontend.dev_server.url', 'http://localhost:5173'), '/');
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
