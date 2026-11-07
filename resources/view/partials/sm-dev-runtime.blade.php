@section('content')
  <div id="file-manager"></div>
@endsection

@push('scripts')
  <script @if (!empty($cspNonce)) nonce="{{ $cspNonce }}" @endif type="module"
    src="{{ $devServerUrl }}/@@vite/client"></script>
  <script @if (!empty($cspNonce)) nonce="{{ $cspNonce }}" @endif type="module" src="{{ $devServerUrl }}/src/ts/main.ts">
  </script>
@endpush
