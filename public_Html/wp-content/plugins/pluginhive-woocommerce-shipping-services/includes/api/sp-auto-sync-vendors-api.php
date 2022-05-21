<?php
class Sp_mv_auto_sync extends WP_REST_Controller {
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
  protected $base = 'stores';

    /**
     * Stores the request.
     * @var array
     */
    protected $request = array();

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
   * Register the routes for settings.
   */
  public function register_routes() {
    register_rest_route( $this->namespace, '/' . $this->base . '/id/(?P<id>[\d]+)/type/(?P<type>[a-zA-Z0-9-]+)', array(
    //register_rest_route( $this->namespace, '/' . $this->base . '/id/(?P<id>[\d]+)', array(
        array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_formatted_item_data' ),
            'permission_callback' => array( $this, 'get_store_vendors_permissions_check' ),
            'args'                => $this->get_collection_params(),
        ),
        'schema' => array( $this, 'get_public_item_schema' ),
    ) );
  }

  

  public function get_store_vendors_permissions_check() {
    return true;
  }

  public function get_formatted_item_data($request) {
  $type = $request['type'];
  $vendor_id = $request['id'];
  $vendor_info_json = array();
  if($type == 'WCFM'){
    $vendor_info_json['vendor_id'] = $vendor_id;
    $disable_vendor = get_user_meta( $wcfm_vendors_id, '_disable_vendor', true );
    $is_store_offline = get_user_meta( $wcfm_vendors_id, '_wcfm_store_offline', true );
    $vendor_info_json['disable_vendor'] = $disable_vendor;
    $vendor_info_json['is_store_offline'] = $is_store_offline;
    $vendor_settings_data = get_user_meta( $vendor_id, 'wcfmmp_profile_settings', true );
    $vendor_info_json['vendor_data'] = $vendor_settings_data;

    return $vendor_info_json;
  }
  else {
    $store = dokan()->vendor->get( $vendor_id );

        if ( empty( $store->id ) ) {
            return new WP_Error( 'no_store_found', __( 'No store found', 'dokan-lite' ), array( 'status' => 404 ) );
        }

        $stores_data = $this->prepare_item_for_response( $store, $request );
        $response    = rest_ensure_response( $stores_data );
        //return $response;
        return $response;
  }
}
  public function prepare_item_for_response( $store, $request, $additional_fields = [] ) {

    $data = $store->to_array();
    $data = array_merge( $data, apply_filters( 'dokan_rest_store_additional_fields', $additional_fields, $store, $request ) );
    $response = rest_ensure_response( $data );
    $response->add_links( $this->prepare_links( $data, $request ) );

    return apply_filters( 'dokan_rest_prepare_store_item_for_response', $response );
  }

  protected function prepare_links( $object, $request ) {
    $links = array(
        'self' => array(
            'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->base, $object['id'] ) ),
        ),
        'collection' => array(
            'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->base ) ),
        ),
    );

    return $links;
  }
}