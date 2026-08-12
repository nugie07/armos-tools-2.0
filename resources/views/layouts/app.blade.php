<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Dashboard') — {{ config('app.name', 'ARMoS Tools') }}</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="{{ asset('css/armos.css') }}">
  @stack('styles')
</head>
@php
  $armosEnvSession = \App\Support\ArmosEnvironment::sessionValue();
  $armosEnvApi = $armosEnvSession === 'production' ? 'prod' : ($armosEnvSession === 'preprod' ? 'preprod' : null);
  $skipEnvRequired = false;
@endphp
<body class="hold-transition sidebar-mini layout-fixed layout-sidebar-right">
<div class="wrapper">

  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <a href="{{ route('dashboard') }}" class="nav-link">Home</a>
      </li>
    </ul>

    <ul class="navbar-nav ml-auto align-items-center flex-row">
      <li class="nav-item dropdown">
        <a class="nav-link" data-toggle="dropdown" href="#">
          <i class="far fa-user"></i>
          <span class="d-none d-md-inline ml-1">{{ auth()->user()->nama ?? 'User' }}</span>
        </a>
        <div class="dropdown-menu dropdown-menu-right">
          <form action="{{ route('logout') }}" method="post" class="px-0 mb-0">
            @csrf
            <button type="submit" class="dropdown-item">
              <i class="fas fa-sign-out-alt mr-2"></i> Logout
            </button>
          </form>
        </div>
      </li>

      <li class="nav-item">
        <form action="{{ route('env.select') }}" method="post" class="form-inline navbar-env-form" id="navbarEnvForm">
          @csrf
          <select name="environment" id="navbarEnvSelect" class="form-control form-control-sm navbar-env-select" required>
            <option value="">-- Pilih Environment --</option>
            <option value="production" {{ $armosEnvSession === 'production' ? 'selected' : '' }}>Production</option>
            <option value="preprod" {{ $armosEnvSession === 'preprod' ? 'selected' : '' }}>Pre Production</option>
          </select>
          <button type="submit" class="btn btn-sm btn-primary ml-2">Pilih</button>
          @if ($armosEnvSession)
            <span class="badge badge-{{ $armosEnvSession === 'production' ? 'danger' : 'info' }} ml-2 d-none d-md-inline">
              {{ $armosEnvSession === 'production' ? 'PROD' : 'PREPROD' }}
            </span>
          @endif
        </form>
      </li>
    </ul>
  </nav>

  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="{{ route('dashboard') }}" class="brand-link">
      <span class="brand-text font-weight-light">{{ config('app.name', 'ARMoS Tools') }}</span>
    </a>

    <div class="sidebar">
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <img src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User">
        </div>
        <div class="info">
          <a href="#" class="d-block">{{ auth()->user()->nama ?? 'User' }}</a>
        </div>
      </div>

      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          <li class="nav-header">MENU</li>

          <li class="nav-item">
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>Dashboard</p>
            </a>
          </li>

          @foreach(config('armos_menus.items', []) as $item)
            <li class="nav-item">
              <a href="{{ route($item['route']) }}" class="nav-link {{ request()->is(ltrim($item['path'], '/')) ? 'active' : '' }}">
                <i class="nav-icon {{ $item['icon'] }}"></i>
                <p>{{ $item['title'] }}</p>
              </a>
            </li>
          @endforeach

          <li class="nav-header">ADMIN</li>
          <li class="nav-item">
            <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
              <i class="nav-icon fas fa-users-cog"></i>
              <p>User Management</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="{{ route('env-config.index') }}" class="nav-link {{ request()->routeIs('env-config.*') ? 'active' : '' }}">
              <i class="nav-icon fas fa-cogs"></i>
              <p>Env Configuration</p>
            </a>
          </li>

          <li class="nav-header">ACCOUNT</li>
          <li class="nav-item">
            <form action="{{ route('logout') }}" method="post">
              @csrf
              <button type="submit" class="nav-link btn btn-link text-left text-white w-100">
                <i class="nav-icon fas fa-sign-out-alt"></i>
                <p>Logout</p>
              </button>
            </form>
          </li>
        </ul>
      </nav>
    </div>
  </aside>

  <div class="content-wrapper">
    <div class="content-header">
      <div class="container-fluid">
        @if (session('success') && ! request()->routeIs('users.*') && ! request()->routeIs('env-config.*'))
          <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('success') }}
          </div>
        @endif
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">@yield('page_title', 'Dashboard')</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
              <li class="breadcrumb-item active">@yield('breadcrumb', 'Dashboard')</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <section class="content">
      <div class="container-fluid">
        @yield('content')
      </div>
    </section>
  </div>

  <footer class="main-footer">
    <strong>{{ config('app.name', 'ARMoS Tools') }} 2.0</strong>
    <div class="float-right d-none d-sm-inline-block">AdminLTE 3</div>
  </footer>
</div>

{{-- Global loading overlay untuk semua GET/POST API --}}
<div id="armosGlobalLoading" class="armos-global-loading d-none" aria-live="polite" aria-busy="true">
  <div class="armos-global-loading-box">
    <div class="spinner-border text-primary mb-2" role="status" style="width: 2.5rem; height: 2.5rem;">
      <span class="sr-only">Loading...</span>
    </div>
    <div class="armos-loading-text font-weight-bold">Memproses...</div>
  </div>
</div>

{{-- Modal: environment belum dipilih (kecuali Sync Manager) --}}
<div class="modal fade" id="modalEnvRequired" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title font-weight-bold mb-0">PILIH ENVIRONMENT TERLEBIH DAHULU</h5>
      </div>
      <div class="modal-body">
        <p class="mb-3">Environment aktif belum dipilih. Pilih <strong>Production</strong> atau <strong>Pre Production</strong> (dari Env Configuration table), lalu klik <strong>Pilih</strong>.</p>
        <form action="{{ route('env.select') }}" method="post" class="form-inline">
          @csrf
          <select name="environment" class="form-control mr-2" required>
            <option value="">-- Pilih Environment --</option>
            <option value="production">Production</option>
            <option value="preprod">Pre Production</option>
          </select>
          <button type="submit" class="btn btn-primary">Pilih</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script>
  window.ARMOS_ENV = @json($armosEnvApi);
  window.ARMOS_ENV_SESSION = @json($armosEnvSession);
  window.ARMOS_ENV_OPTIONAL = @json($skipEnvRequired);

  window.requireArmosEnv = function () {
    if (window.ARMOS_ENV_OPTIONAL) return true;
    if (!window.ARMOS_ENV) {
      $('#modalEnvRequired').modal('show');
      return null;
    }
    return window.ARMOS_ENV;
  };

  window.armosLoading = (function () {
    var count = 0;
    function el() { return document.getElementById('armosGlobalLoading'); }
    function textEl() {
      var node = el();
      return node ? node.querySelector('.armos-loading-text') : null;
    }
    return {
      show: function (msg) {
        count += 1;
        var node = el();
        if (!node) return;
        var t = textEl();
        if (t) t.textContent = msg || 'Memproses...';
        node.classList.remove('d-none');
      },
      hide: function () {
        count = Math.max(0, count - 1);
        if (count === 0) {
          var node = el();
          if (node) node.classList.add('d-none');
        }
      },
      wrap: async function (fn, msg) {
        this.show(msg);
        try { return await fn(); }
        finally { this.hide(); }
      }
    };
  })();

  // Auto-loading + env guard untuk semua /api/*
  (function () {
    var originalFetch = window.fetch.bind(window);
    window.fetch = async function (input, init) {
      var url = typeof input === 'string' ? input : (input && input.url ? input.url : '');
      var isApi = url.indexOf('/api/') !== -1;
      var skipLoading = init && init.skipLoading;
      var opts = init ? Object.assign({}, init) : {};
      if (opts.skipLoading !== undefined) delete opts.skipLoading;

      if (isApi && !window.ARMOS_ENV) {
        window.requireArmosEnv();
        return new Response(JSON.stringify({
          status: 400,
          message: 'PILIH ENVIRONMENT TERLEBIH DAHULU',
          data: null
        }), {
          status: 400,
          headers: { 'Content-Type': 'application/json' }
        });
      }

      if (isApi && !skipLoading) {
        var method = ((opts.method || (typeof input !== 'string' && input.method) || 'GET') + '').toUpperCase();
        var label = method === 'GET' ? 'Mengambil data...' : (method === 'POST' || method === 'PUT' || method === 'PATCH' ? 'Menyimpan data...' : 'Memproses...');
        window.armosLoading.show(label);
      }
      try {
        return await originalFetch(input, opts);
      } finally {
        if (isApi && !skipLoading) window.armosLoading.hide();
      }
    };
  })();

  @if (! $armosEnvSession && ! $skipEnvRequired)
  $(function () {
    $('#modalEnvRequired').modal('show');
  });
  @endif
</script>
@stack('scripts')
</body>
</html>
