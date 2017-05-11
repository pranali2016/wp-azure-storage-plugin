(function( $ ) {

	$( document ).ready( function() {
	
		$( '.button.remove-keys' ).click( function() {
			$( 'input[name=access_end_prorocol],input[name=access_account_name],input[name=access_account_key]' ).val( '' );
		} );
		$('.close').click(function(){
			$('.azure-updated').fadeOut();
		});
		
		$('.container-create').click(function(){		
			$('.container-manual').hide();
			$('.container-error').hide();
			$('.container-action-create').css("display","block");
		});
		
		$('.container-change').click(function(){
			$('.container-save').show();
			$('.azure-main-settings').hide();
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
					// moves to the main settings
					$('.container-save').hide();
					$('.azure-main-settings').show();
					$('.azure-active-container').html(data['container']);					
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
					// moves to the main settings
					$('.container-save').hide();
					$('.azure-main-settings').show();
					$('.azure-active-container').html(data['container']);
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
					$( data[ 'containers' ] ).each( function( idx, container ) {
						$container_list.append( '<li><a id = "'+ container +'" href="#" data-bucket="' + container + '"><span class="container"><span class="dashicons dashicons-portfolio"></span> ' + container + '</span><span class="spinner"></span></span></a></li>' );
						clickToSelect();
					} );

				} else {
					showError( azure.strings.get_container_error, data[ 'error' ], 'container-save' );
				}
			}
			
		});
	}

	function clickToSelect(){
		$( '.container-list li a' ).click(function(){
			var containerName = this.id;
			$('#' + containerName).find('span.spinner').css("visibility","visible");
			data = {
				action: 'manual-save-container',
				container_name: containerName,
			}
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				dataType: 'JSON',
				data: data,
				error: function( jqXHR, textStatus, errorThrown ) {				
					showError( azure.strings.save_container_error, data[ 'error' ], 'container-save' );
				},
				success: function( data, textStatus, jqXHR ) {							
					if ( 'undefined' !== typeof data[ 'success' ] ) {
						$('#' + containerName).find('span.spinner').css("visibility","hidden");
						// moves to the main settings
						$('.container-save').hide();
						$('.azure-main-settings').show();
						$('.azure-active-container').html(data['container']);					
					} else {
						showError( azure.strings.save_container_error, data[ 'error' ], 'container-save' );
					}
				}
			});
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
	
	// if container existed then moves to the main setting page
	function containerExist(){
		var data = {
			action:'container-exist',			
		};
		
		$.ajax({
			url: ajaxurl,
			type: 'GET',
			dataType: 'JSON',
			data: data,
			error: function(jqXHR, textStatus, errorThrown){				
				showError(azure.strings.get_container_error,data['error'],'container-save');
			},
			success: function(data, textStatus,jqXHR){				
				if ( 'undefined' !== typeof data[ 'success' ] ) {					
					$('.container-save').hide();
					$('.azure-main-settings').show();
				} else {
					$('.container-save').show();
					$('.azure-main-settings').hide();
				}
			}
		});
	}
		
})( jQuery );
