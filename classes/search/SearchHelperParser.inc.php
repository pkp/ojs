<?php

/**
 * SearchHelperParser.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package search
 *
 * Class to extract text from a file using an external helper program.
 *
 * $Id$
 */

import('search.SearchFileParser');

class SearchHelperParser extends SearchFileParser {

	/** Type must match an index_XXX in the "search" section in config.inc.php */
	var $type;
	
	function SearchHelperParser($type, $filePath) {
		parent::SearchFileParser($filePath);
		
		$this->type = $type;
	}

	function toText() {
		$prog = Config::getVar('search', 'index_' . $this->type);
		
		if (isset($prog)) {
			$exec = sprintf($prog, $this->getFilePath());
			return `$exec`;
		}
	}
	
}

?>
