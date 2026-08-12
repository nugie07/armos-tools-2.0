@extends('layouts.app')

@section('title', 'Env Configuration')
@section('page_title', 'Env Configuration')
@section('breadcrumb', 'Env Configuration')

@section('content')
@php
  $activeTab = request('tab', 'production');
  if (! in_array($activeTab, ['production', 'preprod', 'general'], true)) {
      $activeTab = 'production';
  }

  $dbKeys = [
    'production' => [
      'host' => 'DATABASE_PROD_HOST',
      'port' => 'DATABASE_PROD_PORT',
      'database' => 'DATABASE_PROD_NAME',
      'username' => 'DATABASE_PROD_USERNAME',
      'password' => 'DATABASE_PROD_PASS',
    ],
    'preprod' => [
      'host' => 'DATABASE_PREPROD_HOST',
      'port' => 'DATABASE_PREPROD_PORT',
      'database' => 'DATABASE_PREPROD_NAME',
      'username' => 'DATABASE_PREPROD_USERNAME',
      'password' => 'DATABASE_PREPROD_PASS',
    ],
  ];
@endphp

@if (session('success'))
  <div class="alert alert-success alert-dismissible">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    {{ session('success') }}
  </div>
@endif

<div class="card card-primary card-outline card-tabs">
  <div class="card-header p-0 pt-1 border-bottom-0">
    <ul class="nav nav-tabs" id="env-tabs" role="tablist">
      <li class="nav-item">
        <a class="nav-link {{ $activeTab === 'production' ? 'active' : '' }}" data-toggle="pill" href="#tab-production" role="tab">Production</a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ $activeTab === 'preprod' ? 'active' : '' }}" data-toggle="pill" href="#tab-preprod" role="tab">Pre Production</a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ $activeTab === 'general' ? 'active' : '' }}" data-toggle="pill" href="#tab-general" role="tab">General Config</a>
      </li>
    </ul>
  </div>
  <form action="{{ route('env-config.update') }}" method="post" id="frmEnvConfig">
    @csrf
    @method('PUT')
    <input type="hidden" name="active_tab" id="active_tab" value="{{ $activeTab }}">
    <div class="card-body">
      <div class="tab-content" id="env-tabs-content">
        <div class="tab-pane fade {{ $activeTab === 'production' ? 'show active' : '' }}" id="tab-production" role="tabpanel">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <p class="text-muted mb-0">
              Database <strong>Production</strong> (`DATABASE_PROD_*`). Dipakai saat navbar memilih Production.
            </p>
            <button type="button" class="btn btn-outline-success btn-sm ml-3 btn-test-db" data-target-env="production">
              <i class="fas fa-plug"></i> Test Koneksi
            </button>
          </div>
          @foreach ($definitions['production'] as $key)
            <div class="form-group row">
              <label class="col-sm-3 col-form-label"><code>{{ $key }}</code></label>
              <div class="col-sm-9">
                <input type="{{ str_contains($key, 'PASS') ? 'password' : 'text' }}"
                       name="settings[{{ $key }}]"
                       id="field-{{ $key }}"
                       class="form-control"
                       value="{{ old('settings.'.$key, $values[$key] ?? '') }}"
                       autocomplete="off"
                       placeholder="Isi nilai {{ $key }}">
              </div>
            </div>
          @endforeach
        </div>

        <div class="tab-pane fade {{ $activeTab === 'preprod' ? 'show active' : '' }}" id="tab-preprod" role="tabpanel">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <p class="text-muted mb-0">
              Database <strong>Pre Production</strong> (`DATABASE_PREPROD_*`). Dipakai saat navbar memilih Pre Production.
            </p>
            <button type="button" class="btn btn-outline-success btn-sm ml-3 btn-test-db" data-target-env="preprod">
              <i class="fas fa-plug"></i> Test Koneksi
            </button>
          </div>
          @foreach ($definitions['preprod'] as $key)
            <div class="form-group row">
              <label class="col-sm-3 col-form-label"><code>{{ $key }}</code></label>
              <div class="col-sm-9">
                <input type="{{ str_contains($key, 'PASS') ? 'password' : 'text' }}"
                       name="settings[{{ $key }}]"
                       id="field-{{ $key }}"
                       class="form-control"
                       value="{{ old('settings.'.$key, $values[$key] ?? '') }}"
                       autocomplete="off"
                       placeholder="Isi nilai {{ $key }}">
              </div>
            </div>
          @endforeach
        </div>

        <div class="tab-pane fade {{ $activeTab === 'general' ? 'show active' : '' }}" id="tab-general" role="tabpanel">
          <p class="text-muted mb-3">
            <strong>Convert &amp; Send:</strong> `AUTH_URL`, `FEED_ORDER_URL`, `SEND_ORDER_USERNAME`, `SEND_ORDER_PASSWORD` (satu set untuk koneksi PROD).<br>
            Lainnya: WMS / WH_TYPE / Supabase. Database TMS ada di tab Production / Pre Production.
          </p>
          @foreach ($definitions['general'] as $key)
            <div class="form-group row">
              <label class="col-sm-3 col-form-label"><code>{{ $key }}</code></label>
              <div class="col-sm-9">
                <input type="{{ str_contains($key, 'KEY') || str_contains($key, 'SECRET') || str_contains($key, 'PASS') ? 'password' : 'text' }}"
                       name="settings[{{ $key }}]"
                       class="form-control"
                       value="{{ old('settings.'.$key, $values[$key] ?? '') }}"
                       autocomplete="off"
                       placeholder="Isi nilai {{ $key }}">
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </div>
    <div class="card-footer">
      <button type="submit" class="btn btn-primary">
        <i class="fas fa-save"></i> Simpan Configuration
      </button>
    </div>
  </form>
</div>

<div class="modal fade" id="modalTestOk" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content border-success">
      <div class="modal-header bg-success">
        <h5 class="modal-title text-white mb-0">Koneksi Berhasil</h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <p id="testOkMessage" class="mb-0"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success" data-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalTestFail" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content border-danger">
      <div class="modal-header bg-danger">
        <h5 class="modal-title text-white mb-0">Koneksi Gagal</h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <pre id="testFailMessage" class="mb-0" style="white-space: pre-wrap; word-break: break-word;"></pre>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
  const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  const dbKeys = @json($dbKeys);

  $('#env-tabs a[data-toggle="pill"]').on('shown.bs.tab', function (e) {
    var href = $(e.target).attr('href') || '';
    var tab = 'production';
    if (href.indexOf('preprod') !== -1) tab = 'preprod';
    if (href.indexOf('general') !== -1) tab = 'general';
    $('#active_tab').val(tab);
  });

  function fieldVal(key) {
    var el = document.getElementById('field-' + key);
    return el ? el.value.trim() : '';
  }

  document.querySelectorAll('.btn-test-db').forEach(function (btn) {
    btn.addEventListener('click', async function () {
      var target = btn.getAttribute('data-target-env');
      var map = dbKeys[target];
      if (!map) return;

      btn.disabled = true;
      var original = btn.innerHTML;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Testing...';

      try {
        var res = await fetch(@json(route('env-config.test')), {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrf,
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({
            target: target,
            host: fieldVal(map.host),
            port: fieldVal(map.port) || '5432',
            database: fieldVal(map.database),
            username: fieldVal(map.username),
            password: fieldVal(map.password)
          }),
          skipLoading: true
        });
        var json = await res.json();
        if (json.status === 200) {
          document.getElementById('testOkMessage').textContent = json.message || 'Koneksi berhasil.';
          $('#modalTestOk').modal('show');
        } else {
          document.getElementById('testFailMessage').textContent = json.message || 'Koneksi gagal.';
          $('#modalTestFail').modal('show');
        }
      } catch (err) {
        document.getElementById('testFailMessage').textContent = 'Error: ' + (err && err.message ? err.message : String(err));
        $('#modalTestFail').modal('show');
      } finally {
        btn.disabled = false;
        btn.innerHTML = original;
      }
    });
  });
})();
</script>
@endpush
