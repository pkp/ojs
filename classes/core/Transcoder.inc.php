<?php

/**
 * Transcoder.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
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

	function Transcoder($fromEncoding, $toEncoding) {
		$this->fromEncoding = $fromEncoding;
		$this->toEncoding = $toEncoding;
	}

	function trans($string) {
		if (function_exists('iconv')) {
			// use the iconv library to transliterate
			return iconv($this->fromEncoding, $this->toEncoding . '//TRANSLIT', $string);

		} elseif (function_exists('mb_convert_encoding')) {
			// fall back to using the multibyte library if necessary (no transliteration)
			return mb_convert_encoding($string, $this->toEncoding, $this->fromEncoding);

		} else {
			// fail gracefully by returning the original string unchanged
			return $string;
		}
	}
}

?>
