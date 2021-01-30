<?php

/**
 * @file controllers/grid/articleGalleys/form/ArticleGalleyForm.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ArticleGalleyForm
 * @ingroup controllers_grid_articleGalleys_form
 * @see ArticleGalley
 *
 * @brief Article galley editing form.
 */

import('lib.pkp.classes.form.Form');

class ArticleGalleyForm extends Form {
	/** @var Submission */
	var $_submission = null;

	/** @var Publication */
	var $_publication = null;

	/** @var ArticleGalley current galley */
	var $_articleGalley = null;

	/**
	 * Constructor.
	 * @param $submission Submission
	 * @param $publication Publication
	 * @param $articleGalley ArticleGalley (optional)
	 */
	function __construct($request, $submission, $publication, $articleGalley = null) {
		parent::__construct('controllers/grid/articleGalleys/form/articleGalleyForm.tpl');
		$this->_submission = $submission;
		$this->_publication = $publication;
		$this->_articleGalley = $articleGalley;

		AppLocale::requireComponents(LOCALE_COMPONENT_APP_EDITOR, LOCALE_COMPONENT_PKP_SUBMISSION);

		$this->addCheck(new FormValidator($this, 'label', 'required', 'editor.issues.galleyLabelRequired'));
		$this->addCheck(new FormValidatorRegExp($this, 'urlPath', 'optional', 'validator.alpha_dash', '/^[-_a-z0-9]*$/'));
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));

		// Ensure a locale is provided and valid
		$journal = $request->getJournal();
		$this->addCheck(
			new FormValidator(
				$this,
				'galleyLocale',
				'required',
				'editor.issues.galleyLocaleRequired'
			),
			function($galleyLocale) use ($journal) {
				return in_array($galleyLocale, $journal->getSupportedSubmissionLocaleNames());
			}
		);
	}

	/**
	 * @copydoc Form::fetch()
	 */
	function fetch($request, $template = null, $display = false) {
		$templateMgr = TemplateManager::getManager($request);
		if ($this->_articleGalley) {
			$articleGalleyFile = $this->_articleGalley->getFile();
			$templateMgr->assign([
				'representationId' => $this->_articleGalley->getId(),
				'articleGalley' => $this->_articleGalley,
				'articleGalleyFile' => $articleGalleyFile,
				'supportsDependentFiles' => $articleGalleyFile ? Services::get('submissionFile')->supportsDependentFiles($articleGalleyFile) : null,
			]);
		}
		$context = $request->getContext();
		$templateMgr->assign(array(
			'supportedLocales' => $context->getSupportedSubmissionLocaleNames(),
			'submissionId' => $this->_submission->getId(),
			'publicationId' => $this->_publication->getId(),
		));

		return parent::fetch($request, $template, $display);
	}

	/**
	 * @copydoc Form::validate
	 */
	function validate($callHooks = true) {

		// Check if urlPath is already being used
		if ($this->getData('urlPath')) {
			if (ctype_digit((string) $this->getData('urlPath'))) {
				$this->addError('urlPath', __('publication.urlPath.numberInvalid'));
				$this->addErrorField('urlPath');
			} else {
				$articleGalley = Application::get()->getRepresentationDAO()->getByBestGalleyId($this->getData('urlPath'), $this->_publication->getId());
				if ($articleGalley &&
					(!$this->_articleGalley || $this->_articleGalley->getId() !== $articleGalley->getId())
				) {
					$this->addError('urlPath', __('publication.urlPath.duplicate'));
					$this->addErrorField('urlPath');
				}
			}
		}

		return parent::validate($callHooks);
	}

	/**
	 * Initialize form data from current galley (if applicable).
	 */
	function initData() {
		if ($this->_articleGalley) {
			$this->_data = array(
				'label' => $this->_articleGalley->getLabel(),
				'galleyLocale' => $this->_articleGalley->getLocale(),
				'urlPath' => $this->_articleGalley->getData('urlPath'),
				'urlRemote' => $this->_articleGalley->getData('urlRemote'),
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
				'galleyLocale',
				'urlPath',
				'urlRemote',
			)
		);
	}

	/**
	 * Save changes to the galley.
	 * @return ArticleGalley The resulting article galley.
	 */
	function execute(...$functionArgs) {
		import('classes.file.IssueFileManager');

		$articleGalley = $this->_articleGalley;
		$articleGalleyDao = DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $articleGalleyDao ArticleGalleyDAO */

		if ($articleGalley) {
			$articleGalley->setLabel($this->getData('label'));
			$articleGalley->setLocale($this->getData('galleyLocale'));
			$articleGalley->setData('urlPath', $this->getData('urlPath'));
			$articleGalley->setData('urlRemote', $this->getData('urlRemote'));

			// Update galley in the db
			$articleGalleyDao->updateObject($articleGalley);
		} else {
			// Create a new galley
			$articleGalley = $articleGalleyDao->newDataObject();
			$articleGalley->setData('publicationId', $this->_publication->getId());
			$articleGalley->setLabel($this->getData('label'));
			$articleGalley->setLocale($this->getData('galleyLocale'));
			$articleGalley->setData('urlPath', $this->getData('urlPath'));
			$articleGalley->setData('urlRemote', $this->getData('urlRemote'));

			// Insert new galley into the db
			$articleGalleyDao->insertObject($articleGalley);
			$this->_articleGalley = $articleGalley;
		}

		parent::execute(...$functionArgs);

		return $articleGalley;
	}
}


