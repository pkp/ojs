<?php

/**
 * @file classes/search/SearchHelperParser.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SearchHelperParser
 * @ingroup search
 *
 * @brief Class to extract text from a file using an external helper program.
 */


import('lib.pkp.classes.search.SearchFileParser');

class SearchHelperParser extends SearchFileParser {

	/** @var string Type should match an index[$type] setting in the "search" section of config.inc.php */
	var $type;

	function __construct($type, $filePath) {
		parent::__construct($filePath);
		$this->type = $type;
	}

	function open() {
		$prog = Config::getVar('search', 'index[' . $this->type . ']');

		if (isset($prog)) {
			$exec = sprintf($prog, escapeshellcmd($this->getFilePath()));
			$this->fp = @popen($exec, 'r');
			return $this->fp ? true : false;
		}

		return false;
	}

	function close() {
		pclose($this->fp);
	}
}

?>
