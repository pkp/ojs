<?php

/**
 * @file pages/about/AboutHandler.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AboutHandler
 * @ingroup pages_editor
 *
 * @brief Handle requests for editor functions. 
 */

import('classes.handler.Handler');

class AboutHandler extends Handler {
	/**
	 * Constructor
	 **/
	function AboutHandler() {
		parent::Handler();
	}

	/**
	 * Display about index page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function index($args, &$request) {
		$this->validate();
		$this->setupTemplate();

		$templateMgr =& TemplateManager::getManager();
		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$journalPath = $request->getRequestedJournalPath();

		if ($journalPath != 'index' && $journalDao->journalExistsByPath($journalPath)) {
			$journal =& $request->getJournal();

			$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
			$templateMgr->assign_by_ref('journalSettings', $journalSettingsDao->getJournalSettings($journal->getId()));

			$customAboutItems =& $journalSettingsDao->getSetting($journal->getId(), 'customAboutItems');
			if (isset($customAboutItems[AppLocale::getLocale()])) $templateMgr->assign('customAboutItems', $customAboutItems[AppLocale::getLocale()]);
			elseif (isset($customAboutItems[AppLocale::getPrimaryLocale()])) $templateMgr->assign('customAboutItems', $customAboutItems[AppLocale::getPrimaryLocale()]);

			foreach ($this->_getPublicStatisticsNames() as $name) {
				if ($journal->getSetting($name)) {
					$templateMgr->assign('publicStatisticsEnabled', true);
					break;
				} 
			}
			
			// Hide membership if the payment method is not configured
			import('classes.payment.ojs.OJSPaymentManager');
			$paymentManager = new OJSPaymentManager($request);
			$templateMgr->assign('paymentConfigured', $paymentManager->isConfigured());

			if ($journal->getSetting('boardEnabled')) {
				$groupDao =& DAORegistry::getDAO('GroupDAO');
				$groups =& $groupDao->getGroups(ASSOC_TYPE_JOURNAL, $journal->getId(), GROUP_CONTEXT_PEOPLE);
				$templateMgr->assign_by_ref('peopleGroups', $groups);
			}

			$templateMgr->assign('helpTopicId', 'user.about');
			$templateMgr->display('about/index.tpl');
		} else {
			$site =& $request->getSite();
			$about = $site->getLocalizedAbout();
			$templateMgr->assign('about', $about);

			$journals =& $journalDao->getJournals(true);
			$templateMgr->assign_by_ref('journals', $journals);
			$templateMgr->display('about/site.tpl');
		}
	}


	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false) {
		parent::setupTemplate();
		$templateMgr =& TemplateManager::getManager();
		$journal =& Request::getJournal();
		
		AppLocale::requireComponents(LOCALE_COMPONENT_OJS_MANAGER, LOCALE_COMPONENT_PKP_MANAGER);

		if (!$journal || !$journal->getSetting('restrictSiteAccess')) {
			$templateMgr->setCacheability(CACHEABILITY_PUBLIC);
		}
		if ($subclass) $templateMgr->assign('pageHierarchy', array(array(Request::url(null, 'about'), 'about.aboutTheJournal')));
	}

	/**
	 * Display contact page.
	 */
	function contact() {
		$this->addCheck(new HandlerValidatorJournal($this));
		$this->validate();

		$this->setupTemplate(true);

		$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
		$journal =& Request::getJournal();

		$templateMgr =& TemplateManager::getManager();
		$journalSettings =& $journalSettingsDao->getJournalSettings($journal->getId());
		$templateMgr->assign_by_ref('journalSettings', $journalSettings);
		$templateMgr->display('about/contact.tpl');
	}

	/**
	 * Display editorialTeam page.
	 */
	function editorialTeam() {
		$this->addCheck(new HandlerValidatorJournal($this));
		$this->validate();
		$this->setupTemplate(true);

		$journal =& Request::getJournal();
		$templateMgr =& TemplateManager::getManager();

		$countryDao =& DAORegistry::getDAO('CountryDAO');
		$countries =& $countryDao->getCountries();
		$templateMgr->assign_by_ref('countries', $countries);

		// FIXME: This is pretty inefficient; should probably be cached.

		if ($journal->getSetting('boardEnabled') != true) {
			// Don't use the Editorial Team feature. Generate
			// Editorial Team information using Role info.
			$roleDao =& DAORegistry::getDAO('RoleDAO');

			$editors =& $roleDao->getUsersByRoleId(ROLE_ID_EDITOR, $journal->getId());
			$editors =& $editors->toArray();

			$sectionEditors =& $roleDao->getUsersByRoleId(ROLE_ID_SECTION_EDITOR, $journal->getId());
			$sectionEditors =& $sectionEditors->toArray();

			$layoutEditors =& $roleDao->getUsersByRoleId(ROLE_ID_LAYOUT_EDITOR, $journal->getId());
			$layoutEditors =& $layoutEditors->toArray();

			$copyEditors =& $roleDao->getUsersByRoleId(ROLE_ID_COPYEDITOR, $journal->getId());
			$copyEditors =& $copyEditors->toArray();

			$proofreaders =& $roleDao->getUsersByRoleId(ROLE_ID_PROOFREADER, $journal->getId());
			$proofreaders =& $proofreaders->toArray();

			$templateMgr->assign_by_ref('editors', $editors);
			$templateMgr->assign_by_ref('sectionEditors', $sectionEditors);
			$templateMgr->assign_by_ref('layoutEditors', $layoutEditors);
			$templateMgr->assign_by_ref('copyEditors', $copyEditors);
			$templateMgr->assign_by_ref('proofreaders', $proofreaders);
			$templateMgr->display('about/editorialTeam.tpl');
		} else {
			// The Editorial Team feature has been enabled.
			// Generate information using Group data.
			$groupDao =& DAORegistry::getDAO('GroupDAO');
			$groupMembershipDao =& DAORegistry::getDAO('GroupMembershipDAO');

			$allGroups =& $groupDao->getGroups(ASSOC_TYPE_JOURNAL, $journal->getId(), GROUP_CONTEXT_EDITORIAL_TEAM);
			$teamInfo = array();
			$groups = array();
			while ($group =& $allGroups->next()) {
				if (!$group->getAboutDisplayed()) continue;
				$memberships = array();
				$allMemberships =& $groupMembershipDao->getMemberships($group->getId());
				while ($membership =& $allMemberships->next()) {
					if (!$membership->getAboutDisplayed()) continue;
					$memberships[] =& $membership;
					unset($membership);
				}
				if (!empty($memberships)) $groups[] =& $group;
				$teamInfo[$group->getId()] = $memberships;
				unset($group);
			}

			$templateMgr->assign_by_ref('groups', $groups);
			$templateMgr->assign_by_ref('teamInfo', $teamInfo);
			$templateMgr->display('about/editorialTeamBoard.tpl');
		}
	}

	/**
	 * Display group info for a particular group.
	 * @param $args array
	 */
	function displayMembership($args) {
		$this->addCheck(new HandlerValidatorJournal($this));
		$this->validate();
		$this->setupTemplate(true);

		$journal =& Request::getJournal();
		$templateMgr =& TemplateManager::getManager();
		$groupId = (int) array_shift($args);

		$groupDao =& DAORegistry::getDAO('GroupDAO');
		$group =& $groupDao->getById($groupId);

		if (	!$journal || !$group ||
			$group->getContext() != GROUP_CONTEXT_PEOPLE ||
			$group->getAssocType() != ASSOC_TYPE_JOURNAL ||
			$group->getAssocId() != $journal->getId()
		) {
			Request::redirect(null, 'about');
		}

		$groupMembershipDao =& DAORegistry::getDAO('GroupMembershipDAO');
		$allMemberships =& $groupMembershipDao->getMemberships($group->getId());
		$memberships = array();
		while ($membership =& $allMemberships->next()) {
			if (!$membership->getAboutDisplayed()) continue;
			$memberships[] =& $membership;
			unset($membership);
		}

		$countryDao =& DAORegistry::getDAO('CountryDAO');
		$countries =& $countryDao->getCountries();
		$templateMgr->assign_by_ref('countries', $countries);

		$templateMgr->assign_by_ref('group', $group);
		$templateMgr->assign_by_ref('memberships', $memberships);
		$templateMgr->display('about/displayMembership.tpl');
	}

	/**
	 * Display a biography for an editorial team member.
	 * @param $args array
	 */
	function editorialTeamBio($args) {
		$this->addCheck(new HandlerValidatorJournal($this));
		$this->validate();
		$this->setupTemplate(true);

		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$journal =& Request::getJournal();

		$templateMgr =& TemplateManager::getManager();

		$userId = isset($args[0])?(int)$args[0]:0;

		// Make sure we're fetching a biography for
		// a user who should appear on the listing;
		// otherwise we'll be exposing user information
		// that might not necessarily be public.

		// FIXME: This is pretty inefficient. Should be cached.

		$user = null;
		if ($journal->getSetting('boardEnabled') != true) {
			$roles =& $roleDao->getRolesByUserId($userId, $journal->getId());
			$acceptableRoles = array(
				ROLE_ID_EDITOR,
				ROLE_ID_SECTION_EDITOR,
				ROLE_ID_LAYOUT_EDITOR,
				ROLE_ID_COPYEDITOR,
				ROLE_ID_PROOFREADER
			);
			foreach ($roles as $role) {
				$roleId = $role->getRoleId();
				if (in_array($roleId, $acceptableRoles)) {
					$userDao =& DAORegistry::getDAO('UserDAO');
					$user =& $userDao->getUser($userId);
					break;
				}
			}

			// Currently we always publish emails in this mode.
			$publishEmail = true;
		} else {
			$groupDao =& DAORegistry::getDAO('GroupDAO');
			$groupMembershipDao =& DAORegistry::getDAO('GroupMembershipDAO');

			$allGroups =& $groupDao->getGroups(ASSOC_TYPE_JOURNAL, $journal->getId());
			$publishEmail = false;
			while ($group =& $allGroups->next()) {
				if (!$group->getAboutDisplayed()) continue;
				$allMemberships =& $groupMembershipDao->getMemberships($group->getId());
				while ($membership =& $allMemberships->next()) {
					if (!$membership->getAboutDisplayed()) continue;
					$potentialUser =& $membership->getUser();
					if ($potentialUser->getId() == $userId) {
						$user = $potentialUser;
						if ($group->getPublishEmail()) $publishEmail = true;
					}
					unset($membership);
				}
				unset($group);
			}
		}

		if (!$user) Request::redirect(null, 'about', 'editorialTeam');

		$countryDao =& DAORegistry::getDAO('CountryDAO');
		if ($user && $user->getCountry() != '') {
			$country = $countryDao->getCountry($user->getCountry());
			$templateMgr->assign('country', $country);
		}

		$templateMgr->assign_by_ref('user', $user);
		$templateMgr->assign_by_ref('publishEmail', $publishEmail);
		$templateMgr->display('about/editorialTeamBio.tpl');
	}

	/**
	 * Display editorialPolicies page.
	 */
	function editorialPolicies() {
		$this->addCheck(new HandlerValidatorJournal($this));
		$this->validate();
		$this->setupTemplate(true);

		$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$sectionEditorsDao =& DAORegistry::getDAO('SectionEditorsDAO');
		$journal =& Request::getJournal();

		$templateMgr =& TemplateManager::getManager();
		$sections =& $sectionDao->getJournalSections($journal->getId());
		$sections =& $sections->toArray();
		$templateMgr->assign_by_ref('sections', $sections);

		$sectionEditorEntriesBySection = array();
		foreach ($sections as $section) {
			$sectionEditorEntriesBySection[$section->getId()] =& $sectionEditorsDao->getEditorsBySectionId($journal->getId(), $section->getId());
		}
		$templateMgr->assign_by_ref('sectionEditorEntriesBySection', $sectionEditorEntriesBySection);

		$templateMgr->display('about/editorialPolicies.tpl');
	}

	/**
	 * Display subscriptions page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function subscriptions($args, &$request) {
		$this->addCheck(new HandlerValidatorJournal($this));
		$this->validate();
		$this->setupTemplate(true);

		$journalDao =& DAORegistry::getDAO('JournalSettingsDAO');
		$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
		$subscriptionTypeDao =& DAORegistry::getDAO('SubscriptionTypeDAO');

		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		$subscriptionName =& $journalSettingsDao->getSetting($journalId, 'subscriptionName');
		$subscriptionEmail =& $journalSettingsDao->getSetting($journalId, 'subscriptionEmail');
		$subscriptionPhone =& $journalSettingsDao->getSetting($journalId, 'subscriptionPhone');
		$subscriptionFax =& $journalSettingsDao->getSetting($journalId, 'subscriptionFax');
		$subscriptionMailingAddress =& $journalSettingsDao->getSetting($journalId, 'subscriptionMailingAddress');
		$subscriptionAdditionalInformation =& $journal->getLocalizedSetting('subscriptionAdditionalInformation');
		$individualSubscriptionTypes =& $subscriptionTypeDao->getSubscriptionTypesByInstitutional($journalId, false, false);
		$institutionalSubscriptionTypes =& $subscriptionTypeDao->getSubscriptionTypesByInstitutional($journalId, true, false);

		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager = new OJSPaymentManager($request);
		$acceptGiftSubscriptionPayments = $paymentManager->acceptGiftSubscriptionPayments();

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('subscriptionName', $subscriptionName);
		$templateMgr->assign('subscriptionEmail', $subscriptionEmail);
		$templateMgr->assign('subscriptionPhone', $subscriptionPhone);
		$templateMgr->assign('subscriptionFax', $subscriptionFax);
		$templateMgr->assign('subscriptionMailingAddress', $subscriptionMailingAddress);
		$templateMgr->assign('subscriptionAdditionalInformation', $subscriptionAdditionalInformation);
		$templateMgr->assign('acceptGiftSubscriptionPayments', $acceptGiftSubscriptionPayments);
		$templateMgr->assign('individualSubscriptionTypes', $individualSubscriptionTypes);
		$templateMgr->assign('institutionalSubscriptionTypes', $institutionalSubscriptionTypes);
		
		$templateMgr->display('about/subscriptions.tpl');
	}

	/**
	 * Display subscriptions page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function memberships($args, &$request) {
		$this->addCheck(new HandlerValidatorJournal($this));
		$this->validate();
		$this->setupTemplate(true);
		
		$journal =& Request::getJournal();
		$journalId = $journal->getId();

		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager = new OJSPaymentManager($request);

		$membershipEnabled = $paymentManager->membershipEnabled();

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('membershipEnabled', $membershipEnabled);		
		if ( $membershipEnabled ) {
			$membershipFee  = $journal->getSetting('membershipFee');
			$membershipFeeName =& $journal->getLocalizedSetting('membershipFeeName');
			$membershipFeeDescription =& $journal->getLocalizedSetting('membershipFeeDescription');
			$currency = $journal->getSetting('currency');

			$templateMgr->assign('membershipFee', $membershipFee);
			$templateMgr->assign('currency', $currency);
			$templateMgr->assign('membershipFeeName', $membershipFeeName);
			$templateMgr->assign('membershipFeeDescription', $membershipFeeDescription);
			$templateMgr->display('about/memberships.tpl');
			return;
		}		
		$request->redirect(null, 'about');
	}

	/**
	 * Display submissions page.
	 */
	function submissions() {
		$this->addCheck(new HandlerValidatorJournal($this));
		$this->validate();
		$this->setupTemplate(true);

		$journalDao =& DAORegistry::getDAO('JournalSettingsDAO');
		$journal =& Request::getJournal();

		$templateMgr =& TemplateManager::getManager();
		$journalSettings =& $journalDao->getJournalSettings($journal->getId());
		$submissionChecklist = $journal->getLocalizedSetting('submissionChecklist');
		if (!empty($submissionChecklist)) {
			ksort($submissionChecklist);
			reset($submissionChecklist);
		}
		$templateMgr->assign('submissionChecklist', $submissionChecklist);
		$templateMgr->assign_by_ref('journalSettings', $journalSettings);
		$templateMgr->assign('helpTopicId','submission.authorGuidelines');
		$templateMgr->display('about/submissions.tpl');
	}

	/**
	 * Display Journal Sponsorship page.
	 */
	function journalSponsorship() {
		$this->addCheck(new HandlerValidatorJournal($this));
		$this->validate();
		$this->setupTemplate(true);

		$journal =& Request::getJournal();

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('publisherInstitution', $journal->getSetting('publisherInstitution'));
		$templateMgr->assign_by_ref('publisherUrl', $journal->getSetting('publisherUrl'));
		$templateMgr->assign_by_ref('publisherNote', $journal->getLocalizedSetting('publisherNote'));
		$templateMgr->assign_by_ref('contributorNote', $journal->getLocalizedSetting('contributorNote'));
		$templateMgr->assign_by_ref('contributors', $journal->getSetting('contributors'));
		$templateMgr->assign('sponsorNote', $journal->getLocalizedSetting('sponsorNote'));
		$templateMgr->assign_by_ref('sponsors', $journal->getSetting('sponsors'));
		$templateMgr->display('about/journalSponsorship.tpl');
	}

	/**
	 * Display siteMap page.
	 */
	function siteMap() {
		$this->validate();
		$this->setupTemplate(true);

		$templateMgr =& TemplateManager::getManager();

		$journalDao =& DAORegistry::getDAO('JournalDAO');

		$user =& Request::getUser();
		$roleDao =& DAORegistry::getDAO('RoleDAO');

		if ($user) {
			$rolesByJournal = array();
			$journals =& $journalDao->getJournals(true);
			// Fetch the user's roles for each journal
			foreach ($journals->toArray() as $journal) {
				$roles =& $roleDao->getRolesByUserId($user->getId(), $journal->getId());
				if (!empty($roles)) {
					$rolesByJournal[$journal->getId()] =& $roles;
				}
			}
		}

		$journals =& $journalDao->getJournals(true);
		$templateMgr->assign_by_ref('journals', $journals->toArray());
		if (isset($rolesByJournal)) {
			$templateMgr->assign_by_ref('rolesByJournal', $rolesByJournal);
		}
		if ($user) {
			$templateMgr->assign('isSiteAdmin', $roleDao->getRole(0, $user->getId(), ROLE_ID_SITE_ADMIN));
		}

		$templateMgr->display('about/siteMap.tpl');
	}

	/**
	 * Display journal history.
	 */
	function history() {
		$this->addCheck(new HandlerValidatorJournal($this));
		$this->validate();
		$this->setupTemplate(true);

		$journal =& Request::getJournal();

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('history', $journal->getLocalizedSetting('history'));
		$templateMgr->display('about/history.tpl');
	}

	/**
	 * Display aboutThisPublishingSystem page.
	 */
	function aboutThisPublishingSystem() {
		$this->validate();
		$this->setupTemplate(true);

		$versionDao =& DAORegistry::getDAO('VersionDAO');
		$version =& $versionDao->getCurrentVersion();

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('ojsVersion', $version->getVersionString());

		foreach (array(AppLocale::getLocale(), $primaryLocale = AppLocale::getPrimaryLocale(), 'en_US') as $locale) {
			$edProcessFile = "locale/$locale/edprocesslarge.png";
			if (file_exists($edProcessFile)) break;
		}
		$templateMgr->assign('edProcessFile', $edProcessFile);

		$templateMgr->display('about/aboutThisPublishingSystem.tpl');
	}

	/**
	 * Display a list of public stats for the current journal.
	 * WARNING: This implementation should be kept roughly synchronized
	 * with the journal manager's statistics view.
	 */
	function statistics($args, $request) {
		$this->addCheck(new HandlerValidatorJournal($this));
		$this->validate();
		$this->setupTemplate(true);

		$journal =& $request->getJournal();
		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->assign('helpTopicId','user.about');

		// Get the statistics year
		$statisticsYear = (int) $request->getUserVar('statisticsYear');

		// Ensure that the requested statistics year is within a sane range
		$journalStatisticsDao =& DAORegistry::getDAO('JournalStatisticsDAO');
		$lastYear = strftime('%Y');
		$firstDate = $journalStatisticsDao->getFirstActivityDate($journal->getId());
		if (!$firstDate) $firstYear = $lastYear;
		else $firstYear = strftime('%Y', $firstDate);
		if ($statisticsYear < $firstYear || $statisticsYear > $lastYear) {
			// Request out of range; redirect to the current year's statistics
			return $request->redirect(null, null, null, null, array('statisticsYear' => strftime('%Y')));
		}

		$templateMgr->assign('statisticsYear', $statisticsYear);
		$templateMgr->assign('firstYear', $firstYear);
		$templateMgr->assign('lastYear', $lastYear);

		$sectionIds = $journal->getSetting('statisticsSectionIds');
		if (!is_array($sectionIds)) $sectionIds = array();
		$templateMgr->assign('sectionIds', $sectionIds);

		foreach ($this->_getPublicStatisticsNames() as $name) {
			$templateMgr->assign($name, $journal->getSetting($name));
		}
		$fromDate = mktime(0, 0, 0, 1, 1, $statisticsYear);
		$toDate = mktime(23, 59, 59, 12, 31, $statisticsYear);

		$articleStatistics = $journalStatisticsDao->getArticleStatistics($journal->getId(), null, $fromDate, $toDate);
		$templateMgr->assign('articleStatistics', $articleStatistics);

		$limitedArticleStatistics = $journalStatisticsDao->getArticleStatistics($journal->getId(), $sectionIds, $fromDate, $toDate);
		$templateMgr->assign('limitedArticleStatistics', $limitedArticleStatistics);

		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$sections =& $sectionDao->getJournalSections($journal->getId());
		$templateMgr->assign('sections', $sections->toArray());

		$issueStatistics = $journalStatisticsDao->getIssueStatistics($journal->getId(), $fromDate, $toDate);
		$templateMgr->assign('issueStatistics', $issueStatistics);

		$reviewerStatistics = $journalStatisticsDao->getReviewerStatistics($journal->getId(), $sectionIds, $fromDate, $toDate);
		$templateMgr->assign('reviewerStatistics', $reviewerStatistics);

		$allUserStatistics = $journalStatisticsDao->getUserStatistics($journal->getId(), null, $toDate);
		$templateMgr->assign('allUserStatistics', $allUserStatistics);

		$userStatistics = $journalStatisticsDao->getUserStatistics($journal->getId(), $fromDate, $toDate);
		$templateMgr->assign('userStatistics', $userStatistics);

		if ($journal->getSetting('publishingMode') == PUBLISHING_MODE_SUBSCRIPTION) {
			$allSubscriptionStatistics = $journalStatisticsDao->getSubscriptionStatistics($journal->getId(), null, $toDate);
			$templateMgr->assign('allSubscriptionStatistics', $allSubscriptionStatistics);

			$subscriptionStatistics = $journalStatisticsDao->getSubscriptionStatistics($journal->getId(), $fromDate, $toDate);
			$templateMgr->assign('subscriptionStatistics', $subscriptionStatistics);
		}

		$templateMgr->display('about/statistics.tpl');
	}

	/**
	 * @see StatisticsHandler::_getPublicStatisticsNames()
	 */
	function _getPublicStatisticsNames() {
		import ('pages.manager.ManagerHandler');
		import ('pages.manager.StatisticsHandler');
		return StatisticsHandler::_getPublicStatisticsNames();
	}
}

?>
