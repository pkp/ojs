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
	
	/** @var $journalId int */
	var $_journal_id;
	
	/** @var $plugin object */
	var $_plugin;
	
	/**
	* Constructor
	* @param $plugin object
	* @param $journal_id int
	*/
	function PLNStatusForm(&$plugin, $journal_id) {
		$this->_journal_id = $journal_id;
		$this->_plugin =& $plugin;           
		parent::Form($this->_plugin->getTemplatePath() . DIRECTORY_SEPARATOR . 'status.tpl');
	}
	
	/**
	 * @see Form::fetch()
	 */
	function display() {
		$deposit_dao =& DAORegistry::getDAO('DepositDAO');
		$journal =& Request::getJournal();
		$network_status = $this->_plugin->getSetting($journal->getId(), 'pln_accepting');
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('deposits', $deposit_dao->getDepositsByJournalId($journal->getId()));
		$templateMgr->assign('network_status', ($network_status?PLN_PLUGIN_NOTIFICATION_PLN_ACCEPTING:PLN_PLUGIN_NOTIFICATION_PLN_NOT_ACCEPTING));
		parent::display();
	}  
	
}
