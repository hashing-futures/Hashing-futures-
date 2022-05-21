<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Shipment Tracking Main Class.
 */
if (!class_exists('SP_Woocommerce_Shipment_Tracking')) {
    /**
     * Shipment Tracking main class.
     */
    class SP_Woocommerce_Shipment_Tracking
    {

        /**
         * Constructor of ASP_Woocommerce_Shipment_Tracking class.
         */
        public function __construct()
        {
            add_action('rest_api_init', array($this, 'asp_tracking_load_api'), 100);
            add_action('woocommerce_view_order', array($this, 'display_asp_tracking_info_on_order_page'));
            add_action('woocommerce_email_order_meta', array($this, 'add_asp_tracking_info_to_email'), 20);
        }

        /**
         * Initialize rest api for tracking.
         */
        public function asp_tracking_load_api()
        {
            if (!class_exists('SP_Shipment_Tracking_API')) {
                require_once 'sp-shipment-tracking-api.php';
            }
            $obj = new SP_Shipment_Tracking_API();
            $obj->register_routes();
        }

        /**
         * Display the Tracking information on Customer MyAccount->Order page.
         * @param $order_id integer Order Id.
         */
        public function display_asp_tracking_info_on_order_page($order_id)
        {
            $tracking_info = $this->get_tracking_message($order_id);
            if (!empty($tracking_info)) {
                echo $tracking_info;
            }
        }

        /**
         * Get Tracking Message from Order
         * @param $order_id integer Order Id.
         * @return string Tracking info.
         */
        public function get_tracking_message($order_id)
        {
            $message = "Your order was shipped on ";
            $array = get_post_meta($order_id, 'asp_wc_shipment_source', false);
            // error_log(print_r($array, 1));
            if (!empty($array)) {
              if (is_array($array) && count($array) != 0) {
                  foreach ($array as $val) {
                      $message .= '<br>' . $val->shippingdate . " via " . $val->carrier . ". To track shipment, please follow the shipment ID(s) " . $val->trackingnumber;
                  }
              } else {
                  $message .= '' . $array->shippingdate . " via " . $array->carrier . ". To track shipment, please follow the shipment ID(s) " . $array->trackingnumber;
              }
            } else {
              return '';
            }
            return $message;
        }

        /**
         * Add tracking Information to email being sent to Customer.
         */
        public function add_asp_tracking_info_to_email($order)
        {

            $order_status = $order->get_status();
            if ($order_status == 'completed') {
                if (version_compare(WC()->version, '2.7.0', "<")) {
                    $order_id = $order->id;
                } else {
                    $order_id = $order->get_id();
                }
                $shipping_title = apply_filters('asp_shipment_tracking_email_shipping_title', __('Shipping Detail', 'woocommerce-shipping-rates-labels-and-tracking'), $order_id);
                $tracking_info = $this->get_tracking_message($order_id);
                echo '<h3>' . $shipping_title . '</h3><p>' . $tracking_info . '</p></br>';
            }
        }
    }
}
