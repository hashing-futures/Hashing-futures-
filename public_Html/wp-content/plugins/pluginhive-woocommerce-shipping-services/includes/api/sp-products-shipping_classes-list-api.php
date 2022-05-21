<?php


class SP_products_shipping_classes_list_API extends WP_REST_Controller {
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
  protected $rest_base = 'products/shipping_classes';

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
    \register_rest_route(
        $this->namespace,
        '/' . $this->rest_base,
        array(
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_product_shipping_classes' ),
                'permission_callback' => array( $this, 'get_items_permissions_check' ),
                'args'                => $this->get_collection_params(),
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
    
    // public function get_shipping_classes() {
	// 	if ( empty( $this->shipping_classes ) ) {
	// 		$classes                = get_terms(
	// 			'product_shipping_class',
	// 			array(
	// 				'hide_empty' => '0',
    //                 'orderby'    => 'name',
    //                 'limit'      => 2,
	// 			)
	// 		);
	// 		$this->shipping_classes = ! is_wp_error( $classes ) ? $classes : array();
	// 	}
	// 	return apply_filters( 'woocommerce_get_shipping_classes', $this->shipping_classes );
	// }

	// public function get_products_shipping_classes( $request ) {
    //     //error_log(print_r($request, 1));
    //     $args = array( 
	// 		'limit'   => 2,//$request['per_page'],
	// 		'page'    => 1,//$request['page'],
    //      );
    //      $shipping           = new WC_Shipping( $args );
    //      $shipping_classes   = $shipping->get_shipping_classes();
	// 	// $products = array();
	// 	// foreach ( $shipping_classes as $product ) {
	// 	// 	// $productObj = $product->get_data();
	// 	// 	// $productObj['type'] = $product->get_type();
	// 	// 	// $productObj['variations'] = $product->get_children();
	// 	// 	// $productObj['grouped_products'] = $product->get_children();
	// 	// 	$products[] = $product->get_data();
	// 	// }
		
	// 	return $shipping_classes;
    // }
    public function get_product_shipping_class( $id, $fields = null ) {

			$term = get_term( $id, 'product_shipping_class' );

			$term_id = intval( $term->term_id );

			$product_shipping_class = array(
				'id'          => $term_id,
				'name'        => $term->name,
				'slug'        => $term->slug,
				'parent'      => $term->parent,
				'description' => $term->description,
                'count'       => intval( $term->count ),
                '_links'      => $term->_links,
			);
            PH_WSS_Common_Functions::ph_wss_admin_diagnostic_report( 'Product Shipping Class:'.print_r($product_shipping_class,true));

			return array( 'product_shipping_class' => apply_filters( 'woocommerce_api_product_shipping_class_response', $product_shipping_class, $id, $fields, $term, $this ) );
		
	}

    public function get_product_shipping_classes( $request ) {
        //error_log(print_r($request, 1));
			$product_shipping_classes = array();

			$terms = get_terms( 'product_shipping_class', array( 
                'hide_empty' => false, 
                'fields' => 'ids', 
                'limit'   => $request['per_page'],
             	'page'    => $request['page'], ) );

			foreach ( $terms as $term_id ) {
				$product_shipping_classes[] = current( $this->get_product_shipping_class( $term_id, $fields = null ) );
			}
            PH_WSS_Common_Functions::ph_wss_admin_diagnostic_report( 'Product Shipping Classes:'.print_r($product_shipping_classes,true));
            return $product_shipping_classes;
		
	}
	
	
	
}
