<?php

/**
 * @file plugins/generic/pln/PLNStatusForm.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PLNStatusForm
 * @ingroup plugins_generic_pln
 *
 * @brief Form for journal managers to check PLN plugin status
 */

import('lib.pkp.classes.form.Form');

class PLNStatusForm extends Form {

	/**
	 * @var $journalId int
	 */
	var $_journalId;

	/**
	 * @var $plugin Object
	 */
	var $_plugin;

	/**
	 * Constructor
	 * @param $plugin object
	 * @param $journalId int
	 */
	function PLNStatusForm(&$plugin, $journalId) {
		$this->_journalId = $journalId;
		$this->_plugin =& $plugin;           
		parent::Form($this->_plugin->getTemplatePath() . DIRECTORY_SEPARATOR . 'status.tpl');
	}

	/**
	 * @see Form::display()
	 */
	function display() {
		$depositDao =& DAORegistry::getDAO('DepositDAO');
		$journal =& Request::getJournal();
		$networkStatus = $this->_plugin->getSetting($journal->getId(), 'pln_accepting');
		$networkStatusMessage = $this->_plugin->getSetting($journal->getId(), 'pln_accepting_message');
		$rangeInfo = Handler::getRangeInfo('deposits');
		
		if (!$networkStatusMessage) {
			if ($networkStatus === true) {
				$networkStatusMessage = __(PLN_PLUGIN_NOTIFICATION_PLN_ACCEPTING);
			} else {
				$networkStatusMessage = __(PLN_PLUGIN_NOTIFICATION_PLN_NOT_ACCEPTING);
			}
		}
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('deposits', $depositDao->getDepositsByJournalId($journal->getId(),$rangeInfo));
		$templateMgr->assign('networkStatus', $networkStatus);
		$templateMgr->assign('networkStatusMessage', $networkStatusMessage);
		parent::display();
	}
	
}
