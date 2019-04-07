<?php
include_once "helpers/validate-fields.php";
include_once "helpers/general-functions.php";
include_once "includes/class-select-stock.php";

add_action( "wp_enqueue_scripts", "enqueue_styles_child_theme" );
function enqueue_styles_child_theme() {

	$parent_style = 'storefront-style';
	$child_style  = 'storefront-child-style';

	wp_enqueue_style( $parent_style,
				get_template_directory_uri() . '/style.css' );

	wp_enqueue_style( $child_style,
				get_stylesheet_directory_uri() . '/style.css',
				array( $parent_style ),
				wp_get_theme()->get('Version')
				);
}

// Grav¡tyForms
// =============

// Variables globales importantes
// ==============================
define('FORM_ID', 3); // Id del formulario
define('FORM_SELECTS_FIELDS',[78,79,80,81,82]); // Campos de select a evaluar stock

$form_id = FORM_ID;

// Resumen y Validación de formulario DNI, IBAN
// ==============================================

// Insertar archivo js personalizado
add_action( "gform_enqueue_scripts", "gf_add_custom_js", 10, 2 );
function gf_add_custom_js(){
    wp_register_script('miscript', get_stylesheet_directory_uri(). '/js/script.js', array('jquery'), '1', true );
    wp_enqueue_script('miscript');
}

// Validar el campo de DNI Field 53
add_filter( "gform_field_validation_${form_id}_53", "gf_validate_field_dni", 10, 4 );
function gf_validate_field_dni($result, $value, $form, $field){
	if ( ! gf_validate_spain_dni($value) ){
		$result['is_valid'] = false;
    	$result['message'] = 'Ingresa un DNI válido';
	}
    return $result;
}

// Validar el campo de IBAN Field 73
add_filter( "gform_field_validation_${form_id}_73", "gf_validate_field_iban", 10, 4 );
function gf_validate_field_iban($result, $value, $form, $field){
	if ( ! gf_validate_iban($value) ){
		$result['is_valid'] = false;
    	$result['message'] = 'Ingresa un IBAN válido';
	}
    return $result;
}


// Grav¡tyForms Control del Stock
// ==============================

// Backend - Se ejecuta cuando se grabará el formulario en la administración
add_action( "gform_after_save_form", "gf_after_save_admin_form", 10, 2);
function gf_after_save_admin_form($form){
	foreach( FORM_SELECTS_FIELDS as $id_select){
		$select = new Select_Stock($id_select);

		if ( $form->id == $form_id ){ //valida form id
			foreach ($form['fields'] as &$field){
				if ( $field->id == $id_select ){
					$select->save_option($field->choices);
					break;
				}
			}
		}
	}
}

// Backend - Se ejecuta al cargar el formulario en la administración
add_action( "gform_admin_pre_render_${form_id}", "gf_pre_render_form_admin" );
function gf_pre_render_form_admin($form){
	foreach( FORM_SELECTS_FIELDS as $id_select){
		$select = new Select_Stock($id_select);

		foreach ($form['fields'] as &$field){
			if ( $field->id == $id_select ){
				$field->choices = $select->get_modify_select($field->choices); //actualiza el array
				break;
			}
		}
	}
	return $form;
}

// Frontend - Se ejecuta al cargar el formulario en el front-end
add_filter( "gform_pre_render_${form_id}", 'gf_pre_render_form' );
function gf_pre_render_form($form){
	foreach( FORM_SELECTS_FIELDS as $id_select){
		$select = new Select_Stock($id_select);

		foreach ($form['fields'] as &$field){
			if ( $field->id == $id_select ){
				$field->choices = $select->remove_item_without_stock($field->choices); //elimino items sin stock
				break;
			}
		}
	}
	return $form;
}

// Frontend - Se ejecuta al enviar el formulario
add_action( "gform_after_submission_${form_id}", "gf_after_submission_form", 10, 2 );
function gf_after_submission_form($entry, $form){
	foreach( FORM_SELECTS_FIELDS as $id_select){
		$select = new Select_Stock($id_select);
		$item_key = $entry[$id_select];
		dump_error_log($entry[$id_select]);
		$select->subtract_item_stock($item_key);
	}
}

// Frontend - Función para validar que se selecciona una opción de los selects
add_action("gform_field_validation", "gf_validate_selects", 10, 4);
function gf_validate_selects($result, $value, $form, $field){
	if ( in_array($field->id, FORM_SELECTS_FIELDS, true) ){
		if ( $value == 'Seleccionar'){
			$result['is_valid'] = false;
			$result['message'] = 'Selecciona una opción';
		}
	}
	return $result;
}



//Obtiene los valores grabados en opciones globales de WordPress


// Recuperar las opciones de la BD
// function gf_get_options_stock($key){

// }

// // Grabar el stock en la BD
// function gf_save_options_stock(){
// 	$id_select = 105;
// 	update_option(GF_STOCK_CONTROL.$id_select, );
// }

// function gf_create_array(){

// }

// Función que se ejecuta al cargar el formulario en la administración
// add_action( "gform_admin_pre_render_${form_id}", "pre_render_function" );
// function pre_render_function($form){
// 	$select_item = new Select_Stock();

// 	foreach ($form['fields'] as &$field){
// 		if ( $field->id == 105 ){
// 			$select_item->save_option($field->id, $field->choices);
// 		}
// 	}

// 	return $form;
// }

// function pre_render_function($form){
// 	// dump_error_log( $form );

// 		foreach ($form['fields'] as &$field){
// 		if ( $field->id == 105 ){
// 			// dump_error_log( $field);

// 			$field->choices = Array( Array(
// 				"text" => "xxxxx ssss",
// 				"value" => "hola",
// 				"isSelected" => true,
// 				"price" => ''
// 				),
// 				Array(
// 					"text" => "hola hola pesh",
// 					"value" => "hola",
// 					"isSelected" => false,
// 					"price" => ''
// 					)
// 			);

// 		}
// 	}

// 	return $form;
// }

// add_action( 'gform_after_save_form', 'dmcs_update_quantities_select', 10, 2 );
// function dmcs_update_quantities_select( $form, $is_new ) {

// 	foreach ($form['fields'] as &$field){
// 		if ( $field->id == 105 ){
// 			dump_error_log( $field);

// 			$field->choices = Array( Array(
// 				"text" => "hola hola pesh xxxx",
// 				"value" => "hola",
// 				"isSelected" => true,
// 				"price" => ''
// 				),
// 				Array(
// 					"text" => "hola hola pesh",
// 					"value" => "hola",
// 					"isSelected" => true,
// 					"price" => ''
// 					)
// 			);

// 		}
// 	}
// }



// add_filter( 'gform_pre_render_3', 'dcms_hide_quantities_select' );

// function dcms_hide_quantities_select($form){
// 	$newFormField = array();

// 	// dump_error_log( $form );

// 	foreach ($form['fields'] as &$field){
// 		if ( $field->id == 78 ){

// 			dump_error_log($field);

// 			$field->choices = Array( Array(
// 				"text" => "hola z",
// 				"value" => "hola",
// 				"isSelected" => false,
// 				"price" => ''
// 				)
// 			);
// 		}
// 	}
// 	return $form;
// }














// add_filter( 'gform_export_fields', 'remove_fields', 10, 1 );
// function remove_fields( $form ) {

//     foreach ( $form['fields'] as $key => $field ) {
//         // $field_id = is_object( $field ) ? $field: $field['id'];
//         error_log( print_r( $field, true)  );
//     }

//     return $form;
// }


// add_filter( 'gform_leads_before_export', 'use_user_display_name_for_export', 10, 3 );
// function use_user_display_name_for_export( $entries, $form, $paging ) {

//     foreach( $entries as &$entry ) {
//  		$entry['79'] = $entry['79'] . ' - ' . $entry['81'];
//   		error_log( print_r( $entry, true)  );
//     }

//     return $entries;
// }












