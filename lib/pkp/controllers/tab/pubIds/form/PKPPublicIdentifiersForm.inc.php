<?php

/**
 * @file controllers/tab/pubIds/form/PKPPublicIdentifiersForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPPublicIdentifiersForm
 * @ingroup controllers_tab_pubIds_form
 *
 * @brief Displays a pub ids form.
 */

import('lib.pkp.classes.form.Form');
import('lib.pkp.classes.plugins.PKPPubIdPluginHelper');

class PKPPublicIdentifiersForm extends Form {

	/** @var int The context id */
	var $_contextId;

	/** @var object The pub object the identifiers are edited of
	 * 	Submission, Representation, SubmissionFile + OJS Issue
	 */
	var $_pubObject;

	/** @var int The current stage id, WORKFLOW_STAGE_ID_ */
	var $_stageId;

	/**
	 * @var array Parameters to configure the form template.
	 */
	var $_formParams;

	/**
	 * Constructor.
	 * @param $template string Form template path
	 * @param $pubObject object
	 * @param $stageId integer
	 * @param $formParams array
	 */
	function __construct($pubObject, $stageId = null, $formParams = null) {
		parent::__construct('controllers/tab/pubIds/form/publicIdentifiersForm.tpl');

		$this->_pubObject = $pubObject;
		$this->_stageId = $stageId;
		$this->_formParams = $formParams;

		$request = Application::getRequest();
		$context = $request->getContext();
		$this->_contextId = $context->getId();

		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_EDITOR);

		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));

		// action links for pub id reset requests
		$pubIdPluginHelper = new PKPPubIdPluginHelper();
		$pubIdPluginHelper->setLinkActions($this->getContextId(), $this, $pubObject);
	}

	/**
	 * @copydoc Form::fetch()
	 */
	function fetch($request) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'pubIdPlugins' => PluginRegistry::loadCategory('pubIds', true, $this->getContextId()),
			'pubObject' => $this->getPubObject(),
			'stageId' => $this->getStageId(),
			'formParams' => $this->getFormParams(),
		));
		// consider JavaScripts
		$pubIdPluginHelper = new PKPPubIdPluginHelper();
		$pubIdPluginHelper->addJavaScripts($this->getContextId(), $request, $templateMgr);
		return parent::fetch($request);
	}

	/**
	 * @copydoc Form::initData()
	 */
	function initData() {
		$pubObject = $this->getPubObject();
		$this->setData('publisherId', $pubObject->getStoredPubId('publisher-id'));
		$pubIdPluginHelper = new PKPPubIdPluginHelper();
		$pubIdPluginHelper->init($this->getContextId(), $this, $pubObject);
	}


	//
	// Getters
	//
	/**
	 * Get the pub object
	 * @return object
	 */
	function getPubObject() {
		return $this->_pubObject;
	}

	/**
	 * Get the stage id
	 * @return integer WORKFLOW_STAGE_ID_
	 */
	function getStageId() {
		return $this->_stageId;
	}

	/**
	 * Get the context id
	 * @return integer
	 */
	function getContextId() {
		return $this->_contextId;
	}

	/**
	 * Get the extra form parameters.
	 * @return array
	 */
	function getFormParams() {
		return $this->_formParams;
	}


	//
	// Form methods
	//
	/**
	 * @copydoc Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array('publisherId'));
		$pubIdPluginHelper = new PKPPubIdPluginHelper();
		$pubIdPluginHelper->readInputData($this->getContextId(), $this);
	}

	/**
	 * @copydoc Form::validate()
	 */
	function validate() {
		$pubObject = $this->getPubObject();
		$assocType = $this->getAssocType($pubObject);
		$publisherId = $this->getData('publisherId');
		$pubObjectId = $pubObject->getId();
		if ($assocType == ASSOC_TYPE_SUBMISSION_FILE) {
			$pubObjectId = $pubObject->getFileId();
		}
		$contextDao = Application::getContextDAO();
		if ($publisherId) {
			if (is_numeric($publisherId)) {
				$this->addError('publisherId', __('editor.publicIdentificationNumericNotAllowed', array('publicIdentifier' => $publisherId)));
				$this->addErrorField('$publisherId');
			} elseif (is_a($pubObject, 'SubmissionFile') && preg_match('/^(\d+)-(\d+)$/', $publisherId)) {
				$this->addError('publisherId', __('editor.publicIdentificationPatternNotAllowed', array('pattern' => '\'/^(\d+)-(\d+)$/\' i.e. \'number-number\'')));
				$this->addErrorField('$publisherId');
			} elseif ($contextDao->anyPubIdExists($this->getContextId(), 'publisher-id', $publisherId, $assocType, $pubObjectId, true)) {
				$this->addError('publisherId', __('editor.publicIdentificationExistsForTheSameType', array('publicIdentifier' => $publisherId)));
				$this->addErrorField('$publisherId');
			}
		}
		$pubIdPluginHelper = new PKPPubIdPluginHelper();
		$pubIdPluginHelper->validate($this->getContextId(), $this, $this->getPubObject());
		return parent::validate();
	}

	/**
	 * Store objects with pub ids.
	 * @copydoc Form::execute()
	 */
	function execute($request) {
		parent::execute($request);

		$pubObject = $this->getPubObject();
		$pubObject->setStoredPubId('publisher-id', $this->getData('publisherId'));

		$pubIdPluginHelper = new PKPPubIdPluginHelper();
		$pubIdPluginHelper->execute($this->getContextId(), $this, $pubObject);

		if (is_a($pubObject, 'Submission')) {
			$submissionDao = Application::getSubmissionDAO();
			$submissionDao->updateObject($pubObject);
		} elseif (is_a($pubObject, 'Representation')) {
			$representationDao = Application::getRepresentationDAO();
			$representationDao->updateObject($pubObject);
		} elseif (is_a($pubObject, 'SubmissionFile')) {
			$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
			$submissionFileDao->updateObject($pubObject);
		}
	}

	/**
	 * Clear pub id.
	 * @param $pubIdPlugInClassName string
	 */
	function clearPubId($pubIdPlugInClassName) {
		$pubIdPluginHelper = new PKPPubIdPluginHelper();
		$pubIdPluginHelper->clearPubId($this->getContextId(), $pubIdPlugInClassName, $this->getPubObject());
	}

	/**
	 * Get assoc type of the given object.
	 * @param $pubObject
	 * @return integer ASSOC_TYPE_
	 */
	function getAssocType($pubObject) {
		$assocType = null;
		if (is_a($pubObject, 'Submission')) {
			$assocType = ASSOC_TYPE_SUBMISSION;
		} elseif (is_a($pubObject, 'Representation')) {
			$assocType = ASSOC_TYPE_REPRESENTATION;
		} elseif (is_a($pubObject, 'SubmissionFile')) {
			$assocType = ASSOC_TYPE_SUBMISSION_FILE;
		}
		return $assocType;
	}
}

?>
