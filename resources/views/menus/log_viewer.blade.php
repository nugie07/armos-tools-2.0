@extends('layouts.app')

@section('title', 'Log Viewer')
@section('page_title', 'Log Viewer')
@section('breadcrumb', 'Log Viewer')

@push('styles')
<style>
  .monospace { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size: 12px; }
  textarea.monospace { height: 200px; }
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

<div class="card card-outline card-primary">
  <div class="card-body">
    <div class="row">
      <div class="col-md-4">
        <div class="form-group">
          <label for="ddlFolder">Pilih Folder (Tanggal)</label>
          <select id="ddlFolder" class="form-control">
            <option value="">-- Pilih folder tanggal --</option>
          </select>
        </div>
      </div>
      <div class="col-md-4">
        <div class="form-group">
          <label for="ddlEvent">Pilih Event</label>
          <select id="ddlEvent" class="form-control">
            <option value="">-- Pilih event --</option>
          </select>
        </div>
      </div>
      <div class="col-md-4">
        <div class="form-group">
          <label for="searchRequest" id="lblRequest">Cari Request (opsional)</label>
          <input id="searchRequest" type="text" class="form-control" placeholder="Masukan Request">
        </div>
      </div>
    </div>
    <button id="btnSearch" type="button" class="btn btn-primary" disabled>Search</button>
    <small class="text-muted d-block mt-2">Pilih folder tanggal dan event, lalu klik Search. Double klik baris untuk melihat detail.</small>
  </div>
</div>

<div class="card">
  <div class="card-body table-responsive p-0">
    <table class="table table-bordered table-striped mb-0" id="tblLogs">
      <thead>
        <tr>
          <th>api_request_log_id</th>
          <th>event</th>
          <th>created_date</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
  <div class="card-footer d-flex align-items-center">
    <button id="prevPage" type="button" class="btn btn-outline-secondary btn-sm">Prev</button>
    <span id="pageInfo" class="small text-muted mx-2">Page 1/1</span>
    <button id="nextPage" type="button" class="btn btn-outline-secondary btn-sm" disabled>Next</button>
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
  const loadingOverlay = document.getElementById('loadingOverlay');
  const ddlFolder = document.getElementById('ddlFolder');
  const ddlEvent = document.getElementById('ddlEvent');
  const searchRequest = document.getElementById('searchRequest');
  const lblRequest = document.getElementById('lblRequest');
  const btnSearch = document.getElementById('btnSearch');
  const tblBody = document.querySelector('#tblLogs tbody');
  const detailEvent = document.getElementById('detailEvent');
  const detailCreated = document.getElementById('detailCreated');
  const detailRequest = document.getElementById('detailRequest');
  const detailResponse = document.getElementById('detailResponse');

  let eventOptions = [];
  let currentData = [];
  let selectedRecordId = null;
  let currentPage = 1;
  let totalPages = 1;
  let hasMore = false;
  let searchField = null;
  const PER_PAGE = 15;
  const SEARCH_TIMEOUT_MS = 90000;

  function setLoading(on) {
    loadingOverlay.classList.toggle('d-none', !on);
  }

  function updateRequestField() {
    const slug = ddlEvent.value;
    const opt = eventOptions.find(o => o.slug === slug);
    if (opt && opt.request_config) {
      const c = opt.request_config;
      searchRequest.disabled = false;
      lblRequest.textContent = (c.label || 'Cari Request') + ' (opsional)';
      searchRequest.placeholder = c.placeholder || 'Masukan Request';
      searchField = c.search_field || null;
    } else {
      searchRequest.disabled = true;
      searchRequest.value = '';
      lblRequest.textContent = 'Cari Request (opsional)';
      searchRequest.placeholder = 'Masukan Request';
      searchField = null;
    }
  }

  function updateSearchButton() {
    btnSearch.disabled = !ddlFolder.value || !ddlEvent.value;
  }

  function renderFolderOptions(folders) {
    ddlFolder.innerHTML = '<option value="">-- Pilih folder tanggal --</option>';
    (folders || []).forEach(f => {
      const name = typeof f === 'string' ? f : (f.folder || f.label || '');
      const opt = document.createElement('option');
      opt.value = name;
      opt.textContent = name;
      ddlFolder.appendChild(opt);
    });
  }

  function renderEventOptions(events) {
    eventOptions = events || [];
    ddlEvent.innerHTML = '<option value="">-- Pilih event --</option>';
    eventOptions.forEach(ev => {
      const opt = document.createElement('option');
      opt.value = ev.slug;
      opt.textContent = ev.label;
      ddlEvent.appendChild(opt);
    });
  }

  async function loadInitial() {
    setLoading(true);
    try {
      const [foldRes, evRes] = await Promise.all([
        fetch('/api/log/folders', { headers: { 'Accept': 'application/json' } }),
        fetch('/api/log/events', { headers: { 'Accept': 'application/json' } })
      ]);
      const foldJson = await foldRes.json();
      const evJson = await evRes.json();
      if (foldJson.status === 200) renderFolderOptions(foldJson.data);
      if (evJson.status === 200) renderEventOptions(evJson.data);
      updateRequestField();
      updateSearchButton();
    } catch (err) {
      alert('Gagal memuat data: ' + (err.message || 'Unknown error'));
    } finally {
      setLoading(false);
    }
  }

  function fillDetails(r) {
    if (!r) return;
    detailEvent.value = r.event ?? '';
    detailCreated.value = r.created_date ?? '';
    let reqText = r.request ?? '';
    try { if (typeof reqText === 'string') { reqText = JSON.stringify(JSON.parse(reqText), null, 2); } } catch (e) {}
    detailRequest.value = reqText;
    let respText = r.response ?? '';
    try { if (typeof respText === 'string') { respText = JSON.stringify(JSON.parse(respText), null, 2); } } catch (e) {}
    detailResponse.value = respText;
  }

  function renderRows(rows) {
    tblBody.innerHTML = '';
    (rows || []).forEach(r => {
      const tr = document.createElement('tr');
      tr.innerHTML = `<td>${r.api_request_log_id ?? ''}</td><td>${r.event ?? ''}</td><td>${r.created_date ?? ''}</td>`;
      tr.addEventListener('dblclick', () => {
        selectedRecordId = r.api_request_log_id ?? null;
        fillDetails(r);
      });
      tblBody.appendChild(tr);
    });
  }

  async function doSearch() {
    const folder = ddlFolder.value;
    const eventSlug = ddlEvent.value;
    if (!folder || !eventSlug) {
      alert('Pilih folder tanggal dan event terlebih dahulu.');
      return;
    }
    const qReq = (searchRequest.value || '').trim();
    const prevSelected = selectedRecordId;
    setLoading(true);
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), SEARCH_TIMEOUT_MS);
    try {
      const url = `/api/log/search?folder=${encodeURIComponent(folder)}&event=${encodeURIComponent(eventSlug)}&request=${encodeURIComponent(qReq)}&search_field=${encodeURIComponent(searchField || '')}&page=${currentPage}&per_page=${PER_PAGE}`;
      const res = await fetch(url, { signal: controller.signal, headers: { 'Accept': 'application/json' } });
      clearTimeout(timeoutId);
      const json = await res.json();
      if (json.status === 200) {
        currentData = (json.data || []).map((x, i) => ({ ...x, _idx: i }));
        totalPages = json.pages || 1;
        hasMore = !!json.has_more;
        document.getElementById('pageInfo').textContent = `Page ${json.page || 1}/${totalPages}`;
        renderRows(currentData);
        document.getElementById('nextPage').disabled = !hasMore;
        if (prevSelected != null) {
          const found = currentData.find(r => String(r.api_request_log_id) === String(prevSelected));
          if (found) fillDetails(found);
        }
      } else {
        alert(json.message || 'Error saat melakukan pencarian');
      }
    } catch (error) {
      clearTimeout(timeoutId);
      if (error.name === 'AbortError') alert('Request timeout (90 detik). Coba lagi.');
      else alert('Gagal terhubung: ' + (error.message || 'Failed to fetch'));
    } finally {
      setLoading(false);
    }
  }

  ddlEvent.addEventListener('change', () => { updateRequestField(); updateSearchButton(); });
  ddlFolder.addEventListener('change', updateSearchButton);
  btnSearch.addEventListener('click', () => { currentPage = 1; doSearch(); });
  document.getElementById('prevPage').addEventListener('click', () => { if (currentPage > 1) { currentPage--; doSearch(); } });
  document.getElementById('nextPage').addEventListener('click', () => { if (hasMore) { currentPage++; doSearch(); } });

  loadInitial();
})();
</script>
@endpush
