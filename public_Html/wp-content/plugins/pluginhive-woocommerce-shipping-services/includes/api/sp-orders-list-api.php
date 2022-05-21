<?php


class SP_orders_list_API extends WP_REST_Controller
{
	/**
	 * Endpoint namespace
	 *
	 * @var string
	 */
	protected $namespace = 'wc/asp/v1';

	/**
	 * Route name
	 *
	 * @var string
	 */
	protected $rest_base = 'orders';

	/**
	 * Stores the request.
	 * @var array
	 */

	/**
	 * Load autometically when class initiate
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function __construct()
	{
	}

	/**
	 * Register the routes for orders.
	 */

	public function register_routes()
	{
		\register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array($this, 'get_orders'),
					'permission_callback' => array($this, 'get_items_permissions_check'),
					'args'                => $this->get_collection_params(),
				),
				//'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		\register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __('Unique identifier for the resource.', 'woocommerce'),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => 'GET',
					'callback'            => array($this, 'get_order'),
					'permission_callback' => array($this, 'get_items_permissions_check'),
					'args'                => array(
						'context' => $this->get_context_param(
							array(
								'default' => 'view',
							)
						),
					),
				),
				//'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Check if a given request has access to read items.
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|boolean
	 */
	public function get_items_permissions_check($request)
	{
		//$token = WP_REST_Request::get_headers($request);
		$token = $request->get_header('authorizationToken');
		//error_log(print_r($token, 1));
		$plugin_settings_from_db = get_option(SP_PLUGIN_ID, true);
		//error_log(print_r($plugin_settings_from_db, 1));
		if ($token !== $plugin_settings_from_db['api-token']) {
			return false; //new \WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce' ), array( 'status' => \rest_authorization_required_code() ) );
		}
		return true;
	}


	public function get_orders($request)
	{
		//error_log(print_r($request, 1));
		$args = array(
			'limit'   => $request['per_page'],
			'page'    => $request['page'],
			'after'   => $request['after'],
			'status'  => $request['status']
		);
		// $query = new WC_Order_Query($args);
		// $orders_query = $query->get_orders();
		$orders_query = wc_get_orders($args);
		$orders = array();
		foreach ($orders_query as $order) {
			//$order_obj = $order->get_data();
			//$order_obj['line_items'][] = $order->line_items;
			//$order_obj['line_items1'] = $order->get_items();
			$orders[] = $this->get_formatted_item_data($order);
		}
		PH_WSS_Common_Functions::ph_wss_admin_diagnostic_report( 'Orders from Store:'.print_r(json_encode($orders),true)); 
		return $orders;
	}

	public function get_order($request)
	{
		$order_id = $request['id'];
		$order = wc_get_order($order_id);
		//$order_data = $order->get_data();
		$order_data = $this->get_formatted_item_data($order);
		PH_WSS_Common_Functions::ph_wss_admin_diagnostic_report( 'Order Data from Store:'.print_r(json_encode($order_data),true)); 
		return $order_data;
	}


	function wc_get_order_statuses()
	{
		$order_statuses = array(
			'wc-pending'    => _x('Pending payment', 'Order status', 'woocommerce'),
			'wc-processing' => _x('Processing', 'Order status', 'woocommerce'),
			'wc-on-hold'    => _x('On hold', 'Order status', 'woocommerce'),
			'wc-completed'  => _x('Completed', 'Order status', 'woocommerce'),
			'wc-cancelled'  => _x('Cancelled', 'Order status', 'woocommerce'),
			'wc-refunded'   => _x('Refunded', 'Order status', 'woocommerce'),
			'wc-failed'     => _x('Failed', 'Order status', 'woocommerce'),
		);
		return apply_filters('wc_order_statuses', $order_statuses);
	}

	protected function get_order_statuses()
	{
		$order_statuses = array();

		foreach (array_keys(wc_get_order_statuses()) as $status) {
			$order_statuses[] = str_replace('wc-', '', $status);
		}

		return $order_statuses;
	}

	public function get_collection_params()
	{
		$params                      = array();
		$params['context']           = $this->get_context_param(array('default' => 'view'));

		$params['status'] = array(
			'default'           => 'any',
			'description'       => __('Limit result set to orders which have specific statuses.', 'woocommerce'),
			'type'              => 'array',
			'items'             => array(
				'type' => 'string',
				'enum' => array_merge(array('any', 'trash'), $this->get_order_statuses()),
			),
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['page']              = array(
			'description'       => __('Current page of the collection.', 'woocommerce'),
			'type'              => 'integer',
			'default'           => 1,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
			'minimum'           => 1,
		);
		$params['per_page']          = array(
			'description'       => __('Maximum number of items to be returned in result set.', 'woocommerce'),
			'type'              => 'integer',
			'default'           => 10,
			'minimum'           => 0,
			'maximum'           => 100,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['after']             = array(
			'description'       => __('Limit response to resources published after a given ISO8601 compliant date.', 'woocommerce'),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['before']            = array(
			'description'       => __('Limit response to resources published before a given ISO8601 compliant date.', 'woocommerce'),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}

	protected function get_order_item_data($item)
	{
		$data           = $item->get_data();
		$format_decimal = array('subtotal', 'subtotal_tax', 'total', 'total_tax', 'tax_total', 'shipping_tax_total');

		// Format decimal values.
		foreach ($format_decimal as $key) {
			if (isset($data[$key])) {
				$data[$key] = wc_format_decimal($data[$key], $this->request['dp']);
			}
		}

		// Add SKU and PRICE to products.
		if (is_callable(array($item, 'get_product'))) {
			$data['sku']   = $item->get_product() ? $item->get_product()->get_sku() : null;
			$data['price'] = $item->get_quantity() ? $item->get_total() / $item->get_quantity() : 0;
		}

		// Format taxes.
		if (!empty($data['taxes']['total'])) {
			$taxes = array();

			foreach ($data['taxes']['total'] as $tax_rate_id => $tax) {
				$taxes[] = array(
					'id'       => $tax_rate_id,
					'total'    => $tax,
					'subtotal' => isset($data['taxes']['subtotal'][$tax_rate_id]) ? $data['taxes']['subtotal'][$tax_rate_id] : '',
				);
			}
			$data['taxes'] = $taxes;
		} elseif (isset($data['taxes'])) {
			$data['taxes'] = array();
		}

		// Remove names for coupons, taxes and shipping.
		if (isset($data['code']) || isset($data['rate_code']) || isset($data['method_title'])) {
			unset($data['name']);
		}

		// $seller_id = get_post_field('post_author', $item['product_id']);
		// $info = get_user_meta($seller_id, 'dokan_profile_settings', true);
		// $info = is_array($info) ? $info : array();
		// $defaults = array(
		// 	'store_name' => '',
		// 	'address'                 => array()
		// );

		// $info               = wp_parse_args($info, $defaults);
		// $info['store_name'] = empty($info['store_name']) ? get_user_by('id', $seller_id)->display_name : $info['store_name'];
		// $info['store_address'] = empty($info['address']) ? '' : $info['address'];
		// $userdata = get_userdata($seller_id);
		// $user_nicename = (!false == $userdata) ? $userdata->user_nicename : '';
		// $options = get_option('dokan_general');
		// if (isset($options['custom_store_url'])) {
		// 	$custom_store_url = $options['custom_store_url'];
		// } else {
		// 	$custom_store_url = 'store';
		// }
		// $url = sprintf('%s/%s/', home_url('/' . $custom_store_url), $user_nicename);
		// $store_data = array(
		// 	'id'        => $seller_id,
		// 	'name'      => get_user_by('id', $seller_id)->display_name,
		// 	'shop_name' => $info['store_name'],
		// 	'url'       => $url,
		// 	'address'   => $info['store_address']
		// );
		// error_log(print_r('$store_data in order list',1));
		// error_log(print_r($store_data,1));

		// $data['store'] = $store_data;

		// Remove props we don't want to expose.
		unset($data['order_id']);
		unset($data['type']);

		return $data;
	}

	protected function get_formatted_item_data($object)
	{
		$data              = $object->get_data();
		// error_log(print_r('$data order',1));
		// error_log(print_r($data,1));
		$format_decimal    = array('discount_total', 'discount_tax', 'shipping_total', 'shipping_tax', 'shipping_total', 'shipping_tax', 'cart_tax', 'total', 'total_tax');
		$format_date       = array('date_created', 'date_modified', 'date_completed', 'date_paid');
		$format_line_items = array('line_items', 'tax_lines', 'shipping_lines', 'fee_lines', 'coupon_lines');

		// Format decimal values.
		foreach ($format_decimal as $key) {
			$data[$key] = wc_format_decimal($data[$key], $this->request['dp']);
		}

		// Format date values.
		foreach ($format_date as $key) {
			$datetime              = $data[$key];
			$data[$key]          = wc_rest_prepare_date_response($datetime, false);
			$data[$key . '_gmt'] = wc_rest_prepare_date_response($datetime);
		}

		// Format the order status.
		$data['status'] = 'wc-' === substr($data['status'], 0, 3) ? substr($data['status'], 3) : $data['status'];

		// Format line items.
		foreach ($format_line_items as $key) {
			$data[$key] = array_values(array_map(array($this, 'get_order_item_data'), $data[$key]));
		}

		// error_log(print_r('$data->store', 1));
		// error_log(print_r($data['store'], 1));

		$seller_id = get_post_field('post_author', $data['line_items'][0]['product_id']);
		$info = get_user_meta($seller_id, 'dokan_profile_settings', true);
		$info = is_array($info) ? $info : array();
		$defaults = array(
			'store_name' => '',
			'address'                 => array()
		);

		$info               = wp_parse_args($info, $defaults);
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
			'id'        => $seller_id,
			'name'      => get_user_by('id', $seller_id)->display_name,
			'shop_name' => $info['store_name'],
			'url'       => $url,
			'address'   => $info['store_address']
		);
		// error_log(print_r('$store_data in order list new', 1));
		// error_log(print_r($store_data, 1));

		$data['store'] = $store_data;

		// Refunds.
		$data['refunds'] = array();
		foreach ($object->get_refunds() as $refund) {
			$data['refunds'][] = array(
				'id'     => $refund->get_id(),
				'reason' => $refund->get_reason() ? $refund->get_reason() : '',
				'total'  => '-' . wc_format_decimal($refund->get_amount(), $this->request['dp']),
			);
		}

		return array(
			'id'                   => $object->get_id(),
			'parent_id'            => $data['parent_id'],
			'number'               => $data['number'],
			'order_key'            => $data['order_key'],
			'created_via'          => $data['created_via'],
			'version'              => $data['version'],
			'status'               => $data['status'],
			'currency'             => $data['currency'],
			'date_created'         => $data['date_created'],
			'date_created_gmt'     => $data['date_created_gmt'],
			'date_modified'        => $data['date_modified'],
			'date_modified_gmt'    => $data['date_modified_gmt'],
			'discount_total'       => $data['discount_total'],
			'discount_tax'         => $data['discount_tax'],
			'shipping_total'       => $data['shipping_total'],
			'shipping_tax'         => $data['shipping_tax'],
			'cart_tax'             => $data['cart_tax'],
			'total'                => $data['total'],
			'total_tax'            => $data['total_tax'],
			'prices_include_tax'   => $data['prices_include_tax'],
			'customer_id'          => $data['customer_id'],
			'customer_ip_address'  => $data['customer_ip_address'],
			'customer_user_agent'  => $data['customer_user_agent'],
			'customer_note'        => $data['customer_note'],
			'billing'              => $data['billing'],
			'shipping'             => $data['shipping'],
			'payment_method'       => $data['payment_method'],
			'payment_method_title' => $data['payment_method_title'],
			'transaction_id'       => $data['transaction_id'],
			'date_paid'            => $data['date_paid'],
			'date_paid_gmt'        => $data['date_paid_gmt'],
			'date_completed'       => $data['date_completed'],
			'date_completed_gmt'   => $data['date_completed_gmt'],
			'cart_hash'            => $data['cart_hash'],
			'meta_data'            => $data['meta_data'],
			'line_items'           => $data['line_items'],
			'tax_lines'            => $data['tax_lines'],
			'shipping_lines'       => $data['shipping_lines'],
			'fee_lines'            => $data['fee_lines'],
			'coupon_lines'         => $data['coupon_lines'],
			'refunds'              => $data['refunds'],
			'store'                => $data['store'],
		);
	}
}
