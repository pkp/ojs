<?php

/**
 * Action.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * Action class.
 *
 * $Id$
 */

class Action {

	/**
	 * Constructor.
	 */
	function Action() {

	}
	
	/**
	 * Actions.
	 */
	 
	/**
	 * View metadata of an article.
	 * @param $articleId int
	 */
	function viewMetadata($articleId, $roleId) {
		import("submission.form.MetadataForm");
		$metadataForm = new MetadataForm($articleId, $roleId);
		$metadataForm->initData();
		$metadataForm->display();
	}
	
	/**
	 * Save metadata.
	 * @param $articleId int
	 */
	function saveMetadata($articleId) {
		import("submission.form.MetadataForm");
		$metadataForm = new MetadataForm($articleId);
		$metadataForm->readInputData();
		
		// Check for any special cases before trying to save
		if (Request::getUserVar('addAuthor')) {
			// Add an author
			$editData = true;
			$authors = $metadataForm->getData('authors');
			array_push($authors, array());
			$metadataForm->setData('authors', $authors);
			
		} else if (($delAuthor = Request::getUserVar('delAuthor')) && count($delAuthor) == 1) {
			// Delete an author
			$editData = true;
			list($delAuthor) = array_keys($delAuthor);
			$delAuthor = (int) $delAuthor;
			$authors = $metadataForm->getData('authors');
			if (isset($authors[$delAuthor]['authorId']) && !empty($authors[$delAuthor]['authorId'])) {
				$deletedAuthors = explode(':', $metadataForm->getData('deletedAuthors'));
				array_push($deletedAuthors, $authors[$delAuthor]['authorId']);
				$metadataForm->setData('deletedAuthors', join(':', $deletedAuthors));
			}
			array_splice($authors, $delAuthor, 1);
			$metadataForm->setData('authors', $authors);
					
			if ($metadataForm->getData('primaryContact') == $delAuthor) {
				$metadataForm->setData('primaryContact', 0);
			}
					
		} else if (Request::getUserVar('moveAuthor')) {
			// Move an author up/down
			$editData = true;
			$moveAuthorDir = Request::getUserVar('moveAuthorDir');
			$moveAuthorDir = $moveAuthorDir == 'u' ? 'u' : 'd';
			$moveAuthorIndex = (int) Request::getUserVar('moveAuthorIndex');
			$authors = $metadataForm->getData('authors');
			
			if (!(($moveAuthorDir == 'u' && $moveAuthorIndex <= 0) || ($moveAuthorDir == 'd' && $moveAuthorIndex >= count($authors) - 1))) {
				$tmpAuthor = $authors[$moveAuthorIndex];
				if ($moveAuthorDir == 'u') {
					$authors[$moveAuthorIndex] = $authors[$moveAuthorIndex - 1];
					$authors[$moveAuthorIndex - 1] = $tmpAuthor;
				} else {
					$authors[$moveAuthorIndex] = $authors[$moveAuthorIndex + 1];
					$authors[$moveAuthorIndex + 1] = $tmpAuthor;
				}
			}
			$metadataForm->setData('authors', $authors);
		}
		
		if (!isset($editData)) {
			$metadataForm->execute();
		}
		
		$metadataForm->display();
	}
	
	/**
	 * Download file.
	 * @param $articleId int
	 * @param $fileId int
	 * @param $revision int
	 * FIXME: Security is not very good!
	 */
	function downloadFile($articleId, $fileId, $revision = null) {
		import("file.ArticleFileManager");
		$articleFileManager = new ArticleFileManager($articleId);
		$articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');
				
		$articleFile = &$articleFileDao->getArticleFile($fileId, $revision);
		
		if ($articleFile->getArticleId() == $articleId) {
			$articleFileManager->downloadFile($fileId, $revision);
		}
	}
}

?>
