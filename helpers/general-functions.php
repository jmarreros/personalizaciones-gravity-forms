<?php




//reportar errores con var_dump
function dump_error_log( $object=null ){
    ob_start();
    var_dump( $object );
    $contents = ob_get_contents();
    ob_end_clean();
    error_log( $contents );
}










