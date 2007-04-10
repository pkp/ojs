<?php

/**
 * String.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
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
			return mb_encode_mimeheader($string, ini_get('mbstring.internal_encoding'), 'B', MAIL_EOL);
		}  else {
			return $string;
		}
	}
	
	function mail($to, $subject, $message, $additional_headers = '', $additional_parameters = '') {
		// Cannot use mb_send_mail as it base64 encodes the whole body of the email,
		// making it useless for multipart emails
		if (empty($additional_parameters)) {
			return mail($to, $subject, $message, $additional_headers);
		} else {
			return mail($to, $subject, $message, $additional_headers, $additional_parameters);
		}
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
		if (function_exists('mime_content_type')) {
			return mime_content_type($filename);
		} elseif (function_exists('finfo_open')) {
			static $fi;
			if (!isset($fi)) {
				$fi = finfo_open(FILEINFO_MIME, Config::getVar('finfo', 'mime_database_path'));
			}
			if ($fi !== false) {
				return finfo_file($fi, $filename);
			}
		}

		// Fall back on an external "file" tool
		$f = escapeshellarg($filename);
		$result = trim(`file -bi $f`);
		// Make sure we just return the mime type.
		if (($i = strpos($result, ';')) !== false) {
			$result = trim(substr($result, 0, $i));
		}
		return $result;
	}


	/**
	 * Strip unsafe HTML from the input text. Covers XSS attacks like scripts,
	 * onclick(...) attributes, javascript: urls, and special characters.
	 * @param $input string input string
	 * @return string
	 */
	function stripUnsafeHtml($input) {
		// Parts of this implementation were taken from Horde:
		// see http://cvs.horde.org/co.php/framework/MIME/MIME/Viewer/html.php.

		static $allowedHtmlTags = '<a> <em> <strong> <cite> <code> <ul> <ol> <li> <dl> <dt> <dd> <b> <i> <u> <img> <sup> <sub>';
		$html = strip_tags($input, $allowedHtmlTags);

		// Change space entities to space characters
		$html = preg_replace('/&#(x0*20|0*32);?/i', ' ', $html);

		// Remove non-printable characters
		$html = preg_replace('/&#x?0*([9A-D]|1[0-3]);/i', '&nbsp;', $html);
		$html = preg_replace('/&#x?0*[9A-D]([^0-9A-F]|$)/i', '&nbsp\\1', $html);
		$html = preg_replace('/&#0*(9|1[0-3])([^0-9]|$)/i', '&nbsp\\2', $html);

		// Remove overly long numeric entities
		$html = preg_replace('/&#x?0*[0-9A-F]{6,};?/i', '&nbsp;', $html);

		/* Get all attribute="javascript:foo()" tags. This is
		 * essentially the regex /(=|url\()("?)[^>]* script:/ but
	         * expanded to catch camouflage with spaces and entities. */
		$preg 	= '/((&#0*61;?|&#x0*3D;?|=)|'
			. '((u|&#0*85;?|&#x0*55;?|&#0*117;?|&#x0*75;?)\s*'
			. '(r|&#0*82;?|&#x0*52;?|&#0*114;?|&#x0*72;?)\s*'
			. '(l|&#0*76;?|&#x0*4c;?|&#0*108;?|&#x0*6c;?)\s*'
			. '(\()))\s*'
			. '(&#0*34;?|&#x0*22;?|"|&#0*39;?|&#x0*27;?|\')?'
			. '[^>]*\s*'
			. '(s|&#0*83;?|&#x0*53;?|&#0*115;?|&#x0*73;?)\s*'
			. '(c|&#0*67;?|&#x0*43;?|&#0*99;?|&#x0*63;?)\s*'
			. '(r|&#0*82;?|&#x0*52;?|&#0*114;?|&#x0*72;?)\s*'
			. '(i|&#0*73;?|&#x0*49;?|&#0*105;?|&#x0*69;?)\s*'
			. '(p|&#0*80;?|&#x0*50;?|&#0*112;?|&#x0*70;?)\s*'
			. '(t|&#0*84;?|&#x0*54;?|&#0*116;?|&#x0*74;?)\s*'
			. '(:|&#0*58;?|&#x0*3a;?)/i';
		$html = preg_replace($preg, '\1\8OJSCleaned', $html);

		/* Get all on<foo>="bar()". NEVER allow these. */
		$html =	preg_replace('/([\s"\']+'
			. '(o|&#0*79;?|&#0*4f;?|&#0*111;?|&#0*6f;?)'
			. '(n|&#0*78;?|&#0*4e;?|&#0*110;?|&#0*6e;?)'
			. '\w+)\s*=/i', '\1OJSCleaned=', $html);

		$pattern = array(
			'|<([^>]*)&{.*}([^>]*)>|',
			'|<([^>]*)mocha:([^>]*)>|i',
			'|<([^>]*)binding:([^>]*)>|i'
		);
		$replace = array('<&{;}\3>', '<\1OJSCleaned:\2>', '<\1OJSCleaned:\2>');
		$html = preg_replace($pattern, $replace, $html);

		return $html;
	}

	/**
	 * Convert UTF-8 encoded characters in a string to escaped HTML entities
	 * This is a helper function for transcoding HTML (perhaps move to core?)
	 * @param $input string input string
	 * @return string
	 */
	function utf2html ($str) {
		$ret = "";
		$max = strlen($str);
		$last = 0;  // keeps the index of the last regular character
		
   for ($i=0; $i<$max; $i++) {
       $c = $str{$i};
       $c1 = ord($c);
       if ($c1>>5 == 6) {  // 110x xxxx, 110 prefix for 2 bytes unicode
           $ret .= substr($str, $last, $i-$last); // append all the regular characters we've passed
           $c1 &= 31; // remove the 3 bit two bytes prefix
           $c2 = ord($str{++$i}); // the next byte
           $c2 &= 63;  // remove the 2 bit trailing byte prefix
           $c2 |= (($c1 & 3) << 6); // last 2 bits of c1 become first 2 of c2
           $c1 >>= 2; // c1 shifts 2 to the right
           $ret .= "&#" . ($c1 * 0x100 + $c2) . ";"; // this is the fastest string concatenation
           $last = $i+1;     
       }
       elseif ($c1>>4 == 14) {  // 1110 xxxx, 110 prefix for 3 bytes unicode
           $ret .= substr($str, $last, $i-$last); // append all the regular characters we've passed
           $c2 = ord($str{++$i}); // the next byte
           $c3 = ord($str{++$i}); // the third byte
           $c1 &= 15; // remove the 4 bit three bytes prefix
           $c2 &= 63;  // remove the 2 bit trailing byte prefix
           $c3 &= 63;  // remove the 2 bit trailing byte prefix
           $c3 |= (($c2 & 3) << 6); // last 2 bits of c2 become first 2 of c3
           $c2 >>=2; //c2 shifts 2 to the right
           $c2 |= (($c1 & 15) << 4); // last 4 bits of c1 become first 4 of c2
           $c1 >>= 4; // c1 shifts 4 to the right
           $ret .= '&#' . (($c1 * 0x10000) + ($c2 * 0x100) + $c3) . ';'; // this is the fastest string concatenation
           $last = $i+1;     
       }
   }
		$str=$ret . substr($str, $last, $i); // append the last batch of regular characters

		return $str;   
	}

}

?>
