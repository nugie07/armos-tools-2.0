@extends('layouts.app')

@section('title', 'Hapus Driver Cost')
@section('page_title', 'Hapus Driver Cost')
@section('breadcrumb', 'Hapus Driver Cost')

@section('content')
<div class="card card-outline card-primary">
  <div class="card-body">
    <form id="frmCari">
      <div class="row align-items-end">
        <div class="col-md-10">
          <div class="form-group mb-md-0">
            <label for="manifest">Manifest Reference</label>
            <input id="manifest" type="text" class="form-control" placeholder="Contoh: RMR-INT-20241218#966314661" required>
          </div>
        </div>
        <div class="col-md-2">
          <button id="btnCari" type="submit" class="btn btn-primary btn-block">Cari</button>
        </div>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex align-items-center justify-content-between">
    <h3 class="card-title mb-0">Daftar Driver Cost</h3>
    <div>
      <button id="btnPrev" type="button" class="btn btn-sm btn-outline-secondary">Prev</button>
      <span id="pageInfo" class="text-muted small mx-2"></span>
      <button id="btnNext" type="button" class="btn btn-sm btn-outline-secondary">Next</button>
    </div>
  </div>
  <div class="card-body table-responsive p-0">
    <table class="table table-bordered table-striped table-hover mb-0" id="tbl">
      <thead>
        <tr>
          <th>order_cost_id</th>
          <th>manifest_reference</th>
          <th>nominal</th>
          <th>driver_name</th>
          <th>receipt_picture</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<div id="detailCard" class="card d-none">
  <div class="card-body">
    <div class="row">
      <div class="col-md-6">
        <div class="form-group">
          <label for="ocId">order_cost_id</label>
          <input id="ocId" type="text" class="form-control" readonly>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-group">
          <label for="manifestRef">manifest_reference</label>
          <input id="manifestRef" type="text" class="form-control" readonly>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-group">
          <label for="nominal">nominal</label>
          <input id="nominal" type="text" class="form-control" readonly>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-group">
          <label for="driverName">driver_name</label>
          <input id="driverName" type="text" class="form-control" readonly>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-group mb-0">
          <label>receipt_picture</label>
          <div class="d-flex align-items-center">
            <img id="receiptImg" src="" alt="receipt" class="img-thumbnail" style="max-height: 200px">
            <button id="btnPreview" type="button" class="btn btn-outline-secondary ml-2" title="Lihat Gambar">Preview</button>
          </div>
        </div>
      </div>
    </div>
    <div class="text-right mt-3">
      <button id="btnDelete" type="button" class="btn btn-danger">Delete</button>
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
        <button type="button" class="btn btn-danger" id="btnConfirm">Ya, Hapus</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalImage" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Preview Gambar</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body text-center">
        <img id="imgFull" src="" alt="receipt-full" style="max-width: 100%; height: auto;">
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
  const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  const frmCari = document.getElementById('frmCari');
  const manifest = document.getElementById('manifest');
  const tblBody = document.querySelector('#tbl tbody');
  const detailCard = document.getElementById('detailCard');
  const ocId = document.getElementById('ocId');
  const manifestRef = document.getElementById('manifestRef');
  const nominal = document.getElementById('nominal');
  const driverName = document.getElementById('driverName');
  const receiptImg = document.getElementById('receiptImg');
  const btnPreview = document.getElementById('btnPreview');
  const modalMessage = document.getElementById('modalMessage');
  const confirmMessage = document.getElementById('confirmMessage');
  const btnConfirm = document.getElementById('btnConfirm');
  const btnDelete = document.getElementById('btnDelete');
  const btnPrev = document.getElementById('btnPrev');
  const btnNext = document.getElementById('btnNext');
  const pageInfo = document.getElementById('pageInfo');
  const imgFull = document.getElementById('imgFull');

  let current = null;
  let currentPage = 1;
  let totalPages = 1;

  async function loadList() {
    const mr = manifest.value.trim();
    if (!mr) {
      modalMessage.textContent = 'Manifest Reference wajib diisi';
      $('#modalInfo').modal('show');
      return;
    }
    try {
      const res = await fetch(`/api/driver-cost/list?manifest_reference=${encodeURIComponent(mr)}&page=${currentPage}`, {
        headers: { 'Accept': 'application/json' }
      });
      const ct = res.headers.get('content-type') || '';
      if (!ct.includes('application/json')) {
        modalMessage.textContent = 'Respon bukan JSON';
        $('#modalInfo').modal('show');
        return;
      }
      const json = await res.json();
      const rows = json.data || [];
      totalPages = json.pages || 1;
      currentPage = json.page || 1;
      pageInfo.textContent = `Page ${currentPage} / ${totalPages}`;
      tblBody.innerHTML = '';
      rows.forEach(r => {
        const tr = document.createElement('tr');
        tr.style.cursor = 'pointer';
        tr.innerHTML = `<td>${r.order_cost_id}</td><td>${r.manifest_reference ?? ''}</td><td>${r.nominal ?? ''}</td><td>${r.driver_name ?? ''}</td><td>${r.receipt_picture ?? ''}</td>`;
        tr.addEventListener('click', () => {
          current = r;
          ocId.value = r.order_cost_id;
          manifestRef.value = r.manifest_reference ?? '';
          nominal.value = r.nominal ?? '';
          driverName.value = r.driver_name ?? '';
          receiptImg.src = r.receipt_picture || '';
          detailCard.classList.remove('d-none');
        });
        tblBody.appendChild(tr);
      });
      if (rows.length === 0) detailCard.classList.add('d-none');
    } catch (err) {
      modalMessage.textContent = 'Gagal terhubung: ' + (err.message || err);
      $('#modalInfo').modal('show');
    }
  }

  frmCari.addEventListener('submit', async (e) => {
    e.preventDefault();
    currentPage = 1;
    await loadList();
  });

  btnDelete.addEventListener('click', () => {
    if (!current) {
      modalMessage.textContent = 'Pilih data terlebih dahulu';
      $('#modalInfo').modal('show');
      return;
    }
    confirmMessage.textContent = `Hapus driver cost dengan order_cost_id ${current.order_cost_id}?`;
    $('#modalConfirm').modal('show');
  });

  btnConfirm.addEventListener('click', async () => {
    if (!current) return;
    btnConfirm.disabled = true;
    try {
      const res = await fetch('/api/driver-cost/delete', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ order_cost_id: current.order_cost_id })
      });
      const ct = res.headers.get('content-type') || '';
      $('#modalConfirm').modal('hide');
      if (!ct.includes('application/json')) {
        modalMessage.textContent = 'Respon bukan JSON';
        $('#modalInfo').modal('show');
        return;
      }
      const json = await res.json();
      modalMessage.textContent = json.status === 200 ? 'Berhasil dihapus' : (json.message || 'Gagal menghapus');
      $('#modalInfo').modal('show');
      await loadList();
      detailCard.classList.add('d-none');
      current = null;
    } catch (err) {
      $('#modalConfirm').modal('hide');
      modalMessage.textContent = 'Terjadi error saat menghapus: ' + (err && err.message ? err.message : String(err));
      $('#modalInfo').modal('show');
    } finally {
      btnConfirm.disabled = false;
    }
  });

  btnPrev.addEventListener('click', async () => {
    if (currentPage > 1) {
      currentPage -= 1;
      await loadList();
    }
  });
  btnNext.addEventListener('click', async () => {
    if (currentPage < totalPages) {
      currentPage += 1;
      await loadList();
    }
  });

  btnPreview.addEventListener('click', () => {
    if (!receiptImg.src) {
      modalMessage.textContent = 'Tidak ada gambar untuk ditampilkan';
      $('#modalInfo').modal('show');
      return;
    }
    imgFull.src = receiptImg.src;
    $('#modalImage').modal('show');
  });
})();
</script>
@endpush
