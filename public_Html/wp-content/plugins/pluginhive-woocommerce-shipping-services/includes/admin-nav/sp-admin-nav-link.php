<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('SP_Admin_Nav_Link')) {
    class SP_Admin_Nav_Link
    {
        public function __construct()
        {
            add_action('in_admin_header', function () {
                remove_all_actions('admin_notices', 999999);
                remove_all_actions('all_admin_notices', 999999);
            }, 99999999);
            add_action('admin_menu', array($this, 'asp_shipping_admin_menu'));
            if (!class_exists("PH_WSS_Common_Functions")) {
                require_once '../../ph-wss-common-functions.php';
            }
        }

        protected function get_asp_token($plugin_settings)
        {
            $requestBody = [];
            $requestBody['accountId'] = $plugin_settings['asp_account_id'];
            $headers = [];
            $headers['authorization'] = 'Bearer ' . $plugin_settings['integration_id'];
            $response_body = PH_WSS_Common_Functions::make_post_request(SP_WC_ADMIN_TOKEN_URL, $requestBody, $headers);
            if ($response_body && isset($response_body->success) && $response_body->success) {
                return $response_body->token;
            }
            return false;
        }
        public function asp_shipping_admin_menu()
        {
            $plugin_settings = get_option(SP_PLUGIN_ID, true);
            if (isset($plugin_settings['enabled']) && $plugin_settings['enabled'] == 'yes') {
                add_menu_page(
                    SP_PLUGIN_ID,
                    __('Shipping', 'pluginhive-woocommerce-shipping-services'),
                    'manage_woocommerce',
                    SP_PLUGIN_ID,
                    array($this, 'sp_add_edit_product_addon'),
                    'dashicons-screenoptions',
                    30
                );
            }
        }

        protected function load_error()
        {
            echo "<div><h1>Unable to load WooCommerce Shipping Rates, Labels and Tracking. Please check if the settings is completed or contact <a href='https://pluginhive.com'>pluginhive.com</h1></div>";
            PH_WSS_Common_Functions::ph_wss_admin_diagnostic_report('PluginHive ERROR message : Unable to load WooCommerce Shipping Rates, Labels and Tracking. Please check if the settings is completed');
            exit;
        }

        protected function load_Iframe($plugin_settings)
        {
            $token = $this->get_asp_token($plugin_settings);
            PH_WSS_Common_Functions::ph_wss_admin_diagnostic_report('PluginHive Token :'.print_r($token,true));
            if ($token) {
                echo '<style>
                #wpcontent{
                  padding: 6px;
                }
                .update-nag, .updated, .error, .is-dismissible .notice{ display: none; }
                </style>';
                echo "<div style='width:100%; height: 100%;'>";
                echo "<iframe style='width:100%; height: 100vh;' src='" . SP_UI_URL . "?sp_token=" . $token . "' ></iframe>";
                echo "</div>";
            } else {
                $this->load_error();
            }
        }

        protected function check_If_ASP_can_be_loaded($plugin_settings)
        {
            return (isset($plugin_settings['asp_account_id']) && $plugin_settings['asp_account_id']
                &&
                isset($plugin_settings['integration_id']) && $plugin_settings['integration_id']
                &&
                isset($plugin_settings['secret_key']) && $plugin_settings['secret_key']
                &&
                isset($plugin_settings['store_uuid']) && $plugin_settings['store_uuid']);
        }

        public function sp_add_edit_product_addon()
        {
            $plugin_settings = get_option(SP_PLUGIN_ID, true);
            if (isset($plugin_settings['enabled']) && $plugin_settings['enabled'] == 'yes') {
                if ($this->check_If_ASP_can_be_loaded($plugin_settings)) {
                    $this->load_Iframe($plugin_settings);
                } else {
                    $this->load_error();
                }
            }
        }
    }

    new SP_Admin_Nav_Link();
}
