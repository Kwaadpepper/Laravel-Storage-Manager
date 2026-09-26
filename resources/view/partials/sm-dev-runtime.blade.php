@section('content')
  <div id="file-manager"></div>
@endsection

@push('scripts')
  <script @if (Vite::cspNonce()) nonce="{{ Vite::cspNonce() }}" @endif type="module"
    src="{{ $devServerUrl }}/@@vite/client"></script>
  <script @if (Vite::cspNonce()) nonce="{{ Vite::cspNonce() }}" @endif type="module" src="{{ $devServerUrl }}/src/ts/main.ts">
  </script>
@endpush
