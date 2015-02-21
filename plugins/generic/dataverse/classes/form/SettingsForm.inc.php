<?php

/**
 * @file plugins/generic/dataverse/classes/form/SettingsForm.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SettingsForm
 * @ingroup plugins_generic_dataverse
 *
 * @brief Plugin settings: set data policies, define terms of use, configure workflows. 
 */

import('lib.pkp.classes.form.Form');
import('plugins.generic.tinymce.TinyMCEPlugin');

class SettingsForm extends Form {

	/** @var $journalId int */
	var $_journalId;

	/** @var $plugin object */
	var $_plugin;

	/**
	 * Constructor. 
	 * @param $plugin DataversePlugin
	 * @param $journalId int
	 * @see Form::Form()
	 */
	function settingsForm(&$plugin, $journalId) {
		$this->_journalId = $journalId;
		$this->_plugin =& $plugin;
		
		// Citation formats
		$this->_citationFormats = array(
				DATAVERSE_PLUGIN_CITATION_FORMAT_APA => __('plugins.generic.dataverse.settings.citationFormat.apa'),
			);
		
		// Study release options
		$this->_studyReleaseOptions = array(
				DATAVERSE_PLUGIN_RELEASE_ARTICLE_ACCEPTED => __('plugins.generic.dataverse.settings.studyReleaseSubmissionAccepted'),
				DATAVERSE_PLUGIN_RELEASE_ARTICLE_PUBLISHED => __('plugins.generic.dataverse.settings.studyReleaseArticlePublished')
		);		

		// Public id plugins
		$this->_pubIdTypes = array();
		$pubIdPlugins =& PluginRegistry::loadCategory('pubIds', true, $this->_journalId);
		if (is_array($pubIdPlugins)) {
			foreach ($pubIdPlugins as $pubIdPlugin) {
				// Load the formatter
				$this->_pubIdTypes[$pubIdPlugin->getName()] = $pubIdPlugin->getDisplayName();
			}
		}
		
		parent::Form($plugin->getTemplatePath() . 'settingsForm.tpl');
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidator($this, 'dataAvailability', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.generic.dataverse.settings.dataAvailabilityRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'termsOfUse', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.generic.dataverse.settings.termsOfUseRequired', array(&$this, '_validateTermsOfUse')));
		$this->addCheck(new FormValidatorCustom($this, 'termsOfUse', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.generic.dataverse.settings.dataverseTermsOfUseError', array(&$this, '_validateDataverseTermsOfUse'))); 
		$this->addCheck(new FormValidatorPost($this));		
	}

	/**
	 * @see Form::initData()
	 */
	function initData() {
		$plugin =& $this->_plugin;
		$journal =& Request::getJournal();

		// Populate form with plugin settings or default values
		$this->setData('dataAvailability', 
						$plugin->getSetting($journal->getId(), 'dataAvailability') ? 
						$plugin->getSetting($journal->getId(), 'dataAvailability') : 
					 __('plugins.generic.dataverse.settings.default.dataAvailabilityPolicy', array('journal' => $journal->getLocalizedTitle()))
					);

		$this->setData('fetchTermsOfUse', $plugin->getSetting($journal->getId(), 'fetchTermsOfUse'));
		$this->setData('termsOfUse',			$plugin->getSetting($journal->getId(), 'termsOfUse'));
		$this->setData('requireData',			$plugin->getSetting($journal->getId(), 'requireData'));
		
		// Get citation formats
		$this->setData('citationFormats', $this->_citationFormats);
		$citationFormat = $this->_plugin->getSetting($journal->getId(), 'citationFormat');
		if (isset($citationFormat) && array_key_exists($citationFormat, $this->_citationFormats)) {
			$this->setData('citationFormat', $citationFormat);
		}
		
		// Get pub id format plugins
		$this->setData('pubIdTypes', $this->_pubIdTypes);
		$pubIdPlugin = $this->_plugin->getSetting($journal->getId(), 'pubIdPlugin');
		if (isset($pubIdPlugin) && array_key_exists($pubIdPlugin, $this->_pubIdTypes)) {
			$this->setData('pubIdPlugin', $pubIdPlugin);
		} 

		$this->setData('studyReleaseOptions', $this->_studyReleaseOptions);
		$studyRelease = $this->_plugin->getSetting($journal->getId(), 'studyRelease');
		if (array_key_exists($studyRelease, $this->_studyReleaseOptions)) {
			$this->setData('studyRelease', $studyRelease);
		}
		
	}

	/**
	 * @see Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(
				array(
				'dataAvailability',
				'fetchTermsOfUse',
				'termsOfUse',
				'citationFormat',
				'pubIdPlugin',
				'requireData',
				'studyRelease',
			)
		);		
	}
	
	/**
	 * @see Form::fetch()
	 */
	function fetch(&$request, $template = null, $display = false) {
		$journal =& Request::getJournal();
		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$sections =& $sectionDao->getJournalSections($this->_journalId);
		
		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->assign('sections', $sections->toArray());
		$templateMgr->assign('citationFormats', $this->_citationFormats);
		$templateMgr->assign('pubIdTypes', $this->_pubIdTypes); 
		$templateMgr->assign('studyReleaseOptions', $this->_studyReleaseOptions);
		$templateMgr->assign('authorGuidelinesContent',			__('plugins.generic.dataverse.settings.default.authorGuidelines', array('journal' => $journal->getLocalizedTitle())));
		$templateMgr->assign('checklistContent',						__('plugins.generic.dataverse.settings.default.checklist', array('journal' => $journal->getLocalizedTitle())));		 
		$templateMgr->assign('reviewPolicyContent',					__('plugins.generic.dataverse.settings.default.reviewPolicy'));
		$templateMgr->assign('reviewGuidelinesContent',			__('plugins.generic.dataverse.settings.default.reviewGuidelines'));		 
		$templateMgr->assign('copyeditInstructionsContent', __('plugins.generic.dataverse.settings.default.copyeditInstructions'));
		
		parent::fetch($request, $template, $display);
	}	 

	/**
	 * @see Form::execute()
	 */
	function execute() { 
		$plugin =& $this->_plugin;

		$plugin->updateSetting($this->_journalId, 'dataAvailability', $this->getData('dataAvailability'), 'string');		
		$plugin->updateSetting($this->_journalId, 'fetchTermsOfUse',	$this->getData('fetchTermsOfUse'),	'bool');		
		$plugin->updateSetting($this->_journalId, 'termsOfUse',				$this->getData('termsOfUse'),				'string');		
		$plugin->updateSetting($this->_journalId, 'citationFormat',		$this->getData('citationFormat'),		'string');		
		$plugin->updateSetting($this->_journalId, 'pubIdPlugin',			$this->getData('pubIdPlugin'),			'string');		
		$plugin->updateSetting($this->_journalId, 'requireData',			$this->getData('requireData'),			'bool');		
		$plugin->updateSetting($this->_journalId, 'studyRelease',			$this->getData('studyRelease'),			'int');		 
		// Store DV TOU as a backup if not accessible via API. Update when fetched from API.
		if ($this->getData('dvTermsOfUse')) {
			$plugin->updateSetting($this->_journalId, 'dvTermsOfUse', $this->getData('dvTermsOfUse'), 'string');
		}
	}
	
	/**
	 * Form validator: provide terms of use OR fetch terms from configured Dataverse
	 * @return boolean 
	 */
	function _validateTermsOfUse() {
		// If JM chooses to define own terms, verify that terms of use are provided 
		return $this->getData('fetchTermsOfUse') === "1" || $this->getData('termsOfUse');
	}
	
	/**
	 * Form validator: if terms of use to be fetched from Dataverse, verify terms
	 * can be retrieved. 
	 * @return boolean true if terms can be retrieved from configured Dataverse
	 */
	function _validateDataverseTermsOfUse() {
		if ($this->getData('fetchTermsOfUse') === "0") return true;

		// Otherwise, try to fetch terms of use
		$dvTermsOfUse = $this->_plugin->getTermsOfUse();
		if (!$dvTermsOfUse) return false;
		
		// Store for faster retrieval on execute
		$this->setData('dvTermsOfUse', $dvTermsOfUse);
		return true;
	}

}
