<?php

if (!defined('ABSPATH'))	exit;

if (!class_exists('Ph_Canada_Post_Deliver_To_Post_Office')) {
	class Ph_Canada_Post_Deliver_To_Post_Office
	{

		/**
		 * Constructor
		 */
		public function __construct($settings = array())
		{
			$this->settings = $settings;
			add_action('wp_head', array($this, 'add_css_class'));
			add_filter('woocommerce_checkout_fields', array($this, 'add_fields_to_checkout'));

			// Giving options to access point select box while calling ajax
			add_filter('woocommerce_update_order_review_fragments', array($this, 'update_deliver_to_po_select_options'), 90, 1);
			// Updating Selected Deliver to Post Office
			add_action('woocommerce_checkout_update_order_review', array($this, 'update_selected_deliver_to_post_office'), 1, 1);
			// Add selected Post Office Delivery location to package
			add_filter('woocommerce_cart_shipping_packages', array($this, 'add_selcted_po_delivery_location_to_packages'));
			// Update the data in order
			add_action('woocommerce_checkout_update_order_meta', array($this, 'update_order_meta'));

			// To DIsplay on Account Page
			add_filter('woocommerce_order_formatted_shipping_address', array($this, 'formatted_shipping_address'), 10, 2);
			add_filter('woocommerce_formatted_address_replacements', array($this, 'address_replacement'), 10, 2);
			add_filter('woocommerce_localisation_address_formats', array($this, 'localized_display_address'));
		}

		public function add_css_class()
		{
			if (!is_checkout()) return; // only on checkout

			echo '<style>
			.show-ca-d2p0{
				display: blocked;
			}
			.hide-ca-d2p0{
				display: none;
			}
			 </style>';

			echo '<script type="text/javascript">
			function handleShippingMethodChange() {
				try {
					var a = "input[name^=' . '\'shipping_method[0]\'' . ']";
					var selected = a + ":checked";
					if (jQuery(selected).attr("id").includes("enable_canada_post_d2po")) {
						jQuery("#ph_canada_post_deliver_to_po_field").removeClass("hide-ca-d2p0");
						jQuery("#ph_canada_post_deliver_to_po_field").addClass("show-ca-d2p0");
					}
					jQuery("form.checkout").on("change", a, function () {
						try {
							selected = a + ":checked";
							var className = "hide-ca-d2p0";
							if (jQuery(selected).attr("id").includes("enable_canada_post_d2po")) {
								className = "show-ca-d2p0";
							}
							jQuery("#ph_canada_post_deliver_to_po_field").removeClass("hide-ca-d2p0");
							jQuery("#ph_canada_post_deliver_to_po_field").removeClass("show-ca-d2p0");
							jQuery("#ph_canada_post_deliver_to_po_field").addClass(className);
						} catch (e) {
			
						}
					});
				} catch (e) {
				}
			}
			jQuery("document").ready(handleShippingMethodChange);
			</script>';
		}

		/**
		 * Add to Formatted Shipping Address.
		 * @param array $shipping_address Shipping Address.
		 * @param object $order WC_Order Object.
		 * @return array
		 */
		public function formatted_shipping_address($shipping_address, $order)
		{
			$po_detail = $order->get_meta('_ph_canada_post_deliver_to_po');
			//error_log(print_r($po_detail, true));
			if (!empty($po_detail)) {
				$po_detail_as_string = $po_detail->value;
				$office_Id_string = (string)$po_detail->key;
				$shipping_address['ph_canada_post_deliver_to_po'] = $po_detail_as_string . ' Office Id ' . $office_Id_string;
			}
			return $shipping_address;
		}

		/**
		 * Add to Address.
		 */
		public function address_replacement($formatted_address, $address)
		{
			$formatted_address['{ph_canada_post_deliver_to_po}'] = !empty($address['ph_canada_post_deliver_to_po']) ?__('Post Office Delivery Location:', '') . ' ' . $address['ph_canada_post_deliver_to_po'] : null;
			return $formatted_address;
		}

		/**
		 * Localize the post office option.
		 */
		public function localized_display_address($formats)
		{
			foreach ($formats as $key => $format) {
				$formats[$key] = $format . "\n{ph_canada_post_deliver_to_po}";
			}
			return $formats;
		}
		/**
		 * Add fields to checkout page.
		 * @param array $fields Array of Checkout Fields
		 * @return array
		 */
		public function add_fields_to_checkout($fields)
		{

			$deliver_to_PO_field = array(
				'label'		=>	__('Select Deliver to Post Office', ''),
				'required'	=>	false,
				'clear'		=> false,
				'type'		=> 'select',
				'class'		=>	array('form-row-wide', 'address-field', 'update_totals_on_change', 'hide-ca-d2p0'),
				'priority'	=>	200,
				'options'     => array(
					'' => __('None', 'wf-shipping-canada-post')
				)
			);
			$fields['billing']['ph_canada_post_deliver_to_po'] = $deliver_to_PO_field;
			return $fields;
		}

		/**
		 * Add Deliver to Post Office Select options
		 * @param array $array
		 * @return array
		 */
		public function update_deliver_to_po_select_options($array)
		{
			if (WC()->session->get('ph_canada_post_deliver_to_po_eligible')) {
				$selected_shipping_method = current(WC()->session->get('chosen_shipping_methods'));
				$shipping_rates = WC()->session->get('sp_shipping_rates');
				if (!empty($shipping_rates[$selected_shipping_method]) && strstr($selected_shipping_method, 'pluginhive_woocommerce_shipping') && $shipping_rates[$selected_shipping_method]['meta_data']['sp_shipping_rates']['canada_post_d2po']) {
					$this->get_nearest_post_office();
				}
			} 
			// else {
			// 	WC()->session->set('ph_canada_post_deliver_to_post_office', []);
			// 	WC()->session->set('ph_canada_post_selected_po_delivery_loc', '');
			// }

			$deliver_to_po_data		=   WC()->session->__get('ph_canada_post_deliver_to_post_office');
			$selected_po			=   WC()->session->get('ph_canada_post_selected_po_delivery_loc');		// Selected
			// parse_str($_POST['post_data'],$data);
// error_log(print_r($data,1));
			if (!empty($deliver_to_po_data) && is_array($deliver_to_po_data)) {
				$data = '<select id="ph_canada_post_deliver_to_po" name="ph_canada_post_deliver_to_po" class="select">';
				$data .= "<option value=''>" . __('None', 'wf-shipping-canada-post') . "</option>";
				foreach ($deliver_to_po_data as $deliver_to_po) {
					$selected	= null;
					$details	= (string)$deliver_to_po->value;
					$option_key = (string)$deliver_to_po->key;
					// error_log(print_r($selected_po, true));
					// error_log(print_r($option_key, true));
					if ($selected_po == $option_key)	$selected = "selected='selected'";		// Check for selected
					$data .= "<option value='" . $option_key . "' $selected>" . $details . "</option>";
				}
				$data .= "</select>";
				$array["#ph_canada_post_deliver_to_po"] = $data;
			}
			return $array;
		}

		/**
		 * Update Selected PO Delivery Location.
		 */
		public function update_selected_deliver_to_post_office($updated_data)
		{
			parse_str($updated_data, $data);
			// error_log(print_r($data['ph_canada_post_deliver_to_po'], true));
			if (isset($data['ph_canada_post_deliver_to_po'])) {
				WC()->session->set('ph_canada_post_selected_po_delivery_loc', $data['ph_canada_post_deliver_to_po']);
			}
		}

		/**
		 * Add Selected Delivery Location to Packages.
		 */
		public function add_selcted_po_delivery_location_to_packages($packages)
		{
			$po_location = WC()->session->get('ph_canada_post_selected_po_delivery_loc');
			if (!empty($po_location)) {
				foreach ($packages as &$package) {
					if (!empty($package['contents'])) {
						$package['ph_canada_post_deliver_to_po'] = $po_location;
					}
				}
			}
			return $packages;
		}

		/**
		 * Add Post Office Details to Order.
		 * @param int $order_id Order Id.
		 */
		public function update_order_meta($order_id)
		{
			// $eligible_shipping_methods_po_delivery = array( 'wf_shipping_canada_post:DOM.XP', 'wf_shipping_canada_post:DOM.EP' );	// Only these two shipping methods eligible for Deliver to Post Office
			$matched_shipping_method = $_POST['shipping_method'];
			if (!empty($_POST['ph_canada_post_deliver_to_po']) && !empty($matched_shipping_method)) {
				$selected_office_id = $_POST['ph_canada_post_deliver_to_po'];
				$post_office_details = WC()->session->get('ph_canada_post_deliver_to_post_office');
				foreach ($post_office_details as $post_office_detail) {
					if ((string)$post_office_detail->key == $selected_office_id) {
						$post_office_data = array(
							office_id => $post_office_detail->key,
							office_name_location => $post_office_detail->value,
						);
						update_post_meta($order_id, '_ph_canada_post_deliver_to_po', $post_office_detail);
					}
				}
			}
			WC()->session->__unset('ph_canada_post_deliver_to_post_office');
			WC()->session->__unset('ph_canada_post_selected_po_delivery_loc');
		}

		/**
		 * Get Nearest Post Office.
		 * @param array $address Address.
		 * @param boolean $production True for Production Mode.
		 * @param array $canada_post_settings Canada Post settings.
		 * @return mixed
		 */
		public function get_nearest_post_office()
		{
			$cart     = current(WC()->cart->get_shipping_packages());
			$address  = $cart['destination'];
			$postcode =	$address['postcode'];
			$province =	$address['state'];
			$city     =	$address['city'];
			$street   =	$address['address_1'] . ' ' . $address['address_2'];

			$transient = 'ph_canada_post' . md5(PLUGINHIVE_WC_CANADA_POST_POST_OFFICE_LIST_URL);
			$cached_data = get_transient($transient);
			if (!empty($cached_data)) {
				WC()->session->set('ph_canada_post_deliver_to_post_office', $cached_data);
			}
			$selected_shipping_method = current(WC()->session->get('chosen_shipping_methods'));

			$destinationAddress = array(
				'streetAddress1' => '',
				'city' => $address['city'],
				'stateCode' => $address['state'],
				'postalCode' => $address['postcode'],
				'countryCode' => $address['country'],
			);
			// error_log(print_r($selected_shipping_method, true));
			$selectedServiceArray = explode(':', $selected_shipping_method);
			// error_log(print_r($selectedServiceArray, true));
			$request_data = array(
				'selectedService' => $selectedServiceArray[1],
				'destinationAddress' => $destinationAddress,
			);
			$data = json_encode($request_data);
			$response = wp_remote_post(
				PLUGINHIVE_WC_CANADA_POST_POST_OFFICE_LIST_URL,
				array(
					'headers'	=>	array(
						'authorization'	=>	"Bearer " . $this->settings['integration_id'],
						'Content-Type'	=>	"application/json",
					),
					'timeout'	=>	20,
					'body'		=>	$data,
				)
			);
			// Check for WP Error
			if (is_wp_error($response)) {
				if ($canada_post_settings['debug'] == 'yes' && !is_admin())	wc_add_notice('CandaPost Deliver to Post Office' . $response->get_error_message());
				WC()->session->__unset('ph_canada_post_deliver_to_post_office');
				return false;
			}

			$error_message = null;
			if ($response['response']['code'] == 200) {
				$body = (array)json_decode($response['body']);

				if ($body['success'] && count($body['postOfficeList'])) {
					$data = $body['postOfficeList'];
					set_transient($transient, $data, 7 * 24 * 60 * 60);
					WC()->session->set('ph_canada_post_deliver_to_post_office', $data);
				}
			}
		}
	}
}