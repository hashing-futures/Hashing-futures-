<?php

/**
 * 
 *
 * Handles requests to the wc/asp/v1.
 * @author   AddonStation
 * @category API
 */
if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SP_Site_URL_API')) {
	/**
	 * Tracking REST API controller class.
	 * @package WooCommerce/API
	 * @extends WC_REST_Controller
	 */
	class SP_Site_URL_API extends WC_REST_Controller
	{

		/**
		 * Endpoint namespace.
		 *
		 * @var string
		 */
		protected $namespace = 'wc/asp/v1';

		/**
		 * Route base.
		 *
		 * @var string
		 */
		protected $rest_base = 'site_url';

		/**
		 * Post type.
		 *
		 * @var string
		 */

		/**
		 * Register the routes for order notes.
		 */
		public function register_routes()
		{
			register_rest_route($this->namespace, '/' . $this->rest_base, array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array($this, 'get_site_info'),
					'permission_callback' => array($this, 'get_items_permissions_check'),
					'args'                => array(),
				),
				'schema' => array($this, 'get_public_item_schema'),
			));
		}
		/**
		 * Check if a given request has write access.
		 *
		 * @param  WP_REST_Request $request Full details about the request.
		 *
		 * @return bool|WP_Error
		 */
		public function get_items_permissions_check($request)
		{
			if (!wc_rest_check_user_permissions('read')) {
				return new WP_Error('woocommerce_rest_cannot_view', __('Sorry, you cannot list resources.', 'woocommerce'), array('status' => rest_authorization_required_code()));
			}

			return true;
		}

		public function get_site_info()
		{
			$site_url = get_site_url();
			return array('url' => $site_url);
		}
	}
}
