(function( $ ) {
    'use strict';
	var str_campus = '';
	var str_fecha = '';
	var bol_descuento = false;
	var int_precio = 0;
	var int_porcentaje_descuento = 10;
	var id_field_referencia = '#input_3_98';

	// Change campus select event
	$('.sel_campus select').change(function(){
		if ( ! $(this).is(':hidden') ){
			str_campus = $(this).val();
			str_fecha = $('.sel_fechas select').not(':hidden').val();
			update_info();
		}
	});

	// Change fechas select event
	$('.sel_fechas select').change(function(){
		if ( ! $(this).is(':hidden') ){
			str_fecha = $(this).val();
			update_info();
		}
	});

	// Change referencia para descuento
	$(id_field_referencia).change(function(){
		update_info();
	});

	// Load event
	$( window ).load(function() {
		str_campus = $('.sel_campus select').not(':hidden').val();
		str_fecha = $('.sel_fechas select').not(':hidden').val();
		update_info();
	});

	// Build str to show
	function update_info(){
		bol_descuento  = ! $(id_field_referencia).is(':hidden') && $(id_field_referencia).val() !== '';
		int_precio = 0;

		if ( str_campus ){
			int_precio = parseInt( str_campus.substring(str_campus.indexOf(':') + 1, str_campus.length) );
			$('#resumen-content').html('<h4>Campus: ' + str_campus + '<br> Fecha : ' + str_fecha + '</h4>');
			if ( int_precio > 0 && bol_descuento ){
				$('#resumen-content').append('<h4> Descuento ' + int_porcentaje_descuento + '% : ' + parseInt( int_precio - int_precio*int_porcentaje_descuento/100 ) + ' € </h4>');
			}
		}
	}

})( jQuery );
