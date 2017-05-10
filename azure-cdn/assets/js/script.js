(function( $ ) {

	$( document ).ready( function() {
	
		$( '.button.remove-keys' ).click( function() {
			$( 'input[name=access_end_prorocol],input[name=access_account_name],input[name=access_account_key]' ).val( '' );
		} );
		$('.azure-updated').fadeOut(3000);
		
		$('.container-create').click(function(){		
			$('.container-manual').hide();
			$('.container-error').hide();			
			$('.container-action-create').css("display","block");
		});
		
		$( 'body' ).on( 'click', '.container-browse', function( e ) {
			e.preventDefault();
			$('.container-manual').hide();
			$('.container-error').hide();
			$('.container-select').css("display","block");
			loadContainerList();
		});
		
		$('.container-action-cancel').click(function(){
			$('.container-manual').show();
			$('.container-error').hide();
			$('.container-select').hide();
			$('.container-action-create').css("display","none");
		});
		
		$( 'body' ).on( 'click', '.container-action-save', function( e ) {
			e.preventDefault();
			saveManual();
		} );
		
		$( 'body' ).on( 'click', '.azure-container-create', function( e ) {
			e.preventDefault();
			create();
		} );
		
		$( 'body' ).on( 'click', '.container-refresh', function( e ) {
			e.preventDefault();
			loadContainerList( );
		} );
				

	} );
	
	// save container to the database
	 function saveManual() {
		var $manualContainerForm = $( '.manual-save-container-form' );
		var $manualContainerInput = $manualContainerForm.find( '.azure-container-name' );
		var $manualContainerButton = $manualContainerForm.find( 'button[type=submit]' );
		var containerName = $manualContainerInput.val();
		var originalContainerText = $manualContainerButton.first().text();

		$( '.container-error' ).hide();
		$manualContainerButton.text( $manualContainerButton.attr( 'data-working' ) );
		$manualContainerButton.prop( 'disabled', true );

		var data = {
			action: 'manual-save-container',
			container_name: containerName,
		};

		var that = this;

		$.ajax( {
			url: ajaxurl,
			type: 'POST',
			dataType: 'JSON',
			data: data,
			error: function( jqXHR, textStatus, errorThrown ) {
				$manualContainerButton.text( originalContainerText );
				showError( azure.strings.save_container_error, data[ 'error' ], 'container-save' );
			},
			success: function( data, textStatus, jqXHR ) {		
				$manualContainerButton.text( originalContainerText );
				$manualContainerButton.prop( 'disabled', false );	
				if ( 'undefined' !== typeof data[ 'success' ] ) {
					//success
				} else {
					showError( azure.strings.save_container_error, data[ 'error' ], 'container-save' );
				}
			}
		} );
	}
	
	// create new container
	function create() {
		var $manualContainerForm = $( '.azure-create-container-form' );
		var $manualContainerInput = $manualContainerForm.find( '.azure-container-name' );
		var $manualContainerButton = $manualContainerForm.find( 'button[type=submit]' );
		var containerName = $manualContainerInput.val();
		var originalContainerText = $manualContainerButton.first().text();

		$( '.container-error' ).hide();
		$manualContainerButton.text( 'creating...' );
		$manualContainerButton.prop( 'disabled', true );

		var data = {
			action: 'azure-container-create',
			container_name: containerName,
		};

		var that = this;

		$.ajax( {
			url: ajaxurl,
			type: 'POST',
			dataType: 'JSON',
			data: data,
			error: function( jqXHR, textStatus, errorThrown ) {
				$manualContainerButton.text( originalContainerText );
				showError( azure.strings.create_container_error, data[ 'error' ], 'container-save' );
			},
			success: function( data, textStatus, jqXHR ) {		
				$manualContainerButton.text( originalContainerText );
				$manualContainerButton.prop( 'disabled', false );	
				if ( 'undefined' !== typeof data[ 'success' ] ) {
					//success
				} else {
					showError( azure.strings.create_container_error, data[ 'error' ], 'container-save' );
				}
			}
		} );
	}
	
	//loadContainerList
	function loadContainerList(){
		var $container_list = $('.container-list');
		$container_list.html("<li class='loading'>"+ $container_list.attr('data-working')+"</li>");
		
		var data = {
			action:'get-container-list',
		};
		
		$.ajax({
			url: ajaxurl,
			type: 'GET',
			dataType: 'JSON',
			data: data,
			error: function(jqXHR, textStatus, errorThrown){
				$container_list.html('');
				showError(azure.strings.get_container_error,data['error'],'container-save');
			},
			success: function(data, textStatus,jqXHR){
				$container_list.html( '' );
					if ( 'undefined' !== typeof data[ 'success' ] ) {
						$( data[ 'containers' ] ).each( function( idx, containers ) {
							var containersClass = containers;
							$container_list.append( '<li><a class="' + containersClass + '" href="#" data-bucket="' + containers + '"><span class="container"><span class="dashicons dashicons-portfolio"></span> ' + containers + '</span><span class="spinner"></span></span></a></li>' );
						} );

					} else {
						showError( azure.strings.get_container_error, data[ 'error' ], 'container-save' );
					}
			}
			
		});
	}
	
	function showError( title, error, context ) {
		var $activeView = $( '.wrap-container' ).children( ':visible' );
		var $containerError = $activeView.find( '.container-error' );
		context = ( 'undefined' === typeof context ) ? null : context;

		if ( context && ! $activeView.hasClass( context ) ) {
			return;
		}

		$containerError.find( 'span.title' ).html( title + ' &mdash;' );
		$containerError.find( 'span.message' ).html( error );
		$containerError.show();
	}
		
})( jQuery );
