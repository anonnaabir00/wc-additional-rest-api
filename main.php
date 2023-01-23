<?php

/*

* Plugin Name: WooCommerce Additional Rest API
* Plugin URI: https://codember.com
* Description: This plugin adds additional endpoints to WooCommerce Rest API.
* Version: 2.0
* Author: Codember
* Author URI: https://codember.com
* License: A "Slug" license name e.g. GPL2
* text-domain: wc-additional-rest-api

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
                    'methods' => 'POST',
                    'callback' => array( $this, 'get_downloads' ),
                ) );

                register_rest_route( 'wcapi/v1', '/order/coupon', array(
                    'methods' => 'POST',
                    'callback' => array( $this, 'check_coupon' ),
                ) );
    
            }

            public function get_downloads($request) {

                    // Retrive customer ID from email

                    $customer_id = get_user_by('email',$request['email']);
                    
                    $downloads = [];

                    // get customers orders
                    $orders = wc_get_orders( array(
                        'customer' => $customer_id->ID,
                    ) );

                    // Loop through orders and Get downloads for each order

                    foreach ( $orders as $order ) {
                        $downloads = array_merge( $downloads, $order->get_downloadable_items() );
                    }

                    return $downloads;

            }


            public function check_coupon($request){
                $coupon = $request['coupon'];

                if (wc_get_coupon_id_by_code( $coupon ) ) {
                    return [
                        'status' => 200,
                        'message' => 'Coupon is valid',
                    ];
                } else {
                    return [
                        'status' => 404,
                        'message' => 'Coupon is not valid',
                    ];
                }
            }
    
        }

        WC_Additional_Rest_API::get_instance();

    }




