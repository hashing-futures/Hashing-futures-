<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * Shipment Tracking Main Class.
 */
if (!class_exists('SP_Woocommerce_Api')) {
	class SP_Woocommerce_Api
	{

		/**
		 * Constructor of SP_Woocommerce_Api class.
		 */
		public function __construct()
		{
			add_action('rest_api_init', array($this, 'site_url'), 100);
			add_action('rest_api_init', array($this, 'products_list_api'), 100);
			add_action('rest_api_init', array($this, 'products_variations_list_api'), 100);
			add_action('rest_api_init', array($this, 'products_shipping_classes_list_api'), 100);
			add_action('rest_api_init', array($this, 'orders_list_api'), 100);
			add_action('rest_api_init', array($this, 'webhook_api'), 100);
			add_action('rest_api_init', array($this, 'stores_list_api'), 100);
			add_action('rest_api_init', array($this, 'user_list_api'), 100);
		}

		/**
		 * Initialize rest api for site url.
		 */
		public function site_url()
		{
			if (!class_exists('SP_Site_URL_API')) {
				require_once 'sp-site-url-api.php';
			}
			$obj = new SP_Site_URL_API();
			$obj->register_routes();
		}

		/**
		 * Initialize rest api for products list.
		 */
		public function products_list_api()
		{
			if (!class_exists('SP_products_list_API')) {
				require_once 'sp-products-list-api.php';
			}
			$obj = new SP_products_list_API();
			$obj->register_routes();
		}

		/**
		 * Initialize rest api for products variations list.
		 */
		public function products_variations_list_api()
		{
			if (!class_exists('SP_products_variations_list_API')) {
				require_once 'sp-products-variations-list-api.php';
			}
			$obj = new SP_products_variations_list_API();
			$obj->register_routes();
		}

		/**
		 * Initialize rest api for products variations list.
		 */
		public function products_shipping_classes_list_api()
		{
			if (!class_exists('SP_products_shipping_classes_list_API')) {
				require_once 'sp-products-shipping_classes-list-api.php';
			}
			$obj = new SP_products_shipping_classes_list_API();
			$obj->register_routes();
		}

		/**
		 * Initialize rest api for orders list.
		 */
		public function orders_list_api()
		{
			if (!class_exists('SP_orders_list_API')) {
				require_once 'sp-orders-list-api.php';
			}
			$obj = new SP_orders_list_API();
			$obj->register_routes();
		}

		/**
		 * Initialize rest api for webhook.
		 */
		public function webhook_api()
		{
			if (!class_exists('ASP_webhook_API')) {
				require_once 'sp-webhook-api.php';
			}
			$obj = new ASP_webhook_API();
			$obj->register_routes();
		}

		public function stores_list_api()
		{
			if (!class_exists('Sp_mv_stores_list')) {
				require_once 'sp-stores-list-api.php';
			}
			$obj = new Sp_mv_stores_list();
			$obj->register_routes();

			if(class_exists('WCFM_REST_Controller')){
				if (!class_exists('Sp_wcfm_mv_stores_list')) {
					require_once 'sp-wcfm-mv-stores-list-api.php';
				}
				$obj = new Sp_wcfm_mv_stores_list();
				$obj->register_routes();
			}

			if (!class_exists('Sp_mv_auto_sync')) {
				require_once 'sp-auto-sync-vendors-api.php';
			}
			$obj = new Sp_mv_auto_sync();
			$obj->register_routes();
		}

		public function user_list_api()
		{
			if (!class_exists('Sp_User_List_API')) {
				require_once 'sp-user-list-api.php';
			}
			$obj = new Sp_User_List_API();
			$obj->register_routes();
		}
	}
}
