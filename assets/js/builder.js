(function(window, document, $, undefined){
	'use strict';

	var app = { $ : {} };

	app.cache = function() {
		app.$.box = $( document.getElementById( '_wds_builder_template_repeat' ) );
		app.$.dropdowns = app.$.box.find( '.wds-simple-page-builder-template-select' );
		app.$.postForm = $( document.getElementById( 'post' ) );
	};

	app.init = function() {
		app.cache();

		app.$.dropdowns
			.on( 'change', app.maybeUnhide )
			.each( app.maybeUnhide );

		app.$.postForm
			.on( 'submit', app.removeHidden );
	};

	app.removeHidden = function( evt ) {
		$( '.hidden-parts-fields.hidden' ).remove();
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
