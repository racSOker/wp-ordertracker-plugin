<?php

use Automattic\WooCommerce\Client;

class OrderTrackingService
{

    private $skydrop_client;

    private $skydrop_carriers;

    private $woocommerce_client;

    private $address_from;

    function __construct()
    {
        $skydrop_url = get_option('ordertracker_skydrop_url', '');
        $skydrop_apikey = get_option('ordertracker_skydrop_apikey', '');
        if ($skydrop_url == '' || $skydrop_apikey == '')
        {
            die("Configure el plugin antes de invocar la generación de guías.");
        }
        $this->skydrop_client = new SkydropAPIClient($skydrop_url, $skydrop_apikey);
        $this->skydrop_carriers = array(array('name' => 'Fedex'));

        $options = array('verify_ssl' => false, 'wp_api_prefix' => '', 'version' => '');
        global $wp;
        $this->woocommerce_client = new Client(
            home_url($wp->request),
            get_option('ordertracker_woocommerce_client', ''),
            get_option('ordertracker_woocommerce_secret', ''),
            $options
        );

        $this->address_from = new SkydropAddressFrom();
        $this->address_from->name = get_option('ordertracker_store_name');
        $this->address_from->city = get_option('ordertracker_store_city');
        $this->address_from->province = get_option('ordertracker_store_state');
        $this->address_from->zip = get_option('ordertracker_store_postcode');
        $this->address_from->country = get_option('ordertracker_store_country');
        $this->address_from->company = get_option('ordertracker_store_company_name');
        $this->address_from->address1 = get_option('ordertracker_store_address1');
        $this->address_from->address2 = get_option('ordertracker_store_address2');
        $this->address_from->phone = get_option('ordertracker_store_phone');
        $this->address_from->email = get_option('ordertracker_store_email');
    }

    function track_order($order, $measures, $provider = null): array
    {
        $selected_providers = $this->skydrop_carriers;
        if ($provider != null)
        {
            $selected_providers = array(array('name' => $provider));
        }

        // Fill shipping information
        $shipping = $order->data['billing'];

        $parcel = new SkydropParcel();
        $parcel->distance_unit = 'CM';
        $parcel->mass_unit = 'KG';
        $parcel->weight = $measures['weight'];
        $parcel->height = $measures['height'];
        $parcel->width = $measures['width'];
        $parcel->length = $measures['length'];
        $parcels = array($parcel);

        $address_to = new SkydropAddressTo();
        $address_to->name = $shipping['first_name'] . ' ' . $order->$shipping['last_name'];
        $address_to->city = $shipping['city'];
        $address_to->province = $shipping['state'];
        $address_to->zip = $shipping['postcode'];
        $address_to->country = $shipping['country'];
        $address_to->company = '-';
        $address_to->address1 = $shipping['address_1'];
        $address_to->address2 = '------';
        $address_to->phone = $shipping['phone'];
        $address_to->email = $shipping['email'];
        $address_to->reference = '---';
        $address_to->contents = 'cajitas con meikup del weso';

        $shipment = new SkydropShipment();
        $shipment->address_from = $this->address_from;
        $shipment->parcels = $parcels;
        $shipment->address_to = $address_to;
        $shipment->parcels = $parcels;
        $shipment->carriers = $selected_providers;
        $shipment->consignment_note_class_code = get_option('ordertracker_skydrop_class', '');
        $shipment->consignment_note_packaging_code = get_option('ordertracker_skydrop_packaging', '');

        $created_shipment = $this->skydrop_client->create_shipment($shipment);

        $rate = filter_rate($created_shipment['included']);
        if ($rate != null)
        {
            $label_response = $this->skydrop_client->create_label(intval($rate['id']), "thermal");

            $tracking_number = $label_response['data']['attributes']['tracking_number'];
            $params = array(
                'tracking_provider' => $tracking_number,
                'tracking_number' => 12345789,
                'status_shipped' => 1
            );

            // $shipment_id = $created_shipment['data']['id'];
            $tracking_url_provider = $label_response['data']['attributes']['tracking_url_provider'];
            $label_url = $label_response['data']['attributes']['label_url'];

            $endpoint = "wp-json/wc-shipment-tracking/v3/orders/" . $order->get_id() . "/shipment-trackings?" . http_build_query($params);

            $shipment_tracking_response = $this->woocommerce_client->post($endpoint, array());

            $response = array(
                'provider' => $rate['attributes']['provider'],
                'tracking_number' => strval($params['tracking_number']),
                'label_url' => $label_url,
                'tracking_url_provider' => $tracking_url_provider,
                'label_price' => $rate['attributes']['total_pricing']
            );

            $order->add_order_note("URL de la guía: $tracking_url_provider");

            return $response;
        }
    }

    function get_skydrop_client(): object
    {
        return $this->skydrop_client;
    }
}

function bulk_track($orders = array())
{
    // Verificar si el nonce es válido
    if (!isset($_POST['ordertracker_nonce']) || !wp_verify_nonce($_POST['ordertracker_nonce'], 'bulk_track'))
    {
        wp_die('Nonce no válido');
    }

    // Verificar si se envió el formulario
    if (isset($_POST['action']) && $_POST['action'] == 'bulk_track')
    {
        $order_tracking_service = new OrderTrackingService();

        $query = new WC_Order_Query(array('limit' => 1, 'orderby' => 'date', 'order' => 'ASC', 'status' => array('wc-processing')));
        $orders = $query->get_orders();
        echo '<pre>';
        print_r($orders);
        echo '</pre>';

        foreach ($orders as $order)
        {
            $order_tracking_service->track_order($order);
        }

    }
}

function filter_rate($included_list): array
{
    $price = 999999.0;
    $rate = null;
    foreach ($included_list as $included)
    {
        if ($included['type'] == 'rates')
        {
            if ($included['attributes']['total_pricing'] < $price)
            {
                $rate = $included;
                $price = $included['attributes']['total_pricing'];
            }
        }
    }
    return $rate;
}

?>