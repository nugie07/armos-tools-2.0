@extends('layouts.app')

@section('title', 'Update Quantity Delivery / Unloading')
@section('page_title', 'Update Quantity Delivery / Unloading')
@section('breadcrumb', 'Update Qty Unloading')

@section('content')
@if (!empty($warehouseError))
  <div class="alert alert-warning">
    Gagal load warehouse dari TMS DB: <code>{{ $warehouseError }}</code>
    <br><small>Isi Env Configuration (DATABASE_MAIN_* atau PREPROD/PROD) lalu refresh. API tetap bisa dipanggil jika DB sudah tersedia di server.</small>
  </div>
@endif

<div class="card card-outline card-primary">
  <div class="card-body">
    <form id="frmFind">
      <div class="row align-items-end">
        <div class="col-md-4">
          <div class="form-group mb-md-0">
            <label for="warehouse">Warehouse</label>
            <select id="warehouse" class="form-control" required>
              <option value="">-- Pilih Warehouse --</option>
              @foreach ($warehouses as $wh)
                <option value="{{ $wh['id'] }}">{{ $wh['id'] }} - {{ $wh['name'] }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group mb-md-0">
            <label for="faktur">Nomor Faktur</label>
            <input id="faktur" type="text" class="form-control" placeholder="Contoh: G03SI2510-0036" required>
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group mb-md-0">
            <label for="sku">SKU</label>
            <input id="sku" type="text" class="form-control" placeholder="Contoh: 01010107110002" required>
          </div>
        </div>
        <div class="col-md-1">
          <button id="btnCari" type="submit" class="btn btn-primary btn-block">Cari</button>
        </div>
      </div>
    </form>
  </div>
</div>

<div id="resultCard" class="card d-none">
  <div class="card-body">
    <div class="row">
      <div class="col-md-6">
        <div class="form-group">
          <label for="detailId">Detail ID</label>
          <input id="detailId" type="text" class="form-control" readonly>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-group">
          <label for="skuItem">SKU ITEM</label>
          <input id="skuItem" type="text" class="form-control" readonly>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-group">
          <label for="qtyFaktur">Faktur Quantity</label>
          <input id="qtyFaktur" type="number" step="0.01" class="form-control" readonly>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-group">
          <label for="qtyUnloading">Jumlah Item yang Dikirim</label>
          <input id="qtyUnloading" type="number" step="0.01" class="form-control">
        </div>
      </div>
    </div>
    <div class="text-right">
      <button id="btnSave" type="button" class="btn btn-success">Simpan</button>
    </div>
  </div>
</div>

<div class="modal fade" id="modalInfo" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Informasi</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <pre id="modalMessage" class="mb-0" style="white-space: pre-wrap"></pre>
      </div>
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
      <div class="modal-body">
        <div id="confirmMessage" class="mb-0"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tidak</button>
        <button type="button" class="btn btn-primary" id="btnConfirm">Ya, Simpan</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
  const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  const frmFind = document.getElementById('frmFind');
  const warehouse = document.getElementById('warehouse');
  const faktur = document.getElementById('faktur');
  const sku = document.getElementById('sku');
  const resultCard = document.getElementById('resultCard');
  const detailId = document.getElementById('detailId');
  const skuItem = document.getElementById('skuItem');
  const qtyFaktur = document.getElementById('qtyFaktur');
  const qtyUnloading = document.getElementById('qtyUnloading');
  const btnSave = document.getElementById('btnSave');
  const modalMessage = document.getElementById('modalMessage');
  const confirmMessage = document.getElementById('confirmMessage');
  const btnConfirm = document.getElementById('btnConfirm');
  let pendingUpdate = null;

  frmFind.addEventListener('submit', async (e) => {
    e.preventDefault();
    const wid = warehouse.value.trim();
    const f = faktur.value.trim();
    const s = sku.value.trim();
    if (!wid || !f || !s) {
      modalMessage.textContent = 'Warehouse, Faktur, SKU wajib diisi';
      $('#modalInfo').modal('show');
      return;
    }
    try {
      const res = await fetch(`/api/qty-unloading/find?warehouse_id=${encodeURIComponent(wid)}&faktur_id=${encodeURIComponent(f)}&sku=${encodeURIComponent(s)}`, {
        headers: { 'Accept': 'application/json' }
      });
      const ct = res.headers.get('content-type') || '';
      if (!ct.includes('application/json')) {
        modalMessage.textContent = 'Respon bukan JSON';
        $('#modalInfo').modal('show');
        return;
      }
      const json = await res.json();
      if (json.status !== 200) {
        modalMessage.textContent = json.message || 'Data tidak ditemukan';
        $('#modalInfo').modal('show');
        resultCard.classList.add('d-none');
        return;
      }
      const d = json.data;
      detailId.value = d.order_detail_id;
      skuItem.value = d.sku;
      qtyFaktur.value = d.quantity_faktur ?? 0;
      qtyUnloading.value = d.quantity_unloading ?? 0;
      resultCard.classList.remove('d-none');
    } catch (err) {
      modalMessage.textContent = 'Gagal terhubung: ' + (err.message || err);
      $('#modalInfo').modal('show');
    }
  });

  btnSave.addEventListener('click', () => {
    const id = parseInt(detailId.value, 10);
    const q = parseFloat(qtyUnloading.value);
    if (!id || isNaN(q)) {
      modalMessage.textContent = 'Nilai tidak valid';
      $('#modalInfo').modal('show');
      return;
    }
    pendingUpdate = { id, q };
    confirmMessage.textContent = `Simpan perubahan jumlah kirim (quantity_unloading) menjadi ${q} untuk Detail ID ${id}?`;
    $('#modalConfirm').modal('show');
  });

  btnConfirm.addEventListener('click', async () => {
    if (!pendingUpdate) return;
    const { id, q } = pendingUpdate;
    btnConfirm.disabled = true;
    try {
      const res = await fetch('/api/qty-unloading/update', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ order_detail_id: id, quantity_unloading: q })
      });
      const ct = res.headers.get('content-type') || '';
      $('#modalConfirm').modal('hide');
      if (!ct.includes('application/json')) {
        modalMessage.textContent = 'Respon bukan JSON';
        $('#modalInfo').modal('show');
        return;
      }
      const json = await res.json();
      modalMessage.textContent = json.status === 200 ? 'Berhasil disimpan' : (json.message || 'Gagal menyimpan');
      $('#modalInfo').modal('show');
    } catch (err) {
      $('#modalConfirm').modal('hide');
      modalMessage.textContent = 'Terjadi error saat menyimpan: ' + (err && err.message ? err.message : String(err));
      $('#modalInfo').modal('show');
    } finally {
      btnConfirm.disabled = false;
      pendingUpdate = null;
    }
  });
})();
</script>
@endpush
