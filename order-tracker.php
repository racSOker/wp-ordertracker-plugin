<?php
/*
Plugin Name: Order Tracker
Description: Mmanages traking numbers for your orders.
Version: 1.0.0-BETA
Author: Oscar CAPA
Author URI: https://donweso.com/
Text Domain: Don weso
WC requires at least: 3.0.0
WC tested up to: 6.6.0
*/+
require __DIR__ . '/vendor/autoload.php';
require "includes/main-functions.php";
require "includes/skydrop-client.php";
require "includes/skydrop-classes.php";
require "includes/settings-pages-functions.php";
require "includes/widget.php";


function plugin_url() {
    return untrailingslashit( plugins_url( '/', __FILE__ ) );
}

add_action('admin_menu', 'ordertracker_register_settings_page');
add_action('admin_init', 'ordertracker_field_configuration');
add_action('admin_menu', 'ordertracker_menu');

add_action('add_meta_boxes', 'tracking_order_admin_woocommerce');
add_action('admin_post_track_order', 'track_order');
add_action('admin_post_nopriv_track_order', 'track_order');

add_action('admin_post_ordertracker_bulktrack_function', 'bulk_track');
add_action('admin_post_bulktrack_function', 'bulk_track');

add_action('wp_ajax_track_order', 'track_order');
add_action('wp_ajax_nopriv_track_order', 'track_order');
wp_enqueue_script( 'order_tracker', plugin_url() . '/assets/js/admin/main.js', array('woocommerce_admin') );


