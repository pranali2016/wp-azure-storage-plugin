<div class="azure-settings">	
	<h3><?php _e( 'Access Credentials', 'azure-web-services' ); ?></h3>
		<p>
			<?php _e( 'You&#8217;ve already defined your Azure connection string in your wp-config.php. If you&#8217;d prefer to manage them here and store them in the database (not recommended), simply remove the lines from your wp-config.', 'amazon-web-services' ); ?>
		</p>
		
		<p>
			<?php _e( 'We recommend defining your Access Keys in wp-config.php so long as you don&#8217;t commit it to source control (you shouldn&#8217;t be). Simply copy the following snippet and replace the stars with the keys.', 'amazon-web-services' ); ?>
		</p>

		<p>define( 'DBI_AZURE_ACCOUNT_NAME', '********************' );<br/> define( 'DBI_AZURE_ACCOUNT_KEY', '****************************************' );</p>

		<form method="post" id="access_credentials" >

			<?php if ( ( $_POST['access_account_name'] != null ) && !isset($_POST['remove-keys']) ) {// input var okay ?>
				<div class="azure-updated updated">
					<p><strong>Settings saved.</strong>
					<span class="right close">x </span></p>
				</div>
			<?php } ?>

			<input type="hidden" name="action" value="save" />
			<?php wp_nonce_field( 'azure-save-settings' ) ?>

			<table class="form-table">
				<tr valign="top">
					<th width="33%" scope="row"><?php _e( 'Choose Default End Point Protocol:', 'azure-web-services' ); ?></th>
					<td>
						<input type="radio" name="access_end_prorocol" value="http" <?php echo ($this->get_access_end_prorocol() == 'http') ? 'checked' : '';?> size="50" autocomplete="off" /> http &nbsp;&nbsp;
						<input type="radio" name="access_end_prorocol" value="https" <?php echo ($this->get_access_end_prorocol() == 'https') ? 'checked' : '';?> size="50" autocomplete="off" /> https
					</td>
				</tr>
				<tr valign="top">
					<th width="33%" scope="row"><?php _e( 'Account Name:', 'azure-web-services' ); ?></th>
					<td>
						<input type="text"  id= "access_account_name" name="access_account_name" value="<?php echo $this->get_access_account_name();?>" size="50" autocomplete="off" />
					</td>
				</tr>
				<tr valign="top">
					<th width="33%" scope="row"><?php _e( 'Account Key:', 'azure-web-services' ); ?></th>
					<td>
						<input type="text" id="access_account_key" name="access_account_key" value="<?php echo $this->get_access_account_key() ? '-- not shown --' : '';?>" size="50" autocomplete="off" />
					</td>
				</tr>
				<tr valign="top">
					<th colspan="2" scope="row">
						<button type="submit" class="button button-primary"><?php _e( 'Save Changes', 'azure-web-services' ); ?></button>
						<?php if ( $this->get_access_account_name() || $this->get_access_account_key() ) : ?>
							&nbsp;
							<button  name="remove-keys" value="true" class="button remove-keys"><?php _e( 'Remove Keys', 'amazon-web-services' ); ?></button>
						<?php endif; ?>
					</th>
				</tr>
			</table>

		</form>

</div>