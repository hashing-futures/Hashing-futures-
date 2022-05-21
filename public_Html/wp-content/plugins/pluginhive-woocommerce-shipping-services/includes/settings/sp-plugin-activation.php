<?php
if (!defined('ABSPATH')) {
    exit;
}
class SP_Plugin_Activation
{
    public function __construct()
    {
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
                'headers' => $headers,
                'timeout' => 20,
                'body' => json_encode($data),
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

    public function sp_send_activation_notification()
    {
        try {

            $plugin_settings_from_db    = $this->get_option(SP_PLUGIN_ID, true);
            $integration_id             = isset($plugin_settings_from_db['integration_id']) ? $plugin_settings_from_db['integration_id'] : '';

            if(!empty($integration_id )) {

                $request_object = [];
                $request_object['email'] = get_option('admin_email');
                $request_object['storeUrl'] = get_site_url();
                $request_object['accountId'] = isset($plugin_settings_from_db['asp_account_id']) ? $plugin_settings_from_db['asp_account_id'] : '';
                
                $headers = [];
                $headers['authorization'] = "Bearer " . $integration_id;
                
                $this->make_request(SP_ACTIVATION_NOTIFICATION_ENDPOINT, $request_object, $headers);   
            }
        } catch (\Throwable $th) {
            echo $th;
        }
    }
}