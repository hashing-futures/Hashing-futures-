<?php

namespace FedExVendor\WPDesk\License;

use FedExVendor\WPDesk\License\ActivationForm\AjaxHandler;
use FedExVendor\WPDesk\License\ActivationForm\PluginsPageRenderer;
use FedExVendor\WPDesk\PluginBuilder\Plugin\HookableCollection;
use FedExVendor\WPDesk\PluginBuilder\Plugin\HookableParent;
use FedExVendor\WPDesk_API_Manager_With_Update_Flag;
use FedExVendor\WPDesk_Plugin_Info;
class LicenseManager implements \FedExVendor\WPDesk\PluginBuilder\Plugin\HookableCollection
{
    use HookableParent;
    /**
     * @var WPDesk_Plugin_Info
     */
    private $plugin_info;
    /**
     * @var PluginsPageRenderer
     */
    private $plugins_page_renderer;
    /**
     * @var AjaxHandler
     */
    private $ajax_handler;
    /**
     * @param WPDesk_Plugin_Info $plugin_info
     */
    public function __construct(\FedExVendor\WPDesk_Plugin_Info $plugin_info)
    {
        $this->plugin_info = $plugin_info;
    }
    /**
     * @param bool $hooks_to_updates
     *
     * @return WPDesk_API_Manager_With_Update_Flag
     */
    public function create_api_manager(bool $hook_to_updates = \true) : \FedExVendor\WPDesk_API_Manager_With_Update_Flag
    {
        $address_repository = new \FedExVendor\WPDesk\License\ServerAddressRepository($this->plugin_info->get_product_id());
        return new \FedExVendor\WPDesk_API_Manager_With_Update_Flag($address_repository->get_default_update_url(), $this->plugin_info->get_version(), $this->plugin_info->get_plugin_file_name(), $this->plugin_info->get_product_id(), $this->plugin_info->get_plugin_file_name(), $this->plugin_info->get_plugin_slug(), $hook_to_updates, $this->plugin_info->get_plugin_name());
    }
    /**
     *
     */
    public function init_activation_form()
    {
        $plugin_slug = $this->plugin_info->get_plugin_slug();
        $plugin_file = $this->plugin_info->get_plugin_file_name();
        $this->plugins_page_renderer = new \FedExVendor\WPDesk\License\ActivationForm\PluginsPageRenderer($this->plugin_info);
        $this->add_hookable($this->plugins_page_renderer);
        $this->ajax_handler = new \FedExVendor\WPDesk\License\ActivationForm\AjaxHandler($this->plugin_info);
        $this->add_hookable($this->ajax_handler);
    }
    /**
     * .
     */
    public function hooks()
    {
        $this->hooks_on_hookable_objects();
    }
    /**
     * @return PluginsPageRenderer
     */
    public function get_plugins_page_renderer() : \FedExVendor\WPDesk\License\ActivationForm\PluginsPageRenderer
    {
        return $this->plugins_page_renderer;
    }
    /**
     * @return AjaxHandler
     */
    public function get_ajax_handler() : \FedExVendor\WPDesk\License\ActivationForm\AjaxHandler
    {
        return $this->ajax_handler;
    }
}