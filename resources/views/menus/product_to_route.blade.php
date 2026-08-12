@extends('layouts.app')

@section('title', 'PRODUCT to ROUTE')
@section('page_title', 'PRODUCT to ROUTE')
@section('breadcrumb', 'PRODUCT to ROUTE')

@section('content')
<div class="card card-outline card-primary">
  <div class="card-body">
    <div class="row align-items-end">
      <div class="col-md-4">
        <div class="form-group mb-md-0">
          <label for="sku">SKU</label>
          <input id="sku" type="text" class="form-control" placeholder="Contoh: 80478905080053">
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-group mb-md-0">
          <label for="startDate">Start Date</label>
          <input id="startDate" type="date" class="form-control">
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-group mb-md-0">
          <label for="endDate">End Date</label>
          <input id="endDate" type="date" class="form-control">
        </div>
      </div>
      <div class="col-md-2">
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
          <th>route_id</th>
          <th>manifest_reference</th>
          <th>route_status</th>
          <th>faktur_id</th>
          <th>order_status</th>
          <th>quantity_faktur</th>
          <th>faktur_date</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
  const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  const sku = document.getElementById('sku');
  const startDate = document.getElementById('startDate');
  const endDate = document.getElementById('endDate');
  const btnCari = document.getElementById('btnCari');
  const loading = document.getElementById('loading');
  const tblBody = document.querySelector('#tblResult tbody');

  function renderRows(rows) {
    tblBody.innerHTML = '';
    (rows || []).forEach(r => {
      const tr = document.createElement('tr');
      tr.innerHTML = `<td>${r.route_id ?? ''}</td><td>${r.manifest_reference ?? ''}</td><td>${r.route_status ?? ''}</td><td>${r.faktur_id ?? ''}</td><td>${r.order_status ?? ''}</td><td>${r.quantity_faktur ?? ''}</td><td>${r.faktur_date ?? ''}</td>`;
      tblBody.appendChild(tr);
    });
  }

  btnCari.addEventListener('click', async () => {
    const s = sku.value.trim();
    const sd = startDate.value;
    const ed = endDate.value;
    if (!s || !sd || !ed) {
      alert('Lengkapi SKU dan rentang tanggal');
      return;
    }
    loading.classList.remove('d-none');
    try {
      const res = await fetch(`/api/product-to-route?sku=${encodeURIComponent(s)}&start_date=${encodeURIComponent(sd)}&end_date=${encodeURIComponent(ed)}`, {
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
})();
</script>
@endpush
