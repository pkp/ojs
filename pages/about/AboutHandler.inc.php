<?php

/**
 * AboutHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.editor
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
			$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
			$journal = &Request::getJournal();
			
			$customAboutItems = &$journalSettingsDao->getSetting($journal->getJournalId(), 'customAboutItems');
			$enableSubscriptions = &$journalSettingsDao->getSetting($journal->getJournalId(), 'enableSubscriptions');

			$templateMgr->assign('customAboutItems', $customAboutItems);
			$templateMgr->assign('enableSubscriptions', $enableSubscriptions);
			$templateMgr->assign('helpTopicId', 'user.about');
			$templateMgr->assign('journalSettings', $journalSettingsDao->getJournalSettings($journal->getJournalId()));
			$templateMgr->display('about/index.tpl');
		} else {
			$site = &Request::getSite();
			$about = $site->getAbout();
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
		$templateMgr->assign('pageHierarchy', array(array('about', 'about.aboutTheJournal')));
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
		$templateMgr->assign('journalSettings', $journalSettings);
		$templateMgr->display('about/contact.tpl');
	}
	
	/**
	 * Display editorialTeam page.
	 */
	function editorialTeam() {
		parent::validate(true);
		
		AboutHandler::setupTemplate(true);
		
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$journal = &Request::getJournal();

		$templateMgr = &TemplateManager::getManager();

		if ($journal->getSetting('boardEnabled') !== true) {
			// Don't use the Editorial Team feature. Generate
			// Editorial Team information using Role info.

			$editors = &$roleDao->getUsersByRoleId(ROLE_ID_EDITOR, $journal->getJournalId());
			$editors = &$editors->toArray();

			$sectionEditors = &$roleDao->getUsersByRoleId(ROLE_ID_SECTION_EDITOR, $journal->getJournalId());
			$sectionEditors = &$sectionEditors->toArray();

			$layoutEditors = &$roleDao->getUsersByRoleId(ROLE_ID_LAYOUT_EDITOR, $journal->getJournalId());
			$layoutEditors = &$layoutEditors->toArray();
		
			$templateMgr->assign('editors', $editors);
			$templateMgr->assign('sectionEditors', $sectionEditors);
			$templateMgr->assign('layoutEditors', $layoutEditors);
			$templateMgr->display('about/editorialTeam.tpl');
		} else {
			// The Editorial Team feature has been enabled.
			// Generate information using Group data.
			$groupDao =& DAORegistry::getDAO('GroupDAO');
			$groupMembershipDao =& DAORegistry::getDAO('GroupMembershipDAO');

			$allGroups =& $groupDao->getGroups($journal->getJournalId());
			$teamInfo = array();
			$groups = array();
			while ($group =& $allGroups->next()) {
				if (!$group->getAboutDisplayed()) continue;
				$memberships = array();
				$allMemberships =& $groupMembershipDao->getMemberships($group->getGroupId());
				while ($membership =& $allMemberships->next()) {
					if (!$membership->getAboutDisplayed()) continue;
					$memberships[] =& $membership;
				}
				if (!empty($memberships)) $groups[] =& $group;
				$teamInfo[$group->getGroupId()] = $memberships;
			}

			$templateMgr->assign_by_ref('groups', $groups);
			$templateMgr->assign_by_ref('teamInfo', $teamInfo);
			$templateMgr->display('about/editorialTeamBoard.tpl');
		}
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
		$templateMgr->assign('journalSettings', $journalSettings);
		$sections = &$sectionDao->getJournalSections($journal->getJournalId());
		$sections = &$sections->toArray();
		$templateMgr->assign('sections', $sections);
		
		$sectionEditors = array();
		foreach ($sections as $section) {
			$sectionEditors[$section->getSectionId()] = &$sectionEditorsDao->getEditorsBySectionId($journal->getJournalId(), $section->getSectionId());
		}
		$templateMgr->assign('sectionEditors', $sectionEditors);

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
		$subscriptionAdditionalInformation = &$journalSettingsDao->getSetting($journalId, 'subscriptionAdditionalInformation');
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
		if (isset($journalSettings['submissionChecklist']) && count($journalSettings['submissionChecklist']) > 0) {
			ksort($journalSettings['submissionChecklist']);
			reset($journalSettings['submissionChecklist']);
		}
		$templateMgr->assign('journalSettings', $journalSettings);
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
		$templateMgr->assign('publisher', $journal->getSetting('publisher'));
		$templateMgr->assign('contributorNote', $journal->getSetting('contributorNote'));
		$templateMgr->assign('contributors', $journal->getSetting('contributors'));
		$templateMgr->assign('sponsorNote', $journal->getSetting('sponsorNote'));
		$templateMgr->assign('sponsors', $journal->getSetting('sponsors'));
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
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->display('about/aboutThisPublishingSystem.tpl');
	}
	

}

?>
