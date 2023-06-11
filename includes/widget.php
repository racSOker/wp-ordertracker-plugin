<?php

$order_tracking_service = new OrderTrackingService();

function tracking_order_admin_woocommerce()
{
  add_meta_box('trackin_order_admin_woocommerce', 'Tracking Order', 'tracking_order_admin_woocommerce_widget', 'shop_order', 'advanced', 'high');
}

function tracking_order_admin_woocommerce_widget()
{
  global $order_tracking_service;
  $providers = $order_tracking_service->get_skydrop_client()->get_carriers();
  ?>
  <div style="display:none" id="order_tracker_info" name="order_tracker_info" >
    <p><strong>Proveedor: </strong><span id="provider"></span></p>
    <p><strong>Referencia de la guía: </strong><span id="tracking_number"></span></p>
    <p><strong>URL de rastreo: </strong><a id="tracking_url_provider"></a></p>
    <p><strong>Guía: </strong><a id="label_url"></a></p>
    <p><strong>Precio: </strong>$<span id="label_price"></span></p>
  </div>

  <div class="order_tracker" id="order_tracker" name="order_tracker">
    <p>
      <label for="provider">Proveedor:</label>
      <select name="provider" id="provider">
        <?php foreach ($providers as $provider): ?>
          <option value="<?= esc_attr($provider['name']) ?>"><?= esc_html($provider['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </p>
    <p>
      <label for="package_length">Largo de paquete:</label>
      <input type="number" name="package_length" id="package_length" /> cm.
    </p>
    <p>
      <label for="package_width">Ancho de paquete:</label>
      <input type="number" name="package_width" id="package_width" /> cm.
    </p>
    <p>
      <label for="package_height">Alto del paquete:</label>
      <input type="number" name="package_height" id="package_height" /> cm.
    </p>
    <p>
      <label for="package_weight">Peso del paquete:</label>
      <input type="number" name="package_weight" id="package_weight" /> kg.
    </p>
    <p>
      <input type="button" class="button track_order" id="track_order" name="track_order" value="Generar guía">
    </p>
  </div>
  <?php
}

function track_order()
{
  $order_id = sanitize_text_field($_POST['post_id']);
  $order = wc_get_order($order_id);

  $provider = sanitize_text_field($_POST['provider']);

  $measures = array(
    'length' => intval(sanitize_text_field($_POST['package_length'])),
    'width' => intval(sanitize_text_field($_POST['package_width'])),
    'height' => intval(sanitize_text_field($_POST['package_height'])),
    'weight' => intval(sanitize_text_field($_POST['package_weight']))
  );

  $order_tracking_service = new OrderTrackingService();
  $tracking_response = $order_tracking_service->track_order($order, $measures, $provider);
  // Procesa los datos enviados por el formulario

  $response = array(
    'status' => 'success',
    'message' => '¡Formulario enviado con éxito!',
    'data' => $tracking_response
  );

  // Envía la respuesta en formato JSON
  wp_send_json($response);
}


?>