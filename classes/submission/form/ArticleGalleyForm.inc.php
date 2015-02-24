<?php

/**
 * @defgroup submission_form
 */

/**
 * @file classes/submission/form/ArticleGalleyForm.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleGalleyForm
 * @ingroup submission_form
 * @see ArticleGalley
 *
 * @brief Article galley editing form.
 */

import('lib.pkp.classes.form.Form');

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
			$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');
			$this->galley =& $galleyDao->getGalley($galleyId, $articleId);
			if (isset($this->galley)) {
				$this->galleyId = $galleyId;
			}
		}

		// Validation checks for this form
		$this->addCheck(new FormValidator($this, 'label', 'required', 'submission.layout.galleyLabelRequired'));
		$this->addCheck(new FormValidator($this, 'galleyLocale', 'required', 'submission.layout.galleyLocaleRequired'), create_function('$galleyLocale,$availableLocales', 'return in_array($galleyLocale,$availableLocales);'), array_keys($journal->getSupportedSubmissionLocaleNames()));
		$this->addCheck(new FormValidatorURL($this, 'remoteURL', 'optional', 'submission.layout.galleyRemoteURLValid'));
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Display the form.
	 */
	function display() {
		$journal =& Request::getJournal();
		$templateMgr =& TemplateManager::getManager();

		$templateMgr->assign('articleId', $this->articleId);
		$templateMgr->assign('galleyId', $this->galleyId);
		$templateMgr->assign('supportedSubmissionLocales', $journal->getSupportedSubmissionLocaleNames());
		$templateMgr->assign('enablePublicGalleyId', $journal->getSetting('enablePublicGalleyId'));

		if (isset($this->galley)) {
			$templateMgr->assign_by_ref('galley', $this->galley);
		}
		$templateMgr->assign('helpTopicId', 'editorial.layoutEditorsRole.layout');
		// consider public identifiers
		$pubIdPlugins =& PluginRegistry::loadCategory('pubIds', true);
		$templateMgr->assign('pubIdPlugins', $pubIdPlugins);
		parent::display();
	}

	/**
	 * Validate the form
	 */
	function validate() {
		// check if public galley ID has already been used for another galley of this article
		$journal =& Request::getJournal();
		$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $galleylDao ArticleGalleyDAO */

		$publicGalleyId = $this->getData('publicGalleyId');
		if ($publicGalleyId && $galleyDao->getGalleyByPubId('publisher-id', $publicGalleyId, $this->articleId)) {
			$this->addError('publicGalleyId', __('editor.publicGalleyIdentificationExists', array('publicIdentifier' => $publicGalleyId)));
			$this->addErrorField('publicGalleyId');
		}

		// Verify additional fields from public identifer plug-ins.
		import('classes.plugins.PubIdPluginHelper');
		$pubIdPluginHelper = new PubIdPluginHelper();
		$pubIdPluginHelper->validate($journal->getId(), $this, $this->galley);

		return parent::validate();
	}

	/**
	 * Initialize form data from current galley (if applicable).
	 */
	function initData() {
		if (isset($this->galley)) {
			$galley =& $this->galley;
			$this->_data = array(
				'label' => $galley->getLabel(),
				'publicGalleyId' => $galley->getPubId('publisher-id'),
				'galleyLocale' => $galley->getLocale()
			);

		} else {
			$this->_data = array();
		}
		// consider the additional field names from the public identifer plugins
		import('classes.plugins.PubIdPluginHelper');
		$pubIdPluginHelper = new PubIdPluginHelper();
		$pubIdPluginHelper->init($this, $galley);

		parent::initData();
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
				'galleyLocale',
				'remoteURL'
			)
		);
		// consider the additional field names from the public identifer plugins
		import('classes.plugins.PubIdPluginHelper');
		$pubIdPluginHelper = new PubIdPluginHelper();
		$pubIdPluginHelper->readInputData($this);

	}

	/**
	 * Save changes to the galley.
	 * @return int the galley ID
	 */
	function execute($fileName = null, $createRemote = false) {
		import('classes.file.ArticleFileManager');
		$articleFileManager = new ArticleFileManager($this->articleId);
		$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');

		$fileName = isset($fileName) ? $fileName : 'galleyFile';
		$journal =& Request::getJournal();
		$fileId = null;

		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$article =& $articleDao->getArticle($this->articleId, $journal->getId());

		if (isset($this->galley)) {
			$galley =& $this->galley;

			// Upload galley file
			if ($articleFileManager->uploadedFileExists($fileName)) {
				if($galley->getFileId()) {
					$articleFileManager->uploadPublicFile($fileName, $galley->getFileId());
					$fileId = $galley->getFileId();
				} else {
					$fileId = $articleFileManager->uploadPublicFile($fileName);
					$galley->setFileId($fileId);
				}

				// Update file search index
				import('classes.search.ArticleSearchIndex');
				$articleSearchIndex = new ArticleSearchIndex();
				$articleSearchIndex->articleFileChanged($this->articleId, ARTICLE_SEARCH_GALLEY_FILE, $galley->getFileId());
				$articleSearchIndex->articleChangesFinished();
			}

			if ($articleFileManager->uploadedFileExists('styleFile')) {
				// Upload stylesheet file
				$styleFileId = $articleFileManager->uploadPublicFile('styleFile', $galley->getStyleFileId());
				$galley->setStyleFileId($styleFileId);

			} else if($this->getData('deleteStyleFile')) {
				// Delete stylesheet file
				$styleFile =& $galley->getStyleFile();
				if (isset($styleFile)) {
					$articleFileManager->deleteFile($styleFile->getFileId());
				}
			}

			// Update existing galley
			$galley->setLabel($this->getData('label'));
			if ($journal->getSetting('enablePublicGalleyId')) {
				$galley->setStoredPubId('publisher-id', $this->getData('publicGalleyId'));
			}
			$galley->setLocale($this->getData('galleyLocale'));
			if ($this->getData('remoteURL')) {
				$galley->setRemoteURL($this->getData('remoteURL'));
			}

			// consider the additional field names from the public identifer plugins
			import('classes.plugins.PubIdPluginHelper');
			$pubIdPluginHelper = new PubIdPluginHelper();
			$pubIdPluginHelper->execute($this, $galley);

			parent::execute();
			$galleyDao->updateGalley($galley);

		} else {
			// Upload galley file
			if ($articleFileManager->uploadedFileExists($fileName)) {
				$fileType = $articleFileManager->getUploadedFileType($fileName);
				$fileId = $articleFileManager->uploadPublicFile($fileName);
			}

			if (isset($fileType) && strstr($fileType, 'html')) {
				// Assume HTML galley
				$galley = new ArticleHTMLGalley();
			} else {
				$galley = new ArticleGalley();
			}

			$galley->setArticleId($this->articleId);
			$galley->setFileId($fileId);

			if ($this->getData('label') == null) {
				// Generate initial label based on file type
				$enablePublicGalleyId = $journal->getSetting('enablePublicGalleyId');
				if ($galley->isHTMLGalley()) {
					$galley->setLabel('HTML');
					if ($enablePublicGalleyId) $galley->setStoredPubId('publisher-id', 'html');
				} else if ($createRemote) {
					$galley->setLabel(__('common.remote'));
					$galley->setRemoteURL(__('common.remoteURL'));
					if ($enablePublicGalleyId) $galley->setStoredPubId('publisher-id', strtolower(__('common.remote')));
				} else if (isset($fileType)) {
					if(strstr($fileType, 'pdf')) {
						$galley->setLabel('PDF');
						if ($enablePublicGalleyId) $galley->setStoredPubId('publisher-id', 'pdf');
					} else if (strstr($fileType, 'postscript')) {
						$galley->setLabel('PostScript');
						if ($enablePublicGalleyId) $galley->setStoredPubId('publisher-id', 'ps');
					} else if (strstr($fileType, 'xml')) {
						$galley->setLabel('XML');
						if ($enablePublicGalleyId) $galley->setStoredPubId('publisher-id', 'xml');
					} else if (strstr($fileType, 'epub')) {
						$galley->setLabel('EPUB');
						if ($enablePublicGalleyId) $galley->setStoredPubId('publisher-id', 'epub');
					}
				}

				if ($galley->getLabel() == null) {
					$galley->setLabel(__('common.untitled'));
				}

			} else {
				$galley->setLabel($this->getData('label'));
			}
			$galley->setLocale($article->getLocale());

			if ($enablePublicGalleyId) {
				// check to make sure the assigned public id doesn't already exist for another galley of this article
				$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $galleylDao ArticleGalleyDAO */

				$publicGalleyId = $galley->getPubId('publisher-id');
				$suffix = '';
				$i = 1;
				while ($galleyDao->getGalleyByPubId('publisher-id', $publicGalleyId . $suffix, $this->articleId)) {
					$suffix = '_'.$i++;
				}

				$galley->setStoredPubId('publisher-id', $publicGalleyId . $suffix);
			}

			parent::execute();

			// Insert new galley
			$galleyDao->insertGalley($galley);
			$this->galleyId = $galley->getId();
		}

		if ($fileId) {
			// Update file search index
			import('classes.search.ArticleSearchIndex');
			$articleSearchIndex = new ArticleSearchIndex();
			$articleSearchIndex->articleFileChanged($this->articleId, ARTICLE_SEARCH_GALLEY_FILE, $fileId);
			$articleSearchIndex->articleChangesFinished();
		}

		// Stamp the article modification (for OAI)
		$articleDao->updateArticle($article);

		return $this->galleyId;
	}

	/**
	 * Upload an image to an HTML galley.
	 * @param $imageName string file input key
	 */
	function uploadImage() {
		import('classes.file.ArticleFileManager');
		$fileManager = new ArticleFileManager($this->articleId);
		$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');

		$fileName = 'imageFile';

		if (isset($this->galley) && $fileManager->uploadedFileExists($fileName)) {
			$type = $fileManager->getUploadedFileType($fileName);
			$extension = $fileManager->getImageExtension($type);
			if (!$extension) {
				$this->addError('imageFile', __('submission.layout.imageInvalid'));
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
		import('classes.file.ArticleFileManager');
		$fileManager = new ArticleFileManager($this->articleId);
		$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');

		if (isset($this->galley)) {
			$images =& $this->galley->getImageFiles();
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
