jQuery('.cmb-td option[selected="selected"]').each(function(index){
	if ( 'none' == jQuery(this).val() ) {
		jQuery(this).parent().parent().parent().hide()
	}
});