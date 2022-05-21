<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Shipping Method Class. Responsible for handling rates.
 */
if (!class_exists("SP_Shipping_Method")) {
    class SP_Shipping_Method extends WC_Shipping_Method
    {

        /**
         * Weight Unit.
         */
        public static $weight_unit;
        /**
         * Dimension Unit.
         */
        public static $dimension_unit;
        /**
         * Currency code.
         */
        public static $currency_code;
        /**
         * Integration Id.
         */
        public static $integration_id;
        /**
         * Secret Key.
         */
        public static $secret_key;

        /**
         * boolean true if debug mode is enabled.
         */
        public static $debug;
        /**
         * AddonStation transaction id returned by AddonStation Server.
         */
        public static $aspTransactionId;
        /**
         * Fall back rate.
         */
        public static $fallback_rate;
        /**
         * Tax Calculation for Shipping rates.
         */
        public static $tax_calculation_mode;

        /**
         * Constructor.
         */
        public function __construct()
        {
            $plugin_configuration = Pluginhive_Woocommerce_Shipping::sp_plugin_configuration();
            $this->id = $plugin_configuration['id'];
            $this->method_title = $plugin_configuration['method_title'];
            $this->method_description = $plugin_configuration['method_description'];
            $this->init();
            // Save settings in admin
            add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
            add_filter('woocommerce_cart_shipping_method_full_label', array($this, 'sp_add_delivery_time'), 10, 2);

            if (!class_exists("PH_WSS_Common_Functions")) {
                require_once '../ph-wss-common-functions.php';
            }
        }

        public function sp_add_delivery_time($label, $method)
        {
            // Older version of WC is not supporting get_meta_data() on method.
            if (!is_object($method) || !method_exists($method, 'get_meta_data')) {
                return $label;
            }

            $est_delivery = $method->get_meta_data();
            if (isset($est_delivery['sp_shipping_rates']) && !empty($est_delivery['sp_shipping_rates']['sp_transit_message']) && strpos($label, 'Est Delivery') == false) {
                $est_delivery_html = "<br /><small>" . 'Est Delivery: ' . $est_delivery['sp_shipping_rates']['sp_transit_message'] . '</small>';
                $label .= $est_delivery_html;
            }
            return $label;
        }

        /**
         * Initialize the settings.
         */
        private function init()
        {

            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();
            $plugin_configuration = get_option(SP_PLUGIN_ID, true);

            $this->title = $this->method_title;
            $this->enabled = isset($this->settings['enabled']) ? $this->settings['enabled'] : 'no';
            $this->enabled_rates = isset($this->settings['enabled_rates']) ? $this->settings['enabled_rates'] : 'no';
            // self::$integration_id = isset($this->settings['integration_id']) ? $this->settings['integration_id'] : null;
            self::$integration_id = isset($plugin_configuration['integration_id']) ? $plugin_configuration['integration_id'] : null;
            // self::$secret_key = isset($this->settings['secret_key']) ? $this->settings['secret_key'] : null;
            self::$secret_key = isset($plugin_configuration['secret_key']) ? $plugin_configuration['secret_key'] : null;
            self::$debug = (isset($this->settings['debug']) && $this->settings['debug'] == 'yes') ? true : false;
            self::$fallback_rate = !empty($this->settings['fallback_rate']) ? $this->settings['fallback_rate'] : null;
            $this->shipping_title = !empty($this->settings['shipping_title']) ? $this->settings['shipping_title'] : 'Shipping Rate';
            self::$tax_calculation_mode = !empty($this->settings['tax_calculation_mode']) ? $this->settings['tax_calculation_mode'] : false;
        }

        /**
         * Settings Form fileds.
         */
        public function init_form_fields()
        {
            $this->form_fields = include 'data-pluginhive-settings.php';
        }

        /**
         * Encrypt the data.
         * @param $key string secret key used for encoding
         * @param $data object Json encoded data.
         * @param string Encrypted data in hexadecimal.
         */
        public static function encrypt_data($key, $data)
        {
            $encryptionMethod = "AES-256-CBC";
            $iv = substr($key, 0, 16);
            if (version_compare(phpversion(), '5.3.2', '>')) {
                $encryptedMessage = openssl_encrypt($data, $encryptionMethod, $key, OPENSSL_RAW_DATA, $iv);
            } else {
                $encryptedMessage = openssl_encrypt($data, $encryptionMethod, $key, OPENSSL_RAW_DATA);
            }
            return bin2hex($encryptedMessage);
        }

        /**
         * Decrypt the data.
         * @param $key string secret key used for encoding
         * @param $data string Binary encoded data.
         * @return string Decrypted data.
         */
        public static function decrypt_data($key, $data)
        {
            $encryptionMethod = "AES-256-CBC";
            $iv = substr($key, 0, 16);

            if (version_compare(phpversion(), '5.3.2', '>')) {
                $decrypted_data = openssl_decrypt($data, $encryptionMethod, $key, OPENSSL_RAW_DATA, $iv);
            } else {
                $decrypted_data = openssl_decrypt($data, $encryptionMethod, $key, OPENSSL_RAW_DATA);
            }
            return $decrypted_data;
        }
        public function required_address_detail_check($package)
        {
            $shipping_required = false;
            $require_postal_code = array('US', 'CA', 'IN', 'UK');
            if (!empty($package['destination'])) {
                $destination = $package['destination'];
                if (!empty($destination['postcode']) || (!empty($destination['city']) && !in_array($destination['country'], $require_postal_code))) {
                    $shipping_required = true;
                }
            }
            return $shipping_required;
        }
        /**
         * Calculate shipping.
         */
        public function calculate_shipping($package = array())
        {

            $this->wc_package = $package;

            if ($this->enabled === 'no' || $this->enabled_rates === 'no') {
                return;
            }
            self::debug(__('PluginHive Debug Mode is On.', 'pluginhive-woocommerce-shipping-services'));
            if (empty(self::$integration_id) || empty(self::$secret_key)) {
                self::debug(__('PluginHive Integration Id or Secret Key Missing.', 'pluginhive-woocommerce-shipping-services'));
                PH_WSS_Common_Functions::ph_wss_admin_diagnostic_report( 'PluginHive Woocommerce Shipping Services : PluginHive Integration Id or Secret Key Missing.');
                return;
            }
            $shipping_required = $this->required_address_detail_check($package);
            if (!$shipping_required) {
                if ($this->debug) {
                    $this->debug(__('Pluginhive WooCommerce Shipping Services : Shipping calculation stopped. Required shipping address fields are missing. Please check shipping address.', 'pluginhive-woocommerce-shipping-services'));
                }
                return;
            }
            $this->found_rates = array();

            if (empty(self::$weight_unit)) {
                self::$weight_unit = get_option('woocommerce_weight_unit');
            }
            if (empty(self::$dimension_unit)) {
                self::$dimension_unit = get_option('woocommerce_dimension_unit');
            }
            if (empty(self::$currency_code)) {
                self::$currency_code = get_woocommerce_currency();
            }

            $formatted_package = self::get_formatted_data($package);

            self::debug('PluginHive Request Package: <a href="#" class="debug_reveal">Reveal</a><pre class="debug_info" style="background:#EEE;border:1px solid #DDD;padding:5px;">' . print_r($formatted_package, true) . '</pre>');
            PH_WSS_Common_Functions::ph_wss_admin_diagnostic_report('PluginHive Request Package :'.print_r($formatted_package,true));

            $encrypted_data = self::encrypt_data(self::$secret_key, json_encode($formatted_package));
            // Format accepted in AddonStation server
            $data = array(
                'data' => $encrypted_data,
                'storeType' => SP_WC_STORE_TYPE,
                'siteUrl' => get_site_url(),
            );

            // Required to get the debug info from api
            if (self::$debug) {
                $data['isDebug'] = true;
            }

            $json_encoded_data = json_encode($data);
            $response = $this->get_rates_from_server($json_encoded_data);

            if ($response !== false) {
                $ratesResult = $this->process_result($response);
            }
            // Handle Fallback rates if no rates returned
            if (empty($this->found_rates) && !empty(self::$fallback_rate)) {
                $shipping_method_detail = new stdClass();
                $shipping_method_detail->ruleName = $this->shipping_title;
                $shipping_method_detail->displayName = $this->shipping_title;
                $shipping_method_detail->rate = self::$fallback_rate;
                $shipping_method_detail->ruleName = $this->shipping_title;
                $shipping_method_detail->ruleId = null;
                $shipping_method_detail->serviceId = null;
                $shipping_method_detail->carrierId = 'fallback_rate';
                $shipping_method_detail->transitMessage = '';
                $shipping_method_detail->transitTime = '';
                $shipping_method_detail->transitType = '';
                $this->prepare_rate($shipping_method_detail);
            }
            $this->add_found_rates();
        }

        /**
         * Get formatted data from woocommerce cart package.
         * @param $package array Package.
         * @return array Formatted package.
         */
        public static function get_formatted_data($package)
        {
            foreach ($package['contents'] as $key => $line_item) {
                $product_data = array();
                $product_data = $line_item['data']->get_data();
                $product_data['product_id'] = !empty($line_item['variation_id']) ? $line_item['variation_id'] : $line_item['product_id'];
                $product_data['weight_unit'] = self::$weight_unit;
                $product_data['dimension_unit'] = self::$dimension_unit;
                $product_data['dimensions'] = array(
                    'length' => $line_item['data']->get_length(),
                    'width' => $line_item['data']->get_width(),
                    'height' => $line_item['data']->get_height(),
                );

                $product_data['weight'] = $line_item['data']->get_weight();
                
                $seller_id = get_post_field('post_author', $line_item['product_id']);
                $info = get_user_meta($seller_id, 'dokan_profile_settings', true);
                $info = is_array($info) ? $info : array();

                $defaults = array(
                    'store_name' => '',
                    'address' => array(),
                );

                $info = wp_parse_args($info, $defaults);
                $info['store_name'] = empty($info['store_name']) ? get_user_by('id', $seller_id)->display_name : $info['store_name'];
                $info['store_address'] = empty($info['address']) ? '' : $info['address'];
                $userdata = get_userdata($seller_id);
                $user_nicename = (!false == $userdata) ? $userdata->user_nicename : '';
                $options = get_option('dokan_general');
                if (isset($options['custom_store_url'])) {
                    $custom_store_url = $options['custom_store_url'];
                } else {
                    $custom_store_url = 'store';
                }
                $url = sprintf('%s/%s/', home_url('/' . $custom_store_url), $user_nicename);
                $store_data = array(
                    'id' => $seller_id,
                    'name' => get_user_by('id', $seller_id)->display_name,
                    'shop_name' => $info['store_name'],
                    'url' => $url,
                    'address' => $info['store_address'],
                );
                $product_data['store'] = $store_data;

                $data_to_send['cart'][] = array(
                    'key' => $key,
                    'product_id' => $line_item['product_id'],
                    'variation_id' => $line_item['variation_id'],
                    'quantity' => $line_item['quantity'],
                    'line_total' => $line_item['line_total'],
                    'product_data' => $product_data,
                );
            }
            try {
                $data_to_send['currency'] = self::$currency_code;
                if (isset($package['cart_subtotal'])) {
                    $data_to_send['cart_subtotal'] = $package['cart_subtotal'];
                }
            } catch (\Throwable $th) {
                //throw $th;
            }

            $data_to_send['destination'] = $package['destination'];
            $data_to_send['reference_id'] = uniqid();

            WC()->session->set('sp_rates_unique_id', $data_to_send['reference_id']);
            return $data_to_send;
        }

        /**
         * Get the rates from AddonStation Server.
         * @param $data string Encrypted data
         * @return
         */
        public function get_rates_from_server($data)
        {

            if (SP_ADVANCE_DEBUG) {
                self::debug('PluginHive Request Data Advance debug: <a href="#" class="debug_reveal">Reveal</a><pre class="debug_info" style="background:#EEE;border:1px solid #DDD;padding:5px;">' . print_r($data, true) . '</pre>');
            }
            // Get the response from server.
            self::debug('PluginHive Response Advance debug: <a href="#" class="debug_reveal">Reveal</a><pre class="debug_info" style="background:#EEE;border:1px solid #DDD;padding:5px;">' . print_r(array("integration_id" => self::$integration_id, "SP_RATE_URL" => SP_RATE_URL), true) . '</pre>');
            $response = wp_remote_post(
                SP_RATE_URL,
                array(
                    'headers' => array(
                        'authorization' => "Bearer " . self::$integration_id,
                        'Content-Type' => "application/json",
                    ),
                    'timeout' => 20,
                    'body' => $data,
                )
            );

            if (SP_ADVANCE_DEBUG) {
                self::debug('PluginHive Response Advance debug: <a href="#" class="debug_reveal">Reveal</a><pre class="debug_info" style="background:#EEE;border:1px solid #DDD;padding:5px;">' . print_r($response, true) . '</pre>');
                PH_WSS_Common_Functions::ph_wss_admin_diagnostic_report('PluginHive Response Advance debug:'.print_r($response,true));                
            }

            // WP_error while getting the response
            if (is_wp_error($response)) {
                $error_string = $response->get_error_message();
                self::debug('PluginHive Response: <a href="#" class="debug_reveal">Reveal</a><pre class="debug_info" style="background:#EEE;border:1px solid #DDD;padding:5px;">' . __('WP Error : ') . print_r($error_string, true) . '</pre>');
                PH_WSS_Common_Functions::ph_wss_admin_diagnostic_report('PluginHive Response Advance debug:'.print_r($error_string,true));                
                return false;
            }

            // Successful response
            if ($response['response']['code'] == '200') {
                $body = $response['body'];
                $body = json_decode($body);
                return $body;
            } else {
                self::debug('PluginHive Response: <a href="#" class="debug_reveal">Reveal</a><pre class="debug_info" style="background:#EEE;border:1px solid #DDD;padding:5px;">' . __('Error Code : ') . print_r($response['response']['code'], true) . '<br/>' . __('Error Message : ') . print_r($response['response']['message'], true) . '</pre>');
                PH_WSS_Common_Functions::ph_wss_admin_diagnostic_report( 'PluginHive Response :    Error Code  :'.print_r($response['response']['code'],true)).'  Error Message  :'.print_r($response['response']['message'],true) ;                  
                return false;
            }
        }

        /**
         * Add debug info to the Front end.
         */
        public static function debug($message, $type = 'notice')
        {
            if (self::$debug) {
                wc_add_notice($message, $type);
            }
        }

        /**
         * Process the Response body received from server.
         */
        public function process_result($body)
        {
            $returnObject = array();
            $deliver_to_po = false;
            if ($body->success && !empty($body->data)) {
                // Decrypt the response
                $decrypted_data = self::decrypt_data(self::$secret_key, hex2bin($body->data));
                $json_decoded_data = json_decode($decrypted_data); // Json decode the decrypted response
                self::$aspTransactionId = $json_decoded_data->aspTransactionId;
                WC()->session->set('asp_shipping_transaction_id', self::$aspTransactionId);
                self::debug('PluginHive Response: <a href="#" class="debug_reveal">Reveal</a><pre class="debug_info" style="background:#EEE;border:1px solid #DDD;padding:5px;">' . __('PluginHive Transaction Id : ') . print_r(self::$aspTransactionId, true) . '<br/><br/>' . print_r($json_decoded_data->info, true) . '</pre>');
                $rates_arr = $json_decoded_data->rates; // Array of rates
                if (is_array($rates_arr)) {
                    foreach ($rates_arr as $rate) {
                        self::prepare_rate($rate);
                    }
                }
            }

            return $returnObject;
        }

        /**
         * Check isset and return empty if no value
         */
        public function check_isset($value)
        {
            return isset($value) ? $value : '';
        }

        /**
         * Prepare the rates.
         * @param $shipping_method_detail object Rate returned from API.
         */
        public function prepare_rate($shipping_method_detail)
        {
            $rate_name = $shipping_method_detail->displayName;
            $rate_cost = $shipping_method_detail->rate;
            $ca_d2po = '';
            $rate_id = $this->id . ':' . $shipping_method_detail->ruleId . ':' . $ca_d2po;
            $this->found_rates[$rate_id] = array(
                'id' => $rate_id,
                'label' => $rate_name,
                'cost' => $rate_cost,
                'taxes' => !empty(self::$tax_calculation_mode) ? '' : false,
                'calc_tax' => self::$tax_calculation_mode,
                'meta_data' => array(
                    'sp_shipping_rates' => array(
                        'sp_transit_message' => isset($shipping_method_detail->transitMessage) ? $shipping_method_detail->transitMessage : '',
                        'transit_time' => isset($shipping_method_detail->transitTime) ? $shipping_method_detail->transitTime : '',
                        'transit_time_type' => isset($shipping_method_detail->transitType) ? $shipping_method_detail->transitType : '',
                        'ruleId' => $shipping_method_detail->ruleId, // Rule Identifier in addonstation account
                        'uniqueId' => WC()->session->get('sp_rates_unique_id'), // Unique Id used while communicating with server
                        'serviceId' => $shipping_method_detail->serviceId, //
                        'carrierId' => $shipping_method_detail->carrierId, //
                        'aspTransactionId' => self::$aspTransactionId,
                    ),
                ),
            );
        }

        /**
         * Add found rates to woocommerce shipping rate.
         */
        public function add_found_rates()
        {
            foreach ($this->found_rates as $key => $rate) {
                $this->add_rate($rate);
            }
        }
    }
}
