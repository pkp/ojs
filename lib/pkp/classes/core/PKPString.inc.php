<?php

/**
 * @file classes/core/PKPString.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPString
 * @ingroup core
 *
 * @brief String manipulation wrapper class.
 *
 */


/*
 * Perl-compatibile regular expression (PCRE) constants:
 * These are defined application-wide for consistency
 */

/*
 * RFC-2396 URIs
 *
 * Thanks to the PEAR Validation package (Tomas V.V.Cox <cox@idecnet.com>,
 * Pierre-Alain Joye <pajoye@php.net>, Amir Mohammad Saied <amir@php.net>)
 *
 * Originally published under the "New BSD License"
 * http://www.opensource.org/licenses/bsd-license.php
 */
define('PCRE_URI', '(?:([a-z][-+.a-z0-9]*):)?' .						// Scheme
		   '(?://' .
		   '(?:((?:%[0-9a-f]{2}|[-a-z0-9_.!~*\'();:\&=+$,])*)@)?' .			// User
		   '(?:((?:[a-z0-9](?:[-a-z0-9]*[a-z0-9])?\.)*[a-z](?:[a-z0-9]+)?\.?)' .	// Hostname
		   '|([0-9]{1,3}(?:\.[0-9]{1,3}){3}))' .					// IP Address
		   '(?::([0-9]*))?)' .								// Port
		   '((?:/(?:%[0-9a-f]{2}|[-a-z0-9_.!~*\'():@\&=+$,;])*)*/?)?' .			// Path
		   '(?:\?([^#]*))?' .								// Query String
		   '(?:\#((?:%[0-9a-f]{2}|[-a-z0-9_.!~*\'();/?:@\&=+$,])*))?');			// Fragment

// RFC-2822 email addresses
define('PCRE_EMAIL_ADDRESS',
	'[-a-z0-9!#\$%&\'\*\+\/=\?\^_\`\{\|\}~]' . '+' . // One or more atom characters.
	'(\.' . '[-a-z0-9!#\$%&\'\*\+\/=\?\^_\`\{\|\}~]' . '+)*'. // Followed by zero or more dot separated sets of one or more atom characters.
	'@'. // Followed by an "at" character.
	'(' . '([a-z0-9]([-a-z0-9]*[a-z0-9]+)?)' . '{1,63}\.)+'. // Followed by one or max 63 domain characters (dot separated).
	'([a-z0-9]([-a-z0-9]*[a-z0-9]+)?)' . '{2,63}' // Must be followed by one set consisting a period of two or max 63 domain characters.
	);

// Two different types of camel case: one for class names and one for method names
define ('CAMEL_CASE_HEAD_UP', 0x01);
define ('CAMEL_CASE_HEAD_DOWN', 0x02);

class PKPString {
	/**
	 * Perform initialization required for the string wrapper library.
	 * @return null
	 */
	static function init() {
		$clientCharset = strtolower_codesafe(Config::getVar('i18n', 'client_charset'));

		// Check if mbstring is installed (requires PHP >= 4.3.0)
		if (self::hasMBString()) {
			// mbstring routines are available
			define('ENABLE_MBSTRING', true);

			// Set up required ini settings for mbstring
			// FIXME Do any other mbstring settings need to be set?
			mb_internal_encoding($clientCharset);
			mb_substitute_character('63');		// question mark
		}

		// Define modifier to be used in regexp_* routines
		// FIXME Should non-UTF-8 encodings be supported with mbstring?
		if ($clientCharset == 'utf-8' && self::hasPCREUTF8()) {
			define('PCRE_UTF8', 'u');
		} else {
			define('PCRE_UTF8', '');
		}
	}

	/**
	 * Check if server has the mbstring library.
	 * Currently requires PHP >= 4.3.0 (for mb_strtolower, mb_strtoupper,
	 * and mb_substr_count)
	 * @return boolean Returns true iff the server supports mbstring functions.
	 */
	static function hasMBString() {
		static $hasMBString;
		if (isset($hasMBString)) return $hasMBString;

		// If string overloading is active, it will break many of the
		// native implementations. mbstring.func_overload must be set
		// to 0, 1 or 4 in php.ini (string overloading disabled).
		if (ini_get('mbstring.func_overload') && defined('MB_OVERLOAD_STRING')) {
			$hasMBString = false;
		} else {
			$hasMBString = (
				extension_loaded('mbstring') &&
				function_exists('mb_strlen') &&
				function_exists('mb_strpos') &&
				function_exists('mb_strrpos') &&
				function_exists('mb_substr') &&
				function_exists('mb_strtolower') &&
				function_exists('mb_strtoupper') &&
				function_exists('mb_substr_count') &&
				function_exists('mb_send_mail')
			);
		}
		return $hasMBString;
	}

	/**
	 * Check if server supports the PCRE_UTF8 modifier.
	 * @return boolean True iff the server supports the PCRE_UTF8 modifier.
	 */
	static function hasPCREUTF8() {
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
	// See the phputf8 documentation for usage.
	//

	/**
	 * @see http://ca.php.net/manual/en/function.strlen.php
	 * @param $string string Input string
	 * @return int String length
	 */
	static function strlen($string) {
		if (defined('ENABLE_MBSTRING')) {
			require_once './lib/pkp/lib/phputf8/mbstring/core.php';
		} else {
			require_once './lib/pkp/lib/phputf8/utils/unicode.php';
			require_once './lib/pkp/lib/phputf8/native/core.php';
		}
		return utf8_strlen($string);
	}

	/**
	 * @see http://ca.php.net/manual/en/function.strpos.php
	 * @param $haystack string Input haystack to search
	 * @param $needle string Input needle to search for
	 * @param $offset int Offset at which to begin searching
	 * @return int Position of needle within haystack
	 */
	static function strpos($haystack, $needle, $offset = 0) {
		if (defined('ENABLE_MBSTRING')) {
			require_once './lib/pkp/lib/phputf8/mbstring/core.php';
		} else {
			require_once './lib/pkp/lib/phputf8/utils/unicode.php';
			require_once './lib/pkp/lib/phputf8/native/core.php';
		}
		return utf8_strpos($haystack, $needle, $offset);
	}

	/**
	 * @see http://ca.php.net/manual/en/function.strrpos.php
	 * @param $haystack string Haystack to search
	 * @param $needle string Needle to search haystack for
	 * @return int String position of needle in haystack (starting from end of haystack)
	 */
	static function strrpos($haystack, $needle) {
		if (defined('ENABLE_MBSTRING')) {
			require_once './lib/pkp/lib/phputf8/mbstring/core.php';
		} else {
			require_once './lib/pkp/lib/phputf8/utils/unicode.php';
			require_once './lib/pkp/lib/phputf8/native/core.php';
		}
		return utf8_strrpos($haystack, $needle);
	}

	/**
	 * @see http://ca.php.net/manual/en/function.substr.php
	 * @param $string string Subject to extract substring from
	 * @param $start int Position to start from
	 * @param $length int Length to extract, or false for entire string from start position
	 * @return string Substring of $string
	 */
	static function substr($string, $start, $length = false) {
		if (defined('ENABLE_MBSTRING')) {
			require_once './lib/pkp/lib/phputf8/mbstring/core.php';
		} else {
			require_once './lib/pkp/lib/phputf8/utils/unicode.php';
			require_once './lib/pkp/lib/phputf8/native/core.php';
			// The default length value for the native implementation
			// differs
			if ($length === false) $length = null;
		}
		return utf8_substr($string, $start, $length);
	}

	/**
	 * @see http://ca.php.net/manual/en/function.substr_replace.php
	 * @param $string string Source string to perform replacement upon
	 * @param $replacement string Replacement to move into $string
	 * @param $start int Start location for replacement
	 * @param $length int Number of characters to replace in source string with $replacement
	 * @return string String resulting from replacement
	 */
	static function substr_replace($string, $replacement, $start, $length = null) {
		if (extension_loaded('mbstring') === true) {
			$string_length = self::strlen($string);

			if ($start < 0) {
				$start = max(0, $string_length + $start);
			} else if ($start > $string_length) {
				$start = $string_length;
			}

			if ($length < 0) {
				$length = max(0, $string_length - $start + $length);
			} else if ((is_null($length) === true) || ($length > $string_length)) {
				$length = $string_length;
			}

			if (($start + $length) > $string_length) {
				$length = $string_length - $start;
			}

			return self::substr($string, 0, $start) . $replacement . self::substr($string, $start + $length, $string_length - $start - $length);
		}
		return (is_null($length) === true) ? substr_replace($string, $replacement, $start) : substr_replace($string, $replacement, $start, $length);
	}

	/**
	 * @see http://ca.php.net/manual/en/function.strtolower.php
	 * @param $string string Input string
	 * @return string Lower case version of input string
	 */
	static function strtolower($string) {
		if (defined('ENABLE_MBSTRING')) {
			require_once './lib/pkp/lib/phputf8/mbstring/core.php';
		} else {
			require_once './lib/pkp/lib/phputf8/utils/unicode.php';
			require_once './lib/pkp/lib/phputf8/native/core.php';
		}
		return utf8_strtolower($string);
	}

	/**
	 * @see http://ca.php.net/manual/en/function.strtoupper.php
	 * @param $string string Input string
	 * @return string Upper case version of input string
	 */
	static function strtoupper($string) {
		if (defined('ENABLE_MBSTRING')) {
			require_once './lib/pkp/lib/phputf8/mbstring/core.php';
		} else {
			require_once './lib/pkp/lib/phputf8/utils/unicode.php';
			require_once './lib/pkp/lib/phputf8/native/core.php';
		}
		return utf8_strtoupper($string);
	}

	/**
	 * @see http://ca.php.net/manual/en/function.ucfirst.php
	 * @param $string string Input string
	 * @return string ucfirst version of input string
	 */
	static function ucfirst($string) {
		if (defined('ENABLE_MBSTRING')) {
			require_once './lib/pkp/lib/phputf8/mbstring/core.php';
			require_once './lib/pkp/lib/phputf8/ucfirst.php';
		} else {
			require_once './lib/pkp/lib/phputf8/utils/unicode.php';
			require_once './lib/pkp/lib/phputf8/native/core.php';
			require_once './lib/pkp/lib/phputf8/ucfirst.php';
		}
		return utf8_ucfirst($string);
	}

	/**
	 * @see http://ca.php.net/manual/en/function.substr_count.php
	 * @param $haystack string Input string to search
	 * @param $needle string String to search within $haystack for
	 * @return int Count of number of times $needle appeared in $haystack
	 */
	static function substr_count($haystack, $needle) {
		if (defined('ENABLE_MBSTRING')) {
			return mb_substr_count($haystack, $needle); // Requires PHP >= 4.3.0
		} else {
			return substr_count($haystack, $needle);
		}
	}

	/**
	 * @see http://ca.php.net/manual/en/function.encode_mime_header.php
	 * @param $string string Input MIME header to encode.
	 * @return string Encoded MIME header.
	 */
	static function encode_mime_header($string) {
		if (defined('ENABLE_MBSTRING')) {
			return mb_encode_mimeheader($string, mb_internal_encoding(), 'B', MAIL_EOL);
		}  else {
			return $string;
		}
	}

	//
	// Wrappers for PCRE-compatible regular expression routines.
	// See the php.net documentation for usage.
	//

	/**
	 * @see http://ca.php.net/manual/en/function.regexp_quote.php
	 * @param $string string String to quote
	 * @param $delimiter string Delimiter for regular expression
	 * @return string Quoted equivalent of $string
	 */
	static function regexp_quote($string, $delimiter = '/') {
		return preg_quote($string, $delimiter);
	}

	/**
	 * @see http://ca.php.net/manual/en/function.regexp_grep.php
	 * @param $pattern string Regular expression
	 * @param $input string Input string
	 * @return array
	 */
	static function regexp_grep($pattern, $input) {
		if (PCRE_UTF8 && !self::utf8_compliant($input)) $input = self::utf8_bad_strip($input);
		return preg_grep($pattern . PCRE_UTF8, $input);
	}

	/**
	 * @see http://ca.php.net/manual/en/function.regexp_match.php
	 * @param $pattern string Regular expression
	 * @param $subject string String to apply regular expression to
	 * @return int
	 */
	static function regexp_match($pattern, $subject) {
		if (PCRE_UTF8 && !self::utf8_compliant($subject)) $subject = self::utf8_bad_strip($subject);
		return preg_match($pattern . PCRE_UTF8, $subject);
	}

	/**
	 * @see http://ca.php.net/manual/en/function.regexp_match_get.php
	 * @param $pattern string Regular expression
	 * @param $subject string String to apply regular expression to
	 * @param $matches array Reference to receive matches
	 * @return int|boolean Returns 1 if the pattern matches given subject, 0 if it does not, or FALSE if an error occurred.
	 */
	static function regexp_match_get($pattern, $subject, &$matches) {
		// NOTE: This function was created since PHP < 5.x does not support optional reference parameters
		if (PCRE_UTF8 && !self::utf8_compliant($subject)) $subject = self::utf8_bad_strip($subject);
		return preg_match($pattern . PCRE_UTF8, $subject, $matches);
	}

	/**
	 * @see http://ca.php.net/manual/en/function.regexp_match_all.php
	 * @param $pattern string Regular expression
	 * @param $subject string String to apply regular expression to
	 * @param $matches array Reference to receive matches
	 * @return int|boolean Returns number of full matches of given subject, or FALSE if an error occurred.
	 */
	static function regexp_match_all($pattern, $subject, &$matches) {
		if (PCRE_UTF8 && !self::utf8_compliant($subject)) $subject = self::utf8_bad_strip($subject);
		return preg_match_all($pattern . PCRE_UTF8, $subject, $matches);
	}

	/**
	 * @see http://ca.php.net/manual/en/function.regexp_replace.php
	 * @param $pattern string Regular expression
	 * @param $replacement string String to replace matches in $subject with
	 * @param $subject string String to apply regular expression to
	 * @param $limit int Number of replacements to perform, maximum, or -1 for no limit.
	 * @return mixed
	 */
	static function regexp_replace($pattern, $replacement, $subject, $limit = -1) {
		if (PCRE_UTF8 && !self::utf8_compliant($subject)) $subject = self::utf8_bad_strip($subject);
		return preg_replace($pattern . PCRE_UTF8, $replacement, $subject, $limit);
	}

	/**
	 * @see http://ca.php.net/manual/en/function.regexp_replace_callback.php
	 * @param $pattern string Regular expression
	 * @param $callback callback PHP callback to generate content to replace matches with
	 * @param $subject string String to apply regular expression to
	 * @param $limit int Number of replacements to perform, maximum, or -1 for no limit.
	 * @return mixed
	 */
	static function regexp_replace_callback($pattern, $callback, $subject, $limit = -1) {
		if (PCRE_UTF8 && !self::utf8_compliant($subject)) $subject = self::utf8_bad_strip($subject);
		return preg_replace_callback($pattern . PCRE_UTF8, $callback, $subject, $limit);
	}

	/**
	 * @see http://ca.php.net/manual/en/function.regexp_split.php
	 * @param $pattern string Regular expression
	 * @param $subject string String to apply regular expression to
	 * @param $limit int Number of times to match; -1 for unlimited
	 * @return array Resulting string segments
	 */
	static function regexp_split($pattern, $subject, $limit = -1) {
		if (PCRE_UTF8 && !self::utf8_compliant($subject)) $subject = self::utf8_bad_strip($subject);
		return preg_split($pattern . PCRE_UTF8, $subject, $limit);
	}

	/**
	 * @see http://ca.php.net/manual/en/function.mime_content_type.php
	 * @param $filename string Filename to test.
	 * @param $suggestedExtension string Suggested file extension (used for common misconfigurations)
	 * @return string Detected MIME type
	 */
	static function mime_content_type($filename, $suggestedExtension = '') {
		$result = null;

		if (function_exists('finfo_open')) {
			$fi =& Registry::get('fileInfo', true, null);
			if ($fi === null) {
				$fi = finfo_open(FILEINFO_MIME, Config::getVar('finfo', 'mime_database_path'));
			}
			if ($fi !== false) {
				$result = strtok(finfo_file($fi, $filename), ' ;');
			}
		}

		if (!$result && function_exists('mime_content_type')) {
			$result = mime_content_type($filename);
			// mime_content_type appears to return a charset
			// (erroneously?) in recent versions of PHP5
			if (($i = strpos($result, ';')) !== false) {
				$result = trim(substr($result, 0, $i));
			}
		}

		if (!$result) {
			// Fall back on an external "file" tool
			$f = escapeshellarg($filename);
			$result = trim(`file --brief --mime $f`);
			// Make sure we just return the mime type.
			if (($i = strpos($result, ';')) !== false) {
				$result = trim(substr($result, 0, $i));
			}
		}

		// Check ambiguous mimetypes against extension
		$ext = array_pop(explode('.',$filename));
		if ($suggestedExtension) {
			$ext = $suggestedExtension;
		}
		// SUGGESTED_EXTENSION:DETECTED_MIME_TYPE => OVERRIDE_MIME_TYPE
		$ambiguities = array(
			'css:text/x-c' => 'text/css',
			'css:text/plain' => 'text/css',
			'xlsx:application/zip' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'xltx:application/zip' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
			'potx:application/zip' => 'application/vnd.openxmlformats-officedocument.presentationml.template',
			'ppsx:application/zip' => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
			'pptx:application/zip' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
			'sldx:application/zip' => 'application/vnd.openxmlformats-officedocument.presentationml.slide',
			'docx:application/zip' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'dotx:application/zip' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
		);
		if (isset($ambiguities[strtolower($ext.':'.$result)])) {
			$result = $ambiguities[strtolower($ext.':'.$result)];
		}

		return $result;
	}

	/**
	 * Strip unsafe HTML from the input text. Covers XSS attacks like scripts,
	 * onclick(...) attributes, javascript: urls, and special characters.
	 * @param $input string input string
	 * @return string
	 */
	static function stripUnsafeHtml($input) {
		require_once('lib/pkp/lib/vendor/ezyang/htmlpurifier/library/HTMLPurifier.path.php');
		require_once('HTMLPurifier.includes.php');
		static $purifier;
		if (!isset($purifier)) {
			$config = HTMLPurifier_Config::createDefault();
			$config->set('Core.Encoding', Config::getVar('i18n', 'client_charset'));
			$config->set('HTML.Doctype', 'HTML 4.01 Transitional');
			$config->set('HTML.Allowed', Config::getVar('security', 'allowed_html'));
			$config->set('Cache.SerializerPath', 'cache');
			$purifier = new HTMLPurifier($config);
		}
		return $purifier->purify($input);
	}

	/**
	 * Convert limited HTML into a string.
	 * @param $html string
	 * @return string
	 */
	static function html2text($html) {
		$html = self::regexp_replace('/<[\/]?p>/', "\n", $html);
		$html = self::regexp_replace('/<li>/', '&bull; ', $html);
		$html = self::regexp_replace('/<\/li>/', "\n", $html);
		$html = self::regexp_replace('/<br[ ]?[\/]?>/', "\n", $html);
		$html = html_entity_decode(strip_tags($html), ENT_COMPAT, 'UTF-8');
		return $html;
	}

	//
	// Wrappers for UTF-8 validation routines
	// See the phputf8 documentation for usage.
	//

	/**
	 * Detect whether a string contains non-ascii multibyte sequences in the UTF-8 range
	 * @param $str string input string
	 * @return boolean
	 */
	static function utf8_is_valid($str) {
		require_once './lib/pkp/lib/phputf8/utils/validation.php';
		return utf8_is_valid($str);
	}

	/**
	 * Tests whether a string complies as UTF-8; faster and less strict than utf8_is_valid
	 * see lib/phputf8/utils/validation.php for more details
	 * @param $str string input string
	 * @return boolean
	 */
	static function utf8_compliant($str) {
		require_once './lib/pkp/lib/phputf8/utils/validation.php';
		return utf8_compliant($str);
	}

	/**
	 * Locates the first bad byte in a UTF-8 string returning it's byte index in the string
	 * @param $str string input string
	 * @return string
	 */
	static function utf8_bad_find($str) {
		require_once './lib/pkp/lib/phputf8/utils/bad.php';
		return utf8_bad_find($str);
	}

	/**
	 * Strips out any bad bytes from a UTF-8 string and returns the rest
	 * @param $str string input string
	 * @return string
	 */
	static function utf8_bad_strip($str) {
		require_once './lib/pkp/lib/phputf8/utils/bad.php';
		return utf8_bad_strip($str);
	}

	/**
	 * Replace bad bytes with an alternative character - ASCII character
	 * @param $str string input string
	 * @param $replace string optional
	 * @return string
	 */
	static function utf8_bad_replace($str, $replace = '?') {
		require_once './lib/pkp/lib/phputf8/utils/bad.php';
		return utf8_bad_replace($str, $replace);
	}

	/**
	 * Replace bad bytes with an alternative character - ASCII character
	 * @param $str string input string
	 * @return string
	 */
	static function utf8_strip_ascii_ctrl($str) {
		require_once './lib/pkp/lib/phputf8/utils/ascii.php';
		return utf8_strip_ascii_ctrl($str);
	}

	/**
	 * Normalize a string in an unknown (non-UTF8) encoding into a valid UTF-8 sequence
	 * @param $str string input string
	 * @return string
	 */
	static function utf8_normalize($str) {
		import('lib.pkp.classes.core.Transcoder');

		if (self::hasMBString()) {
			// NB: CP-1252 often segfaults; we've left it out here but it will detect as 'ISO-8859-1'
			$mb_encoding_order = 'UTF-8, UTF-7, ASCII, ISO-8859-1, EUC-JP, SJIS, eucJP-win, SJIS-win, JIS, ISO-2022-JP';

			$detected_encoding = mb_detect_encoding($str, $mb_encoding_order, false);

		} elseif (function_exists('iconv') && strlen(iconv('CP1252', 'UTF-8', $str)) != strlen(iconv('ISO-8859-1', 'UTF-8', $str))) {
			// use iconv to detect CP-1252, assuming default ISO-8859-1
			$detected_encoding = 'CP1252';
		} else {
			// assume ISO-8859-1, PHP default
			$detected_encoding = 'ISO-8859-1';
		}

		// transcode CP-1252/ISO-8859-1 into HTML entities; this works because CP-1252 is mapped onto ISO-8859-1
		if ('ISO-8859-1' == $detected_encoding || 'CP1252' == $detected_encoding) {
			$trans = new Transcoder('CP1252', 'HTML-ENTITIES');
			$str = $trans->trans($str);
		}

		// transcode from detected encoding to to UTF-8
		$trans = new Transcoder($detected_encoding, 'UTF-8');
		$str = $trans->trans($str);

		return $str;
	}

	/**
	 * US-ASCII transliterations of Unicode text
	 * @param $str string input string
	 * @return string
	 */
	static function utf8_to_ascii($str) {
		require_once('./lib/pkp/lib/phputf8/utf8_to_ascii.php');
		return utf8_to_ascii($str);
	}

	/**
	 * Return an associative array of named->numeric HTML entities
	 * Required to support HTML functions without objects in PHP4/PHP5
	 * From php.net: function.get-html-translation-table.php
	 * @return string
	 */
	static function getHTMLEntities () {
		// define the conversion table
		$html_entities = array(
			"&Aacute;" => "&#193;",	"&aacute;" => "&#225;",	"&Acirc;" => "&#194;",
			"&acirc;" => "&#226;",	"&acute;" => "&#180;",	"&AElig;" => "&#198;",
			"&aelig;" => "&#230;",	"&Agrave;" => "&#192;",	"&agrave;" => "&#224;",
			"&alefsym;" => "&#8501;","&Alpha;" => "&#913;",	"&alpha;" => "&#945;",
			"&amp;" => "&#38;",	"&and;" => "&#8743;",	"&ang;" => "&#8736;",
			"&apos;" => "&#39;",	"&Aring;" => "&#197;",	"&aring;" => "&#229;",
			"&asymp;" => "&#8776;",	"&Atilde;" => "&#195;",	"&atilde;" => "&#227;",
			"&Auml;" => "&#196;",	"&auml;" => "&#228;",	"&bdquo;" => "&#8222;",
			"&Beta;" => "&#914;",	"&beta;" => "&#946;",	"&brvbar;" => "&#166;",
			"&bull;" => "&#8226;",	"&cap;" => "&#8745;",	"&Ccedil;" => "&#199;",
			"&ccedil;" => "&#231;",	"&cedil;" => "&#184;",	"&cent;" => "&#162;",
			"&Chi;" => "&#935;",	"&chi;" => "&#967;",	"&circ;" => "&#94;",
			"&clubs;" => "&#9827;",	"&cong;" => "&#8773;",	"&copy;" => "&#169;",
			"&crarr;" => "&#8629;",	"&cup;" => "&#8746;",	"&curren;" => "&#164;",
			"&dagger;" => "&#8224;","&Dagger;" => "&#8225;", "&darr;" => "&#8595;",
			"&dArr;" => "&#8659;",	"&deg;" => "&#176;",	"&Delta;" => "&#916;",
			"&delta;" => "&#948;",	"&diams;" => "&#9830;",	"&divide;" => "&#247;",
			"&Eacute;" => "&#201;",	"&eacute;" => "&#233;",	"&Ecirc;" => "&#202;",
			"&ecirc;" => "&#234;",	"&Egrave;" => "&#200;",	"&egrave;" => "&#232;",
			"&empty;" => "&#8709;",	"&emsp;" => "&#8195;",	"&ensp;" => "&#8194;",
			"&Epsilon;" => "&#917;","&epsilon;" => "&#949;","&equiv;" => "&#8801;",
			"&Eta;" => "&#919;",	"&eta;" => "&#951;",	"&ETH;" => "&#208;",
			"&eth;" => "&#240;",	"&Euml;" => "&#203;",	"&euml;" => "&#235;",
			"&euro;" => "&#8364;",	"&exist;" => "&#8707;",	"&fnof;" => "&#402;",
			"&forall;" => "&#8704;","&frac12;" => "&#189;",	"&frac14;" => "&#188;",
			"&frac34;" => "&#190;",	"&frasl;" => "&#8260;",	"&Gamma;" => "&#915;",
			"&gamma;" => "&#947;",	"&ge;" => "&#8805;",	"&gt;" => "&#62;",
			"&harr;" => "&#8596;",	"&hArr;" => "&#8660;",	"&hearts;" => "&#9829;",
			"&hellip;" => "&#8230;","&Iacute;" => "&#205;",	"&iacute;" => "&#237;",
			"&Icirc;" => "&#206;",	"&icirc;" => "&#238;",	"&iexcl;" => "&#161;",
			"&Igrave;" => "&#204;",	"&igrave;" => "&#236;",	"&image;" => "&#8465;",
			"&infin;" => "&#8734;",	"&int;" => "&#8747;",	"&Iota;" => "&#921;",
			"&iota;" => "&#953;",	"&iquest;" => "&#191;",	"&isin;" => "&#8712;",
			"&Iuml;" => "&#207;",	"&iuml;" => "&#239;",	"&Kappa;" => "&#922;",
			"&kappa;" => "&#954;",	"&Lambda;" => "&#923;",	"&lambda;" => "&#955;",
			"&lang;" => "&#9001;",	"&laquo;" => "&#171;",	"&larr;" => "&#8592;",
			"&lArr;" => "&#8656;",	"&lceil;" => "&#8968;",
			"&ldquo;" => "&#8220;",	"&le;" => "&#8804;",	"&lfloor;" => "&#8970;",
			"&lowast;" => "&#8727;","&loz;" => "&#9674;",	"&lrm;" => "&#8206;",
			"&lsaquo;" => "&#8249;","&lsquo;" => "&#8216;",	"&lt;" => "&#60;",
			"&macr;" => "&#175;",	"&mdash;" => "&#8212;",	"&micro;" => "&#181;",
			"&middot;" => "&#183;",	"&minus;" => "&#45;",	"&Mu;" => "&#924;",
			"&mu;" => "&#956;",	"&nabla;" => "&#8711;",	"&nbsp;" => "&#160;",
			"&ndash;" => "&#8211;",	"&ne;" => "&#8800;",	"&ni;" => "&#8715;",
			"&not;" => "&#172;",	"&notin;" => "&#8713;",	"&nsub;" => "&#8836;",
			"&Ntilde;" => "&#209;",	"&ntilde;" => "&#241;",	"&Nu;" => "&#925;",
			"&nu;" => "&#957;",	"&Oacute;" => "&#211;",	"&oacute;" => "&#243;",
			"&Ocirc;" => "&#212;",	"&ocirc;" => "&#244;",	"&OElig;" => "&#338;",
			"&oelig;" => "&#339;",	"&Ograve;" => "&#210;",	"&ograve;" => "&#242;",
			"&oline;" => "&#8254;",	"&Omega;" => "&#937;",	"&omega;" => "&#969;",
			"&Omicron;" => "&#927;","&omicron;" => "&#959;","&oplus;" => "&#8853;",
			"&or;" => "&#8744;",	"&ordf;" => "&#170;",	"&ordm;" => "&#186;",
			"&Oslash;" => "&#216;",	"&oslash;" => "&#248;",	"&Otilde;" => "&#213;",
			"&otilde;" => "&#245;",	"&otimes;" => "&#8855;","&Ouml;" => "&#214;",
			"&ouml;" => "&#246;",	"&para;" => "&#182;",	"&part;" => "&#8706;",
			"&permil;" => "&#8240;","&perp;" => "&#8869;",	"&Phi;" => "&#934;",
			"&phi;" => "&#966;",	"&Pi;" => "&#928;",	"&pi;" => "&#960;",
			"&piv;" => "&#982;",	"&plusmn;" => "&#177;",	"&pound;" => "&#163;",
			"&prime;" => "&#8242;",	"&Prime;" => "&#8243;",	"&prod;" => "&#8719;",
			"&prop;" => "&#8733;",	"&Psi;" => "&#936;",	"&psi;" => "&#968;",
			"&quot;" => "&#34;",	"&radic;" => "&#8730;",	"&rang;" => "&#9002;",
			"&raquo;" => "&#187;",	"&rarr;" => "&#8594;",	"&rArr;" => "&#8658;",
			"&rceil;" => "&#8969;",	"&rdquo;" => "&#8221;",	"&real;" => "&#8476;",
			"&reg;" => "&#174;",	"&rfloor;" => "&#8971;","&Rho;" => "&#929;",
			"&rho;" => "&#961;",	"&rlm;" => "&#8207;",	"&rsaquo;" => "&#8250;",
			"&rsquo;" => "&#8217;",	"&sbquo;" => "&#8218;",	"&Scaron;" => "&#352;",
			"&scaron;" => "&#353;",	"&sdot;" => "&#8901;",	"&sect;" => "&#167;",
			"&shy;" => "&#173;",	"&Sigma;" => "&#931;",	"&sigma;" => "&#963;",
			"&sigmaf;" => "&#962;",	"&sim;" => "&#8764;",	"&spades;" => "&#9824;",
			"&sub;" => "&#8834;",	"&sube;" => "&#8838;",	"&sum;" => "&#8721;",
			"&sup1;" => "&#185;",	"&sup2;" => "&#178;",	"&sup3;" => "&#179;",
			"&sup;" => "&#8835;",	"&supe;" => "&#8839;",	"&szlig;" => "&#223;",
			"&Tau;" => "&#932;",	"&tau;" => "&#964;",	"&there4;" => "&#8756;",
			"&Theta;" => "&#920;",	"&theta;" => "&#952;",	"&thetasym;" => "&#977;",
			"&thinsp;" => "&#8201;","&THORN;" => "&#222;",	"&thorn;" => "&#254;",
			"&tilde;" => "&#126;",	"&times;" => "&#215;",	"&trade;" => "&#8482;",
			"&Uacute;" => "&#218;",	"&uacute;" => "&#250;",	"&uarr;" => "&#8593;",
			"&uArr;" => "&#8657;",	"&Ucirc;" => "&#219;",	"&ucirc;" => "&#251;",
			"&Ugrave;" => "&#217;",	"&ugrave;" => "&#249;",	"&uml;" => "&#168;",
			"&upsih;" => "&#978;",	"&Upsilon;" => "&#933;","&upsilon;" => "&#965;",
			"&Uuml;" => "&#220;",	"&uuml;" => "&#252;",	"&weierp;" => "&#8472;",
			"&Xi;" => "&#926;",	"&xi;" => "&#958;",	"&Yacute;" => "&#221;",
			"&yacute;" => "&#253;",	"&yen;" => "&#165;",	"&yuml;" => "&#255;",
			"&Yuml;" => "&#376;",	"&Zeta;" => "&#918;",	"&zeta;" => "&#950;",
			"&zwj;" => "&#8205;",	"&zwnj;" => "&#8204;"
		);

		return $html_entities;
	}

	/**
	 * Trim punctuation from a string
	 * @param $string string input string
	 * @return string the trimmed string
	 */
	static function trimPunctuation($string) {
		return trim($string, ' ,.;:!?&()[]\\/');
	}

	/**
	 * Convert a string to proper title case
	 * @param $title string
	 * @return string
	 */
	static function titleCase($title) {
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_COMMON);
		$smallWords = explode(' ', __('common.titleSmallWords'));

		$words = explode(' ', $title);
		foreach ($words as $key => $word) {
			if ($key == 0 or !in_array(self::strtolower($word), $smallWords)) {
				$words[$key] = ucfirst(self::strtolower($word));
			} else {
				$words[$key] = self::strtolower($word);
			}
		}

		$newTitle = implode(' ', $words);
		return $newTitle;
	}

	/**
	 * Joins two title string fragments (in $fields) either with a
	 * space or a colon.
	 * @param $fields array
	 * @return string the joined string
	 */
	static function concatTitleFields($fields) {
		// Set the characters that will avoid the use of
		// a semicolon between title and subtitle.
		$avoidColonChars = array('?', '!', '/', '&');

		// if the first field ends in a character in $avoidColonChars,
		// concat with a space, otherwise use a colon.
		// Check for any of these characters in
		// the last position of current full title value.
		if (in_array(substr($fields[0], -1, 1), $avoidColonChars)) {
			$fullTitle = join(' ', $fields);
		} else {
			$fullTitle = join(': ', $fields);
		}

		return $fullTitle;
	}

	/**
	 * Iterate over an array of delimiters and see whether
	 * it exists in the given input string. If so, then use
	 * it to explode the string into an array.
	 * @param $delimiters array
	 * @param $input string
	 * @return array
	 */
	static function iterativeExplode($delimiters, $input) {
		// Run through the delimiters and try them out
		// one by one.
		foreach($delimiters as $delimiter) {
			if (strstr($input, $delimiter) !== false) {
				return explode($delimiter, $input);
			}
		}

		// If none of the delimiters works then return
		// the original string as an array.
		return (array($input));
	}



	/**
	 * Transform "handler-class" to "HandlerClass"
	 * and "my-op" to "myOp".
	 * @param $string input string
	 * @param $type which kind of camel case?
	 * @return string the string in camel case
	 */
	static function camelize($string, $type = CAMEL_CASE_HEAD_UP) {
		assert($type == CAMEL_CASE_HEAD_UP || $type == CAMEL_CASE_HEAD_DOWN);

		// Transform "handler-class" to "HandlerClass" and "my-op" to "MyOp"
		$string = str_replace(' ', '', ucwords(str_replace('-', ' ', $string)));

		// Transform "MyOp" to "myOp"
		if ($type == CAMEL_CASE_HEAD_DOWN) {
			// lcfirst() is PHP>5.3, so use workaround
			$string = strtolower(substr($string, 0, 1)).substr($string, 1);
		}

		return $string;
	}

	/**
	 * Transform "HandlerClass" to "handler-class"
	 * and "myOp" to "my-op".
	 * @param $string
	 * @return string
	 */
	static function uncamelize($string) {
		assert(!empty($string));

		// Transform "myOp" to "MyOp"
		$string = ucfirst($string);

		// Insert hyphens between words and return the string in lowercase
		$words = array();
		self::regexp_match_all('/[A-Z][a-z0-9]*/', $string, $words);
		assert(isset($words[0]) && !empty($words[0]) && strlen(implode('', $words[0])) == strlen($string));
		return strtolower(implode('-', $words[0]));
	}

	/**
	 * Calculate the differences between two strings and
	 * produce an array with three types of entries: added
	 * substrings, deleted substrings and unchanged substrings.
	 *
	 * The calculation is optimized to identify the common
	 * largest substring.
	 *
	 * The return value is an array of the following format:
	 *
	 * array(
	 *   array( diff-type => substring ),
	 *   array(...)
	 * )
	 *
	 * whereby diff-type can be one of:
	 *   -1 = deletion
	 *    0 = common substring
	 *    1 = addition
	 *
	 * @param $originalString string
	 * @param $editedString string
	 * @return array
	 */
	static function diff($originalString, $editedString) {
		// Split strings into character arrays (multi-byte compatible).
		foreach(array('originalStringCharacters' => $originalString, 'editedStringCharacters' => $editedString) as $characterArrayName => $string) {
			${$characterArrayName} = array();
			self::regexp_match_all('/./', $string, ${$characterArrayName});
			if (isset(${$characterArrayName}[0])) {
				${$characterArrayName} = ${$characterArrayName}[0];
			}
		}

		// Determine the length of the strings.
		$originalStringLength = count($originalStringCharacters);
		$editedStringLength = count($editedStringCharacters);

		// Is there anything to compare?
		if ($originalStringLength == 0 && $editedStringLength == 0) return array();

		// Is the original string empty?
		if ($originalStringLength == 0) {
			// Return the edited string as addition.
			return array(array(1 => $editedString));
		}

		// Is the edited string empty?
		if ($editedStringLength == 0) {
			// Return the original string as deletion.
			return array(array(-1 => $originalString));
		}

		// Initialize the local indices:
		// 1) Create a character index for the edited string.
		$characterIndex = array();
		for($characterPosition = 0; $characterPosition < $editedStringLength; $characterPosition++) {
			$characterIndex[$editedStringCharacters[$characterPosition]][] = $characterPosition;
		}
		// 2) Initialize the substring and the length index.
		$substringIndex = $lengthIndex = array();

		// Iterate over the original string to identify
		// the largest common string.
		for($originalPosition = 0; $originalPosition < $originalStringLength; $originalPosition++) {
			// Find all occurrences of the original character
			// in the target string.
			$comparedCharacter = $originalStringCharacters[$originalPosition];

			// Do we have a commonality between the original string
			// and the edited string?
			if (isset($characterIndex[$comparedCharacter])) {
				// Loop over all commonalities.
				foreach($characterIndex[$comparedCharacter] as $editedPosition) {
					// Calculate the current and the preceding position
					// ids for indexation.
					$currentPosition = $originalPosition . '-' . $editedPosition;
					$previousPosition = ($originalPosition-1) . '-' . ($editedPosition-1);

					// Does the occurrence in the target string continue
					// an existing common substring or does it start
					// a new one?
					if (isset($substringIndex[$previousPosition])) {
						// This is a continuation of an existing common
						// substring...
						$newSubstring = $substringIndex[$previousPosition].$comparedCharacter;
						$newSubstringLength = self::strlen($newSubstring);

						// Move the substring in the substring index.
						$substringIndex[$currentPosition] = $newSubstring;
						unset($substringIndex[$previousPosition]);

						// Move the substring in the length index.
						$lengthIndex[$newSubstringLength][$currentPosition] = $newSubstring;
						unset($lengthIndex[$newSubstringLength - 1][$previousPosition]);
					} else {
						// Start a new common substring...
						// Add the substring to the substring index.
						$substringIndex[$currentPosition] = $comparedCharacter;

						// Add the substring to the length index.
						$lengthIndex[1][$currentPosition] = $comparedCharacter;
					}
				}
			}
		}

		// If we have no commonalities at all then mark the original
		// string as deleted and the edited string as added and
		// return.
		if (empty($lengthIndex)) {
			return array(
				array( -1 => $originalString ),
				array( 1 => $editedString )
			);
		}

		// Pop the largest common substrings from the length index.
		end($lengthIndex);
		$largestSubstringLength = key($lengthIndex);

		// Take the first common substring if we have more than
		// one substring with the same length.
		// FIXME: Find a better heuristic for this decision.
		reset($lengthIndex[$largestSubstringLength]);
		$largestSubstringPosition = key($lengthIndex[$largestSubstringLength]);
		list($largestSubstringEndOriginal, $largestSubstringEndEdited) = explode('-', $largestSubstringPosition);
		$largestSubstring = $lengthIndex[$largestSubstringLength][$largestSubstringPosition];

		// Add the largest common substring to the result set
		$diffResult = array(array( 0 => $largestSubstring ));

		// Prepend the diff of the substrings before the common substring
		// to the result diff (by recursion).
		$precedingSubstringOriginal = self::substr($originalString, 0, $largestSubstringEndOriginal-$largestSubstringLength+1);
		$precedingSubstringEdited = self::substr($editedString, 0, $largestSubstringEndEdited-$largestSubstringLength+1);
		$diffResult = array_merge(self::diff($precedingSubstringOriginal, $precedingSubstringEdited), $diffResult);

		// Append the diff of the substrings after thr common substring
		// to the result diff (by recursion).
		$succeedingSubstringOriginal = self::substr($originalString, $largestSubstringEndOriginal+1);
		$succeedingSubstringEdited = self::substr($editedString, $largestSubstringEndEdited+1);
		$diffResult = array_merge($diffResult, self::diff($succeedingSubstringOriginal, $succeedingSubstringEdited));

		// Return the array representing the diff.
		return $diffResult;
	}

	/**
	 * Get a letter $steps places after 'A'
	 * @param $steps int
	 * @return string Letter
	 */
	static function enumerateAlphabetically($steps) {
		return chr(ord('A') + $steps);
	}

	/**
	 * Create a new UUID (version 4)
	 * @return string
	 */
	function generateUUID() {
		mt_srand((double)microtime()*10000);
		$charid = strtoupper(md5(uniqid(rand(), true)));
		$hyphen = '-';
		$uuid = substr($charid, 0, 8).$hyphen
				.substr($charid, 8, 4).$hyphen
				.'4'.substr($charid,13, 3).$hyphen
				.strtoupper(dechex(hexdec(ord(substr($charid,16,1))) % 4 + 8)).substr($charid,17, 3).$hyphen
				.substr($charid,20,12);
		return $uuid;
	}

	/**
	 * Matches each symbol of PHP strftime format string
	 * to jQuery Datepicker widget date format. 
	 * @param $phpFormat string
	 * @return string
	 */
	function dateformatPHP2JQueryDatepicker($phpFormat) {
		$symbols = array(
			// Day
			'a' => 'D',	// date() format 'D'
			'A' => 'DD',	// date() format 'DD'
			'd' => 'dd',	// date() format 'd'
			'e' => 'd',	// date() format 'j'
			'j' => 'oo',	// date() format none
			'u' => '',		// date() format 'N'
			'w' => '',		// date() format 'w'

			// Week
			'U' => '',		// date() format none
			'V' => '',		// date() format none
			'W' => '',		// date() format 'W'

			// Month
			'b' => 'M',	// date() format 'M'
			'h' => 'M',	// date() format 'M'
			'B' => 'MM',	// date() format 'F'
			'm' => 'mm',	// date() format 'm'

			// Year
			'C' => '',		// date() format none
			'g' => 'y',	// date() format none
			'G' => 'yy',	// date() format 'o'
			'y' => 'y',	// date() format 'y'
			'Y' => 'yy',	// date() format 'Y'

			// Time
			'H' => '',		// date() format 'H'
			'k' => '',		// date() format none
			'I' => '',		// date() format 'h'
			'l' => '',		// date() format 'g'
			'P' => '',		// date() format 'a'
			'p' => '',		// date() format 'A'
			'M' => '',		// date() format 'i'
			'S' => '',		// date() format 's'
			's' => '',		// date() format 'u'

			// Timezone
			'z' => '',		// date() format 'O'
			'Z' => '',		// date() format 'T'

			// Full Date/Time
			'r' => '',		// date() format none
			'R' => '',		// date() format none
			'X' => '',		// date() format none
			'D' => '',		// date() format none
			'F' => '',		// date() format none
			'x' => '',		// date() format none
			'c' => '',		// date() format none

			// Other
			'%' => ''
		);

		$datepickerFormat = "";
		$escaping = false;

		for ($i = 0; $i < strlen($phpFormat); $i++) {
			$char = $phpFormat[$i];
			if($char === '\\') {
				$i++;
				$datepickerFormat .= $escaping ? $phpFormat[$i] : '\'' . $phpFormat[$i];

				$escaping = true;
			} else {
				if($escaping) {
					$datepickerFormat .= "'";
					$escaping = false;
				}

				$datepickerFormat .= isset($symbols[$char]) ? $symbols[$char] : $char;

			}
		}

		return $datepickerFormat;
	}
}

?>
