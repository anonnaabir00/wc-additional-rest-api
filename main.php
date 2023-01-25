<?php

/*

* Plugin Name: WooCommerce Additional Rest API
* Plugin URI: https://codember.com
* Description: This plugin adds additional endpoints to WooCommerce Rest API.
* Version: 2.1
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

                register_rest_route( 'wcapi/v1', '/order/create', array(
                    'methods' => 'POST',
                    'callback' => array( $this, 'create_order' ),
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

            public function create_order($request){
                $product_id = $request['product_id'];
                $coupon = $request['coupon'];
                
                $address = array(
                    'first_name' => $request['first_name'],
                    'last_name'  => $request['last_name'],
                    'email'      => $request['email'],
                    'address_1'  => $request['address'],
                );

                $user = strstr($request['email'], '@', true);

                // generate random password
                $password = wp_generate_password( 12, true );

                // Create User
                
                $create_user = wp_insert_user( array(
                    'user_login' => $user,
                    'user_pass' => $password,
                    'user_email' => $request['email'],
                    'first_name' => $request['first_name'],
                    'last_name' => $request['last_name'],
                    'display_name' => $request['first_name'].' '.$request['last_name'],
                    'role' => 'subscriber'
                  ));

                
                // Get User ID
                $user_id = get_user_by('id', $create_user);

                // Create order
                $order = wc_create_order();

                // Add Product
                $order->add_product(wc_get_product($product_id), 1 );

                // Apply Coupon
                $order->apply_coupon($coupon);

                // Set Customer ID
                $order->set_customer_id($user_id->ID);

                // Set address
                $order->set_address( $address, 'billing' );

                // add payment method
                $order->set_payment_method( 'cod' );
                $order->set_payment_method_title( 'Paddle' );

                $order->set_status( 'wc-completed', 'Order is created programmatically' );

                $order->calculate_totals();
                
                return [
                    'status' => 200,
                    'message' => 'Order is created',
                    'order_id' => $order->get_id(),
                    'user_id' => $user_id->ID,
                    'password' => $password,
                ];

                // Set cart hash
                // $order->set_cart_hash( $request['cart_hash'] );

            }
    
        }

        WC_Additional_Rest_API::get_instance();

    }




