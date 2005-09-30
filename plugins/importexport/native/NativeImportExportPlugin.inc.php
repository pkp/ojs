<?php

/**
 * NativeImportExportPlugin.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * Native import/export plugin
 *
 * $Id$
 */

import('classes.plugins.ImportExportPlugin');

import('xml.XMLWriter');

class NativeImportExportPlugin extends ImportExportPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param @category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		$this->addLocaleData();
		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'NativeImportExportPlugin';
	}

	function getDisplayName() {
		return Locale::translate('plugins.importexport.native.displayName');
	}

	function getDescription() {
		return Locale::translate('plugins.importexport.native.description');
	}

	function display(&$args) {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pluginUrl', $this->getPluginUrl());

		$issueDao = &DAORegistry::getDAO('IssueDAO');

		$journal = &Request::getJournal();
		switch (array_shift($args)) {
			case 'exportIssues':
				$issueIds = Request::getUserVar('issueId');
				if (!isset($issueIds)) $issueIds = array();
				$issues = array();
				foreach ($issueIds as $issueId) {
					$issue = &$issueDao->getIssueById($issueId);
					if (!$issue) Request::redirect($this->getPluginUrl());
					$issues[] = &$issue;
				}
				$this->exportIssues($journal, $issues);
				break;
			case 'exportIssue':
				$issueId = array_shift($args);
				$issue = &$issueDao->getIssueById($issueId);
				if (!$issue) Request::redirect($this->getPluginUrl());
				$this->exportIssue($journal, $issue);
				break;
			case 'exportArticle':
				$articleIds = array(array_shift($args));
				$result = array_shift(ArticleSearch::formatResults($articleIds));
				$this->exportArticle($journal, $result['issue'], $result['section'], $result['publishedArticle']);
				break;
			case 'exportArticles':
				$articleIds = Request::getUserVar('articleId');
				if (!isset($articleIds)) $articleIds = array();
				$results = &ArticleSearch::formatResults($articleIds);
				$this->exportArticles($results);
				break;
			case 'issues':
				// Display a list of issues for export
				$this->setBreadcrumbs(array(), true);
				$issueDao = &DAORegistry::getDAO('IssueDAO');
				$issues = &$issueDao->getIssues($journal->getJournalId(), Handler::getRangeInfo('issues'));

				$templateMgr->assign_by_ref('issues', $issues);
				$templateMgr->display($this->getTemplatePath() . 'issues.tpl');
				break;
			case 'articles':
				// Display a list of articles for export
				$this->setBreadcrumbs(array(), true);
				$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
				$rangeInfo = Handler::getRangeInfo('articles');
				$articleIds = $publishedArticleDao->getPublishedArticleIdsAlphabetizedByJournal($journal->getJournalId(), $rangeInfo);
				$totalArticles = count($articleIds);
				if ($rangeInfo->isValid()) $articleIds = array_slice($articleIds, $rangeInfo->getCount() * ($rangeInfo->getPage()-1), $rangeInfo->getCount());
				$iterator = &new VirtualArrayIterator(ArticleSearch::formatResults($articleIds), $totalArticles, $rangeInfo->getPage(), $rangeInfo->getCount());
				$templateMgr->assign_by_ref('articles', $iterator);
				$templateMgr->display($this->getTemplatePath() . 'articles.tpl');
				break;
			case 'import':
				import('file.TemporaryFileManager');
				$issueDao = &DAORegistry::getDAO('IssueDAO');
				$sectionDao = &DAORegistry::getDAO('SectionDAO');
				$user = &Request::getUser();
				$temporaryFileManager = new TemporaryFileManager();

				if (($existingFileId = Request::getUserVar('temporaryFileId'))) {
					// The user has just entered more context. Fetch an existing file.
					$temporaryFile = TemporaryFileManager::getFile($existingFileId, $user->getUserId());
				} else {
					$temporaryFile = $temporaryFileManager->handleUpload('importFile', $user->getUserId());
				}

				$context = array(
					'journal' => $journal,
					'user' => $user
				);

				if (($sectionId = Request::getUserVar('sectionId'))) {
					$context['section'] = $sectionDao->getSection($sectionId);
				}

				if (($issueId = Request::getUserVar('issueId'))) {
					$context['issue'] = $issueDao->getIssueById($issueId, $journal->getJournalId());
				}

				$doc = &$this->getDocument($temporaryFile->getFilePath());

				if (substr($this->getRootNodeName($doc), 0, 7) === 'article') {
					// Ensure the user has supplied enough valid information to
					// import articles within an appropriate context. If not,
					// prompt them for the.
					if (!isset($context['issue']) || !isset($context['section'])) {
						$issues = &$issueDao->getIssues($journal->getJournalId(), Handler::getRangeInfo('issues'));
						$templateMgr->assign_by_ref('issues', $issues);
						$templateMgr->assign('sectionOptions', array('0' => Locale::translate('author.submit.selectSection')) + $sectionDao->getSectionTitles($journal->getJournalId(), false));
						$templateMgr->assign('temporaryFileId', $temporaryFile->getFileId());
						return $templateMgr->display($this->getTemplatePath() . 'articleContext.tpl');
					}
				}

				if ($this->handleImport($context, $doc, $errors, $issues, $articles)) {
					$templateMgr->assign_by_ref('issues', $issues);
					$templateMgr->assign_by_ref('articles', $articles);
					return $templateMgr->display($this->getTemplatePath() . 'importSuccess.tpl');
				} else {
					$templateMgr->assign_by_ref('errors', $errors);
					return $templateMgr->display($this->getTemplatePath() . 'importError.tpl');
				}
				break;
			default:
				$this->setBreadcrumbs();
				$templateMgr->display($this->getTemplatePath() . 'index.tpl');
		}
	}

	function exportIssue(&$journal, &$issue, $outputFile = null) {
		require_once(dirname(__FILE__) . '/NativeExportDom.inc.php');
		$doc = &XMLWriter::createDocument('issue', 'native.dtd');
		$issueNode = &NativeExportDom::generateIssueDom($doc, $journal, $issue);
		XMLWriter::appendChild($doc, $issueNode);

		if (!empty($outputFile)) {
			if (($h = fopen($outputFile, 'w'))===false) return false;
			fwrite($h, XMLWriter::getXML($doc));
			fclose($h);
		} else {
			header("Content-Type: application/xml");
			XMLWriter::printXML($doc);
		}
		return true;
	}

	function exportArticle(&$journal, &$issue, &$section, &$article, $outputFile = null) {
		require_once(dirname(__FILE__) . '/NativeExportDom.inc.php');
		$doc = &XMLWriter::createDocument('article', '/native.dtd');
		$articleNode = &NativeExportDom::generateArticleDom($doc, $journal, $issue, $section, $article);
		XMLWriter::appendChild($doc, $articleNode);

		if (!empty($outputFile)) {
			if (($h = fopen($outputFile, 'w'))===false) return false;
			fwrite($h, XMLWriter::getXML($doc));
			fclose($h);
		} else {
			header("Content-Type: application/xml");
			XMLWriter::printXML($doc);
		}
		return true;
	}

	function exportIssues(&$journal, &$issues, $outputFile = null) {
		require_once(dirname(__FILE__) . '/NativeExportDom.inc.php');
		$doc = &XMLWriter::createDocument('issues', '/native.dtd');
		$issuesNode = &XMLWriter::createElement($doc, 'issues');
		XMLWriter::appendChild($doc, $issuesNode);

		foreach ($issues as $issue) {
			$issueNode = &NativeExportDom::generateIssueDom($doc, $journal, $issue);
			XMLWriter::appendChild($issuesNode, $issueNode);
		}

		if (!empty($outputFile)) {
			if (($h = fopen($outputFile, 'w'))===false) return false;
			fwrite($h, XMLWriter::getXML($doc));
			fclose($h);
		} else {
			header("Content-Type: application/xml");
			XMLWriter::printXML($doc);
		}
		return true;
	}

	function exportArticles(&$results, $outputFile = null) {
		require_once(dirname(__FILE__) . '/NativeExportDom.inc.php');
		$doc = &XMLWriter::createDocument('articles', '/native.dtd');
		$articlesNode = &XMLWriter::createElement($doc, 'articles');
		XMLWriter::appendChild($doc, $articlesNode);

		foreach ($results as $result) {
			$article = &$result['publishedArticle'];
			$section = &$result['section'];
			$issue = &$result['issue'];
			$journal = &$result['journal'];
			$articleNode = &NativeExportDom::generateArticleDom($doc, $journal, $issue, $section, $article);
			XMLWriter::appendChild($articlesNode, $articleNode);
		}

		if (!empty($outputFile)) {
			if (($h = fopen($outputFile, 'w'))===false) return false;
			fwrite($h, XMLWriter::getXML($doc));
			fclose($h);
		} else {
			header("Content-Type: application/xml");
			XMLWriter::printXML($doc);
		}
		return true;
	}

	function &getDocument($fileName) {
		$parser = new XMLParser();
		return $parser->parse($fileName);
	}

	function getRootNodeName(&$doc) {
		return $doc->name;
	}

	function handleImport(&$context, &$doc, &$errors, &$issues, &$articles) {
		$errors = array();
		$issues = array();
		$articles = array();

		$user = &$context['user'];
		$journal = &$context['journal'];

		$rootNodeName = $this->getRootNodeName($doc);

		require_once(dirname(__FILE__) . '/NativeImportDom.inc.php');

		switch ($rootNodeName) {
			case 'issues':
				return NativeImportDom::importIssues($journal, $doc->children, $issues, $errors, $user, false);
				break;
			case 'issue':
				$result = NativeImportDom::importIssue($journal, $doc, $issue, $errors, $user, false);
				if ($result) $issues = array($issue);
				return $result;
				break;
			case 'articles':
				$section = &$context['section'];
				$issue = &$context['issue'];
				return NativeImportDom::importArticles($journal, $doc->children, $issue, $section, $articles, $errors, $user, false);
				break;
			case 'article':
				$section = &$context['section'];
				$issue = &$context['issue'];
				$result = NativeImportDom::importArticle($journal, $doc, $issue, $section, $article, $errors, $user, false);
				if ($result) $articles = array($article);
				return $result;
				break;
			default:
				$errors[] = array('plugins.importexport.native.import.error.unsupportedRoot', array('rootName' => $rootNodeName));
				return false;
				break;
		}
	}

	/**
	 * Execute import/export tasks using the command-line interface.
	 * @param $args Parameters to the plugin
	 */ 
	function executeCLI($scriptName, &$args) {
		$command = array_shift($args);
		$xmlFile = array_shift($args);
		$journalPath = array_shift($args);

		$journalDao = &DAORegistry::getDAO('JournalDAO');
		$issueDao = &DAORegistry::getDAO('IssueDAO');
		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');

		$journal = &$journalDao->getJournalByPath($journalPath);

		if (!$journal) {
			if ($journalPath != '') {
				echo Locale::translate('plugins.importexport.native.cliError') . "\n";
				echo Locale::translate('plugins.importexport.native.error.unknownJournal', array('journalPath' => $journalPath)) . "\n\n";
			}
			$this->usage($scriptName);
			return;
		}

		switch ($command) {
			case 'import':
				$userName = array_shift($args);
				$user = &$userDao->getUserByUsername($userName);

				if (!$user) {
					if ($userName != '') {
						echo Locale::translate('plugins.importexport.native.cliError') . "\n";
						echo Locale::translate('plugins.importexport.native.error.unknownUser', array('userName' => $userName)) . "\n\n";
					}
					$this->usage($scriptName);
					return;
				}

				$doc = &$this->getDocument($xmlFile);

				$context = array(
					'user' => $user,
					'journal' => $journal
				);

				switch ($this->getRootNodeName($doc)) {
					case 'article':
					case 'articles':
						// Determine the extra context information required
						// for importing articles.
						if (array_shift($args) !== 'issue_id') return $this->usage($scriptName);
						$issue = &$issueDao->getIssueByBestIssueId(($issueId = array_shift($args)), $journal->getJournalId());
						if (!$issue) {
							echo Locale::translate('plugins.importexport.native.cliError') . "\n";
							echo Locale::translate('plugins.importexport.native.export.error.issueNotFound', array('issueId' => $issueId)) . "\n\n";
							return;
						}

						$context['issue'] = &$issue;

						switch (array_shift($args)) {
							case 'section_id':
								$section = &$sectionDao->getSection(($sectionIdentifier = array_shift($args)));
								break;
							case 'section_name':
								$section = &$sectionDao->getSectionByTitle(($sectionIdentifier = array_shift($args)), $journal->getJournalId());
								break;
							case 'section_abbrev':
								$section = &$sectionDao->getSectionByAbbrev(($sectionIdentifier = array_shift($args)));
								break;
							default:
								return $this->usage($scriptName);
						}

						if (!$section) {
							echo Locale::translate('plugins.importexport.native.cliError') . "\n";
							echo Locale::translate('plugins.importexport.native.export.error.sectionNotFound', array('sectionIdentifier' => $sectionIdentifier)) . "\n\n";
							return;
						}
						$context['section'] = &$section;
				}

				$result = $this->handleImport($context, $doc, $errors, $issues, $articles);
				if ($result) {
					echo Locale::translate('plugins.importexport.native.import.success.description') . "\n\n";
					if (!empty($issues)) echo Locale::translate('issue.issues') . ":\n";
					foreach ($issues as $issue) {
						echo "\t" . $issue->getIssueIdentification() . "\n";
					}

					if (!empty($articles)) echo Locale::translate('article.articles') . ":\n";
					foreach ($articles as $article) {
						echo "\t" . $article->getTitle() . "\n";
					}
				} else {
					echo Locale::translate('plugins.importexport.native.cliError') . "\n";
					foreach ($errors as $error) {
						echo "\t" . Locale::translate($error[0], $error[1]) . "\n";
					}
				}
				return;
				break;
			case 'export':
				if ($xmlFile != '') switch (array_shift($args)) {
					case 'article':
						$articleId = array_shift($args);
						$publishedArticle = &$publishedArticleDao->getPublishedArticleByBestArticleId($articleId, $journal->getJournalId());
						if ($publishedArticle == null) {
							echo Locale::translate('plugins.importexport.native.cliError') . "\n";
							echo Locale::translate('plugins.importexport.native.export.error.articleNotFound', array('articleId' => $articleId)) . "\n\n";
							return;
						}
						$issue = &$issueDao->getIssueById($publishedArticle->getIssueId());

						$sectionDao = &DAORegistry::getDAO('SectionDAO');
						$section = &$sectionDao->getSection($publishedArticle->getSectionId());

						if (!$this->exportArticle($journal, $issue, $section, $publishedArticle, $xmlFile)) {
							echo Locale::translate('plugins.importexport.native.cliError') . "\n";
							echo Locale::translate('plugins.importexport.native.export.error.couldNotWrite', array('fileName' => $xmlFile)) . "\n\n";
						}
						return;
					case 'articles':
						$results = &ArticleSearch::formatResults($args);
						if (!$this->exportArticles($results, $xmlFile)) {
							echo Locale::translate('plugins.importexport.native.cliError') . "\n";
							echo Locale::translate('plugins.importexport.native.export.error.couldNotWrite', array('fileName' => $xmlFile)) . "\n\n";
						}
						return;
					case 'issue':
						$issueId = array_shift($args);
						$issue = &$issueDao->getIssueByBestIssueId($issueId, $journal->getJournalId());
						if ($issue == null) {
							echo Locale::translate('plugins.importexport.native.cliError') . "\n";
							echo Locale::translate('plugins.importexport.native.export.error.issueNotFound', array('issueId' => $issueId)) . "\n\n";
							return;
						}
						if (!$this->exportIssue($journal, $issue, $xmlFile)) {
							echo Locale::translate('plugins.importexport.native.cliError') . "\n";
							echo Locale::translate('plugins.importexport.native.export.error.couldNotWrite', array('fileName' => $xmlFile)) . "\n\n";
						}
						return;
					case 'issues':
						$issues = array();
						while (($issueId = array_shift($args))!==null) {
							$issue = &$issueDao->getIssueByBestIssueId($issueId, $journal->getJournalId());
							if ($issue == null) {
								echo Locale::translate('plugins.importexport.native.cliError') . "\n";
								echo Locale::translate('plugins.importexport.native.export.error.issueNotFound', array('issueId' => $issueId)) . "\n\n";
								return;
							}
							$issues[] = &$issue;
						}
						if (!$this->exportIssues($journal, $issues, $xmlFile)) {
							echo Locale::translate('plugins.importexport.native.cliError') . "\n";
							echo Locale::translate('plugins.importexport.native.export.error.couldNotWrite', array('fileName' => $xmlFile)) . "\n\n";
						}
						return;
				}
				break;
		}
		$this->usage($scriptName);
	}

	/**
	 * Display the command-line usage information
	 */
	function usage($scriptName) {
		echo Locale::translate('plugins.importexport.native.cliUsage', array(
			'scriptName' => $scriptName,
			'pluginName' => $this->getName()
		)) . "\n";
	}
}

?>
