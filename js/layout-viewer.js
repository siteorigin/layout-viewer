jQuery( function( $ ){
	setTimeout( function(){
		var $v = $('#layout-viewer-bar');
		var height = $v.outerHeight();

		$('body').animate( { 'margin-top' : height } );
		$v.slideDown();
	}, 1250 );
} );