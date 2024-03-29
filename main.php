<?php

/*

* Plugin Name: WooCommerce Additional Rest API
* Plugin URI: https://codember.com
* Description: This plugin adds additional endpoints to WooCommerce Rest API.
* Version: 2.5
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

                register_rest_route( 'wcapi/v1', '/customer/orders', array(
                    'methods' => 'POST',
                    'callback' => array( $this, 'get_orders' ),
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

                    // check if downloads are empty
                    if ( empty( $downloads ) ) {
                        return [
                            'status' => 404,
                            'message' => 'No downloads found',
                        ];
                    }

                        return [
                            'status' => 200,
                            'message' => 'Downloads found',
                            'downloads' => $downloads,
                        ];

            }


            public function get_orders($request) {
                // $customer_id = get_user_by('email', $request['email']);
                
                // if (!$customer_id) {
                //     return [
                //         'status' => 404,
                //         'message' => 'Customer not found',
                //     ];
                // }
            
                $all_orders = [];
            
                // Get customer's orders
                // $orders = wc_get_orders(array(
                //     'customer' => $customer_id->ID,
                // ));

                $orders = wc_get_orders( array(
                    'billing_email' => $request['email'],
                ) );
            
                // Loop through orders
                foreach ($orders as $order) {
                    $order_items = $order->get_items();
            
                    // Initialize an array to store item details for this order
                    $order_details = [];
            
                    // Loop through order items
                    foreach ($order_items as $item_id => $item) {
                        $product_id = $item->get_product_id();
                        $variation_id = $item->get_variation_id();
                        $product = $item->get_product();
                        $product_name = $item->get_name();
                        $quantity = $item->get_quantity();
                        $subtotal = $item->get_subtotal();
                        $total = $item->get_total();
                        $tax = $item->get_subtotal_tax();
                        $tax_class = $item->get_tax_class();
                        $tax_status = $item->get_tax_status();
                        $allmeta = $item->get_meta_data();
                        $somemeta = $item->get_meta('_whatever', true);
                        $item_type = $item->get_type();
            
                        // Create an array to store item details
                        $item_details = [
                            'product_id' => $product_id,
                            'variation_id' => $variation_id,
                            'product' => $product,
                            'product_name' => $product_name,
                            'quantity' => $quantity,
                            'subtotal' => $subtotal,
                            'total' => $total,
                            'tax' => $tax,
                            'tax_class' => $tax_class,
                            'tax_status' => $tax_status,
                            'allmeta' => $allmeta,
                            'somemeta' => $somemeta,
                            'item_type' => $item_type,
                        ];
            
                        // Add item details to the order details array
                        $order_details[] = $item_details;
                    }
            
                    // Add the order details for this order to the $all_orders array
                    $all_orders[] = $order_details;
                }
            
                // Check if orders are empty
                if (empty($all_orders)) {
                    return [
                        'status' => 404,
                        'message' => 'No orders found',
                    ];
                }
            
                return [
                    'status' => 200,
                    'message' => 'Orders found',
                    'orders' => $all_orders,
                ];
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

                // $user = strstr($request['email'], '@', true);

                // generate random password
                $password = wp_generate_password( 12, true );

                // Create User

                // if user email does not exist, then create user
                if ( !email_exists( $request['email'] ) ) {
                    $create_user = wp_insert_user( array(
                        'user_login' => $request['email'],
                        'user_pass' => $password,
                        'user_email' => $request['email'],
                        'first_name' => $request['first_name'],
                        'last_name' => $request['last_name'],
                        'display_name' => $request['first_name'].' '.$request['last_name'],
                        'role' => 'subscriber'
                    ));
                    // get user id
                    $user_id = get_user_by('id', $create_user);
                }
                
                else {
                    $user_id = get_user_by('email', $request['email']);
                }
                

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




