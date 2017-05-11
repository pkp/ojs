<?php

/**
 * @file classes/search/SearchHTMLParser.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SearchHTMLParser
 * @ingroup search
 *
 * @brief Class to extract text from an HTML file.
 */


import('lib.pkp.classes.search.SearchFileParser');
import('lib.pkp.classes.core.PKPString');

class SearchHTMLParser extends SearchFileParser {

	function doRead() {
		// strip HTML tags from the read line
		$line = fgetss($this->fp, 4096);

		// convert HTML entities to valid UTF-8 characters
		$line = html_entity_decode($line, ENT_COMPAT, 'UTF-8');

		// slightly (~10%) faster than above, but not quite as accurate, and requires html_entity_decode()
//		$line = html_entity_decode($line, ENT_COMPAT, strtoupper(Config::getVar('i18n', 'client_charset')));

		return $line;
	}
}

?>
