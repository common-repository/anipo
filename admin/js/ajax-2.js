jQuery(document).ready(function ($) {

  $('.anipo-print-barcode-button').on('click', function (e) {
    e.preventDefault(); // Prevent the default behavior (page reload)

    let orderId = $(this).data('order-id');

    $('#anipo-ajax-loader').show();

    $.ajax({
      url: barcodeAjax.ajax_url,  // Use the admin-ajax.php URL passed to JavaScript
      type: 'POST',
      dataType: 'json',
      data: {
        action: 'anipo_print_barcode',
        order_id: orderId,
        nonce: barcodeAjax.nonce  // Security nonce
      },
      success: function (response) {
        // Hide the loader
        $('#anipo-ajax-loader').hide();

        if (response.success) {
          if (response.data.status) {
            let data = encodeURIComponent(JSON.stringify(response.data.data));
            let printUrl = barcodeAjax.admin_url + `HTML/print-barcode-order.html?&data=${data}`;
            let printWindow = window.open(printUrl, '_blank');
            printWindow.onafterprint = function () {
              printWindow.close();
            };
          } else {
            alert(response.data.message);
          }
        } else {
          alert(response);  // Show error message
        }
      },
      error: function (xhr, status, error) {
        // Hide the loader
        $('#anipo-ajax-loader').hide();
        alert('An error occurred: ' + error);  // Handle error
        location.reload();
      }
    });
  });
});
