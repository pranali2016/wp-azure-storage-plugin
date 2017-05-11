<div class="wrap-container">
<?php $container = $this->get_setting( 'container' ); ?>
	<div class="container-save" style="display : <?php echo isset($container) ? 'none': 'block' ?>">
		<div class="error inline container-error " style="display: none;">
			<p>
				<span class="title"></span>
				<span class="message"></span>
			</p>
		</div>
		<div class="container-manual">
		<h3><?php _e('What container would you like to use ?','azure-storage-and-cdn'); ?></h3>
		<form method="post" class="manual-save-container-form">
			<input type="text"  name="container_name" class="azure-container-name" placeholder="<?php _e( 'Existing container name', 'azure-storage-and-cdn' ); ?>" value="<?php echo $this->get_container_name();?>">
			<hr>
			<p class="container-actions actions manual">
				<button id="container-manual-save" type="submit" class="container-action-save button button-primary" data-working="<?php _e( 'Saving...', 'azure-storage-and-cdn' ); ?>"><?php _e( 'Save Container', 'azure-storage-and-cdn' ); ?></button>
				<span><a href="#" id="bucket-action-browse" class="container-browse"><?php _e( 'Browse existing containers', 'azure-storage-and-cdn' ); ?></a></span>&nbsp;&nbsp;
				<span><a href="#" id="container-action-create" class="container-create"><?php _e( 'Create new container', 'azure-storage-and-cdn' ); ?></a></span>
			</p>			
		</form>
		</div>
		
		<div class="container-select" style="display:none">
			<h3><?php _e( 'Select container', 'azure-storage-and-cdn' ); ?></h3>
			<ul class="container-list" data-working="<?php _e( 'Loading...', 'azure-storage-and-cdn' ); ?>"></ul>
			<hr>
			<p class="container-actions ">
				<span> <a class="container-action-cancel"> Cancel </a> </span>
				<span class="right"><a href="#" class="container-refresh"><?php _e( 'Refresh', 'azure-storage-and-cdn' ); ?></a></span>
			</p>		
		</div>
		
		<div class="container-action-create" style="display:none" >
		<h3> <?php _e('Create new Container','azure-storage-and-cdn'); ?> </h3>	
		<p><?php _e('Container Naming Rules:','azure-storage-and-cdn'); ?></p>
		<ol>
			<li>Container names must start with a letter or number, and can contain only letters, numbers, and the dash (-) character.</li>
			<li>Every dash (-) character must be immediately preceded and followed by a letter or number; consecutive dashes are not permitted in container names.</li>
			<li>All letters in a container name must be lowercase.</li>
			<li>Container names must be from 3 through 63 characters long</li>
		</ol>
		<form method="post" class="azure-create-container-form">
			<input type="text" class="azure-container-name" name="container_name" placeholder="<?php _e( 'Container name', 'azure-storage-and-cdn' ); ?>">
			<hr>
			<button id="bucket-manual-save" type="submit" class="azure-container-create button button-primary" ><?php _e( 'Create New Container', 'azure-storage-and-cdn' ); ?></button>
			<span> <a class="container-action-cancel"> Cancel </a> </span>
		</form>
		</div>
	</div>

	<div class="azure-main-settings" style="display : <?php echo isset($container) ? 'block': 'none' ?>">
		<form method="post">
		<?php if ( ( $_POST['action'] != null ) ) {// input var okay ?>
				<div class="azure-updated updated">
					<p><strong>Settings saved.</strong> 
					<span class="right close">x </span></p>
				</div>
			<?php } ?>
			<input type="hidden" name="action" value="save" />
			<table class="form-table">				
				<tr class="border-bottom ">
					<td><h3><?php _e( 'CONTAINER', 'azure-storage-and-cdn' ); ?></h3></td>
					<td><span class="azure-active-container"><b><?php echo $container; ?></b></span>
					<span> <a class="container-change"> Change </a> </span>
					<input id="container" type="hidden" class="no-compare" name="container" value="<?php echo esc_attr( $container ); ?>">
					</td>
				<tr class="azure-setting-title">
					<td colspan="2"><h3><?php _e( 'Enable/Disable the Plugin', 'azure-storage-and-cdn' ); ?></h3></td>
				</tr>
				<tr class="">
					<td>
						<label class="switch">
						  <input type="checkbox" name="copy-to-azure"  <?php echo $this->get_copy_to_azure_setting() ? 'checked':'';?>>
						  <div class="slider"></div>
						</label>
					</td>
					<td>						
						<h3><?php _e( 'Copy Files to Azure Storage', 'azure-storage-and-cdn' ) ?></h3>
						<p>
							<?php _e( 'When a file is uploaded to the Media Library, copy it to Azure Storage. Existing files are <b>not</b> copied to Azure Storage.', 'azure-storage-and-cdn' ); ?>							
						</p>

					</td>
				</tr>
				<tr class="border-bottom">
					<td>
						<label class="switch">
						  <input type="checkbox" name="serve-from-azure"  <?php echo $this->get_serve_from_azure_setting() ? 'checked':'';?>>
						  <div class="slider"></div>
						</label>
					</td>
					<td>						
						<h3><?php _e( 'Rewrite File URLs', 'azure-storage-and-cdn' ) ?></h3>
						<p>
							<?php _e( 'For media library that have been copied to <b>Azure Storage</b> rewrite URL so that they can be served from <b>CDN</b> instead of your server', 'azure-storage-and-cdn' ); ?>							
						</p>

					</td>
				</tr>
			</table>
			<p>
				<button type="submit" class="button button-primary" ><?php _e( 'Save Changes', 'azure-storage-and-cdn' ); ?></button>
			</p>
		</form>
	</div>

</div>