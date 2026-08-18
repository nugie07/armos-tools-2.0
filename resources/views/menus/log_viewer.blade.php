@extends('layouts.app')

@section('title', 'Log Viewer')
@section('page_title', 'Log Viewer')
@section('breadcrumb', 'Log Viewer')

@push('styles')
<style>
  .monospace { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size: 12px; }
  textarea.monospace { height: 220px; }
  #tblLogs tbody tr { cursor: pointer; }
  #tblLogs tbody tr.active { background: #e8f4ff; }
  #loadingOverlay {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(255,255,255,0.85); display: flex; align-items: center; justify-content: center;
    z-index: 9999;
  }
  #loadingOverlay.d-none { display: none !important; }
</style>
@endpush

@section('content')
<div id="loadingOverlay" class="d-none">
  <div class="text-center">
    <div class="spinner-border text-primary" role="status"></div>
    <p class="mt-2 mb-0 text-muted">Loading...</p>
  </div>
</div>

<div class="card card-outline card-secondary mb-3">
  <div class="card-body py-3">
    <div class="d-flex flex-wrap align-items-center justify-content-between">
      <div class="small text-muted" id="syncStatusText">Memuat status sync...</div>
      <button id="btnSync" type="button" class="btn btn-outline-primary btn-sm" disabled>Sync Now</button>
    </div>
  </div>
</div>

<div class="card card-outline card-primary">
  <div class="card-body">
    <div class="row">
      <div class="col-md-3">
        <div class="form-group">
          <label for="ddlEvent">Pilih Event</label>
          <select id="ddlEvent" class="form-control">
            <option value="">-- Pilih event --</option>
          </select>
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-group">
          <label for="searchRequest" id="lblRequest">Reference</label>
          <input id="searchRequest" type="text" class="form-control" placeholder="Exact match" disabled>
        </div>
      </div>
      <div class="col-md-2">
        <div class="form-group">
          <label for="dateFrom">Dari tanggal</label>
          <input id="dateFrom" type="date" class="form-control">
        </div>
      </div>
      <div class="col-md-2">
        <div class="form-group">
          <label for="dateTo">Sampai tanggal</label>
          <input id="dateTo" type="date" class="form-control">
        </div>
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <div class="form-group w-100">
          <button id="btnSearch" type="button" class="btn btn-primary btn-block" disabled>Search</button>
        </div>
      </div>
    </div>
    <small class="text-muted">Filter tanggal memakai kolom <code>created_date</code> (tanggal log dibuat di TMS), inclusive per hari kalender. Double-klik baris untuk load detail (request/response) dari production by ID.</small>
  </div>
</div>

<div class="card">
  <div class="card-body table-responsive p-0">
    <table class="table table-bordered table-striped mb-0" id="tblLogs">
      <thead>
        <tr>
          <th>api_request_log_id</th>
          <th>event</th>
          <th>reference</th>
          <th>created_date</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
  <div class="card-footer d-flex align-items-center">
    <button id="btnPrev" type="button" class="btn btn-outline-secondary btn-sm" disabled>Prev</button>
    <span id="pageInfo" class="small text-muted mx-2"></span>
    <button id="btnNext" type="button" class="btn btn-outline-secondary btn-sm" disabled>Next</button>
  </div>
</div>

<div class="card card-outline card-secondary">
  <div class="card-header"><h3 class="card-title">Detail</h3></div>
  <div class="card-body">
    <div class="row">
      <div class="col-md-6">
        <div class="form-group">
          <label>Nama Event</label>
          <input id="detailEvent" type="text" class="form-control monospace" readonly>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-group">
          <label>Created Date</label>
          <input id="detailCreated" type="text" class="form-control monospace" readonly>
        </div>
      </div>
      <div class="col-12">
        <div class="form-group">
          <label>Request</label>
          <textarea id="detailRequest" class="form-control monospace" readonly></textarea>
        </div>
      </div>
      <div class="col-12">
        <div class="form-group mb-0">
          <label>Response</label>
          <textarea id="detailResponse" class="form-control monospace" readonly></textarea>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
  const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  const loadingOverlay = document.getElementById('loadingOverlay');
  const ddlEvent = document.getElementById('ddlEvent');
  const searchRequest = document.getElementById('searchRequest');
  const lblRequest = document.getElementById('lblRequest');
  const dateFrom = document.getElementById('dateFrom');
  const dateTo = document.getElementById('dateTo');
  const btnSearch = document.getElementById('btnSearch');
  const btnPrev = document.getElementById('btnPrev');
  const btnNext = document.getElementById('btnNext');
  const btnSync = document.getElementById('btnSync');
  const syncStatusText = document.getElementById('syncStatusText');
  const tblBody = document.querySelector('#tblLogs tbody');
  const pageInfo = document.getElementById('pageInfo');
  const detailEvent = document.getElementById('detailEvent');
  const detailCreated = document.getElementById('detailCreated');
  const detailRequest = document.getElementById('detailRequest');
  const detailResponse = document.getElementById('detailResponse');

  let eventOptions = [];
  let currentPage = 1;
  let lastPage = 1;
  const perPage = 15;

  function setLoading(on) { loadingOverlay.classList.toggle('d-none', !on); }

  function updateRequestField() {
    const opt = eventOptions.find(o => o.slug === ddlEvent.value);
    if (opt && opt.request_config && opt.request_config.search_field) {
      const c = opt.request_config;
      searchRequest.disabled = false;
      lblRequest.textContent = c.label || 'Reference';
      searchRequest.placeholder = c.placeholder || 'Exact match';
    } else {
      searchRequest.disabled = true;
      searchRequest.value = '';
      lblRequest.textContent = 'Reference (tidak tersedia)';
      searchRequest.placeholder = '';
    }
  }

  function updateSearchButton() {
    btnSearch.disabled = !ddlEvent.value;
  }

  async function loadEvents() {
    const res = await fetch('/api/logs/events', { headers: { 'Accept': 'application/json' } });
    const json = await res.json();
    eventOptions = json.data || [];
    ddlEvent.innerHTML = '<option value="">-- Pilih event --</option>';
    eventOptions.forEach(ev => {
      const opt = document.createElement('option');
      opt.value = ev.slug;
      opt.textContent = ev.label;
      ddlEvent.appendChild(opt);
    });
  }

  async function loadSyncStatus() {
    try {
      const res = await fetch('/api/logs/sync-status', { headers: { 'Accept': 'application/json' } });
      const json = await res.json();
      if (json.status !== 200 || !json.data) {
        syncStatusText.textContent = json.message || 'Status sync tidak tersedia';
        btnSync.disabled = true;
        return;
      }
      const d = json.data;
      const parts = [
        `Env: ${d.environment || '-'}`,
        `Status: ${d.status || '-'}`,
        `Last Sync: ${d.last_sync_started_at || '-'}`,
        `Records: ${d.last_sync_records ?? 0}`,
      ];
      if (d.lookback_from) parts.push(`From: ${d.lookback_from}`);
      if (d.next_manual_sync_at) parts.push(`Next manual sync: ${d.next_manual_sync_at}`);
      if (d.last_error) parts.push(`Error: ${d.last_error}`);
      syncStatusText.textContent = parts.join(' | ');
      btnSync.disabled = !!d.cooldown_active;
    } catch (e) {
      syncStatusText.textContent = 'Gagal memuat status sync';
      btnSync.disabled = true;
    }
  }

  function pretty(v) {
    if (v == null) return '';
    if (typeof v === 'string') {
      try { return JSON.stringify(JSON.parse(v), null, 2); } catch (e) { return v; }
    }
    try { return JSON.stringify(v, null, 2); } catch (e) { return String(v); }
  }

  function clearDetails() {
    detailEvent.value = '';
    detailCreated.value = '';
    detailRequest.value = '';
    detailResponse.value = '';
  }

  async function loadDetail(id) {
    setLoading(true);
    try {
      const res = await fetch(`/api/logs/${id}`, { headers: { 'Accept': 'application/json' } });
      const json = await res.json();
      if (json.status === 200 && json.data) {
        const d = json.data;
        detailEvent.value = d.event || '';
        detailCreated.value = d.created_date || '';
        detailRequest.value = pretty(d.request);
        detailResponse.value = pretty(d.response);
      } else {
        alert(json.message || 'Gagal load detail');
      }
    } catch (e) {
      alert('Gagal load detail: ' + (e.message || e));
    } finally {
      setLoading(false);
    }
  }

  function appendRows(rows, replace) {
    if (replace) tblBody.innerHTML = '';
    (rows || []).forEach(r => {
      const tr = document.createElement('tr');
      tr.innerHTML = `<td>${r.api_request_log_id ?? ''}</td><td>${r.event ?? r.event_slug ?? ''}</td><td>${r.reference_value ?? ''}</td><td>${r.created_date ?? ''}</td>`;
      tr.addEventListener('click', () => {
        [...tblBody.querySelectorAll('tr')].forEach(x => x.classList.remove('active'));
        tr.classList.add('active');
      });
      tr.addEventListener('dblclick', () => loadDetail(r.api_request_log_id));
      tblBody.appendChild(tr);
    });
  }

  function updatePager() {
    btnPrev.disabled = currentPage <= 1;
    btnNext.disabled = currentPage >= lastPage;
  }

  async function doSearch(page) {
    if (!ddlEvent.value) {
      alert('Pilih event terlebih dahulu.');
      return;
    }
    currentPage = page || 1;
    clearDetails();
    setLoading(true);
    try {
      const params = new URLSearchParams({
        event_slug: ddlEvent.value,
        page: String(currentPage),
        per_page: String(perPage),
      });
      const ref = (searchRequest.value || '').trim();
      if (ref && !searchRequest.disabled) params.set('reference_value', ref);
      if (dateFrom.value) params.set('date_from', dateFrom.value);
      if (dateTo.value) params.set('date_to', dateTo.value);

      const res = await fetch('/api/logs?' + params.toString(), { headers: { 'Accept': 'application/json' } });
      const json = await res.json();
      if (json.status === 200) {
        appendRows(json.data || [], true);
        currentPage = json.current_page || 1;
        lastPage = json.last_page || 1;
        const total = json.total ?? 0;
        const shown = (json.data || []).length;
        const from = total === 0 ? 0 : ((currentPage - 1) * perPage) + 1;
        const to = total === 0 ? 0 : ((currentPage - 1) * perPage) + shown;
        pageInfo.textContent = total === 0
          ? '0 rows'
          : `${from}–${to} of ${total} · page ${currentPage}/${lastPage}`;
        updatePager();
      } else {
        alert(json.message || 'Error search');
      }
    } catch (e) {
      alert('Gagal search: ' + (e.message || e));
    } finally {
      setLoading(false);
    }
  }

  async function triggerSync() {
    btnSync.disabled = true;
    setLoading(true);
    try {
      const res = await fetch('/api/logs/sync', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'X-Requested-With': 'XMLHttpRequest'
        }
      });
      const json = await res.json();
      alert(json.message || (res.ok ? 'Sync started' : 'Sync rejected'));
      await loadSyncStatus();
    } catch (e) {
      alert('Gagal sync: ' + (e.message || e));
      await loadSyncStatus();
    } finally {
      setLoading(false);
    }
  }

  ddlEvent.addEventListener('change', () => { updateRequestField(); updateSearchButton(); });
  btnSearch.addEventListener('click', () => doSearch(1));
  btnPrev.addEventListener('click', () => { if (currentPage > 1) doSearch(currentPage - 1); });
  btnNext.addEventListener('click', () => { if (currentPage < lastPage) doSearch(currentPage + 1); });
  btnSync.addEventListener('click', triggerSync);

  (async function init() {
    setLoading(true);
    try {
      await Promise.all([loadEvents(), loadSyncStatus()]);
      updateRequestField();
      updateSearchButton();
    } finally {
      setLoading(false);
    }
  })();
})();
</script>
@endpush
