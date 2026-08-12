@extends('layouts.app')

@section('title', 'Uncheck Document Reconciliation')
@section('page_title', 'Uncheck Document Reconciliation')
@section('breadcrumb', 'Uncheck Reconciliation')

@section('content')
<div class="card card-outline card-primary">
  <div class="card-body">
    <div class="row align-items-end">
      <div class="col-md-8">
        <div class="form-group mb-md-0">
          <label for="faktur">Nomor Faktur</label>
          <input id="faktur" type="text" class="form-control" placeholder="Contoh: G10SI2412-1020">
        </div>
      </div>
      <div class="col-md-4">
        <button id="btnCari" type="button" class="btn btn-primary btn-block">Cari</button>
      </div>
    </div>
  </div>
</div>

<div class="card d-none" id="resultWrap">
  <div class="card-body table-responsive p-0">
    <table class="table table-bordered table-striped mb-0" id="tblRecon">
      <thead>
        <tr>
          <th>order_id</th>
          <th>faktur_id</th>
          <th>created_date</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
  <div class="card-footer">
    <button id="btnUncheck" type="button" class="btn btn-danger">Uncheck</button>
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
        <p id="modalMessage" class="mb-0"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
        <button type="button" class="btn btn-primary d-none" id="modalYes">Ya</button>
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
  const resultWrap = document.getElementById('resultWrap');
  const tblBody = document.querySelector('#tblRecon tbody');
  const btnUncheck = document.getElementById('btnUncheck');
  const modalMsg = document.getElementById('modalMessage');
  const modalYes = document.getElementById('modalYes');
  let current = null;

  function showInfo(message, showYes) {
    modalMsg.textContent = message;
    if (showYes) modalYes.classList.remove('d-none');
    else modalYes.classList.add('d-none');
    $('#modalInfo').modal('show');
  }

  btnCari.addEventListener('click', async () => {
    const f = faktur.value.trim();
    if (!f) {
      alert('Nomor faktur wajib diisi');
      return;
    }
    try {
      const res = await fetch(`/api/reconciliation?faktur_id=${encodeURIComponent(f)}`, {
        headers: { 'Accept': 'application/json' }
      });
      if (res.status === 404) {
        showInfo('Data tidak ditemukan', false);
        resultWrap.classList.add('d-none');
        return;
      }
      const json = await res.json();
      if (json.status !== 200) {
        showInfo(json.message || 'Gagal mencari data', false);
        resultWrap.classList.add('d-none');
        return;
      }
      const rows = json.data || [];
      tblBody.innerHTML = '';
      current = null;
      rows.forEach(r => {
        const orderId = r.order_id ?? r.ORDER_ID ?? null;
        const fakturId = r.faktur_id ?? r.FAKTUR_ID ?? f;
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${orderId ?? ''}</td><td>${fakturId ?? ''}</td><td>${r.created_date ?? ''}</td>`;
        tblBody.appendChild(tr);
        current = { order_id: orderId, faktur_id: fakturId };
      });
      resultWrap.classList.remove('d-none');
    } catch (e) {
      alert('Gagal terhubung: ' + (e.message || e));
    }
  });

  btnUncheck.addEventListener('click', () => {
    if (!current || !current.order_id) {
      showInfo('Data tidak valid untuk dihapus', false);
      return;
    }
    showInfo('Apakah anda yakin akan uncheck document reconciliation ini?', true);
  });

  modalYes.addEventListener('click', async () => {
    if (!current) return;
    try {
      const res = await fetch('/api/reconciliation/uncheck', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ order_id: Number(current.order_id) })
      });
      const json = await res.json();
      $('#modalInfo').modal('hide');
      if (json.status === 200) {
        alert('Berhasil dihapus');
        resultWrap.classList.add('d-none');
        current = null;
      } else {
        alert(json.message || 'Gagal menghapus');
      }
    } catch (e) {
      alert('Gagal terhubung: ' + (e.message || e));
    }
  });
})();
</script>
@endpush
