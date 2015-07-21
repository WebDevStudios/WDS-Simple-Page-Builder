jQuery(document).ready(function($) {

	var pageBuilderClass = builder_l10n.builder_class;
	var pageBuilderParts = builder_l10n.parts;
	var partsCount = Number( builder_l10n.parts_count );

	$( 'div.'+pageBuilderClass ).each( function( i ) {
		$(this).addClass(pageBuilderParts[i % partsCount]);
	});
});