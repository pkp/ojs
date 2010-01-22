<?php

/**
 * @file classes/rt/ojs/form/ContextForm.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ContextForm
 * @ingroup rt_ojs_form
 *
 * @brief Form to change metadata information for an RT context.
 */

// $Id$


import('form.Form');

class ContextForm extends Form {

	/** @var int the ID of the context */
	var $contextId;

	/** @var Context current context */
	var $context;

	/** @var int ID of the version */
	var $versionId;

	/**
	 * Constructor.
	 */
	function ContextForm($contextId, $versionId) {
		parent::Form('rtadmin/context.tpl');
		$this->addCheck(new FormValidatorPost($this));

		$rtDao = &DAORegistry::getDAO('RTDAO');
		$this->context = &$rtDao->getContext($contextId);

		$this->versionId = $versionId;

		if (isset($this->context)) {
			$this->contextId = $contextId;
		}
	}

	/**
	 * Initialize form data from current context.
	 */
	function initData() {
		if (isset($this->context)) {
			$context = &$this->context;
			$this->_data = array(
				'abbrev' => $context->getAbbrev(),
				'title' => $context->getTitle(),
				'order' => $context->getOrder(),
				'description' => $context->getDescription(),
				'authorTerms' => $context->getAuthorTerms(),
				'citedBy' => $context->getCitedBy(),
				'geoTerms' => $context->getGeoTerms(),
				'defineTerms' => $context->getDefineTerms()
			);
		} else {
			$this->_data = array();
		}
	}

	/**
	 * Display the form.
	 */
	function display() {
		$journal = &Request::getJournal();
		$templateMgr = &TemplateManager::getManager();

		$templateMgr->assign('versionId', $this->versionId);

		if (isset($this->context)) {
			$templateMgr->assign_by_ref('context', $this->context);
			$templateMgr->assign('contextId', $this->contextId);
		}

		$templateMgr->assign('helpTopicId', 'journal.managementPages.readingTools.contexts');
		parent::display();
	}


	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(
			array(
				'abbrev',
				'title',
				'order',
				'description',
				'authorTerms',
				'citedBy',
				'defineTerms'
			)
		);
	}

	/**
	 * Save changes to context.
	 * @return int the context ID
	 */
	function execute() {
		$rtDao = &DAORegistry::getDAO('RTDAO');

		$context = $this->context;
		if (!isset($context)) {
			$context = &new RTContext();
			$context->setVersionId($this->versionId);
		}

		$context->setTitle($this->getData('title'));
		$context->setAbbrev($this->getData('abbrev'));
		$context->setCitedBy($this->getData('citedBy')==true);
		$context->setAuthorTerms($this->getData('authorTerms')==true);
		$context->setGeoTerms($this->getData('geoTerms')==true);
		$context->setDefineTerms($this->getData('defineTerms')==true);
		$context->setDescription($this->getData('description'));
		if (!isset($this->context)) $context->setOrder(-1);

		if (isset($this->context)) {
			$rtDao->updateContext($context);
		} else {
			$rtDao->insertContext($context);
			$this->contextId = $context->getContextId();
		}

		return $this->contextId;
	}

}

?>
