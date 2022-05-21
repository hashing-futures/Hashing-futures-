<?php
/**
 * Fedex Shipping Method.
 *
 * @package WPDesk\FlexibleShippingFedex
 */

namespace WPDesk\FlexibleShippingFedex;

use FedExVendor\WPDesk\FedexShippingService\FedexSettingsDefinition;
use FedExVendor\WPDesk\FedexShippingService\FedexShippingService;
use FedExVendor\WPDesk\WooCommerceShipping\CustomFields\ApiStatus\FieldApiStatus;
use FedExVendor\WPDesk\WooCommerceShipping\CustomFields\ApiStatus\FieldApiStatusAjax;
use FedExVendor\WPDesk\WooCommerceShipping\ShippingMethod;

/**
 * Fedex Shipping Method.
 */
class FedexShippingMethod extends ShippingMethod {

	const UNIQUE_ID = 'flexible_shipping_fedex';

	/**
	 * .
	 *
	 * @var FieldApiStatusAjax
	 */
	protected static $api_status_ajax_handler;

	/**
	 * .
	 *
	 * @param int $instance_id Instance ID.
	 */
	public function __construct( $instance_id = 0 ) {
		parent::__construct( $instance_id );
		$this->title = $this->get_option( 'title', $this->title );
	}

}
