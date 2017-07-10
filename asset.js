(function( $ ) {
	$( document ).ready( function() {
		$( '.copy-now-asset' ).click (function() {
			copyAssetsManually();
		});
		
		$('.remove-assets-manually').click(function(){
			removeAssetsManually();
		});
				
	});		
	
	function copyAssetsManually(){
		data = {
			action:'copy-assets-manually',
		};
		$.ajax({			
			url: ajaxurl,
			type: 'POST',
			dataType: 'JSON',
			data: data,
			error: function( jqXHR, textStatus, errorThrown ){},
			success: function( data, textStatus, jqXHR ){
				if ( 'undefined' !== typeof data[ 'errors' ] ) {
						var msg = data['errors']['exception'][0];
						$('.asset-addon-error').html(msg);
						$('.asset-addon-error').css({color:'red'});
				}
			}
		});
	}
	
	function removeAssetsManually(){
		data = {
			action: 'remove-assets-manually',
		};
		
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			dataType: 'JSON',
			data: data,
			error: function(jqXHR, textStatus, errorThrown){},
			success: function( data, textStatus, jqXHR ){
			
			}
		});
	}
	
})(jQuery);
