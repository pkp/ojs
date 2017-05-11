<?php

/**
 * @file classes/core/Transcoder.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Transcoder
 * @ingroup db
 *
 * @brief Multi-class transcoder; uses mbstring and iconv if available, otherwise falls back to built-in classes
 */


class Transcoder {
	/** @var string Name of source encoding */
	var $fromEncoding;

	/** @var string Name of target encoding */
	var $toEncoding;

	/** @var boolean Whether or not to transliterate while transcoding */
	var $translit;

	/**
	 * Constructor
	 * @param $fromEncoding string Name of source encoding
	 * @param $toEncoding string Name of target encoding
	 * @param $translit boolean Whether or not to transliterate while transcoding
	 */
	function __construct($fromEncoding, $toEncoding, $translit = false) {
		$this->fromEncoding = $fromEncoding;
		$this->toEncoding = $toEncoding;
		$this->translit = $translit;
	}

	/**
	 * Transcode a string
	 * @param $string string String to transcode
	 * @return string Result of transcoding
	 */
	function trans($string) {
		// detect existence of encoding conversion libraries
		$mbstring = function_exists('mb_convert_encoding');
		$iconv = function_exists('iconv');

		// don't do work unless we have to
		if (strtolower($this->fromEncoding) == strtolower($this->toEncoding)) {
			return $string;
		}

		// 'HTML-ENTITIES' is not a valid encoding for iconv, so transcode manually
		if ($this->toEncoding == 'HTML-ENTITIES' && !$mbstring) {
			// NB: old PHP versions may have issues with htmlentities()
			if (checkPhpVersion('5.2.3')) {
				// don't double encode added in PHP 5.2.3
				return htmlentities($string, ENT_COMPAT, $this->fromEncoding, false);
			} else {
				return htmlentities($string, ENT_COMPAT, $this->fromEncoding);
			}

		} elseif ($this->fromEncoding == 'HTML-ENTITIES' && !$mbstring) {
			return html_entity_decode($string, ENT_COMPAT, $this->toEncoding);
		// Special cases for transliteration ("down-sampling")
		} elseif ($this->translit && $iconv) {
			// use the iconv library to transliterate
			return iconv($this->fromEncoding, $this->toEncoding . '//TRANSLIT', $string);

		} elseif ($this->translit && $this->fromEncoding == "UTF-8" && $this->toEncoding == "ASCII") {
			// use the utf2ascii library
			require_once './lib/pkp/lib/phputf8/utf8_to_ascii.php';
			return utf8_to_ascii($string);

		} elseif ($mbstring) {
			// use the mbstring library to transcode
			return mb_convert_encoding($string, $this->toEncoding, $this->fromEncoding);

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
