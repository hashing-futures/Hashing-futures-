<?php

class Sp_mv_stores_list extends WP_REST_Controller
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
    protected $base = 'stores';

    /**
     * Register all routes releated with stores
     *
     * @return void
     */
    public function register_routes()
    {
        register_rest_route($this->namespace, '/' . $this->base, array(
            array(
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_stores'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
                'args'     => $this->get_collection_params()
            )
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
        $token = $request->get_header('authorizationToken');
		//error_log(print_r($token, 1));
		$plugin_settings_from_db = get_option(SP_PLUGIN_ID, true);
		//error_log(print_r($plugin_settings_from_db, 1));
		if($token !== $plugin_settings_from_db['api-token']) {
			return false;//new \WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce' ), array( 'status' => \rest_authorization_required_code() ) );
		}
		return true;
    }

    /**
     * Get stores
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function get_stores($request)
    {
        try {
            $params = $request->get_params();

            $args = array(
                'number' => (int) $params['per_page'],
                'offset' => (int) ($params['page'] - 1) * $params['per_page']
            );

            if (!empty($params['search'])) {
                $args['search']         = '*' . sanitize_text_field(($params['search'])) . '*';
                $args['search_columns'] = array('user_login', 'user_email', 'display_name');
            }

            if (!empty($params['status'])) {
                $args['status'] = sanitize_text_field($params['status']);
            }

            if (!empty($params['orderby'])) {
                $args['orderby'] = sanitize_sql_orderby($params['orderby']);
            }

            if (!empty($params['order'])) {
                $args['order'] = sanitize_text_field($params['order']);
            }

            if (!empty($params['featured'])) {
                $args['featured'] = sanitize_text_field($params['featured']);
            }

            if (!function_exists('dokan')) {
                return [];
            }

            $stores       = dokan()->vendor->get_vendors($args);
            PH_WSS_Common_Functions::ph_wss_admin_diagnostic_report( 'Available stores:'.print_r($stores,true));
            $data_objects = array();

            foreach ($stores as $store) {
                $stores_data    = $this->prepare_item_for_response($store, $request);
                $data_objects[] = $this->prepare_response_for_collection($stores_data);
            }

            $response = rest_ensure_response($data_objects);
            $response = $this->format_collection_response($response, $request, dokan()->vendor->get_total());

            return $response;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Prepare links for the request.
     *
     * @param WC_Data         $object  Object data.
     * @param WP_REST_Request $request Request object.
     *
     * @return array                   Links for the given post.
     */
    protected function prepare_links($object, $request)
    {
        $links = array(
            'self' => array(
                'href' => rest_url(sprintf('/%s/%s/%d', $this->namespace, $this->base, $object['id'])),
            ),
            'collection' => array(
                'href' => rest_url(sprintf('/%s/%s', $this->namespace, $this->base)),
            ),
        );

        return $links;
    }

    /**
     * Format item's collection for response
     *
     * @param  object $response
     * @param  object $request
     * @param  array $items
     * @param  int $total_items
     *
     * @return object
     */
    public function format_collection_response($response, $request, $total_items)
    {

        // Store pagation values for headers then unset for count query.
        $per_page  = (int) (!empty($request['per_page']) ? $request['per_page'] : 20);
        $page      = (int) (!empty($request['page']) ? $request['page'] : 1);
        $max_pages = ceil($total_items / $per_page);

        if (function_exists('dokan_get_seller_status_count') && current_user_can('manage_options')) {
            $counts = dokan_get_seller_status_count();
            $response->header('X-Status-Pending', (int) $counts['inactive']);
            $response->header('X-Status-Approved', (int) $counts['active']);
            $response->header('X-Status-All', (int) $counts['total']);
        }

        $response->header('X-WP-Total', (int) $total_items);
        $response->header('X-WP-TotalPages', (int) $max_pages);

        if ($total_items === 0) {
            return $response;
        }

        $base = add_query_arg($request->get_query_params(), rest_url(sprintf('/%s/%s', $this->namespace, $this->base)));

        if ($page > 1) {
            $prev_page = $page - 1;

            if ($prev_page > $max_pages) {
                $prev_page = $max_pages;
            }

            $prev_link = add_query_arg('page', $prev_page, $base);
            $response->link_header('prev', $prev_link);
        }

        if ($max_pages > $page) {

            $next_page = $page + 1;
            $next_link = add_query_arg('page', $next_page, $base);
            $response->link_header('next', $next_link);
        }

        return $response;
    }

    /**
     * Prepare a single user output for response
     *
     * @param object $item
     * @param WP_REST_Request $request Request object.
     * @param array $additional_fields (optional)
     *
     * @return WP_REST_Response $response Response data.
     */
    public function prepare_item_for_response($store, $request, $additional_fields = [])
    {

        $data = $store->to_array();
        $data = array_merge($data, apply_filters('dokan_rest_store_additional_fields', $additional_fields, $store, $request));
        $response = rest_ensure_response($data);
        $response->add_links($this->prepare_links($data, $request));

        return apply_filters('dokan_rest_prepare_store_item_for_response', $response);
    }
}