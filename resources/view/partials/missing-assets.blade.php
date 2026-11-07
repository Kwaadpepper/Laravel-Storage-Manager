@section('content')
  <div class="missing-assets-warning">
    {{ __('storage-manager::storage-manager.ui.missing_assets') }}
  </div>
  <div id="file-manager"></div>
@endsection

@push('styles')
  <style @if (!empty($cspNonce)) nonce="{{ $cspNonce }}" @endif>
    .missing-assets-warning {
      background-color: #ffcc00;
      color: #333;
      padding: 10px;
      text-align: center;
      font-weight: bold;
    }
  </style>
@endpush
