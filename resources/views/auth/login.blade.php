@extends('layouts.guest')

@section('title', 'Login')

@section('content')
<div class="login-box">
  <div class="login-logo">
    <a href="{{ url('/') }}"><b>ARMoS</b> Tools</a>
  </div>

  <div class="card">
    <div class="card-body login-card-body">
      <p class="login-box-msg">Masuk untuk memulai sesi</p>

      @if ($errors->any())
        <div class="alert alert-danger alert-dismissible">
          <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
          {{ $errors->first() }}
        </div>
      @endif

      <form action="{{ url('/login') }}" method="post">
        @csrf
        <div class="input-group mb-3">
          <input type="text" name="nama" class="form-control @error('nama') is-invalid @enderror" placeholder="Nama" value="{{ old('nama', 'admin') }}" required autofocus>
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-user"></span>
            </div>
          </div>
        </div>
        <div class="input-group mb-3">
          <input type="password" name="password" class="form-control" placeholder="Password" required>
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-lock"></span>
            </div>
          </div>
        </div>

        <div class="form-group mb-3">
          <label for="captcha" class="captcha-label">
            Captcha: berapa <strong>{{ $captchaA }} × {{ $captchaB }}</strong> ?
          </label>
          <div class="input-group">
            <input
              type="number"
              name="captcha"
              id="captcha"
              class="form-control @error('captcha') is-invalid @enderror"
              placeholder="Hasil perkalian"
              required
              autocomplete="off"
            >
            <div class="input-group-append">
              <div class="input-group-text">
                <span class="fas fa-calculator"></span>
              </div>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-8">
            <div class="icheck-primary">
              <input type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
              <label for="remember">Remember Me</label>
            </div>
          </div>
          <div class="col-4">
            <button type="submit" class="btn btn-primary btn-block">Masuk</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
