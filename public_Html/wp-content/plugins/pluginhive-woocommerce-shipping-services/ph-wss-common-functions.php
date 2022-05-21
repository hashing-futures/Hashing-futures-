<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Common Functions class.
 */
if (!class_exists("PH_WSS_Common_Functions")) {
    /**
     * Holds Common Methods.
     */
    class PH_WSS_Common_Functions
    {

        /**
         * Array of active plugins.
         */
        private static $active_plugins;
        /**
         * Current user details WP_User object.
         */
        private static $current_user_details;
        /**
         * Current User email id.
         */
        private static $current_user_email_id;

        /**
         * Initialize the active plugins.
         */
        public static function init()
        {
            self::$active_plugins = (array) get_option('active_plugins', array());
            if (is_multisite()) {
                self::$active_plugins = array_merge(self::$active_plugins, get_site_option('active_sitewide_plugins', array()));
            }
        }

        /**
         * Check whether woocommerce is active or not.
         * @return boolean True if woocommerce is active else false.
         */
        public static function woocommerce_active_check()
        {
            if (!self::$active_plugins) {
                self::init();
            }
            return in_array('woocommerce/woocommerce.php', self::$active_plugins) || array_key_exists('woocommerce/woocommerce.php', self::$active_plugins);
        }

        /**
         * Get current user details.
         * @return object WP_User Object
         */
        public static function get_current_user_details()
        {
            if (empty(self::$current_user_details)) {
                self::$current_user_details = wp_get_current_user();
            }
            return self::$current_user_details;
        }

        /**
         * Get current user email.
         * @return string Current user email id.
         */
        public static function get_current_user_email_id()
        {
            if (empty(self::$current_user_email_id)) {
                $current_user_details = self::get_current_user_details();
                self::$current_user_email_id = $current_user_details->__get('user_email');
            }
            return self::$current_user_email_id;
        }

        public static function make_post_request($url, $data, $headers = [])
        {
            $headers['Content-Type'] = 'application/json';
            $response = wp_remote_post(
                $url,
                array(
                    'headers' => $headers,
                    'timeout' => 20,
                    'body' => json_encode($data),
                )
            );

            if (is_wp_error($response)) {
                $response_data = [];
                $error_string = $response->get_error_message();
                $response_data['success'] = false;
                $response_data['message'] = $error_string;
                return $response_data;
            }
            return json_decode($response['body']);
        }
        
        public static function makeGetRequest($url, $headers = [])
        {
            $headers['Content-Type'] = 'application/json';
            $response = wp_remote_get(
                $url,
                array(
                    'headers' => $headers,
                    'timeout' => 20,
                )
            );

            if (is_wp_error($response)) {
                $response_data = [];
                $error_string = $response->get_error_message();
                $response_data['success'] = false;
                $response_data['message'] = $error_string;
                return $response_data;
            }
            return json_decode($response['body']);
        }

        /**
         * Add diagnostic report info to the Woocommerce Logs.
         */
        public static function ph_wss_admin_diagnostic_report( $data ) {

            $settings = get_option('woocommerce_pluginhive_woocommerce_shipping_settings', true);
            $debug = (isset($settings['debug']) && $settings['debug'] == 'yes') ? true : false;
	
            if( $debug && function_exists("wc_get_logger") ) {
    
                $log = wc_get_logger();
                $log->debug( ($data).PHP_EOL.PHP_EOL, array('source' => 'PluginHive-WooCommerce-Shipping-Services-Debug-Log'));
            }
        }
    }
}
