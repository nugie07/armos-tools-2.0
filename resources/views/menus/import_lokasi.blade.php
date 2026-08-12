@extends('layouts.app')

@section('title', 'Import Lokasi')
@section('page_title', 'Import Lokasi')
@section('breadcrumb', 'Import Lokasi')

@push('styles')
<style>
  textarea { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
</style>
@endpush

@section('content')
<div class="card card-outline card-primary">
  <div class="card-body">
    <form id="frmUpload">
      <div class="form-group">
        <label for="fileInput">Pilih file Excel (.xlsx)</label>
        <input id="fileInput" type="file" class="form-control-file" accept=".xlsx" required>
        <small class="form-text text-muted">
          Target DB mengikuti environment aktif di kanan atas (session).
          File yang diupload akan disimpan di folder <code>import_lokasi</code>
        </small>
      </div>

      <div class="d-flex align-items-center mb-3">
        <button id="btnProcess" type="submit" class="btn btn-primary">Upload &amp; Import</button>
        <div id="loading" class="spinner-border spinner-border-sm text-primary ml-2 d-none" role="status">
          <span class="sr-only">Loading...</span>
        </div>
      </div>
    </form>

    <div id="resultMessage" class="d-none"></div>

    <div id="downloadSection" class="mb-3 d-none">
      <button id="btnDownloadLog" type="button" class="btn btn-success">Download Log Excel</button>
      <small class="form-text text-muted">Download log hasil import dalam format Excel dengan 2 sheet (Parent dan Child)</small>
    </div>

    <div class="form-group mb-0">
      <label for="txtMessages">Detail Proses Import</label>
      <textarea id="txtMessages" class="form-control" rows="15" readonly placeholder="Detail proses import akan muncul di sini..."></textarea>
    </div>

    <div class="alert alert-info mt-3 mb-0">
      <strong>Format File Excel:</strong><br>
      - Kolom <code>is_parent</code>: Y untuk insert ke <code>mst_location_parent</code>, selain itu insert ke <code>mst_location_child</code><br>
      - Kolom wajib: <code>is_parent</code>, <code>code</code>, <code>name</code><br>
      - Untuk <code>mst_location_child</code>, kolom tambahan: <code>tipe_child</code>, <code>channel</code>, <code>availability</code>,
      <code>alamat</code>, <code>longitude</code>, <code>latitude</code>, <code>unloading_duration</code>,
      <code>frequency_drop</code>, <code>available_drop_days</code>, <code>loading_dock</code>, <code>priority</code>,
      <code>open_hour</code>, <code>closed_hour</code>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
  const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  const frmUpload = document.getElementById('frmUpload');
  const fileInput = document.getElementById('fileInput');
  const btnProcess = document.getElementById('btnProcess');
  const loading = document.getElementById('loading');
  const txtMessages = document.getElementById('txtMessages');
  const resultMessage = document.getElementById('resultMessage');
  const downloadSection = document.getElementById('downloadSection');
  const btnDownloadLog = document.getElementById('btnDownloadLog');
  let currentLogFilename = null;

  frmUpload.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!window.requireArmosEnv()) return;
    if (!fileInput.files || fileInput.files.length === 0) {
      alert('Pilih file .xlsx terlebih dahulu');
      return;
    }

    loading.classList.remove('d-none');
    btnProcess.disabled = true;
    txtMessages.value = '';
    resultMessage.className = 'd-none';
    resultMessage.innerHTML = '';
    downloadSection.classList.add('d-none');
    currentLogFilename = null;

    try {
      const fd = new FormData();
      fd.append('file', fileInput.files[0]);

      const res = await fetch('/api/import-lokasi', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: fd
      });
      const data = await res.json();

      if (data.messages && Array.isArray(data.messages)) {
        txtMessages.value = data.messages.join('\n');
      } else if (data.message) {
        txtMessages.value = data.message;
      }

      resultMessage.className = 'alert';
      if (data.success || res.status === 200) {
        resultMessage.classList.add('alert-success');
        resultMessage.innerHTML = `
          <strong>Import Berhasil!</strong><br>
          ${data.message || 'Import selesai tanpa error.'}
          ${data.filename ? `<br><small>File: ${data.filename}</small>` : ''}
        `;
        if (data.log_data_filename) {
          currentLogFilename = data.log_data_filename;
          downloadSection.classList.remove('d-none');
        }
      } else {
        resultMessage.classList.add('alert-danger');
        resultMessage.innerHTML = `
          <strong>Import Gagal!</strong><br>
          ${data.message || 'Import gagal. Lihat detail di bawah.'}
          ${data.filename ? `<br><small>File: ${data.filename}</small>` : ''}
        `;
        if (data.log_data_filename) {
          currentLogFilename = data.log_data_filename;
          downloadSection.classList.remove('d-none');
        }
      }
      resultMessage.classList.remove('d-none');
    } catch (err) {
      resultMessage.className = 'alert alert-danger';
      resultMessage.innerHTML = `
        <strong>Error!</strong><br>
        Terjadi error saat memproses: ${err && err.message ? err.message : String(err)}
      `;
      resultMessage.classList.remove('d-none');
      txtMessages.value = `Error: ${err && err.message ? err.message : String(err)}`;
    } finally {
      loading.classList.add('d-none');
      btnProcess.disabled = false;
      frmUpload.reset();
    }
  });

  btnDownloadLog.addEventListener('click', () => {
    if (!currentLogFilename) {
      alert('Tidak ada log yang tersedia untuk didownload');
      return;
    }
    const url = `/api/import-lokasi/download-log?filename=${encodeURIComponent(currentLogFilename)}`;
    window.open(url, '_blank');
  });
})();
</script>
@endpush
