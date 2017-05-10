<h2 class="nav-tab-wrapper">
	<?php
	$tabs = $this->get_settings_tabs();
	foreach ( $tabs as $tab => $label ) : ?>
		<a href="#" class="nav-tab <?php echo 'media' == $tab ? 'nav-tab-active' : ''; ?> js-action-link <?php echo $tab; ?>" data-tab="<?php echo $tab; ?>">
			<?php echo esc_html( $label ); ?>
		</a>
	<?php endforeach; ?>
</h2>

<div class="wrap-container">
<div class="container-save">
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
			<span><a href="#" id="bucket-action-browse" class="bucket-action-browse"><?php _e( 'Browse existing containers', 'azure-storage-and-cdn' ); ?></a></span>&nbsp;&nbsp;
			<span><a href="#" id="container-action-create" class="container-create"><?php _e( 'Create new container', 'azure-storage-and-cdn' ); ?></a></span>
		</p>			
	</form>
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
		<button id="bucket-manual-save" type="submit" class="azure-container-create button button-primary" ><?php _e( 'Create New Container', 'amazon-s3-and-cloudfront' ); ?></button>
		<span> <a class="container-action-cancel"> Cancel </a> </span>
	</form>
	</div>
</div>
</div>