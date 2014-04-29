<?php

/**
 * @file plugins/generic/sword/DepositPointForm.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DepositPointForm
 * @ingroup plugins_generic_sword
 *
 * @brief Form for journal managers to modify SWORD deposit points
 */

define('SWORD_PASSWORD_SLUG', '******');

import('lib.pkp.classes.form.Form');

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
		if (isset($depositPoints[$this->depositPointId])) {
			$depositPoint = $depositPoints[$this->depositPointId];
			// Don't echo passwords back to the user.
			$depositPoint['password'] = SWORD_PASSWORD_SLUG;
		}
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

		if ($this->depositPointId !== null) {
			$depositPoint = $this->getData('depositPoint');
			if ($depositPoint['password'] == SWORD_PASSWORD_SLUG && isset($depositPoints[$this->depositPointId])) {
				// The old password was not changed; preserve it
				$depositPoint['password'] = $depositPoints[$this->depositPointId]['password'];
			}
			$depositPoints[$this->depositPointId] = $depositPoint;
		}
		else $depositPoints[] = $this->getData('depositPoint');

		$plugin->updateSetting($journalId, 'depositPoints', $depositPoints);
	}
}

?>
