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

// GravityForms
// =============

// Variables globales importantes
// ==============================
define('FORM_ID', 3); // Id del formulario
define('FORM_SELECTS_FIELDS',[78,79,80,81,82]); // Campos de select a evaluar stock
define('FORM_REMOVE_FIELDS',[79,80,81,82,100,101,102,103]); // Campos de select a evaluar stock
define('PERCENT_DISCOUNT', 10); // El % de descuento a usar

define('FORM_DISCOUNT_FIELD', 91); // El segundo campo "Tipo de campus" se reutilizará
define('FORM_REF_FIELD',99); // Se fusionara todas las referencias en el campo 99

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

		if ( $form['id'] == FORM_ID ){ //valida form id
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
		$select->subtract_item_stock($item_key);
	}
}

// Frontend - Función para validar que se selecciona una opción de los selects
add_action("gform_field_validation", "gf_validate_selects", 10, 4);
function gf_validate_selects($result, $value, $form, $field){
	$field_id = is_object( $field ) ? $field->id : $field['id'];
	if ( in_array($field_id, FORM_SELECTS_FIELDS, true) ){
		if ( $value == 'Seleccionar'){
			$result['is_valid'] = false;
			$result['message'] = 'Selecciona una opción';
		}
	}
	return $result;
}



// Grav¡tyForms Exportar archivos CSV
// ===================================

//Elimino los campos que no deberían mostrarse
add_filter( 'gform_export_fields', 'gf_remove_add_fields', 10, 1 );
function gf_remove_add_fields( $form ) {

	if ( $form['id'] == FORM_ID ) {
		// Campos a eliminar
        $fields_standard = array('payment_amount','payment_date','payment_status','transaction_id','user_agent','ip','post_id');
		$fields_to_remove = array_merge(FORM_REMOVE_FIELDS, $fields_standard);

        foreach ( $form['fields'] as $key => $field ) {
			$field_id = is_object( $field ) ? $field->id : $field['id'];
			// Eliminar campos
            if ( in_array( $field_id, $fields_to_remove ) ) {
                unset ( $form['fields'][ $key ] );
			}
			//Modificar campo
			if ($field_id == FORM_DISCOUNT_FIELD){
				$form['fields'][ $key ]['label'] = "Descuento ".PERCENT_DISCOUNT."%";
			}
			if ($field_id == FORM_REF_FIELD){
				$form['fields'][ $key ]['label'] = "Detalle Ref";
			}

		}

	}

    return $form;
}

//Fusiona las columnas
// 76,91 = Tipo de campus
// 78,79,80,81,82 = fechas
// 98 = referencia
// 99,100,101,102,103 = Campos adicionales detalle referencia
add_filter( 'gform_leads_before_export', 'gf_fusion_columns_for_export', 10, 3 );
function gf_fusion_columns_for_export( $entries, $form, $paging ) {
	if ( $form['id'] == FORM_ID ){
		foreach( $entries as &$entry ) {

			$entry['76'] = $entry['76'].$entry['91'];
			$entry['78'] = $entry['78'].$entry['79'].$entry['80'].$entry['81'].$entry['82'];
			$entry['99'] = $entry['99'].$entry['100'].$entry['101'].$entry['102'].$entry['103'];

			//Calcular % descuento
			$entry[FORM_DISCOUNT_FIELD] = "";
			if ( ! empty($entry['98']) &&  strtolower($entry['98']) != 'ninguno' ){
				$price = get_string_price($entry['76']);
				if ( $price >= 0 ){
					$entry[FORM_DISCOUNT_FIELD] = intval($price - ($price * PERCENT_DISCOUNT)/100) . '€';
				}
			}
		}
	}

    return $entries;
}












