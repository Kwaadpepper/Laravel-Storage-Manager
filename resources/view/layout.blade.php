<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>{{ app('storage-manager.config')->getStaticConfig('packageName') }}</title>
  <meta name="storage-manager-url" content="{{ url('/') }}">

  @stack('styles')
</head>

<body>
  @yield('content')

  @stack('scripts')
</body>

</html>
