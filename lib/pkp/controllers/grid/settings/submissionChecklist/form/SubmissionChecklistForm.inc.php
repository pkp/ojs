<?php

/**
 * @file controllers/grid/settings/submissionChecklist/form/SubmissionChecklistForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionChecklistForm
 * @ingroup controllers_grid_settings_submissionChecklist_form
 *
 * @brief Form for adding/edditing a submissionChecklist
 * stores/retrieves from an associative array
 */

import('lib.pkp.classes.form.Form');

class SubmissionChecklistForm extends Form {
	/** @var int The id for the submissionChecklist being edited **/
	var $submissionChecklistId;

	/**
	 * Constructor.
	 */
	function __construct($submissionChecklistId = null) {
		$this->submissionChecklistId = $submissionChecklistId;
		parent::__construct('controllers/grid/settings/submissionChecklist/form/submissionChecklistForm.tpl');

		// Validation checks for this form
		$this->addCheck(new FormValidatorLocale($this, 'checklistItem', 'required', 'maganer.setup.submissionChecklistItemRequired'));
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

		$submissionChecklistAll = $context->getSetting('submissionChecklist');
		$checklistItem = array();
		// preparea  localizable array for this checklist Item
		foreach (AppLocale::getSupportedLocales() as $locale => $name) {
			$checklistItem[$locale] = null;
		}

		// if editing, set the content
		// use of 'content' as key is for backwards compatibility
		if ( isset($this->submissionChecklistId) ) {
			foreach (AppLocale::getSupportedLocales() as $locale => $name) {
				if ( !isset($submissionChecklistAll[$locale][$this->submissionChecklistId]['content'])) {
					$checklistItem[$locale] = '';
				} else {
					$checklistItem[$locale] = $submissionChecklistAll[$locale][$this->submissionChecklistId]['content'];
				}
			}
		}
		// assign the data to the form
		$this->_data = array( 'checklistItem' => $checklistItem	);

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
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_MANAGER);
		return parent::fetch($request);
	}

	/**
	 * Assign form data to user-submitted data.
	 * @see Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array('submissionChecklistId', 'checklistItem'));
		$this->readUserVars(array('gridId', 'rowId'));
	}

	/**
	 * Save checklist entry.
	 */
	function execute($args, $request) {
		$router = $request->getRouter();
		$context = $router->getContext($request);
		$submissionChecklistAll = $context->getSetting('submissionChecklist');
		$locale = AppLocale::getPrimaryLocale();
		//FIXME: a bit of kludge to get unique submissionChecklist id's
		$this->submissionChecklistId = ($this->submissionChecklistId != null ? $this->submissionChecklistId:(max(array_keys($submissionChecklistAll[$locale])) + 1));

		$order = 0;
		foreach ($submissionChecklistAll[$locale] as $checklistItem) {
			if ($checklistItem['order'] > $order) {
				$order = $checklistItem['order'];
			}
		}
		$order++;

		$checklistItem = $this->getData('checklistItem');
		foreach (AppLocale::getSupportedLocales() as $locale => $name) {
			if (isset($checklistItem[$locale])) {
				$submissionChecklistAll[$locale][$this->submissionChecklistId]['content'] = $checklistItem[$locale];
				$submissionChecklistAll[$locale][$this->submissionChecklistId]['order'] = $order;
			}
		}

		$context->updateSetting('submissionChecklist', $submissionChecklistAll, 'object', true);
		return true;
	}
}

?>
