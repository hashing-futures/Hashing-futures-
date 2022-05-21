<?php


class SP_products_variations_list_API extends WP_REST_Controller {
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
  protected $rest_base = 'products/(?P<product_id>[\d]+)/variations';

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
    public function __construct() {
 
    }

  	/**
	 * Register the routes for products.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace, '/' . $this->rest_base, array(
				'args'   => array(
					'product_id' => array(
						'description' => __( 'Unique identifier for the variable product.', 'woocommerce' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_product_variations' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				// array(
				// 	'methods'             => WP_REST_Server::CREATABLE,
				// 	'callback'            => array( $this, 'create_item' ),
				// 	'permission_callback' => array( $this, 'create_item_permissions_check' ),
				// 	'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				// ),
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
	public function get_items_permissions_check( $request ) {
		//$token = WP_REST_Request::get_headers($request);
		$token = $request->get_header('authorizationToken');
		//error_log(print_r($token, 1));
		$plugin_settings_from_db = get_option(SP_PLUGIN_ID, true);
		//error_log(print_r($plugin_settings_from_db, 1));
		if($token !== $plugin_settings_from_db['api-token']) {
			return false;//new \WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce' ), array( 'status' => \rest_authorization_required_code() ) );
		}
		return true;
	}
	
	// private function get_attributes( $product ) {

	// 	$attributes = array();

	// 	if ( $product->is_type( 'variation' ) ) {

	// 		// variation attributes
	// 		foreach ( $product->get_variation_attributes() as $attribute_name => $attribute ) {

	// 			// taxonomy-based attributes are prefixed with `pa_`, otherwise simply `attribute_`
	// 			$attributes[] = array(
	// 				'name'   => wc_attribute_label( str_replace( 'attribute_', '', $attribute_name ), $product ),
	// 				'slug'   => str_replace( 'attribute_', '', wc_attribute_taxonomy_slug( $attribute_name ) ),
	// 				'option' => $attribute,
	// 			);
	// 		}
	// 	} else {

	// 		foreach ( $product->get_attributes() as $attribute ) {
	// 			$attributes[] = array(
	// 				'name'      => wc_attribute_label( $attribute['name'], $product ),
	// 				'slug'      => wc_attribute_taxonomy_slug( $attribute['name'] ),
	// 				'position'  => (int) $attribute['position'],
	// 				'visible'   => (bool) $attribute['is_visible'],
	// 				'variation' => (bool) $attribute['is_variation'],
	// 				'options'   => $this->get_attribute_options( $product->get_id(), $attribute ),
	// 			);
	// 		}
	// 	}

	// 	return $attributes;
	// }

    

	public function get_product_variations( $request ) {
        //error_log(print_r($request, 1));
        $product_id = $request['product_id'];
        // $prouduct = new WC_Product_Variable( $product_id );
        // $args = array( 
		// 	'limit'   => 10,//$request['per_page'],
		// 	'page'    => 1,//$request['page'],
		// 	// 'after'   => $request['after'],
		// 	// 'status'  => $request['status']
        //  );

    	//$variables = $prouduct->get_available_variations($args);
        $product = wc_get_product( $product_id );
        $variation_ids  = $product->get_children();
		$available_variations = array();

		foreach ( $variation_ids as $variation_id ) {

			$variation = wc_get_product( $variation_id );
            //$variation_object = $variation->get_data();
            //$variation_object['attributes1'] = $variation->get_attributes();
			$available_variations[] = $this->prepare_object_for_response( $variation, $request );;
		}

		// $available_variations = array_values( array_filter( $available_variations ) );
        PH_WSS_Common_Functions::ph_wss_admin_diagnostic_report( 'Variable Products from Store:'.print_r(json_encode($available_variations),true));
		return $available_variations;
		//return $variables;
	}


	protected function get_images( $product ) {
		$images         = array();
		$attachment_ids = array();

		// Add featured image.
		if ( $product->get_image_id() ) {
			$attachment_ids[] = $product->get_image_id();
		}

		// Add gallery images.
		$attachment_ids = array_merge( $attachment_ids, $product->get_gallery_image_ids() );

		// Build image data.
		foreach ( $attachment_ids as $position => $attachment_id ) {
			$attachment_post = get_post( $attachment_id );
			if ( is_null( $attachment_post ) ) {
				continue;
			}

			$attachment = wp_get_attachment_image_src( $attachment_id, 'full' );
			if ( ! is_array( $attachment ) ) {
				continue;
			}

			$images[] = array(
				'id'                => (int) $attachment_id,
				'date_created'      => wc_rest_prepare_date_response( $attachment_post->post_date, false ),
				'date_created_gmt'  => wc_rest_prepare_date_response( strtotime( $attachment_post->post_date_gmt ) ),
				'date_modified'     => wc_rest_prepare_date_response( $attachment_post->post_modified, false ),
				'date_modified_gmt' => wc_rest_prepare_date_response( strtotime( $attachment_post->post_modified_gmt ) ),
				'src'               => current( $attachment ),
				'name'              => get_the_title( $attachment_id ),
				'alt'               => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
				'position'          => (int) $position,
			);
		}

		// Set a placeholder image if the product has no images set.
		if ( empty( $images ) ) {
			$images[] = array(
				'id'                => 0,
				'date_created'      => wc_rest_prepare_date_response( current_time( 'mysql' ), false ), // Default to now.
				'date_created_gmt'  => wc_rest_prepare_date_response( current_time( 'timestamp', true ) ), // Default to now.
				'date_modified'     => wc_rest_prepare_date_response( current_time( 'mysql' ), false ),
				'date_modified_gmt' => wc_rest_prepare_date_response( current_time( 'timestamp', true ) ),
				'src'               => wc_placeholder_img_src(),
				'name'              => __( 'Placeholder', 'woocommerce' ),
				'alt'               => __( 'Placeholder', 'woocommerce' ),
				'position'          => 0,
			);
		}

		return $images;
	}

	protected function get_attribute_taxonomy_label( $name ) {
		$tax    = get_taxonomy( $name );
		$labels = get_taxonomy_labels( $tax );

		return $labels->singular_name;
	}

	protected function get_attributes( $product ) {
		$attributes = array();

		if ( $product->is_type( 'variation' ) ) {
			// Variation attributes.
			foreach ( $product->get_variation_attributes() as $attribute_name => $attribute ) {
				$name = str_replace( 'attribute_', '', $attribute_name );

				if ( ! $attribute ) {
					continue;
				}

				// Taxonomy-based attributes are prefixed with `pa_`, otherwise simply `attribute_`.
				if ( 0 === strpos( $attribute_name, 'attribute_pa_' ) ) {
					$option_term = get_term_by( 'slug', $attribute, $name );
					$attributes[] = array(
						'id'     => wc_attribute_taxonomy_id_by_name( $name ),
						'name'   => $this->get_attribute_taxonomy_label( $name ),
						'option' => $option_term && ! is_wp_error( $option_term ) ? $option_term->name : $attribute,
					);
				} else {
					$attributes[] = array(
						'id'     => 0,
						'name'   => $name,
						'option' => $attribute,
					);
				}
			}
		} else {
			foreach ( $product->get_attributes() as $attribute ) {
				if ( $attribute['is_taxonomy'] ) {
					$attributes[] = array(
						'id'        => wc_attribute_taxonomy_id_by_name( $attribute['name'] ),
						'name'      => $this->get_attribute_taxonomy_label( $attribute['name'] ),
						'position'  => (int) $attribute['position'],
						'visible'   => (bool) $attribute['is_visible'],
						'variation' => (bool) $attribute['is_variation'],
						'options'   => $this->get_attribute_options( $product->get_id(), $attribute ),
					);
				} else {
					$attributes[] = array(
						'id'        => 0,
						'name'      => $attribute['name'],
						'position'  => (int) $attribute['position'],
						'visible'   => (bool) $attribute['is_visible'],
						'variation' => (bool) $attribute['is_variation'],
						'options'   => $this->get_attribute_options( $product->get_id(), $attribute ),
					);
				}
			}
		}

		return $attributes;
	}

	protected function get_default_attributes( $product ) {
		$default = array();

		if ( $product->is_type( 'variable' ) ) {
			foreach ( array_filter( (array) $product->get_default_attributes(), 'strlen' ) as $key => $value ) {
				if ( 0 === strpos( $key, 'pa_' ) ) {
					$default[] = array(
						'id'     => wc_attribute_taxonomy_id_by_name( $key ),
						'name'   => $this->get_attribute_taxonomy_name( $key, $product ),
						'option' => $value,
					);
				} else {
					$default[] = array(
						'id'     => 0,
						'name'   => $this->get_attribute_taxonomy_name( $key, $product ),
						'option' => $value,
					);
				}
			}
		}

		return $default;
	}

	public function prepare_object_for_response( $object, $request ) {
		$data = array(
			'id'                    => $object->get_id(),
			'date_created'          => wc_rest_prepare_date_response( $object->get_date_created(), false ),
			'date_created_gmt'      => wc_rest_prepare_date_response( $object->get_date_created() ),
			'date_modified'         => wc_rest_prepare_date_response( $object->get_date_modified(), false ),
			'date_modified_gmt'     => wc_rest_prepare_date_response( $object->get_date_modified() ),
			'description'           => wc_format_content( $object->get_description() ),
			'permalink'             => $object->get_permalink(),
			'sku'                   => $object->get_sku(),
			'price'                 => $object->get_price(),
			'regular_price'         => $object->get_regular_price(),
			'sale_price'            => $object->get_sale_price(),
			'date_on_sale_from'     => wc_rest_prepare_date_response( $object->get_date_on_sale_from(), false ),
			'date_on_sale_from_gmt' => wc_rest_prepare_date_response( $object->get_date_on_sale_from() ),
			'date_on_sale_to'       => wc_rest_prepare_date_response( $object->get_date_on_sale_to(), false ),
			'date_on_sale_to_gmt'   => wc_rest_prepare_date_response( $object->get_date_on_sale_to() ),
			'on_sale'               => $object->is_on_sale(),
			'visible'               => $object->is_visible(),
			'purchasable'           => $object->is_purchasable(),
			'virtual'               => $object->is_virtual(),
			'downloadable'          => $object->is_downloadable(),
			'downloads'             => $object->get_downloads(),
			'download_limit'        => '' !== $object->get_download_limit() ? (int) $object->get_download_limit() : -1,
			'download_expiry'       => '' !== $object->get_download_expiry() ? (int) $object->get_download_expiry() : -1,
			'tax_status'            => $object->get_tax_status(),
			'tax_class'             => $object->get_tax_class(),
			'manage_stock'          => $object->managing_stock(),
			'stock_quantity'        => $object->get_stock_quantity(),
			'in_stock'              => $object->is_in_stock(),
			'backorders'            => $object->get_backorders(),
			'backorders_allowed'    => $object->backorders_allowed(),
			'backordered'           => $object->is_on_backorder(),
			'weight'                => $object->get_weight(),
			'dimensions'            => array(
				'length' => $object->get_length(),
				'width'  => $object->get_width(),
				'height' => $object->get_height(),
			),
			'shipping_class'        => $object->get_shipping_class(),
			'shipping_class_id'     => $object->get_shipping_class_id(),
			'image'                 => current( $this->get_images( $object ) ),
			'attributes'            => $this->get_attributes( $object ),
			//'menu_order'            => $object->get_menu_order(),
			'meta_data'             => $object->get_meta_data(),
			'attribute_summary' 	=> $object->get_attribute_summary(),
		);

		$context  = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		//$response = rest_ensure_response( $data );
		//$response->add_links( $this->prepare_links( $object, $request ) );

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
		PH_WSS_Common_Functions::ph_wss_admin_diagnostic_report( 'Mapping Variable Products from Store:'.print_r($data,true)); 
		return $data;
	}
    
    public function get_collection_params() {
		$params                       = array();
		$params['context']            = $this->get_context_param();
		$params['context']['default'] = 'view';

        $params['status']         = array(
			'default'           => 'any',
			'description'       => __( 'Limit result set to products assigned a specific status.', 'woocommerce' ),
			'type'              => 'string',
			'enum'              => array_merge( array( 'any', 'future' ), array_keys( get_post_statuses() ) ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
        );
        
		$params['page'] = array(
			'description'       => __( 'Current page of the collection.', 'woocommerce' ),
			'type'              => 'integer',
			'default'           => 1,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
			'minimum'           => 1,
		);

		$params['per_page'] = array(
			'description'       => __( 'Maximum number of items to be returned in result set.', 'woocommerce' ),
			'type'              => 'integer',
			'default'           => 10,
			'minimum'           => 1,
			'maximum'           => 100,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['search'] = array(
			'description'       => __( 'Limit results to those matching a string.', 'woocommerce' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['after'] = array(
			'description'       => __( 'Limit response to resources created after a given ISO8601 compliant date.', 'woocommerce' ),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['before'] = array(
			'description'       => __( 'Limit response to resources created before a given ISO8601 compliant date.', 'woocommerce' ),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}
}
