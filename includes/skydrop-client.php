<?php
class SkydropAPIClient {

  const CONSIGNMENT_NOTES_CLASSES_URI = "/v1/consignment_notes/subcategories/288/classes";
  const CONSIGNMENT_NOTES_PACKAGINGS_URI = "/v1/consignment_notes/packagings";
  const SHIPMENTS_URI = "/v1/shipments";
  const LABELS_URI = "/v1/labels";
  const CARRIERS_URI = "/v1/carriers";

  private $base_url;
  private $api_key;

  function __construct($base_url, $api_key) {
    $this->base_url = $base_url;
    $this->api_key = $api_key;
  }

  function  get_consignment_classes(): array{
    return $this->_get(self::CONSIGNMENT_NOTES_CLASSES_URI);
  }

  function  get_consignment_packagings(): array{
    return $this->_get(self::CONSIGNMENT_NOTES_PACKAGINGS_URI);
  }

  function  get_carriers(): array{
    $carriers = $this->_get(self::CARRIERS_URI);
    $carrier_names = array();
    foreach ($carriers['data'] as $carrier){
      $carrier_names[] = array('name' => $carrier['attributes']['name']);
    }
    return $carrier_names;
  }

  function  create_shipment($shipment): array{
    $api_endpoint = $this->base_url . self::SHIPMENTS_URI;
    $api_args     = array(
       'method'  => 'POST',
       'timeout' => 10,
       'headers' => $this->headers(),
       'body' => json_encode($shipment)
    );
     
    $response  = wp_remote_post( $api_endpoint, $api_args );
    return json_decode( wp_remote_retrieve_body( $response ), true );
  }

  function  create_label($rate_id, $format): array{

    $label_request = array(
      'rate_id' => $rate_id,
      'label_format' => $format
  );

    $api_endpoint = $this->base_url . self::LABELS_URI;
    $api_args     = array(
       'method'  => 'POST',
       'timeout' => 10,
       'headers' => $this->headers(),
       'body' => json_encode($label_request)
    );
     
    $response  = wp_remote_post( $api_endpoint, $api_args );
    return json_decode( wp_remote_retrieve_body( $response ), true );
  }

  function _get($endpoint): array {
    $api_endpoint = $this->base_url . $endpoint;
    $api_args     = array(
       'headers' => $this->headers()
    );
     
    $response  = wp_remote_get( $api_endpoint, $api_args );
    return json_decode( wp_remote_retrieve_body( $response ), true );
  }

  function _post($endpoint): array {
    $api_endpoint = $this->base_url . $endpoint;
    $api_args     = array(
       'headers' => $this->headers()
    );
    $response  = wp_remote_get( $api_endpoint, $api_args );
    return json_decode( wp_remote_retrieve_body( $response ), true );
  }

  function headers(): array {
    return array( 
      'Authorization' => 'Bearer '.$this->api_key,
      'Content-Type'  => 'application/json', );
  }
}

?>