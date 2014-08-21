<?php

/**
 * @file plugins/generic/pln/SettingsForm.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SettingsForm
 * @ingroup plugins_generic_pln
 *
 * @brief Form for journal managers to modify PLN plugin settings
 */

import('lib.pkp.classes.form.Form');

class SettingsForm extends Form {
	
	/** @var $journalId int */
	var $_journal_id;
	
	/** @var $plugin object */
	var $_plugin;
	
	/**
	* Constructor
	* @param $plugin object
	* @param $journalId int
	*/
	function SettingsForm(&$plugin, $journalId) {
		
		$this->_journal_id = $journalId;
		$this->_plugin =& $plugin;           
		parent::Form($plugin->getTemplatePath() . DIRECTORY_SEPARATOR . 'settings.tpl');
	}
	
	/**
	 * Validate the form
	 *
	 * @return bool Whether or not the form validated
	 */
	function validate() {

		// these have been taken out for the time being...
		//$this->addCheck(new FormValidator($this, 'pln_network', 			'required', 'plugins.generic.pln.required.pln_network'));
		//$this->addCheck(new FormValidator($this, 'object_type', 			'required', 'plugins.generic.pln.required.object_type'));
		//$this->addCheck(new FormValidator($this, 'object_threshold', 		'required', 'plugins.generic.pln.required.object_threshold'));
		
		return parent::validate();
	}
	
	/**
	* Initialize form data.
	*/
	function initData() {
			
		if (!$this->_plugin->getSetting($this->_journal_id, 'terms_of_use')) {
			$this->_plugin->getServiceDocument($this->_journal_id);
		}

		//$this->setData('pln_network', $this->_plugin->getSetting($this->_journal_id, 'pln_network'));
		//$this->setData('object_type', $this->_plugin->getSetting($this->_journal_id, 'object_type'));
		//$this->setData('object_threshold', $this->_plugin->getSetting($this->_journal_id, 'object_threshold'));
		$this->setData('terms_of_use', unserialize($this->_plugin->getSetting($this->_journal_id, 'terms_of_use')));
		$this->setData('terms_of_use_agreement', unserialize($this->_plugin->getSetting($this->_journal_id, 'terms_of_use_agreement')));
	}
	
	/**
	* Assign form data to user-submitted data.
	*/
	function readInputData() {
	
		/*
		$this->readUserVars(array(
			'pln_network',
			'object_type',
			'object_threshold',
			
		));
		*/
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
		
		//$templateMgr->assign('pln_networks', unserialize(PLN_PLUGIN_NETWORKS));
		$templateMgr->assign('terms_of_use', unserialize($this->_plugin->getSetting($this->_journal_id, 'terms_of_use')));
		//$templateMgr->assign('supported_objects', unserialize(PLN_PLUGIN_DEPOSIT_SUPPORTED_OBJECTS));
		
		// form fields
		//$templateMgr->assign('pln_network', $this->getData('pln_network'));
		//$templateMgr->assign('object_type', $this->getData('object_type'));
		//$templateMgr->assign('object_threshold', $this->getData('object_threshold'));
		$templateMgr->assign('terms_of_use_agreement', $this->getData('terms_of_use_agreement'));
		
		// signals indicating plugin compatibility
		$templateMgr->assign('curl_support', function_exists('curl_init') ? __('plugins.generic.pln.settings.installed') : __('plugins.generic.pln.settings.missing'));
		$templateMgr->assign('zip_support', extension_loaded('zlib') ? __('plugins.generic.pln.settings.installed') : __('plugins.generic.pln.settings.missing'));
		
		parent::display();
	}  
	
	/**
	* Save settings.
	*/
	function execute() { 
	
		//$this->_plugin->updateSetting($this->_journal_id, 'pln_network', $this->getData('pln_network'), 'string');
		//$this->_plugin->updateSetting($this->_journal_id, 'object_type', $this->getData('object_type'), 'string');
		//$this->_plugin->updateSetting($this->_journal_id, 'object_threshold', $this->getData('object_threshold'), 'int');
		$this->_plugin->updateSetting($this->_journal_id, 'terms_of_use_agreement', serialize($this->getData('terms_of_use_agreement')), 'object');
	}
	
}
