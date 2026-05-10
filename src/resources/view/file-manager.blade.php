@extends('storage-manager::layout')

@push('styles')
  <link rel="stylesheet" href="{{ asset('vendor/storage-manager/css/app.css') }}">
@endpush

@push('scripts')
  <script type="module" src="{{ asset('vendor/storage-manager/js/main.js') }}"></script>
@endpush

@section('content')
  <div id="file-manager"></div>
@endsection
