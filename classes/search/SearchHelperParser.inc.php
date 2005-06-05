<?php

/**
 * SearchHelperParser.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
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

	/** Type should match an index[$type] setting in the "search" section of config.inc.php */
	var $type;
	
	function SearchHelperParser($type, $filePath) {
		parent::SearchFileParser($filePath);
		
		$this->type = $type;
	}

	function toText() {
		$prog = Config::getVar('search', 'index[' . $this->type . ']');
		
		if (isset($prog)) {
			$exec = sprintf($prog, escapeshellcmd($this->getFilePath()));
			return `$exec`;
		}
		
		return '';
	}
	
}

?>
