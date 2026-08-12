@extends('layouts.app')

@section('title', 'Dashboard')
@section('page_title')
Dashboard <small class="text-muted font-weight-normal" style="font-size: 1rem;">beta 1.0</small>
@endsection
@section('breadcrumb', 'Dashboard')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">ARMoS Tools — 14 Utilitas Operasional</h3>
      </div>
      <div class="card-body">
        <p class="mb-3">
          Selamat datang, <strong>{{ auth()->user()->nama }}</strong>.
          Pilih menu di sidebar kanan atau kartu di bawah.
        </p>
      </div>
    </div>
  </div>
</div>

<div class="row">
  @foreach($menus as $menu)
    <div class="col-md-6 col-lg-4">
      <div class="small-box bg-light">
        <div class="inner">
          <h5>{{ $menu['title'] }}</h5>
          <p class="text-muted mb-0" style="min-height: 3rem;">{{ $menu['description'] }}</p>
        </div>
        <div class="icon"><i class="{{ $menu['icon'] }}"></i></div>
        <a href="{{ route($menu['route']) }}" class="small-box-footer bg-primary">
          Buka <i class="fas fa-arrow-circle-right"></i>
        </a>
      </div>
    </div>
  @endforeach
</div>
@endsection
