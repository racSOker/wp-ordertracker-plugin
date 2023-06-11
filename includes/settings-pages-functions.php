<?php

function ordertracker_register_settings_page()
{
  add_options_page('Configuración de Order Tracker', 'Order Tracker', 'manage_options', 'ordertracker', 'ordertracker_settings_page');
}

function ordertracker_settings_page()
{
?>
  <div class="wrap">
    <h1>Order Tracker</h1>
    <?php $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'bulktrack_tab' ?>
    <h2 class="nav-tab-wrapper">
      <a href="?page=ordertracker-configuracion&tab=bulktrack_tab" class="nav-tab <?= $active_tab == 'bulktrack_tab' ? 'nav-tab-active' : '' ?>">Bulk Tracker</a>
      <a href="?page=ordertracker-configuracion&tab=skydrop_tab" class="nav-tab <?= $active_tab == 'skydrop_tab' ? 'nav-tab-active' : '' ?>">Skydrop Settings</a>
      <a href="?page=ordertracker-configuracion&tab=woocommerce_tab" class="nav-tab <?php $active_tab == 'woocommerce_tab' ? 'nav-tab-active' : '' ?>">Woocommerce Settings</a>
      <a href="?page=ordertracker-configuracion&tab=store_tab" class="nav-tab <?php $active_tab == 'store_tab' ? 'nav-tab-active' : '' ?>">Store Settings</a>
    </h2>
    <?php
    if ($active_tab == 'skydrop_tab') {
      settings_part('ordertracker_options_skydrop', 'ordertracker_skydrop');
    } else if ($active_tab == 'woocommerce_tab') {
      settings_part('ordertracker_options_woocommerce', 'ordertracker_woocommerce');
    } else if ($active_tab == 'store_tab') {
      settings_part('ordertracker_options_store', 'ordertracker_store');
    } else {
      order_form();
    }
    ?>
  </div>
<?php
}

function order_form()
{
?>
  <div class="wrap">
    <h1>Generador de guías</h1>
    <form method="post" action="<?= esc_url(admin_url('admin-post.php')) ?>">

      <p>Existen <strong>
          <?= wc_orders_count('processing') ?>
        </strong> órdenes en progreso.</p>
      <?= wp_nonce_field('bulk_track', 'ordertracker_nonce') ?>
      <input type="hidden" name="action" value="bulk_track">
      <?= submit_button("Generar guías") ?>
    </form>
  </div>
<?php
}

function settings_part($options, $options_section)
{
?>
  <form method="post" action="options.php">
    <?php
    settings_fields($options);
    do_settings_sections($options_section);
    submit_button();
    ?>
  </form>
<?php
}

function ordertracker_field_configuration()
{
  add_settings_section('ordertracker_skydrop_section', 'Configuración Skydrop', 'ordertracker_section_text', 'ordertracker_skydrop');
  $skydrop_settings = array(
    'ordertracker_skydrop_url' => 'Skydrop URL',
    'ordertracker_skydrop_apikey' => 'Skydrop API Key'
  );
  foreach ($skydrop_settings as $key => $value) {
    add_setting($key, $value, 'ordertracker_skydrop', 'ordertracker_skydrop_section', 'ordertracker_options_skydrop');
  }
  add_settings_field('ordertracker_skydrop_class', 'Skydrop Class', 'ordertracker_skydrop_class_text', 'ordertracker_skydrop', 'ordertracker_skydrop_section');
  register_setting('ordertracker_options_skydrop', 'ordertracker_skydrop_class');
  add_settings_field('ordertracker_skydrop_packaging', 'Skydrop Packaging', 'ordertracker_skydrop_packaging_text', 'ordertracker_skydrop', 'ordertracker_skydrop_section');
  register_setting('ordertracker_options_skydrop', 'ordertracker_skydrop_packaging');

  add_settings_section('ordertracker_woocommmerce_section', 'Configuración WooCommerce', 'ordertracker_section_text', 'ordertracker_woocommerce');
  $woocommerce_settings = array(
    'ordertracker_woocommerce_client' => 'WooCommerce Client',
    'ordertracker_woocommerce_secret' => 'WooCommerce Secret',
  );
  foreach ($woocommerce_settings as $key => $value) {
    add_secret_setting($key, $value, 'ordertracker_woocommerce', 'ordertracker_woocommmerce_section', 'ordertracker_options_woocommerce');
  }

  add_settings_section('ordertracker_store_section', 'Configuración Tienda', 'ordertracker_section_text', 'ordertracker_store');
  $address_settings = array(
    'ordertracker_store_company_name' => 'Compañía',
    'ordertracker_store_name' => 'Nombre',
    'ordertracker_store_address1' => 'Dirección',
    'ordertracker_store_address2' => 'Dirección 2',
    'ordertracker_store_city' => 'Ciudad',
    'ordertracker_store_state' => 'Estado',
    'ordertracker_store_postcode' => 'Código Postal',
    'ordertracker_store_country' => 'País',
    'ordertracker_store_phone' => 'Teléfono',
    'ordertracker_store_email' => 'e-mail',
  );
  foreach ($address_settings as $key => $value) {
    add_setting($key, $value, 'ordertracker_store', 'ordertracker_store_section', 'ordertracker_options_store');
  }
}

function add_setting($field_id, $field_name, $page, $section, $option_group)
{
  add_settings_field($field_id, $field_name, 'print_wordpress_input_text', $page, $section, $field_id);
  register_setting($option_group, $field_id);
}

function add_secret_setting($field_id, $field_name, $page, $section, $option_group)
{
  add_settings_field($field_id, $field_name, 'print_wordpress_secret_text', $page, $section, $field_id);
  register_setting($option_group, $field_id);
}

function ordertracker_skydrop_class_text()
{
  $valor = get_option('ordertracker_skydrop_class', '');
  $skydrop_url = get_option('ordertracker_skydrop_url', '');
  $skydrop_apikey = get_option('ordertracker_skydrop_apikey', '');
  if ($skydrop_url == '' || $skydrop_apikey == '') {
    echo "Debe ingresar una url y apikey para acceder a estas opciones.";
    return;
  }
  try {
    $skydrop_client = new SkydropAPIClient($skydrop_url, $skydrop_apikey);
    $consignment_notes_classes = $skydrop_client->get_consignment_classes();

    echo '<select name="ordertracker_skydrop_class">';
    echo '<option value="">Selecciona una clase...</option>';
    foreach ($consignment_notes_classes['data'] as $class) :
      echo '<option value="' . $class['attributes']['code'] . '"' . ($class['attributes']['code'] == $valor ? 'selected="selected"' : '') . '>' . $class['attributes']['name'] . '</option>';
    endforeach;
    echo '</select>';
  } catch (TypeError $e) {
    echo "Error obteniendo datos: " . $e->getMessage();
  }
}

function ordertracker_skydrop_packaging_text()
{
  $valor = get_option('ordertracker_skydrop_packaging', '');
  $skydrop_url = get_option('ordertracker_skydrop_url', '');
  $skydrop_apikey = get_option('ordertracker_skydrop_apikey', '');
  if ($skydrop_url == '' || $skydrop_apikey == '') {
    echo "Debe ingresar una url y apikey para acceder a estas opciones.";
    return;
  }

  try {
    $skydrop_client = new SkydropAPIClient($skydrop_url, $skydrop_apikey);
    $consignment_notes_packagings = $skydrop_client->get_consignment_packagings();

    echo '<select name="ordertracker_skydrop_packaging">';
    echo '<option value="">Selecciona un tipo de empaque...</option>';
    foreach ($consignment_notes_packagings['data'] as $packaging) :
      echo '<option value="' . $packaging['attributes']['code'] . '"' . ($packaging['attributes']['code'] == $valor ? 'selected="selected"' : '') . '>' . $packaging['attributes']['name'] . '</option>';
    endforeach;
    echo '</select>';
  } catch (TypeError $e) {
    echo "Error obteniendo datos: " . $e->getMessage();
  }
}

function print_wordpress_input_text($wordpress_setting_option)
{
  $currentValue = get_option($wordpress_setting_option, '');
  echo "<input type=\"text\" name=\"$wordpress_setting_option\" value=\"" . esc_attr($currentValue) . "\" />";
}

function print_wordpress_secret_text($wordpress_setting_option)
{
  $currentValue = get_option($wordpress_setting_option, '');
  echo "<input type=\"password\" name=\"$wordpress_setting_option\" value=\"" . esc_attr($currentValue) . "\" />";
}

function ordertracker_menu()
{
  add_menu_page(
    'Configuración OrderTracker',
    'Order Tracker',
    'manage_options',
    'ordertracker-configuracion',
    'ordertracker_settings_page',
    'dashicons-admin-generic'
  );
}


?>