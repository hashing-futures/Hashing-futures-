jQuery( document ).ready( function( $ ) {

    window.addEventListener('load', function() {

        // Hide 'Active' option
        jQuery('#woocommerce_pluginhive_woocommerce_shipping_enabled').closest('tr').hide();

        function hideSaveButton() {
            if (jQuery('p.submit button.woocommerce-save-button').prop('type') == 'submit') {
                jQuery('p.submit button.woocommerce-save-button').hide();
            }
        }

        // To create button to get the account details
        var ajaxUrl = sp_admin_script.ajaxUrl;
        var storeData = JSON.parse(sp_admin_script.storeData);

        if (!storeData || (!storeData.asp_account_id || !storeData.integration_id || !storeData.secret_key)) {
            console.log('if: ');
            // Hide Setup
            jQuery('#woocommerce_pluginhive_woocommerce_shipping_other_settings').closest('h3').hide();
            jQuery('#woocommerce_pluginhive_woocommerce_shipping_enabled_rates').closest('tr').hide();
            jQuery('#woocommerce_pluginhive_woocommerce_shipping_debug').closest('tr').hide();
    jQuery('#woocommerce_pluginhive_woocommerce_shipping_fallback_rate').closest('tr').hide();
    jQuery('#woocommerce_pluginhive_woocommerce_shipping_change_status').closest('tr').hide();
    jQuery('#woocommerce_pluginhive_woocommerce_shipping_show_clear_plugin_data_button').closest('tr').hide();
    jQuery('#woocommerce_pluginhive_woocommerce_shipping_clear_button').closest('tr').hide();
            jQuery('#woocommerce_pluginhive_woocommerce_shipping_tax_calculation_mode').closest('tr').hide();

            jQuery('#woocommerce_pluginhive_woocommerce_shipping_setup_button').removeClass().addClass('button-primary woocommerce-save-button').val('Register');
            hideSaveButton();
        } else if (storeData && !storeData.store_uuid) {

            jQuery('#woocommerce_pluginhive_woocommerce_shipping_setup_button').attr('id', 'woocommerce_pluginhive_woocommerce_shipping_sync_store_button')
            jQuery('#woocommerce_pluginhive_woocommerce_shipping_sync_store_button').removeClass().addClass('button-primary woocommerce-save-button').val('Sync Store');
            jQuery('#woocommerce_pluginhive_woocommerce_shipping_sync_store_button').show();
            hideSaveButton();
        } else {

            jQuery('#woocommerce_pluginhive_woocommerce_shipping_setup_button').hide();
        }

        var siteURL   = sp_admin_script.site_URL;
        if (storeData && storeData.synced_store && storeData.synced_store !== siteURL) {
            if (!jQuery("#mc_shipping_labels_store_sync_message").length) {
                var message = "<tr><td colspan=2 id='mc_shipping_labels_store_sync_message'><span style='color: red;'> We see that you have changed your store after setting up the app. Please contact <a href='https://www.pluginhive.com'>PluginHive.com</a> to continue the services.<br/><br/> Last Synced store was <b>" + storeData.synced_store + "</b></span></td></tr>";
                jQuery('#woocommerce_pluginhive_woocommerce_shipping_fallback_rate').closest('tr').after(message);
                hideSaveButton();
            }
        }

        if (storeData && storeData.asp_account_id && storeData.integration_id && storeData.secret_key && storeData.store_uuid) {

            var spNavLink = sp_admin_script.spNavLink;
            console.log('spNavLink: ', spNavLink);
            if (!jQuery("#mc_shipping_labels_account_complete_message").length) {
                // Hide consumer credentials and display message
                // var message = "<div style='padding-bottom:10px' id='mc_shipping_labels_account_complete_message'><span style='color: green;'>Congratulations! You have successfully integrated your store with WooCommerce Shipping Services. <a class='button-primary woocommerce-save-button' href='" + spNavLink + "'>Let's start Fulfilling</a></span></div>";
                // jQuery('#woocommerce_pluginhive_woocommerce_shipping_store_settings').closest('h3').after(message);
      var message = "<div style='padding-bottom:10px' id='mc_shipping_labels_account_complete_message'><span style='color: green;'>Congratulations! You have successfully integrated your store with WooCommerce Shipping Services. <br/><br/><a class='button-primary woocommerce-save-button' href='" + spNavLink + "'>Let's start Fulfilling</a></span> &nbsp; <input type='button' id='woocommerce_pluginhive_woocommerce_shipping_resync_button' class='button button-primary' value='Resync'></div>";
      jQuery('#woocommerce_pluginhive_woocommerce_shipping_store_settings').closest('h3').after(message);
      jQuery('#woocommerce_pluginhive_woocommerce_shipping_clear_button').attr('id', 'woocommerce_pluginhive_woocommerce_shipping_clear_plugin_data_button');
      jQuery('#woocommerce_pluginhive_woocommerce_shipping_clear_plugin_data_button').removeClass().addClass('button-primary woocommerce-save-button').val('Clear Plugin Data');
      var clearMessage = "<span style='color: red;'>This action will clear the plugin data and raise a request to support@pluginhive.com to clear wss user account data. <b>This action is not reversible.</b></span><br/><br/>";
      jQuery('#woocommerce_pluginhive_woocommerce_shipping_clear_plugin_data_button').before(clearMessage);
                //jQuery('#woocommerce_pluginhive_woocommerce_shipping_consumer_key').closest('tr').hide();
                //jQuery('#woocommerce_pluginhive_woocommerce_shipping_consumer_secret').closest('tr').hide();
            }
  }
  show_or_hide_clear_plugin_data_button();
  jQuery('#woocommerce_pluginhive_woocommerce_shipping_show_clear_plugin_data_button').click(function() {
    show_or_hide_clear_plugin_data_button();
  });
        jQuery('#woocommerce_pluginhive_woocommerce_shipping_setup_button').unbind('click');
        jQuery('#woocommerce_pluginhive_woocommerce_shipping_setup_button').on('click', function() {
            var data = {};
            data.enabled = jQuery("#woocommerce_pluginhive_woocommerce_shipping_enabled").val();
            data.enabled_rates = jQuery("#woocommerce_pluginhive_woocommerce_shipping_enabled_rates").val();
            //data.consumer_key = jQuery("#woocommerce_pluginhive_woocommerce_shipping_consumer_key").val();
            //data.consumer_secret = jQuery("#woocommerce_pluginhive_woocommerce_shipping_consumer_secret").val();

            // if (!data.consumer_key) {
            // 	return alert('consumer key needed.');
            // }
            // if (!data.consumer_secret) {
            // 	return alert('consumer secret needed.');
            // }

            var actions = {
                action: 'sp_configure_account',
                data: data,
            };

            jQuery(this).prop("disabled", true);
            jQuery.post(ajaxUrl, actions)
                .done(function(response) {
                    var parsedResponse = JSON.parse(response);
                    if (!parsedResponse.success) {
                        alert(parsedResponse.message);
                    } else if (parsedResponse.success) {
                        window.location.reload();
                    }
                    jQuery('#woocommerce_pluginhive_woocommerce_shipping_setup_button').prop("disabled", false);
                })
                .fail(function(result) {
                    alert('No response from Server. Something Went wrong');
                    jQuery('#woocommerce_pluginhive_woocommerce_shipping_setup_button').prop("disabled", false);
                });
  });
  
  jQuery('#woocommerce_pluginhive_woocommerce_shipping_clear_plugin_data_button').unbind('click');
        jQuery('#woocommerce_pluginhive_woocommerce_shipping_clear_plugin_data_button').on('click', function() {
    var data = {};
            var confirmValue = prompt("This will clear plugin data, Please enter YES to continue?");
            if(confirmValue == 'YES'){

            var actions = {
                action: 'clear_plugin_data',
                data: data,
            };

            jQuery(this).prop("disabled", true);
            jQuery.post(ajaxUrl, actions)
                .done(function(response) {
        var parsedResponse = JSON.parse(response);
                    if (parsedResponse.success) {
          alert('Plugin Data Deleted Successfully');
          location.reload();
                    } else  {
                        // window.location.reload();
                        alert('Error While Deleting Plugin Data');
                    }
                    jQuery('#woocommerce_pluginhive_woocommerce_shipping_clear_plugin_data_button').prop("disabled", false);
                })
                .fail(function(result) {
                    alert('No response from Server. Something Went wrong');
                    jQuery('#woocommerce_pluginhive_woocommerce_shipping_setup_button').prop("disabled", false);
                });
    }
  });
    
        jQuery('#woocommerce_pluginhive_woocommerce_shipping_resync_button').unbind('click');
        jQuery('#woocommerce_pluginhive_woocommerce_shipping_resync_button').on('click', function() {
            var data = {};
            // data.enabled = jQuery("#woocommerce_pluginhive_woocommerce_shipping_enabled").val();
            // data.enabled_rates = jQuery("#woocommerce_pluginhive_woocommerce_shipping_enabled_rates").val();
            //data.consumer_key = jQuery("#woocommerce_pluginhive_woocommerce_shipping_consumer_key").val();
            //data.consumer_secret = jQuery("#woocommerce_pluginhive_woocommerce_shipping_consumer_secret").val();

            // if (!data.consumer_key) {
            // 	return alert('consumer key needed.');
            // }
            // if (!data.consumer_secret) {
            // 	return alert('consumer secret needed.');
            // }
            // console.log('data: ', data);
            var confirmValue = confirm("This will resync the Store URL and the Email ID. would you like to continue?");
            if(confirmValue == true){

            var actions = {
                action: 'sp_resync_account',
                data: data,
            };

            jQuery(this).prop("disabled", true);
            jQuery.post(ajaxUrl, actions)
                .done(function(response) {
                    var parsedResponse = JSON.parse(response);
                    if (!parsedResponse.success) {
                        alert(parsedResponse.message);
                    } else if (parsedResponse.success) {
                        // window.location.reload();
                        alert(parsedResponse.message);
                    }
                    jQuery('#woocommerce_pluginhive_woocommerce_shipping_resync_button').prop("disabled", false);
                })
                .fail(function(result) {
                    alert('No response from Server. Something Went wrong');
                    jQuery('#woocommerce_pluginhive_woocommerce_shipping_setup_button').prop("disabled", false);
                });
            }
        });

        jQuery('#woocommerce_pluginhive_woocommerce_shipping_sync_store_button').unbind('click');
        jQuery('#woocommerce_pluginhive_woocommerce_shipping_sync_store_button').on('click', function() {
            var data = {};
            //data.consumer_key = jQuery("#woocommerce_pluginhive_woocommerce_shipping_consumer_key").val();
            //data.consumer_secret = jQuery("#woocommerce_pluginhive_woocommerce_shipping_consumer_secret").val();

            // if (!data.consumer_key) {
            // 	return alert('consumer key needed.');
            // }
            // if (!data.consumer_secret) {
            // 	return alert('consumer secret needed.');
            // }

            var actions = {
                action: 'sp_sync_store',
                data: data,
            };

            jQuery(this).prop("disabled", true);
            jQuery.post(ajaxUrl, actions)
                .done(function(response) {
                    var parsedResponse = JSON.parse(response);
                    alert(parsedResponse.message);
                    jQuery('#woocommerce_pluginhive_woocommerce_shipping_sync_store_button').prop("disabled", false);
                })
                .fail(function(result) {
                    alert('No response from Server. Something Went wrong');
                    jQuery('#woocommerce_pluginhive_woocommerce_shipping_sync_store_button').prop("disabled", false);
                });
        });

});

function show_or_hide_clear_plugin_data_button() {
  const showClearButton =jQuery('#woocommerce_pluginhive_woocommerce_shipping_show_clear_plugin_data_button').is(":checked");
  if (showClearButton) {
    //message += "&nbsp; <input type='button' id='woocommerce_pluginhive_woocommerce_shipping_clear_plugin_data_button' class='button button-primary' value='Clear Plugin Data'>"
    jQuery('#woocommerce_pluginhive_woocommerce_shipping_clear_plugin_data_button').closest('tr').show();
    jQuery('#woocommerce_pluginhive_woocommerce_shipping_clear_plugin_data_button').show();
  } else {
    jQuery('#woocommerce_pluginhive_woocommerce_shipping_clear_plugin_data_button').closest('tr').hide();
    jQuery('#woocommerce_pluginhive_woocommerce_shipping_clear_plugin_data_button').hide();
  }
}

});