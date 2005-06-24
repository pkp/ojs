<?php

/**
 * ManagerHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.manager
 *
 * Handle requests for journal management functions. 
 *
 * $Id$
 */

import('pages.manager.PeopleHandler');
import('pages.manager.SectionHandler');
import('pages.manager.SetupHandler');
import('pages.manager.EmailHandler');
import('pages.manager.JournalLanguagesHandler');
import('pages.manager.FilesHandler');
import('pages.manager.SubscriptionHandler');
import('pages.manager.ImportExportHandler');

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

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('subscriptionsEnabled', $subscriptionsEnabled);
		$templateMgr->assign('helpTopicId','journal.index');
		$templateMgr->display('manager/index.tpl');
	}
	
	/**
	 * Validate that user has permissions to manage the selected journal.
	 * Redirects to user index page if not properly authenticated.
	 */
	function validate() {
		parent::validate();
		if (!Validation::isJournalManager()) {
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
			$subclass ? array(array('user', 'navigation.user'), array('manager', 'manager.journalManagement'))
				: array(array('user', 'navigation.user'))
		);
		$templateMgr->assign('pagePath', '/user/manager');
	}
	
	
	//
	// Setup
	//

	function setup($args) {
		SetupHandler::setup($args);
	}

	function saveSetup($args) {
		SetupHandler::saveSetup($args);
	}
	
	
	//
	// People Management
	//

	function people($args) {
		PeopleHandler::people($args);
	}
	
	function enrollSearch($args) {
		PeopleHandler::enrollSearch($args);
	}
	
	function enroll($args) {
		PeopleHandler::enroll($args);
	}
	
	function unEnroll($args) {
		PeopleHandler::unEnroll($args);
	}
	
	function createUser() {
		PeopleHandler::createUser();
	}
	
	function disableUser($args) {
		PeopleHandler::disableUser($args);
	}
	
	function enableUser($args) {
		PeopleHandler::enableUser($args);
	}
	
	function removeUser($args) {
		PeopleHandler::removeUser($args);
	}
	
	function editUser($args) {
		PeopleHandler::editUser($args);
	}
	
	function updateUser() {
		PeopleHandler::updateUser();
	}
	
	function userProfile($args) {
		PeopleHandler::userProfile($args);
	}
	
	function signInAsUser($args) {
		PeopleHandler::signInAsUser($args);
	}
	
	function signOutAsUser() {
		PeopleHandler::signOutAsUser();
	}
	
	
	//
	// Section Management
	//
	
	function sections() {
		SectionHandler::sections();
	}
	
	function createSection() {
		SectionHandler::createSection();
	}
	
	function editSection($args) {
		SectionHandler::editSection($args);
	}
	
	function updateSection() {
		SectionHandler::updateSection();
	}
	
	function deleteSection($args) {
		SectionHandler::deleteSection($args);
	}
	
	function moveSection() {
		SectionHandler::moveSection();
	}
	
	
	//
	// E-mail Management
	//
	
	function emails() {
		EmailHandler::emails();
	}
	
	function editEmail($args) {
		EmailHandler::editEmail($args);
	}
	
	function updateEmail() {
		EmailHandler::updateEmail();
	}
	
	function resetEmail($args) {
		EmailHandler::resetEmail($args);
	}
	
	function disableEmail($args) {
		EmailHandler::disableEmail($args);
	}
	
	function enableEmail($args) {
		EmailHandler::enableEmail($args);
	}
	
	function resetAllEmails() {
		EmailHandler::resetAllEmails();
	}
	
	function email($args) {
		PeopleHandler::email($args);
	}
	
	function selectTemplate($args) {
		PeopleHandler::selectTemplate($args);
	}
	
	
	//
	// Languages
	//
	
	function languages() {
		JournalLanguagesHandler::languages();
	}
	
	function saveLanguageSettings() {
		JournalLanguagesHandler::saveLanguageSettings();
	}
	
	
	//
	// Files Browser
	//
	
	function files($args) {
		FilesHandler::files($args);
	}
	
	function fileUpload($args) {
		FilesHandler::fileUpload($args);
	}
	
	function fileMakeDir($args) {
		FilesHandler::fileMakeDir($args);
	}
	
	function fileDelete($args) {
		FilesHandler::fileDelete($args);
	}


	//
	// Subscription Types
	//

	function subscriptionTypes() {
		SubscriptionHandler::subscriptionTypes();
	}

	function deleteSubscriptionType($args) {
		SubscriptionHandler::deleteSubscriptionType($args);
	}

	function createSubscriptionType() {
		SubscriptionHandler::createSubscriptionType();
	}

	function editSubscriptionType($args) {
		SubscriptionHandler::editSubscriptionType($args);
	}

	function updateSubscriptionType($args) {
		SubscriptionHandler::updateSubscriptionType($args);
	}


	//
	// Subscriptions
	//

	function subscriptions() {
		SubscriptionHandler::subscriptions();
	}

	function deleteSubscription($args) {
		SubscriptionHandler::deleteSubscription($args);
	}

	function createSubscription() {
		SubscriptionHandler::createSubscription();
	}

	function editSubscription($args) {
		SubscriptionHandler::editSubscription($args);
	}

	function updateSubscription($args) {
		SubscriptionHandler::updateSubscription($args);
	}

	//
	// Import/Export
	//

	function importexport($args) {
		ImportExportHandler::importExport($args);
	}
}

?>
