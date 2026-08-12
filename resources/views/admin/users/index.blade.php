@extends('layouts.app')

@section('title', 'User Management')
@section('page_title', 'User Management')
@section('breadcrumb', 'User Management')

@section('content')
@if (session('success'))
  <div class="alert alert-success alert-dismissible">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    {{ session('success') }}
  </div>
@endif
@if ($errors->any())
  <div class="alert alert-danger alert-dismissible">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    {{ $errors->first() }}
  </div>
@endif

<div class="card">
  <div class="card-header">
    <h3 class="card-title">Daftar User</h3>
    <div class="card-tools">
      <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">
        <i class="fas fa-plus"></i> Tambah User
      </a>
    </div>
  </div>
  <div class="card-body table-responsive p-0">
    <table class="table table-hover table-striped mb-0">
      <thead>
        <tr>
          <th style="width: 40%">Username</th>
          <th style="width: 40%">Password</th>
          <th style="width: 20%" class="text-right">Aksi</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($users as $user)
          <tr>
            <td>{{ $user->nama }}</td>
            <td><code>••••••••</code></td>
            <td class="text-right text-nowrap">
              <a href="{{ route('users.edit', $user) }}" class="btn btn-info btn-xs">
                <i class="fas fa-edit"></i> Edit
              </a>
              <form action="{{ route('users.destroy', $user) }}" method="post" class="d-inline" onsubmit="return confirm('Hapus user {{ $user->nama }}?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-xs" @if(auth()->id() === $user->id) disabled title="User sedang login" @endif>
                  <i class="fas fa-trash"></i> Hapus
                </button>
              </form>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="3" class="text-center text-muted py-4">Belum ada user.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
