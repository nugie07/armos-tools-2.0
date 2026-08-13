@extends('layouts.app')

@section('title', 'Upload Order (Convert & Send)')
@section('page_title', 'Upload Order (Convert & Send)')
@section('breadcrumb', 'Convert & Send')

@push('styles')
<style>
  textarea { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
</style>
@endpush

@section('content')
<div class="card card-outline card-primary">
  <div class="card-body">
    <form id="frmUpload">
      <div class="row align-items-end">
        <div class="col-md-8">
          <div class="form-group mb-md-0">
            <label for="fileInput">Pilih file Excel (.xlsx)</label>
            <input id="fileInput" type="file" class="form-control-file" accept=".xlsx" required>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group mb-md-0 d-flex align-items-center">
            <button id="btnProcess" type="submit" class="btn btn-primary flex-grow-1">Upload, Convert &amp; Send</button>
            <div id="loading" class="spinner-border spinner-border-sm text-primary ml-2 d-none" role="status">
              <span class="sr-only">Loading...</span>
            </div>
          </div>
        </div>
      </div>
    </form>

    <div class="form-group mt-3 mb-0">
      <label for="txtJson">Hasil Konversi (JSON)</label>
      <textarea id="txtJson" class="form-control" rows="14" readonly placeholder="Hasil JSON akan muncul di sini..."></textarea>
    </div>

    <div class="alert alert-info mt-3 mb-0">
      File harus berisi sheet <code>order_data</code> dan <code>order_detail</code> (relasi <code>order_data.id</code> = <code>order_detail.order_data_id</code>). Hasil konversi tampil di textarea, lalu tiap order dikirim ke feed API.
    </div>
  </div>
</div>

<div class="modal fade" id="modalInfo" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Informasi Proses</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <pre id="modalMessage" class="mb-0" style="white-space: pre-wrap; word-wrap: break-word;"></pre>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
      </div>
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
  const txtJson = document.getElementById('txtJson');
  const modalMessage = document.getElementById('modalMessage');

  frmUpload.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!fileInput.files || fileInput.files.length === 0) {
      alert('Pilih file .xlsx terlebih dahulu');
      return;
    }
    loading.classList.remove('d-none');
    btnProcess.disabled = true;
    txtJson.value = '';
    modalMessage.textContent = '';
    try {
      const fd = new FormData();
      fd.append('file', fileInput.files[0]);
      const res = await fetch('/api/convert-send', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: fd
      });
      const ct = res.headers.get('content-type') || '';
      let data;
      if (ct.includes('application/json')) {
        data = await res.json();
      } else {
        data = { status: res.status, message: await res.text() };
      }
      if (data && data.converted_json) {
        txtJson.value = JSON.stringify(data.converted_json, null, 2);
      }
      const msgs = [];
      if (data && data.steps) {
        data.steps.forEach(s => msgs.push(`[${s.status}] ${s.message}`));
      }
      if (data && data.message) {
        msgs.push(data.message);
      }
      modalMessage.textContent = msgs.join('\n') || 'Selesai.';
      $('#modalInfo').modal('show');
    } catch (err) {
      modalMessage.textContent = 'Terjadi error saat memproses: ' + (err && err.message ? err.message : String(err));
      $('#modalInfo').modal('show');
    } finally {
      loading.classList.add('d-none');
      btnProcess.disabled = false;
      frmUpload.reset();
    }
  });
})();
</script>
@endpush
