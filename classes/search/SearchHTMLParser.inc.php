<?php

/**
 * @file SearchHTMLParser.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
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
import('core.String');

class SearchHTMLParser extends SearchFileParser {

	function doRead() {
		// strip HTML tags from the read line
		$line = fgetss($this->fp, 4096);

		// convert HTML entities to valid UTF-8 characters
		$line = String::html2utf($line);
		
		// slightly (~10%) faster than above, but not quite as accurate, and requires html_entity_decode()
//		$line = html_entity_decode($line, ENT_COMPAT, strtoupper(Config::getVar('i18n', 'client_charset')));

		return $line;
	}

}

?>
