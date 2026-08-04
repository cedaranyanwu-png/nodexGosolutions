$(document).ready(function() {

  // Set Default Dates
  const today = new Date().toISOString().split('T')[0];
  $('#invoiceDate').val(today);

  // Load Customers list from PHP backend
  loadCustomerDatabase();

  // Add initial line item
  addLineItem('Web Development Services', 1, 500);
  recalculateTotals();

  // Image Upload Handler
  $('#logoPreview').on('click', function() {
    $('#logoInput').click();
  });

  $('#logoInput').on('change', function(e) {
    const file = e.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(evt) {
        $('#logoPreview').attr('src', evt.target.result);
      };
      reader.readAsDataURL(file);
    }
  });

  // Dynamic Item Handlers
  $('#addItemBtn').on('click', function() {
    addLineItem();
  });

  $(document).on('click', '.remove-item-btn', function() {
    if ($('#itemsBody tr').length > 1) {
      $(this).closest('tr').remove();
      recalculateTotals();
    }
  });

  // Recalculate amounts on inputs
  $(document).on('input', '.item-qty, .item-rate, #taxInput', function() {
    recalculateTotals();
  });

  // Payment Status Styling Handler
  $('#paymentStatus').on('change', function() {
    const val = $(this).val();
    $(this).removeClass('text-warning text-success text-danger');
    if (val === 'Paid') $(this).addClass('text-success');
    else if (val === 'Overdue') $(this).addClass('text-danger');
    else $(this).addClass('text-warning');
  });

  // Select Customer Auto-fill
  $('#customerSelect').on('change', function() {
    const selected = $(this).find(':selected').data('customer');
    if (selected) {
      $('#clientName').val(selected.name);
      $('#clientDetails').val(selected.details);
    }
  });

  function addLineItem(desc = '', qty = 1, rate = 0) {
    const amount = (qty * rate).toFixed(2);
    const rowHtml = `
      <tr>
        <td>
          <input type="text" class="form-control form-control-sm item-desc" placeholder="Item or service description" value="${desc}">
        </td>
        <td class="text-center">
          <input type="number" class="form-control form-control-sm text-center item-qty" value="${qty}" min="1">
        </td>
        <td>
          <input type="number" class="form-control form-control-sm text-end item-rate" value="${rate}" step="0.01">
        </td>
        <td class="text-end fw-semibold item-amount">
          $${amount}
        </td>
        <td class="text-center no-print">
          <button type="button" class="btn btn-link text-danger btn-sm p-0 remove-item-btn"><i class="fa-solid fa-trash"></i></button>
        </td>
      </tr>
    `;
    $('#itemsBody').append(rowHtml);
  }

  function recalculateTotals() {
    let subtotal = 0;
    $('#itemsBody tr').each(function() {
      const qty = parseFloat($(this).find('.item-qty').val()) || 0;
      const rate = parseFloat($(this).find('.item-rate').val()) || 0;
      const rowAmount = qty * rate;

      $(this).find('.item-amount').text('$' + rowAmount.toFixed(2));
      subtotal += rowAmount;
    });

    const taxPercent = parseFloat($('#taxInput').val()) || 0;
    const taxAmount = subtotal * (taxPercent / 100);
    const total = subtotal + taxAmount;

    $('#subtotalVal').text('$' + subtotal.toFixed(2));
    $('#totalVal').text('$' + total.toFixed(2));
  }

  // Load Customers via AJAX
  function loadCustomerDatabase() {
    $.ajax({
      url: '/php/invoice_gen.php?action=get_customers',
      type: 'GET',
      dataType: 'json',
      success: function(response) {
        if (response.success && response.customers) {
          const select = $('#customerSelect');
          select.html('<option value="">-- Select Saved Customer --</option>');
          response.customers.forEach(cust => {
            const opt = $('<option></option>').val(cust.id).text(cust.name).data('customer', cust);
            select.append(opt);
          });
        }
      }
    });
  }

  // AJAX Save Invoice Data to Server
  $('#saveBtn').on('click', function() {
    const invoiceData = {
      invoice_num: $('#invoiceNum').val(),
      client_name: $('#clientName').val(),
      client_details: $('#clientDetails').val(),
      total: $('#totalVal').text(),
      status: $('#paymentStatus').val()
    };

    $.ajax({
      url: '/php/invoice_gen.php?action=save_invoice',
      type: 'POST',
      dataType: 'json',
      data: { invoice: JSON.stringify(invoiceData) },
      success: function(res) {
        if (res.success) {
          alert('Invoice saved successfully!');
          loadCustomerDatabase(); // Refresh customers if new
        }
      }
    });
  });

  // Export PDF using html2pdf.js
  $('#downloadPdfBtn').on('click', function() {
    const element = document.getElementById('invoicePaper');

    // Add temporary rendering class to hide print buttons
    $('#invoicePaper').addClass('rendering-pdf');

    const opt = {
      margin:       0.3,
      filename:     ($('#invoiceNum').val() || 'Invoice') + '.pdf',
      image:        { type: 'jpeg', quality: 0.98 },
      html2canvas:  { scale: 2 },
      jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
    };

    html2pdf().set(opt).from(element).save().then(function() {
      $('#invoicePaper').removeClass('rendering-pdf');
    });
  });

});