<?php

/**
 * @file classes/note/Note.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Note
 * @ingroup note
 * @see NoteDAO
 *
 * @brief Class for OJS Note.
 */

import('classes.article.ArticleFile');
import('lib.pkp.classes.note.PKPNote');

class Note extends PKPNote {
	/**
	 * Constructor.
	 */
	function Note() {
		parent::PKPNote();
	}

	/**
	 * get article note id
	 * @return int
	 */
	function getNoteId() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getId();
	}

	/**
	 * set article note id
	 * @param $noteId int
	 */
	function setNoteId($noteId) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->setId($noteId);
	}

	/**
	 * get article id
	 * @return int
	 */
	function getArticleId() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getAssocId();
	}

	/**
	 * set article id
	 * @param $articleId int
	 */
	function setArticleId($articleId) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->setAssocId($articleId);
	}

	/**
	 * get note
	 * @return string
	 */
	function getNote() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getContents();
	}

	/**
	 * set note
	 * @param $note string
	 */
	function setNote($note) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->setContents($note);
	}

	/**
	 * get file
	 * @return string
	 */
	function getFile() {
		return $this->getData('file');
	}

	/**
	 * set note
	 * @param $note string
	 */
	function setFile($file) {
		return $this->setData('file', $file);
	}

	function getOriginalFileName() {
		$file = $this->getFile();
		return $file->getOriginalFileName();
	}
}

?>
