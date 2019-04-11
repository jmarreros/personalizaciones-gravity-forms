<?php

// Devuelve el valor del stock de una cadena, -1 si no lo encutnra
function get_string_price($str){
    $price = substr(strrchr($str, ':'), 1 );
    if ( $price !== false ){
        $price = intval($price);
        if ( $price >= 0) return $price;
    }
    return -1;
}

//reportar errores con var_dump
function dump_error_log( $object=null ){
    ob_start();
    var_dump( $object );
    $contents = ob_get_contents();
    ob_end_clean();
    error_log( $contents );
}