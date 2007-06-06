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
		// detect existence of encoding conversion libraries
		$mbstring = function_exists('mb_convert_encoding');
		$iconv = function_exists('iconv');

		// ===	special cases for HTML entities to handle various PHP platforms
		// 'HTML-ENTITIES' is not a valid encoding for iconv, so transcode manually

		if ($this->toEncoding == 'HTML-ENTITIES' && !$mbstring) {

			if ( strtoupper($this->fromEncoding) == 'UTF-8' ) {
				return String::utf2html($string);		// NB: this will return all numeric entities
			} else {
				// NB: old PHP versions may have issues with htmlentities()
				return htmlentities($string, ENT_COMPAT, $this->fromEncoding);
			}

		} elseif ($this->fromEncoding == 'HTML-ENTITIES' && !$mbstring) {

			if ( strtoupper($this->toEncoding) == 'UTF-8' ) {
				// convert named entities to numeric entities
				$string = strtr($string, String::getHTMLEntities());

				// some platforms (PHP 4.3.x, 5.1? ) have problems displaying UTF-8 characters
				// transliterate characters instead of transcoding back from HTML entities
				// TODO: determine how to detect these platforms (OS/webserver?)
//				$string = String::utf2ascii($string);

				// use PCRE-aware replace function to replace numeric entities
				$string = String::regexp_replace('~&#x([0-9a-f]+);~ei', 'String::code2utf(hexdec("\\1"))', $string);
				$string = String::regexp_replace('~&#([0-9]+);~e', 'String::code2utf(\\1)', $string);

				return $string;

			} else {
				// NB: old PHP versions may have issues with html_entity_decode()
				return html_entity_decode($string, ENT_COMPAT, $this->toEncoding);
			}

		// === end special cases for HTML entities

		} elseif ($this->translit == true && $iconv) {
			// use the iconv library to transliterate
			return iconv($this->fromEncoding, $this->toEncoding . '//TRANSLIT', $string);

		} elseif ($mbstring) {
			// use the multibyte library to transcode (no transliteration)
			// this call semantic uses backwards-compatible by-reference for better reliability
			return call_user_func_array('mb_convert_encoding', array(&$string, $this->toEncoding, $this->fromEncoding));

		} elseif ($iconv) {
			// use the iconv library to transcode
			return iconv($this->fromEncoding, $this->toEncoding . '//IGNORE', $string);

		} else {
			// fail gracefully by returning the original string unchanged
			return $string;
		}
	}

}
?>