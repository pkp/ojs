<?php

/**
 * @file controllers/grid/languages/LanguageGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class LanguageGridCellProvider
 * @ingroup controllers_grid_languages
 *
 * @brief Subclass for a language grid column's cell provider
 */

import('lib.pkp.classes.controllers.grid.GridCellProvider');

class LanguageGridCellProvider extends GridCellProvider {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * @copydoc GridCellProvider::getTemplateVarsFromRowColumn()
	 */
	function getTemplateVarsFromRowColumn($row, $column) {
		$element = $row->getData();
		$columnId = $column->getId();
		switch ($columnId) {
			case 'enable':
				return array('selected' => $element['supported'],
					'disabled' => false);
			case 'locale':
				$label = $element['name'];
				$returnArray = array('label' => $label);

				if (isset($element['incomplete'])) {
					$returnArray['incomplete'] = $element['incomplete'];
				}
				return $returnArray;
			case 'sitePrimary':
				return array('selected' => $element['primary'],
					'disabled' => !$element['supported']);
			case 'contextPrimary':
				return array('selected' => $element['primary'],
					'disabled' => !$element['supported']);
			case 'uiLocale';
				return array('selected' => $element['supportedLocales'],
					'disabled' => !$element['supported']);
			case 'submissionLocale';
				return array('selected' => $element['supportedSubmissionLocales'],
					'disabled' => !$element['supported']);
			case 'formLocale';
				return array('selected' => $element['supportedFormLocales'],
					'disabled' => !$element['supported']);
			default:
				assert(false);
				break;
		}
	}

	/**
	 * @copydoc GridCellProvider::getCellActions()
	 */
	function getCellActions($request, $row, $column) {
		import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');
		import('lib.pkp.classes.linkAction.request.AjaxAction');

		$element = $row->getData();
		$router = $request->getRouter();
		$actions = array();
		$actionArgs = array('rowId' => $row->getId());

		$action = null;
		$actionRequest = null;

		switch ($column->getId()) {
			case 'enable':
				$enabled = $element['supported'];
				if ($enabled) {
					$action = 'disable-' . $row->getId();
					$actionRequest = new RemoteActionConfirmationModal(
						$request->getSession(),
						__('admin.languages.confirmDisable'),
						__('common.disable'),
						$router->url($request, null, null, 'disableLocale', null, $actionArgs)
					);
				} else {
					$action = 'enable-' . $row->getId();
					$actionRequest = new AjaxAction($router->url($request, null, null, 'enableLocale', null, $actionArgs));
				}
				break;
			case 'sitePrimary':
				$primary = $element['primary'];
				if (!$primary) {
					$action = 'setPrimary-' . $row->getId();
					$actionRequest = new AjaxAction($router->url($request, null, null, 'setPrimaryLocale', null, $actionArgs));
				}
				break;
			case 'contextPrimary':
				$primary = $element['primary'];
				if (!$primary) {
					$action = 'setPrimary-' . $row->getId();
					$actionRequest = new AjaxAction($router->url($request, null, null, 'setContextPrimaryLocale', null, $actionArgs));
				}
				break;
			case 'uiLocale':
				$action = 'setUiLocale-' . $row->getId();
				$actionArgs['setting'] = 'supportedLocales';
				$actionArgs['value'] = !$element['supportedLocales'];
				$actionRequest = new AjaxAction($router->url($request, null, null, 'saveLanguageSetting', null, $actionArgs));
				break;
			case 'submissionLocale':
				$action = 'setSubmissionLocale-' . $row->getId();
				$actionArgs['setting'] = 'supportedSubmissionLocales';
				$actionArgs['value'] = !$element['supportedSubmissionLocales'];
				$actionRequest = new AjaxAction($router->url($request, null, null, 'saveLanguageSetting', null, $actionArgs));
				break;
			case 'formLocale':
				$action = 'setFormLocale-' . $row->getId();
				$actionArgs['setting'] = 'supportedFormLocales';
				$actionArgs['value'] = !$element['supportedFormLocales'];
				$actionRequest = new AjaxAction($router->url($request, null, null, 'saveLanguageSetting', null, $actionArgs));
				break;
		}

		if ($action && $actionRequest) {
			$linkAction = new LinkAction($action, $actionRequest, null, null);
			$actions = array($linkAction);
		}

		return $actions;
	}
}

?>
