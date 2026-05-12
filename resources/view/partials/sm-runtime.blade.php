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
