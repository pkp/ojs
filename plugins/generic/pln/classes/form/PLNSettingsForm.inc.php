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

	/**
	 * @var $_journalId int
	 */
	var $_journalId;

	/** 
	 * @var $plugin object
	 */
	var $_plugin;

	/**
	 * Constructor
	 * @param $plugin object
	 * @param $journalId int
	 */
	function PLNSettingsForm(&$plugin, $journalId) {
		$this->_journalId = $journalId;
		$this->_plugin =& $plugin;
		parent::Form($plugin->getTemplatePath() . DIRECTORY_SEPARATOR . 'settings.tpl');
		$this->addCheck(new FormValidatorCustom($this, 'object_type', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.generic.pln.required.object_type', array(&$this, '_validateObjectType')));
		$this->addCheck(new FormValidatorCustom($this, 'object_threshold', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.generic.pln.required.object_threshold', array(&$this, '_validateObjectThreshold')));
	}

	/**
	 * Initialize form data.
	 */
	function initData() {		
		if (!$this->_plugin->getSetting($this->_journalId, 'terms_of_use')) {
			$this->_plugin->getServiceDocument($this->_journalId);
		}
		$this->setData('journal_uuid',$this->_plugin->getSetting($this->_journalId, 'journal_uuid'));
		$this->setData('object_type',$this->_plugin->getSetting($this->_journalId, 'object_type'));
		
		$object_threshold = $this->_plugin->getSetting($this->_journalId, 'object_threshold');
		$this->setData('object_threshold',($object_threshold==null?20:$object_threshold));
		$this->setData('terms_of_use', unserialize($this->_plugin->getSetting($this->_journalId, 'terms_of_use')));
		$this->setData('terms_of_use_agreement', unserialize($this->_plugin->getSetting($this->_journalId, 'terms_of_use_agreement')));
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
		if (Request::getUserVar('object_type')) {
			switch (Request::getUserVar('object_type')) {
				case PLN_PLUGIN_DEPOSIT_OBJECT_ARTICLE:
				case PLN_PLUGIN_DEPOSIT_OBJECT_ISSUE:
					$this->setData('object_type', Request::getUserVar('object_type'));
					break;
				default:
			}
		}
		if (Request::getUserVar('object_threshold') && (is_numeric(Request::getUserVar('object_threshold')))) {
			$this->setData('object_threshold', Request::getUserVar('object_threshold'));
		}	
	}

	/**
	 * @see Form::display()
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('terms_of_use', unserialize($this->_plugin->getSetting($this->_journalId, 'terms_of_use')));
		$templateMgr->assign('terms_of_use_agreement', $this->getData('terms_of_use_agreement'));
		$templateMgr->assign('object_type', $this->getData('object_type'));
		$templateMgr->assign('object_threshold', $this->getData('object_threshold'));
		$templateMgr->assign('object_type_options', array(
			__('plugins.generic.pln.objects.default'),
			//PLN_PLUGIN_DEPOSIT_OBJECT_ARTICLE => __('plugins.generic.pln.objects.article'), //disabling this option for pilot
			PLN_PLUGIN_DEPOSIT_OBJECT_ISSUE => __('plugins.generic.pln.objects.issue')
		));
		parent::display();
	}
	/**
	 * @see Form::execute()
	 */
	function execute() { 
		$this->_plugin->updateSetting($this->_journalId, 'terms_of_use_agreement', serialize($this->getData('terms_of_use_agreement')), 'object');
		$this->_plugin->updateSetting($this->_journalId, 'object_type', $this->getData('object_type'), 'string');
		$this->_plugin->updateSetting($this->_journalId, 'object_threshold', $this->getData('object_threshold'), 'int');
	}
	
	/**
	 * Form validator: verify a valid object type's been chosen
	 * @return boolean 
	 */
	function _validateObjectType() {
		switch ($this->getData('object_type')) {
			case PLN_PLUGIN_DEPOSIT_OBJECT_ARTICLE:
			case PLN_PLUGIN_DEPOSIT_OBJECT_ISSUE:
				return true;
				break;
		}
		return false;
	}
	
	/**
	 * Form validator: verify a valid object threshold's been chosen
	 * @return boolean 
	 */
	function _validateObjectThreshold() {
		return is_numeric($this->getData('object_threshold')) && ($this->getData('object_threshold') > 0);
	}
	
}
