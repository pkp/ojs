<?php

/**
 * @file controllers/grid/pubIds/form/AssignPublicIdentifiersForm.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AssignPublicIdentifiersForm
 * @ingroup controllers_grid_pubIds_form
 *
 * @brief Displays the assign pub id form.
 */

import('lib.pkp.controllers.grid.pubIds.form.PKPAssignPublicIdentifiersForm');

class AssignPublicIdentifiersForm extends PKPAssignPublicIdentifiersForm {

	/**
	 * @var array Parameters to configure the form template.
	 */
	var $_formParams;

	/**
	 * Constructor.
	 * @param $template string Form template
	 * @param $pubObject object
	 * @param $approval boolean
	 * @param $confirmationText string
	 * @param $formParams array
	 */
	function __construct($template, $pubObject, $approval, $confirmationText, $formParams = null) {
		parent::__construct($template, $pubObject, $approval, $confirmationText);

		$this->_formParams = $formParams;
	}

	/**
	 * @copydoc Form::fetch()
	 */
	function fetch($request) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('formParams', $this->getFormParams());
		return parent::fetch($request);
	}

	//
	// Getters and Setters
	//
	/**
	 * Get the extra form parameters.
	 * @return array
	 */
	function getFormParams() {
		return $this->_formParams;
	}

}

?>
