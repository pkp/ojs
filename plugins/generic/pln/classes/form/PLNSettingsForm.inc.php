<?php

/**
 * @file plugins/generic/pln/PLNSettingsForm.inc.php
 *
 * Copyright (c) 2013-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
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
		parent::Form($plugin->getTemplatePath() . 'settings.tpl');
	}

	/**
	 * @see Form::validate()
	 */
	function validate() {
		return parent::validate();
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$journalId = $this->_journalId;
		if (!$this->_plugin->getSetting($journalId, 'terms_of_use')) {
			$this->_plugin->getServiceDocument($journalId);
		}
		$this->setData('terms_of_use', unserialize($this->_plugin->getSetting($journalId, 'terms_of_use')));
		$this->setData('terms_of_use_agreement', unserialize($this->_plugin->getSetting($journalId, 'terms_of_use_agreement')));
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$terms_agreed = $this->getData('terms_of_use_agreement');
		if (Request::getUserVar('terms_agreed')) {
			foreach (array_keys(Request::getUserVar('terms_agreed')) as $term_agreed) {
				$terms_agreed[$term_agreed] = gmdate('c');
			}
			$this->setData('terms_of_use_agreement', $terms_agreed);
		}
	}
	
	/**
	 * Check for the prerequisites for the plugin, and return a translated 
	 * message for each missing requirement.
	 * 
	 * @return array
	 */
	function _checkPrerequisites() {
		$messages = array();
		
		if( ! $this->_plugin->php5Installed()) {
			// If php5 isn't available, then the other checks are not 
			// useful.
			$messages[] =  __('plugins.generic.pln.notifications.php5_missing');
			return $messages;
		}
		if( ! @include_once('Archive/Tar.php')) {
			$messages[] = __('plugins.generic.pln.notifications.archive_tar_missing');
		}
		if( ! $this->_plugin->curlInstalled()) {
			$messages[] = __('plugins.generic.pln.notifications.curl_missing');
		}
		if( ! $this->_plugin->zipInstalled()) {
			$messages = __('plugins.generic.pln.notifications.zip_missing');
		}
		if( ! $this->_plugin->cronEnabled()) {
			$messages = __('plugins.generic.pln.settings.acron_required');
		}
		return $messages;
	}

	/**
	 * @see Form::display()
	 */
	function display() {
		$journal =& Request::getJournal();
		$issn = '';
		if ($journal->getSetting('onlineIssn')) {
			$issn = $journal->getSetting('onlineIssn');
		} else if ($journal->getSetting('printIssn')) {
			$issn = $journal->getSetting('printIssn');
		}
		$hasIssn = false;
		if ($issn != '') {
			$hasIssn = true;
		}
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('hasIssn', $hasIssn);
		$templateMgr->assign('prerequisitesMissing', $this->_checkPrerequisites());
		$templateMgr->assign('journal_uuid', $this->_plugin->getSetting($this->_journalId, 'journal_uuid'));
		$templateMgr->assign('terms_of_use', unserialize($this->_plugin->getSetting($this->_journalId, 'terms_of_use')));
		$templateMgr->assign('terms_of_use_agreement', $this->getData('terms_of_use_agreement'));
		parent::display();
	}

	/**
	 * @see Form::execute()
	 */
	function execute() {
		$this->_plugin->updateSetting($this->_journalId, 'terms_of_use_agreement', serialize($this->getData('terms_of_use_agreement')), 'object');

		$pluginSettingsDao =& DAORegistry::getDAO('PluginSettingsDAO');
		$pluginSettingsDao->installSettings($this->_journalId, $this->_plugin->getName(), $this->_plugin->getContextSpecificPluginSettingsFile());
	}

}
