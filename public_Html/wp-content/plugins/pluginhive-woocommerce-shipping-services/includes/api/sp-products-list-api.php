<?php


class SP_products_list_API extends WP_REST_Controller
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
	protected $rest_base = 'products';

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
	 * Register the routes for products.
	 */

	public function register_routes()
	{
		\register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array($this, 'get_products'),
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
					'callback'            => array($this, 'get_product'),
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
	 * Check if a given request has access to read an item.
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|boolean
	 */
	public function get_items_permissions_check($request)
	{
		//$token = WP_REST_Request::get_headers($request);
		$token = $request->get_header('authorizationToken');
		$plugin_settings_from_db = get_option(SP_PLUGIN_ID, true);
		if ($token !== $plugin_settings_from_db['api-token']) {
			return false; //new \WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce' ), array( 'status' => \rest_authorization_required_code() ) );
		}
		return true;
	}


	public function get_products($request)
	{
		//error_log(print_r($referer, 1));
		$args = array(
			'limit'   => $request['per_page'],
			'page'    => $request['page'],
			'after'   => $request['after'],
			'status'  => $request['status']
			//'type'    =>  $type,
		);
		// $query = new WC_Product_Query( $args );
		// $products_query = $query->get_products();
		$products_query = wc_get_products($args);
		$products = array();
		foreach ($products_query as $product) {
			// $productObj = $product->get_data();
			// $productObj['type'] = $product->get_type();
			// $productObj['variations'] = $product->get_children();
			// $productObj['grouped_products'] = $product->get_children();
			$products[] = $this->prepare_object_for_response($product, $request);
		}
        PH_WSS_Common_Functions::ph_wss_admin_diagnostic_report( 'Products from Store:'.print_r(json_encode($products),true));
		return $products;
	}

	public function get_product($request)
	{
		//error_log(print_r($request, 1));
		$product_id = $request['id'];
		$product = wc_get_product($product_id);
		// $product_data = $product->get_data();
		// $product_data['type'] = $product->get_type();
		// $product_data['variations'] = $product->get_children();
		// $product_data['grouped_products'] = $product->get_children();
		$product_data = $this->prepare_object_for_response($product, $request);
		PH_WSS_Common_Functions::ph_wss_admin_diagnostic_report( 'Product Data from Store:'.print_r(json_encode($product_data),true));
		return $product_data;
	}

	public function prepare_object_for_response($object, $request)
	{
		$context = !empty($request['context']) ? $request['context'] : 'view';
		$data    = $this->get_product_data($object, $context);

		// Add variations to variable products.
		if ($object->is_type('variable') && $object->has_child()) {
			$data['variations'] = $object->get_children();
		}

		// Add grouped products data.
		if ($object->is_type('grouped') && $object->has_child()) {
			$data['grouped_products'] = $object->get_children();
		}

		$data     = $this->add_additional_fields_to_object($data, $request);
		$data     = $this->filter_response_by_context($data, $context);

		/**
		 * Filter the data for a response.
		 *
		 * The dynamic portion of the hook name, $this->post_type,
		 * refers to object type being prepared for the response.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param WC_Data          $object   Object data.
		 * @param WP_REST_Request  $request  Request object.
		 */
		return $data;
	}

	protected function get_images($product)
	{
		$images         = array();
		$attachment_ids = array();

		// Add featured image.
		if ($product->get_image_id()) {
			$attachment_ids[] = $product->get_image_id();
		}

		// Add gallery images.
		$attachment_ids = array_merge($attachment_ids, $product->get_gallery_image_ids());

		// Build image data.
		foreach ($attachment_ids as $position => $attachment_id) {
			$attachment_post = get_post($attachment_id);
			if (is_null($attachment_post)) {
				continue;
			}

			$attachment = wp_get_attachment_image_src($attachment_id, 'full');
			if (!is_array($attachment)) {
				continue;
			}

			$images[] = array(
				'id'                => (int) $attachment_id,
				'date_created'      => wc_rest_prepare_date_response($attachment_post->post_date, false),
				'date_created_gmt'  => wc_rest_prepare_date_response(strtotime($attachment_post->post_date_gmt)),
				'date_modified'     => wc_rest_prepare_date_response($attachment_post->post_modified, false),
				'date_modified_gmt' => wc_rest_prepare_date_response(strtotime($attachment_post->post_modified_gmt)),
				'src'               => current($attachment),
				'name'              => get_the_title($attachment_id),
				'alt'               => get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
				'position'          => (int) $position,
			);
		}

		// Set a placeholder image if the product has no images set.
		if (empty($images)) {
			$images[] = array(
				'id'                => 0,
				'date_created'      => wc_rest_prepare_date_response(current_time('mysql'), false), // Default to now.
				'date_created_gmt'  => wc_rest_prepare_date_response(current_time('timestamp', true)), // Default to now.
				'date_modified'     => wc_rest_prepare_date_response(current_time('mysql'), false),
				'date_modified_gmt' => wc_rest_prepare_date_response(current_time('timestamp', true)),
				'src'               => wc_placeholder_img_src(),
				'name'              => __('Placeholder', 'woocommerce'),
				'alt'               => __('Placeholder', 'woocommerce'),
				'position'          => 0,
			);
		}

		return $images;
	}

	protected function get_taxonomy_terms($product, $taxonomy = 'cat')
	{
		$terms = array();

		foreach (wc_get_object_terms($product->get_id(), 'product_' . $taxonomy) as $term) {
			$terms[] = array(
				'id'   => $term->term_id,
				'name' => $term->name,
				'slug' => $term->slug,
			);
		}

		return $terms;
	}


	protected function get_product_data($product, $context = 'view')
	{
		$seller_id = get_post_field('post_author', $product->get_id());
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
		$data = array(
			'id'                    => $product->get_id(),
			'name'                  => $product->get_name($context),
			'slug'                  => $product->get_slug($context),
			'permalink'             => $product->get_permalink(),
			'date_created'          => wc_rest_prepare_date_response($product->get_date_created($context), false),
			'date_created_gmt'      => wc_rest_prepare_date_response($product->get_date_created($context)),
			'date_modified'         => wc_rest_prepare_date_response($product->get_date_modified($context), false),
			'date_modified_gmt'     => wc_rest_prepare_date_response($product->get_date_modified($context)),
			'type'                  => $product->get_type(),
			'status'                => $product->get_status($context),
			'featured'              => $product->is_featured(),
			'catalog_visibility'    => $product->get_catalog_visibility($context),
			'description'           => 'view' === $context ? wpautop(do_shortcode($product->get_description())) : $product->get_description($context),
			'short_description'     => 'view' === $context ? apply_filters('woocommerce_short_description', $product->get_short_description()) : $product->get_short_description($context),
			'sku'                   => $product->get_sku($context),
			'price'                 => $product->get_price($context),
			'regular_price'         => $product->get_regular_price($context),
			'sale_price'            => $product->get_sale_price($context) ? $product->get_sale_price($context) : '',
			'date_on_sale_from'     => wc_rest_prepare_date_response($product->get_date_on_sale_from($context), false),
			'date_on_sale_from_gmt' => wc_rest_prepare_date_response($product->get_date_on_sale_from($context)),
			'date_on_sale_to'       => wc_rest_prepare_date_response($product->get_date_on_sale_to($context), false),
			'date_on_sale_to_gmt'   => wc_rest_prepare_date_response($product->get_date_on_sale_to($context)),
			'price_html'            => $product->get_price_html(),
			'on_sale'               => $product->is_on_sale($context),
			'purchasable'           => $product->is_purchasable(),
			'total_sales'           => $product->get_total_sales($context),
			'virtual'               => $product->is_virtual(),
			'downloadable'          => $product->is_downloadable(),
			'downloads'             => $product->get_downloads(),
			'download_limit'        => $product->get_download_limit($context),
			'download_expiry'       => $product->get_download_expiry($context),
			'external_url'          => $product->is_type('external') ? $product->get_product_url($context) : '',
			'button_text'           => $product->is_type('external') ? $product->get_button_text($context) : '',
			'tax_status'            => $product->get_tax_status($context),
			'tax_class'             => $product->get_tax_class($context),
			'manage_stock'          => $product->managing_stock(),
			'stock_quantity'        => $product->get_stock_quantity($context),
			'in_stock'              => $product->is_in_stock(),
			'backorders'            => $product->get_backorders($context),
			'backorders_allowed'    => $product->backorders_allowed(),
			'backordered'           => $product->is_on_backorder(),
			'sold_individually'     => $product->is_sold_individually(),
			'weight'                => $product->get_weight($context),
			'dimensions'            => array(
				'length' => $product->get_length($context),
				'width'  => $product->get_width($context),
				'height' => $product->get_height($context),
			),
			'shipping_required'     => $product->needs_shipping(),
			'shipping_taxable'      => $product->is_shipping_taxable(),
			'shipping_class'        => $product->get_shipping_class(),
			'shipping_class_id'     => $product->get_shipping_class_id($context),
			'reviews_allowed'       => $product->get_reviews_allowed($context),
			'average_rating'        => 'view' === $context ? wc_format_decimal($product->get_average_rating(), 2) : $product->get_average_rating($context),
			'rating_count'          => $product->get_rating_count(),
			//'related_ids'           => array_map( 'absint', array_values( wc_get_related_products( $product->get_id() ) ) ),
			'upsell_ids'            => array_map('absint', $product->get_upsell_ids($context)),
			'cross_sell_ids'        => array_map('absint', $product->get_cross_sell_ids($context)),
			'parent_id'             => $product->get_parent_id($context),
			'purchase_note'         => 'view' === $context ? wpautop(do_shortcode(wp_kses_post($product->get_purchase_note()))) : $product->get_purchase_note($context),
			'categories'            => $this->get_taxonomy_terms($product),
			'tags'                  => $this->get_taxonomy_terms($product, 'tag'),
			'images'                => $this->get_images($product),
			//'attributes'            => $this->get_attributes( $product ),
			//'default_attributes'    => $this->get_default_attributes( $product ),
			'variations'            => array(),
			'grouped_products'      => array(),
			//'menu_order'            => $product->get_menu_order( $context ),
			'meta_data'             => $product->get_meta_data(),
			'store'                 => $store_data,
		);
        PH_WSS_Common_Functions::ph_wss_admin_diagnostic_report( 'Mapping Products from Store:'.print_r($data,true)); 
		return $data;
	}

	public function get_collection_params()
	{
		$params                       = array();
		$params['context']            = $this->get_context_param();
		$params['context']['default'] = 'view';

		$params['status']         = array(
			'default'           => 'any',
			'description'       => __('Limit result set to products assigned a specific status.', 'woocommerce'),
			'type'              => 'string',
			'enum'              => array_merge(array('any', 'future'), array_keys(get_post_statuses())),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['page'] = array(
			'description'       => __('Current page of the collection.', 'woocommerce'),
			'type'              => 'integer',
			'default'           => 1,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
			'minimum'           => 1,
		);

		$params['per_page'] = array(
			'description'       => __('Maximum number of items to be returned in result set.', 'woocommerce'),
			'type'              => 'integer',
			'default'           => 10,
			'minimum'           => 1,
			'maximum'           => 100,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['search'] = array(
			'description'       => __('Limit results to those matching a string.', 'woocommerce'),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['after'] = array(
			'description'       => __('Limit response to resources created after a given ISO8601 compliant date.', 'woocommerce'),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['before'] = array(
			'description'       => __('Limit response to resources created before a given ISO8601 compliant date.', 'woocommerce'),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}
}
