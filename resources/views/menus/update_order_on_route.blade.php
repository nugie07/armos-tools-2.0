@extends('layouts.app')

@section('title', 'Update Order on Route')
@section('page_title', 'Update Order on Route')
@section('breadcrumb', 'Update Order on Route')

@push('styles')
<style>
  .table-responsive { max-height: 600px; overflow-y: auto; }
  .loading-overlay {
    position: absolute; top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(255, 255, 255, 0.8);
    display: flex; align-items: center; justify-content: center; z-index: 1000;
  }
  .table-container { position: relative; }
</style>
@endpush

@section('content')
<div class="card card-outline card-primary">
  <div class="card-body">
    <form id="frmSearch">
      <div class="row align-items-end">
        <div class="col-md-10">
          <div class="form-group mb-md-0">
            <label for="manifestReference">Manifest Reference <span class="text-danger">*</span></label>
            <input id="manifestReference" type="text" class="form-control" placeholder="Masukan Manifest Reference" required>
            <small class="form-text text-muted">Target DB mengikuti environment aktif di kanan atas (session).</small>
          </div>
        </div>
        <div class="col-md-2">
          <button id="btnSearch" type="submit" class="btn btn-primary btn-block">Cari</button>
        </div>
      </div>
    </form>
  </div>
</div>

<div id="loadingSearch" class="d-none mb-3">
  <div class="d-flex align-items-center">
    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
    <span class="text-muted ml-2">Mencari data...</span>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h3 class="card-title mb-0">Data Order (Manifest)</h3>
    <small class="text-muted d-block mt-1" id="dataHint">Data order dalam manifest. Isi form di bawah untuk mengubah status atau integration id manifest ini.</small>
  </div>
  <div class="card-body table-container p-0">
    <div id="loadingTable" class="loading-overlay d-none">
      <div class="spinner-border text-primary" role="status"></div>
    </div>
    <div class="table-responsive">
      <table class="table table-bordered table-striped table-hover mb-0" id="tblData">
        <thead class="thead-dark">
          <tr id="tableHeader"></tr>
        </thead>
        <tbody id="tableBody">
          <tr><td colspan="100%" class="text-center text-muted">Masukan Manifest Reference dan klik Cari</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="card" id="formCard" style="display: none;">
  <div class="card-header bg-primary">
    <h3 class="card-title mb-0">Ubah Data Manifest</h3>
    <small class="d-block mt-1">Isi perubahan untuk manifest reference yang dicari di atas</small>
  </div>
  <div class="card-body">
    <form id="frmUpdate">
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label for="selectStatus">Ubah Status Manifest</label>
            <select id="selectStatus" class="form-control">
              <option value="">-- Tidak Diubah --</option>
              <option value="new">new</option>
              <option value="loading">loading</option>
              <option value="ready_to_deliver">ready_to_deliver</option>
              <option value="in_delivery">in_delivery</option>
              <option value="delivery_success">delivery_success</option>
              <option value="delivery_completed">delivery_completed</option>
              <option value="rejected">rejected</option>
            </select>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label for="inputIntegrationId">Ubah Integration Id</label>
            <input id="inputIntegrationId" type="text" class="form-control" placeholder="Kosongkan jika tidak diubah">
          </div>
        </div>
      </div>
      <div class="text-right">
        <button id="btnSave" type="submit" class="btn btn-success">Simpan Perubahan</button>
      </div>
      <small class="text-muted d-block mt-2">* Hanya field yang diisi/dipilih yang akan diubah</small>
    </form>
  </div>
</div>

<div class="modal fade" id="modalError" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Error</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body"><p id="errorMessage" class="mb-0"></p></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalConfirm" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Konfirmasi</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body"><p id="confirmMessage" class="mb-0" style="white-space: pre-wrap"></p></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="btnConfirmSave">Ya, Simpan</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalSuccess" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Berhasil</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body"><p id="successMessage" class="mb-0"></p></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
  const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  const frmSearch = document.getElementById('frmSearch');
  const manifestReference = document.getElementById('manifestReference');
  const btnSearch = document.getElementById('btnSearch');
  const loadingSearch = document.getElementById('loadingSearch');
  const loadingTable = document.getElementById('loadingTable');
  const tableHeader = document.getElementById('tableHeader');
  const tableBody = document.getElementById('tableBody');
  const formCard = document.getElementById('formCard');
  const frmUpdate = document.getElementById('frmUpdate');
  const selectStatus = document.getElementById('selectStatus');
  const inputIntegrationId = document.getElementById('inputIntegrationId');
  const btnSave = document.getElementById('btnSave');
  const errorMessage = document.getElementById('errorMessage');
  const confirmMessage = document.getElementById('confirmMessage');
  const successMessage = document.getElementById('successMessage');
  const btnConfirmSave = document.getElementById('btnConfirmSave');

  const COLUMN_ORDER = ['order_id', 'faktur_id', 'order_status', 'route_status', 'manifest_reference', 'manifest_integration_id'];
  let dataList = [];

  function showError(msg) {
    errorMessage.textContent = msg;
    $('#modalError').modal('show');
  }
  function showSuccess(msg) {
    successMessage.textContent = msg;
    $('#modalSuccess').modal('show');
  }

  function resetForm() {
    manifestReference.value = '';
    selectStatus.value = '';
    inputIntegrationId.value = '';
    formCard.style.display = 'none';
    tableHeader.innerHTML = '';
    tableBody.innerHTML = '<tr><td colspan="100%" class="text-center text-muted">Masukan Manifest Reference dan klik Cari</td></tr>';
    dataList = [];
  }

  function renderTable() {
    tableHeader.innerHTML = '';
    COLUMN_ORDER.forEach(col => {
      const th = document.createElement('th');
      th.textContent = col;
      th.style.whiteSpace = 'nowrap';
      tableHeader.appendChild(th);
    });
    tableBody.innerHTML = '';
    if (!dataList.length) return;
    dataList.forEach(row => {
      const tr = document.createElement('tr');
      COLUMN_ORDER.forEach(col => {
        const td = document.createElement('td');
        const v = row[col];
        td.textContent = v != null && v !== undefined ? String(v) : '';
        td.style.whiteSpace = 'nowrap';
        tr.appendChild(td);
      });
      tableBody.appendChild(tr);
    });
  }

  async function loadData() {
    if (!window.requireArmosEnv()) return;
    const ref = manifestReference.value.trim();
    if (!ref) { showError('Manifest Reference wajib diisi'); return; }
    loadingSearch.classList.remove('d-none');
    btnSearch.disabled = true;
    loadingTable.classList.remove('d-none');
    formCard.style.display = 'none';
    dataList = [];
    try {
      const res = await fetch(`/api/update-order-on-route/search?manifest_reference=${encodeURIComponent(ref)}`, {
        headers: { 'Accept': 'application/json' }
      });
      const json = await res.json();
      if (json.status === 200) {
        dataList = Array.isArray(json.data) ? json.data : [];
        renderTable();
        const hint = document.getElementById('dataHint');
        if (hint) hint.textContent = 'Menampilkan ' + dataList.length + ' data. Isi form di bawah untuk mengubah manifest ini.';
        formCard.style.display = 'block';
        selectStatus.value = '';
        inputIntegrationId.value = '';
      } else if (json.status === 404) {
        showError("Manifest Reference '" + ref + "' tidak ditemukan");
        tableHeader.innerHTML = '';
        tableBody.innerHTML = '<tr><td colspan="100%" class="text-center text-muted">Data tidak ditemukan</td></tr>';
      } else {
        showError(json.message || 'Error saat mengambil data');
      }
    } catch (e) {
      showError('Error: ' + e.message);
    } finally {
      loadingSearch.classList.add('d-none');
      btnSearch.disabled = false;
      loadingTable.classList.add('d-none');
    }
  }

  async function saveUpdate() {
    if (!window.requireArmosEnv()) return;
    const ref = manifestReference.value.trim();
    const status = selectStatus.value.trim();
    const integrationId = inputIntegrationId.value.trim();
    if (!ref) { showError('Manifest Reference wajib diisi'); return; }
    if (!status && !integrationId) {
      showError('Minimal satu field (Status Manifest atau Integration Id) harus diisi');
      return;
    }
    btnSave.disabled = true;
    try {
      const payload = { manifest_reference: ref };
      if (status) payload.status = status;
      if (integrationId) payload.manifest_integration_id = integrationId;
      const res = await fetch('/api/update-order-on-route/update', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(payload)
      });
      const json = await res.json();
      if (json.status === 200) {
        showSuccess(json.message || 'Data berhasil diubah');
        resetForm();
      } else {
        showError(json.message || 'Gagal mengubah data');
      }
    } catch (e) {
      showError('Error: ' + e.message);
    } finally {
      btnSave.disabled = false;
    }
  }

  frmSearch.addEventListener('submit', function (e) {
    e.preventDefault();
    loadData();
  });
  frmUpdate.addEventListener('submit', function (e) {
    e.preventDefault();
    if (!window.requireArmosEnv()) return;
    const envLabel = (window.ARMOS_ENV || '').toUpperCase();
    const ref = manifestReference.value.trim();
    const status = selectStatus.value.trim();
    const integrationId = inputIntegrationId.value.trim();
    if (!ref) { showError('Manifest Reference wajib diisi'); return; }
    if (!status && !integrationId) { showError('Minimal satu field harus diisi'); return; }
    let msg = 'Apakah Anda yakin ingin mengubah data manifest "' + ref + '" di environment ' + envLabel + '?';
    if (status) msg += '\nStatus: ' + status;
    if (integrationId) msg += '\nIntegration Id: ' + integrationId;
    confirmMessage.textContent = msg;
    $('#modalConfirm').modal('show');
  });
  btnConfirmSave.addEventListener('click', function () {
    $('#modalConfirm').modal('hide');
    saveUpdate();
  });
  manifestReference.addEventListener('keypress', function (e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      loadData();
    }
  });
})();
</script>
@endpush
