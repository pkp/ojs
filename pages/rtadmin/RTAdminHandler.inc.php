<?php

/**
 * RTAdminHandler.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.rtadmin
 *
 * Handle Reading Tools administration requests. 
 *
 * $Id$
 */

import('rt.ojs.JournalRTAdmin');

import('pages.rtadmin.RTSetupHandler');
import('pages.rtadmin.RTVersionHandler');
import('pages.rtadmin.RTContextHandler');
import('pages.rtadmin.RTSearchHandler');

class RTAdminHandler extends Handler {

	/**
	 * If no journal is selected, display list of journals.
	 * Otherwise, display the index page for the selected journal.
	 */
	function index() {
		$journal = Request::getJournal();
		$user = Request::getUser();
		if ($user && $journal) {
			// Display the administration menu for this journal.

			RTAdminHandler::setupTemplate();
			$templateMgr = &TemplateManager::getManager();
			$templateMgr->display('rtadmin/index.tpl');
		} elseif ($user) {
			// Display a list of journals.
			$journalDao = &DAORegistry::getDAO('JournalDAO');
			$roleDao = &DAORegistry::getDAO('RoleDAO');

			$journals = array();

			foreach ($journalDao->getJournals() as $journal) {
				if ($roleDao->roleExists($journal->getJournalId(), $user->getUserId(), ROLE_ID_JOURNAL_MANAGER)) {
					$journals[] = $journal;
				}
			}

			RTAdminHandler::setupTemplate();
			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign('journals', &$journals);
			$templateMgr->display('rtadmin/journals.tpl');
		} else {
			// Not logged in.
			Validation::redirectLogin();
		}
	}
	
	/**
	 * Ensure that this page is available to the user.
	 */
	function validate() {
		parent::validate(true);
		if (!Validation::isJournalManager()) {
			Validation::redirectLogin();
		}
	}
	
	
	//
	// General
	//
	
	function settings() {
		RTSetupHandler::settings();
	}
	
	function saveSettings() {
		RTSetupHandler::saveSettings();
	}
	
	//
	// Versions
	//

	function createVersion($args) {
		RTVersionHandler::createVersion($args);
	}

	function exportVersion($args) {
		RTVersionHandler::exportVersion($args);
	}
	
	function importVersion($args) {
		RTVersionHandler::importVersion($args);
	}
	
	function restoreVersions() {
		RTVersionHandler::restoreVersions();
	}
	
	function versions() {
		RTVersionHandler::versions();
	}
	
	function editVersion($args) {
		RTVersionHandler::editVersion($args);
	}

	function deleteVersion($args) {
		RTVersionHandler::deleteVersion($args);
	}
	
	function saveVersion($args) {
		RTVersionHandler::saveVersion($args);
	}
	
	
	//
	// Contexts
	//
	
	function createContext($args) {
		RTContextHandler::createContext($args);
	}

	function contexts($args) {
		RTContextHandler::contexts($args);
	}
	
	function editContext($args) {
		RTContextHandler::editContext($args);
	}
	
	function saveContext($args) {
		RTContextHandler::saveContext($args);
	}
	
	function deleteContext($args) {
		RTContextHandler::deleteContext($args);
	}
	
	function moveContext($args) {
		RTContextHandler::moveContext($args);
	}
	
	
	//
	// Searches
	//
	
	function createSearch($args) {
		RTSearchHandler::createSearch($args);
	}

	function searches($args) {
		RTSearchHandler::searches($args);
	}
	
	function editSearch($args) {
		RTSearchHandler::editSearch($args);
	}
	
	function saveSearch($args) {
		RTSearchHandler::saveSearch($args);
	}

	function deleteSearch($args) {
		RTSearchHandler::deleteSearch($args);
	}

	function moveSearch($args) {
		RTSearchHandler::moveSearch($args);
	}

	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 * @param $version object The current version, if applicable
	 * @param $context object The current context, if applicable
	 * @param $search object The current search, if applicable
	 */
	function setupTemplate($subclass = false, $version = null, $context = null, $search = null) {
		$templateMgr = &TemplateManager::getManager();

		$pageHierarchy = array(array('user', 'navigation.user'), array('manager', 'manager.journalManagement'));

		if ($subclass) $pageHierarchy[] = array('rtadmin', 'rt.readingTools');

		if ($version) {
			$pageHierarchy[] = array('rtadmin/versions', 'rt.versions');
			$pageHierarchy[] = array('rtadmin/editVersion/' . $version->getVersionId(), $version->getTitle(), true);
			if ($context) {
				$pageHierarchy[] = array('rtadmin/contexts/' . $version->getVersionId(), 'rt.contexts');
				$pageHierarchy[] = array('rtadmin/editContext/' . $version->getVersionId() . '/' . $context->getContextId(), $context->getAbbrev(), true);
				if ($search) {
					$pageHierarchy[] = array('rtadmin/searches/' . $version->getVersionId() . '/' . $context->getContextId(), 'rt.searches');
					$pageHierarchy[] = array('rtadmin/editSearch/' . $version->getVersionId() . '/' . $context->getContextId() . '/' . $search->getSearchId(), $search->getTitle(), true);
				}
			}
		}
		$templateMgr->assign('pageHierarchy', &$pageHierarchy);
		$templateMgr->assign('pagePath', '/user/rtadmin');
	}
}

?>
