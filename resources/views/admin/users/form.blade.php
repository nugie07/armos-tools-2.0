@extends('layouts.app')

@section('title', $mode === 'create' ? 'Tambah User' : 'Edit User')
@section('page_title', $mode === 'create' ? 'Tambah User' : 'Edit User')
@section('breadcrumb', 'User Management')

@section('content')
<div class="card card-primary">
  <div class="card-header">
    <h3 class="card-title">{{ $mode === 'create' ? 'Form Tambah User' : 'Form Edit User' }}</h3>
  </div>
  <form action="{{ $mode === 'create' ? route('users.store') : route('users.update', $user) }}" method="post">
    @csrf
    @if ($mode === 'edit')
      @method('PUT')
    @endif
    <div class="card-body">
      @if ($errors->any())
        <div class="alert alert-danger">
          <ul class="mb-0">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <div class="form-group">
        <label for="nama">Username</label>
        <input type="text" name="nama" id="nama" class="form-control @error('nama') is-invalid @enderror"
               value="{{ old('nama', $user->nama) }}" required autofocus>
        @error('nama') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror"
               {{ $mode === 'create' ? 'required' : '' }}
               placeholder="{{ $mode === 'edit' ? 'Kosongkan jika tidak diubah' : '' }}">
        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
    </div>
    <div class="card-footer">
      <button type="submit" class="btn btn-primary">Simpan</button>
      <a href="{{ route('users.index') }}" class="btn btn-default">Batal</a>
    </div>
  </form>
</div>
@endsection
