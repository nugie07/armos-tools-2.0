@extends('layouts.app')

@section('title', 'Ubah Order Data')
@section('page_title', 'Ubah Order Data')
@section('breadcrumb', 'Ubah Order Status')

@push('styles')
<style>
  .table-responsive { max-height: 600px; overflow-y: auto; }
  .loading-overlay {
    position: absolute; top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(255, 255, 255, 0.8);
    display: flex; align-items: center; justify-content: center; z-index: 1000;
  }
  .table-container { position: relative; }
  #tblOrder tbody tr.selectable { cursor: pointer; }
  #tblOrder tbody tr.selected { background-color: rgba(0, 123, 255, 0.2) !important; }
</style>
@endpush

@section('content')
<div class="card card-outline card-primary">
  <div class="card-body">
    <form id="frmSearch">
      <div class="row align-items-end">
        <div class="col-md-10">
          <div class="form-group mb-md-0">
            <label for="orderNumber">Order Number <span class="text-danger">*</span></label>
            <input id="orderNumber" type="text" class="form-control" placeholder="Masukan DO Number atau Faktur ID" required>
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
    <div class="spinner-border spinner-border-sm text-primary" role="status">
      <span class="sr-only">Loading...</span>
    </div>
    <span class="text-muted ml-2">Mencari data...</span>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h3 class="card-title mb-0">Data Order</h3>
    <small class="text-muted d-block mt-1" id="dataOrderHint">Double-click salah satu baris untuk mengisi form Ubah Data Order di bawah</small>
  </div>
  <div class="card-body table-container p-0">
    <div id="loadingOrderTable" class="loading-overlay d-none">
      <div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>
    </div>
    <div class="table-responsive">
      <table class="table table-bordered table-striped table-hover mb-0" id="tblOrder">
        <thead class="thead-dark">
          <tr id="orderTableHeader"></tr>
        </thead>
        <tbody id="orderTableBody">
          <tr><td colspan="100%" class="text-center text-muted">Masukan Order Number dan klik Cari untuk melihat data</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="card" id="statusUpdateCard" style="display: none;">
  <div class="card-header bg-primary">
    <h3 class="card-title mb-0">Ubah Data Order</h3>
    <small class="d-block mt-1">Double-click satu baris di tabel Data Order di atas, lalu isi perubahan di bawah ini</small>
  </div>
  <div class="card-body">
    <form id="frmUpdateStatus">
      <div class="row">
        <div class="col-md-4">
          <div class="form-group">
            <label for="selectStatus">Status</label>
            <select id="selectStatus" class="form-control">
              <option value="">-- Tidak Diubah --</option>
              <option value="new">new</option>
              <option value="loading">loading</option>
              <option value="ready_to_deliver">ready_to_deliver</option>
              <option value="in_delivery">in_delivery</option>
              <option value="completed">completed</option>
              <option value="skip">skip</option>
              <option value="rejected">rejected</option>
              <option value="hold">hold</option>
              <option value="failed">failed</option>
              <option value="return_to_wms">return_to_wms</option>
              <option value="inactive">inactive</option>
              <option value="in_optimization">in_optimization</option>
            </select>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label for="inputOrderIntegrationId">Order Integration ID</label>
            <input id="inputOrderIntegrationId" type="text" class="form-control" placeholder="Kosongkan jika tidak diubah">
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label for="inputDeliveryDate">Delivery Date</label>
            <input id="inputDeliveryDate" type="date" class="form-control">
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
      <div class="modal-body"><p id="confirmMessage" class="mb-0"></p></div>
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
  const orderNumber = document.getElementById('orderNumber');
  const btnSearch = document.getElementById('btnSearch');
  const loadingSearch = document.getElementById('loadingSearch');
  const loadingOrderTable = document.getElementById('loadingOrderTable');
  const orderTableHeader = document.getElementById('orderTableHeader');
  const orderTableBody = document.getElementById('orderTableBody');
  const statusUpdateCard = document.getElementById('statusUpdateCard');
  const frmUpdateStatus = document.getElementById('frmUpdateStatus');
  const selectStatus = document.getElementById('selectStatus');
  const inputOrderIntegrationId = document.getElementById('inputOrderIntegrationId');
  const inputDeliveryDate = document.getElementById('inputDeliveryDate');
  const btnSave = document.getElementById('btnSave');
  const errorMessage = document.getElementById('errorMessage');
  const confirmMessage = document.getElementById('confirmMessage');
  const successMessage = document.getElementById('successMessage');
  const btnConfirmSave = document.getElementById('btnConfirmSave');

  let currentOrderData = null;

  const ORDER_TABLE_COLUMNS = [
    'order_id', 'faktur_id', 'faktur_date', 'delivery_date', 'do_number', 'status', 'skip_count',
    'created_date', 'created_by', 'updated_date', 'updated_by', 'notes', 'customer_id', 'warehouse_id',
    'delivery_type_id', 'order_integration_id', 'origin_name', 'origin_address_1', 'origin_address_2',
    'origin_city', 'origin_zipcode', 'origin_phone', 'origin_email', 'destination_name',
    'destination_address_1', 'destination_address_2', 'destination_city', 'destination_zip_code',
    'destination_phone', 'destination_email', 'client_id', 'cancel_reason', 'rdo_integration_id',
    'address_change', 'divisi', 'pre_status', 'atena_sorting_code'
  ];

  function resetForm() {
    orderNumber.value = '';
    selectStatus.value = '';
    inputOrderIntegrationId.value = '';
    inputDeliveryDate.value = '';
    statusUpdateCard.style.display = 'none';
    orderTableHeader.innerHTML = '';
    orderTableBody.innerHTML = '<tr><td colspan="100%" class="text-center text-muted">Masukan Order Number dan klik Cari untuk melihat data</td></tr>';
    currentOrderData = null;
    window._orderList = null;
  }

  function showError(message) {
    errorMessage.textContent = message;
    $('#modalError').modal('show');
  }

  function showSuccess(message) {
    successMessage.textContent = message;
    $('#modalSuccess').modal('show');
  }

  function updateFormPlaceholders(data) {
    if (!data) {
      inputOrderIntegrationId.placeholder = 'Kosongkan jika tidak diubah';
      inputOrderIntegrationId.title = '';
      inputDeliveryDate.title = 'Pilih baris order di tabel untuk mengubah';
      return;
    }
    inputOrderIntegrationId.placeholder = data.order_integration_id != null && data.order_integration_id !== ''
      ? `Current: ${data.order_integration_id}`
      : 'Kosongkan jika tidak diubah';
    if (data.delivery_date) {
      const dateStr = (typeof data.delivery_date === 'string' && data.delivery_date.indexOf('T') !== -1)
        ? data.delivery_date.split('T')[0]
        : data.delivery_date;
      inputDeliveryDate.title = `Current: ${dateStr}`;
    } else {
      inputDeliveryDate.title = 'Kosongkan jika tidak diubah';
    }
  }

  function renderTableHeaders(container, data) {
    if (!data || Object.keys(data).length === 0) return;
    container.innerHTML = '';
    const headers = ORDER_TABLE_COLUMNS.filter(col => Object.prototype.hasOwnProperty.call(data, col));
    headers.forEach(header => {
      const th = document.createElement('th');
      th.textContent = header;
      th.style.whiteSpace = 'nowrap';
      container.appendChild(th);
    });
  }

  function renderTableRows(container, dataList, headers) {
    container.innerHTML = '';
    if (!dataList || dataList.length === 0) {
      const tr = document.createElement('tr');
      const td = document.createElement('td');
      td.colSpan = headers.length;
      td.className = 'text-center text-muted';
      td.textContent = 'Tidak ada data';
      tr.appendChild(td);
      container.appendChild(tr);
      return;
    }
    dataList.forEach((data, index) => {
      const tr = document.createElement('tr');
      tr.className = 'selectable';
      tr.dataset.index = index;
      tr.dataset.orderId = data.order_id != null ? String(data.order_id) : '';
      headers.forEach(header => {
        const td = document.createElement('td');
        const value = data[header];
        td.textContent = value !== null && value !== undefined ? String(value) : '';
        td.style.whiteSpace = 'nowrap';
        td.style.maxWidth = '300px';
        td.style.overflow = 'hidden';
        td.style.textOverflow = 'ellipsis';
        td.title = value !== null && value !== undefined ? String(value) : '';
        tr.appendChild(td);
      });
      container.appendChild(tr);
    });
  }

  async function loadOrder() {
    if (!window.requireArmosEnv()) return;
    const orderNum = orderNumber.value.trim();
    if (!orderNum) { showError('Order Number wajib diisi'); return; }

    loadingSearch.classList.remove('d-none');
    btnSearch.disabled = true;
    loadingOrderTable.classList.remove('d-none');
    statusUpdateCard.style.display = 'none';
    currentOrderData = null;

    try {
      const res = await fetch(`/api/ubah-order-status/search?order_number=${encodeURIComponent(orderNum)}`, {
        headers: { 'Accept': 'application/json' }
      });
      const json = await res.json();

      if (json.status === 200) {
        const dataList = Array.isArray(json.data) ? json.data : (json.data ? [json.data] : []);
        if (dataList.length > 0) {
          const first = dataList[0];
          const headers = ORDER_TABLE_COLUMNS.filter(col => Object.prototype.hasOwnProperty.call(first, col));
          renderTableHeaders(orderTableHeader, first);
          renderTableRows(orderTableBody, dataList, headers);
          currentOrderData = null;
          window._orderList = dataList;
          const hintEl = document.getElementById('dataOrderHint');
          if (hintEl) hintEl.textContent = 'Menampilkan ' + dataList.length + ' data. Double-click salah satu baris untuk mengisi form Ubah Data Order di bawah.';
          statusUpdateCard.style.display = 'block';
          updateFormPlaceholders(null);
          selectStatus.value = '';
          inputOrderIntegrationId.value = '';
          inputDeliveryDate.value = '';
          document.querySelectorAll('#tblOrder tbody tr.selected').forEach(r => r.classList.remove('selected'));
          if (dataList.length === 1) {
            const firstRow = orderTableBody.querySelector('tr.selectable');
            if (firstRow) {
              firstRow.classList.add('selected');
              currentOrderData = dataList[0];
              updateFormPlaceholders(currentOrderData);
              selectStatus.value = currentOrderData.status || '';
              inputOrderIntegrationId.value = currentOrderData.order_integration_id != null ? String(currentOrderData.order_integration_id) : '';
              if (currentOrderData.delivery_date) {
                const d = currentOrderData.delivery_date;
                inputDeliveryDate.value = (typeof d === 'string' && d.indexOf('T') !== -1) ? d.split('T')[0] : d;
              } else {
                inputDeliveryDate.value = '';
              }
            }
          }
        } else {
          orderTableHeader.innerHTML = '';
          orderTableBody.innerHTML = '<tr><td colspan="100%" class="text-center text-muted">Tidak ada data ditemukan</td></tr>';
          statusUpdateCard.style.display = 'none';
        }
      } else if (json.status === 404) {
        showError(`Order Number ${orderNum} tidak ditemukan`);
        orderTableHeader.innerHTML = '';
        orderTableBody.innerHTML = '<tr><td colspan="100%" class="text-center text-muted">Data tidak ditemukan</td></tr>';
      } else {
        showError(json.message || 'Error saat mengambil data');
      }
    } catch (error) {
      showError('Error: ' + error.message);
    } finally {
      loadingSearch.classList.add('d-none');
      btnSearch.disabled = false;
      loadingOrderTable.classList.add('d-none');
    }
  }

  async function updateOrderData() {
    if (!window.requireArmosEnv()) return;
    if (!currentOrderData) {
      showError('Tidak ada data order yang dipilih');
      return;
    }
    const newStatus = selectStatus.value.trim();
    const newOrderIntegrationId = inputOrderIntegrationId.value.trim();
    const newDeliveryDate = inputDeliveryDate.value.trim();
    const orderId = currentOrderData.order_id;

    if (!orderId) { showError('Order ID tidak valid'); return; }
    if (!newStatus && !newOrderIntegrationId && !newDeliveryDate) {
      showError('Minimal satu field harus diisi untuk melakukan perubahan');
      return;
    }

    btnSave.disabled = true;
    try {
      const payload = { order_id: orderId };
      if (newStatus) payload.status = newStatus;
      if (newOrderIntegrationId) payload.order_integration_id = newOrderIntegrationId;
      if (newDeliveryDate) payload.delivery_date = newDeliveryDate;

      const res = await fetch('/api/ubah-order-data/update', {
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
        showError(json.message || 'Error saat mengubah data');
      }
    } catch (error) {
      showError('Error: ' + error.message);
    } finally {
      btnSave.disabled = false;
    }
  }

  orderTableBody.addEventListener('dblclick', (e) => {
    const tr = e.target.closest('tr.selectable');
    if (!tr || !window._orderList) return;
    const index = parseInt(tr.dataset.index, 10);
    if (isNaN(index) || index < 0 || index >= window._orderList.length) return;
    document.querySelectorAll('#tblOrder tbody tr.selected').forEach(r => r.classList.remove('selected'));
    tr.classList.add('selected');
    currentOrderData = window._orderList[index];
    updateFormPlaceholders(currentOrderData);
    selectStatus.value = currentOrderData.status || '';
    inputOrderIntegrationId.value = currentOrderData.order_integration_id != null ? String(currentOrderData.order_integration_id) : '';
    if (currentOrderData.delivery_date) {
      const d = currentOrderData.delivery_date;
      inputDeliveryDate.value = (typeof d === 'string' && d.indexOf('T') !== -1) ? d.split('T')[0] : d;
    } else {
      inputDeliveryDate.value = '';
    }
  });

  frmSearch.addEventListener('submit', async (e) => {
    e.preventDefault();
    await loadOrder();
  });

  frmUpdateStatus.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!currentOrderData) {
      showError('Tidak ada data order yang dipilih');
      return;
    }
    if (!window.requireArmosEnv()) return;
    const envLabel = (window.ARMOS_ENV || '').toUpperCase();
    const newStatus = selectStatus.value.trim();
    const newOrderIntegrationId = inputOrderIntegrationId.value.trim();
    const newDeliveryDate = inputDeliveryDate.value.trim();
    const orderNum = orderNumber.value.trim();

    if (!newStatus && !newOrderIntegrationId && !newDeliveryDate) {
      showError('Minimal satu field harus diisi untuk melakukan perubahan');
      return;
    }

    let changes = [];
    if (newStatus) changes.push(`Status: "${currentOrderData.status || '-'}" → "${newStatus}"`);
    if (newOrderIntegrationId) changes.push(`Order Integration ID: "${currentOrderData.order_integration_id || '-'}" → "${newOrderIntegrationId}"`);
    if (newDeliveryDate) {
      const currentDate = currentOrderData.delivery_date ? String(currentOrderData.delivery_date).split('T')[0] : '-';
      changes.push(`Delivery Date: "${currentDate}" → "${newDeliveryDate}"`);
    }

    confirmMessage.innerHTML = `Apakah Anda yakin ingin mengubah data order "<strong>${orderNum}</strong>" di environment <strong>${envLabel}</strong>?<br><br><strong>Perubahan:</strong><br>` + changes.join('<br>');
    $('#modalConfirm').modal('show');
  });

  btnConfirmSave.addEventListener('click', async () => {
    $('#modalConfirm').modal('hide');
    await updateOrderData();
  });

  orderNumber.addEventListener('keypress', async (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      await loadOrder();
    }
  });
})();
</script>
@endpush
