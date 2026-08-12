@extends('layouts.app')

@section('title', 'Update WMS Integrasi')
@section('page_title', 'Update WMS Integrasi')
@section('breadcrumb', 'WMS Integrasi')

@section('content')
<div class="card card-outline card-primary">
  <div class="card-body">
    <div class="row align-items-end">
      <div class="col-md-8">
        <div class="form-group mb-md-0">
          <label for="faktur">Nomor Faktur</label>
          <input id="faktur" type="text" class="form-control" placeholder="Contoh: DO/FAKTUR">
        </div>
      </div>
      <div class="col-md-4">
        <div class="form-group mb-md-0 d-flex align-items-center">
          <button id="btnCari" type="button" class="btn btn-primary flex-grow-1">Cari</button>
          <div id="loading" class="spinner-border spinner-border-sm text-primary ml-2 d-none" role="status">
            <span class="sr-only">Loading...</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-body table-responsive p-0">
    <table class="table table-bordered table-striped mb-0" id="tblResult">
      <thead>
        <tr>
          <th>order_id</th>
          <th>faktur_id</th>
          <th>faktur_date</th>
          <th>status</th>
          <th>order_integration_id</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
  <div class="card-footer text-muted small">Double-click baris untuk update order_integration_id</div>
</div>

<div class="modal fade" id="modalUpdate" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Update order_integration_id</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="form-group mb-0">
          <label for="txtIntegration">order_integration_id</label>
          <input id="txtIntegration" type="text" class="form-control">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="btnSave">Simpan</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
  const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  const faktur = document.getElementById('faktur');
  const btnCari = document.getElementById('btnCari');
  const loading = document.getElementById('loading');
  const tblBody = document.querySelector('#tblResult tbody');
  const txtIntegration = document.getElementById('txtIntegration');
  const btnSave = document.getElementById('btnSave');
  let current = null;

  function renderRows(rows) {
    tblBody.innerHTML = '';
    (rows || []).forEach(r => {
      const tr = document.createElement('tr');
      tr.style.cursor = 'pointer';
      tr.innerHTML = `<td>${r.order_id ?? ''}</td><td>${r.faktur_id ?? ''}</td><td>${r.faktur_date ?? ''}</td><td>${r.status ?? ''}</td><td>${r.order_integration_id ?? ''}</td>`;
      tr.addEventListener('dblclick', () => {
        current = r;
        txtIntegration.value = r.order_integration_id ?? '';
        $('#modalUpdate').modal('show');
      });
      tblBody.appendChild(tr);
    });
  }

  btnCari.addEventListener('click', async () => {
    const f = faktur.value.trim();
    if (!f) {
      alert('Isi faktur');
      return;
    }
    loading.classList.remove('d-none');
    try {
      const res = await fetch(`/api/wms-integration?faktur_id=${encodeURIComponent(f)}`, {
        headers: { 'Accept': 'application/json' }
      });
      const json = await res.json();
      renderRows(json.data || []);
    } catch (e) {
      alert('Gagal terhubung: ' + (e.message || e));
    } finally {
      loading.classList.add('d-none');
    }
  });

  btnSave.addEventListener('click', async () => {
    if (!current) return;
    const newVal = txtIntegration.value.trim();
    if (!newVal) {
      alert('Isi nilai baru');
      return;
    }
    try {
      const res = await fetch('/api/wms-integration/update', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ order_id: current.order_id, order_integration_id: newVal })
      });
      const json = await res.json();
      $('#modalUpdate').modal('hide');
      if (json.status === 200) {
        alert('Berhasil update');
        btnCari.click();
      } else {
        alert(json.message || 'Gagal update');
      }
    } catch (e) {
      alert('Gagal terhubung: ' + (e.message || e));
    }
  });
})();
</script>
@endpush
