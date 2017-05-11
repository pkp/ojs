<?php

/**
 * @file controllers/grid/pubIds/form/PKPAssignPublicIdentifiersForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPAssignPublicIdentifiersForm
 * @ingroup controllers_grid_pubIds_form
 *
 * @brief Displays the assign pub id form.
 */

import('lib.pkp.classes.form.Form');
import('lib.pkp.classes.plugins.PKPPubIdPluginHelper');

class PKPAssignPublicIdentifiersForm extends Form {

	/** @var int The context id */
	var $_contextId;

	/** @var object The pub object, that are beeing approved,
	 * the pub ids can be considered for assignement there
	 * OJS Issue, Representation or SubmissionFile
	 */
	var $_pubObject;

	/** @var boolean */
	var $_approval;

	/**
	 * @var string Confirmation to display.
	 */
	var $_confirmationText;

	/**
	 * Constructor.
	 * @param $template string Form template
	 * @param $pubObject object
	 * @param $approval boolean
	 * @param $confirmationText string
	 */
	function __construct($template, $pubObject, $approval, $confirmationText) {
		parent::__construct($template);

		$this->_pubObject = $pubObject;
		$this->_approval = $approval;
		$this->_confirmationText = $confirmationText;

		$request = Application::getRequest();
		$context = $request->getContext();
		$this->_contextId = $context->getId();

		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	/**
	 * @copydoc Form::fetch()
	 */
	function fetch($request) {
		$templateMgr = TemplateManager::getManager($request);
		$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true, $this->getContextId());
		$templateMgr->assign(array(
			'pubIdPlugins' => $pubIdPlugins,
			'pubObject' => $this->getPubObject(),
			'approval' => $this->getApproval(),
			'confirmationText' => $this->getConfirmationText(),
		));
		return parent::fetch($request);
	}


	//
	// Getters and Setters
	//
	/**
	 * Get the pub object
	 * @return object
	 */
	function getPubObject() {
		return $this->_pubObject;
	}

	/**
	 * Get weather it is an approval
	 * @return boolean
	 */
	function getApproval() {
		return $this->_approval;
	}

	/**
	 * Get the context id
	 * @return integer
	 */
	function getContextId() {
		return $this->_contextId;
	}

	/**
	 * Get the confirmation text.
	 * @return string
	 */
	function getConfirmationText() {
		return $this->_confirmationText;
	}

	/**
	 * @copydoc Form::readInputData()
	 */
	function readInputData() {
		$pubIdPluginHelper = new PKPPubIdPluginHelper();
		$pubIdPluginHelper->readAssignInputData($this);
	}

	/**
	 * Assign pub ids.
	 * @param $request PKPRequest
	 * @param $save boolean
	 *  true if the pub id shall be saved here
	 *  false if this form is integrated somewhere else, where the pub object will be updated.
	 */
	function execute($request, $save = false) {
		parent::execute($request);

		$pubObject = $this->getPubObject();
		$pubIdPluginHelper = new PKPPubIdPluginHelper();
		$pubIdPluginHelper->assignPubId($this->getContextId(), $this, $pubObject, $save);
	}

}

?>
