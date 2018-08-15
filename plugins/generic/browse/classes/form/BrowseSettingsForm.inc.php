<?php

/**
 * @file plugins/generic/browse/classes/form/BrowseSettingsForm.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SettingsForm
 * @ingroup plugins_generic_browse
 *
 * @brief Form for journal managers to setup browse plugin
 */


import('lib.pkp.classes.form.Form');

class BrowseSettingsForm extends Form {

	/** @var int */
	var $journalId;

	/** @var object */
	var $plugin;

	/**
	 * Constructor
	 * @param $plugin object
	 * @param $journalId int
	 */
	function __construct(&$plugin, $journalId) {
		$this->journalId = $journalId;
		$this->plugin =& $plugin;
		parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}
	
	/**
	 * Initialize form data.
	 */
	function initData() {
		$journalId = $this->journalId;
		$plugin = $this->plugin;

		$sectionDao = DAORegistry::getDAO('SectionDAO'); 
		$sectionsResultFactory = $sectionDao->getByJournalId($journalId);
		$sections = array();
		$identifyTypes = array();
		while ($section = $sectionsResultFactory->next()) {
			// consider all section titles
			$sections[$section->getId()] = $section->getLocalizedTitle();
			// several sections could have the same identify type => don't duplicate
			// and leave out the empty identify types
			if (!in_array($section->getLocalizedIdentifyType(), $identifyTypes) && $section->getLocalizedIdentifyType() != '') {
				$identifyTypes[$section->getId()] = $section->getLocalizedIdentifyType();
			}
		}
				
		asort($identifyTypes);
		$this->_data = array(
			'enableBrowseBySections' => $plugin->getSetting($journalId, 'enableBrowseBySections'),
			'enableBrowseByIdentifyTypes' => $plugin->getSetting($journalId, 'enableBrowseByIdentifyTypes'),
			'excludedSections' => $plugin->getSetting($journalId, 'excludedSections'),
			'excludedIdentifyTypes' => $plugin->getSetting($journalId, 'excludedIdentifyTypes'),
			'sections' => $sections,
			'identifyTypes' => $identifyTypes
		);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('enableBrowseBySections', 'enableBrowseByIdentifyTypes', 'excludedSections', 'excludedIdentifyTypes'));
	}

	/**
	 * Save settings.
	 */
	function execute() {
		$plugin =& $this->plugin;
		$journalId = $this->journalId;
		
		$plugin->updateSetting($journalId, 'enableBrowseBySections', $this->getData('enableBrowseBySections'), 'bool');
		$plugin->updateSetting($journalId, 'enableBrowseByIdentifyTypes', $this->getData('enableBrowseByIdentifyTypes'), 'bool');
		$plugin->updateSetting($journalId, 'excludedSections', $this->getData('excludedSections')?$this->getData('excludedSections'):array(), 'object');
		$excludedIdentifyTypesData = $this->getData('excludedIdentifyTypes');
		$excludedIdentifyTypes = array();
		$sectionDao = DAORegistry::getDAO('SectionDAO'); 
		$sectionsResultFactory = $sectionDao->getByJournalId($journalId);
		// consider all sections for exclusion with an excluded identify type 
		while ($section = $sectionsResultFactory->next()) {
			if ($section->getLocalizedIdentifyType() != '' && in_array($section->getLocalizedIdentifyType(), $excludedIdentifyTypesData)) {
				$excludedIdentifyTypes[] = $section->getId();
			}
		}
		$plugin->updateSetting($journalId, 'excludedIdentifyTypes', $excludedIdentifyTypes, 'object');
	}
}


