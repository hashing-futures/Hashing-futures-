<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;	// exit if directly accessed
}

if (is_admin() && !empty($_GET['section']) && $_GET['section'] == 'pluginhive_woocommerce_shipping') {
	wp_enqueue_script( 'sp-admin-script', plugins_url( '/resources/js/scripts.js', __FILE__ ), array( 'jquery' ) );
	$settings   = array(
		'ajaxUrl'	=>	admin_url('admin-ajax.php'),										
	    'storeData'	=>  json_encode(get_option(SP_PLUGIN_ID, true)),
		'spNavLink' =>  admin_url('admin.php?page=woocommerce_pluginhive_settings'),
		'site_URL'  =>   get_site_url(),
	);
	wp_localize_script( 'sp-admin-script', 'sp_admin_script', $settings);

}

$logged_in_user_email_id = null;
if( is_admin() && ! empty($_GET['section']) && $_GET['section'] == 'pluginhive_woocommerce_shipping' ) {
	$logged_in_user_email_id = Pluginhive_Shipping_Rates_Common::get_current_user_email_id();
}

$urlToCreateKeys = admin_url('admin.php?page=wc-settings&tab=advanced&section=keys&create-key=1');

$orderStatusArray = wc_get_order_statuses();
$excluded_statuses = array(
	'wc-cancelled',
	'wc-failed',
	'wc-processing',
	'wc-on-hold',
	'wc-refunded',
);

foreach($orderStatusArray as $key => $status){
    if(in_array( esc_attr( $key ), $excluded_statuses ) ) {
        unset($orderStatusArray[$key]);
    }
}

// Settings
return array(
	'store_settings'			=> array(
		'title'		   	=> __('Store Setup', 'pluginhive-woocommerce-shipping-services'),
		'type'			=> 'title',
		//'description'	=> __('Click on register to create your account')
	),
	'enabled'			=> array(
		'title'		   	=> __('Active', 'pluginhive-woocommerce-shipping-services'),
		'type'			=> 'checkbox',
		'label'			=> __('Enable', 'pluginhive-woocommerce-shipping-services'),
		'default'		=> 'yes',
		'class'			=> 'disable',
	),
	// 'consumer_key'	=> array(
	// 	'title'			=> __('WooCommerce Consumer Key', 'woocommerce-shipping-rates-labels-and-tracking'),
	// 	'type'			=> 'text',
	// 	'description'	=> __("Required for AddonStation Account Authentication. Get it from ", 'woocommerce-shipping-rates-labels-and-tracking') . '<a href="' . $urlToCreateKeys . '" target="_blank">' . __('Here', 'woocommerce-shipping-rates-labels-and-tracking') . '</a>',
	// 	// 'desc_tip'		=> true,
	// ),
	// 'consumer_secret'		=> array(
	// 	'title'			=> __('WooCommerce Consumer Secret', 'woocommerce-shipping-rates-labels-and-tracking'),
	// 	'type'			=> 'text',
	// 	'description'	=> __("Required for AddonStation Account Authentication. Get it from ", 'woocommerce-shipping-rates-labels-and-tracking') . '<a href="' . $urlToCreateKeys . '" target="_blank">' . __('Here', 'woocommerce-shipping-rates-labels-and-tracking') . '</a>',
	// 	// 'desc_tip'		=> true,
	// ),
	'other_settings'			=> array(
		'title'		   	=> __('Settings', 'pluginhive-woocommerce-shipping-services'),
		'type'			=> 'title',
	),
	'enabled_rates'			=> array(
		'title'		   	=> __('Realtime Rates', 'pluginhive-woocommerce-shipping-services'),
		'type'			=> 'checkbox',
		'label'			=> __('Enable', 'pluginhive-woocommerce-shipping-services'),
		'default'		=> 'yes',
	),
	'debug'		=> array(
		'title'		   	=> __('Debug Mode', 'pluginhive-woocommerce-shipping-services'),
		'type'			=> 'checkbox',
		'label'			=> __('Enable', 'pluginhive-woocommerce-shipping-services'),
		'description'	=> __('Enable debug mode to show debugging information on your cart/checkout.', 'pluginhive-woocommerce-shipping-services'),
		'desc_tip'		=>	true,
		'default'		=> 'no',
	),
	'tax_calculation_mode'		=> array(
		'title'		   	=> __('Tax Calculation', 'pluginhive-woocommerce-shipping-services'),
		'type'			=> 'select',
		'description'	=> __('Select Tax Calculation for shipping rates as your requirement.', 'pluginhive-woocommerce-shipping-services'),
		'desc_tip'		=>	true,
		'default'		=> null,
		'options'	 => array(
			'per_order' 	=> __('Taxable', 'pluginhive-woocommerce-shipping-services'),
			null			=> __('None', 'pluginhive-woocommerce-shipping-services'),
		),
	),
	'fallback_rate'		=> array(
		'title'			=> __('Fallback Rate', 'pluginhive-woocommerce-shipping-services'),
		'type'			=> 'text',
		'default'		=> '10',
		'description'	=> __("If no rate returned by PluginHive account then this fallback rate will be displayed. Shipping Method Title will be used as Service name.", 'woocommerce-shipping-rates-labels-and-tracking'),
		'desc_tip'		=> true,
    ),
	'change_status'  => array(
		'title'			=> __( 'Mark Shipped Orders as', 'pluginhive-woocommerce-shipping-services' ),
		'type'			=> 'select',
		'default'		=> 'wc-completed',
		'options'	    => $orderStatusArray,
		'description'	=> __("Once the orders are marked as Shipped, change the WooCommerce order status to the one selected in this option.", 'pluginhive-woocommerce-shipping-services'),
		'desc_tip'		=> true,
	),
  'show_clear_plugin_data_button'		=> array(
		'title'			=> __('Delete WSS Account And Clear Plugin Data Button', 'pluginhive-woocommerce-shipping-services'),
    'type'			=> 'checkbox',
    'label'			=> __('Enable', 'pluginhive-woocommerce-shipping-services'),
    //'description'	=> __('Enable debug mode to show debugging information on your cart/checkout.', 'pluginhive-woocommerce-shipping-services'),
		'description'	=> __("Enable this if you want to delete wss account and clear plugin data", 'pluginhive-woocommerce-shipping-services'),
    'desc_tip'		=> true,
    'default'		=> false,
  ),
  'clear_button'		=> array(
		// 'title'			=> __('Start Setup', 'woocommerce-shipping-rates-labels-and-tracking'),
		'type'			=> 'button',
	),
	'setup_button'		=> array(
		// 'title'			=> __('Start Setup', 'woocommerce-shipping-rates-labels-and-tracking'),
		'type'			=> 'button',
	),
);

