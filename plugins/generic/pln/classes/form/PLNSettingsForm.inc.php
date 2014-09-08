<?php

/**
 * @file plugins/generic/pln/PLNSettingsForm.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PLNSettingsForm
 * @ingroup plugins_generic_pln
 *
 * @brief Form for journal managers to modify PLN plugin settings
 */

import('lib.pkp.classes.form.Form');

class PLNSettingsForm extends Form {
	
	/** @var $journalId int */
	var $_journal_id;
	
	/** @var $plugin object */
	var $_plugin;
	
	/**
	* Constructor
	* @param $plugin object
	* @param $journal_id int
	*/
	function PLNSettingsForm(&$plugin, $journal_id) {
		
		$this->_journal_id = $journal_id;
		$this->_plugin =& $plugin;           
		parent::Form($plugin->getTemplatePath() . DIRECTORY_SEPARATOR . 'settings.tpl');
	}
	
	/**
	 * Validate the form
	 *
	 * @return bool Whether or not the form validated
	 */
	function validate() {
		return parent::validate();
	}
	
	/**
	* Initialize form data.
	*/
	function initData() {		
		if (!$this->_plugin->getSetting($this->_journal_id, 'terms_of_use')) {
			$this->_plugin->getServiceDocument($this->_journal_id);
		}
		$this->setData('terms_of_use', unserialize($this->_plugin->getSetting($this->_journal_id, 'terms_of_use')));
		$this->setData('terms_of_use_agreement', unserialize($this->_plugin->getSetting($this->_journal_id, 'terms_of_use_agreement')));
	}
	
	/**
	* Assign form data to user-submitted data.
	*/
	function readInputData() {
		$terms_agreed = $this->getData('terms_of_use_agreement');
		if (Request::getUserVar('terms_agreed')) {
			foreach(array_keys(Request::getUserVar('terms_agreed')) as $term_agreed) {
				$terms_agreed[$term_agreed] = TRUE;
			}
			$this->setData('terms_of_use_agreement', $terms_agreed);
		}
	}
	
	/**
	 * @see Form::fetch()
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('terms_of_use', unserialize($this->_plugin->getSetting($this->_journal_id, 'terms_of_use')));
		$templateMgr->assign('terms_of_use_agreement', $this->getData('terms_of_use_agreement'));
		parent::display();
	}  
	
	/**
	* Save settings.
	*/
	function execute() { 
		$this->_plugin->updateSetting($this->_journal_id, 'terms_of_use_agreement', serialize($this->getData('terms_of_use_agreement')), 'object');
	}
	
}
