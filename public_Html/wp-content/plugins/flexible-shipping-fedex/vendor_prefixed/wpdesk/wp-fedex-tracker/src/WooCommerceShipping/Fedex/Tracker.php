<?php

/**
 * Tracker
 *
 * @package WPDesk\WooCommerceShipping\Fedex
 */
namespace FedExVendor\WPDesk\WooCommerceShipping\Fedex;

use FedExVendor\WPDesk\FedexShippingService\FedexSettingsDefinition;
use FedExVendor\WPDesk\PluginBuilder\Plugin\Hookable;
use FedExVendor\WPDesk\WooCommerceShipping\ShippingMethod;
/**
 * Handles tracker actions.
 */
class Tracker implements \FedExVendor\WPDesk\PluginBuilder\Plugin\Hookable
{
    const OPTION_VALUE_NO = 'no';
    const OPTION_VALUE_YES = 'yes';
    /**
     * Plugin file.
     *
     * @var string
     */
    private $plugin_file;
    /**
     * Tracker constructor.
     *
     * @param string $plugin_file .
     */
    public function __construct($plugin_file = 'flexible-shipping-fedex/flexible-shipping-fedex.php')
    {
        $this->plugin_file = $plugin_file;
    }
    /**
     * Hooks.
     */
    public function hooks()
    {
        \add_filter('wpdesk_tracker_data', array($this, 'wpdesk_tracker_data_fedex'), 11);
        \add_filter('wpdesk_tracker_notice_screens', array($this, 'wpdesk_tracker_notice_screens'));
        if ($this->plugin_file) {
            \add_filter('plugin_action_links_' . $this->plugin_file, array($this, 'plugin_action_links'), 9);
        }
    }
    /**
     * Prepare default plugin data.
     *
     * @param ShippingMethod $flexible_shipping_fedex Shipping method.
     *
     * @return array
     */
    protected function prepare_plugin_data(\FedExVendor\WPDesk\WooCommerceShipping\ShippingMethod $flexible_shipping_fedex)
    {
        $custom_services = $flexible_shipping_fedex->get_option(\FedExVendor\WPDesk\FedexShippingService\FedexSettingsDefinition::FIELD_ENABLE_CUSTOM_SERVICES, self::OPTION_VALUE_NO);
        return array('pro_version' => 'no', 'enable_shipping_method' => $flexible_shipping_fedex->get_option('enable_shipping_method', self::OPTION_VALUE_NO), 'title' => $flexible_shipping_fedex->get_option('title', 'FedEx'), 'fallback' => $flexible_shipping_fedex->get_option(\FedExVendor\WPDesk\FedexShippingService\FedexSettingsDefinition::FIELD_FALLBACK, self::OPTION_VALUE_NO), 'custom_services' => $custom_services, 'insurance' => $flexible_shipping_fedex->get_option(\FedExVendor\WPDesk\FedexShippingService\FedexSettingsDefinition::FIELD_INSURANCE, self::OPTION_VALUE_NO), 'request_type' => $flexible_shipping_fedex->get_option(\FedExVendor\WPDesk\FedexShippingService\FedexSettingsDefinition::FIELD_REQUEST_TYPE, ''), 'destination_address_type' => $flexible_shipping_fedex->get_option(\FedExVendor\WPDesk\FedexShippingService\FedexSettingsDefinition::FIELD_DESTINATION_ADDRESS_TYPE, ''), 'debug_mode' => $flexible_shipping_fedex->get_option('debug_mode', self::OPTION_VALUE_NO), 'units' => $flexible_shipping_fedex->get_option(\FedExVendor\WPDesk\FedexShippingService\FedexSettingsDefinition::FIELD_UNITS, 'imperial'), 'origin_country' => $this->get_origin_country($flexible_shipping_fedex), 'fedex_services' => $this->prepare_custom_services($custom_services, $flexible_shipping_fedex->get_option(\FedExVendor\WPDesk\FedexShippingService\FedexSettingsDefinition::FIELD_SERVICES_TABLE)));
    }
    /**
     * @param string $custom_services
     * @param array $services_table
     *
     * @return array
     */
    private function prepare_custom_services($custom_services, $services_table)
    {
        $service_array = [];
        if (self::OPTION_VALUE_YES === $custom_services) {
            foreach ($services_table as $key => $service) {
                if (isset($service['enabled'])) {
                    $service_array[$service['enabled']] = 1;
                }
            }
        }
        return $service_array;
    }
    /**
     * @param ShippingMethod $flexible_shipping_fedex Shipping method.
     *
     * @return string
     */
    protected function get_origin_country(\FedExVendor\WPDesk\WooCommerceShipping\ShippingMethod $flexible_shipping_fedex)
    {
        list($origin_country) = \explode(':', \get_option('woocommerce_default_country', ''));
        return $origin_country;
    }
    /**
     * Add plugin data tracker.
     *
     * @param array $data Data.
     *
     * @return array
     */
    public function wpdesk_tracker_data_fedex(array $data)
    {
        $shipping_methods = \WC()->shipping()->get_shipping_methods();
        if (isset($shipping_methods['flexible_shipping_fedex'])) {
            /** @var ShippingMethod $flexible_shipping_fedex */
            $flexible_shipping_fedex = $shipping_methods['flexible_shipping_fedex'];
            $plugin_data = $this->prepare_plugin_data($flexible_shipping_fedex);
            $data['flexible_shipping_fedex'] = $plugin_data;
        }
        return $data;
    }
    /**
     * Add Fedex settings to tracker screens.
     *
     * @param array $screens .
     *
     * @return array
     */
    public function wpdesk_tracker_notice_screens($screens)
    {
        $current_screen = \get_current_screen();
        if ($current_screen instanceof \FedExVendor\WPDesk\WooCommerceShipping\Fedex\WP_Screen) {
            if ('woocommerce_page_wc-settings' === $current_screen->id) {
                if (isset($_GET['tab']) && 'shipping' === $_GET['tab'] && isset($_GET['section']) && 'flexible_shipping_fedex' === $_GET['section']) {
                    // WPCS: Input var okay. CSRF ok.
                    $screens[] = $current_screen->id;
                }
            }
        }
        return $screens;
    }
    /**
     * Opt in/opt out action links.
     *
     * @param array $links .
     *
     * @return array
     */
    public function plugin_action_links($links)
    {
        if (!$this->is_tracker_enabled() || \apply_filters('wpdesk_tracker_do_not_ask', \false)) {
            return $links;
        }
        $options = \get_option('wpdesk_helper_options', array());
        if (!\is_array($options)) {
            $options = array();
        }
        if (empty($options['wpdesk_tracker_agree'])) {
            $options['wpdesk_tracker_agree'] = '0';
        }
        $plugin_links = array();
        if (0 === \intval($options['wpdesk_tracker_agree'])) {
            $opt_in_link = \admin_url('admin.php?page=wpdesk_tracker&plugin=flexible-shipping-fedex/flexible-shipping-fedex.php');
            $plugin_links[] = '<a href="' . $opt_in_link . '">' . \__('Opt-in', 'flexible-shipping-fedex') . '</a>';
        } else {
            $opt_in_link = \admin_url('plugins.php?wpdesk_tracker_opt_out=1&plugin=flexible-shipping-fedex/flexible-shipping-fedex.php');
            $plugin_links[] = '<a href="' . $opt_in_link . '">' . \__('Opt-out', 'flexible-shipping-fedex') . '</a>';
        }
        return \array_merge($plugin_links, $links);
    }
    /**
     * Is WPDesk Tracker enabled?
     *
     * @return bool
     */
    private function is_tracker_enabled()
    {
        $tracker_enabled = \true;
        if (!empty($_SERVER['SERVER_ADDR']) && '127.0.0.1' === $_SERVER['SERVER_ADDR']) {
            // WPCS: Input var okay.
            $tracker_enabled = \false;
        }
        return \apply_filters('wpdesk_tracker_enabled', $tracker_enabled);
    }
}
