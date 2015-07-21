jQuery(document).ready(function($) {
	var pageBuilderClass = builder_l10n.builder_class;
	var pageBuilderParts = builder_l10n.parts;
	var partsCount = length( pageBuilderParts );
	$( pageBuilderClass ).each( function( index ) {
		if ( partsCount > 1 ) {
			console.log(pageBuilderParts);
		}
	});
});