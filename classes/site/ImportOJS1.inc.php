<?php

/**
 * ImportOJS1.inc.php
 *
 * Copyright (c) 2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package site
 *
 * Class to import data from an OJS 1.x installation.
 *
 * $Id$
 */

define('OJS1_MIN_VERSION', '1.1.5');
define('OJS1_MIN_VERSION_SUBSCRIPTIONS', '1.1.8');

import('user.User');
import('journal.Journal');
import('journal.Section');
import('security.Role');
import('subscription.Subscription');
import('subscription.SubscriptionType');
import('subscription.Currency');
import('article.Article');
import('article.ArticleComment');
import('article.ArticleFile');
import('article.ArticleGalley');
import('article.ArticleHTMLGalley');
import('article.ArticleNote');
import('article.Author');
import('article.PublishedArticle');
import('article.SuppFile');
import('submission.common/Action');
import('submission.author.AuthorSubmission');
import('submission.reviewer.ReviewerSubmission');
import('issue.Issue');
import('submission.copyAssignment.CopyAssignment');
import('submission.editAssignment.EditAssignment');
import('submission.layoutAssignment.LayoutAssignment');
import('submission.proofAssignment.ProofAssignment');
import('submission.reviewAssignment.ReviewAssignment');
import('comment.Comment');
import('file.ArticleFileManager');
import('file.PublicFileManager');
import('search.ArticleSearchIndex');

	
class ImportOJS1 {

	//
	// Private variables
	//

	var $importPath;
	var $importVersion;
	var $journalPath;
	var $journalId = 0;
	
	var $userMap = array();
	var $issueMap = array();
	var $sectionMap = array();
	var $articleMap = array();
	var $fileMap = array();
	
	var $importDBConn;
	var $importDao;
	
	var $indexUrl;
	var $importJournal;
	var $journalConfigInfo;
	var $journalInfo;
	var $journalLayoutUserId = 0;
	var $issueLabelFormat;
	
	var $userCount = 0;
	var $issueCount = 0;
	var $articleCount = 0;
	
	var $options;
	var $error;


	/**
	 * Constructor.
	 */
	function ImportOJS1() {
		// Note: generally Request's auto-detection won't work correctly
		// when run via CLI so use config setting if available
		$this->indexUrl = Config::getVar('general', 'base_url');
		if ($this->indexUrl)
			$this->indexUrl .= '/' . INDEX_SCRIPTNAME;
		else
			$this->indexUrl = Request::getIndexUrl();
	}
	
	/**
	 * Record error message.
	 * @return string;
	 */
	function error($message = null) {
		if (isset($message)) {
			$this->error = $message;
		}
		return $this->error;
	}
	
	/**
	 * Check if an option is enabled.
	 * @param $option string
	 * @return boolean
	 */
	function hasOption($option) {
		return in_array($option, $this->options);
	}
	
	/**
	 * Execute import of an OJS 1 journal.
	 * If an existing journal path is specified, only content is imported;
	 * otherwise, a new journal is created and all journal settings are also imported.
	 * @param $journalPath string journal URL path
	 * @param $importPath string local filesystem path to the base OJS 1 directory
	 * @param $options array supported: 'importSubscriptions'
	 * @return boolean/int false or journal ID
	 */
	function import($journalPath, $importPath, $options = array()) {
		@set_time_limit(0);
		$this->journalPath = $journalPath;
		$this->importPath = $importPath;
		$this->options = $options;
		
		// Force a new database connection
		$dbconn = &DBConnection::getInstance();
		$dbconn->reconnect(true);
		
		// Create a connection to the old database
		if (!@include($this->importPath . '/include/db.php')) { // Suppress E_NOTICE messages
			$this->error('Failed to load ' . $this->importPath . '/include/db.php');
			return false;
		}
		
		// Assumes no character set (not supported by OJS 1.x)
		// Forces open a new connection
		$this->importDBConn = &new DBConnection($db_config['type'], $db_config['host'], $db_config['uname'], $db_config['password'], $db_config['name'], false, false, true, false, true);
		$dbconn = &$this->importDBConn->getDBConn();
		
		if (!$this->importDBConn->isConnected()) {
			$this->error('Database connection error: ' . $dbconn->errorMsg());
			return false;
		}
		
		$this->importDao = &new DAO($dbconn);
		
		if (!$this->loadJournalConfig()) {
			$this->error('Unsupported or unrecognized OJS version');
			return false;
		}
		
		// Determine if journal already exists
		$journalDao = &DAORegistry::getDAO('JournalDAO');
		$journal = &$journalDao->getJournalByPath($this->journalPath);
		$this->importJournal = ($journal == null);
		
		// Import data
		if ($this->importJournal) {
			$this->importJournal();
			$this->importReadingTools();
		} else {
			$this->journalId = $journal->getJournalId();
		}
		$this->importUsers();
		if ($this->hasOption('importSubscriptions') && version_compare($this->importVersion, OJS1_MIN_VERSION_SUBSCRIPTIONS) >= 0) {
			// Subscriptions requires OJS >= 1.1.8
			$this->importSubscriptions();
		}
		$this->importSections();
		$this->importIssues();
		$this->importArticles();
		
		// Rebuild search index
		$this->rebuildSearchIndex();
		
		return $this->journalId;
	}
	
	/**
	 * Load OJS 1 journal configuration and settings data.
	 * @return boolean
	 */
	function loadJournalConfig() {
		// Load journal config
		$result = &$this->importDao->retrieve('SELECT * FROM tbljournalconfig');
		$this->journalConfigInfo = &$result->fields;
		$result->Close();
		
		if (!isset($this->journalConfigInfo['chOJSVersion'])) {
			return false;
		}
		$this->importVersion = $this->journalConfigInfo['chOJSVersion'];
		if (version_compare($this->importVersion, OJS1_MIN_VERSION) < 0) {
			return false;
		}

		// Load journal settings
		$result = &$this->importDao->retrieve('SELECT * FROM tbljournal');
		$this->journalInfo = &$result->fields;
		$result->Close();
		
		return true;
	}
	
	
	//
	// Journal
	//
	
	/**
	 * Import journal and journal settings.
	 */
	function importJournal() {
		if ($this->hasOption('verbose')) {
			printf("Importing journal\n");
		}
		
		// Create journal
		$journalDao = &DAORegistry::getDAO('JournalDAO');
		$journal = &new Journal();
		$journal->setTitle($this->journalInfo['chTitle']);
		$journal->setPath($this->journalPath);
		$journal->setEnabled(1);
		$this->journalId = $journalDao->insertJournal($journal);
		$journalDao->resequenceJournals();
		
		// Add journal manager role for site administrator(s)
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$admins = $roleDao->getUsersByRoleId(ROLE_ID_SITE_ADMIN);
		foreach ($admins->toArray() as $admin) {
			$role = &new Role();
			$role->setJournalId($this->journalId);
			$role->setUserId($admin->getUserId());
			$role->setRoleId(ROLE_ID_JOURNAL_MANAGER);
			$roleDao->insertRole($role);
		}
		
		// Install the default RT versions.
		import('rt.ojs.JournalRTAdmin');
		$journalRtAdmin = &new JournalRTAdmin($this->journalId);
		$journalRtAdmin->restoreVersions(false);
		
		// Publishers, sponsors, and contributors
		$publisher = array();
		$sponsors = array();
		$result = &$this->importDao->retrieve('SELECT * FROM tblsponsors ORDER BY nSponsorID');
		while (!$result->EOF) {
			$row = &$result->fields;
			$sponsors[] = array('institution' => $row['chName'], 'url' => $row['chWebpage']);
			if (empty($publisher)) {
				$publisher = array('institution' => $row['chName'], 'url' => $row['chWebpage']);
			}
			$result->MoveNext();
		}
		$result->Close();
		
		$contributors = array();
		$result = &$this->importDao->retrieve('SELECT * FROM tblcontributors ORDER BY nContributorID');
		while (!$result->EOF) {
			$row = &$result->fields;
			$contributors[] = array('name' => $row['chName'], 'url' => $row['chWebpage']);
			$result->MoveNext();
		}
		$result->Close();
		
		// Submission checklist
		$submissionChecklist = array();
		$result = &$this->importDao->retrieve('SELECT * FROM tblsubmissionchecklist ORDER BY nOrder');
		while (!$result->EOF) {
			$row = &$result->fields;
			$submissionChecklist[] = array('order' => $row['nOrder'], 'content' => $row['chCheck']);
			$result->MoveNext();
		}
		$result->Close();
		
		// Additional about items
		$customAboutItems = array();
		$result = &$this->importDao->retrieve('SELECT * FROM tblaboutjournal ORDER BY nItemID');
		while (!$result->EOF) {
			$row = &$result->fields;
			$customAboutItems[] = array('title' => $row['chTitle'], 'content' => $row['chContent']);
			$result->MoveNext();
		}
		$result->Close();
		
		// Navigation items
		$navItems = array();
		if ($this->journalInfo['bDiscussion'] && !empty($this->journalInfo['chDiscussionURL'])) {
			$navItems[] = array('name' => 'Forum', 'url' => $this->journalInfo['chDiscussionURL'], 'isLiteral' => '1', 'isAbsolute' => '1');
		}
		
		$publicationFormat = ISSUE_LABEL_NUM_VOL_YEAR;
		if ($this->journalInfo['nSchedulingType'] == 1 && !$this->journalInfo['bPubUseNum']) {
			$publicationFormat = ISSUE_LABEL_VOL_YEAR;
		} else if($this->journalInfo['nSchedulingType'] == 2) {
			$publicationFormat = ISSUE_LABEL_YEAR;
		}
		
		// Journal images
		$homeHeaderLogoImage = $this->copyJournalImage('chSmallHomeLogo', 'homeHeaderLogoImage');
		$homeHeaderTitleImage = $this->copyJournalImage('chLargeHomeLogo', 'homeHeaderTitleImage');
		$pageHeaderLogoImage = $this->copyJournalImage('chSmallLogo', 'pageHeaderLogoImage');
		$pageHeaderTitleImage = $this->copyJournalImage('chLargeLogo', 'pageHeaderTitleImage');
		$homepageImage = $this->copyJournalImage('chTableOfContentImage', 'homepageImage');
		
		$translateParams = array('indexUrl' => $this->indexUrl, 'journalPath' => $this->journalPath, 'journalName' => $this->journalInfo['chTitle']);
		
		// Journal settings
		// NOTE: Commented out settings do not have an equivalent in OJS 1.x
		$journalSettings = array(
			'journalInitials' => array('string', $this->journalInfo['chAbbrev']),
			'issn' => array('string', $this->journalInfo['chISSN']),
			'mailingAddress' => array('string', $this->journalInfo['chMailAddr']),
			'useEditorialBoard' => array('bool', $this->journalInfo['bRevBoard']),
			'contactName' => array('string', $this->journalInfo['chContactName']),
			'contactTitle' => array('string', $this->journalInfo['chContactTitle']),
			'contactAffiliation' => array('string', $this->journalInfo['chContactAffiliation']),
			'contactEmail' => array('string', $this->journalInfo['chContactEmail']),
			'contactPhone' => array('string', $this->journalInfo['chContactPhone']),
			'contactFax' => array('string', $this->journalInfo['chContactFax']),
			'contactMailingAddress' => array('string', $this->journalInfo['chContactMailAddr']),
			'supportName' => array('string', $this->journalInfo['chSupportName']),
			'supportEmail' => array('string', $this->journalInfo['chSupportEmail']),
			'supportPhone' => array('string', $this->journalInfo['chSupportPhone']),
			'sponsorNote' => array('string', $this->journalInfo['chSponsorNote']),
			'sponsors' => array('object', $sponsors),
			'publisher' => array('object', $publisher),
			'contributorNote' => array('string', $this->journalInfo['chContribNote']),
			'contributors' => array('object', $contributors),
			'searchDescription' => array('string', $this->journalInfo['chMetaDescription']),
			'searchKeywords' => array('string', $this->journalInfo['chMetaKeywords']),
		//	'customHeaders' => array('string', ''),
			
			'focusScopeDesc' => array('string', $this->journalInfo['chFocusScope']),
			'numWeeksPerReview' => array('int', $this->journalInfo['nReviewDueWeeks']),
		//	'remindForInvite' => array('int', ''),
		//	'remindForSubmit' => array('int', ''),
		//	'numDaysBeforeInviteReminder' => array('int', ''),
		//	'numDaysBeforeSubmitReminder' => array('int', ''),
		//	'rateReviewerOnQuality' => array('int', ''),
			'restrictReviewerFileAccess' => array('int', isset($this->journalInfo['bReviewerSubmissionRestrict']) ? $this->journalInfo['bReviewerSubmissionRestrict'] : 0),
			'reviewPolicy' => array('string', $this->journalInfo['chReviewProcess']),
			'mailSubmissionsToReviewers' => array('int', isset($this->journalInfo['bReviewerMailSubmission']) ? $this->journalInfo['bReviewerMailSubmission'] : 0),
			'reviewGuidelines' => array('string', $this->journalInfo['chReviewerGuideline']),
			'authorSelectsEditor' => array('int', isset($this->journalInfo['bAuthorSelectEditor']) ? $this->journalInfo['bAuthorSelectEditor'] : 0),
			'privacyStatement' => array('string', $this->journalInfo['chPrivacyStatement']),
			'openAccessPolicy' => array('string', $this->journalInfo['chOpenAccess']),
		//	'envelopeSender' => array('string', ''),
			'emailSignature' => array('string', Locale::translate('default.journalSettings.emailSignature', $translateParams)),
		//	'disableUserReg' => array('bool', ''),
		//	'allowRegReader' => array('bool', ''),
		//	'allowRegAuthor' => array('bool', ''),
		//	'allowRegReviewer' => array('bool', ''),
		//	'restrictSiteAccess' => array('bool', ''),
		//	'restrictArticleAccess' => array('bool', ''),
		//	'articleEventLog' => array('bool', ''),
		//	'articleEmailLog' => array('bool', ''),
			'customAboutItems' => array('object', $customAboutItems),
			'enableComments' => array('string', $this->journalInfo['bComments'] ? 'unauthenticated' : 'disabled'),
			
			'authorGuidelines' => array('string', $this->journalInfo['chAuthorGuideline']),
			'submissionChecklist' => array('object', $submissionChecklist),
			'copyrightNotice' => array('string', $this->journalInfo['chCopyrightNotice']),
			'metaDiscipline' => array('bool', $this->journalInfo['bMetaDiscipline']),
			'metaDisciplineExamples' => array('string', $this->journalInfo['chDisciplineExamples']),
			'metaSubjectClass' => array('bool', $this->journalInfo['bMetaSubjectClass']),
			'metaSubjectClassTitle' => array('string', $this->journalInfo['chSubjectClassTitle']),
			'metaSubjectClassUrl' => array('string', $this->journalInfo['chSubjectClassURL']),
			'metaSubject' => array('bool', $this->journalInfo['bMetaSubject']),
			'metaSubjectExamples' => array('string', $this->journalInfo['chSubjectExamples']),
			'metaCoverage' => array('bool', $this->journalInfo['bMetaCoverage']),
			'metaCoverageGeoExamples' => array('string', $this->journalInfo['chCovGeoExamples']),
			'metaCoverageChronExamples' => array('string', $this->journalInfo['chCovChronExamples']),
			'metaCoverageResearchSampleExamples' => array('string', $this->journalInfo['chCovSampleExamples']),
			'metaType' => array('bool', $this->journalInfo['bMetaType']),
			'metaTypeExamples' => array('string', $this->journalInfo['chDisciplineExamples']),
			
			'publicationFormat' => array('int', $publicationFormat),
			'initialVolume' => array('int', $this->journalInfo['nInitVol']),
			'initialNumber' => array('int', $this->journalInfo['nInitNum']),
			'initialYear' => array('int', $this->journalInfo['nInitYear']),
			'pubFreqPolicy' => array('string', $this->journalInfo['chFreqPublication']),
			'useCopyeditors' => array('bool', $this->journalInfo['bCopyEditor']),
			'copyeditInstructions' => array('string', $this->journalInfo['chCopyeditInstructions']),
			'useLayoutEditors' => array('bool', $this->journalInfo['bLayoutEditor']),
		//	'layoutInstructions' => array('string', ''),
			'useProofreaders' => array('bool', $this->journalInfo['bProofReader']),
			'proofInstructions' => array('string', $this->journalInfo['chProofingInstructions']),
			'enableSubscriptions' => array('bool', isset($this->journalInfo['bSubscriptions']) ? $this->journalInfo['bSubscriptions'] : 0),
			'subscriptionName' => array('string', $this->journalInfo['chContactName']),
			'subscriptionEmail' => array('string', $this->journalInfo['chContactEmail']),
			'subscriptionPhone' => array('string', $this->journalInfo['chContactPhone']),
			'subscriptionFax' => array('string', $this->journalInfo['chContactFax']),
			'subscriptionMailingAddress' => array('string', $this->journalInfo['chContactMailAddr']),
		//	'subscriptionAdditionalInformation' => array('string', ''),
		//	'volumePerYear' => array('int', ''),
		//	'issuePerVolume' => array('int', ''),
		//	'enablePublicIssueId' => array('bool', ''),
		//	'enablePublicArticleId' => array('bool', ''),
		//	'enablePageNumber' => array('bool', ''),
		
			'homeHeaderTitleType' => array('int', isset($homeHeaderTitleImage) ? 1 : 0),
			'homeHeaderTitle' => array('string', $this->journalInfo['chTitle']),
		//	'homeHeaderTitleTypeAlt1' => array('int', 0),
		//	'homeHeaderTitleAlt1' => array('string', ''),
		//	'homeHeaderTitleTypeAlt2' => array('int', 0),
		//	'homeHeaderTitleAlt2' => array('string', ''),
			'pageHeaderTitleType' => array('int', isset($pageHeaderTitleImage) ? 1 : 0),
			'pageHeaderTitle' => array('string', $this->journalInfo['chTitle']),
		//	'pageHeaderTitleTypeAlt1' => array('int', 0),
		//	'pageHeaderTitleAlt1' => array('string', ''),
		//	'pageHeaderTitleTypeAlt2' => array('int', 0),
		//	'pageHeaderTitleAlt2' => array('string', ''),
			'homeHeaderLogoImage' => array('object', $homeHeaderLogoImage),
			'homeHeaderTitleImage' => array('object', $homeHeaderTitleImage),
			'pageHeaderLogoImage' => array('object', $pageHeaderLogoImage),
			'pageHeaderTitleImage' => array('object', $pageHeaderTitleImage),
			'homepageImage' => array('object', $homepageImage),
			'readerInformation' => array('string', Locale::translate('default.journalSettings.forReaders', $translateParams)),
			'authorInformation' => array('string', Locale::translate('default.journalSettings.forAuthors', $translateParams)),
			'librarianInformation' => array('string', Locale::translate('default.journalSettings.forLibrarians', $translateParams)),
			'journalPageHeader' => array('string', $this->journalInfo['chHeader']),
			'journalPageFooter' => array('string', $this->journalInfo['chFooter']),
			'displayCurrentIssue' => array('bool', $this->journalInfo['bHomepageCurrIssue']),
			'additionalHomeContent' => array('string', $this->journalInfo['chTableOfContentText']),
			'journalDescription' => array('string', $this->journalInfo['chHomepageIntro']),
			'navItems' => array('object', $navItems),
			'itemsPerPage' => array('int', 25),
			'numPageLinks' => array('int', 10),
		);
		
		$settingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
		
		foreach ($journalSettings as $settingName => $settingInfo) {
			list($settingType, $settingValue) = $settingInfo;
			$settingsDao->updateSetting($this->journalId, $settingName, $settingValue, $settingType);
		}
	}
		
	/**
	 * Import reading tools (nee RST) settings.
	 */
	function importReadingTools() {
		if ($this->hasOption('verbose')) {
			printf("Importing RT settings\n");
		}
		
		$rtDao = &DAORegistry::getDAO('RTDAO');
		
		$versionId = 0;
		
		// Try to map to new version
		$result = &$this->importDao->retrieve('SELECT chTitle FROM tblrstversions WHERE bDefault = 1');
		if ($result->RecordCount() != 0) {
			$result = &$rtDao->retrieve('SELECT version_id FROM rt_versions WHERE journal_id = ? AND title = ?', array($this->journalId, $result->fields[0]));
			if ($result->RecordCount() != 0) {
				$versionId = $result->fields[0];
			}
		}
		
		$result = &$this->importDao->retrieve('SELECT * FROM tblrst');
		$row = &$result->fields;
		
		import('rt.ojs.JournalRT');
		$rt = &new JournalRT($this->journalId);
		$rt->setVersion($versionId);
		$rt->setCaptureCite($row['bCaptureCite']);
		$rt->setViewMetadata($row['bViewMetadata']);
		$rt->setSupplementaryFiles($row['bSuppFiles']);
		$rt->setPrinterFriendly($row['bPrintVersion']);
		$rt->setAuthorBio($row['bAuthorBios']);
		$rt->setDefineTerms($row['bDefineTerms']);
		$rt->setAddComment($row['bAddComment']);
		$rt->setEmailAuthor($row['bEmailAuthor']);
		$rt->setEmailOthers($row['bEmailOthers']);
		$rt->setBibFormat($this->journalInfo['chCitationStyle']);
		
		$rtDao->insertJournalRT($rt);
	}
	
	
	//
	// Users
	//
	
	/**
	 * Import users and roles.
	 */
	function importUsers() {
		if ($this->hasOption('verbose')) {
			printf("Importing users\n");
		}
		
		$userDao = &DAORegistry::getDAO('UserDAO');
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$notifyDao = &DAORegistry::getDAO('NotificationStatusDAO');
		
		$result = &$this->importDao->retrieve('SELECT *, DECODE(chPassword, ?) AS chPassword FROM tblusers ORDER BY nUserID', $this->journalConfigInfo['chPasswordSalt']);
		while (!$result->EOF) {
			$row = &$result->fields;
			
			$initials = substr($row['chFirstName'], 0, 1) . (empty($row['chMiddleInitial']) ? '' : substr($row['chMiddleInitial'], 0, 1)) . substr($row['chSurname'], 0, 1);
			$interests = '';
		
			if ($row['fkEditorID']) {
				$tmpResult = &$this->importDao->retrieve('SELECT chInitials, nEditorRole FROM tbleditors WHERE nEditorID = ?', $row['fkEditorID']);
				list($initials, $editorRole) = $tmpResult->fields;
			}
			
			if ($row['fkReviewerID']) {
				$tmpResult = &$this->importDao->retrieve('SELECT chInterests FROM tblreviewers WHERE nReviewerID = ?', $row['fkReviewerID']);
				list($interests) = $tmpResult->fields;
			}
			
			// Check for existing user with this username
			$user = $userDao->getUserByUsername($row['chUsername']);
			$existingUser = ($user != null);
			
			if (!isset($user)) {
				// Create new user
				$user = &new User();
				$user->setUsername($row['chUsername']);
				$user->setPassword(Validation::encryptCredentials($row['chUsername'], $row['chPassword']));
				$user->setFirstName($row['chFirstName']);
				$user->setMiddleName($row['chMiddleInitial']);
				$user->setInitials($initials);
				$user->setLastName($row['chSurname']);
				$user->setAffiliation($row['chAffiliation']);
				$user->setEmail($row['chEmail']);
				$user->setPhone($row['chPhone']);
				$user->setFax($row['chFax']);
				$user->setMailingAddress($row['chMailAddr']);
				$user->setBiography($row['chBiography']);
				$user->setInterests($interests);
				$user->setLocales(array());
				$user->setDateRegistered($row['dtDateSignedUp']);
				$user->setDateLastLogin($row['dtDateSignedUp']);
				$user->setMustChangePassword(0);
				$user->setDisabled(0);
				
				if ($userDao->userExistsByEmail($row['chEmail'])) {
					// User exists with this email -- munge it to make unique
					$user->setEmail('ojs-' . $row['chUsername'] . '+' . $row['chEmail']);
				}
				
				$userDao->insertUser($user);
			}
			$userId = $user->getUserId();
			
			if ($row['bNotify']) {
				if ($existingUser) {
					// Just in case
					$notifyDao->setJournalNotifications($this->journalId, $userId, 0);
				}
				$notifyDao->setJournalNotifications($this->journalId, $userId, 1);
			}
			
			if ($row['fkEditorID']) {
				$role = &new Role();
				$role->setJournalId($this->journalId);
				$role->setUserId($userId);
				switch ($editorRole) {
					case 0:
						$role->setRoleId(ROLE_ID_EDITOR);
						break;
					case 1:
						$role->setRoleId(ROLE_ID_SECTION_EDITOR);
						break;
					case 2:
						$role->setRoleId(ROLE_ID_JOURNAL_MANAGER);
						break;
					case 3:
						$role->setRoleId(ROLE_ID_LAYOUT_EDITOR);
						$this->journalLayoutUserId = $userId; // Assume one LE per journal, as per OJS 1.x semantics
						break;
				}
				
				if (!$existingUser || !$roleDao->roleExists($role->getJournalId(), $role->getUserId(), $role->getRoleId())) {
					$roleDao->insertRole($role);
				}
			}
			
			if ($row['fkAuthorID']) {
				$role = &new Role();
				$role->setJournalId($this->journalId);
				$role->setUserId($userId);
				$role->setRoleId(ROLE_ID_AUTHOR);
				if (!$existingUser || !$roleDao->roleExists($role->getJournalId(), $role->getUserId(), $role->getRoleId())) {
					$roleDao->insertRole($role);
				}
			}
			
			if ($row['fkReviewerID']) {
				$role = &new Role();
				$role->setJournalId($this->journalId);
				$role->setUserId($userId);
				$role->setRoleId(ROLE_ID_REVIEWER);
				if (!$existingUser || !$roleDao->roleExists($role->getJournalId(), $role->getUserId(), $role->getRoleId())) {
					$roleDao->insertRole($role);
				}
			}
			
			if ($row['fkCopyEdID']) {
				$role = &new Role();
				$role->setJournalId($this->journalId);
				$role->setUserId($userId);
				$role->setRoleId(ROLE_ID_COPYEDITOR);
				if (!$existingUser || !$roleDao->roleExists($role->getJournalId(), $role->getUserId(), $role->getRoleId())) {
					$roleDao->insertRole($role);
				}
			}
			
			if ($row['fkProofID']) {
				$role = &new Role();
				$role->setJournalId($this->journalId);
				$role->setUserId($userId);
				$role->setRoleId(ROLE_ID_PROOFREADER);
				if (!$existingUser || !$roleDao->roleExists($role->getJournalId(), $role->getUserId(), $role->getRoleId())) {
					$roleDao->insertRole($role);
				}
			}
			
			if ($row['fkReaderID']) {
				$role = &new Role();
				$role->setJournalId($this->journalId);
				$role->setUserId($userId);
				$role->setRoleId(ROLE_ID_READER);
				if (!$existingUser || !$roleDao->roleExists($role->getJournalId(), $role->getUserId(), $role->getRoleId())) {
					$roleDao->insertRole($role);
				}
			}
			
			$this->userMap[$row['nUserID']] = $userId;
			$this->userCount++;
			$result->MoveNext();
		}
		$result->Close();
	}
	
	/**
	 * Import subscriptions and subscription types.
	 */
	function importSubscriptions() {
		if ($this->hasOption('verbose')) {
			printf("Importing subscriptions\n");
		}
		
		$subscriptionTypeDao = &DAORegistry::getDAO('SubscriptionTypeDAO');
		$subscriptionDao = &DAORegistry::getDAO('SubscriptionDAO');
		
		$subscriptionTypeMap = array();
		
		$subscriptionFormatMap = array(
			1 => SUBSCRIPTION_TYPE_FORMAT_PRINT,
			2 => SUBSCRIPTION_TYPE_FORMAT_ONLINE,
			3 => SUBSCRIPTION_TYPE_FORMAT_PRINT_ONLINE
		);
		
		$currencyMap = array(
			1 => 22,	// CDN
			2 => 160	// USD
		);
		
		$result = &$this->importDao->retrieve('SELECT * FROM tblsubscriptiontype ORDER BY nOrder');
		$count = 0;
		while (!$result->EOF) {
			$row = &$result->fields;
			
			$subscriptionType = &new SubscriptionType();
			$subscriptionType->setJournalId($this->journalId);
			$subscriptionType->setTypeName($row['chSubscriptionType']);
			$subscriptionType->setDescription($row['chSubscriptionTypeDesc']);
			$subscriptionType->setCost($row['fCost']);
			$subscriptionType->setCurrencyId(isset($currencyMap[$row['fkCurrencyID']]) ? $currencyMap[$row['fkCurrencyID']] : 160);
			$subscriptionType->setDuration(12); // No equivalent in OJS 1.x
			$subscriptionType->setFormat(isset($subscriptionFormatMap[$row['fkSubscriptionFormatID']]) ? $subscriptionFormatMap[$row['fkSubscriptionFormatID']] : SUBSCRIPTION_TYPE_FORMAT_PRINT_ONLINE);
			$subscriptionType->setInstitutional($row['bInstitutional']);
			$subscriptionType->setMembership($row['bMembership']);
			$subscriptionType->setPublic(1); // No equivalent in OJS 1.x
			$subscriptionType->setSequence(++$count);
			
			$subscriptionTypeDao->insertSubscriptionType($subscriptionType);
			$subscriptionTypeMap[$row['nSubscriptionTypeID']] = $subscriptionType->getTypeId();
			$result->MoveNext();
		}
		$result->Close();
		
		$result = &$this->importDao->retrieve('SELECT tblsubscribers.*, nUserID FROM tblsubscribers LEFT JOIN tblusers ON nSubscriberID = fkSubscriberID ORDER BY nSubscriberID');
		while (!$result->EOF) {
			$row = &$result->fields;
			
			$subscription = &new Subscription();
			$subscription->setJournalId($this->journalId);
			$subscription->setUserId(isset($this->userMap[$row['nUserID']]) ? $this->userMap[$row['nUserID']] : 0);
			$subscription->setTypeId(isset($subscriptionTypeMap[$row['fkSubscriptionTypeID']]) ? $subscriptionTypeMap[$row['fkSubscriptionTypeID']] : 0);
			$subscription->setDateStart($row['dtDateStart']);
			$subscription->setDateEnd($row['dtDateEnd']);
			$subscription->setMembership($row['chMembership']);
			$subscription->setDomain($row['chDomain']);
			$subscription->setIPRange('');
			
			$subscriptionDao->insertSubscription($subscription);
			$result->MoveNext();
		}
		$result->Close();
	}
	
	
	//
	// Issues
	//
	
	/**
	 * Import issues.
	 */
	function importIssues() {
		if ($this->hasOption('verbose')) {
			printf("Importing issues\n");
		}
		
		$issueDao = &DAORegistry::getDAO('IssueDAO');
		
		$this->issueLabelFormat = ISSUE_LABEL_NUM_VOL_YEAR;
		if ($this->journalInfo['nSchedulingType'] == 1 && !$this->journalInfo['bPubUseNum']) {
			$this->issueLabelFormat = ISSUE_LABEL_VOL_YEAR;
		} else if($this->journalInfo['nSchedulingType'] == 2) {
			$this->issueLabelFormat = ISSUE_LABEL_YEAR;
		}
		
		$result = &$this->importDao->retrieve('SELECT * FROM tblissues ORDER BY bPublished DESC, bLive ASC, nYear ASC, nVolume ASC, nNumber ASC');
		while (!$result->EOF) {
			$row = &$result->fields;
			
			$issue = &new Issue();
			$issue->setJournalId($this->journalId);
			$issue->setTitle($row['chIssueTitle']);
			$issue->setVolume($row['nVolume']);
			$issue->setNumber($row['nNumber']);
			$issue->setYear($row['nYear']);
			$issue->setPublished($row['bPublished']);
			$issue->setCurrent($row['bLive']);
			$issue->setDatePublished($row['dtDatePublished']);
			$issue->setAccessStatus(OPEN_ACCESS);
			$issue->setOpenAccessDate(isset($row['dtDateOpenAccess']) ? $row['dtDateOpenAccess'] : null);
			$issue->setLabelFormat($this->issueLabelFormat);
			$issue->setDescription('');
			$issue->setShowCoverPage(0);
			
			$issueId = $issueDao->insertIssue($issue);
			$this->issueMap[$row['nIssueID']] = $issueId;
			$this->issueCount++;
			$result->MoveNext();
		}
		$result->Close();
		
		if ($this->issueLabelFormat == ISSUE_LABEL_YEAR) {
			// Insert issues for each year in "publish by year" mode
			$result = &$this->importDao->retrieve('SELECT DISTINCT(DATE_FORMAT(dtDatePublished, \'%Y\')) FROM tblarticles WHERE bPublished = 1 ORDER BY year');
			while (!$result->EOF) {
				list($year) = $result->fields;
				
				$issue = &new Issue();
				$issue->setJournalId($this->journalId);
				$issue->setTitle('');
				$issue->setVolume('');
				$issue->setNumber('');
				$issue->setYear($year);
				$issue->setPublished(1);
				$issue->setDatePublished($year . '-01-01');
				$issue->setAccessStatus(OPEN_ACCESS);
				$issue->setOpenAccessDate(null);
				$issue->setLabelFormat($this->issueLabelFormat);
				
				$result->MoveNext();
			
				$issue->setCurrent($result->EOF ? 1 : 0);	
				$issueId = $issueDao->insertIssue($issue);
				$this->issueMap['YEAR' . $year] = $issueId;
				$this->issueCount++;
			}
			$result->Close();			
		}
	}
	
	/**
	 * Import sections.
	 */
	function importSections() {
		if ($this->hasOption('verbose')) {
			printf("Importing sections\n");
		}
		
		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		$sectionEditorDao = &DAORegistry::getDAO('SectionEditorsDAO');
		
		$result = &$this->importDao->retrieve('SELECT * FROM tblsections ORDER BY nRank');
		$count = 0;
		while (!$result->EOF) {
			$row = &$result->fields;
			
			$section = &new Section();
			$section->setJournalId($this->journalId);
			$section->setTitle($row['chTitle']);
			$section->setAbbrev($row['chAbbrev']);
			$section->setSequence(++$count);
			$section->setMetaIndexed($row['bMetaIndex']);
			$section->setEditorRestricted($row['bAcceptSubmissions'] ? 0 : 1);
			$section->setPolicy($row['chPolicies']);
			
			$sectionId = $sectionDao->insertSection($section);
			$this->sectionMap[$row['nSectionID']] = $sectionId;
			$result->MoveNext();
		}
		$result->Close();
		
		// Note: ignores board members (not supported in OJS 1.x)
		$result = &$this->importDao->retrieve('SELECT nUserID, fkSectionID FROM tblusers, tbleditorsections WHERE tblusers.fkEditorID = tbleditorsections.fkEditorID AND fkSectionID IS NOT NULL AND fkSectionID != -1 ORDER BY nUserID');
		while (!$result->EOF) {
			$row = &$result->fields;
			
			if (isset($this->sectionMap[$row['fkSectionID']]) && isset($this->userMap[$row['nUserID']])) {
				$sectionEditorDao->insertEditor($this->journalId, $this->sectionMap[$row['fkSectionID']], $this->userMap[$row['nUserID']]);
			}
			
			$result->MoveNext();
		}
		$result->Close();
	}
	
	/**
	 * Import articles (including metadata and files).
	 */
	function importArticles() {
		if ($this->hasOption('verbose')) {
			printf("Importing articles\n");
		}
		
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
		$galleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');
		$editAssignmentDao = &DAORegistry::getDAO('EditAssignmentDAO');
		$copyAssignmentDao = &DAORegistry::getDAO('CopyeditorSubmissionDAO');
		$layoutAssignmentDao = &DAORegistry::getDAO('LayoutAssignmentDAO');
		$proofAssignmentDao = &DAORegistry::getDAO('ProofAssignmentDAO');
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		
		$articleUsers = array();
		
		$reviewRecommendations = array(
			0 => null,
			1 => null,
			2 => SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT,
			3 => SUBMISSION_REVIEWER_RECOMMENDATION_PENDING_REVISIONS,
			4 => SUBMISSION_REVIEWER_RECOMMENDATION_RESUBMIT_HERE,
			5 => SUBMISSION_REVIEWER_RECOMMENDATION_RESUBMIT_ELSEWHERE,
			6 => SUBMISSION_REVIEWER_RECOMMENDATION_DECLINE,
			7 => SUBMISSION_REVIEWER_RECOMMENDATION_SEE_COMMENTS
		);
		
		// Import articles
		$result = &$this->importDao->retrieve('SELECT tblarticles.*, editor.nUserID AS nEditorUserID FROM tblarticles LEFT JOIN tblusers AS editor ON (tblarticles.fkEditorId = editor.fkEditorID) ORDER by nArticleID');
		while (!$result->EOF) {
			$row = &$result->fields;
			
			$status = STATUS_QUEUED;
			if ($row['nStatus'] !== null) {
				if ($row['nStatus'] == 3) {
					$status = STATUS_DECLINED;
				} else if ($row['bArchive']) {
					$status = STATUS_ARCHIVED;
				} else if($row['bPublished']) {
					$status = STATUS_PUBLISHED;
				} else if($row['bSchedule']) {
					$status = STATUS_SCHEDULED;
				}
			}
			
			$article = &new Article();
			$article->setUserId(1);
			$article->setJournalId($this->journalId);
			$article->setSectionId(isset($this->sectionMap[$row['fkSectionID']]) ? $this->sectionMap[$row['fkSectionID']] : 0);
			$article->setTitle($row['chMetaTitle']);
			$article->setTitleAlt1('');
			$article->setTitleAlt2('');
			$article->setAbstract($row['chMetaAbstract']);
			$article->setAbstractAlt1('');
			$article->setAbstractAlt2('');
			$article->setDiscipline($row['chMetaDiscipline']);
			$article->setSubjectClass($row['chMetaSubjectClass']);
			$article->setSubject($row['chMetaSubject']);
			$article->setCoverageGeo($row['chMetaCoverageGeo']);
			$article->setCoverageChron($row['chMetaCoverageChron']);
			$article->setCoverageSample($row['chMetaCoverageSample']);
			$article->setType($row['chMetaType_Author']);
			$article->setLanguage($row['chMetaLanguage']);
			$article->setSponsor($row['chMetaSponsor_Author']);
			$article->setCommentsToEditor($row['chNotesToEditor']);
			$article->setDateSubmitted($row['dtDateSubmitted']);
			$article->setDateStatusModified($row['dtDateSubmitted']);
			$article->setLastModified($row['dtDateSubmitted']);
			$article->setStatus($status);
			$article->setSubmissionProgress($row['dtDateSubmitted'] ? 0 : $row['nSubmissionProgress']);
			$article->setCurrentRound(1);
			$article->setPages('');
			
			// Add article authors
			$authorResult = &$this->importDao->retrieve('SELECT nUserID, tblmetaauthors.* FROM tblmetaauthors LEFT JOIN tblusers ON tblmetaauthors.fkAuthorID = tblusers.fkAuthorID WHERE fkArticleID = ? ORDER BY nRank', $row['nArticleID']);
			while (!$authorResult->EOF) {
				$authorRow = &$authorResult->fields;
				
				$author = &new Author();
				$author->setFirstName($authorRow['chFirstName']);
				$author->setMiddleName($authorRow['chMiddleInitial']);
				$author->setLastName($authorRow['chSurname']);
				$author->setAffiliation($authorRow['chAffiliation']);
				$author->setEmail($authorRow['chEmail']);
				$author->setBiography($authorRow['chBiography']);
				$author->setPrimaryContact($authorRow['bPrimaryContact']);
				
				if ($authorRow['bPrimaryContact'] && isset($this->userMap[$authorRow['nUserID']])) {
					$article->setUserId($this->userMap[$authorRow['nUserID']]);
				}
				
				$article->addAuthor($author);
				$authorResult->MoveNext();
			}
			$authorResult->Close();
			
			$articleDao->insertArticle($article);
			$articleId = $article->getArticleId();
			$this->articleMap[$row['nArticleID']] = $articleId;
			$this->articleCount++;
			
			$articleUsers[$articleId] = array(
				'authorId' => $article->getUserId(),
				'editorId' => $article->getUserId(),
				'proofId' => 0,
				'reviewerId' => array()
			);
			
			if (empty($row['fkIssueID']) && $row['bPublished'] && $row['dtDatePublished'] && $this->issueLabelFormat == ISSUE_LABEL_YEAR) {
				$row['fkIssueID'] = 'YEAR' . date('Y', strtotime($row['dtDatePublished']));
			}
			
			if ($row['fkIssueID']) {
				$publishedArticle = &new PublishedArticle();
				$publishedArticle->setArticleId($articleId);
				$publishedArticle->setIssueId($this->issueMap[$row['fkIssueID']]);
				$publishedArticle->setDatePublished($row['dtDatePublished']);
				$publishedArticle->setSeq((int)$row['nOrder']);
				$publishedArticle->setViews($row['nHitCounter']);
				$publishedArticle->setSectionId(isset($this->sectionMap[$row['fkSectionID']]) ? $this->sectionMap[$row['fkSectionID']] : 0);
				$publishedArticle->setAccessStatus(isset($row['fkPublishStatusID']) && $row['fkPublishStatusID'] == 2 ? SUBSCRIPTION : OPEN_ACCESS);
				
				$publishedArticleDao->insertPublishedArticle($publishedArticle);
			}
			
			// Article files
			if ($row['fkFileOriginalID']) {
				$fileId = $this->addArticleFile($articleId, $row['fkFileOriginalID'], ARTICLE_FILE_SUBMISSION);
				$article->setSubmissionFileId($fileId);
			}
			if ($row['fkFileRevisionsID']) {
				$fileId = $this->addArticleFile($articleId, $row['fkFileRevisionsID'], ARTICLE_FILE_EDITOR);
				$article->setRevisedFileId($fileId);
			}
			if ($row['fkFileEditorID']) {
				$fileId = $this->addArticleFile($articleId, $row['fkFileEditorID'], ARTICLE_FILE_EDITOR);
				$article->setEditorFileId($fileId);
			}
			
			if ($row['dtDateSubmitted']) {
				$fileManager = &new ArticleFileManager($articleId);
				
				if ($article->getSubmissionFileId()) {
					// Copy submission file to review version (not separate in OJS 1.x)
					$fileId = $fileManager->copyToReviewFile($article->getSubmissionFileId());
					$article->setReviewFileId($fileId);
					if (!$article->getEditorFileId()) {
						$fileId = $fileManager->copyToEditorFile($fileId);
						$article->setEditorFileId($fileId);
					}
				}
				
				// Add editor decision and review round (only one round in OJS 1.x)
				if ($row['dtDateEdDec']) {
					$articleDao->update('INSERT INTO edit_decisions
							(article_id, round, editor_id, decision, date_decided)
							VALUES (?, ?, ?, ?, ?)',
							array($articleId, 1, isset($this->userMap[$row['nEditorUserID']]) ? $this->userMap[$row['nEditorUserID']] : 0, $row['nStatus'] == 3 ? SUBMISSION_EDITOR_DECISION_DECLINE : SUBMISSION_EDITOR_DECISION_ACCEPT, $row['dtDateEdDec']));
				}
				
				$articleDao->update('INSERT INTO review_rounds
					(article_id, round, review_revision)
					VALUES
					(?, ?, ?)',
					array($articleId, 1, 1)
				);
				
				// Article galleys
				if ($row['fkFileHTMLID']) {
					$fileId = $this->addArticleFile($articleId, $row['fkFileHTMLID'], ARTICLE_FILE_PUBLIC);
					$galley = &new ArticleHTMLGalley();
					$galley->setArticleId($articleId);
					$galley->setFileId($fileId);
					$galley->setLabel('HTML');
					$galley->setSequence(1);
					if ($row['fkFileStyleID']) {
						$fileId = $this->addArticleFile($articleId, $row['fkFileStyleID'], ARTICLE_FILE_PUBLIC);
						$galley->setStyleFile($fileId);
					}
					$galleyDao->insertGalley($galley);
					$this->copyHTMLGalleyImages($galley, $row['chLongID']);
				}
				if ($row['fkFilePDFID']) {
					$fileId = $this->addArticleFile($articleId, $row['fkFilePDFID'], ARTICLE_FILE_PUBLIC);
					$galley = &new ArticleGalley();
					$galley->setArticleId($articleId);
					$galley->setFileId($fileId);
					$galley->setLabel('PDF');
					$galley->setSequence(2);
					$galleyDao->insertGalley($galley);
				}
				if ($row['fkFilePostScriptID']) {
					$fileId = $this->addArticleFile($articleId, $row['fkFilePostScriptID'], ARTICLE_FILE_PUBLIC);
					$galley = &new ArticleGalley();
					$galley->setArticleId($articleId);
					$galley->setFileId($fileId);
					$galley->setLabel('PostScript');
					$galley->setSequence(3);
					$galleyDao->insertGalley($galley);
				}
			
				// Create submission management assignment records
				if ($row['nEditorUserID']) {
					// Editor assignment
					$editAssignment = &new EditAssignment();
					$editAssignment->setArticleId($articleId);
					$editAssignment->setEditorId($this->userMap[$row['nEditorUserID']]);
					$editAssignment->setDateNotified($row['dtDateEditorNotified']);
					$editAssignment->setDateUnderway($row['dtDateEditorNotified']);
					$editAssignmentDao->insertEditAssignment($editAssignment);
				}
				
				// Copyediting assignment
				$copyAssignment = &new CopyeditorSubmission();
				$copyAssignment->setArticleId($articleId);
				$copyResult = &$this->importDao->retrieve('SELECT tblcopyedit.*, nUserID FROM tblcopyedit, tblarticlesassigned, tblusers WHERE tblcopyedit.fkArticleID = tblarticlesassigned.fkArticleID AND tblusers.fkCopyEdID = tblarticlesassigned.fkCopyEdID AND bReplaced = 0 AND bDeclined = 0 AND tblcopyedit.fkArticleID = ?', $row['nArticleID']);
				if ($copyResult->RecordCount() != 0) {
					$copyRow = &$copyResult->fields;
					
					if ($copyRow['fkFileCopyEdID']) {
						$fileId = $this->addArticleFile($articleId, $copyRow['fkFileCopyEdID'], ARTICLE_FILE_COPYEDIT);
						$article->setCopyeditFileId($fileId);
					}
					
					$copyAssignment->setCopyeditorId($this->userMap[$copyRow['nUserID']]);
					$copyAssignment->setDateNotified($copyRow['dtDateNotified_CEd']);
					$copyAssignment->setDateUnderway($copyRow['dtDateNotified_CEd']);
					$copyAssignment->setDateCompleted($copyRow['dtDateCompleted_CEd']);
					$copyAssignment->setDateAcknowledged($copyRow['dtDateAcknowledged_CEd']);
					$copyAssignment->setDateAuthorNotified($copyRow['dtDateNotified_Author']);
					$copyAssignment->setDateAuthorUnderway($copyRow['dtDateNotified_Author']);
					$copyAssignment->setDateAuthorCompleted($copyRow['dtDateCompleted_Author']);
					$copyAssignment->setDateAuthorAcknowledged($copyRow['dtDateAcknowledged_Author']);
					$copyAssignment->setDateFinalNotified($copyRow['dtDateNotified_Final']);
					$copyAssignment->setDateFinalUnderway($copyRow['dtDateNotified_Final']);
					$copyAssignment->setDateFinalCompleted($copyRow['dtDateCompleted_Final']);
					$copyAssignment->setDateFinalAcknowledged($copyRow['dtDateAcknowledged_Final']);
					$copyAssignment->setInitialRevision(1);
					$copyAssignment->setEditorAuthorRevision(1);
					$copyAssignment->setFinalRevision(1);
				} else {
					$copyAssignment->setCopyeditorId(0);
				}
				$copyResult->Close();
				$copyAssignmentDao->insertCopyeditorSubmission($copyAssignment);
				
				$layoutAssignment = &new LayoutAssignment();
				$layoutAssignment->setArticleId($articleId);
		
				// Proofreading assignment
				$proofAssignment = &new ProofAssignment();
				$proofAssignment->setArticleId($articleId);
				$proofResult = &$this->importDao->retrieve('SELECT tblproofread.*, nUserID, dtDateSchedule FROM tblproofread, tblarticlesassigned, tblusers, tblarticles WHERE tblproofread.fkArticleID = tblarticles.nArticleID AND tblproofread.fkArticleID = tblarticlesassigned.fkArticleID AND tblusers.fkProofID = tblarticlesassigned.fkProofID AND bReplaced = 0 AND bDeclined = 0 AND tblproofread.fkArticleID = ?', $row['nArticleID']);
				if ($proofResult->RecordCount() != 0) {
					$proofRow = &$proofResult->fields;
					
					if ($proofRow['fkFileProofID']) {
						// Treat proofreader file as layout file
						$fileId = $this->addArticleFile($articleId, $proofRow['fkFileProofID'], ARTICLE_FILE_LAYOUT);
						$layoutAssignment->setLayoutFileId($fileId);
					}
					
					$proofAssignment->setProofreaderId($this->userMap[$proofRow['nUserID']]);
					$proofAssignment->setDateSchedulingQueue($proofRow['dtDateSchedule']);
					$proofAssignment->setDateAuthorNotified($proofRow['dtDateNotified_Author']);
					$proofAssignment->setDateAuthorUnderway($proofRow['dtDateNotified_Author']);
					$proofAssignment->setDateAuthorCompleted($proofRow['dtDateCompleted_Author']);
					$proofAssignment->setDateAuthorAcknowledged($proofRow['dtDateAcknowledged_Author']);
					$proofAssignment->setDateProofreaderNotified($proofRow['dtDateNotified_Proof']);
					$proofAssignment->setDateProofreaderUnderway($proofRow['dtDateNotified_Proof']);
					$proofAssignment->setDateProofreaderCompleted($proofRow['dtDateCompleted_Proof']);
					$proofAssignment->setDateProofreaderAcknowledged($proofRow['dtDateAcknowledged_Proof']);
					$proofAssignment->setDateLayoutEditorNotified(null); // Not applicable to 1.x
					$proofAssignment->setDateLayoutEditorUnderway(null);
					$proofAssignment->setDateLayoutEditorCompleted(null);
					$proofAssignment->setDateLayoutEditorAcknowledged(null);
				} else {
					$proofAssignment->setProofreaderId(0);
				}
				$proofResult->Close();
				$proofAssignmentDao->insertProofAssignment($proofAssignment);
				
				// Layout editing assignment
				$layoutAssignment->setEditorId($this->journalLayoutUserId);
				$layoutAssignment->setDateNotified($row['dtDateRequestGalleys']);
				$layoutAssignment->setDateUnderway($row['dtDateRequestGalleys']);
				$layoutAssignment->setDateCompleted($row['dtDateGalleysCompleted']);
				$layoutAssignment->setDateAcknowledged($row['dtDateGalleysCompleted']);
				$layoutAssignmentDao->insertLayoutAssignment($layoutAssignment);
				
				$reviewerOrder = 1;
				$reviewResult = &$this->importDao->retrieve('SELECT tblreviews.*, tblarticlesassigned.*, nUserID FROM tblreviews, tblarticlesassigned, tblusers, tblarticles WHERE tblreviews.fkArticleID = tblarticles.nArticleID AND tblreviews.fkArticleID = tblarticlesassigned.fkArticleID AND tblusers.fkReviewerID = tblarticlesassigned.fkReviewerID AND tblreviews.fkArticleID = ?', $articleId);
				while (!$reviewResult->EOF) {
					$reviewRow = &$reviewResult->fields;
					
					$reviewAssignment = &new ReviewAssignment();
					
					if ($reviewRow['fkFileRevCopyID']) {
						$fileId = $this->addArticleFile($articleId, $reviewRow['fkFileRevCopyID'], ARTICLE_FILE_REVIEW);
						$reviewAssignment->setReviewFileId($fileId);
					}
					
					$reviewAssignment->setArticleId($articleId);
					$reviewAssignment->setReviewerId($this->userMap[$reviewRow['nUserID']]);
					$reviewAssignment->setRecommendation($reviewRecommendations[(int)$reviewRow['nRecommendation']]);
					$reviewAssignment->setDateAssigned($reviewRow['dtDateAssigned']);
					$reviewAssignment->setDateNotified($reviewRow['dtDateNotified']);
					$reviewAssignment->setDateConfirmed($reviewRow['dtDateConfirmedDeclined']);
					$reviewAssignment->setDateCompleted($reviewRow['dtDateReviewed']);
					$reviewAssignment->setDateAcknowledged($reviewRow['dtDateAcknowledged']);
					$reviewAssignment->setDateDue($reviewRow['dtDateRequestedBy']);
					$reviewAssignment->setLastModified(isset($reviewRow['dtDateReviewed']) ? $reviewRow['dtDateReviewed'] : (isset($reviewRow['dtDateConfirmedDeclined']) ? $reviewRow['dtDateConfirmedDeclined'] : $reviewRow['dtDateAssigned']));
					$reviewAssignment->setDeclined($reviewRow['bDeclined']);
					$reviewAssignment->setReplaced($reviewRow['bReplaced']);
					$reviewAssignment->setCancelled(0); // Not applicable to 1.x
					$reviewAssignment->setQuality(null);
					$reviewAssignment->setDateRated(null);
					$reviewAssignment->setDateReminded($reviewRow['dtDateReminded']);
					$reviewAssignment->setReminderWasAutomatic(0);
					$reviewAssignment->setRound(1);
					
					if (!$reviewRow['bReplaced']) {
						$articleUsers[$articleId]['reviewerId'][$reviewerOrder] = $reviewAssignment->getReviewerId();
						$reviewerOrder++;
					}
					
					$reviewAssignmentDao->insertReviewAssignment($reviewAssignment);
					$reviewResult->MoveNext();
				}
				$reviewResult->Close();
			}
			
			// Update article with file IDs, etc.
			$articleDao->updateArticle($article);
			
			$result->MoveNext();
		}
		$result->Close();
		
		
		// Supplementary files
		$suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
		
		$result = &$this->importDao->retrieve('SELECT * FROM tblsupplementaryfiles ORDER BY nSupFileID');
		while (!$result->EOF) {
			$row = &$result->fields;
			
			$fileId = $this->addArticleFile($this->articleMap[$row['fkArticleID']], $row['fkFileID'], ARTICLE_FILE_SUPP);
			
			$suppFile = &new SuppFile();
			$suppFile->setFileId($fileId);
			$suppFile->setArticleId($this->articleMap[$row['fkArticleID']]);
			$suppFile->setTitle($row['chTitle']);
			$suppFile->setCreator($row['chCreator']);
			$suppFile->setSubject($row['chSubject']);
			$suppFile->setType($row['chType']);
			$suppFile->setTypeOther($row['chTypeOther']);
			$suppFile->setDescription($row['chDescription']);
			$suppFile->setPublisher($row['chPublisher']);
			$suppFile->setSponsor($row['chSponsor']);
			$suppFile->setDateCreated($row['dtDateCreated']);
			$suppFile->setSource($row['chSource']);
			$suppFile->setLanguage($row['chLanguage']);
			$suppFile->setShowReviewers($row['bShowReviewer']);
			$suppFile->setDateSubmitted($row['dtDateCreated']);
			
			$suppFileDao->insertSuppFile($suppFile);
			$result->MoveNext();
		}
		$result->Close();
		
		
		// Article (public) comments
		$commentDao = &DAORegistry::getDAO('CommentDAO');
		
		$result = &$this->importDao->retrieve('SELECT * FROM tblcomments ORDER BY nCommentID');
		while (!$result->EOF) {
			$row = &$result->fields;
			
			if (!empty($row['chAffiliation'])) {
				$row['chAuthor'] .= ', ' . $row['chAffiliation'];
			}
			
			$comment = &new Comment();
			$comment->setArticleId($this->articleMap[$row['fkArticleID']]);
			$comment->setPosterIP('');
			$comment->setPosterName($row['chAuthor']);
			$comment->setPosterEmail($row['chEmail']);
			$comment->setTitle($row['chCommentTitle']);
			$comment->setBody($row['chComments']);
			$comment->setDatePosted($row['dtDate']);
			$comment->setDateModified($row['dtDate']);
			$comment->setChildCommentCount(0);
			
			$commentDao->insertComment($comment);
			$result->MoveNext();
		}
		$result->Close();

		
		// Submission comments
		$articleCommentDao = &DAORegistry::getDAO('ArticleCommentDAO');
		
		$commentTypes = array(
			'reviewer' => COMMENT_TYPE_PEER_REVIEW,
			'editorrev' => COMMENT_TYPE_EDITOR_DECISION,
			'proof' => COMMENT_TYPE_PROOFREAD
		);
		
		$result = &$this->importDao->retrieve('SELECT * FROM tblsubmissioncomments ORDER BY nCommentID');
		while (!$result->EOF) {
			$row = &$result->fields;
			
			// Stupidly these strings are localized so this won't necessarily work if using non-English or modified localization
			switch ($row['chFrom']) {
				case 'Author':
					$authorId = $articleUsers[$this->articleMap[$row['fkArticleID']]]['authorId'];
					$roleId = ROLE_ID_AUTHOR;
					break;
				case 'Proofreader':
					$authorId = $articleUsers[$this->articleMap[$row['fkArticleID']]]['proofId'];
					$roleId = ROLE_ID_PROOFREADER;
					break;
				case 'Reviewer':
					$authorId = @$articleUsers[$this->articleMap[$row['fkArticleID']]]['reviewerId']['nOrder'];
					$roleId = ROLE_ID_REVIEWER;
					break;
				case 'Editor':
				default:
					$authorId = $articleUsers[$this->articleMap[$row['fkArticleID']]]['editorId'];
					$roleId = ROLE_ID_EDITOR;
					break;
			}
			
			if (!isset($authorId)) {
				// Assume "Editor" by default
				$authorId = $articleUsers[$this->articleMap[$row['fkArticleID']]]['editorId'];
				$roleId = ROLE_ID_EDITOR;
			}
			
			$articleComment = &new ArticleComment();
			$articleComment->setCommentType($commentTypes[$row['chType']]);
			$articleComment->setRoleId($roleId);
			$articleComment->setArticleId($this->articleMap[$row['fkArticleID']]);
			$articleComment->setAssocId($this->articleMap[$row['fkArticleID']]);
			$articleComment->setAuthorId($authorId);
			$articleComment->setCommentTitle(''); // Not applicable to 1.x
			$articleComment->setComments($row['chComment']);
			$articleComment->setDatePosted($row['dtDateCreated']);
			$articleComment->setDateModified($row['dtDateCreated']);
			$articleComment->setViewable(0);
			
			$articleCommentDao->insertArticleComment($articleComment);
			$result->MoveNext();
		}
		$result->Close();
	}
	
	
	//
	// Helper functions
	//
	
	/**
	 * Rebuild the article search index.
	 * Note: Rebuilds index for _all_ journals (non-optimal, but shouldn't be a problem)
	 * Based on code from tools/rebuildSearchIndex.php
	 */
	function rebuildSearchIndex() {
		if ($this->hasOption('verbose')) {
			printf("Rebuilding search index\n");
		}
		
		// Clear index
		$searchDao = &DAORegistry::getDAO('ArticleSearchDAO');
		$searchDao->update('DELETE FROM article_search_keyword_index');
		$searchDao->update('DELETE FROM article_search_keyword_list');
		
		// Build index
		$journalDao = &DAORegistry::getDAO('JournalDAO');
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		
		$journals = &$journalDao->getJournals();
		while (!$journals->eof()) {
			$journal = &$journals->next();
			
			$articles = &$articleDao->getArticlesByJournalId($journal->getJournalId());
			while (!$articles->eof()) {
				$article = &$articles->next();
				if ($article->getDateSubmitted()) {
					ArticleSearchIndex::indexArticleMetadata($article);
					ArticleSearchIndex::indexArticleFiles($article);
				}
			}
		}
	}
	
	/**
	 * Copy a journal title/logo image.
	 * @param $oldName string old setting name
	 * @param $newName string new setting name
	 * @return array image info
	 */
	function copyJournalImage($oldName, $newName) {
		if (empty($this->journalInfo[$oldName])) {
			return null;
		}
		
		$oldPath = $this->importPath . '/images/custom/' . $this->journalInfo[$oldName];
		if (!file_exists($oldPath)) {
			return null;
		}
		
		list($width, $height) = getimagesize($oldPath);
		
		$fileManager = &new PublicFileManager();
		$extension = $fileManager->getExtension($this->journalInfo[$oldName]);
				
		$uploadName = $newName . '.' . $extension;
		if (!$fileManager->copyJournalFile($this->journalId, $oldPath, $uploadName)) {
			printf("Failed to copy file %s\n", $oldPath);
			return null; // This should never happen
		}
		
		return array(
			'name' => $this->journalInfo[$oldName],
			'uploadName' => $uploadName,
			'width' => $width,
			'height' => $height,
			'dateUploaded' => Core::getCurrentDate()
		);
	}
	
	/**
	 * Copy an article file.
	 * @param $articleId int
	 * @param $oldFileId int
	 * @param $fileType string
	 */
	function addArticleFile($articleId, $oldFileId, $fileType) {
		if (!$oldFileId) {
			return 0;
		}
		
		$result = &$this->importDao->retrieve('SELECT * FROM tblfiles WHERE nFileID = ?', $oldFileId);

		if ($result->RecordCount() == 0) {
			return 0;
		}
		
		$row = &$result->fields;
		$oldPath = $this->journalConfigInfo['chFilePath'] . $row['chFilePath'];
		
		$fileManager = &new ArticleFileManager($articleId);
		$articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');
		
		$articleFile = &new ArticleFile();
		$articleFile->setArticleId($articleId);
		$articleFile->setFileName('temp');
		$articleFile->setOriginalFileName($row['chOldFileName']);
		$articleFile->setFileType($row['chFileType']);
		$articleFile->setFileSize(filesize($oldPath));
		$articleFile->setType($fileManager->typeToPath($fileType));
		$articleFile->setStatus('');
		$articleFile->setDateUploaded($row['dtDateUploaded']);
		$articleFile->setDateModified($row['dtDateUploaded']);
		$articleFile->setRound(1);
		$articleFile->setRevision(1);
		
		$fileId = $articleFileDao->insertArticleFile($articleFile);
		
		$newFileName = $fileManager->generateFilename($articleFile, $fileType, $row['chOldFileName']);
		if (!$fileManager->copyFile($oldPath, $fileManager->filesDir . $fileManager->typeToPath($fileType) . '/' . $newFileName)) {
			$articleFileDao->deleteArticleFileById($articleFile->getFileId());
			printf("Failed to copy file %s\n", $oldPath);
			return 0; // This should never happen
		}
		
		$articleFileDao->updateArticleFile($articleFile);
		$this->fileMap[$oldFileId] = $fileId;
		
		return $fileId;
	}
	
	/**
	 * Copy all image files for an article's HTML galley.
	 * @param $galley ArticleHTMLGalley
	 * @param $prefix string image file prefix, e.g. "<abbrev>-<year>-<id>"
	 */
	function copyHTMLGalleyImages($galley, $prefix) {
		$dir = opendir($this->importPath . '/images/articleimages');
		if (!$dir) {
			printf("Failed to open directory %s\n", $this->importPath . '/images/articleimages');
			return; // This should never happen
		}
		
		while(($file = readdir($dir)) !== false) {
			if (!strstr($file, $prefix . '-')) {
				continue;
			}
			
			if (!isset($fileManager)) {
				$fileManager = &new ArticleFileManager($galley->getArticleId());
				$galleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');
				$articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');
			}
			
			$fileType = ARTICLE_FILE_PUBLIC;
			$oldPath = $this->importPath . '/images/articleimages/' . $file;
			
			if (function_exists('mime_content_type')) {
				$mimeType = mime_content_type($oldPath);
			} else {
				$extension = $fileManager->getExtension($file);
				if ($extension == 'jpg') {
					$mimeType = 'image/jpeg';
				} else {
					$mimeType = 'image/' . $extension;
				}
			}
		
			$articleFile = &new ArticleFile();
			$articleFile->setArticleId($galley->getArticleId());
			$articleFile->setFileName('temp');
			$articleFile->setOriginalFileName($file);
			$articleFile->setFileType($mimeType);
			$articleFile->setFileSize(filesize($oldPath));
			$articleFile->setType($fileManager->typeToPath($fileType));
			$articleFile->setStatus('');
			$articleFile->setDateUploaded(date('Y-m-d', filemtime($oldPath)));
			$articleFile->setDateModified($articleFile->getDateUploaded());
			$articleFile->setRound(1);
			$articleFile->setRevision(1);
			
			$fileId = $articleFileDao->insertArticleFile($articleFile);
			
			$newFileName = $fileManager->generateFilename($articleFile, $fileType, $file);
			if (!$fileManager->copyFile($oldPath, $fileManager->filesDir . $fileManager->typeToPath($fileType) . '/' . $newFileName)) {
				$articleFileDao->deleteArticleFileById($articleFile->getFileId());
				printf("Failed to copy file %s\n", $oldPath);
				// This should never happen
			} else {
				$articleFileDao->updateArticleFile($articleFile);
				$galleyDao->insertGalleyImage($galley->getGalleyId(), $fileId);
			}
		}
		
		closedir($dir);
	}
	
}

?>
