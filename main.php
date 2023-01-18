<?php

/*

Plugin Name: WooCommerce Additional Rest API

Plugin URI: https://codember.com

Description: A brief description of the Plugin.

Version: 1.0.0

Author: Codember

Author URI: https://codember.com

License: A "Slug" license name e.g. GPL2

text-domain: wc-additional-rest-api

*/

    // if class exists, then intiate the class

    if ( ! class_exists( 'WC_Additional_Rest_API' ) ) {

        class WC_Additional_Rest_API {

            private static $instance = false;

            public static function get_instance() {
                if ( !self::$instance )
                    self::$instance = new self;
                return self::$instance;
            }

            public function __construct() {
                add_action( 'rest_api_init', array( $this, 'register_routes' ) );
            }
    
            public function register_routes() {
    
                register_rest_route( 'wcapi/v1', '/customer/downloads', array(
                    'methods' => 'GET',
                    'callback' => array( $this, 'get_downloads' ),
                ) );
    
            }

            public function get_downloads() {

                    // Get customer ID
                    $customer_id = 1;
                    
                    $downloads = [];

                    // get customers orders
                    $orders = wc_get_orders( array(
                        'customer' => $customer_id,
                    ) );

                    // Loop through orders and Get downloads for each order

                    foreach ( $orders as $order ) {
                        $downloads = array_merge( $downloads, $order->get_downloadable_items() );
                    }

                    return $downloads;

            }
    
        }

        WC_Additional_Rest_API::get_instance();

    }




