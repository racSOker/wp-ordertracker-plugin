<?php
class SkydropShipment {

  public $address_from;
  public $address_to;

  public $parcels;

  public $consignment_note_class_code;

  public $consignment_note_packaging_code;

  public $carriers;

}

class SkydropAddressFrom {
  public $province;
  public $city;
  public $name;
  public $zip;
  public $country;
  public $address1;
  public $company;
  public $address2;
  public $phone;
  public $email;
}

class SkydropAddressTo {
  public $province;
  public $city;
  public $name;
  public $zip;
  public $country;
  public $address1;
  public $company;
  public $address2;
  public $phone;
  public $email;
  public $reference;
  public $contents;
}

class SkydropParcel {
public $weight;
public $distance_unit;
public $mass_unit;
public $height;
public $width;
public $length;
}

?>