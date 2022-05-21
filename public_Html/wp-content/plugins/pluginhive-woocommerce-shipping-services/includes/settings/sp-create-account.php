<?php
if (!defined('ABSPATH'))  exit;

if (!class_exists('SP_Create_Account')) {
  class SP_Create_Account
  {
    public function __construct()
    {
      add_action('wp_ajax_sp_configure_account', array($this, 'sp_configure_account'));
      add_action('wp_ajax_sp_sync_store', array($this, 'sp_sync_store'));
      add_action('wp_ajax_sp_resync_account', array($this, 'sp_resync_account'));
      add_action('wp_ajax_clear_plugin_data', array($this, 'clear_plugin_data'));
      if (!class_exists("PH_WSS_Common_Functions")) {
        require_once '../../ph-wss-common-functions.php';
      }
    }

    protected function get_option($key)
    {
      return get_option($key, true);
    }

    protected function update_option($key, $value)
    {
      update_option($key, $value);
      return $value;
    }

    protected function update_plugin_settings($response, $request_body, $helper)
    {
      $plugin_settings = get_option(SP_PLUGIN_ID, true);
      if (!is_array($plugin_settings)) {
        $plugin_settings = [];
      }
      $plugin_settings['enabled'] = $request_body['enabled'] ? 'yes' : 'no';
      $plugin_settings['enabled_rates'] = $request_body['enabled_rates'] ? 'yes' : 'no';
      //$plugin_settings['consumer_key'] = $request_body['consumer_key'];
      //$plugin_settings['consumer_secret'] = $request_body['consumer_secret'];
      $plugin_settings['synced_store'] = $helper['storeUrl'];
      $plugin_settings['synced_email'] = $helper['email'];
      $plugin_settings['asp_account_id'] = $response->account->accountId;
      $plugin_settings['integration_id'] = $response->apiDetails->integrationId;
      $plugin_settings['secret_key'] = $response->apiDetails->secretKey;
      // $plugin_settings['user_id'] = get_current_user_id();
      return $this->update_option(SP_PLUGIN_ID, $plugin_settings);
    }

    protected function format_response($array)
    {
      return json_encode($array);
    }

    protected function make_request($url, $data, $headers = [])
    {
      $headers['Content-Type'] = 'application/json';
      $response = wp_remote_post(
        $url,
        array(
          'headers'  => $headers,
          'timeout'  =>  20,
          'body'    =>  json_encode($data),
        )
      );

      if (is_wp_error($response)) {
        $response_data = [];
        $error_string = $response->get_error_message();
        $response_data['success'] = false;
        $response_data['message'] = $error_string;
        return $response_data;
      }
      return json_decode($response['body']);
    }

    protected function sync_address($plugin_settings, $helper)
    {
      $address_info = [];
      $address_info['addressLine1'] = $this->get_option('woocommerce_store_address');
      $address_info['addressLine2'] = $this->get_option('woocommerce_store_address_2');
      $address_info['city'] = $this->get_option('woocommerce_store_city');
      $countryState = explode(':', $this->get_option('woocommerce_default_country'));
      $address_info['country'] = $countryState[0];
      $address_info['stateCode'] = isset($countryState[1]) ? $countryState[1] : '';
      $address_info['postalCode'] = $this->get_option('woocommerce_store_postcode');
      $address_info['companyName'] = $helper['storeName'];
      $address_info['phoneNumber'] = $helper['phoneNumber'];
      $address_info['email'] = $helper['email'];
      $address_info['personName'] = $helper['firstName'] . '' . ($helper['lastName'] ? $helper['lastName'] : '');

      $request_object = [];
      $request_object['address'] = $address_info;
      $request_object['accountId'] = $plugin_settings['asp_account_id'];
      $headers['authorization']  =  "Bearer " . $plugin_settings['integration_id'];
      $response = $this->make_request(SP_SYNC_ADDRESS_ENDPOINT, $request_object,   $headers);
      if ($response && isset($response->success) && $response->success) {
        $plugin_settings_from_db = $this->get_option(SP_PLUGIN_ID, true);
        $plugin_settings_from_db['addressId'] = $response->addressId;
        $updated_plugin_settings = $this->update_option(SP_PLUGIN_ID, $plugin_settings_from_db);
        return [
          'success' => 1,
          'message' => 'successfully completed.'
        ];
      }
      return $response;
    }



    protected function sync_store($plugin_settings, $helper)
    {
      $storeInfo = [];
      $storeInfo['api_token'] = bin2hex(random_bytes(16));
      $storeInfo['site_url'] = $plugin_settings['synced_store'];
      $storeInfo['store_name'] = $helper['storeName'];
      $storeInfo['currency'] = $this->get_option('woocommerce_currency');
      $storeInfo['weight_unit'] = $this->get_option('woocommerce_weight_unit');
      $storeInfo['dimensions_unit'] = $this->get_option('woocommerce_dimension_unit');
      $request_object = [];
      $request_object['store'] = $storeInfo;
      $request_object['accountId'] = $plugin_settings['asp_account_id'];

      $headers = [];
      $headers['authorization']  =  "Bearer " . $plugin_settings['integration_id'];
      //error_log(print_r($request_object, 1));
      PH_WSS_Common_Functions::ph_wss_admin_diagnostic_report('PluginHive Sync Request Data :'.print_r($request_object,true));
      $response = $this->make_request(SP_SYNC_STORE_ENDPOINT, $request_object,   $headers);
      PH_WSS_Common_Functions::ph_wss_admin_diagnostic_report('PluginHive Sync Response Data :'.print_r($response,true));
      if ($response && isset($response->success) && $response->success) {
        $plugin_settings_from_db = $this->get_option(SP_PLUGIN_ID, true);
        $plugin_settings_from_db['store_uuid'] = $response->storeId;
        $plugin_settings_from_db['api-token'] = $response->apiToken;
        //error_log(print_r($plugin_settings_from_db, 1));
        $updated_plugin_settings = $this->update_option(SP_PLUGIN_ID, $plugin_settings_from_db);
        return $updated_plugin_settings;
      }
      return $response;
    }


    protected function sync_with_addonstation($plugin_settings, $helper)
    {
      $sync_store_result = $this->sync_store($plugin_settings, $helper);
      if (is_array($sync_store_result) && isset($sync_store_result['store_uuid'])) {
        return [
          'success' => 1,
          'message' => 'Successfully completed Sync store',
        ];
      }
      return $sync_store_result;
    }

    public function sp_configure_account()
    {
      try {
        $plugin_settings = get_option(SP_PLUGIN_ID, true);
        if (!is_array($plugin_settings)) {
          $plugin_settings = [];
        }
        $plugin_settings['user_id'] = get_current_user_id();
        $this->update_option(SP_PLUGIN_ID, $plugin_settings);
        $request_body = $_POST['data'];
        $currentUser = wp_get_current_user();
        $request_object = [];
        $request_object['email'] = get_option('admin_email');
        $request_object['storeUrl'] = get_site_url();
        // $request_object['consumer_key'] = $request_body['consumer_key'];
        // $request_object['consumer_secret'] = $request_body['consumer_secret'];
        $request_object['storeName'] = get_option('blogname');
        if ($currentUser->user_firstname) {
          $request_object['firstName'] = $currentUser->user_firstname;
        }
        if (!$request_object['firstName']) {
          $request_object['firstName'] = $request_object['storeName'];
        }
        if (!$request_object['firstName']) {
          $request_object['firstName'] = '--';
        }

        $request_object['lastName'] = $currentUser->user_lastname;
        if (!$request_object['lastName']) {
          $request_object['lastName'] = $request_object['firstName'];
        }
        $request_object['phoneNumber'] = get_user_meta($currentUser->ID, 'billing_phone', true);
        $request_object['storeType'] = SP_WC_STORE_TYPE;
        $request_object['UTCTimeZoneOffset'] = $this->get_option('gmt_offset') * 60;
        $request_object['TimeStamp'] = current_time('timestamp');
        $request_object['platform'] = 'PLUGINHIVE';
        PH_WSS_Common_Functions::ph_wss_admin_diagnostic_report('PluginHive Configure Account Request Data :'.print_r($request_object,true));
        $response_body = $this->make_request(SP_ACCOUNT_REGISTER_ENDPOINT, $request_object);
        PH_WSS_Common_Functions::ph_wss_admin_diagnostic_report('PluginHive Configure Account Response Data :'.print_r($response_body,true));
        if ($response_body && isset($response_body->success) && $response_body->success) {
          $updated_plugin_settings = $this->update_plugin_settings($response_body,  $request_body, $request_object);
          $this->sync_with_addonstation($updated_plugin_settings, $request_object);
          $response = $this->format_response($this->sync_address($updated_plugin_settings, $request_object));
          echo $response;
          exit;
        } else {
          echo  $this->format_response($response_body);
          exit;
        }
      } catch (\Throwable $th) {
        echo $th;
      }
    }

    public function clear_plugin_data()
    {
      try {
        $plugin_settings = get_option(SP_PLUGIN_ID, true);
        $headers['authorization']  =  "Bearer " . $plugin_settings['integration_id'];
        $is_deleted = delete_option(SP_PLUGIN_ID, true);
        if ($is_deleted) {
          $this->make_request(SP_ACCOUNT_DELETION_REQUEST_ENDPOINT, $plugin_settings, $headers);
          PH_WSS_Common_Functions::ph_wss_admin_diagnostic_report('PluginHive Clear Plugin Data :   Headers -'.print_r($headers,true).' Plugin Settings-'.print_r($plugin_settings,true));
          echo $this->format_response([
            'success' => 1,
          ]);
        } else {
          echo $this->format_response([
            'success' => 0,
          ]);
        }
        exit;
      } catch (\Throwable $th) {
        echo $th;
      }
    }

    public function sp_resync_account()
    {
      try {
        $plugin_settings = get_option(SP_PLUGIN_ID, true);
        $request_object = [];
        $request_object['email'] = get_option('admin_email');
        $request_object['storeUrl'] = get_site_url();
        $request_object['synced_store'] = $plugin_settings['synced_store'];
        $request_object['synced_email'] = $plugin_settings['synced_email'];
        $request_object['accountId'] = $plugin_settings['asp_account_id'];
        $request_object['apiToken'] = $plugin_settings['api-token'];
        $headers = [];
        $headers['authorization']  =  "Bearer " . $plugin_settings['integration_id'];
        // error_log(print_r('headers', 1));
        // error_log(print_r($headers, 1));
        PH_WSS_Common_Functions::ph_wss_admin_diagnostic_report('PluginHive Resync Request Data :'.print_r($request_object,true));
        $response_body = $this->make_request(SP_ACCOUNT_RESYNC_ENDPOINT, $request_object, $headers);
        PH_WSS_Common_Functions::ph_wss_admin_diagnostic_report('PluginHive Resync Response Data :'.print_r($response_body,true));
        // error_log(print_r('reload', 1));
        // error_log(print_r($response_body, 1));
        if ($response_body && isset($response_body->success) && $response_body->success) {
          $plugin_settings_from_db = $this->get_option(SP_PLUGIN_ID, true);
          $plugin_settings_from_db['synced_store'] = $response_body->storeUrl;
          $plugin_settings_from_db['synced_email'] = $response_body->email;
          $updated_plugin_settings = $this->update_option(SP_PLUGIN_ID, $plugin_settings_from_db);
          // return [
          //   'success' => 1,
          //   'message' => 'Successfully resynced the store',
          // ];
          echo $this->format_response([
            'success' => 1,
            'message' => 'Successfully resynced the store',
          ]);
          exit;
        } else {
          // error_log(print_r('inside else', 1));
          echo  $this->format_response($response_body);
          exit;
        }
      } catch (\Throwable $th) {
        echo $th;
      }
    }

    public function sp_sync_store()
    {
      try {
        $request_body = $_POST['data'];
        $currentUser = wp_get_current_user();
        $request_object = [];
        $request_object['email'] = get_option('admin_email');
        $request_object['storeUrl'] = get_site_url();
        // $request_object['consumer_key'] = $request_body['consumer_key'];
        // $request_object['consumer_secret'] = $request_body['consumer_secret'];
        $request_object['firstName'] = $currentUser->user_firstname;
        $request_object['lastName'] = $currentUser->user_lastname;
        $request_object['phoneNumber'] = get_user_meta($currentUser->ID, 'billing_phone', true);
        $request_object['storeName'] = get_option('blogname');
        $request_object['storeType'] = SP_WC_STORE_TYPE;
        $request_object['UTCTimeZoneOffset'] = $this->get_option('gmt_offset') * 60;
        $request_object['TimeStamp'] = current_time('timestamp');
        $plugin_settings_from_db = $this->get_option(SP_PLUGIN_ID, true);
        echo $this->format_response($this->sync_with_addonstation($plugin_settings_from_db, $request_object));
        exit;
      } catch (\Throwable $th) {
        echo $th;
      }
    }
  }
  new SP_Create_Account();
}
