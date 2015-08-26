<?php

/**
 * @file plugins/generic/pln/PLNSettingsForm.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
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

		$this->addCheck(new FormValidatorUrl($this, 'pln_network', 'required', 'plugins.generic.pln.settings.pln_network_invalid'));
		$this->addCheck(new FormValidatorCustom($this, 'pln_network', 'required', 'plugins.generic.pln.settings.pln_network_path_invalid',
			create_function('$network', 'return PLNSettingsForm::validateNetwork($network);')
		));
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
		$this->setData('pln_network', $this->_plugin->getSetting($journalId, 'pln_network'));
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
		if (Request::getUserVar('pln_network')) {
			$this->setData('pln_network', Request::getUserVar('pln_network'));
		}
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
		$templateMgr->assign('journal_uuid', $this->_plugin->getSetting($this->_journalId, 'journal_uuid'));
		$templateMgr->assign('terms_of_use', unserialize($this->_plugin->getSetting($this->_journalId, 'terms_of_use')));
		$templateMgr->assign('terms_of_use_agreement', $this->getData('terms_of_use_agreement'));
		parent::display();
	}

	/**
	 * When a journal manager changes the network URL, we assume that the new
	 * network has none of the deposits, and that it is using different terms
	 * of use. Reset all of the deposits to their initial state to force them
	 * to be deposited to the new network, and remove the terms of use and
	 * agreement data. A journal manager must accept the terms of use for the
	 * new network.
	 */
	function _networkChanged() {
		/** @var DepositDAO */
		$depositDao =& DAORegistry::getDAO('DepositDAO');

		$deposits =& $depositDao->getDepositsByJournalId($this->_journalId);
		foreach ($deposits->toArray() as $deposit) {
			$deposit->setStatus(PLN_PLUGIN_DEPOSIT_STATUS_NEW);
			$depositDao->updateDeposit($deposit);
		}
		$this->_plugin->updateSetting($this->_journalId, 'terms_of_use', serialize(array()), 'object');
		$this->_plugin->updateSetting($this->_journalId, 'terms_of_use_agreement', serialize(array()), 'object');
		$this->_plugin->updateSetting($this->_journalId, 'pln_accepting', false, 'bool');
		$this->_plugin->getServiceDocument($this->_journalId);
	}

	/**
	 * Custom network URL validation function.
	 *
	 * The PLN staging server must be run at the root of a domain name, and must
	 * be http or https. An optional port may be specified.
	 *
	 * http://example.com:8080/ is OK
	 * http://pln.example.com/path/to/pln is not OK.
	 */
	static function validateNetwork($url) {
		// $url has already been validated as a URL at this point.
		$parts = parse_url($url);
		if( !preg_match('/^https?$/', $parts['scheme']))
			return false;
		if(isset($parts['user']) 
				|| isset($parts['pass'])
				|| isset($parts['query'])
				|| isset($parts['fragment'])) {
			return false;
		}
		return true;
	}

	/**
	 * @see Form::execute()
	 */
	function execute() {
		$this->_plugin->updateSetting($this->_journalId, 'terms_of_use_agreement', serialize($this->getData('terms_of_use_agreement')), 'object');

		if($this->getData('pln_network') != $this->_plugin->getSetting($this->_journalId, 'pln_network')) {
			$url = $this->getData('pln_network');

			// If the URL ends in a slash, remove the slash.
			if(substr($url, -1) == '/')
				$url = substr($url, 0, -1);
			$this->_plugin->updateSetting($this->_journalId, 'pln_network', $url, 'string');
			$this->_networkChanged();
		}

		$pluginSettingsDao =& DAORegistry::getDAO('PluginSettingsDAO');
		$pluginSettingsDao->installSettings($this->_journalId, $this->_plugin->getName(), $this->_plugin->getContextSpecificPluginSettingsFile());
	}

}
