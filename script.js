(function( $ ) {

	$( document ).ready( function() {		
		
                $('.notice-dismiss').click(function(){
                    $('.container-error').hide();
                    $('.azure-updated').hide();
                })
		$('.container-create').click(function(){		
			$('.container-manual').hide();
			$('.container-error').hide();
			$('.container-action-create').css("display","block");
		});
		
		$('.container-change').click(function(){
			$('.container-save').show();
			$('.azure-assets-settings').hide();
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
		
		$('.container-refresh').click(function(){
			$('.container-error').hide();
		});
		
		$( 'body' ).on( 'click', '.asset-container-action-save', function( e ) {
			e.preventDefault();
			saveContainer();
		} );
		
		$( 'body' ).on( 'click', '.asset-container-create', function( e ) {
			e.preventDefault();
			createContainer();
		} );
		
		$( 'body' ).on( 'click', '.container-refresh', function( e ) {
			e.preventDefault();
			loadContainerList();
		} );
				

	} );
	
	// save container to the database
	 function saveContainer() {
		var $containerForm = $( '.manual-save-container-form' );
		var $containerInput = $containerForm.find( '.assets-container-name' );
		var $containerButton = $containerForm.find( 'button[type=submit]' );
		var containerName = $containerInput.val();
		var originalContainerText = $containerButton.first().text();

		$( '.container-error' ).hide();
		$containerButton.text( $containerButton.attr( 'data-working' ) );
		$containerButton.prop( 'disabled', true );

		var data = {
			action: 'manual-save-asset-container',
			container_name: containerName,
			asset: true,
		};

		var that = this;

		$.ajax( {
			url: ajaxurl,
			type: 'POST',
			dataType: 'JSON',
			data: data,
			error: function( jqXHR, textStatus, errorThrown ) {
				$containerButton.text( originalContainerText );
				displayError( azure.strings.save_container_error, data[ 'error' ], 'container-save' );
			},
			success: function( data, textStatus, jqXHR ) {		
				$containerButton.text( originalContainerText );
				$containerButton.prop( 'disabled', false );	
				if ( 'undefined' !== typeof data[ 'success' ] ) {
					// moves to the main settings
					$('.container-save').hide();
                                        $('.azure-assets-settings').show();
                                        $('.active-asset-container').html(data['container']);					
				} else {
					displayError( azure.strings.save_container_error, data[ 'error' ], 'container-save' );
				}
			}
		} );
	}
	
	// create new container
	function createContainer() {
		var $containerForm = $( '.asset-create-container-form' );
		var $containerInput = $containerForm.find( '.assets-container-name' );
		var $containerButton = $containerForm.find( 'button[type=submit]' );
		var containerName = $containerInput.val();
		var originalContainerText = $containerButton.first().text();

		$( '.container-error' ).hide();
		$containerButton.text( 'creating...' );
		$containerButton.prop( 'disabled', true );

		var data = {
			action: 'asset-container-create',
			container_name: containerName,
                        asset: true,
		};

		var that = this;

		$.ajax( {
			url: ajaxurl,
			type: 'POST',
			dataType: 'JSON',
			data: data,
			error: function( jqXHR, textStatus, errorThrown ) {
				$containerButton.text( originalContainerText );
				displayError( azure.strings.create_container_error, data[ 'error' ], 'container-save' );
			},
			success: function( data, textStatus, jqXHR ) {		
				$containerButton.text( originalContainerText );
				$containerButton.prop( 'disabled', false );	
				if ( 'undefined' !== typeof data[ 'success' ] ) {
					// moves to the main settings
					$('.container-save').hide();
                                        $('.azure-assets-settings').show();
                                        $('.active-asset-container').html(data['container']);
				} else {
					displayError( azure.strings.create_container_error, data[ 'error' ], 'container-save' );
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
				displayError(azure.strings.get_container_error,data['error'],'container-save');
			},
			success: function(data, textStatus,jqXHR){
				$container_list.html( '' );
				if ( 'undefined' !== typeof data[ 'success' ] ) {					
					$( data[ 'containers' ] ).each( function( idx, container ) {
						$container_list.append( '<li><a id = "'+ container +'" href="#" data-bucket="' + container + '"><span class="container"><span class="dashicons dashicons-portfolio"></span> ' + container + '</span><span class="spinner"></span></span></a></li>' );	
					} );
                                    clickToSelect();
				} else {
					displayError( azure.strings.get_container_error, data[ 'error' ], 'container-save' );
				}
			}
			
		});
	}

	function clickToSelect(){
		$( '.container-list li a' ).click(function(){
			var containerName = this.id;
			$('#' + containerName).find('span.spinner').css("visibility","visible");
			data = {
				action: 'manual-save-asset-container',
				container_name: containerName,
                                asset: true,
			}
                       
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				dataType: 'JSON',
				data: data,
				error: function( jqXHR, textStatus, errorThrown ) {				
					displayError( azure.strings.save_container_error, data[ 'error' ], 'container-save' );
				},
				success: function( data, textStatus, jqXHR ) {							
					if ( 'undefined' !== typeof data[ 'success' ] ) {
						$('#' + containerName).find('span.spinner').css("visibility","hidden");
						// moves to the main settings
						$('.container-save').hide();
						$('.azure-assets-settings').show();
						$('.active-asset-container').html(data['container']);					
					} else {
						displayError( azure.strings.save_container_error, data[ 'error' ], 'container-save' );
					}
				}
			});
		});
	}
	
	function displayError( title, error, context ) {
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
				displayError(azure.strings.get_container_error,data['error'],'container-save');
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
