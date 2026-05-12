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
