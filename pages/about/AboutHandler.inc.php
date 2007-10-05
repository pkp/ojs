<?php

/**
 * @file AboutHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.editor
 * @class AboutHandler
 *
 * Handle requests for editor functions. 
 *
 * $Id$
 */

class AboutHandler extends Handler {

	/**
	 * Display about index page.
	 */
	function index() {
		parent::validate();

		$templateMgr = &TemplateManager::getManager();
		$journalDao = &DAORegistry::getDAO('JournalDAO');
		$journalPath = Request::getRequestedJournalPath();

		if ($journalPath != 'index' && $journalDao->journalExistsByPath($journalPath)) {
			$journal = &Request::getJournal();

			$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
			$templateMgr->assign_by_ref('journalSettings', $journalSettingsDao->getJournalSettings($journal->getJournalId()));

			$customAboutItems = &$journalSettingsDao->getSetting($journal->getJournalId(), 'customAboutItems');
			if (isset($customAboutItems[Locale::getLocale()])) $templateMgr->assign('customAboutItems', $customAboutItems[Locale::getLocale()]);
			elseif (isset($customAboutItems[Locale::getPrimaryLocale()])) $templateMgr->assign('customAboutItems', $customAboutItems[Locale::getPrimaryLocale()]);

			foreach (AboutHandler::getPublicStatisticsNames() as $name) {
				if ($journal->getSetting($name)) {
					$templateMgr->assign('publicStatisticsEnabled', true);
					break;
				} 
			}

			$groupDao =& DAORegistry::getDAO('GroupDAO');
			$groups =& $groupDao->getGroups($journal->getJournalId(), GROUP_CONTEXT_PEOPLE);

			$templateMgr->assign_by_ref('peopleGroups', $groups);
			$templateMgr->assign('helpTopicId', 'user.about');
			$templateMgr->display('about/index.tpl');
		} else {
			$site = &Request::getSite();
			$about = $site->getSiteAbout();
			$templateMgr->assign('about', $about);

			$journals = &$journalDao->getEnabledJournals(); //Enabled Added
			$templateMgr->assign_by_ref('journals', $journals);
			$templateMgr->display('about/site.tpl');
		}
	}


	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false) {
		parent::validate();
		$templateMgr = &TemplateManager::getManager();
		$journal =& Request::getJournal();

		if (!$journal || !$journal->getSetting('restrictSiteAccess')) {
			$templateMgr->setCacheability(CACHEABILITY_PUBLIC);
		}
		$templateMgr->assign('pageHierarchy', array(array(Request::url(null, 'about'), 'about.aboutTheJournal')));
	}

	/**
	 * Display contact page.
	 */
	function contact() {
		parent::validate(true);

		AboutHandler::setupTemplate(true);

		$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
		$journal = &Request::getJournal();

		$templateMgr = &TemplateManager::getManager();
		$journalSettings = &$journalSettingsDao->getJournalSettings($journal->getJournalId());
		$templateMgr->assign_by_ref('journalSettings', $journalSettings);
		$templateMgr->display('about/contact.tpl');
	}

	/**
	 * Display editorialTeam page.
	 */
	function editorialTeam() {
		parent::validate(true);
		AboutHandler::setupTemplate(true);
		$journal = &Request::getJournal();
		$templateMgr = &TemplateManager::getManager();

		$countryDao =& DAORegistry::getDAO('CountryDAO');
		$countries =& $countryDao->getCountries();
		$templateMgr->assign_by_ref('countries', $countries);

		// FIXME: This is pretty inefficient; should probably be cached.

		if ($journal->getSetting('boardEnabled') != true) {
			// Don't use the Editorial Team feature. Generate
			// Editorial Team information using Role info.
			$roleDao = &DAORegistry::getDAO('RoleDAO');

			$editors = &$roleDao->getUsersByRoleId(ROLE_ID_EDITOR, $journal->getJournalId());
			$editors = &$editors->toArray();

			$sectionEditors = &$roleDao->getUsersByRoleId(ROLE_ID_SECTION_EDITOR, $journal->getJournalId());
			$sectionEditors = &$sectionEditors->toArray();

			$layoutEditors = &$roleDao->getUsersByRoleId(ROLE_ID_LAYOUT_EDITOR, $journal->getJournalId());
			$layoutEditors = &$layoutEditors->toArray();

			$copyEditors = &$roleDao->getUsersByRoleId(ROLE_ID_COPYEDITOR, $journal->getJournalId());
			$copyEditors = &$copyEditors->toArray();

			$proofreaders = &$roleDao->getUsersByRoleId(ROLE_ID_PROOFREADER, $journal->getJournalId());
			$proofreaders = &$proofreaders->toArray();

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

			$allGroups =& $groupDao->getGroups($journal->getJournalId(), GROUP_CONTEXT_EDITORIAL_TEAM);
			$teamInfo = array();
			$groups = array();
			while ($group =& $allGroups->next()) {
				if (!$group->getAboutDisplayed()) continue;
				$memberships = array();
				$allMemberships =& $groupMembershipDao->getMemberships($group->getGroupId());
				while ($membership =& $allMemberships->next()) {
					if (!$membership->getAboutDisplayed()) continue;
					$memberships[] =& $membership;
					unset($membership);
				}
				if (!empty($memberships)) $groups[] =& $group;
				$teamInfo[$group->getGroupId()] = $memberships;
				unset($group);
			}

			$templateMgr->assign_by_ref('groups', $groups);
			$templateMgr->assign_by_ref('teamInfo', $teamInfo);
			$templateMgr->display('about/editorialTeamBoard.tpl');
		}
	}

	/**
	 * Display group info for a particular group.
	 */
	function displayMembership($args) {
		parent::validate(true);
		AboutHandler::setupTemplate(true);
		$journal = &Request::getJournal();
		$templateMgr = &TemplateManager::getManager();
		$groupId = (int) array_shift($args);

		$groupDao =& DAORegistry::getDAO('GroupDAO');
		$group =& $groupDao->getGroup($groupId);

		if (	!$journal || !$group ||
			$group->getContext() != GROUP_CONTEXT_PEOPLE ||
			$group->getJournalId() != $journal->getJournalId()
		) {
			Request::redirect(null, 'about');
		}

		$groupMembershipDao =& DAORegistry::getDAO('GroupMembershipDAO');
		$allMemberships =& $groupMembershipDao->getMemberships($group->getGroupId());
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
	 */
	function editorialTeamBio($args) {
		parent::validate(true);

		AboutHandler::setupTemplate(true);

		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$journal = &Request::getJournal();

		$templateMgr = &TemplateManager::getManager();

		$userId = isset($args[0])?(int)$args[0]:0;

		// Make sure we're fetching a biography for
		// a user who should appear on the listing;
		// otherwise we'll be exposing user information
		// that might not necessarily be public.

		// FIXME: This is pretty inefficient. Should be cached.

		$user = null;
		if ($journal->getSetting('boardEnabled') != true) {
			$editors = &$roleDao->getUsersByRoleId(ROLE_ID_EDITOR, $journal->getJournalId());
			while ($potentialUser =& $editors->next()) {
				if ($potentialUser->getUserId() == $userId)
					$user =& $potentialUser;
				unset($potentialUser);
			}

			$sectionEditors = &$roleDao->getUsersByRoleId(ROLE_ID_SECTION_EDITOR, $journal->getJournalId());
			while ($potentialUser =& $sectionEditors->next()) {
				if ($potentialUser->getUserId() == $userId)
					$user =& $potentialUser;
				unset($potentialUser);
			}

			$layoutEditors = &$roleDao->getUsersByRoleId(ROLE_ID_LAYOUT_EDITOR, $journal->getJournalId());
			while ($potentialUser =& $layoutEditors->next()) {
				if ($potentialUser->getUserId() == $userId)
					$user = $potentialUser;
				unset($potentialUser);
			}

			$copyEditors = &$roleDao->getUsersByRoleId(ROLE_ID_COPYEDITOR, $journal->getJournalId());
			while ($potentialUser =& $copyEditors->next()) {
				if ($potentialUser->getUserId() == $userId)
					$user = $potentialUser;
				unset($potentialUser);
			}

			$proofreaders = &$roleDao->getUsersByRoleId(ROLE_ID_PROOFREADER, $journal->getJournalId());
			while ($potentialUser =& $proofreaders->next()) {
				if ($potentialUser->getUserId() == $userId)
					$user = $potentialUser;
				unset($potentialUser);
			}

		} else {
			$groupDao =& DAORegistry::getDAO('GroupDAO');
			$groupMembershipDao =& DAORegistry::getDAO('GroupMembershipDAO');

			$allGroups =& $groupDao->getGroups($journal->getJournalId());
			while ($group =& $allGroups->next()) {
				if (!$group->getAboutDisplayed()) continue;
				$allMemberships =& $groupMembershipDao->getMemberships($group->getGroupId());
				while ($membership =& $allMemberships->next()) {
					if (!$membership->getAboutDisplayed()) continue;
					$potentialUser =& $membership->getUser();
					if ($potentialUser->getUserId() == $userId)
						$user = $potentialUser;
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
		$templateMgr->display('about/editorialTeamBio.tpl');
	}

	/**
	 * Display editorialPolicies page.
	 */
	function editorialPolicies() {
		parent::validate(true);

		AboutHandler::setupTemplate(true);

		$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		$sectionEditorsDao = &DAORegistry::getDAO('SectionEditorsDAO');
		$journal = &Request::getJournal();

		$templateMgr = &TemplateManager::getManager();
		$journalSettings = &$journalSettingsDao->getJournalSettings($journal->getJournalId());
		$templateMgr->assign_by_ref('journalSettings', $journalSettings);
		$sections = &$sectionDao->getJournalSections($journal->getJournalId());
		$sections = &$sections->toArray();
		$templateMgr->assign_by_ref('sections', $sections);

		$sectionEditorEntriesBySection = array();
		foreach ($sections as $section) {
			$sectionEditorEntriesBySection[$section->getSectionId()] = &$sectionEditorsDao->getEditorsBySectionId($journal->getJournalId(), $section->getSectionId());
		}
		$templateMgr->assign_by_ref('sectionEditorEntriesBySection', $sectionEditorEntriesBySection);

		$templateMgr->display('about/editorialPolicies.tpl');
	}

	/**
	 * Display subscriptions page.
	 */
	function subscriptions() {
		parent::validate(true);

		AboutHandler::setupTemplate(true);

		$journalDao = &DAORegistry::getDAO('JournalSettingsDAO');
		$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
		$subscriptionTypeDao = &DAORegistry::getDAO('SubscriptionTypeDAO');

		$journal = &Request::getJournal();
		$journalId = $journal->getJournalId();

		$subscriptionName = &$journalSettingsDao->getSetting($journalId, 'subscriptionName');
		$subscriptionEmail = &$journalSettingsDao->getSetting($journalId, 'subscriptionEmail');
		$subscriptionPhone = &$journalSettingsDao->getSetting($journalId, 'subscriptionPhone');
		$subscriptionFax = &$journalSettingsDao->getSetting($journalId, 'subscriptionFax');
		$subscriptionMailingAddress = &$journalSettingsDao->getSetting($journalId, 'subscriptionMailingAddress');
		$subscriptionAdditionalInformation = &$journal->getLocalizedSetting('subscriptionAdditionalInformation');
		$subscriptionTypes = &$subscriptionTypeDao->getSubscriptionTypesByJournalId($journalId);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('subscriptionName', $subscriptionName);
		$templateMgr->assign('subscriptionEmail', $subscriptionEmail);
		$templateMgr->assign('subscriptionPhone', $subscriptionPhone);
		$templateMgr->assign('subscriptionFax', $subscriptionFax);
		$templateMgr->assign('subscriptionMailingAddress', $subscriptionMailingAddress);
		$templateMgr->assign('subscriptionAdditionalInformation', $subscriptionAdditionalInformation);
		$templateMgr->assign('subscriptionTypes', $subscriptionTypes);
		$templateMgr->display('about/subscriptions.tpl');
	}

	/**
	 * Display submissions page.
	 */
	function submissions() {
		parent::validate(true);

		AboutHandler::setupTemplate(true);

		$journalDao = &DAORegistry::getDAO('JournalSettingsDAO');
		$journal = &Request::getJournal();

		$templateMgr = &TemplateManager::getManager();
		$journalSettings = &$journalDao->getJournalSettings($journal->getJournalId());
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
		parent::validate();

		AboutHandler::setupTemplate(true);

		$journal = &Request::getJournal();

		$templateMgr = &TemplateManager::getManager();
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
		parent::validate();

		AboutHandler::setupTemplate(true);
		$templateMgr = &TemplateManager::getManager();

		$journalDao = &DAORegistry::getDAO('JournalDAO');

		$user = &Request::getUser();
		$roleDao = &DAORegistry::getDAO('RoleDAO');

		if ($user) {
			$rolesByJournal = array();
			$journals = &$journalDao->getJournals();
			// Fetch the user's roles for each journal
			foreach ($journals->toArray() as $journal) {
				$roles = &$roleDao->getRolesByUserId($user->getUserId(), $journal->getJournalId());
				if (!empty($roles)) {
					$rolesByJournal[$journal->getJournalId()] = &$roles;
				}
			}
		}

		$journals = &$journalDao->getJournals();
		$templateMgr->assign_by_ref('journals', $journals->toArray());
		if (isset($rolesByJournal)) {
			$templateMgr->assign_by_ref('rolesByJournal', $rolesByJournal);
		}
		if ($user) {
			$templateMgr->assign('isSiteAdmin', $roleDao->getRole(0, $user->getUserId(), ROLE_ID_SITE_ADMIN));
		}

		$templateMgr->display('about/siteMap.tpl');
	}

	/**
	 * Display aboutThisPublishingSystem page.
	 */
	function aboutThisPublishingSystem() {
		parent::validate();

		AboutHandler::setupTemplate(true);

		$versionDao =& DAORegistry::getDAO('VersionDAO');
		$version =& $versionDao->getCurrentVersion();

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('ojsVersion', $version->getVersionString());
		$templateMgr->display('about/aboutThisPublishingSystem.tpl');
	}

	/**
	 * Display a list of public stats for the current journal.
	 * WARNING: This implementation should be kept roughly synchronized
	 * with the reader's statistics view in the About pages.
	 */
	function statistics() {
		parent::validate();
		AboutHandler::setupTemplate(true);

		$journal = &Request::getJournal();
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('helpTopicId','user.about');

		$statisticsYear = Request::getUserVar('statisticsYear');
		if (empty($statisticsYear)) $statisticsYear = date('Y');
		$templateMgr->assign('statisticsYear', $statisticsYear);

		$sectionIds = $journal->getSetting('statisticsSectionIds');
		if (!is_array($sectionIds)) $sectionIds = array();
		$templateMgr->assign('sectionIds', $sectionIds);

		foreach (AboutHandler::getPublicStatisticsNames() as $name) {
			$templateMgr->assign($name, $journal->getSetting($name));
		}
		$fromDate = mktime(0, 0, 0, 1, 1, $statisticsYear);
		$toDate = mktime(23, 59, 59, 12, 31, $statisticsYear);

		$journalStatisticsDao =& DAORegistry::getDAO('JournalStatisticsDAO');
		$articleStatistics = $journalStatisticsDao->getArticleStatistics($journal->getJournalId(), null, $fromDate, $toDate);
		$templateMgr->assign('articleStatistics', $articleStatistics);

		$limitedArticleStatistics = $journalStatisticsDao->getArticleStatistics($journal->getJournalId(), $sectionIds, $fromDate, $toDate);
		$templateMgr->assign('limitedArticleStatistics', $limitedArticleStatistics);

		$limitedArticleStatistics = $journalStatisticsDao->getArticleStatistics($journal->getJournalId(), $sectionIds, $fromDate, $toDate);
		$templateMgr->assign('articleStatistics', $articleStatistics);

		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$sections =& $sectionDao->getJournalSections($journal->getJournalId());
		$templateMgr->assign('sections', $sections->toArray());

		$issueStatistics = $journalStatisticsDao->getIssueStatistics($journal->getJournalId(), $fromDate, $toDate);
		$templateMgr->assign('issueStatistics', $issueStatistics);

		$reviewerStatistics = $journalStatisticsDao->getReviewerStatistics($journal->getJournalId(), $sectionIds, $fromDate, $toDate);
		$templateMgr->assign('reviewerStatistics', $reviewerStatistics);

		$allUserStatistics = $journalStatisticsDao->getUserStatistics($journal->getJournalId(), null, $toDate);
		$templateMgr->assign('allUserStatistics', $allUserStatistics);

		$userStatistics = $journalStatisticsDao->getUserStatistics($journal->getJournalId(), $fromDate, $toDate);
		$templateMgr->assign('userStatistics', $userStatistics);

		$enableSubscriptions = $journal->getSetting('enableSubscriptions');
		if ($enableSubscriptions) {
			$templateMgr->assign('enableSubscriptions', true);
			$allSubscriptionStatistics = $journalStatisticsDao->getSubscriptionStatistics($journal->getJournalId(), null, $toDate);
			$templateMgr->assign('allSubscriptionStatistics', $allSubscriptionStatistics);

			$subscriptionStatistics = $journalStatisticsDao->getSubscriptionStatistics($journal->getJournalId(), $fromDate, $toDate);
			$templateMgr->assign('subscriptionStatistics', $subscriptionStatistics);
		}

		$notificationStatusDao =& DAORegistry::getDAO('NotificationStatusDAO');
		$notifiableUsers = $notificationStatusDao->getNotifiableUsersCount($journal->getJournalId());
		$templateMgr->assign('notifiableUsers', $notifiableUsers);

		$templateMgr->display('about/statistics.tpl');
	}

	function getPublicStatisticsNames() {
		import ('pages.manager.ManagerHandler');
		import ('pages.manager.StatisticsHandler');
		return StatisticsHandler::getPublicStatisticsNames();
	}

}

?>
