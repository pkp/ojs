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

	function validateUrls($args) {
		RTAdminHandler::validate();

		$rtDao = &DAORegistry::getDAO('RTDAO');
		$journal = Request::getJournal();

		if (!$journal) {
			Request::redirect('rtadmin');
			return;
		}

		$versionId = isset($args[0])?$args[0]:0;
		$journalId = $journal->getJournalId();

		$version = $rtDao->getVersion($versionId, $journalId);

		if ($version) {
			// Validate the URLs for a single version
			$versions = array(&$version);
		} else {
			// Validate all URLs for this journal
			$versions = $rtDao->getVersions($journalId);
		}

		RTAdminHandler::setupTemplate(true, $version);
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->register_modifier('validate_url', 'smarty_rtadmin_validate_url');
		$templateMgr->assign('versions', $versions);
		$templateMgr->display('rtadmin/validate.tpl');
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

function rtadmin_validate_url($url, $useGet = false, $redirectsAllowed = 5) {
	$data = parse_url($url);
	if(!isset($data['host'])) {
		echo '<!-- Hostname invalid -->';
		return false;
	}

	$fp = @ fsockopen($data['host'], isset($data['port']) && !empty($data['port']) ? $data['port'] : 80, $errno, $errstr, 10);
	if (!$fp) {
		echo '<!-- Unable to open socket to host -->';
		return false;
	}

	$req = sprintf("%s %s HTTP/1.0\r\nHost: %s\r\nUser-Agent: Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.4b) Gecko/20030516\r\n\r\n", ($useGet ? 'GET' : 'HEAD'), (isset($data['path']) && $data['path'] !== '' ? $data['path'] : '/') .  (isset($data['query']) && $data['query'] !== '' ? '?' .  $data['query'] : ''), $data['host']);

	fputs($fp, $req);

	for($res = '', $time = time(); !feof($fp) && $time >= time() - 15; ) {
		$res .= fgets($fp, 128);
	}

	fclose($fp);

	// Check result for HTTP status code.
	if(!preg_match('!^HTTP/(\d\.?\d*) (\d+)\s*(.+)[\n\r]!m', $res, $matches)) {
		echo '<!-- Unable to determine HTTP status code from response -->';
		return false;
	}
	list($match, $http_version, $http_status_no, $http_status_str) = $matches;

	// If HTTP status code 2XX (Success)
	if(preg_match('!^2\d\d$!', $http_status_no)) return true;

	// If HTTP status code 3XX (Moved)
	if(preg_match('!^(?:(?:Location)|(?:URI)|(?:location)): ([^\s]+)[\r\n]!m', $res, $matches)) {
		// Recursively validate the URL if an additional redirect is allowed..
		if ($redirectsAllowed >= 1) return rtadmin_validate_url(preg_match('!^https?://!', $matches[1]) ? $matches[1] : $data['scheme'] . '://' . $data['host'] . ($data['path'] !== '' && strpos($matches[1], '/') !== 0  ? $data['path'] : (strpos($matches[1], '/') === 0 ? '' : '/')) . $matches[1], $useGet, $redirectsAllowed-1);
		echo '<!-- Too many redirects -->';
		return false;
	}

	// If it's not found or there is an error condition
	if(($http_status_no == 403 || $http_status_no == 404 || $http_status_no == 405 || $http_status_no == 500 || strstr($res, 'Bad Request') || strstr($res, 'Bad HTTP Request') || trim($res) == '') && !$useGet) {
		return rtadmin_validate_url($url, true, $redirectsAllowed-1);
	}

	echo '<!-- Misc. Error; Response: ' . $res . ' -->';
	return false;
}

function smarty_rtadmin_validate_url ($search, $errors) {
	// Make sure any prior content is flushed to the user's browser.
	flush();
	ob_flush();

	if (!is_array($errors)) $errors = array();

	if (!rtadmin_validate_url($search->getUrl())) {
		$errors[] = $search->getUrl();
	}
	return $errors;
}
?>
