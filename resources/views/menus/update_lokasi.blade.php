@extends('layouts.app')

@section('title', 'Update Lokasi Customer')
@section('page_title', 'Update Lokasi Customer')
@section('breadcrumb', 'Update Lokasi Customer')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-theme@0.1.0-beta.10/dist/select2-bootstrap.min.css">
<style>
  .select2-container { width: 100% !important; }
</style>
@endpush

@section('content')
@if ($warehouseError)
  <div class="alert alert-warning">
    Gagal load warehouse dari TMS DB: <code>{{ $warehouseError }}</code>
    <br><small>Isi Env Configuration (DATABASE_MAIN_* atau PREPROD/PROD) lalu refresh. API tetap bisa dipanggil jika DB sudah tersedia di server.</small>
  </div>
@endif

<div class="card card-outline card-primary">
  <div class="card-body">
    <div class="row align-items-end">
      <div class="col-md-4">
        <div class="form-group mb-md-0">
          <label for="warehouse">Warehouse</label>
          <select id="warehouse" class="form-control">
            <option value="">-- Pilih Warehouse --</option>
            @foreach ($warehouses as $wh)
              <option value="{{ $wh['id'] }}">{{ $wh['id'] }} - {{ $wh['name'] }}</option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="col-md-5">
        <div class="form-group mb-md-0">
          <label for="faktur">Nomor Faktur</label>
          <input id="faktur" type="text" class="form-control" placeholder="Contoh: G04SI2505-0180">
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-group mb-md-0 d-flex align-items-center">
          <button id="btnCari" type="button" class="btn btn-primary flex-grow-1">Cari</button>
          <div id="loading" class="spinner-border spinner-border-sm text-primary ml-2 d-none" role="status"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-body table-responsive p-0">
    <table class="table table-bordered table-striped mb-0" id="tblOrders">
      <thead>
        <tr>
          <th>faktur_date</th>
          <th>faktur_id</th>
          <th>order_id</th>
          <th>warehouse_id</th>
          <th>mst_location_child_id</th>
          <th>code</th>
          <th>name</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
  <div class="card-footer">
    <button id="btnUbahLokasi" type="button" class="btn btn-warning" disabled>Ubah Lokasi</button>
  </div>
</div>

<div class="modal fade" id="modalLokasi" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Pilih Lokasi Baru</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="form-group mb-0">
          <label for="ddlLokasi">Lokasi</label>
          <select id="ddlLokasi" class="form-control" style="width:100%"></select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
        <button id="btnSaveLokasi" type="button" class="btn btn-primary">Simpan</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
(function () {
  const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  const tblBody = document.querySelector('#tblOrders tbody');
  const btnCari = document.getElementById('btnCari');
  const btnUbah = document.getElementById('btnUbahLokasi');
  const faktur = document.getElementById('faktur');
  const warehouse = document.getElementById('warehouse');
  const loading = document.getElementById('loading');
  const ddlLokasi = document.getElementById('ddlLokasi');
  let currentFaktur = '';

  function renderRows(rows) {
    tblBody.innerHTML = '';
    (rows || []).forEach(r => {
      const tr = document.createElement('tr');
      ['faktur_date','faktur_id','order_id','warehouse_id','mst_location_child_id','code','name'].forEach(k => {
        const td = document.createElement('td');
        td.textContent = r[k] ?? '';
        tr.appendChild(td);
      });
      tblBody.appendChild(tr);
    });
    btnUbah.disabled = !rows || rows.length === 0;
  }

  btnCari.addEventListener('click', async () => {
    const w = warehouse.value.trim();
    const f = faktur.value.trim();
    if (!w || !f) {
      alert('Pilih warehouse dan isi nomor faktur');
      return;
    }
    currentFaktur = f;
    loading.classList.remove('d-none');
    try {
      const res = await fetch(`/api/orders?warehouse_id=${encodeURIComponent(w)}&faktur_id=${encodeURIComponent(f)}`, {
        headers: { 'Accept': 'application/json' }
      });
      const json = await res.json();
      if (json.status !== 200) {
        alert(json.message || 'Gagal mencari order');
        renderRows([]);
        return;
      }
      renderRows(json.data || []);
    } catch (e) {
      alert('Gagal terhubung: ' + (e.message || e));
    } finally {
      loading.classList.add('d-none');
    }
  });

  function renderLocationOptions(list) {
    ddlLokasi.innerHTML = '';
    (list || []).forEach(x => {
      const opt = document.createElement('option');
      opt.value = x.mst_location_child_id;
      opt.textContent = `${x.mst_location_child_id} - ${x.code} - ${x.name}`;
      ddlLokasi.appendChild(opt);
    });
  }

  btnUbah.addEventListener('click', async () => {
    try {
      const res = await fetch('/api/locations', { headers: { 'Accept': 'application/json' } });
      const json = await res.json();
      if (json.status !== 200) {
        alert(json.message || 'Gagal load lokasi');
        return;
      }
      renderLocationOptions(json.data || []);
      const $ddl = $('#ddlLokasi');
      if ($ddl.hasClass('select2-hidden-accessible')) {
        $ddl.select2('destroy');
      }
      $ddl.select2({
        dropdownParent: $('#modalLokasi'),
        width: '100%',
        theme: 'bootstrap',
        placeholder: 'Pilih lokasi',
        allowClear: true
      });
      $('#modalLokasi').modal('show');
    } catch (e) {
      alert('Gagal terhubung: ' + (e.message || e));
    }
  });

  document.getElementById('btnSaveLokasi').addEventListener('click', async () => {
    const val = parseInt(ddlLokasi.value, 10);
    if (!currentFaktur || Number.isNaN(val)) {
      alert('Data tidak valid');
      return;
    }
    try {
      const res = await fetch('/api/orders/update-location', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ faktur_id: currentFaktur, customer_id: val })
      });
      const json = await res.json();
      if (json.status === 200) {
        alert('Lokasi berhasil diubah');
        $('#modalLokasi').modal('hide');
        btnCari.click();
      } else {
        alert(json.message || 'Gagal mengubah lokasi');
      }
    } catch (e) {
      alert('Gagal terhubung: ' + (e.message || e));
    }
  });
})();
</script>
@endpush
