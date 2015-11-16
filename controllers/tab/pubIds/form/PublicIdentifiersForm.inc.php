<?php

/**
 * @file controllers/tab/issueEntry/form/PublicIdentifiersForm.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PublicIdentifiersForm
 * @ingroup controllers_tab_issueEntry_form_PublicIdentifiersForm
 *
 * @brief Displays a submission's pub ids form.
 */

import('lib.pkp.classes.form.Form');

class PublicIdentifiersForm extends Form {

	/** @var int The context id */
	var $_contextId;

	/** @var object The pub object the identifiers are edited of 
	 * (Submission, Issue)
	 */
	var $_pubObject;

	/** @var int The current stage id */
	var $_stageId;

	/**
	 * @var array Parameters to configure the form template.
	 */
	var $_formParams;

	/**
	 * Constructor.
	 * @param $pubObject object
	 * @param $stageId integer
	 * @param $formParams array
	 */
	function PublicIdentifiersForm($pubObject, $stageId = null, $formParams = null) {
		parent::Form('controllers/tab/pubIds/form/publicIdentifiersForm.tpl');

		$this->_pubObject = $pubObject;
		$this->_stageId = $stageId;
		$this->_formParams = $formParams;

		$request = Application::getRequest();
		$context = $request->getContext();
		$this->_contextId = $context->getId();

		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Fetch the HTML contents of the form.
	 * @param $request PKPRequest
	 * return string
	 */
	function fetch($request) {
		$templateMgr = TemplateManager::getManager($request);
		$pubIdPlugins =& PluginRegistry::loadCategory('pubIds', true, $this->getContextId());
		$templateMgr->assign('pubIdPlugins', $pubIdPlugins);
		$templateMgr->assign('pubObject', $this->getPubObject());
		$templateMgr->assign('stageId', $this->getStageId());
		$templateMgr->assign('formParams', $this->getFormParams());
		return parent::fetch($request);
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$pubObject = $this->getPubObject();
		import('lib.pkp.classes.plugins.PKPPubIdPluginHelper');
		$pubIdPluginHelper = new PKPPubIdPluginHelper();
		$pubIdPluginHelper->init($this->getContextId(), $this, $pubObject);
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
	 * Get the stage id
	 * @return int
	 */
	function getStageId() {
		return $this->_stageId;
	}

	/**
	 * Get the context id
	 * @return int
	 */
	function getContextId() {
		return $this->_contextId;
	}

	/**
	 * Get the extra form parameters.
	 */
	function getFormParams() {
		return $this->_formParams;
	}

	/**
	 * @copydoc Form::readInputData()
	 */
	function readInputData() {
		import('lib.pkp.classes.plugins.PKPPubIdPluginHelper');
		$pubIdPluginHelper = new PKPPubIdPluginHelper();
		$pubIdPluginHelper->readInputData($this->getContextId(), $this);
	}

	/**
	 * @copydoc Form::validate()
	 */
	function validate() {
		import('lib.pkp.classes.plugins.PKPPubIdPluginHelper');
		$pubIdPluginHelper = new PKPPubIdPluginHelper();
		$pubIdPluginHelper->validate($this->getContextId(), $this, $this->getPubObject());

		return parent::validate();
	}

	/**
	 * Save the metadata and store the catalog data for this published
	 * monograph.
	 */
	function execute($request) {
		parent::execute($request);
		
		$pubObject = $this->getPubObject();

		import('lib.pkp.classes.plugins.PKPPubIdPluginHelper');
		$pubIdPluginHelper = new PKPPubIdPluginHelper();
		$pubIdPluginHelper->execute($this->getContextId(), $this, $pubObject);

		if (is_a($pubObject, 'Article')) {
			$articleDao = DAORegistry::getDAO('ArticleDAO');
			$articleDao->updateObject($pubObject);
		} elseif (is_a($pubObject, 'Issue')) {
			$issueDao = DAORegistry::getDAO('IssueDAO');
			$issueDao->updateObject($pubObject);
			
			// Exclude or clear all issue objects pub ids
			$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true);
			if (is_array($pubIdPlugins)) {
				foreach ($pubIdPlugins as $pubIdPlugin) {
					$excludeSubmittName = 'excludeIssueObjects_' . $pubIdPlugin->getPubIdType();
					$clearSubmittName = 'clearIssueObjects_' . $pubIdPlugin->getPubIdType();
					$exclude = $clear = false;
					if ($request->getUserVar($excludeSubmittName)) $exclude = true;
					if ($request->getUserVar($clearSubmittName)) $clear = true;
					if ($exclude || $clear) {
						$pubIdPlugin->excludeAndClearIssueObjects($exclude, $clear, $pubObject);
					}
				}
			}
		}
	}
}

?>
