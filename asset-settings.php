<tr class="border-top">
	<td colspan="2">
		<h2><?php _e('<b>Asset Settings</b>');?></h2>
	<td>
</tr>
<tr>
	<td>
		<input type="checkbox" class="asset-setting" name="copy-assets"  <?php echo $this->get_copy_assets() ? 'checked':'';?>>
	</td>
	<td>
		<h3> <?php _e('Enable Asset Addon'); ?> </h3>
		<p> <?php _e('Copy assets to the Azure storage and serve using CDN.'); ?> </p>
		<span class='asset-addon-error'></span>
	</td>
</tr>
<tr>
	<td>
		<input type="checkbox" class="asset-setting" name="automatic-scan"  <?php echo $this->automatic_scan_assets() ? 'checked':'';?>>
	</td>
	<td>
		<h3> <?php _e('Automatic Scan'); ?> </h3>
		<p> <?php _e('When a theme is changed, its assets will be copied to the Azure storage and rewrite URLs for enqueued CSS & JS scripts.'); ?> </p>
		<div class="extension">
		<h3> <?php _e('File Extensions'); ?> </h3>
		<p><?php _e('Comma separated lists of file extensions to copy and serve from Azure storage.')?></p>
		<input type='text' name='asset-extenstions' value="<?php echo $this->get_asset_extensions(); ?>"/>
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