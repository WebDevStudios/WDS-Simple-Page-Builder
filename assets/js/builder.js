(function(window, document, $, cmb, undefined){
	'use strict';

	var app = { $ : {} };

	app.cache = function() {
		app.$.box         = $( document.getElementById( '_wds_builder_template_repeat' ) );
		app.$.dropdowns   = app.$.box.find( '.wds-simple-page-builder-template-select' );
		app.$.postForm    = $( document.getElementById( 'post' ) );
		app.$.hiddenParts = app.$.box.find( '.hidden-parts-fields' );
	};

	app.init = function() {
		app.cache();

		app.resetHide();

		app.$.dropdowns
			.on( 'change', app.maybeUnhide );

		app.$.postForm
			.on( 'submit', app.removeHidden );

		cmb.metabox().find('.cmb-repeatable-group')
			.on( 'cmb2_add_row cmb2_remove_row cmb2_shift_rows_complete', app.resetHide );
	};

	app.removeHidden = function() {
		$( '.hidden-parts-fields.hidden' ).remove();
	};

	app.resetHide = function() {
		app.$.hiddenParts.addClass( 'hidden' );
		app.$.dropdowns.each( app.maybeUnhide );
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

})(window, document, jQuery, CMB2);
