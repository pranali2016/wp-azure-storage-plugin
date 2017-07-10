<div class="wrap-container"style="padding-top: 10px;" >
<?php 
        $container = $this->get_setting( 'asset-container' );
	$this->display_view( 'asset-container-select' );       
?>
<div class="azure-assets-settings" style="display : <?php echo isset($container) ? 'block': 'none' ?>">
	<form method="post">
	<?php if ( ( $_POST['action'] != null ) ) {// input var okay ?>
			<div class="azure-updated updated">
				<p><strong>Settings saved.</strong> 
				<span class="notice-dismiss pos"> </span></p>
			</div>
		<?php } ?>
		<input type="hidden" name="action" value="save" />
		<table class="form-table">
                        <tr class="border-bottom ">
                            <td colspan="2"><h2 style="display: inline;margin-right: 100px;"><?php _e( 'ASSET - CONTAINER', 'wp-offload-azure-assets' ); ?></h2><span class="active-asset-container"><b><?php echo $container;  ?></b></span>
                                <span> <a class="container-change"> Change </a> </span>
                                <input id="container" type="hidden" class="no-compare" name="asset-container" value="<?php echo esc_attr( $container ); ?>">
                                </td>
                        </tr>
			<tr class="border-top">
				<td colspan="2">
					<h2><?php _e('<b>Asset Settings</b>');?></h2>
				<td>
			</tr>
			<tr>
				<td>
					<input type="checkbox" class="asset-setting" name="enable-assets-addon"  <?php echo $this->is_enable_assets_addon() ? 'checked':'';?>>
				</td>
				<td>
					<h3> <?php _e('Enable Asset Addon'); ?> </h3>
					<p> <?php _e('Copy assets to the Azure storage and serve using CDN.'); ?> </p>
                                        <h4> CDN Endpoint</h4><input type="text" name="asset-cdn-endpoint" value="<?php echo $this->get_cdn_endpoint();?>"> &nbsp; (For eg: "abc.azureedge.net")
					<span class='asset-addon-error'></span>
				</td>
			</tr>
			<tr>
				<td>
					<input type="checkbox" class="asset-automatic-scan" name="automatic-scan" <?php echo $this->automatic_scan_assets() ? 'checked':'';?> >
				</td>
				<td>
					<h3> <?php _e('Automatic Scan'); ?> </h3>
					<p> <?php _e('When a theme is changed, its assets will be copied to the Azure storage and rewrite URLs for enqueued CSS & JS scripts.'); ?> </p>
					<div class="extension">
					<h3> <?php _e('File Extensions'); ?> </h3>
					<p><?php _e('Comma separated lists of file extensions to copy and serve from Azure storage.')?></p>
					<input type='text' name='asset-extenstions' value="<?php echo $this->get_asset_extensions() ?>"/>
					</div>
				</td>
			</tr>

			<tr>
				<td> <button type="button"  class="button copy-now-asset" value="Click Now"> Scan Now </button></td>
				<td>
					<h3><?php _e('Copy <b>Assets</b> to Azure storage and serve using CDN');?> </h3>
				</td>
			</tr>
			<tr>
				<td>
					<input type="button" class="button remove-assets-manually" value="Delete" name="delete" />
				</td>
				<td>
					<h3> <?php _e('Remove all asset files from Azure Storage'); ?>
				</td>
			</tr>
		</table>
		<p>
			<button type="submit" class="button button-primary" ><?php _e( 'Save Changes', 'wp-offload-azure-assets' ); ?></button>
		</p>
	</form>
</div>

</div>