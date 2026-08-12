@extends('layouts.app')

@section('title', 'Export Data to CSV')
@section('page_title', 'Export Data to CSV')
@section('breadcrumb', 'Export Data CSV')

@push('styles')
<style>
  .loading-overlay {
    position: fixed; top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex; align-items: center; justify-content: center; z-index: 9999;
  }
  .loading-content {
    background: white; padding: 2rem; border-radius: 8px; text-align: center;
  }
</style>
@endpush

@section('content')
<div class="card card-outline card-primary">
  <div class="card-body">
    <form id="frmExport">
      <div class="row align-items-end">
        <div class="col-md-10">
          <div class="form-group mb-md-0">
            <label for="selectDataType">Tipe Data <span class="text-danger">*</span></label>
            <select id="selectDataType" class="form-control" required>
              <option value="">-- Pilih Tipe Data --</option>
              <option value="dataproduct">Data Product</option>
              <option value="datavehicle">Data Vehicle</option>
              <option value="lovconfig">Lov Config</option>
              <option value="masterlocation">Master Location</option>
              <option value="childlocation">Child Location</option>
            </select>
            <small class="form-text text-muted">Target DB mengikuti environment aktif di kanan atas (session).</small>
          </div>
        </div>
        <div class="col-md-2">
          <button id="btnGenerate" type="submit" class="btn btn-primary btn-block">Generate</button>
        </div>
      </div>
    </form>
  </div>
</div>

<div id="resultSection" class="card d-none">
  <div class="card-body">
    <div id="resultMessage"></div>
    <div id="downloadSection" class="mt-3 d-none">
      <a id="downloadLink" href="#" class="btn btn-success" download>Download CSV</a>
      <span id="fileInfo" class="ml-3 text-muted"></span>
    </div>
  </div>
</div>

<div id="loadingOverlay" class="loading-overlay d-none">
  <div class="loading-content">
    <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
      <span class="sr-only">Loading...</span>
    </div>
    <div class="font-weight-bold">Sedang generate data...</div>
    <div class="text-muted mt-2">Mohon tunggu</div>
  </div>
</div>

<div class="modal fade" id="modalError" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Error</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <p id="errorMessage" class="mb-0"></p>
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
  const frmExport = document.getElementById('frmExport');
  const selectDataType = document.getElementById('selectDataType');
  const btnGenerate = document.getElementById('btnGenerate');
  const loadingOverlay = document.getElementById('loadingOverlay');
  const resultSection = document.getElementById('resultSection');
  const resultMessage = document.getElementById('resultMessage');
  const downloadSection = document.getElementById('downloadSection');
  const downloadLink = document.getElementById('downloadLink');
  const fileInfo = document.getElementById('fileInfo');
  const errorMessage = document.getElementById('errorMessage');

  function showError(message) {
    errorMessage.textContent = message;
    $('#modalError').modal('show');
  }

  function showResult(message, isSuccess, filename, rowCount) {
    resultSection.classList.remove('d-none');
    if (isSuccess) {
      resultMessage.className = 'alert alert-success';
      resultMessage.innerHTML = `<strong>Berhasil!</strong><br>${message}`;
      if (filename) {
        downloadSection.classList.remove('d-none');
        downloadLink.href = `/api/export-data-csv/download?filename=${encodeURIComponent(filename)}`;
        fileInfo.textContent = `File: ${filename} (${rowCount || 0} baris)`;
      }
    } else {
      resultMessage.className = 'alert alert-danger';
      resultMessage.innerHTML = `<strong>Error!</strong><br>${message}`;
      downloadSection.classList.add('d-none');
    }
  }

  async function generateCSV() {
    if (!window.requireArmosEnv()) return;
    const dataType = selectDataType.value.trim();
    if (!dataType) { showError('Tipe data wajib dipilih'); return; }

    loadingOverlay.classList.remove('d-none');
    btnGenerate.disabled = true;
    resultSection.classList.add('d-none');

    try {
      const res = await fetch('/api/export-data-csv/generate', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ data_type: dataType })
      });
      const json = await res.json();
      if (json.status === 200) {
        showResult(json.message || 'Data berhasil di-export', true, json.filename, json.row_count);
      } else {
        showResult(json.message || 'Error saat generate data', false);
        if (json.status !== 400 && json.status !== 404) {
          showError(json.message || 'Error saat generate data');
        }
      }
    } catch (error) {
      showResult('Error: ' + error.message, false);
      showError('Error: ' + error.message);
    } finally {
      loadingOverlay.classList.add('d-none');
      btnGenerate.disabled = false;
    }
  }

  frmExport.addEventListener('submit', async (e) => {
    e.preventDefault();
    await generateCSV();
  });
})();
</script>
@endpush
