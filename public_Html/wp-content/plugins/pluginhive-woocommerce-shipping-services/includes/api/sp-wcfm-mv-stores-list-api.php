<?php
class Sp_wcfm_mv_stores_list extends WCFM_REST_Controller {
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
  protected $base = 'stores/wcfmmp';

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
    register_rest_route( $this->namespace, '/' . $this->base , array(
        array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_store_vendors' ),
            'permission_callback' => array( $this, 'get_store_vendors_permissions_check' ),
            'args'                => $this->get_collection_params(),
        ),
        'schema' => array( $this, 'get_public_item_schema' ),
    ) );
  }

  public function get_store_vendors($request) {
    //print_r('testing');
    global $WCFM;
    $_POST["controller"] = 'wcfm-vendors';
    $_POST['length'] = !empty($request['per_page']) ? intval($request['per_page']) : 10;
    $_POST['start'] = !empty($request['page']) ? ( intval($request['page']) - 1 ) * $_POST['length'] : 0;
    $_POST['filter_date_form'] = !empty($request['after']) ? $request['after'] : '';
    $_POST['filter_date_to'] = !empty($request['before']) ? $request['before'] : '';
    define('WCFM_REST_API_CALL', TRUE);
    $WCFM->init();
    $wcfm_vendors_array = array();
    $wcfm_vendors_json_arr = array();
    $response = array();
    // $wcfm_vendors_array = $WCFM->ajax->wcfm_ajax_controller(); 
    $allVendors = true;
    $offset =  absint($_POST['start']);
    $no_of_vendors =  absint($_POST['length']);
    $search_vendor = '';
    $allow_vendors_list = '';
    $is_disabled_vendors = true;
    $vendor_search_data = array();
    $wcfm_vendors_array = $WCFM->wcfm_vendor_support->wcfm_get_vendor_list( $allVendors, $offset, $no_of_vendors, $search_vendor, $allow_vendors_list, $is_disabled_vendors, $vendor_search_data );
    
    if( !empty($wcfm_vendors_array) ) {
      $index = 0;
      foreach($wcfm_vendors_array as $wcfm_vendors_id => $wcfm_vendors_name ) {
        $response[$index] = $this->get_formatted_item_data( $wcfm_vendors_id, $wcfm_vendors_json_arr , $wcfm_vendors_name, $_POST['filter_date_form'], $_POST['filter_date_to']  );
        $index++;
      }
      //print_r($response);
      return apply_filters( "wcfmapi_rest_prepare_store_vendors_objects", $response, $request );
      return rest_ensure_response( $response );
    } else {
      return rest_ensure_response( $response );
    }
  }

  public function get_store_vendors_permissions_check() {
    return true;
  }

  protected function get_formatted_item_data( $wcfm_vendors_id, $wcfm_vendors_json_arr , $wcfm_vendors_name, $filter_date_form, $filter_date_to ) {
    global $WCFM;
    $admin_fee_mode = apply_filters( 'wcfm_is_admin_fee_mode', false );
    $price_decimal = get_option('woocommerce_price_num_decimals', 2);
    $report_for = 'month';

    $wcfm_vendors_json_arr['vendor_id'] = $wcfm_vendors_id;
    $wcfm_vendors_json_arr['vendor_display_name'] =  $wcfm_vendors_name;
    $wcfm_vendors_json_arr['vendor_shop_name'] = $WCFM->wcfm_vendor_support->wcfm_get_vendor_store_name_by_vendor( $wcfm_vendors_id );

    $store_user  = wcfmmp_get_store( absint( $wcfm_vendors_id ) );
    $email       = $store_user->get_email();
    $phone       = $store_user->get_phone(); 
    $address     = $store_user->get_address_string();

    if($email) {
      $wcfm_vendors_json_arr['vendor_email'] =  $email;
    }

    if($address) {
      $wcfm_vendors_json_arr['vendor_address'] =  $address;
    }

    if($phone) {
      $wcfm_vendors_json_arr['vendor_phone'] =  $phone;
    }
    
    $wcfm_vendors_json_arr['vendor_shop_name'] = $WCFM->wcfm_vendor_support->wcfm_get_vendor_store_name_by_vendor( $wcfm_vendors_id );
    $disable_vendor = get_user_meta( $wcfm_vendors_id, '_disable_vendor', true );
    $is_store_offline = get_user_meta( $wcfm_vendors_id, '_wcfm_store_offline', true );
    $wcfm_vendors_json_arr['disable_vendor'] = $disable_vendor;
    $wcfm_vendors_json_arr['is_store_offline'] = $is_store_offline;
    if( apply_filters( 'wcfm_is_allow_email_verification', true ) ) {
      $email_verified = false;
      $vendor_user = get_userdata( $wcfm_vendors_id );
      $user_email = $vendor_user->user_email;
      $email_verified = get_user_meta( $wcfm_vendors_id, '_wcfm_email_verified', true );
      $wcfm_email_verified_for = get_user_meta( $wcfm_vendors_id, '_wcfm_email_verified_for', true );
      if( $email_verified && ( $user_email != $wcfm_email_verified_for ) ) $email_verified = false;
      $wcfm_vendors_json_arr['email_verified'] = $email_verified;
    }

    // $wcfm_vendors_json_arr['additional_data'] = apply_filters( 'wcfm_vendors_additonal_data', '&ndash;', $wcfm_vendors_id );
    $vendor_id = $wcfm_vendors_id;

    $wcfmvm_registration_custom_fields = get_option( 'wcfmvm_registration_custom_fields', array() );
		$wcfmvm_custom_infos = get_user_meta( $vendor_id, 'wcfmvm_custom_infos', true );

		$wcfm_vendors_json_arr['vendor_additional_info'] = array();

    if(!empty($wcfmvm_registration_custom_fields)) {
      foreach( $wcfmvm_registration_custom_fields as $key => $wcfmvm_registration_custom_field ) {
        $wcfmvm_registration_custom_field['name'] = sanitize_title( $wcfmvm_registration_custom_field['label'] );
        if( !empty( $wcfmvm_custom_infos ) ) {
          if( $wcfmvm_registration_custom_field['type'] == 'checkbox' ) {
            $field_value = isset( $wcfmvm_custom_infos[$wcfmvm_registration_custom_field['name']] ) ? $wcfmvm_custom_infos[$wcfmvm_registration_custom_field['name']] : 'no';
          } elseif( $wcfmvm_registration_custom_field['type'] == 'upload' ) {
            $field_name  = 'wcfmvm_custom_infos[' . $wcfmvm_registration_custom_field['name'] . ']';
            $field_id    = md5( $field_name );
            $field_value = isset( $wcfmvm_custom_infos[$field_id] ) ? $wcfmvm_custom_infos[$field_id] : '';
          } else {
            $field_value = isset( $wcfmvm_custom_infos[$wcfmvm_registration_custom_field['name']] ) ? $wcfmvm_custom_infos[$wcfmvm_registration_custom_field['name']] : '';
          }
        }
        $wcfm_vendors_json_arr['vendor_additional_info'][$key] = $wcfmvm_registration_custom_field;
        $wcfm_vendors_json_arr['vendor_additional_info'][$key]['value'] = $field_value;
      }

    } else {
      $wcfm_vendors_json_arr['vendor_additional_info'] = array();
    }
    $vendor_settings_data = get_user_meta( $vendor_id, 'wcfmmp_profile_settings', true );
    $wcfm_vendors_json_arr['vendor_data'] = $vendor_settings_data;

    return $wcfm_vendors_json_arr;
  }
}