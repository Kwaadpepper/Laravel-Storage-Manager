<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>{{ app('storage-manager.config')->getStaticConfig('packageName') }}</title>
  <meta name="storage-manager-url" content="{{ url('/') }}">

  <link type="image/png" href="{{ route('storage-manager.assets', ['dir' => 'images', 'file' => 'favicon-96x96.png']) }}" rel="icon"
    sizes="96x96" />
  <link type="image/svg+xml" href="{{ route('storage-manager.assets', ['dir' => 'images', 'file' => 'favicon.svg']) }}" rel="icon" />
  <link href="{{ route('storage-manager.assets', ['dir' => 'images', 'file' => 'favicon.ico']) }}" rel="shortcut icon" />
  <link href="{{ route('storage-manager.assets', ['dir' => 'images', 'file' => 'apple-touch-icon.png']) }}" rel="apple-touch-icon"
    sizes="180x180" />
  <meta name="apple-mobile-web-app-title" content="Storag. Man" />
  <link href="{{ route('storage-manager.assets', ['dir' => 'images', 'file' => 'site.webmanifest']) }}" rel="manifest" />

  @stack('styles')
</head>

<body>
  @yield('content')

  @stack('scripts')
</body>

</html>
