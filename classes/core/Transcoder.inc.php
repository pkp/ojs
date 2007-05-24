<?php

/**
 * Transcoder.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package db
 *
 * "iconv"-based transcoder. NOTE: iconv may not always be available.
 *
 * $Id$
 */

class Transcoder {
	var $fromEncoding;
	var $toEncoding;
	var $translit = false;

	function Transcoder($fromEncoding, $toEncoding) {
		$this->fromEncoding = $fromEncoding;
		$this->toEncoding = $toEncoding;
	}

	function trans($string) {
		if (function_exists('iconv') && $this->translit == true) {
			// use the iconv library to transliterate
			return iconv($this->fromEncoding, $this->toEncoding . '//TRANSLIT', $string);

		// === special cases for HTML entities (NB: 'HTML-ENTITIES' is not a valid encoding for iconv)

		} elseif ($this->toEncoding == 'HTML-ENTITIES') {
			// NB: old PHP versions may have issues; see http://ca.php.net/manual/en/function.htmlentities.php
			if ( strtoupper($this->fromEncoding) == 'UTF-8' )
				return String::utf2html($string);
			else
				return htmlentities($string, ENT_COMPAT, $this->fromEncoding);

		} elseif ($this->fromEncoding == 'HTML-ENTITIES') {
			// NB: old PHP versions may have issues with html_entity_decode
			// this function is smarter since it uses a bigger table and better encoding

			if ( strtoupper($this->toEncoding) == 'UTF-8' ) {
				// convert named entities to numeric entities
				$string = &String::named2numeric($string);

				// replace numeric entities; NB: PHP 4.3 has problems creating UTF-8 characters
				if ( phpversion() >= '4.4.0' ) {
					$string = preg_replace('~&#x([0-9a-f]+);~ei', 'String::code2utf(hexdec("\\1"))', $string);
					$string = preg_replace('~&#([0-9]+);~e', 'String::code2utf(\\1)', $string);
				}

				return $string;
			} else {
				return html_entity_decode($string, ENT_COMPAT, $this->toEncoding);
			}

		// === end special cases

		} elseif (function_exists('mb_convert_encoding')) {
			// transcode using the multibyte library (no transliteration)
//			return mb_convert_encoding($string, $this->toEncoding, $this->fromEncoding);

			// this call semantic uses backwards-compatible by-reference for better reliability
			return call_user_func_array('mb_convert_encoding', array(&$string, $this->toEncoding, $this->fromEncoding));

		} elseif (function_exists('iconv')) {
			// use the iconv library to transcode
			return iconv($this->fromEncoding, $this->toEncoding . '//IGNORE', $string);

		} else {
			// fail gracefully by returning the original string unchanged
			return $string;
		}
	}

}

?>
