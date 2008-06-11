<?php

/**
 * @file Transcoder.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package db
 * @class Transcoder
 *
 * Multi-class transcoder; uses mbstring and iconv if available, otherwise falls back to built-in classes
 *
 * $Id$
 */

class Transcoder {
	var $fromEncoding;
	var $toEncoding;
	var $translit;

	function Transcoder($fromEncoding, $toEncoding, $translit = false) {
		$this->fromEncoding = $fromEncoding;
		$this->toEncoding = $toEncoding;
		$this->translit = $translit;
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
				if ($string == html_entity_decode($string, ENT_COMPAT, $this->fromEncoding)) {
					return htmlentities($string, ENT_COMPAT, $this->fromEncoding);
				} else {
					return $string;
				}
			}

		} elseif ($this->fromEncoding == 'HTML-ENTITIES' && !$mbstring) {

			if ( strtoupper($this->toEncoding) == 'UTF-8' ) {
				// use built-in transcoding to UTF8
				return String::html2utf($string);

			} else {
				// NB: old PHP versions may have issues with html_entity_decode()
				return html_entity_decode($string, ENT_COMPAT, $this->toEncoding);
			}

		// === end special cases for HTML entities

		} elseif ($this->translit == true && $iconv) {
			// use the iconv library to transliterate
			return iconv($this->fromEncoding, $this->toEncoding . '//TRANSLIT', $string);

		} elseif ($this->translit == true && $this->fromEncoding == "UTF-8" && $this->toEncoding == "ASCII") {
			// transliterate using built-in mapping
			return String::html2utf(String::html2ascii(String::utf2html($string)));

		// === end special cases for transliteration

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