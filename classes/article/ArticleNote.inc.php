<?php

/**
 * @file classes/article/ArticleNote.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleNote
 * @ingroup article
 * @see ArticleNoteDAO
 *
 * @brief Class for ArticleNote.
 */

import('classes.note.Note');

class ArticleNote extends Note {
	/**
	 * Constructor.
	 */
	function ArticleNote() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated class ArticleNote. Use Note instead');
		parent::Note();
	}
}

?>
