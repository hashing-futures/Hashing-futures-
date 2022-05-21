<?php
/*
	Plugin Name: PluginHive WooCommerce Shipping Services
	Plugin URI: 
	Description: Get the WooCommerce shipping rates based on rules configured in your Account. Also get tracking information from the plugin into your WooCommerce Orders.
	Version: 1.2.1
	Author: PluginHive
	Author URI: https://www.pluginhive.com/
	Copyright: PluginHive
    Text Domain: pluginhive-woocommerce-shipping-services
	WC requires at least: 3.0.0
	WC tested up to: 6.3.1
*/


if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (!defined('SP_PLUGIN_ID')) {
	define('SP_PLUGIN_ID', 'woocommerce_pluginhive_settings');
}

if (!class_exists("PH_WSS_Common_Functions")) {
	require_once 'ph-wss-common-functions.php';
}



/*
 * Common Classes.
 */
if (!class_exists("Pluginhive_Shipping_Rates_Common")) {
	require_once 'class-pluginhive-shipping-rates-common.php';
}

try {
	if (in_array('dokan-lite/dokan.php', get_option('active_plugins'))) {
		// error_log(print_r("dokan lite if", 1));
		require_once  'includes/dokan/functions.php';

		add_action('dokan_loaded',  'remove_dokan_split_cart', 15);

		function remove_dokan_split_cart()
		{
			try {
        $settings = get_option('woocommerce_pluginhive_woocommerce_shipping_settings', true);
        // error_log(print_r($settings, 1));
				if (!empty($settings) && isset($settings['enabled_rates']) && $settings['enabled_rates'] === 'yes') {
          // error_log(print_r('Removing dokan split cart......', 1));
					remove_filter('woocommerce_cart_shipping_packages', 'dokan_custom_split_shipping_packages', 10);
					remove_filter('woocommerce_shipping_package_name', 'dokan_change_shipping_pack_name', 10);
					remove_action('woocommerce_checkout_create_order_shipping_item', 'dokan_add_shipping_pack_meta', 10);
				}
			} catch (\Throwable $th) {
				//throw $th;
			}
			// error_log('remove_dokan_split_cart');
		}
	}
} catch (Exception $e) {
	//throw $th;
}

try {
	$activePlugins = get_option('active_plugins');
	// error_log(print_r($activePlugins, true));
	if (in_array('wc-multivendor-marketplace/wc-multivendor-marketplace.php', get_option('active_plugins')) && in_array('wc-frontend-manager/wc_frontend_manager.php', get_option('active_plugins'))) {

		require_once  'includes/wcfm/functions.php';
	}
} catch (Exception $e) {
	//throw $th;
}

register_activation_hook(__FILE__, function () {
	$woocommerce_plugin_status = Pluginhive_Shipping_Rates_Common::woocommerce_active_check();	// True if woocommerce is active.
	if ($woocommerce_plugin_status === false) {
		deactivate_plugins(basename(__FILE__));
		wp_die(__("Oops! You tried installing the plugin to get woocommerce shipping rates without activating woocommerce. Please install and activate woocommerce and then try again .", "pluginhive-woocommerce-shipping-services"), "", array('back_link' => 1));
	}
	else {
		if (!class_exists("SP_Plugin_Activation")) {
		 require_once 'includes/settings/sp-plugin-activation.php';
     }
	 $activationObject = new SP_Plugin_Activation();
	 $activationObject->sp_send_activation_notification();
	}
});

/**
 * Advance Debug Mode ( For Developer debug ).
 */
if (!defined('PLUGINHIVE_ADVANCE_DEBUG')) {
	define('PLUGINHIVE_ADVANCE_DEBUG', false);
}

/**
 * PluginHive woocommerce shipping services root directory path.
 */
if (!defined('PLUGINHIVE_WC_RATE_PLUGIN_ROOT_DIR')) {
	define('PLUGINHIVE_WC_RATE_PLUGIN_ROOT_DIR', __DIR__);
}

/**
 * PluginHive woocommerce shipping services root file.
 */
if (!defined('PLUGINHIVE_WC_RATE_PLUGIN_ROOT_FILE')) {
	define('PLUGINHIVE_WC_RATE_PLUGIN_ROOT_FILE', __FILE__);
}

/**
 * PluginHive woocommerce shipping services rates api.
 */
if (!defined("PLUGINHIVE_WC_RATE_URL")) {
	// define("PLUGINHIVE_WC_RATE_URL", "http://localhost:3001/api/v1/storepep/rates/");
	//define("PLUGINHIVE_WC_RATE_URL", "https://api-ship.storepep.com/api/v1/storepep/rates/");
	// define("PLUGINHIVE_WC_RATE_URL", "https://beta-api-ship.storepep.com/api/v1/storepep/rates/");
	define("PLUGINHIVE_WC_RATE_URL", "https://mcsl-w-api.pluginhive.com/api/v1/storepep/rates/");
}

/**
 * PluginHive woocommerce shipping services Mv Token.
 */
if (!defined("PLUGINHIVE_WC_VENDOR_TOKEN_URL")) {
	// define("PLUGINHIVE_WC_VENDOR_TOKEN_URL", "http://localhost:3001/api/v1/wcmv/token/");
	// define("PLUGINHIVE_WC_VENDOR_TOKEN_URL", "http://beta-api-ship.storepep.com/api/v1/wcmv/token/");
	define("PLUGINHIVE_WC_VENDOR_TOKEN_URL", "https://mcsl-w-api.pluginhive.com/api/v1/wcmv/token/");
	// define("PLUGINHIVE_WC_VENDOR_TOKEN_URL", "https://api-ship.storepep.com/api/v1/wcmv/token/");
}

/**
 * PluginHive woocommerce shipping services MV UI URL.
 */
if (!defined("PLUGINHIVE_WC_MV_UI_URL")) {
	// define("PLUGINHIVE_WC_MV_UI_URL", "http://localhost:6002/main.js");
	// define("PLUGINHIVE_WC_MV_UI_URL", "https://mv-ui.storepep.com/main.js");
	// define("PLUGINHIVE_WC_MV_UI_URL", "https://mv-ui.storepep.com/main-beta.js");
	define("PLUGINHIVE_WC_MV_UI_URL", "https://mv-ui.storepep.com/main-w-mcsl.js");
	//define("PLUGINHIVE_WC_MV_UI_URL", "https://mv-ui.storepep.com/main-local.js");
}

/**
 *
 *  * PluginHive woocommerce shipping services Canada Post office list api. PluginHive woocommerce shipping services Canada Post office list api.
 */
if (!defined("PLUGINHIVE_WC_CANADA_POST_POST_OFFICE_LIST_URL")) {
	// define("PLUGINHIVE_WC_CANADA_POST_POST_OFFICE_LIST_URL", "http://localhost:3001/api/v1/storepep/canadapost/postoffice");
	// define("PLUGINHIVE_WC_CANADA_POST_POST_OFFICE_LIST_URL", "https://api-ship.storepep.com/api/v1/storepep/canadapost/postoffice");
	// define("PLUGINHIVE_WC_CANADA_POST_POST_OFFICE_LIST_URL", "https://beta-api-ship.storepep.com/api/v1/storepep/canadapost/postoffice");
	define("PLUGINHIVE_WC_CANADA_POST_POST_OFFICE_LIST_URL", "https://mcsl-w-api.pluginhive.com/api/v1/storepep/canadapost/postoffice");
}

/**
 * PluginHive woocommerce shipping services account register api.
 */
if (!defined("PLUGINHIVE_WC_ACCOUNT_REGISTER_ENDPOINT")) {
	// define("PLUGINHIVE_WC_ACCOUNT_REGISTER_ENDPOINT", "http://localhost:3000/api/v1/storepepconnector/register");
	// define("PLUGINHIVE_WC_ACCOUNT_REGISTER_ENDPOINT", "https://api-ship.storepep.com/api/v1/storepepconnector/register");
	// define("PLUGINHIVE_WC_ACCOUNT_REGISTER_ENDPOINT", "https://beta-api-ship.storepep.com/api/v1/storepepconnector/register");
	define("PLUGINHIVE_WC_ACCOUNT_REGISTER_ENDPOINT", "https://mcsl-w-api.pluginhive.com/api/v1/storepepconnector/register");
}

/**
 * Storetype defined in PluginHive woocommerce shipping services api, Required to identify the Store type (Woocommerce or Magento etc) in server.
 */
if (!defined("PLUGINHIVE_WC_STORE_TYPE")) {
	define("PLUGINHIVE_WC_STORE_TYPE", "S1");			// S1 for woocommerce defined in storepep rates api
}

if (!defined('SP_ROOT_DIR')) {
	define('SP_ROOT_DIR', __DIR__);
}

/**
 * AddonStation root file.
 */
if (!defined('SP_ROOT_FILE')) {
	define('SP_ROOT_FILE', __FILE__);
}

if (!defined('SP_API_BASE_URL')) {
	// define('SP_API_BASE_URL', 'http://localhost:3001');
	//  define('SP_API_BASE_URL', 'https://beta-api-ship.s÷torepep.com');
	define('SP_API_BASE_URL', 'https://mcsl-w-api.pluginhive.com');
	//define('SP_API_BASE_URL', 'https://api-ship.storepep.com');
}

if (!defined('SP_UI_URL')) {
	// define('SP_UI_URL', 'http://localhost:4000');
	//  define('SP_UI_URL', 'https://beta-ship.storepep.com');
	define('SP_UI_URL', 'https://mcsl-w.pluginhive.com');
	//define('SP_UI_URL', 'https://ship.storepep.com');
}

/**
 * AddoPluginHive woocommerce shipping servicesnStation rates api.
 */
if (!defined("SP_RATE_URL")) {
	define("SP_RATE_URL", SP_API_BASE_URL . "/api/v1/sp/rates");
}



/**
 * PluginHive woocommerce shipping services WC Token.
 */
if (!defined("SP_WC_ADMIN_TOKEN_URL")) {
	define("SP_WC_ADMIN_TOKEN_URL", SP_API_BASE_URL . "/api/v1/connector/mc/token");
}


/**
 * AddonSPluginHive woocommerce shipping servicestation account register api.
 */
if (!defined("SP_ACCOUNT_REGISTER_ENDPOINT")) {
	define("SP_ACCOUNT_REGISTER_ENDPOINT", SP_API_BASE_URL . "/api/v1/connector/mc_register");
}

if (!defined("SP_ACCOUNT_RESYNC_ENDPOINT")) {
	define("SP_ACCOUNT_RESYNC_ENDPOINT", SP_API_BASE_URL . "/api/v1/connector/resync");
}

/**
 * PluginHive woocommerce shipping services sync store api.
 */
if (!defined("SP_SYNC_STORE_ENDPOINT")) {
	define("SP_SYNC_STORE_ENDPOINT", SP_API_BASE_URL . "/api/v1/connector/sync/store");
}

/**
 * PluginHive woocommerce shipping services sync address api.
 */
if (!defined("SP_SYNC_ADDRESS_ENDPOINT")) {
	define("SP_SYNC_ADDRESS_ENDPOINT", SP_API_BASE_URL . "/api/v1/connector/sync/address");
}

if (!defined("SP_ACCOUNT_DELETION_REQUEST_ENDPOINT")) {
	define("SP_ACCOUNT_DELETION_REQUEST_ENDPOINT", SP_API_BASE_URL . "/api/v1/connector/delete_account");
}

if (!defined('SP_ADVANCE_DEBUG')) {
	define('SP_ADVANCE_DEBUG', false);
}

if (!defined("SP_WC_STORE_TYPE")) {
	define("SP_WC_STORE_TYPE", "S1"); // S1 for woocommerce defined in PluginHive woocommerce shipping services rates api
}


/**
 * PluginHive woocommerce shipping services uninstall notification API
 */
if (!defined("SP_UNINSTALL_NOTIFICATION_ENDPOINT")) {
    define("SP_UNINSTALL_NOTIFICATION_ENDPOINT", SP_API_BASE_URL . "/api/v1/connector/uninstall");
}
register_deactivation_hook(__FILE__, 'sp_send_uninstall_notification');
function sp_send_uninstall_notification() {
    if (!class_exists("SP_Plugin_Uninstallation")) {
        require_once 'includes/settings/sp-plugin-uninstallation.php';
    }
    $uninstallObject = new SP_Plugin_Uninstallation();
    $uninstallObject->sp_send_uninstallation_notification();

}

/**
 * PluginHive woocommerce shipping services install notification API
 */
if (!defined("SP_ACTIVATION_NOTIFICATION_ENDPOINT")) {
    define("SP_ACTIVATION_NOTIFICATION_ENDPOINT", SP_API_BASE_URL . "/api/v1/connector/activation");
}

/**
 * PluginHive woocommerce shipping services.
 */
if (!class_exists("Pluginhive_Woocommerce_Shipping")) {
	/**
	 * Shipping Calculator Class.
	 */
	class Pluginhive_Woocommerce_Shipping
	{

		/**
		 * Constructor
		 */
		public function __construct()
		{
			// Handle links on plugin page
			add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'sp_plugin_action_links'));
			// Initialize the shipping method
			add_action('woocommerce_shipping_init', array($this, 'sp_shipping_init'));
			// Register the shipping method
			add_filter('woocommerce_shipping_methods', array($this, 'sp_shipping_methods'));
			add_action('woocommerce_thankyou', array($this, 'add_asp_transaction_id_to_order_note'));
			add_filter('plugin_row_meta', array($this, 'add_woocommerce_shipping_services'), 10, 2);

			// add_action('admin_enqueue_scripts', array( $this, 'scripts' ));
			require_once 'includes/sp-register-admin-settings.php';
			require_once 'includes/admin-nav/sp-admin-nav-link.php';
		}

		// public function scripts(){
		//     wp_register_script( 'sp-admin-script', plugins_url( '/resources/js/sp-admin-settings.js', __FILE__ ));
		// }

		public function add_woocommerce_shipping_services($plugin_meta, $pluginFile)
		{
			// $plugin_meta[] = sprintf('<a href="%s">%s</a>', esc_attr('https://pluginhive.com'), 'WooCommerce Shipping Services');
			// return $plugin_meta;
			if( $pluginFile == 'pluginhive-woocommerce-shipping-services/pluginhive-woocommerce-shipping-services.php' ) {
				$plugin_meta[] = sprintf('<a href="%s">%s</a>', esc_attr('https://www.pluginhive.com/woocommerce-shipping-services/'), 'WooCommerce Shipping Services');
			}
			return $plugin_meta;
		}
		public static function sp_plugin_configuration()
		{
			return array(
				'id' => 'pluginhive_woocommerce_shipping',
				'method_title' => __('PluginHive WooCommerce Shipping Services', 'pluginhive-woocommerce-shipping-services'),
				'method_description' => __("Seamlessly integrate all the top shipping carriers like FedEx, UPS, Stamps.com(USPS), DHL and many more to WooCommerce. It displays live shipping rates, generates shipping labels & provides live shipment tracking.", 'pluginhive-woocommerce-shipping-services') . '<br/><br/>' . 'Supported Shipping Carriers <a href="https://pluginhive.com" target="_blank">' . __('Click Here', 'pluginhive-woocommerce-shipping-services') . '</a>',
			);
		}

		/**
		 * Shipping Initialization.
		 */

		public function sp_shipping_init()
		{
			if (!class_exists("SP_Shipping_Method")) {
				require_once 'includes/sp-shipping-method.php';
			}
			$shipping_obj = new SP_Shipping_Method();

			$this->plugin_settings = get_option('woocommerce_pluginhive_settings', array());
			if (!class_exists('Ph_Canada_Post_Deliver_To_Post_Office'))
				require_once 'includes/class-pluginhive-deliver-to-post-office.php';
			new Ph_Canada_Post_Deliver_To_Post_Office($this->plugin_settings);
		}

		/**
		 * Register Shipping Method to woocommerce.
		 */
		public function sp_shipping_methods($methods)
		{
			$methods[] = 'SP_Shipping_Method';
			return $methods;
		}

		/**
		 * Plugin action links on Plugin page.
		 */
		public function sp_plugin_action_links($links)
		{
			$plugin_links = array(
				'<a href="' . admin_url('admin.php?page=wc-settings&tab=shipping&section=pluginhive_woocommerce_shipping') . '">' . __('Settings', 'pluginhive-woocommerce-shipping-services') . '</a>',
				'<a href="https://pluginhive.com">' . __('Documentation', 'pluginhive-woocommerce-shipping-services') . '</a>',
			);
			return array_merge($plugin_links, $links);
		}

		public function add_asp_transaction_id_to_order_note($order_id)
		{
			$aspTransactionId = WC()->session->get('asp_shipping_transaction_id');
			if (!empty($aspTransactionId)) {
				$order = wc_get_order($order_id);
				$order->add_order_note(__('PluginHive Transaction Id : ', '') . $aspTransactionId, 0, 1);
			}
		}

		/**
		 * Register Shipping Method to woocommerce.
		 */
		// public function pluginhive_woocommerce_shipping_methods($methods)
		// {
		// 	$methods[] = 'pluginhive_woocommerce_shipping_Method';
		// 	return $methods;
		// }

		/**
		 * Add StorePep Transaction Id to Order note.
		 * @param $order_id int Order Id.
		 */
		public function add_storepep_transaction_id_to_order_note($order_id)
		{
			$storepepTransactionId = WC()->session->get('storepep_shipping_transaction_id');
			if (!empty($storepepTransactionId)) {
				$order = wc_get_order($order_id);
				$order->add_order_note(__('StorePep Transaction Id : ', '') . $storepepTransactionId, 0, 1);
			}
		}
	}

	new Pluginhive_Woocommerce_Shipping();
}

// if (!class_exists('Storepep_Woocommerce_Api')) {
// 	require_once 'includes/api/class-storepep-woocommerce-api.php';
// }
// new Storepep_Woocommerce_Api();

if (!class_exists('SP_Woocommerce_Api')) {
	require_once 'includes/api/sp-woocommerce-api.php';
}
new SP_Woocommerce_Api();

/**
 * Include Shipment Tracking Functionality.
 */
// if (!class_exists('Storepep_Woocommerce_Shipment_Tracking')) {
// 	require_once 'includes/tracking/class-storepep-woocommerce-shipment-tracking.php';
// }
// new Storepep_Woocommerce_Shipment_Tracking();

if (!class_exists('SP_Woocommerce_Shipment_Tracking')) {
	require_once 'includes/tracking/sp-woocommerce-shipment-tracking.php';
}
new SP_Woocommerce_Shipment_Tracking();

// Plugin updater
require 'plugin-update-checker/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://bitbucket.org/pluginhive/pluginhive-woocommerce-shipping-services/',
	__FILE__,
	'pluginhive-woocommerce-shipping-services'
);

$myUpdateChecker->setAuthentication(array(
	'consumer_key' => 'nXGsrkFGPS86v9eHnK',
	'consumer_secret' => 'G5SHXQtbg8Yp9dpkuMhSmvLYQWkhG97x',
));

add_action('init', 'load_pluginhive_vendor_view');

function load_pluginhive_vendor_view()
{
	//PH_WSS_Common_Functions::ph_wss_admin_diagnostic_report(sprintf( 'Check Dokan WCFM are Available :'.print_r($_GET,true))); 
	if (isset($_GET['PluginHive-Wcfm'])) {
		$plugin_path = trailingslashit(dirname(__FILE__));
		require_once($plugin_path . 'includes/wcfm/pluginhive.php');
		//echo 'storepep';
		die();
	}
	if (isset($_GET['PluginHive-Dokan'])) {
		$plugin_path = trailingslashit(dirname(__FILE__));
		require_once($plugin_path . 'includes/dokan/pluginhive.php');
		//echo 'storepep';
		die();
	}
}
