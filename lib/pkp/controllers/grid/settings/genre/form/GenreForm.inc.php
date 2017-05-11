<?php

/**
 * @file controllers/grid/settings/genre/form/GenreForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class GenreForm
 * @ingroup controllers_grid_settings_genre_form
 *
 * @brief Form for adding/editing a Submission File Genre.
 */

import('lib.pkp.classes.form.Form');

class GenreForm extends Form {
	/** the id for the genre being edited **/
	var $_genreId;

	/**
	 * Set the genre id
	 * @param $genreId int
	 */
	function setGenreId($genreId) {
		$this->_genreId = $genreId;
	}

	/**
	 * Get the genre id
	 * @return int
	 */
	function getGenreId() {
		return $this->_genreId;
	}


	/**
	 * Constructor.
	 */
	function __construct($genreId = null) {
		$this->setGenreId($genreId);
		parent::__construct('controllers/grid/settings/genre/form/genreForm.tpl');

		// Validation checks for this form
		$this->addCheck(new FormValidatorLocale($this, 'name', 'required', 'manager.setup.form.genre.nameRequired'));
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	/**
	 * Initialize form data from current settings.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function initData($args, $request) {
		$context = $request->getContext();

		$genreDao = DAORegistry::getDAO('GenreDAO');

		if($this->getGenreId()) {
			$genre = $genreDao->getById($this->getGenreId(), $context->getId());
		}

		if (isset($genre) ) {
			$this->_data = array(
				'genreId' => $this->getGenreId(),
				'name' => $genre->getName(null),
				'sortable' => $genre->getSortable(),
				'category' => $genre->getCategory(),
				'dependent' => $genre->getDependent(),
				'supplementary' => $genre->getSupplementary(),
			);
		} else {
			$this->_data = array(
				'name' => array(),
			);
		}

		// grid related data
		$this->_data['gridId'] = $args['gridId'];
		$this->_data['rowId'] = isset($args['rowId']) ? $args['rowId'] : null;
	}

	/**
	 * Fetch
	 * @param $request PKPRequest
	 * @see Form::fetch()
	 */
	function fetch($request) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('submissionFileCategories', array(
			GENRE_CATEGORY_DOCUMENT => __('submission.document'),
			GENRE_CATEGORY_ARTWORK => __('submission.art'),
			GENRE_CATEGORY_SUPPLEMENTARY => __('submission.supplementary'),
		));

		AppLocale::requireComponents(LOCALE_COMPONENT_APP_MANAGER);
		return parent::fetch($request);
	}

	/**
	 * Assign form data to user-submitted data.
	 * @see Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array('genreId', 'name', 'sortable', 'category', 'dependent', 'supplementary', 'gridId', 'rowId'));
	}

	/**
	 * Save email template.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function execute($args, $request) {
		$genreDao = DAORegistry::getDAO('GenreDAO');
		$context = $request->getContext();

		// Update or insert genre
		if (!$this->getGenreId()) {
			$genre = $genreDao->newDataObject();
			$genre->setContextId($context->getId());
		} else {
			$genre = $genreDao->getById($this->getGenreId(), $context->getId());
		}

		$genre->setData('name', $this->getData('name'), null); // Localized
		$genre->setSortable($this->getData('sortable'));
		$genre->setCategory($this->getData('category'));
		$genre->setDependent($this->getData('dependent'));
		$genre->setSupplementary($this->getData('supplementary'));

		if (!$this->getGenreId()) {
			$this->setGenreId($genreDao->insertObject($genre));
		} else {
			$genreDao->updateObject($genre);
		}

		return true;
	}
}

?>
