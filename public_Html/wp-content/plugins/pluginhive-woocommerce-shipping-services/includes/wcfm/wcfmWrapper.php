<?php
global $WCFM, $wp_query;
$url = get_site_url( null, '', null);
?>
<div class="collapse wcfm-collapse" id="wcfm_upgrade_listing">
	<div class="wcfm-page-headig">
		<span class="fa fa-cubes"></span>
		<span class="wcfm-page-heading-text"><?php _e( 'Shipping', 'wcfm-custom-menus' ); ?></span>
		<?php do_action( 'wcfm_page_heading' ); ?>
	</div>
	<div class="wcfm-collapse-content">
		<div id="wcfm_page_load"></div>
		<?php do_action( 'before_wcfm_upgrade' ); ?>
		<div class="wcfm-container wcfm-top-element-container">
			<h2><?php _e('Shipping', 'wcfm-custom-menus' ); ?></h2>
		    <div class="wcfm-clearfix"></div>
	    </div>
	    <div class="wcfm-clearfix"></div><br />
		<div style="width:100%; height:100%">
            <div style="width:100%; height:100%">
				<!-- <div id="wcfm-orders_wrapper" width="100%" height="100%"> -->
				<article style="width:100%; height:900px">
					<iframe src='<?php echo $url;?>/?PluginHive-Wcfm=1' style="width:100%; height:100%"></iframe>
				</article>   
                <div class="wcfm-clearfix"></div> 
            </div>
			<div class="wcfm-clearfix"></div>
        </div>
        <div class="wcfm-clearfix"></div>
	</div>
		<?php
		do_action( 'after_wcfm_upgrade' );
		?>
</div>