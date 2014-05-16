<?php

require_once(dirname(__FILE__).'/Unframed.php');

/**
 * Generate $byteLength pseudo random bytes with Open SSL and return an hex
 * encoded string.
 *
 * @param int $byteLength
 *
 * @return string the random bytes encoded in hex
 */
function unframed_jsbn_random($byteLength=20) {
	return strtoupper(bin2hex(openssl_random_pseudo_bytes($byteLength)));
}

/**
 * Generate a new RSA key of the given $bitLength or 512 bits.
 */
function unframed_jsbn_rsa_new($bitLength=512) {
	return openssl_pkey_new(array(
	    "private_key_bits" => $bitLength,
	    "private_key_type" => OPENSSL_KEYTYPE_RSA,
	));
}

/**
 * Get the public details of an RSA key encoded in uppercase hexadecimal.
 */
function unframed_jsbn_rsa_public($keys) {
	$details = openssl_pkey_get_details($keys);
	$modulus = $details['rsa']['n'];
	$exponent = $details['rsa']['e'];
	return array(
		"n" => strtoupper(bin2hex($modulus)),
		"e" => strtoupper(bin2hex($exponent))
		);
}

/**
 * Decode and decrypt from jsbn.
 */
function unframed_jsbn_decrypt($base64Cipher, $privateKey) {
	if (openssl_private_decrypt($data, $decrypted, $privateKey) === FALSE) {
		throw new Unframed('unframed_jsbn_decrypt: openssl_private_decrypt(...) === FALSE');
	} else {
		return $decrypted;
	}
}
