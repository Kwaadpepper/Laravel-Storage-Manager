@section('content')
  <div id="file-manager"></div>
@endsection

@push('styles')
  <script @if (Vite::cspNonce()) nonce="{{ Vite::cspNonce() }}" @endif type="module">
    import RefreshRuntime from '{{ $devServerUrl }}/@@react-refresh'
    RefreshRuntime.injectIntoGlobalHook(window)
    window.$RefreshReg$ = () => {}
    window.$RefreshSig$ = () => (type) => type
    window.__vite_plugin_react_preamble_installed__ = true
  </script>
@endpush

@push('scripts')
  <script @if (Vite::cspNonce()) nonce="{{ Vite::cspNonce() }}" @endif type="module"
    src="{{ $devServerUrl }}/@@vite/client"></script>
  <script @if (Vite::cspNonce()) nonce="{{ Vite::cspNonce() }}" @endif type="module" src="{{ $devServerUrl }}/src/ts/main.tsx">
  </script>
@endpush
