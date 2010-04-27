<?php

/**
 * @file plugins/generic/sword/DepositPointForm.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DepositPointForm
 * @ingroup plugins_generic_sword
 *
 * @brief Form for journal managers to modify SWORD deposit points
 */

// $Id$


import('form.Form');

class DepositPointForm extends Form {

	/** @var $journalId int */
	var $journalId;

	/** @var $depositPointId int */
	var $depositPointId;

	/** @var $plugin object */
	var $plugin;

	/**
	 * Constructor
	 * @param $plugin object
	 * @param $journalId int
	 * @param $depositPointId int
	 */
	function DepositPointForm(&$plugin, $journalId, $depositPointId) {
		$this->journalId = $journalId;
		$this->depositPointId = $depositPointId;
		$this->plugin =& $plugin;

		parent::Form($plugin->getTemplatePath() . 'depositPointForm.tpl');
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$journalId = $this->journalId;
		$plugin =& $this->plugin;
		$depositPoints = $plugin->getSetting($journalId, 'depositPoints');
		$depositPoint = null;
		if (isset($depositPoints[$this->depositPointId])) $depositPoint = $depositPoints[$this->depositPointId];
		$this->setData('depositPoint', $depositPoint);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('depositPoint'));
	}

	function display() {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('depositPointId', $this->depositPointId);
		$templateMgr->assign('depositPointTypes', $this->plugin->getTypeMap());
		parent::display();
	}

	/**
	 * Save settings. 
	 */
	function execute() {
		$plugin =& $this->plugin;
		$journalId = $this->journalId;
		$depositPoints = $plugin->getSetting($journalId, 'depositPoints');

		if ($this->depositPointId !== null) $depositPoints[$this->depositPointId] = $this->getData('depositPoint');
		else $depositPoints[] = $this->getData('depositPoint');

		$plugin->updateSetting($journalId, 'depositPoints', $depositPoints);
	}
}

?>
