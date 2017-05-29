<?php

/**
 * @file plugins/generic/openAIRE/OpenAIREPlugin.inc.php
 *
 * Copyright (c) 2013-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Contributed by 4Science (http://www.4science.it).
 * 
 * @class OpenAIREPlugin
 * @ingroup plugins_generic_openAIRE
 *
 * @brief OpenAIRE plugin class
 */

import('lib.pkp.classes.plugins.GenericPlugin');

define('OPENAIRE_API_URL', 'http://api.openaire.eu/');
define('OPENAIRE_SEARCH_PROJECTS', 'search/projects');

class OpenAIREPlugin extends GenericPlugin {

	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True if plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		//$this->addLocaleData();
		if ($success && $this->getEnabled()) {
			$this->import('OpenAIREDAO');
			$openAIREDao = new OpenAIREDAO();
			DAORegistry::registerDAO('OpenAIREDAO', $openAIREDao);

			// Insert openAIRE callback
			HookRegistry::register('LoadHandler', array(&$this, 'setupCallbackHandler'));

			// Insert new field into author metadata submission form (submission step 3) and metadata form
			HookRegistry::register('Templates::Author::Submit::AdditionalMetadata', array($this, 'metadataFieldEdit'));
			HookRegistry::register('Templates::Submission::MetadataEdit::AdditionalMetadata', array($this, 'metadataFieldEdit'));
			// Consider the new field in the metadata view
			HookRegistry::register('Templates::Submission::Metadata::Metadata::AdditionalMetadata', array($this, 'metadataFieldView'));

			// Hook for initData in two forms -- init the new field
			HookRegistry::register('metadataform::initdata', array($this, 'metadataInitData'));
			HookRegistry::register('authorsubmitstep3form::initdata', array($this, 'metadataInitData'));

			// Hook for readUserVars in two forms -- consider the new field entry
			HookRegistry::register('metadataform::readuservars', array($this, 'metadataReadUserVars'));
			HookRegistry::register('authorsubmitstep3form::readuservars', array($this, 'metadataReadUserVars'));

			// Hook for execute in two forms -- consider the new field in the article settings
			HookRegistry::register('authorsubmitstep3form::execute', array($this, 'metadataExecute'));
			HookRegistry::register('metadataform::execute', array($this, 'metadataExecute'));

			// Hook for save in two forms -- add validation for the new field
			HookRegistry::register('authorsubmitstep3form::Constructor', array($this, 'addCheck'));
			HookRegistry::register('metadataform::Constructor', array($this, 'addCheck'));

			// Consider the new field for ArticleDAO for storage
			HookRegistry::register('articledao::getAdditionalFieldNames', array($this, 'articleSubmitGetFieldNames'));

			// Add OpenAIRE set to OAI results
			HookRegistry::register('OAIDAO::getJournalSets', array($this, 'sets'));
			HookRegistry::register('JournalOAI::identifiers', array($this, 'recordsOrIdentifiers'));
			HookRegistry::register('JournalOAI::records', array($this, 'recordsOrIdentifiers'));
			HookRegistry::register('OAIDAO::_returnRecordFromRow', array($this, 'addSet'));
			HookRegistry::register('OAIDAO::_returnIdentifierFromRow', array($this, 'addSet'));

			 // Change Dc11Desctiption -- consider OpenAIRE elements relation, rights and date
			HookRegistry::register('Dc11SchemaArticleAdapter::extractMetadataFromDataObject', array($this, 'changeDc11Desctiption'));

			// consider OpenAIRE articles in article tombstones
			HookRegistry::register('ArticleTombstoneManager::insertArticleTombstone', array($this, 'insertOpenAIREArticleTombstone'));

		}
		return $success;
	}

	function getDisplayName() {
		return __('plugins.generic.openAIRE.displayName');
	}

	function getDescription() {
		return __('plugins.generic.openAIRE.description');
	}

	/**
	 * Get page handler path for this plugin.
	  * @return string Path to plugin's page handler
	   */
	function getHandlerPath() {
		return $this->getPluginPath() . DIRECTORY_SEPARATOR . 'pages';
	}

	/**
	 * Get the template path for this plugin.
	 */
	function getTemplatePath() {
		return parent::getTemplatePath() . 'templates/';
	} 

	/**
	 * Hook callback: register pages for each sushi-lite method
	 * This URL is of the form: openaireapi/{$openAIRErequest}
	 * @see PKPPageRouter::route()
	 */
	function setupCallbackHandler($hookName, $params) {
		$page = $params[0];
		if ($this->getEnabled() && $page == 'openaireapi') {
			$this->import('pages/OpenAIREHandler');
			define('HANDLER_CLASS', 'OpenAIREHandler');
			return true;
		}
		return false;
	}

	/*
	 * Metadata
	 */

	/**
	 * Insert projectID field into author submission step 3 and metadata edit form
	 */
	function metadataFieldEdit($hookName, $params) {
		$smarty =& $params[1];
		$output =& $params[2];

		$output .= $smarty->fetch($this->getTemplatePath() . 'projectIDEdit.tpl');
		return false;
	}

	/**
	 * Add projectID to the metadata view
	 */
	function metadataFieldView($hookName, $params) { 
		$smarty =& $params[1];
		$output =& $params[2];

		$output .= $smarty->fetch($this->getTemplatePath() . 'projectIDView.tpl');
		return false;
	}

	/**
	 * Add projectID element to the article
	 */
	function articleSubmitGetFieldNames($hookName, $params) {
		$fields =& $params[1];
		$fields[] = 'projectID';
		$fields[] = 'projectTitle';
		$fields[] = 'projectFunder';
		$fields[] = 'projectFundingProgram';
		return false;
	}

	/**
	 * Set article projectID
	 */
	function metadataExecute($hookName, $params) { 
		$form =& $params[0];
		$article =& $form->article;
		$formProjectID = $form->getData('projectID');
		$formProjectTitle = $form->getData('projectTitle');
		$formFunder = $form->getData('projectFunder');
		$formFunderProgram = $form->getData('projectFundingProgram');
		$article->setData('projectID', $formProjectID);
		$article->setData('projectTitle', $formProjectTitle);
		$article->setData('projectFunder', $formFunder);
		$article->setData('projectFundingProgram', $formFunderProgram);
		return false;
	}

	/**
	 * Add check/validation for the projectID field (= 6 numbers)
	 */
	function addCheck($hookName, $params) {
		$form =& $params[0];
		if (get_class($form) == 'AuthorSubmitStep3Form' || get_class($form) == 'MetadataForm' ) {
			$form->addCheck(new FormValidatorRegExp($form, 'projectID', 'optional', 'plugins.generic.openAIRE.projectIDValid', '/^\d{6}$/'));
		}
		return false;
	}

	/**
	 * Init article projectID
	 */
	function metadataInitData($hookName, $params) {
		$form =& $params[0];
		$article =& $form->article;
		$articleProjectID      = $article->getData('projectID');
		$articleProjectTitle   = $article->getData('projectTitle');
		$articleFunder         = $article->getData('projectFunder');
		$articleFundingProgram = $article->getData('projectFundingProgram');
		$form->setData('projectID', $articleProjectID);
		$form->setData('projectTitle', $articleProjectTitle);
		$form->setData('projectFunder', $articleFunder);
		$form->setData('projectFundingProgram', $articleFundingProgram);
		return false;
	}

	/**
	 * Concern projectID field in the form
	 */
	function metadataReadUserVars($hookName, $params) {
		$userVars =& $params[1];
		$userVars[] = 'projectID';
		$userVars[] = 'projectTitle';
		$userVars[] = 'projectFunder';
		$userVars[] = 'projectFundingProgram';
		return false;
	}

	/*
	 * OAI interface
	 */

	/**
	 * Add OpenAIRE set
	 */
	function sets($hookName, $params) {
		$sets =& $params[5];
		array_push($sets, new OAISet('ec_fundedresources', 'EC_fundedresources', ''));
		return false;
	}

	/**
	 * Get OpenAIRE records or identifiers
	 */
	function recordsOrIdentifiers($hookName, $params) {
		$journalOAI =& $params[0];
		$from = $params[1];
		$until = $params[2];
		$set = $params[3];
		$offset = $params[4];
		$limit = $params[5];
		$total = $params[6];
		$records =& $params[7];

		$records = array();
		if (isset($set) && $set == 'ec_fundedresources') {
			$openAIREDao =& DAORegistry::getDAO('OpenAIREDAO');
			$openAIREDao->setOAI($journalOAI);
			if ($hookName == 'JournalOAI::records') {
				$funcName = '_returnRecordFromRow';
			} else if ($hookName == 'JournalOAI::identifiers') {
				$funcName = '_returnIdentifierFromRow';
			}
			$journalId = $journalOAI->journalId;
			$records = $openAIREDao->getOpenAIRERecordsOrIdentifiers(array($journalId, null), $from, $until, $offset, $limit, $total, $funcName);
			return true;
		}
		return false;
	}

	/**
	 * Change OAI record or identifier to consider the OpenAIRE set
	 */
	function addSet($hookName, $params) {
		$record =& $params[0];
		$row = $params[1];

		$openAIREDao =& DAORegistry::getDAO('OpenAIREDAO');
		if ($openAIREDao->isOpenAIRERecord($row)) {
			$record->sets[] = 'ec_fundedresources';
		}
		return false;
	}

	/**
	 * Change Dc11 Description to consider the OpenAIRE elements
	 */
	function changeDc11Desctiption($hookName, $params) {
		$adapter =& $params[0];
		$article = $params[1];
		$journal = $params[2];
		$issue = $params[3];
		$dc11Description =& $params[4];

		$openAIREDao =& DAORegistry::getDAO('OpenAIREDAO');
		$openAIREDao->setOAI($journalOAI);
		if ($openAIREDao->isOpenAIREArticle($article->getId())) {

			// Determine OpenAIRE DC elements values
			// OpenAIRE DC Relation
			$articleProjectID = $article->getData('projectID');
			$articleProjectFunder = $article->getData('projectFunder');
			$articleProjectFundingProgram = $article->getData('projectFundingProgram');
			$openAIRERelation = 'info:eu-repo/grantAgreement/' . $articleProjectFunder . '/' . $articleProjectFundingProgram . '/' . $articleProjectID;

			// OpenAIRE DC Rights
			$openAIRERights = 'info:eu-repo/semantics/';
			$status = '';
			if ($journal->getSetting('publishingMode') == PUBLISHING_MODE_OPEN) {
				$status = 'openAccess';
			} else if ($journal->getSetting('publishingMode') == PUBLISHING_MODE_SUBSCRIPTION) {
				if ($issue->getAccessStatus() == 0 || $issue->getAccessStatus() == ISSUE_ACCESS_OPEN) {
					$status = 'openAccess';
				} else if ($issue->getAccessStatus() == ISSUE_ACCESS_SUBSCRIPTION) {
					if (is_a($article, 'PublishedArticle') && $article->getAccessStatus() == ARTICLE_ACCESS_OPEN) {
						$status = 'openAccess';
					} else if ($issue->getAccessStatus() == ISSUE_ACCESS_SUBSCRIPTION && $issue->getOpenAccessDate() != NULL) {
						$status = 'embargoedAccess';
					} else if ($issue->getAccessStatus() == ISSUE_ACCESS_SUBSCRIPTION && $issue->getOpenAccessDate() == NULL) {
						$status = 'closedAccess';
					}
				}
			}
			if ($journal->getSetting('restrictSiteAccess') == 1 || $journal->getSetting('restrictArticleAccess') == 1) {
				$status = 'restrictedAccess';
			}
			$openAIRERights = $openAIRERights . $status;

			// OpenAIRE DC Date
			$openAIREDate = null;
			if ($status == 'embargoedAccess') {
				$openAIREDate = 'info:eu-repo/date/embargoEnd/' . date('Y-m-d', strtotime($issue->getOpenAccessDate()));
			}

			// Get current DC statements
			$dcRelationValues = array();
			$dcRightsValues = array();
			$dcDateValues = array();
			if ($dc11Description->hasStatement('dc:relation')) {
				$dcRelationValues = $dc11Description->getStatement('dc:relation');
			}
			if ($dc11Description->hasStatement('dc:rights')) {
				$dcRightsValues = $dc11Description->getStatementTranslations('dc:rights');
			}
			if ($dc11Description->hasStatement('dc:date')) {
				$dcDateValues = $dc11Description->getStatement('dc:date');
			}

			// Set new DC statements, concerning OpenAIRE
			array_unshift($dcRelationValues, $openAIRERelation);
			$newDCRelationStatements = array('dc:relation' => $dcRelationValues);
			$dc11Description->setStatements($newDCRelationStatements);

			foreach ($dcRightsValues as $key => $value) {
				array_unshift($value, $openAIRERights);
				$dcRightsValues[$key] = $value;
			}
			if (!array_key_exists($journal->getPrimaryLocale(), $dcRightsValues)) {
				$dcRightsValues[$journal->getPrimaryLocale()] = array($openAIRERights);
			}
			$newDCRightsStatements = array('dc:rights' => $dcRightsValues);
			$dc11Description->setStatements($newDCRightsStatements);

			if ($openAIREDate != null) {
				array_unshift($dcDateValues, $openAIREDate);
				$newDCDateStatements = array('dc:date' => $dcDateValues);
				$dc11Description->setStatements($newDCDateStatements);
			}
		}
		return false;
	}

	/**
	 * Consider the OpenAIRE set in the article tombstone
	 */
	function insertOpenAIREArticleTombstone($hookName, $params) {
		$articleTombstone =& $params[0];

		$openAIREDao =& DAORegistry::getDAO('OpenAIREDAO');
		if ($openAIREDao->isOpenAIREArticle($articleTombstone->getDataObjectId())) {
			$dataObjectTombstoneSettingsDao =& DAORegistry::getDAO('DataObjectTombstoneSettingsDAO');
			$dataObjectTombstoneSettingsDao->updateSetting($articleTombstone->getId(), 'openaire', true, 'bool');
		}
		return false;
	} 
}