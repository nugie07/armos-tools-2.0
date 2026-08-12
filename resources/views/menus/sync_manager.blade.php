@extends('layouts.app')

@section('title', 'Sync Manager')
@section('page_title', 'Sync Manager')
@section('breadcrumb', 'Sync Manager')

@section('content')
<div class="row mb-3">
  <div class="col-md-3">
    <div class="small-box bg-info">
      <div class="inner">
        <h3 id="metricTotal">-</h3>
        <p>Total Sync Records</p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="small-box bg-success">
      <div class="inner">
        <h3 id="metricOk">-</h3>
        <p>Successful Syncs</p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="small-box bg-danger">
      <div class="inner">
        <h3 id="metricFail">-</h3>
        <p>Failed Syncs</p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="info-box mb-3">
      <span class="info-box-icon bg-secondary"><i class="fas fa-clock"></i></span>
      <div class="info-box-content">
        <span class="info-box-text">Last Sync</span>
        <span class="info-box-number" id="metricLast" style="font-size: 1rem;">-</span>
      </div>
    </div>
  </div>
</div>

<div class="card card-outline card-primary">
  <div class="card-body">
    <form id="frmSync">
      <div class="row align-items-end">
        <div class="col-md-4">
          <div class="form-group mb-md-0">
            <label for="syncType">Sync Type</label>
            <select id="syncType" class="form-control">
              <option value="fact_order">fact_order</option>
              <option value="fact_delivery">fact_delivery</option>
              <option value="both">both</option>
            </select>
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group mb-md-0">
            <label for="dateFrom">Date From</label>
            <input id="dateFrom" type="date" class="form-control">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group mb-md-0">
            <label for="dateTo">Date To</label>
            <input id="dateTo" type="date" class="form-control">
          </div>
        </div>
        <div class="col-md-2">
          <div class="form-group mb-md-0 d-flex align-items-center">
            <button id="btnRun" type="submit" class="btn btn-primary flex-grow-1">Run</button>
            <div id="loading" class="spinner-border spinner-border-sm text-primary ml-2 d-none" role="status">
              <span class="sr-only">Loading...</span>
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex align-items-center justify-content-between">
    <h3 class="card-title mb-0">Recent Sync Status</h3>
    <div>
      <button id="btnPrev" type="button" class="btn btn-sm btn-outline-secondary">Prev</button>
      <span id="pageInfo" class="text-muted small mx-2"></span>
      <button id="btnNext" type="button" class="btn btn-sm btn-outline-secondary">Next</button>
      <button id="btnRefresh" type="button" class="btn btn-sm btn-outline-secondary ml-2">Refresh</button>
    </div>
  </div>
  <div class="card-body table-responsive p-0">
    <table class="table table-bordered table-striped mb-0" id="tblStatus">
      <thead>
        <tr>
          <th>Type</th>
          <th>Start</th>
          <th>End</th>
          <th>Status</th>
          <th>Records</th>
          <th>Error</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="modalInfo" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Sync Job</h5>
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
@endsection

@push('scripts')
<script>
(function () {
  const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  const frmSync = document.getElementById('frmSync');
  const syncType = document.getElementById('syncType');
  const dateFrom = document.getElementById('dateFrom');
  const dateTo = document.getElementById('dateTo');
  const btnRun = document.getElementById('btnRun');
  const loading = document.getElementById('loading');
  const tblBody = document.querySelector('#tblStatus tbody');
  const btnRefresh = document.getElementById('btnRefresh');
  const btnPrev = document.getElementById('btnPrev');
  const btnNext = document.getElementById('btnNext');
  const pageInfo = document.getElementById('pageInfo');
  const modalMessage = document.getElementById('modalMessage');
  const metricTotal = document.getElementById('metricTotal');
  const metricOk = document.getElementById('metricOk');
  const metricFail = document.getElementById('metricFail');
  const metricLast = document.getElementById('metricLast');
  let currentPage = 1;
  let totalPages = 1;

  async function loadStatus() {
    const res = await fetch(`/api/sync/status?limit=20&page=${currentPage}`, {
      headers: { 'Accept': 'application/json' }
    });
    const ct = res.headers.get('content-type') || '';
    if (!ct.includes('application/json')) {
      const text = await res.text();
      throw new Error('Non-JSON response: ' + text.slice(0, 200));
    }
    const json = await res.json();
    const rows = json.sync_history || [];
    totalPages = json.pages || 1;
    currentPage = json.page || 1;
    pageInfo.textContent = `Page ${currentPage} / ${totalPages}`;
    const s = json.stats || {};
    metricTotal.textContent = s.total_syncs ?? '-';
    metricOk.textContent = s.successful_syncs ?? '-';
    metricFail.textContent = s.failed_syncs ?? '-';
    metricLast.textContent = s.last_sync ?? '-';
    tblBody.innerHTML = '';
    rows.forEach(r => {
      const tr = document.createElement('tr');
      tr.innerHTML = `<td>${r.sync_type}</td><td>${r.start_time ?? ''}</td><td>${r.end_time ?? ''}</td><td>${r.status}</td><td>${r.records_processed ?? 0}</td><td>${r.error_message ?? ''}</td>`;
      tblBody.appendChild(tr);
    });
  }

  btnPrev.addEventListener('click', async () => {
    if (currentPage > 1) {
      currentPage -= 1;
      await loadStatus();
    }
  });
  btnNext.addEventListener('click', async () => {
    if (currentPage < totalPages) {
      currentPage += 1;
      await loadStatus();
    }
  });
  btnRefresh.addEventListener('click', loadStatus);

  frmSync.addEventListener('submit', async (e) => {
    e.preventDefault();
    btnRun.disabled = true;
    loading.classList.remove('d-none');
    try {
      const body = {
        sync_type: syncType.value,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined
      };
      const res = await fetch('/api/sync/run', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(body)
      });
      const ct = res.headers.get('content-type') || '';
      if (!ct.includes('application/json')) {
        const text = await res.text();
        modalMessage.textContent = 'Respon bukan JSON (kemungkinan sesi login habis).\n' + text.slice(0, 400);
        $('#modalInfo').modal('show');
        return;
      }
      const json = await res.json();
      if (json.status !== 200) {
        modalMessage.textContent = 'Gagal menjalankan sync: ' + (json.message || res.status);
        $('#modalInfo').modal('show');
        return;
      }
      const jobId = json.job_id;
      modalMessage.textContent = 'Job submitted: ' + jobId + '\nMenunggu selesai...';
      $('#modalInfo').modal('show');
      let done = false;
      while (!done) {
        await new Promise(r => setTimeout(r, 1500));
        const s = await fetch('/api/sync/job/' + jobId, {
          headers: { 'Accept': 'application/json' }
        });
        const sct = s.headers.get('content-type') || '';
        if (!sct.includes('application/json')) break;
        const sj = await s.json();
        if (sj && sj.job) {
          modalMessage.textContent = 'Job: ' + jobId + '\nStatus: ' + sj.job.status + (sj.job.error ? ('\nError: ' + sj.job.error) : '');
          done = ['SUCCESS', 'FAILED'].includes(sj.job.status);
        } else {
          done = true;
        }
      }
      await loadStatus();
      try { $('#modalInfo').modal('hide'); } catch (e) {}
    } catch (err) {
      modalMessage.textContent = 'Error: ' + (err && err.message ? err.message : String(err));
      $('#modalInfo').modal('show');
    } finally {
      btnRun.disabled = false;
      loading.classList.add('d-none');
    }
  });

  loadStatus().catch(err => {
    modalMessage.textContent = 'Error load status: ' + (err.message || err);
    $('#modalInfo').modal('show');
  });
})();
</script>
@endpush
