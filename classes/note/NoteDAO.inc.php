<?php

/**
 * @file classes/note/NoteDAO.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NoteDAO
 * @ingroup note
 * @see PKPNoteDAO
 *
 * @brief OJS extension of PKPNoteDAO
 */

import('lib.pkp.classes.note.PKPNoteDAO');
import('classes.note.Note');

class NoteDAO extends PKPNoteDAO {
	/** @var $articleFileDao Object */
	var $articleFileDao;

	/**
	 * Constructor
	 */
	function NoteDAO() {
		$this->articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');
		parent::PKPNoteDAO();
	}

	/**
	 * Construct a new data object corresponding to this DAO.
	 * @return Note
	 */
	function newDataObject() {
		return new Note();
	}

	function &_returnNoteFromRow($row) {
		$note =& parent::_returnNoteFromRow($row);

		if ($fileId = $note->getFileId()) {
			$file =& $this->articleFileDao->getArticleFile($fileId);
			$note->setFile($file);
		}

		return $note;
	}
}

?>
