<?php

/**
 * String.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
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

		static $allowedHtmlTags = '<a> <em> <strong> <cite> <code> <ul> <ol> <li> <dl> <dt> <dd> <b> <i> <u> <img> <sup> <sub> <br> <p>';
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
	 * Detect whether a string contains non-ascii multibyte sequences in the UTF-8 range
	 * Does not require any multibyte PHP libraries
	 * @param $input string input string
	 * @return boolean
	 */
	function isUTF8 ($str) {
	    // From http://w3.org/International/questions/qa-forms-utf-8.html
		return preg_match('%(?:
				[\xC2-\xDF][\x80-\xBF]								# non-overlong 2-byte
				|\xE0[\xA0-\xBF][\x80-\xBF]					# excluding overlongs
				|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}		# straight 3-byte
				|\xED[\x80-\x9F][\x80-\xBF]					# excluding surrogates
				|\xF0[\x90-\xBF][\x80-\xBF]{2}				# planes 1-3
				|[\xF1-\xF3][\x80-\xBF]{3}						# planes 4-15
				|\xF4[\x80-\x8F][\x80-\xBF]{2}				# plane 16
				)+%xs', $str);
	}

	/**
	 * Returns the UTF-8 string corresponding to the unicode value
	 * Does not require any multibyte PHP libraries
	 * (from php.net, courtesy - romans@void.lv)
	 * @param $input string input string
	 * @return boolean
	 */
	function code2utf ($num) {
		if ($num < 128) return chr($num);
		if ($num < 2048) return chr(($num >> 6) + 192) . chr(($num & 63) + 128);
		if ($num < 65536) return chr(($num >> 12) + 224) . chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);
		if ($num < 2097152) return chr(($num >> 18) + 240) . chr((($num >> 12) & 63) + 128) . chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);
		return '';
	}

	/**
	 * Convert UTF-8 encoded characters in a string to escaped HTML entities
	 * This is a helper function for transcoding into HTML
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
			if ($c1>>5 == 6) {										// 110x xxxx, 110 prefix for 2 bytes unicode
				$ret .= substr($str, $last, $i-$last);			// append all the regular characters we've passed
				$c1 &= 31;													// remove the 3 bit two bytes prefix
				$c2 = ord($str{++$i});								// the next byte
				$c2 &= 63;													// remove the 2 bit trailing byte prefix
				$c2 |= (($c1 & 3) << 6);							// last 2 bits of c1 become first 2 of c2
				$c1 >>= 2;													// c1 shifts 2 to the right
				$ret .= "&#" . ($c1 * 0x100 + $c2) . ";";	// this is the fastest string concatenation
				$last = $i+1;     
			}
			elseif ($c1>>4 == 14) { 								// 1110 xxxx, 110 prefix for 3 bytes unicode
				$ret .= substr($str, $last, $i-$last);			// append all the regular characters we've passed
				$c2 = ord($str{++$i}); 								// the next byte
				$c3 = ord($str{++$i}); 								// the third byte
				$c1 &= 15; 												// remove the 4 bit three bytes prefix
				$c2 &= 63; 												// remove the 2 bit trailing byte prefix
				$c3 &= 63; 												// remove the 2 bit trailing byte prefix
				$c3 |= (($c2 & 3) << 6);							// last 2 bits of c2 become first 2 of c3
				$c2 >>=2; 													//c2 shifts 2 to the right
				$c2 |= (($c1 & 15) << 4);							// last 4 bits of c1 become first 4 of c2
				$c1 >>= 4; 												// c1 shifts 4 to the right
				$ret .= '&#' . (($c1 * 0x10000) + ($c2 * 0x100) + $c3) . ';'; // this is the fastest string concatenation
				$last = $i+1;     
			}
		}
		$str=$ret . substr($str, $last, $i); // append the last batch of regular characters

		return $str;   
	}

	/**
	 * Convert escaped HTML entities in a string to UTF-8 encoded characters 
	 * This is a native alternative to the buggy html_entity_decode() using UTF8
	 * @param $input string input string
	 * @return string
	 */
	 function html2utf($str) {
		// convert named entities to numeric entities
		$str = strtr($str, String::getHTMLEntities());

		// use PCRE-aware replace function to replace numeric entities
		$str = String::regexp_replace('~&#x([0-9a-f]+);~ei', 'String::code2utf(hexdec("\\1"))', $str);
		$str = String::regexp_replace('~&#([0-9]+);~e', 'String::code2utf(\\1)', $str);
	 }

	/**
	 * Convert UTF-8 numeric entities in a string to ASCII values
	 * This is a helper function for transcoding into HTML/XML
	 * @param $input string input string
	 * @return string
	 */
	function utf2ascii ($str) {
		// define the conversion table
		$entities = array(
			"&#126;" => "~",			"&#160;" => " ",				"&#161;" => "!",
			"&#166;" => "|",				"&#177;" => "+/-",		"&#178;" => "2",
			"&#179;" => "3",			"&#180;" => "'",				"&#185;" => "1",
			"&#188;" => "1/4",		"&#189;" => "1/2",		"&#190;" => "3/4",
			"&#191;" => "?",				"&#192;" => "A",			"&#193;" => "A",
			"&#194;" => "A",			"&#195;" => "A",			"&#196;" => "A",
			"&#197;" => "A",			"&#198;" => "AE",			"&#199;" => "C",
			"&#200;" => "E",			"&#201;" => "E",			"&#202;" => "E",
			"&#203;" => "E",			"&#204;" => "I",				"&#205;" => "I",
			"&#206;" => "I",				"&#207;" => "I",				"&#208;" => "D",
			"&#209;" => "N",			"&#210;" => "O",			"&#211;" => "O",
			"&#212;" => "O",			"&#213;" => "O",			"&#214;" => "O",
			"&#215;" => "x",			"&#216;" => "O",			"&#217;" => "U",
			"&#218;" => "U",			"&#220;" => "U",			"&#221;" => "Y",
			"&#224;" => "a",			"&#225;" => "a",			"&#226;" => "a",
			"&#227;" => "a",			"&#228;" => "a",			"&#229;" => "a",
			"&#230;" => "ae",			"&#231;" => "c",				"&#232;" => "e",
			"&#233;" => "e",			"&#234;" => "e",			"&#235;" => "e",
			"&#236;" => "i",				"&#237;" => "i",				"&#238;" => "i",
			"&#239;" => "i",				"&#240;" => "o",			"&#241;" => "n",
			"&#242;" => "o",			"&#243;" => "o",			"&#244;" => "o",
			"&#245;" => "o",			"&#246;" => "o",			"&#248;" => "o",
			"&#249;" => "u",			"&#250;" => "u",			"&#252;" => "u",
			"&#253;" => "y",				"&#255;" => "y",				"&#338;" => "OE",
			"&#339;" => "oe",			"&#352;" => "S",			"&#353;" => "s",
			"&#376;" => "Y",			"&#39;" => "'",				"&#402;" => "f",
			"&#45;" => "-",				"&#710;" => "^",			"&#732;" => "~",
			"&#8194;" => " ",			"&#8195;" => " ",			"&#8201;" => " ",
			"&#8211;" => "-",			"&#8212;" => "--",		"&#8216;" => "'",
			"&#8217;" => "'",			"&#8218;" => ",",			"&#8220;" => '"',
			"&#8221;" => '"',			"&#8222;" => ",,",			"&#8226;" => "*",
			"&#8230;" => "...",			"&#8240;" => "%o",		"&#8242;" => "'",
			"&#8243;" => "''",			"&#8482;" => "TM",		"&#8722;" => "-",
			"&#8727;" => "*",			"&#8743;" => "/\\",		"&#8744;" => "\/",
			"&#8764;" => "~",			"&#8901;" => "*",			"&#913;" => "A",
			"&#914;" => "B",			"&#917;" => "E",			"&#918;" => "Z",
			"&#919;" => "H",			"&#921;" => "|",				"&#922;" => "K",
			"&#924;" => "M",			"&#925;" => "N",			"&#927;" => "O",
			"&#929;" => "P",			"&#932;" => "T",			"&#933;" => "Y",
			"&#935;" => "X",			"&#94;" => "^",				"&#959;" => "o",
			"&#961;" => "p",			"&#962;" => "?",				"&#977;" => "?",
			"&#982;" => "?");

		return strtr($str, $entities);
	}

	/**
	 * Convert Windows CP-1252 numeric entities in a string to named HTML entities
	 * This is a helper function for transcoding into HTML/XML
	 * @param $input string input string
	 * @return string
	 */
	function cp1252ToEntities ($str) {
		// define the conversion table;  from: http://www.noqta.it/tc.html
		$cp1252 = array(	"&#128;" => "",						"&#129;" => "",
										"&#130;" => "&lsquor;",		"&#131;" => "&fnof;",
										"&#132;" => "&ldquor;",		"&#133;" => "&hellip;",
										"&#134;" => "&dagger;",		"&#135;" => "&Dagger;",
										"&#136;" => "",						"&#137;" => "&permil;",
										"&#138;" => "&Scaron;",		"&#139;" => "&lsaquo;",
										"&#140;" => "&OElig;",			"&#141;" => "",
										"&#142;" => "",						"&#143;" => "",
										"&#144;" => "",						"&#145;" => "&lsquo;",
										"&#146;" => "&rsquo;",			"&#147;" => "&ldquo;",
										"&#148;" => "&rdquo;",		"&#149;" => "&bull;",
										"&#150;" => "&ndash;",		"&#151;" => "&mdash;",
										"&#152;" => "&tilde;",			"&#153;" => "&trade;",
										"&#154;" => "&scaron;",		"&#155;" => "&rsaquo;",
										"&#156;" => "&oelig;",			"&#157;" => "",
										"&#158;" => "",						"&#159;" => "&Yuml;");

		// corrections to map to valid ISO entities
		$cp1252["&#130;"] = "&lsquo;";
		$cp1252["&#132;"] = "&ldquo;";
		$cp1252["&#146;"] = "&rsquo;";
		$cp1252["&#148;"] = "&rdquo;";

		return strtr($str, $cp1252);
	}

	/**
	 * Return an associative array of named->numeric HTML entities
	 * Required to support HTML functions without objects in PHP4/PHP5
	 * From php.net: function.get-html-translation-table.php
	 * @return string
	 */
	 function getHTMLEntities () {
		// define the conversion table
		$html_entities = array(
			"&Aacute;" => "&#193;",			"&aacute;" => "&#225;",			"&Acirc;" => "&#194;",
			"&acirc;" => "&#226;",				"&acute;" => "&#180;",				"&AElig;" => "&#198;",
			"&aelig;" => "&#230;",				"&Agrave;" => "&#192;",			"&agrave;" => "&#224;",
			"&alefsym;" => "&#8501;",		"&Alpha;" => "&#913;",				"&alpha;" => "&#945;",
			"&amp;" => "&#38;",					"&and;" => "&#8743;",				"&ang;" => "&#8736;",
			"&apos;" => "&#39;",					"&Aring;" => "&#197;",				"&aring;" => "&#229;",
			"&asymp;" => "&#8776;",			"&Atilde;" => "&#195;",				"&atilde;" => "&#227;",
			"&Auml;" => "&#196;",				"&auml;" => "&#228;",				"&bdquo;" => "&#8222;",
			"&Beta;" => "&#914;",				"&beta;" => "&#946;",				"&brvbar;" => "&#166;",
			"&bull;" => "&#8226;",				"&cap;" => "&#8745;",				"&Ccedil;" => "&#199;",
			"&ccedil;" => "&#231;",				"&cedil;" => "&#184;",				"&cent;" => "&#162;",
			"&Chi;" => "&#935;",					"&chi;" => "&#967;",					"&circ;" => "&#94;",
			"&clubs;" => "&#9827;",			"&cong;" => "&#8773;",			"&copy;" => "&#169;",
			"&crarr;" => "&#8629;",			"&cup;" => "&#8746;",				"&curren;" => "&#164;",
			"&dagger;" => "&#8224;",		"&Dagger;" => "&#8225;",		"&darr;" => "&#8595;",
			"&dArr;" => "&#8659;",				"&deg;" => "&#176;",				"&Delta;" => "&#916;",
			"&delta;" => "&#948;",				"&diams;" => "&#9830;",			"&divide;" => "&#247;",
			"&Eacute;" => "&#201;",			"&eacute;" => "&#233;",			"&Ecirc;" => "&#202;",
			"&ecirc;" => "&#234;",				"&Egrave;" => "&#200;",			"&egrave;" => "&#232;",
			"&empty;" => "&#8709;",			"&emsp;" => "&#8195;",			"&ensp;" => "&#8194;",
			"&Epsilon;" => "&#917;",			"&epsilon;" => "&#949;",			"&equiv;" => "&#8801;",
			"&Eta;" => "&#919;",					"&eta;" => "&#951;",					"&ETH;" => "&#208;",
			"&eth;" => "&#240;",					"&Euml;" => "&#203;",				"&euml;" => "&#235;",
			"&euro;" => "&#8364;",				"&exist;" => "&#8707;",			"&fnof;" => "&#402;",
			"&forall;" => "&#8704;",			"&frac12;" => "&#189;",			"&frac14;" => "&#188;",
			"&frac34;" => "&#190;",			"&frasl;" => "&#8260;",				"&Gamma;" => "&#915;",
			"&gamma;" => "&#947;",			"&ge;" => "&#8805;",				"&gt;" => "&#62;",
			"&harr;" => "&#8596;",				"&hArr;" => "&#8660;",				"&hearts;" => "&#9829;",
			"&hellip;" => "&#8230;",			"&Iacute;" => "&#205;",				"&iacute;" => "&#237;",
			"&Icirc;" => "&#206;",				"&icirc;" => "&#238;",				"&iexcl;" => "&#161;",
			"&Igrave;" => "&#204;",			"&igrave;" => "&#236;",			"&image;" => "&#8465;",
			"&infin;" => "&#8734;",				"&int;" => "&#8747;",				"&Iota;" => "&#921;",
			"&iota;" => "&#953;",				"&iquest;" => "&#191;",			"&isin;" => "&#8712;",
			"&Iuml;" => "&#207;",				"&iuml;" => "&#239;",				"&Kappa;" => "&#922;",
			"&kappa;" => "&#954;",			"&Lambda;" => "&#923;",			"&lambda;" => "&#955;",
			"&lang;" => "&#9001;",				"&laquo;" => "&#171;",				"&larr;" => "&#8592;",
			"&lArr;" => "&#8656;",				"&lceil;" => "&#8968;",				
			"&ldquo;" => "&#8220;",			"&le;" => "&#8804;",					"&lfloor;" => "&#8970;",
			"&lowast;" => "&#8727;",			"&loz;" => "&#9674;",				"&lrm;" => "&#8206;",
			"&lsaquo;" => "&#8249;",			"&lsquo;" => "&#8216;",			"&lt;" => "&#60;",
			"&macr;" => "&#175;",				"&mdash;" => "&#8212;",			"&micro;" => "&#181;",
			"&middot;" => "&#183;",			"&minus;" => "&#45;",				"&Mu;" => "&#924;",
			"&mu;" => "&#956;",					"&nabla;" => "&#8711;",			"&nbsp;" => "&#160;",
			"&ndash;" => "&#8211;",			"&ne;" => "&#8800;",				"&ni;" => "&#8715;",
			"&not;" => "&#172;",					"&notin;" => "&#8713;",			"&nsub;" => "&#8836;",
			"&Ntilde;" => "&#209;",				"&ntilde;" => "&#241;",				"&Nu;" => "&#925;",
			"&nu;" => "&#957;",					"&Oacute;" => "&#211;",			"&oacute;" => "&#243;",
			"&Ocirc;" => "&#212;",				"&ocirc;" => "&#244;",				"&OElig;" => "&#338;",
			"&oelig;" => "&#339;",				"&Ograve;" => "&#210;",			"&ograve;" => "&#242;",
			"&oline;" => "&#8254;",			"&Omega;" => "&#937;",			"&omega;" => "&#969;",
			"&Omicron;" => "&#927;",		"&omicron;" => "&#959;",			"&oplus;" => "&#8853;",
			"&or;" => "&#8744;",					"&ordf;" => "&#170;",				"&ordm;" => "&#186;",
			"&Oslash;" => "&#216;",			"&oslash;" => "&#248;",			"&Otilde;" => "&#213;",
			"&otilde;" => "&#245;",				"&otimes;" => "&#8855;",			"&Ouml;" => "&#214;",
			"&ouml;" => "&#246;",				"&para;" => "&#182;",				"&part;" => "&#8706;",
			"&permil;" => "&#8240;",			"&perp;" => "&#8869;",				"&Phi;" => "&#934;",
			"&phi;" => "&#966;",					"&Pi;" => "&#928;",					"&pi;" => "&#960;",
			"&piv;" => "&#982;",					"&plusmn;" => "&#177;",			"&pound;" => "&#163;",
			"&prime;" => "&#8242;",			"&Prime;" => "&#8243;",			"&prod;" => "&#8719;",
			"&prop;" => "&#8733;",			"&Psi;" => "&#936;",					"&psi;" => "&#968;",
			"&quot;" => "&#34;",					"&radic;" => "&#8730;",			"&rang;" => "&#9002;",
			"&raquo;" => "&#187;",				"&rarr;" => "&#8594;",				"&rArr;" => "&#8658;",
			"&rceil;" => "&#8969;",				"&rdquo;" => "&#8221;",			"&real;" => "&#8476;",
			"&reg;" => "&#174;",					"&rfloor;" => "&#8971;",			"&Rho;" => "&#929;",
			"&rho;" => "&#961;",					"&rlm;" => "&#8207;",				"&rsaquo;" => "&#8250;",
			"&rsquo;" => "&#8217;",			"&sbquo;" => "&#8218;",			"&Scaron;" => "&#352;",
			"&scaron;" => "&#353;",			"&sdot;" => "&#8901;",				"&sect;" => "&#167;",
			"&shy;" => "&#173;",					"&Sigma;" => "&#931;",			"&sigma;" => "&#963;",
			"&sigmaf;" => "&#962;",			"&sim;" => "&#8764;",				"&spades;" => "&#9824;",
			"&sub;" => "&#8834;",				"&sube;" => "&#8838;",			"&sum;" => "&#8721;",
			"&sup1;" => "&#185;",				"&sup2;" => "&#178;",				"&sup3;" => "&#179;",
			"&sup;" => "&#8835;",				"&supe;" => "&#8839;",			"&szlig;" => "&#223;",
			"&Tau;" => "&#932;",				"&tau;" => "&#964;",					"&there4;" => "&#8756;",
			"&Theta;" => "&#920;",				"&theta;" => "&#952;",				"&thetasym;" => "&#977;",
			"&thinsp;" => "&#8201;",			"&THORN;" => "&#222;",			"&thorn;" => "&#254;",
			"&tilde;" => "&#126;",				"&times;" => "&#215;",				"&trade;" => "&#8482;",
			"&Uacute;" => "&#218;",			"&uacute;" => "&#250;",			"&uarr;" => "&#8593;",
			"&uArr;" => "&#8657;",				"&Ucirc;" => "&#219;",				"&ucirc;" => "&#251;",
			"&Ugrave;" => "&#217;",			"&ugrave;" => "&#249;",			"&uml;" => "&#168;",
			"&upsih;" => "&#978;",				"&Upsilon;" => "&#933;",			"&upsilon;" => "&#965;",
			"&Uuml;" => "&#220;",				"&uuml;" => "&#252;",				"&weierp;" => "&#8472;",
			"&Xi;" => "&#926;",					"&xi;" => "&#958;",					"&Yacute;" => "&#221;",
			"&yacute;" => "&#253;",			"&yen;" => "&#165;",					"&yuml;" => "&#255;",
			"&Yuml;" => "&#376;",				"&Zeta;" => "&#918;",				"&zeta;" => "&#950;",
			"&zwj;" => "&#8205;",				"&zwnj;" => "&#8204;");

		return $html_entities;
	 }

}
?>