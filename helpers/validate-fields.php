<?php

// Validate DNI
function gf_validate_spain_dni($dni){
	$letra = substr($dni, -1);
	$numeros = substr($dni, 0, -1);
	if ( substr("TRWAGMYFPDXBNJZSQVHLCKE", $numeros%23, 1) == $letra && 
		strlen($letra) == 1 && 
		strlen ($numeros) == 8 ){
		return true;
	}
	return false;
}

// Validate IBAN
function gf_validate_iban($iban) {
	// Normalize input (remove spaces and make upcase)
	$iban = strtoupper(str_replace(' ', '', $iban));

	if (preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{1,30}$/', $iban)) {
	    $country = substr($iban, 0, 2);
	    $check = intval(substr($iban, 2, 2));
	    $account = substr($iban, 4);

	    // To numeric representation
	    $search = range('A','Z');
	    foreach (range(10,35) as $tmp)
	        $replace[]=strval($tmp);
	    $numstr=str_replace($search, $replace, $account.$country.'00');

	    // Calculate checksum
	    $checksum = intval(substr($numstr, 0, 1));
	    for ($pos = 1; $pos < strlen($numstr); $pos++) {
	        $checksum *= 10;
	        $checksum += intval(substr($numstr, $pos,1));
	        $checksum %= 97;
	    }

	    return ((98-$checksum) == $check);
	} else
	    return false;
}









