@extends('layouts.app')

@section('title', 'Check Order Status')
@section('page_title', 'Check Order Status')
@section('breadcrumb', 'Check Order Status')

@push('styles')
<style>
  .table-responsive { max-height: 600px; overflow-y: auto; }
  .loading-overlay {
    position: absolute; top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(255, 255, 255, 0.8);
    display: flex; align-items: center; justify-content: center; z-index: 1000;
  }
  .table-container { position: relative; }
</style>
@endpush

@section('content')
<div class="card card-outline card-primary">
  <div class="card-body">
    <form id="frmSearch">
      <div class="row align-items-end">
        <div class="col-md-10">
          <div class="form-group mb-md-0">
            <label for="doNumber">Masukan DO Number</label>
            <input id="doNumber" type="text" class="form-control" placeholder="Contoh: G01SI2511-0237" required>
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
  <div class="card-header"><h3 class="card-title mb-0">LIST Order</h3></div>
  <div class="card-body table-container p-0">
    <div id="loadingOrderTable" class="loading-overlay d-none">
      <div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>
    </div>
    <div class="table-responsive">
      <table class="table table-bordered table-striped table-hover mb-0" id="tblOrders">
        <thead class="thead-dark">
          <tr id="orderTableHeader"></tr>
        </thead>
        <tbody id="orderTableBody">
          <tr><td colspan="100%" class="text-center text-muted">Masukan DO Number dan klik Cari untuk melihat data</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header"><h3 class="card-title mb-0">Detail Order</h3></div>
  <div class="card-body table-container p-0">
    <div id="loadingDetailTable" class="loading-overlay d-none">
      <div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>
    </div>
    <div class="table-responsive">
      <table class="table table-bordered table-striped table-hover mb-0" id="tblOrderDetails">
        <thead class="thead-dark">
          <tr id="detailTableHeader"></tr>
        </thead>
        <tbody id="detailTableBody">
          <tr><td colspan="100%" class="text-center text-muted">Double klik pada data di LIST Order untuk melihat Detail Order</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header"><h3 class="card-title mb-0">Data Product VS Inventory</h3></div>
  <div class="card-body table-container p-0">
    <div id="loadingProductVsInventoryTable" class="loading-overlay d-none">
      <div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>
    </div>
    <div class="table-responsive">
      <table class="table table-bordered table-striped table-hover mb-0" id="tblProductVsInventory">
        <thead class="thead-dark">
          <tr id="productVsInventoryTableHeader"></tr>
        </thead>
        <tbody id="productVsInventoryTableBody">
          <tr><td colspan="100%" class="text-center text-muted">Data akan muncul setelah memasukan DO Number dan klik Cari</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="modalError" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Error</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <p id="errorMessage" class="mb-0"></p>
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
  const frmSearch = document.getElementById('frmSearch');
  const doNumber = document.getElementById('doNumber');
  const btnSearch = document.getElementById('btnSearch');
  const loadingSearch = document.getElementById('loadingSearch');
  const loadingOrderTable = document.getElementById('loadingOrderTable');
  const loadingDetailTable = document.getElementById('loadingDetailTable');
  const loadingProductVsInventoryTable = document.getElementById('loadingProductVsInventoryTable');
  const orderTableHeader = document.getElementById('orderTableHeader');
  const orderTableBody = document.getElementById('orderTableBody');
  const detailTableHeader = document.getElementById('detailTableHeader');
  const detailTableBody = document.getElementById('detailTableBody');
  const productVsInventoryTableHeader = document.getElementById('productVsInventoryTableHeader');
  const productVsInventoryTableBody = document.getElementById('productVsInventoryTableBody');
  const errorMessage = document.getElementById('errorMessage');

  let currentOrderId = null;

  const ORDER_LIST_COLUMNS = [
    'order_id', 'faktur_id', 'faktur_date', 'delivery_date', 'do_number', 'status', 'skip_count',
    'created_date', 'created_by', 'updated_date', 'updated_by', 'notes', 'customer_id', 'warehouse_id',
    'delivery_type_id', 'order_integration_id', 'origin_name', 'origin_address_1', 'origin_address_2',
    'origin_city', 'origin_zipcode', 'origin_phone', 'origin_email', 'destination_name',
    'destination_address_1', 'destination_address_2', 'destination_city', 'destination_zip_code',
    'destination_phone', 'destination_email', 'client_id', 'cancel_reason', 'rdo_integration_id',
    'address_change', 'divisi', 'pre_status', 'atena_sorting_code'
  ];

  function showError(message) {
    errorMessage.textContent = message;
    $('#modalError').modal('show');
  }

  function renderTableHeaders(container, data, orderedHeaders) {
    if (!data || data.length === 0) return;
    container.innerHTML = '';
    const headers = orderedHeaders || Object.keys(data[0]);
    headers.forEach(header => {
      const th = document.createElement('th');
      th.textContent = header;
      th.style.whiteSpace = 'nowrap';
      container.appendChild(th);
    });
  }

  function renderTableRows(container, data, headers) {
    container.innerHTML = '';
    if (!data || data.length === 0) {
      const tr = document.createElement('tr');
      const td = document.createElement('td');
      td.colSpan = headers.length;
      td.className = 'text-center text-muted';
      td.textContent = 'Tidak ada data';
      tr.appendChild(td);
      container.appendChild(tr);
      return;
    }
    data.forEach(row => {
      const tr = document.createElement('tr');
      tr.style.cursor = 'pointer';
      headers.forEach(header => {
        const td = document.createElement('td');
        const value = row[header];
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

  async function loadOrders() {
    const fakturId = doNumber.value.trim();
    if (!fakturId) {
      showError('DO Number wajib diisi');
      return;
    }
    loadingSearch.classList.remove('d-none');
    btnSearch.disabled = true;
    loadingOrderTable.classList.remove('d-none');
    try {
      const res = await fetch(`/api/check-order-status/orders?faktur_id=${encodeURIComponent(fakturId)}`, {
        headers: { 'Accept': 'application/json' }
      });
      const json = await res.json();
      if (json.status === 200) {
        const data = json.data || [];
        if (data.length > 0) {
          const first = data[0];
          const headers = ORDER_LIST_COLUMNS.filter(h => Object.prototype.hasOwnProperty.call(first, h));
          renderTableHeaders(orderTableHeader, data, headers);
          renderTableRows(orderTableBody, data, headers);
          orderTableBody.querySelectorAll('tr').forEach((tr, index) => {
            tr.addEventListener('dblclick', () => {
              const orderId = data[index].order_id;
              if (orderId) {
                currentOrderId = orderId;
                loadOrderDetails(orderId);
              }
            });
          });
        } else {
          orderTableHeader.innerHTML = '';
          orderTableBody.innerHTML = '<tr><td colspan="100%" class="text-center text-muted">Tidak ada data ditemukan</td></tr>';
        }
        detailTableHeader.innerHTML = '';
        detailTableBody.innerHTML = '<tr><td colspan="100%" class="text-center text-muted">Double klik pada data di LIST Order untuk melihat Detail Order</td></tr>';
        currentOrderId = null;
        loadProductVsInventory(fakturId);
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

  async function loadOrderDetails(orderId) {
    if (!orderId) return;
    loadingDetailTable.classList.remove('d-none');
    try {
      const res = await fetch(`/api/check-order-status/order-details?order_id=${orderId}`, {
        headers: { 'Accept': 'application/json' }
      });
      const json = await res.json();
      if (json.status === 200) {
        const data = json.data || [];
        if (data.length > 0) {
          const headers = Object.keys(data[0]);
          renderTableHeaders(detailTableHeader, data);
          renderTableRows(detailTableBody, data, headers);
        } else {
          detailTableHeader.innerHTML = '';
          detailTableBody.innerHTML = '<tr><td colspan="100%" class="text-center text-muted">Tidak ada detail order ditemukan</td></tr>';
        }
      } else {
        showError(json.message || 'Error saat mengambil detail order');
      }
    } catch (error) {
      showError('Error: ' + error.message);
    } finally {
      loadingDetailTable.classList.add('d-none');
    }
  }

  async function loadProductVsInventory(fakturId) {
    if (!fakturId) {
      productVsInventoryTableHeader.innerHTML = '';
      productVsInventoryTableBody.innerHTML = '<tr><td colspan="100%" class="text-center text-muted">Data akan muncul setelah memasukan DO Number dan klik Cari</td></tr>';
      return;
    }
    loadingProductVsInventoryTable.classList.remove('d-none');
    try {
      const res = await fetch(`/api/check-order-status/product-vs-inventory?faktur_id=${encodeURIComponent(fakturId)}`, {
        headers: { 'Accept': 'application/json' }
      });
      const json = await res.json();
      if (json.status === 200) {
        const data = json.data || [];
        if (data.length > 0) {
          const orderedHeaders = ['sku', 'product_id', 'mp_product', 'faktur_qty', 'avail_qty', 'check_status'];
          renderTableHeaders(productVsInventoryTableHeader, data, orderedHeaders);
          renderTableRows(productVsInventoryTableBody, data, orderedHeaders);
        } else {
          productVsInventoryTableHeader.innerHTML = '';
          productVsInventoryTableBody.innerHTML = '<tr><td colspan="100%" class="text-center text-muted">Tidak ada data Product VS Inventory ditemukan</td></tr>';
        }
      } else {
        productVsInventoryTableHeader.innerHTML = '';
        productVsInventoryTableBody.innerHTML = `<tr><td colspan="100%" class="text-center text-warning">${json.message || 'Error saat mengambil data Product VS Inventory'}</td></tr>`;
      }
    } catch (error) {
      productVsInventoryTableHeader.innerHTML = '';
      productVsInventoryTableBody.innerHTML = `<tr><td colspan="100%" class="text-center text-danger">Error: ${error.message}</td></tr>`;
    } finally {
      loadingProductVsInventoryTable.classList.add('d-none');
    }
  }

  frmSearch.addEventListener('submit', async (e) => {
    e.preventDefault();
    await loadOrders();
  });

  doNumber.addEventListener('keypress', async (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      await loadOrders();
    }
  });
})();
</script>
@endpush
