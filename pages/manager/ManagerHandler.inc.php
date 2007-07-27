<?php

/**
 * @file ManagerHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.manager
 * @class ManagerHandler
 *
 * Handle requests for journal management functions. 
 *
 * $Id$
 */

class ManagerHandler extends Handler {

	/**
	 * Display journal management index page.
	 */
	function index() {
		ManagerHandler::validate();
		ManagerHandler::setupTemplate();

		$journal = &Request::getJournal();
		$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
		$subscriptionsEnabled = $journalSettingsDao->getSetting($journal->getJournalId(), 'enableSubscriptions'); 
		$announcementsEnabled = $journalSettingsDao->getSetting($journal->getJournalId(), 'enableAnnouncements'); 

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('subscriptionsEnabled', $subscriptionsEnabled);
		$templateMgr->assign('announcementsEnabled', $announcementsEnabled);
		$templateMgr->assign('helpTopicId','journal.index');
		$templateMgr->display('manager/index.tpl');
	}
	
	/**
	 * Send an email to a user or group of users.
	 */
	function email($args) {
		parent::validate();

		ManagerHandler::setupTemplate(true);
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'journal.users.emailUsers');

		$userDao = &DAORegistry::getDAO('UserDAO');

		$site = &Request::getSite();
		$journal = &Request::getJournal();
		$user = &Request::getUser();

		import('mail.MailTemplate');
		$email = &new MailTemplate(Request::getUserVar('template'), Request::getUserVar('locale'));
		
		if (Request::getUserVar('send') && !$email->hasErrors()) {
			$email->send();
			Request::redirect(null, Request::getRequestedPage());
		} else {
			$email->assignParams(); // FIXME Forces default parameters to be assigned (should do this automatically in MailTemplate?)
			if (!Request::getUserVar('continued')) {
				if (($groupId = Request::getUserVar('toGroup')) != '') {
					// Special case for emailing entire groups:
					// Check for a group ID and add recipients.
					$groupDao =& DAORegistry::getDAO('GroupDAO');
					$group =& $groupDao->getGroup($groupId);
					if ($group && $group->getJournalId() == $journal->getJournalId()) {
						$groupMembershipDao =& DAORegistry::getDAO('GroupMembershipDAO');
						$memberships =& $groupMembershipDao->getMemberships($group->getGroupId());
						$memberships =& $memberships->toArray();
						foreach ($memberships as $membership) {
							$user =& $membership->getUser();
							$email->addRecipient($user->getEmail(), $user->getFullName());
						}
					}
				}
				if (count($email->getRecipients())==0) $email->addRecipient($user->getEmail(), $user->getFullName());
			}
			$email->displayEditForm(Request::url(null, null, 'email'), array(), 'manager/people/email.tpl');
		}
	}

	/**
	 * Validate that user has permissions to manage the selected journal.
	 * Redirects to user index page if not properly authenticated.
	 */
	function validate() {
		parent::validate();
		$journal =& Request::getJournal();
		if (!$journal || (!Validation::isJournalManager() && !Validation::isSiteAdmin())) {
			Validation::redirectLogin();
		}
	}
	
	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false) {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy',
			$subclass ? array(array(Request::url(null, 'user'), 'navigation.user'), array(Request::url(null, 'manager'), 'manager.journalManagement'))
				: array(array(Request::url(null, 'user'), 'navigation.user'))
		);
	}
	
	
	//
	// Setup
	//

	function setup($args) {
		import('pages.manager.SetupHandler');
		SetupHandler::setup($args);
	}

	function saveSetup($args) {
		import('pages.manager.SetupHandler');
		SetupHandler::saveSetup($args);
	}

	function setupSaved($args) {
		import('pages.manager.SetupHandler');
		SetupHandler::setupSaved($args);
	}

	function downloadLayoutTemplate($args) {
		import('pages.manager.SetupHandler');
		SetupHandler::downloadLayoutTemplate($args);
	}
	
	//
	// People Management
	//

	function people($args) {
		import('pages.manager.PeopleHandler');
		PeopleHandler::people($args);
	}
	
	function enrollSearch($args) {
		import('pages.manager.PeopleHandler');
		PeopleHandler::enrollSearch($args);
	}
	
	function enroll($args) {
		import('pages.manager.PeopleHandler');
		PeopleHandler::enroll($args);
	}
	
	function unEnroll($args) {
		import('pages.manager.PeopleHandler');
		PeopleHandler::unEnroll($args);
	}
	
	function enrollSyncSelect($args) {
		import('pages.manager.PeopleHandler');
		PeopleHandler::enrollSyncSelect($args);
	}
	
	function enrollSync($args) {
		import('pages.manager.PeopleHandler');
		PeopleHandler::enrollSync($args);
	}
	
	function createUser() {
		import('pages.manager.PeopleHandler');
		PeopleHandler::createUser();
	}

	function suggestUsername() {
		import('pages.manager.PeopleHandler');
		PeopleHandler::suggestUsername();
	}

	function mergeUsers($args) {
		import('pages.manager.PeopleHandler');
		PeopleHandler::mergeUsers($args);
	}
	
	function disableUser($args) {
		import('pages.manager.PeopleHandler');
		PeopleHandler::disableUser($args);
	}
	
	function enableUser($args) {
		import('pages.manager.PeopleHandler');
		PeopleHandler::enableUser($args);
	}
	
	function removeUser($args) {
		import('pages.manager.PeopleHandler');
		PeopleHandler::removeUser($args);
	}
	
	function editUser($args) {
		import('pages.manager.PeopleHandler');
		PeopleHandler::editUser($args);
	}
	
	function updateUser() {
		import('pages.manager.PeopleHandler');
		PeopleHandler::updateUser();
	}
	
	function userProfile($args) {
		import('pages.manager.PeopleHandler');
		PeopleHandler::userProfile($args);
	}
	
	function signInAsUser($args) {
		import('pages.manager.PeopleHandler');
		PeopleHandler::signInAsUser($args);
	}
	
	function signOutAsUser() {
		import('pages.manager.PeopleHandler');
		PeopleHandler::signOutAsUser();
	}
	
	
	//
	// Section Management
	//
	
	function sections() {
		import('pages.manager.SectionHandler');
		SectionHandler::sections();
	}
	
	function createSection() {
		import('pages.manager.SectionHandler');
		SectionHandler::createSection();
	}
	
	function editSection($args) {
		import('pages.manager.SectionHandler');
		SectionHandler::editSection($args);
	}
	
	function updateSection($args) {
		import('pages.manager.SectionHandler');
		SectionHandler::updateSection($args);
	}
	
	function deleteSection($args) {
		import('pages.manager.SectionHandler');
		SectionHandler::deleteSection($args);
	}
	
	function moveSection() {
		import('pages.manager.SectionHandler');
		SectionHandler::moveSection();
	}
	
	
	//
	// E-mail Management
	//
	
	function emails($args) {
		import('pages.manager.EmailHandler');
		EmailHandler::emails($args);
	}
	
	function createEmail($args) {
		import('pages.manager.EmailHandler');
		EmailHandler::createEmail($args);
	}
	
	function editEmail($args) {
		import('pages.manager.EmailHandler');
		EmailHandler::editEmail($args);
	}
	
	function updateEmail() {
		import('pages.manager.EmailHandler');
		EmailHandler::updateEmail();
	}
	
	function deleteCustomEmail($args) {
		import('pages.manager.EmailHandler');
		EmailHandler::deleteCustomEmail($args);
	}
	
	function resetEmail($args) {
		import('pages.manager.EmailHandler');
		EmailHandler::resetEmail($args);
	}
	
	function disableEmail($args) {
		import('pages.manager.EmailHandler');
		EmailHandler::disableEmail($args);
	}
	
	function enableEmail($args) {
		import('pages.manager.EmailHandler');
		EmailHandler::enableEmail($args);
	}
	
	function resetAllEmails() {
		import('pages.manager.EmailHandler');
		EmailHandler::resetAllEmails();
	}
	
	
	//
	// Languages
	//
	
	function languages() {
		import('pages.manager.JournalLanguagesHandler');
		JournalLanguagesHandler::languages();
	}
	
	function saveLanguageSettings() {
		import('pages.manager.JournalLanguagesHandler');
		JournalLanguagesHandler::saveLanguageSettings();
	}
	
	
	//
	// Files Browser
	//
	
	function files($args) {
		import('pages.manager.FilesHandler');
		FilesHandler::files($args);
	}
	
	function fileUpload($args) {
		import('pages.manager.FilesHandler');
		FilesHandler::fileUpload($args);
	}
	
	function fileMakeDir($args) {
		import('pages.manager.FilesHandler');
		FilesHandler::fileMakeDir($args);
	}
	
	function fileDelete($args) {
		import('pages.manager.FilesHandler');
		FilesHandler::fileDelete($args);
	}


	//
	// Subscription Policies 
	//

	function subscriptionPolicies() {
		import('pages.manager.SubscriptionHandler');
		SubscriptionHandler::subscriptionPolicies();
	}

	function saveSubscriptionPolicies($args) {
		import('pages.manager.SubscriptionHandler');
		SubscriptionHandler::saveSubscriptionPolicies($args);
	}


	//
	// Subscription Types
	//

	function subscriptionTypes() {
		import('pages.manager.SubscriptionHandler');
		SubscriptionHandler::subscriptionTypes();
	}

	function deleteSubscriptionType($args) {
		import('pages.manager.SubscriptionHandler');
		SubscriptionHandler::deleteSubscriptionType($args);
	}

	function createSubscriptionType() {
		import('pages.manager.SubscriptionHandler');
		SubscriptionHandler::createSubscriptionType();
	}

	function selectSubscriber($args) {
		import('pages.manager.SubscriptionHandler');
		SubscriptionHandler::selectSubscriber($args);
	}

	function editSubscriptionType($args) {
		import('pages.manager.SubscriptionHandler');
		SubscriptionHandler::editSubscriptionType($args);
	}

	function updateSubscriptionType($args) {
		import('pages.manager.SubscriptionHandler');
		SubscriptionHandler::updateSubscriptionType($args);
	}

	function moveSubscriptionType($args) {
		import('pages.manager.SubscriptionHandler');
		SubscriptionHandler::moveSubscriptionType($args);
	}


	//
	// Subscriptions
	//

	function subscriptions() {
		import('pages.manager.SubscriptionHandler');
		SubscriptionHandler::subscriptions();
	}

	function deleteSubscription($args) {
		import('pages.manager.SubscriptionHandler');
		SubscriptionHandler::deleteSubscription($args);
	}

	function createSubscription() {
		import('pages.manager.SubscriptionHandler');
		SubscriptionHandler::createSubscription();
	}

	function editSubscription($args) {
		import('pages.manager.SubscriptionHandler');
		SubscriptionHandler::editSubscription($args);
	}

	function updateSubscription($args) {
		import('pages.manager.SubscriptionHandler');
		SubscriptionHandler::updateSubscription($args);
	}


	//
	// Announcement Types 
	//

	function announcementTypes() {
		import('pages.manager.AnnouncementHandler');
		AnnouncementHandler::announcementTypes();
	}

	function deleteAnnouncementType($args) {
		import('pages.manager.AnnouncementHandler');
		AnnouncementHandler::deleteAnnouncementType($args);
	}

	function createAnnouncementType() {
		import('pages.manager.AnnouncementHandler');
		AnnouncementHandler::createAnnouncementType();
	}

	function editAnnouncementType($args) {
		import('pages.manager.AnnouncementHandler');
		AnnouncementHandler::editAnnouncementType($args);
	}

	function updateAnnouncementType($args) {
		import('pages.manager.AnnouncementHandler');
		AnnouncementHandler::updateAnnouncementType($args);
	}


	//
	// Announcements 
	//

	function announcements() {
		import('pages.manager.AnnouncementHandler');
		AnnouncementHandler::announcements();
	}

	function deleteAnnouncement($args) {
		import('pages.manager.AnnouncementHandler');
		AnnouncementHandler::deleteAnnouncement($args);
	}

	function createAnnouncement() {
		import('pages.manager.AnnouncementHandler');
		AnnouncementHandler::createAnnouncement();
	}

	function editAnnouncement($args) {
		import('pages.manager.AnnouncementHandler');
		AnnouncementHandler::editAnnouncement($args);
	}

	function updateAnnouncement($args) {
		import('pages.manager.AnnouncementHandler');
		AnnouncementHandler::updateAnnouncement($args);
	}

	//
	// Import/Export
	//

	function importexport($args) {
		import('pages.manager.ImportExportHandler');
		ImportExportHandler::importExport($args);
	}

	//
	// Plugin Management
	//

	function plugins($args) {
		import('pages.manager.PluginHandler');
		PluginHandler::plugins($args);
	}

	function plugin($args) {
		import('pages.manager.PluginHandler');
		PluginHandler::plugin($args);
	}

	//
	// Group Management
	//

	function groups($args) {
		import('pages.manager.GroupHandler');
		GroupHandler::groups($args);
	}

	function createGroup($args) {
		import('pages.manager.GroupHandler');
		GroupHandler::createGroup($args);
	}

	function updateGroup($args) {
		import('pages.manager.GroupHandler');
		GroupHandler::updateGroup($args);
	}

	function deleteGroup($args) {
		import('pages.manager.GroupHandler');
		GroupHandler::deleteGroup($args);
	}

	function editGroup($args) {
		import('pages.manager.GroupHandler');
		GroupHandler::editGroup($args);
	}

	function groupMembership($args) {
		import('pages.manager.GroupHandler');
		GroupHandler::groupMembership($args);
	}

	function addMembership($args) {
		import('pages.manager.GroupHandler');
		GroupHandler::addMembership($args);
	}

	function deleteMembership($args) {
		import('pages.manager.GroupHandler');
		GroupHandler::deleteMembership($args);
	}

	function setBoardEnabled($args) {
		import('pages.manager.GroupHandler');
		GroupHandler::setBoardEnabled($args);
	}

	function moveGroup($args) {
		import('pages.manager.GroupHandler');
		GroupHandler::moveGroup($args);
	}

	function moveMembership($args) {
		import('pages.manager.GroupHandler');
		GroupHandler::moveMembership($args);
	}

	//
	// Statistics Functions
	//

	function statistics($args) {
		import('pages.manager.StatisticsHandler');
		StatisticsHandler::statistics($args);
	}
	
	function saveStatisticsSections() {
		import('pages.manager.StatisticsHandler');
		StatisticsHandler::saveStatisticsSections();
	}

	function savePublicStatisticsList() {
		import('pages.manager.StatisticsHandler');
		StatisticsHandler::savePublicStatisticsList();
	}

	function reportGenerator($args) {
		import('pages.manager.StatisticsHandler');
		StatisticsHandler::reportGenerator($args);
	}
}

?>
