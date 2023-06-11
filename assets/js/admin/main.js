jQuery(function ($) {

  // Stand-in wcTracks.recordEvent in case tracks is not available (for any reason).
  window.wcTracks = window.wcTracks || {};
  window.wcTracks.recordEvent = window.wcTracks.recordEvent || function () { };

  var order_tracker_object = {
    init: function () {
      $('#order_tracker')
        .on('click', 'input#track_order', this.track_order_ajax);
    },

    track_order_ajax: function () {

      $('#order_tracker').block({
        message: null,
        overlayCSS: {
          background: '#fff',
          opacity: 0.6
        }
      });

      var data = {
        action: 'track_order',
        post_id: woocommerce_admin_meta_boxes.post_id,
        provider: $('select#provider').val(),
        package_length: $('input#package_length').val(),
        package_width: $('input#package_width').val(),
        package_height: $('input#package_height').val(),
        package_weight: $('input#package_weight').val(),
      };

      $.post(woocommerce_admin_meta_boxes.ajax_url, data, function (response) {

        $('#provider').text(response.data.provider);
        $('#tracking_number').text(response.data.tracking_number.toString());
        $('#tracking_url_provider').text(response.data.tracking_url_provider);
        $('#tracking_url_provider').attr('href', response.data.tracking_url_provider);
        $('#label_url').text(response.data.label_url);
        $('#label_url').attr('href', response.data.label_url);
        $('#label_price').text(response.data.label_price);
        $('#order_tracker').unblock();
        $('#order_tracker').hide();
        $('#order_tracker_info').show();

      }).fail(function (response) {
        $('#order_tracker').unblock();
        alert('Error: ' + response.responseText);
      });

      return false;
    },
  };

  order_tracker_object.init();
});