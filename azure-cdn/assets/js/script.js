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
		
		$('.container-action-cancel').click(function(){
			$('.container-manual').show();
			$('.container-error').hide();			
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
