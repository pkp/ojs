<?php

/**
 * String.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package core
 *
 * String manipulation wrapper class.
 *
 * $Id$
 */

class String {

	/**
	 * Perform initialization required for the string wrapper library.
	 */
	function init() {
		$clientCharset = strtolower(Config::getVar('i18n', 'client_charset'));
		
		// FIXME Should non-UTF-8 encodings be supported with mbstring?
		$PCRE_UTF8 = '';
		if ($clientCharset == 'utf-8' && String::hasPCREUTF8()) {
			$PCRE_UTF8 = 'u';
		}
		
		// Check if mbstring is installed
		// NOTE: Requires PHP >= 4.3.0
		if (String::hasMBString()) {
			// mbstring routines are available
			define('ENABLE_MBSTRING', 1);
			
			// Set up required ini settings for mbstring
			ini_set('mbstring.internal_encoding', $clientCharset);
			if ($clientCharset == 'utf-8') {
				ini_set('mbstring.substitute_character', '12307');
			}
			
			// FIXME Do any other mbstring settings need to be set?
		}
		
		// Define modifier to be used in regexp_* routines
		define('PCRE_UTF8', $PCRE_UTF8);
	}
	
	/**
	 * Check if server has the mbstring library.
	 * Currently requires PHP >= 4.3.0 (for mb_strtolower, mb_strtoupper, and mb_substr_count)
	 */
	function hasMBString() {
		return (function_exists('mb_strlen')
				&& function_exists('mb_strpos')
				&& function_exists('mb_strrpos')
				&& function_exists('mb_substr')
				&& function_exists('mb_strtolower')
				&& function_exists('mb_strtoupper')
				&& function_exists('mb_substr_count')
				&& function_exists('mb_send_mail'));
	}
	
	/**
	 * Check if server supports the PCRE_UTF8 modifier.
	 */
	function hasPCREUTF8() {
		// The PCRE_UTF8 modifier is only supported on PHP >= 4.1.0 (*nix) or PHP >= 4.2.3 (win32)
		// Evil check to see if PCRE_UTF8 is supported
		if (@preg_match('//u', '')) {
			return true;
		} else {
			return false;
		}
	}
	
	//
	// Wrappers for basic string manipulation routines.
	// See the php.net documentation for usage.
	//
	
	function strlen($string) {
		if (defined('ENABLE_MBSTRING')) {
			return mb_strlen($string);
		} else {
			return strlen($string);
		}
	}
	
	function strpos($haystack, $needle, $offset = 0) {
		if (defined('ENABLE_MBSTRING')) {
			return mb_strpos($haystack, $needle, $offset);
		} else {
			return strpos($haystack, $needle, $offset);
		}
	}
	
	function strrpos($haystack, $needle) {
		if (defined('ENABLE_MBSTRING')) {
			return mb_strrpos($haystack, $needle);
		} else {
			return strrpos($haystack, $needle);
		}
	}
	
	function substr($string, $start, $length = null) {
		if (defined('ENABLE_MBSTRING')) {
			$substr = 'mb_substr';
		} else {
			$substr = 'substr';
		}
		if (isset($length)) {
			return $substr($string, $start, $length);
		} else {
			return $substr($string, $start);
		}
	}
	
	function strtolower($string) {
		if (defined('ENABLE_MBSTRING')) {
			return mb_strtolower($string); // Requires PHP >= 4.3.0
		} else {
			return strtolower($string);
		}
	}
	
	function strtoupper($string) {
		if (defined('ENABLE_MBSTRING')) {
			return mb_strtoupper($string); // Requires PHP >= 4.3.0
		} else {
			return strtolower($string);
		}
	}
	
	function substr_count($haystack, $needle) {
		if (defined('ENABLE_MBSTRING')) {
			return mb_substr_count($haystack, $needle); // Requires PHP >= 4.3.0
		} else {
			return substr_count($haystack, $needle);
		}
	}
	
	function encode_mime_header($string) {
		if (defined('ENABLE_MBSTRING')) {
			return mb_encode_mimeheader($string);
		}  else {
			return $string;
		}
	}
	
	function mail($to, $subject, $message, $additional_headers = '', $additional_parameters = '') {
		// Cannot use mb_send_mail as it base64 encodes the whole body of the email,
		// making it useless for multipart emails
		
		return mail($to, $subject, $message, $additional_headers, $additional_parameters);
	}
	
	//
	// Wrappers for PCRE-compatible regular expression routines.
	// See the php.net documentation for usage.
	//
	
	function regexp_quote($string, $delimiter = '/') {
		return preg_quote($string, $delimiter);
	}
	
	function regexp_grep($pattern, $input) {
		$pattern .= PCRE_UTF8;
		return preg_grep($pattern, $input);
	}
	
	function regexp_match($pattern, $subject) {
		$pattern .= PCRE_UTF8;
		return preg_match($pattern, $subject);
	}
	
	function regexp_match_get($pattern, $subject, &$matches) {
		// NOTE: This function was created since PHP < 5.x does not support optional reference parameters
		$pattern .= PCRE_UTF8;
		return preg_match($pattern, $subject, $matches);
	}
	
	function regexp_match_all($pattern, $subject, &$matches) {
		$pattern .= PCRE_UTF8;
		return preg_match_all($pattern, $subject, $matches);
	}
	
	function regexp_replace($pattern, $replacement, $subject, $limit = -1) {
		$pattern .= PCRE_UTF8;
		return preg_replace($pattern, $replacement, $subject, $limit);
	}
	
	function regexp_replace_callback($pattern, $callback, $subject, $limit = -1) {
		$pattern .= PCRE_UTF8;
		return preg_replace_callback($pattern, $callback, $subject, $limit);
	}
	
	function regexp_split($pattern, $subject, $limit = -1) {
		$pattern .= PCRE_UTF8;
		return preg_split($pattern, $subject, $limit);
	}

	function mime_content_type($filename) {
		if (!function_exists('mime_content_type')) {
			$f = escapeshellarg($filename);
			return trim(`file -bi $f`);
		}
		return mime_content_type($filename);
	}
}

?>
