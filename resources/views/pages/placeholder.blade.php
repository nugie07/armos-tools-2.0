@extends('layouts.app')

@section('title', $title)
@section('page_title', $title)
@section('breadcrumb', $title)

@section('content')
<div class="card">
  <div class="card-header">
    <h3 class="card-title">{{ $title }}</h3>
  </div>
  <div class="card-body">
    <p class="text-muted mb-0">
      Halaman <strong>{{ $title }}</strong> masih placeholder UI (AdminLTE). Backend/API belum diimplementasikan.
    </p>
  </div>
</div>
@endsection
