<?php

/**
 * @file plugins/generic/lucene/classes/form/LuceneSettingsForm.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class LuceneSettingsForm
 * @ingroup plugins_generic_lucene_classes_form
 *
 * @brief Form to configure Lucene/Solr search.
 */


import('lib.pkp.classes.form.Form');
import('plugins.generic.lucene.classes.EmbeddedServer');

// These are the first few letters of an md5 of '##placeholder##'.
// FIXME: Any better idea how to prevent a password clash?
define('LUCENE_PLUGIN_PASSWORD_PLACEHOLDER', '##5ca39841ab##');

class LuceneSettingsForm extends Form {

	/** @var LucenePlugin */
	var $_plugin;

	/** @var EmbeddedServer */
	var $_embeddedServer;

	/**
	 * Constructor
	 * @param $plugin LucenePlugin
	 */
	function __construct(&$plugin, &$embeddedServer) {
		$this->_plugin =& $plugin;
		$this->_embeddedServer =& $embeddedServer;
		parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));

		// Server configuration.
		$this->addCheck(new FormValidatorUrl($this, 'searchEndpoint', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.generic.lucene.settings.searchEndpointRequired'));
		// The username is used in HTTP basic authentication and according to RFC2617 it therefore may not contain a colon.
		$this->addCheck(new FormValidatorRegExp($this, 'username', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.generic.lucene.settings.usernameRequired', '/^[^:]+$/'));
		$this->addCheck(new FormValidator($this, 'password', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.generic.lucene.settings.passwordRequired'));
		$this->addCheck(new FormValidator($this, 'instId', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.generic.lucene.settings.instIdRequired'));

		// Search feature configuration.
		$this->addCheck(new FormValidatorInSet($this, 'autosuggestType', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.generic.lucene.settings.internalError', array_keys($this->_getAutosuggestTypes())));
		$binaryFeatureSwitches = $this->_getFormFields(true);
		foreach($binaryFeatureSwitches as $binaryFeatureSwitch) {
			$this->addCheck(new FormValidatorBoolean($this, $binaryFeatureSwitch, 'plugins.generic.lucene.settings.internalError'));
		}

		// Index administration.
		$journalsToReindex = array_keys($this->_getJournalsToReindex());
		$this->addCheck(new FormValidatorInSet($this, 'journalToReindex', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.generic.lucene.settings.internalError', $journalsToReindex));
	}


	//
	// Implement template methods from Form.
	//
	/**
	 * @see Form::initData()
	 */
	function initData() {
		$plugin =& $this->_plugin;
		foreach ($this->_getFormFields() as $fieldName) {
			$this->setData($fieldName, $plugin->getSetting(0, $fieldName));
		}
		// We do not echo back the real password.
		$this->setData('password', LUCENE_PLUGIN_PASSWORD_PLACEHOLDER);
	}

	/**
	 * @see Form::readInputData()
	 */
	function readInputData() {
		// Read regular form data.
		$this->readUserVars($this->_getFormFields());
		$request = Application::getRequest();

		// Set the password to the one saved in the DB
		// if we only got the placehlder from the form.
		$password = $request->getUserVar('password');
		if ($password === LUCENE_PLUGIN_PASSWORD_PLACEHOLDER) {
			$plugin =& $this->_plugin;
			$password = $plugin->getSetting(0, 'password');
		}
		$this->setData('password', $password);
	}

	/**
	 * @see Form::fetch()
	 */
	function fetch($request, $template = null, $display = false) {
		// Prepare auto-suggest.
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('autosuggestTypes', $this->_getAutosuggestTypes());

		// Prepare ranking-by-metric/sorting-by-metric.
		$metricName = $this->_getDefaultMetric();
		$templateMgr->assign('metricName', $metricName);
		$templateMgr->assign('noMainMetric', empty($metricName));
		$filesDir = Config::getVar('files', 'files_dir');
		$filePath = $filesDir . DIRECTORY_SEPARATOR . 'lucene' . DIRECTORY_SEPARATOR . 'data';
		$templateMgr->assign('canWriteBoostFile', is_writable($filePath));

		// Prepare index rebuild.
		$templateMgr->assign('journalsToReindex', $this->_getJournalsToReindex());

		// Prepare solr server management.
		$embeddedServer = $this->_embeddedServer;
		$templateMgr->assign('serverIsAvailable', $embeddedServer->isAvailable());
		$templateMgr->assign('serverIsRunning', $embeddedServer->isRunning());

		parent::fetch($request, $template, $display);
	}

	/**
	 * Execute the form.
	 */
	function execute() {
		$plugin =& $this->_plugin;
		$formFields = $this->_getFormFields();
		$formFields[] = 'password';
		foreach($formFields as $formField) {
			$plugin->updateSetting(0, $formField, $this->getData($formField), 'string');
		}
	}


	//
	// Private helper methods
	//
	/**
	 * Return the field names of this form.
	 * @param $booleanOnly boolean Return only binary
	 *  switches.
	 * @return array
	 */
	function _getFormFields($booleanOnly = false) {
		$booleanFormFields = array(
			'autosuggest', 'spellcheck', 'pullIndexing',
			'simdocs', 'highlighting', 'facetCategoryDiscipline',
			'facetCategorySubject', 'facetCategoryType',
			'facetCategoryCoverage', 'facetCategoryJournalTitle',
			'facetCategoryAuthors', 'facetCategoryPublicationDate',
			'customRanking', 'instantSearch',
			'rankingByMetric', 'sortingByMetric', 'useProxySettings'
		);
		$otherFormFields = array(
			'searchEndpoint', 'username', 'instId',
			'autosuggestType'
		);
		if ($booleanOnly) {
			return $booleanFormFields;
		} else {
			return array_merge($booleanFormFields, $otherFormFields);
		}
	}

	/**
	 * Return a list of auto-suggest types.
	 * @return array
	 */
	function _getAutosuggestTypes() {
		return array(
			SOLR_AUTOSUGGEST_SUGGESTER => __('plugins.generic.lucene.settings.autosuggestTypeSuggester'),
			SOLR_AUTOSUGGEST_FACETING => __('plugins.generic.lucene.settings.autosuggestTypeFaceting')
		);
	}

	/**
	 * Return a list of journals that can be re-indexed
	 * with a default option "all journals".
	 * @return array An associative array of journal IDs and names.
	 */
	function _getJournalsToReindex() {
		static $journalsToReindex;

		if (is_null($journalsToReindex)) {
			$journalDao = DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
			$journalsToReindex = array(
				'' => __('plugins.generic.lucene.settings.indexRebuildAllJournals')
			);
			foreach($journalDao->getTitles(true) as $journalId => $journalName) {
				$journalsToReindex[$journalId] = __('plugins.generic.lucene.settings.indexRebuildJournal', array('journalName' => $journalName));
			}
		}

		return $journalsToReindex;
	}

	/**
	 * Return the default metric for the current request context.
	 * @return null|string a metric identifier or null
	 */
	function _getDefaultMetric() {
		$application = Application::getApplication();
		$metricType = $application->getDefaultMetricType();
		if (empty($metricType)) return null;
		$metricNames = $application->getMetricTypes(true);
		if (!isset($metricNames[$metricType])) return null;
		return $metricNames[$metricType];
	}
}

?>
