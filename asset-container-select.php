<div class="container-save" style="<?php echo $this->get_setting( 'asset-container' ) ? 'display:none' : 'display:block' ?>" >
	<div class="error inline container-error " style="display: none;">
	<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>	
            <p>
                <span class="title"></span>
                <span class="message"></span>
            </p>                
	</div>
	<div class="container-manual">
	<h3><?php _e('What container would you like to use ?','wp-offload-azure-assets'); ?></h3>
	<form method="post" class="manual-save-container-form">
		<input type="text"  name="assets_container_name" class="assets-container-name" placeholder="<?php _e( 'Existing container name', 'wp-offload-azure-assets' ); ?>" value="<?php echo $this->get_setting( 'asset-container')?>">
		<hr>
		<p class="container-actions actions manual">
			<button id="asset-container-manual-save" type="submit" class="asset-container-action-save button button-primary" data-working="<?php _e( 'Saving...', 'wp-offload-azure-assets' ); ?>"><?php _e( 'Save Container', 'wp-offload-azure-assets' ); ?></button>
			<span><a href="#" id="bucket-action-browse" class="container-browse"><?php _e( 'Browse existing containers', 'wp-offload-azure-assets' ); ?></a></span>&nbsp;&nbsp;
			<span><a href="#" id="container-action-create" class="container-create"><?php _e( 'Create new container', 'wp-offload-azure-assets' ); ?></a></span>
		</p>			
	</form>
	</div>
	
	<div class="container-select" style="display:none">
		<h3><?php _e( 'Select container', 'azure-storage-and-cdn' ); ?></h3>
		<ul class="container-list" data-working="<?php _e( 'Loading...', 'wp-offload-azure-assets' ); ?>"></ul>
		<hr>
		<p class="container-actions ">
			<span> <a class="container-action-cancel"> Cancel </a> </span>
			<span ><a href="#" class="right container-refresh"><?php _e( 'Refresh', 'wp-offload-azure-assets' ); ?></a></span>
		</p>		
	</div>
	
	<div class="container-action-create" style="display:none" >
	<h3> <?php _e('Create new Container','wp-offload-azure-assets'); ?> </h3>	
	<p><?php _e('Container Naming Rules:','wp-offload-azure-assets'); ?></p>
	<ol>
		<li>Container names must start with a letter or number, and can contain only letters, numbers, and the dash (-) character.</li>
		<li>Every dash (-) character must be immediately preceded and followed by a letter or number; consecutive dashes are not permitted in container names.</li>
		<li>All letters in a container name must be lowercase.</li>
		<li>Container names must be from 3 through 63 characters long</li>
	</ol>
	<form method="post" class="asset-create-container-form">
		<input type="text" class="assets-container-name" name="assets_container_name" placeholder="<?php _e( 'Container name', 'wp-offload-azure-assets' ); ?>">
		<hr>
		<button id="asset-manual-save" type="submit" class="asset-container-create button button-primary" ><?php _e( 'Create New Container', 'wp-offload-azure-assets' ); ?></button>
		<span> <a class="right container-action-cancel"> Cancel </a> </span>
	</form>
	</div>
</div>
</div>