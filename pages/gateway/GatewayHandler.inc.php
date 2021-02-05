<?php

/**
 * @file pages/gateway/GatewayHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class GatewayHandler
 * @ingroup pages_gateway
 *
 * @brief Handle external gateway requests.
 */

import('classes.handler.Handler');

class GatewayHandler extends Handler {

	var $plugin;

	/**
	 * Constructor
	 *
	 * @param $request PKPRequest
	 */
	function __construct($request) {
		parent::__construct();
		$op = $request->getRouter()->getRequestedOp($request);
		if ($op == 'plugin') {
			$args = $request->getRouter()->getRequestedArgs($request);
			$pluginName = array_shift($args);
			$plugins = PluginRegistry::loadCategory('gateways');
			if (!isset($plugins[$pluginName])) {
				$request->getDispatcher()->handle404();
			}
			$this->plugin = $plugins[$pluginName];
			foreach ($this->plugin->getPolicies($request) as $policy) {
				$this->addPolicy($policy);
			}
		}
	}

	/**
	 * Index handler.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function index($args, $request) {
		$request->redirect(null, 'index');
	}

	/**
	 * Display the LOCKSS manifest.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function lockss($args, $request) {
		$this->validate();
		$this->setupTemplate($request);

		$journal = $request->getJournal();
		$templateMgr = TemplateManager::getManager($request);

		if ($journal != null) {
			if (!$journal->getData('enableLockss')) {
				$request->redirect(null, 'index');
			}

			$year = $request->getUserVar('year');

			$issueDao = DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */

			// FIXME Should probably go in IssueDAO or a subclass
			if (isset($year)) {
				$year = (int)$year;
				$result = $issueDao->retrieve(
					'SELECT * FROM issues WHERE journal_id = ? AND year = ? AND published = 1 ORDER BY current DESC, year ASC, volume ASC, number ASC',
					[$journal->getId(), $year]
				);
				if (!$result->current()) unset($year);
			}

			if (!isset($year)) {
				$result = $issueDao->retrieve(
					'SELECT MAX(year) AS max_year FROM issues WHERE journal_id = ? AND published = 1',
					[$journal->getId()]
				);
				$row = $result->current();
				$year = $row?$row->max_year:null;
				$templateMgr->assign('showInfo', true);
			}

			$prevYear = $nextYear = null;
			if (isset($year)) {
				$result = $issueDao->retrieve(
					'SELECT MAX(year) AS max_year FROM issues WHERE journal_id = ? AND published = 1 AND year < ?',
					[$journal->getId(), $year]
				);
				$row = $result->current();
				$prevYear = $row?$row->max_year:null;

				$result = $issueDao->retrieve(
					'SELECT MIN(year) AS min_year FROM issues WHERE journal_id = ? AND published = 1 AND year > ?',
					[$journal->getId(), $year]
				);
				$row = $result->current();
				$nextYear = $row?$row->min_year:null;
			}

			$issues = $issueDao->getPublishedIssuesByNumber($journal->getId(), null, null, $year);
			$templateMgr->assign([
				'journal' => $journal,
				'year' => $year,
				'prevYear' => $prevYear,
				'nextYear' => $nextYear,
				'issues' => $issues,
			]);

			$locales = $journal->getSupportedLocaleNames();
			if (!isset($locales) || empty($locales)) {
				$localeNames = AppLocale::getAllLocales();
				$primaryLocale = AppLocale::getPrimaryLocale();
				$locales = array($primaryLocale => $localeNames[$primaryLocale]);
			}
			$templateMgr->assign('locales', $locales);
		} else {
			$journalDao = DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
			$journals = $journalDao->getAll(true);
			$templateMgr->assign('journals', $journals);
		}

		$templateMgr->display('gateway/lockss.tpl');
	}

	/**
	 * Display the CLOCKSS manifest.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function clockss($args, $request) {
		$this->validate();
		$this->setupTemplate($request);

		$journal = $request->getJournal();
		$templateMgr = TemplateManager::getManager($request);

		if ($journal != null) {
			if (!$journal->getData('enableClockss')) {
				$request->redirect(null, 'index');
			}

			$year = $request->getUserVar('year');

			$issueDao = DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */

			// FIXME Should probably go in IssueDAO or a subclass
			if (isset($year)) {
				$year = (int)$year;
				$result = $issueDao->retrieve(
					'SELECT * FROM issues WHERE journal_id = ? AND year = ? AND published = 1 ORDER BY current DESC, year ASC, volume ASC, number ASC',
					[$journal->getId(), $year]
				);
				$row = $result->current();
				if (!$row) unset($year);
			}

			if (!isset($year)) {
				$result = $issueDao->retrieve(
					'SELECT MAX(year) AS max_year FROM issues WHERE journal_id = ? AND published = 1',
					[$journal->getId()]
				);
				$row = $result->current();
				$year = $row?$row->max_year:null;
				$issues = $issueDao->getPublishedIssuesByNumber($journal->getId(), null, null, $year);
				$templateMgr->assign([
					'issues' => $issues,
					'showInfo' => true,
				]);
			}

			$prevYear = $nextYear = null;
			if (isset($year)) {
				$result = $issueDao->retrieve(
					'SELECT MAX(year) AS max_year FROM issues WHERE journal_id = ? AND published = 1 AND year < ?',
					[$journal->getId(), $year]
				);
				$row = $result->current();
				$prevYear = $row?$row->max_year:null;

				$result = $issueDao->retrieve(
					'SELECT MIN(year) AS min_year FROM issues WHERE journal_id = ? AND published = 1 AND year > ?',
					[$journal->getId(), $year]
				);
				$row = $result->current();
				$nextYear = $row?$row->min_year:null;
			}

			$issues = $issueDao->getPublishedIssuesByNumber($journal->getId(), null, null, $year);
			$templateMgr->assign([
				'journal' => $journal,
				'year' => $year,
				'prevYear' => $prevYear,
				'nextYear' => $nextYear,
				'issues' => $issues,
			]);

			$locales = $journal->getSupportedLocaleNames();
			if (!isset($locales) || empty($locales)) {
				$localeNames = AppLocale::getAllLocales();
				$primaryLocale = AppLocale::getPrimaryLocale();
				$locales = array($primaryLocale => $localeNames[$primaryLocale]);
			}
			$templateMgr->assign('locales', $locales);

		} else {
			$journalDao = DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
			$journals = $journalDao->getAll(true);
			$templateMgr->assign('journals', $journals);
		}

		$templateMgr->display('gateway/clockss.tpl');
	}

	/**
	 * Handle requests for gateway plugins.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function plugin($args, $request) {
		$this->validate();
		if (isset($this->plugin)) {
			if (!$this->plugin->fetch(array_slice($args, 1), $request)) {
				$request->redirect(null, 'index');
			}
		} else {
			$request->redirect(null, 'index');
		}
	}
}
