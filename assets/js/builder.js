(function(window, document, $, undefined){
	'use strict';

	var app = { $ : {} };

	app.cache = function() {
		app.$.box = $( document.getElementById( '_wds_builder_template_repeat' ) );
		app.$.dropdowns = app.$.box.find( '.cmb2_select' );
	};

	app.init = function() {
		app.cache();

		app.$.dropdowns
			.on( 'change', app.maybeUnhide )
			.each( app.maybeUnhide );
	};

	app.maybeUnhide = function( evt ) {
		var $this = $(this);
		var id    = $this.val();
		var $row  = $this.parents( '.cmb-repeatable-grouping' );

		if ( evt.target ) {
			$row.find( '.hidden-parts-fields' ).addClass( 'hidden' );
		}

		var $hidden = $row.find( '.hidden-parts-' + id );

		if ( $hidden.length ) {
			$hidden.removeClass( 'hidden' );
		}
	};

	$( app.init );

})(window, document, jQuery);
