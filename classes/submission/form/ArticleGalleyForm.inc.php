<?php

/**
 * @file ArticleGalleyForm.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission.form
 * @class ArticleGalleyForm
 *
 * Article galley editing form.
 *
 * $Id$
 */

import('form.Form');

class ArticleGalleyForm extends Form {
	/** @var int the ID of the article */
	var $articleId;

	/** @var int the ID of the galley */
	var $galleyId;

	/** @var ArticleGalley current galley */
	var $galley;

	/**
	 * Constructor.
	 * @param $articleId int
	 * @param $galleyId int (optional)
	 */
	function ArticleGalleyForm($articleId, $galleyId = null) {
		parent::Form('submission/layout/galleyForm.tpl');
		$journal =& Request::getJournal();
		$this->articleId = $articleId;

		if (isset($galleyId) && !empty($galleyId)) {
			$galleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');
			$this->galley = &$galleyDao->getGalley($galleyId, $articleId);
			if (isset($this->galley)) {
				$this->galleyId = $galleyId;
			}
		}

		// Validation checks for this form
		$this->addCheck(new FormValidator($this, 'label', 'required', 'submission.layout.galleyLabelRequired'));
		$this->addCheck(new FormValidator($this, 'galleyLocale', 'required', 'submission.layout.galleyLocaleRequired'), create_function('$galleyLocale,$availableLocales', 'return in_array($galleyLocale,$availableLocales);'), array_keys($journal->getSupportedLocaleNames()));
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Display the form.
	 */
	function display() {
		$journal =& Request::getJournal();
		$templateMgr = &TemplateManager::getManager();

		$templateMgr->assign('articleId', $this->articleId);
		$templateMgr->assign('galleyId', $this->galleyId);
		$templateMgr->assign('supportedLocales', $journal->getSupportedLocaleNames());
		$templateMgr->assign('enablePublicGalleyId', $journal->getSetting('enablePublicGalleyId'));

		if (isset($this->galley)) {
			$templateMgr->assign_by_ref('galley', $this->galley);
		}
		$templateMgr->assign('helpTopicId', 'editorial.layoutEditorsRole.layout');
		parent::display();
	}

	/**
	 * Validate the form
	 */
	function validate() {
		// check if public galley ID has already used
		$journal =& Request::getJournal();
		$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');

		$publicGalleyId = $this->getData('publicGalleyId');
		if ($publicGalleyId && $galleyDao->publicGalleyIdExists($publicGalleyId, $this->galleyId)) {
			$this->addError('publicGalleyId', Locale::translate('submission.layout.galleyPublicIdentificationExists'));
			$this->addErrorField('publicIssueId');
		}

		return parent::validate();
	}

	/**
	 * Initialize form data from current galley (if applicable).
	 */
	function initData() {
		if (isset($this->galley)) {
			$galley = &$this->galley;
			$this->_data = array(
				'label' => $galley->getLabel(),
				'publicGalleyId' => $galley->getPublicGalleyId(),
				'galleyLocale' => $galley->getLocale()
			);

		} else {
			$this->_data = array();
		}

	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(
			array(
				'label',
				'publicGalleyId',
				'deleteStyleFile',
				'galleyLocale'
			)
		);
	}

	/**
	 * Save changes to the galley.
	 * @return int the galley ID
	 */
	function execute($fileName = null) {
		import('file.ArticleFileManager');
		$articleFileManager = &new ArticleFileManager($this->articleId);
		$galleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');

		$fileName = isset($fileName) ? $fileName : 'galleyFile';
		$journal =& Request::getJournal();

		if (isset($this->galley)) {
			$galley = &$this->galley;

			// Upload galley file
			if ($articleFileManager->uploadedFileExists($fileName)) {
				if($galley->getFileId()) {
					$articleFileManager->uploadPublicFile($fileName, $galley->getFileId());
				} else {
					$fileId = $articleFileManager->uploadPublicFile($fileName);
					$galley->setFileId($fileId);
				}

				// Update file search index
				import('search.ArticleSearchIndex');
				ArticleSearchIndex::updateFileIndex($this->articleId, ARTICLE_SEARCH_GALLEY_FILE, $galley->getFileId());
			}

			if ($articleFileManager->uploadedFileExists('styleFile')) {
				// Upload stylesheet file
				$styleFileId = $articleFileManager->uploadPublicFile('styleFile', $galley->getStyleFileId());
				$galley->setStyleFileId($styleFileId);

			} else if($this->getData('deleteStyleFile')) {
				// Delete stylesheet file
				$styleFile = &$galley->getStyleFile();
				if (isset($styleFile)) {
					$articleFileManager->deleteFile($styleFile->getFileId());
				}
			}

			// Update existing galley
			$galley->setLabel($this->getData('label'));
			if ($journal->getSetting('enablePublicGalleyId')) {
				$galley->setPublicGalleyId($this->getData('publicGalleyId'));
			}
			$galley->setLocale($this->getData('galleyLocale'));
			$galleyDao->updateGalley($galley);

		} else {
			// Upload galley file
			if ($articleFileManager->uploadedFileExists($fileName)) {
				$fileType = $articleFileManager->getUploadedFileType($fileName);
				$fileId = $articleFileManager->uploadPublicFile($fileName);

				// Update file search index
				import('search.ArticleSearchIndex');
				ArticleSearchIndex::updateFileIndex($this->articleId, ARTICLE_SEARCH_GALLEY_FILE, $fileId);
			} else {
				$fileId = 0;
			}

			if (isset($fileType) && strstr($fileType, 'html')) {
				// Assume HTML galley
				$galley = &new ArticleHTMLGalley();
			} else {
				$galley = &new ArticleGalley();
			}

			$galley->setArticleId($this->articleId);
			$galley->setFileId($fileId);

			if ($this->getData('label') == null) {
				// Generate initial label based on file type
				$enablePublicGalleyId = $journal->getSetting('enablePublicGalleyId');
				if ($galley->isHTMLGalley()) {
					$galley->setLabel('HTML');
					if ($enablePublicGalleyId) $galley->setPublicGalleyId('html');
				} else if (isset($fileType)) {
					if(strstr($fileType, 'pdf')) {
						$galley->setLabel('PDF');
						if ($enablePublicGalleyId) $galley->setPublicgalleyId('pdf');
					} else if (strstr($fileType, 'postscript')) {
						$galley->setLabel('PostScript');
						if ($enablePublicGalleyId) $galley->setPublicgalleyId('ps');
					} else if (strstr($fileType, 'xml')) {
						$galley->setLabel('XML');
						if ($enablePublicGalleyId) $galley->setPublicgalleyId('xml');
					}
				}

				if ($galley->getLabel() == null) {
					$galley->setLabel(Locale::translate('common.untitled'));
				}

			} else {
				$galley->setLabel($this->getData('label'));
			}
			$galley->setLocale($this->getData('galleyLocale'));

			// Insert new galley
			$galleyDao->insertGalley($galley);
			$this->galleyId = $galley->getGalleyId();
		}

		return $this->galleyId;
	}

	/**
	 * Upload an image to an HTML galley.
	 * @param $imageName string file input key
	 */
	function uploadImage() {
		import('file.ArticleFileManager');
		$fileManager = &new ArticleFileManager($this->articleId);
		$galleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');

		$fileName = 'imageFile';

		if (isset($this->galley) && $fileManager->uploadedFileExists($fileName)) {
			$type = $fileManager->getUploadedFileType($fileName);
			$extension = $fileManager->getImageExtension($type);
			if (!$extension) {
				$this->addError('imageFile', Locale::translate('submission.layout.imageInvalid'));
				return false;
			}

			if ($fileId = $fileManager->uploadPublicFile($fileName)) {
				$galleyDao->insertGalleyImage($this->galleyId, $fileId);

				// Update galley image files
				$this->galley->setImageFiles($galleyDao->getGalleyImages($this->galleyId));
			}

		}
	}

	/**
	 * Delete an image from an HTML galley.
	 * @param $imageId int the file ID of the image
	 */
	function deleteImage($imageId) {
		import('file.ArticleFileManager');
		$fileManager = &new ArticleFileManager($this->articleId);
		$galleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');

		if (isset($this->galley)) {
			$images = &$this->galley->getImageFiles();
			if (isset($images)) {
				for ($i=0, $count=count($images); $i < $count; $i++) {
					if ($images[$i]->getFileId() == $imageId) {
						$fileManager->deleteFile($images[$i]->getFileId());
						$galleyDao->deleteGalleyImage($this->galleyId, $imageId);
						unset($images[$i]);
						break;
					}
				}
			}
		}
	}
}

?>
