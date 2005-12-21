<?php

/**
 * GoogleScholarSettingsForm.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * Form for journal managers to modify Google Scholar gateway settings
 *
 * $Id$
 */

import('form.Form');

class GoogleScholarSettingsForm extends Form {
	/** @var $journalId int */
	var $journalId;

	/** @var $plugin object */
	var $plugin;

	function elementsAreEmails($elements) {
		if (!is_array($elements)) return false;
		$regexp = FormValidatorEmail::getRegexp();
		foreach ($elements as $element) {
			if (!String::regexp_match($regexp, $element)) {
				echo "$element failed $regexp<br/>\n";
				return false;
			}
		}
		return true;
	}

	/**
	 * Constructor
	 * @param $journalId int
	 */
	function GoogleScholarSettingsForm(&$plugin, $journalId) {
		parent::Form($plugin->getTemplatePath() . 'settingsForm.tpl');
	
		$this->addCheck(new FormValidator($this, 'publisherName', 'required', 'plugins.gateways.googleScholar.errors.noPublisherName'));
		$this->addCheck(new FormValidatorArray($this, 'contact', 'required', 'plugins.gateways.googleScholar.errors.noContacts'));
		$this->addCheck(new FormValidatorCustom($this, 'contact', 'required', 'plugins.gateways.googleScholar.errors.noContacts', array(&$this, 'elementsAreEmails')));

		$this->journalId = $journalId;
		$this->plugin =& $plugin;
	}
	
	/**
	 * Initialize form data from current group group.
	 */
	function initData() {
		$journalId = $this->journalId;
		$plugin =& $this->plugin;

		$contact = $plugin->getSetting($journalId, 'contact');
		$this->_data = array(
			'publisherName' => $plugin->getSetting($journalId, 'publisher-name'),
			'contact' => &$contact,
			'publisherLocation' => $plugin->getSetting($journalId, 'publisher-location'),
			'publisherResultName' => $plugin->getSetting($journalId, 'publisher-result-name')
		);

		for ($i=0; $i<5; $i++) {
			if (Request::getUserVar("deleteContact-$i")) {
				$this->readInputData();
				$contact = $this->getData('contact');
				array_splice($contact, $i, 1);
				break;
			}
		}
		if (Request::getUserVar('addContact')) {
			$this->readInputData();
			$contact = $this->getData('contact');
			if (!is_array($contact)) $contact = array();
			array_push($contact, '');
		}

	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('publisherName', 'contact', 'publisherLocation', 'publisherResultName'));
	}
	
	/**
	 * Save group. 
	 */
	function execute() {
		$plugin =& $this->plugin;
		$journalId = $this->journalId;
		$plugin->updateSetting($journalId, 'publisher-name', $this->getData('publisherName'));
		$plugin->updateSetting($journalId, 'contact', $this->getData('contact'));
		$plugin->updateSetting($journalId, 'publisher-location', $this->getData('publisherLocation'));
		$plugin->updateSetting($journalId, 'publisher-result-name', $this->getData('publisherResultName'));
	}
	
}

?>
