<?php

/**
 * @file SearchHTMLParser.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package search
 * @class SearchHTMLParser
 *
 * Class to extract text from an HTML file.
 *
 * $Id$
 */

import('search.SearchFileParser');

class SearchHTMLParser extends SearchFileParser {

	// From php.net/html_entity_decode
	function unhtmlentities($string) {
		// replace numeric entities
		$string = preg_replace('~&#x([0-9a-f]+);~ei', 'chr(hexdec("\\1"))', $string);
		$string = preg_replace('~&#([0-9]+);~e', 'chr(\\1)', $string);
		// replace literal entities
		$trans_tbl = get_html_translation_table(HTML_ENTITIES);
		$trans_tbl = array_flip($trans_tbl);
		return strtr($string, $trans_tbl);
	}


	function doRead() {
		$line = fgetss($this->fp, 4096);
		$line = str_replace('&nbsp;', ' ', $line);
		if (function_exists('html_entity_decode')) {
			$line = html_entity_decode($line);
		} else {
			$line = $this->unhtmlentities($line);
		}
		return $line;
	}
	
}

?>
