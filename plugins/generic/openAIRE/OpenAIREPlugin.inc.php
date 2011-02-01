<?php

/**
 * @file plugins/generic/openAIRE/OpenAIREPlugin.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OpenAIREPlugin
 * @ingroup plugins_generic_openAIRE
 *
 * @brief OpenAIRE plugin class
 */

import('lib.pkp.classes.plugins.GenericPlugin');


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
			HookRegistry::register('JournalOAI::sets', array($this, 'sets'));
			HookRegistry::register('JournalOAI::identifiers', array($this, 'identifiers'));
			HookRegistry::register('JournalOAI::records', array($this, 'records'));
			HookRegistry::register('OAIDAO::_returnRecordFromRow', array($this, 'changeRecord'));
			HookRegistry::register('OAIDAO::_returnIdentifierFromRow', array($this, 'changeIdentifier'));
			
			// Change Dc11Desctiption -- consider OpenAIRE elements relation, rights and date
			HookRegistry::register('OAIMetadataFormat_DC::toXml', array($this, 'changeXml'));
			
		}
		return $success;
	}

	function getDisplayName() {
		return Locale::translate('plugins.generic.openAIRE.displayName');
	}

	function getDescription() {
		return Locale::translate('plugins.generic.openAIRE.description');
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
		return false;
	}
	
	/**
	 * Set article projectID
	 */
	function metadataExecute($hookName, $params) {
		$form =& $params[0];
		$article =& $form->article;
		$formProjectID = $form->getData('projectID');
		$article->setData('projectID', $formProjectID);		
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
		$articleProjectID = $article->getData('projectID');	
		$form->setData('projectID', $articleProjectID);
		return false;
	}
	
	/**
	 * Concern projectID field in the form
	 */
	function metadataReadUserVars($hookName, $params) {
		$userVars =& $params[1];		
		$userVars[] = 'projectID';		
		return false;
	}
	
		
	/*
	 * OAI interface
	 */
	
	/**
	 * Add OpenAIRE set
	 */
	function sets($hookName, $params) {
		$journalOAI =& $params[0];
		$offset = $params[1];
		$total = $params[2];
		$sets =& $params[3];
		$journalId = $journalOAI->journalId;	
		$journalDao =& DAORegistry::getDAO('JournalDAO');	
		$sectionDao =& DAORegistry::getDAO('SectionDAO');

		if (isset($journalId)) {
			$journals = array($journalDao->getJournal($journalId));
		} else {
			$journals =& $journalDao->getJournals();
			$journals =& $journals->toArray();
		}

		// FIXME Set descriptions
		$sets = array();
		$openAIRESetName = "EC_fundedresources";
		$openAIRESetAbbrev = "ec_fundedresources";
		foreach ($journals as $journal) {
			$title = $journal->getLocalizedTitle();
			$abbrev = $journal->getPath();
			array_push($sets, new OAISet(urlencode($abbrev), $title, ''));

			$sections =& $sectionDao->getJournalSections($journal->getId());
			foreach ($sections->toArray() as $section) {
				array_push($sets, new OAISet(urlencode($abbrev) . ':' . urlencode($section->getLocalizedAbbrev()), $section->getLocalizedTitle(), ''));
			}			
			array_push($sets, new OAISet(urlencode($abbrev) . ':' . urlencode($openAIRESetAbbrev), $openAIRESetName, ''));
		}

		
		if ($offset != 0) {
			$sets = array_slice($sets, $offset);
		}

		return true;
	}

	/**
	 * Change OAI records to consider the OpenAIRE set
	 */
	function records($hookName, $params) {
		$journalOAI =& $params[0];
		$from = $params[1];
		$until = $params[2];
		$set = $params[3];
		$offset = $params[4];
		$limit = $params[5];
		$total = $params[6];
		$records =& $params[7];
		
		$records = array();
		if (isset($set) && strpos($set, 'ec_fundedresources') != false) {
			$journalId = $journalOAI->journalId;
			$openAIREDao =& DAORegistry::getDAO('OpenAIREDAO');
			$openAIREDao->setOAI($journalOAI);
			$records = $openAIREDao->getOpenAIRERecords($journalId, $from, $until, $offset, $limit, $total);
			return true;
		} 
		
		return false;
	}	
	
	/**
	 * Change OAI identifier to consider the OpenAIRE set
	 */
	function identifiers($hookName, $params) {
		$journalOAI =& $params[0];
		$from = $params[1];
		$until = $params[2];
		$set = $params[3];
		$offset = $params[4];
		$limit = $params[5];
		$total = $params[6];
		$records =& $params[7];
		
		$records = array();
		if (isset($set) && strpos($set, 'ec_fundedresources') != false) {
			$journalId = $journalOAI->journalId;
			$openAIREDao =& DAORegistry::getDAO('OpenAIREDAO');
			$openAIREDao->setOAI($journalOAI);
			$records = $openAIREDao->getOpenAIREIdentifiers($journalId, $from, $until, $offset, $limit, $total);
			return true;
		}

		return false;
	}	
		
	/**
	 * Change OAI record to consider the OpenAIRE set
	 */
	function changeRecord($hookName, $params) {
		$record =& $params[0];
		$row = $params[1];

		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$journal = $journalDao->getJournal($row['journal_id']);
		$openAIREDao =& DAORegistry::getDAO('OpenAIREDAO');
		if ($openAIREDao->isOpenAIREArticle($row['article_id'])) {
			$record->sets[] = $journal->getPath() . ':ec_fundedresources';
		}
		return false;	
	}

	/**
	 * Change OAI identifier to consider the OpenAIRE set
	 */
	function changeIdentifier($hookName, $params) {
		$record =& $params[0];
		$row = $params[1];
		
		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$journal = $journalDao->getJournal($row['journal_id']);
		$openAIREDao =& DAORegistry::getDAO('OpenAIREDAO');
		if ($openAIREDao->isOpenAIREArticle($row['article_id'])) {
			$record->sets[] = $journal->getPath() . ':ec_fundedresources';
		}	
		return false;	
	}
	
	/**
	 * Change DC XML to consider the OpenAIRE elements
	 */
	function changeXML($hookName, $params) {
		$dcOAIMetadataFormat =& $params[0];
		$record = $params[1];
		$response =& $params[2];

		$article =& $record->getData('article');
		$journal =& $record->getData('journal');
		$section =& $record->getData('section');
		$issue =& $record->getData('issue');
		$galleys =& $record->getData('galleys');
	
		Locale::requireComponents(array(LOCALE_COMPONENT_APPLICATION_COMMON));
		
		// Sources contains journal title, issue ID, and pages
		$sources = $dcOAIMetadataFormat->stripAssocArray((array) $journal->getTitle(null));
		$pages = $article->getPages();
		if (!empty($pages)) $pages = '; ' . $pages;
		foreach ($sources as $key => $source) {
			$sources[$key] .= '; ' . $issue->getIssueIdentification() . $pages;
		}
	
		// Format creators
		$creators = array();
		$authors = $article->getAuthors();
		for ($i = 0, $num = count($authors); $i < $num; $i++) {
			$authorName = $authors[$i]->getFullName(true);
			$affiliation = $authors[$i]->getLocalizedAffiliation();
			if (!empty($affiliation)) {
				$authorName .= '; ' . $affiliation;
			}
			$creators[] = $authorName;
		}
	
		// Publisher
		$publishers = $dcOAIMetadataFormat->stripAssocArray((array) $journal->getTitle(null)); // Default
		$publisherInstitution = $journal->getSetting('publisherInstitution');
		if (!empty($publisherInstitution)) {
			$publishers = array($journal->getPrimaryLocale() => $publisherInstitution);
		}
	
		// Types
		$types = $dcOAIMetadataFormat->stripAssocArray((array) $section->getIdentifyType(null));
		$types = array_merge_recursive(
			empty($types)?array(Locale::getLocale() => Locale::translate('rt.metadata.pkp.peerReviewed')):$types,
			$dcOAIMetadataFormat->stripAssocArray((array) $article->getType(null))
		);
	
		// Formats
		$formats = array();
		foreach ($galleys as $galley) {
			$formats[] = $galley->getFileType();
		}
	
		// Relation
		$relation = array();
		foreach ($article->getSuppFiles() as $suppFile) {
			$relation[] = Request::url($journal->getPath(), 'article', 'download', array($article->getId(), $suppFile->getFileId()));
		}
		
		// Date
		$date = array();
		$date[] = date('Y-m-d', strtotime($issue->getDatePublished()));
		
		// Rights
		$rights = $dcOAIMetadataFormat->stripAssocArray((array) $journal->getSetting('copyrightNotice'));

		$openAIREDao =& DAORegistry::getDAO('OpenAIREDAO');
		if ($openAIREDao->isOpenAIREArticle($article->getArticleId())) {
		
			// OpenAIRE DC Relation
			$articleProjectID = $article->getData('projectID');	
			$openAIRERelation = 'info:eu-repo/grantAgreement/EC/FP7/' . $articleProjectID;

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
			$openAIRERightsValues = array();
			foreach ($rights as $key => $value) {
				$openAIRERightsValues[$key] = $openAIRERights;
			}		
			
			// OpenAIRE DC Date
			$openAIREDate = null;
			if ($status == 'embargoedAccess') {
				$openAIREDate = 'info:eu-repo/date/embargoEnd/' . date('Y-m-d', strtotime($issue->getOpenAccessDate()));
			}
			
			// add OpenAIRE elements relation, rights, date
			array_unshift($relation, $openAIRERelation);

			$rights = array_merge_recursive(
				empty($rights)?array(Locale::getLocale() => $openAIRERights):
				$dcOAIMetadataFormat->stripAssocArray((array) $openAIRERightsValues), $rights
			);
			
			if ($openAIREDate != null) {
				array_unshift($date, $openAIREDate);
			}			
			
		}
		
		$response = "<oai_dc:dc\n" .
			"\txmlns:oai_dc=\"http://www.openarchives.org/OAI/2.0/oai_dc/\"\n" .
			"\txmlns:dc=\"http://purl.org/dc/elements/1.1/\"\n" .
			"\txmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n" .
			"\txsi:schemaLocation=\"http://www.openarchives.org/OAI/2.0/oai_dc/\n" .
			"\thttp://www.openarchives.org/OAI/2.0/oai_dc.xsd\">\n" .
			$dcOAIMetadataFormat->formatElement('title', $dcOAIMetadataFormat->stripAssocArray((array) $article->getTitle(null)), true) .
			$dcOAIMetadataFormat->formatElement('creator', $creators) .
			$dcOAIMetadataFormat->formatElement(
				'subject',
				array_merge_recursive(
					$dcOAIMetadataFormat->stripAssocArray((array) $article->getDiscipline(null)),
					$dcOAIMetadataFormat->stripAssocArray((array) $article->getSubject(null)),
					$dcOAIMetadataFormat->stripAssocArray((array) $article->getSubjectClass(null))
				),
				true
			) .
			$dcOAIMetadataFormat->formatElement('description', $dcOAIMetadataFormat->stripAssocArray((array) $article->getAbstract(null)), true) .
			$dcOAIMetadataFormat->formatElement('publisher', $publishers, true) .
			$dcOAIMetadataFormat->formatElement('contributor', $dcOAIMetadataFormat->stripAssocArray((array) $article->getSponsor(null)), true) .
			$dcOAIMetadataFormat->formatElement('date', $date) .
			$dcOAIMetadataFormat->formatElement('type', $types, true) .
			$dcOAIMetadataFormat->formatElement('format', $formats) .
			$dcOAIMetadataFormat->formatElement('identifier', Request::url($journal->getPath(), 'article', 'view', array($article->getBestArticleId()))) .
			(($doi = $article->getDOI())?$dcOAIMetadataFormat->formatElement('identifier', $doi, false, array('xsi:type' => 'dcterms:DOI')):'') .
			$dcOAIMetadataFormat->formatElement('source', $sources, true) .
			$dcOAIMetadataFormat->formatElement('language', strip_tags($article->getLanguage())) .
			$dcOAIMetadataFormat->formatElement('relation', $relation) .
			$dcOAIMetadataFormat->formatElement(
				'coverage',
				array_merge_recursive(
					$dcOAIMetadataFormat->stripAssocArray((array) $article->getCoverageGeo(null)),
					$dcOAIMetadataFormat->stripAssocArray((array) $article->getCoverageChron(null)),
					$dcOAIMetadataFormat->stripAssocArray((array) $article->getCoverageSample(null))
				),
				true
			) .
			$dcOAIMetadataFormat->formatElement('rights', $rights, true) .
			"</oai_dc:dc>\n";
	
		return true;	
		
	}
	
	
}
?>
