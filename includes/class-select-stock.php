<?php

/**
 * Clase que administra el stock pot ítem de un campo select
 *
 */
class Select_Stock{
	const GF_STOCK_CONTROL = 'gf_select_stock_control_'; //parte de la key a almacenar para le select
	private $id_select;
	private $option_key;

	/**
	 * Constructor de la clase, se requiere el id del campo,
	 * que será el id de un campo select
	 *
	 * @param int $id_select
	 * @return void
	*/
	public function __construct($id_select){
		$this->id_select = $id_select;
		$this->option_key = self::GF_STOCK_CONTROL.$this->id_select;
	}

	/**
	 * Obtiene como entrada el array de arrays de los items del select y
	 * recupera los valores de la BD del stock, hace las modificaciones a los ítems
	 * y devuelve nuevamente un array modificado
	 *
	 * @param array $choices
	 * @return array
	*/
	public function get_modify_select($choices){
		$modify_choices = $choices;

		//recupero las opciones de la BD
		$option_val = get_option($this->option_key);
		if (! $option_val) {
			return $choices;
		}

		// Hago las modificaciones del stock en el array modify_choices
		foreach( $modify_choices as $i => $item){
			$item_key = $this->remove_string_stock($item['text']);
			if ( array_key_exists($item_key, $option_val) ){
				$stock = $option_val[$item_key];
				$modify_choices[$i]['text'] = $item_key.'- '.$stock;
			};
		}

		return $modify_choices;
	}

	/**
	 * Graba los stocks asociados a cada ítem para el select específicado
	 * se usa la tabla wp_options de WordPress para grabar estos valores
	 * se almacena como un solo registro en un array asociativo el item asociado con el stock
	 *
	 * @param array $choices
	 * @return bool
	*/
	public function save_option($choices){
		$option_val = [];

		// Lleno el array option_val con los keys y con los stocks
		foreach ($choices as $item) {
			$item_key = $this->remove_string_stock($item['text']);
			$item_val = $this->get_string_stock($item['text']);
			if ($item_val >= 0){
				$option_val[$item_key] = $item_val;
			}
		}

		return update_option($this->option_key, $option_val);
	}


	/**
	 * Muestra sólo los ítems que tienen stock
	 * si tiene stock 0 los elimina del select devuelto
	 * además muestra sólo las cadenas sin el stock al final
	 *
	 * @param array $choices
	 * @return array
	*/
	public function remove_item_without_stock($choices){
		$modify_choices = $choices;

		//recupero las opciones de la BD
		$option_val = get_option($this->option_key);
		if (! $option_val) {
			return $choices;
		}

		// Hago las modificaciones eliminando el stock del array, tanto texto como valores
		foreach( $modify_choices as $i => $item){
			$item_key = $this->remove_string_stock($item['text']);
			if ( array_key_exists($item_key, $option_val) ){
				// comprobamos si el item tiene stock
				if ( $option_val[$item_key] > 0 ){
					$modify_choices[$i]['text'] = $item_key;
					$modify_choices[$i]['value'] = $item_key;
				} else {
					unset($modify_choices[$i]);
				};
			}
		}

		return $modify_choices;
	}


	/**
	 * Permite restar el stock de un ítem específico de un select determinado
	 *
	 * @param array $choices
	 * @return array
	*/
	public function subtract_item_stock($item_key){
		//recupero las opciones de la BD
		$option_val = get_option($this->option_key);
		if (! $option_val) {
			return false;
		}

		// Recupero el stock de las opciones y lo descuento
		if (array_key_exists($item_key, $option_val) ){
			$stock = $option_val[$item_key];
			if ( $stock > 0 ) {
				$option_val[$item_key] = $stock - 1;
			}
			return update_option($this->option_key, $option_val);
		}
		return false;
	}

	// Métodos auxiliares
	// ===================

	/**
	 * Obtiene el número final de una cadena que sería el stock
	 * debe estar separado con -, retorna el valor, y en caso no existir -1
	 *
	 * @param string $str
	 * @return int
	*/
	private function get_string_stock($str){
		$stock = substr(strrchr($str, '-'), 1 );
		if ( $stock !== false  && is_numeric(trim($stock)) ){
			$stock = intval($stock);
			if ( $stock >= 0) return $stock;
		}
		return -1;
	}

	/**
	 * Quita el stock del valor de la cadena
	 * dejando la cadena sólo con caracteres iniciales sin stock
	 *
	 * @param string $str
	 * @return string
	*/
	private function remove_string_stock($str){
		$last_pos =  strrpos($str, '-');
		if ( $last_pos !== false && $this->get_string_stock($str) >= 0 ){
			return trim(substr($str, 0, $last_pos));
		}
		return $str;
	}

}