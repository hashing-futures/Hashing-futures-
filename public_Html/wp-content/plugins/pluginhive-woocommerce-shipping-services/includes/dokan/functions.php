<?php
add_filter('dokan_query_var_filter', 'dokan_load_document_menu');
function dokan_load_document_menu($query_vars)
{
  $query_vars['pluginhive'] = 'pluginhive';
  PH_WSS_Common_Functions::ph_wss_admin_diagnostic_report('PluginHive Dokan Query Data:' .print_r($query_vars,true));
  return $query_vars;
}
add_filter('dokan_get_dashboard_nav', 'dokan_add_help_menu');
function dokan_add_help_menu($urls)
{
  // error_log(print_r(dokan_get_navigation_url('help'), true));
  $settigns = get_option('woocommerce_pluginhive_settings', array());
	$custom_name = isset($settigns['custom_name']) ? $settigns['custom_name'] : 'Shipping (PSS)';
  $urls['pss'] = array(
    'title' => __($custom_name, 'dokan'),
    'icon'  => '<i class="fa fa-user"></i>',
    'url'   => dokan_get_navigation_url('pluginhive'),
    'pos'   => 51
  );
  PH_WSS_Common_Functions::ph_wss_admin_diagnostic_report('PluginHive Dokan URLS :' .print_r($urls,true));
  return $urls;
}
add_action('dokan_load_custom_template', 'dokan_load_template');
function dokan_load_template($query_vars)
{
  if (isset($query_vars['pluginhive'])) {
    require_once dirname(__FILE__) . '/dokanWrapper.php';
    PH_WSS_Common_Functions::ph_wss_admin_diagnostic_report('------------PluginHive- Loading Custom Dokan template-------');
    //exit();
  }
}