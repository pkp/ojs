<?php

/**
 * @file plugins/importexport/native/NativeImportExportPlugin.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NativeImportExportPlugin
 * @ingroup plugins_importexport_native
 *
 * @brief Native import/export plugin
 */

import('classes.plugins.ImportExportPlugin');

import('lib.pkp.classes.xml.XMLCustomWriter');

define('NATIVE_DTD_URL', 'http://pkp.sfu.ca/ojs/dtds/2.4/native.dtd');
define('NATIVE_DTD_ID', '-//PKP//OJS Articles and Issues XML//EN');

class NativeImportExportPlugin extends ImportExportPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
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
		return __('plugins.importexport.native.displayName');
	}

	function getDescription() {
		return __('plugins.importexport.native.description');
	}

	function display(&$args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		parent::display($args, $request);

		$issueDao = DAORegistry::getDAO('IssueDAO');

		$journal = $request->getJournal();
		switch (array_shift($args)) {
			case 'exportIssues':
				$issueIds = $request->getUserVar('issueId');
				if (!isset($issueIds)) $issueIds = array();
				$issues = array();
				foreach ($issueIds as $issueId) {
					$issue = $issueDao->getById($issueId, $journal->getId());
					if (!$issue) $request->redirect();
					$issues[] = $issue;
				}
				$this->exportIssues($journal, $issues);
				break;
			case 'exportIssue':
				$issueId = array_shift($args);
				$issue = $issueDao->getById($issueId, $journal->getId());
				if (!$issue) $request->redirect();
				$this->exportIssue($journal, $issue);
				break;
			case 'exportArticle':
				$articleIds = array(array_shift($args));
				$articleSearch = new ArticleSearch();
				$result = array_shift($articleSearch->formatResults($articleIds));
				if (!$result) $request->redirect();
				$this->exportArticle($journal, $result['issue'], $result['section'], $result['publishedArticle']);
				break;
			case 'exportArticles':
				$articleIds = $request->getUserVar('articleId');
				if (!isset($articleIds)) $articleIds = array();
				$articleSearch = new ArticleSearch();
				$results = $articleSearch->formatResults($articleIds);
				$this->exportArticles($results);
				break;
			case 'issues':
				// Display a list of issues for export
				AppLocale::requireComponents(LOCALE_COMPONENT_APP_EDITOR);
				$issueDao = DAORegistry::getDAO('IssueDAO');
				$issues = $issueDao->getIssues($journal->getId(), Handler::getRangeInfo($this->getRequest(), 'issues'));

				$templateMgr->assign('issues', $issues);
				$templateMgr->display($this->getTemplatePath() . 'issues.tpl');
				break;
			case 'articles':
				// Display a list of articles for export
				$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
				$rangeInfo = Handler::getRangeInfo($this->getRequest(), 'articles');
				$articleIds = $publishedArticleDao->getPublishedArticleIdsAlphabetizedByJournal($journal->getId(), false);
				$totalArticles = count($articleIds);
				if ($rangeInfo->isValid()) $articleIds = array_slice($articleIds, $rangeInfo->getCount() * ($rangeInfo->getPage()-1), $rangeInfo->getCount());
				import('lib.pkp.classes.core.VirtualArrayIterator');
				$articleSearch = new ArticleSearch();
				$iterator = new VirtualArrayIterator($articleSearch->formatResults($articleIds), $totalArticles, $rangeInfo->getPage(), $rangeInfo->getCount());
				$templateMgr->assign('articles', $iterator);
				$templateMgr->display($this->getTemplatePath() . 'articles.tpl');
				break;
			case 'import':
				AppLocale::requireComponents(LOCALE_COMPONENT_APP_EDITOR, LOCALE_COMPONENT_APP_AUTHOR);
				import('lib.pkp.classes.file.TemporaryFileManager');
				$issueDao = DAORegistry::getDAO('IssueDAO');
				$sectionDao = DAORegistry::getDAO('SectionDAO');
				$user = $request->getUser();
				$temporaryFileManager = new TemporaryFileManager();

				if (($existingFileId = $request->getUserVar('temporaryFileId'))) {
					// The user has just entered more context. Fetch an existing file.
					$temporaryFile = $temporaryFileManager->getFile($existingFileId, $user->getId());
				} else {
					$temporaryFile = $temporaryFileManager->handleUpload('importFile', $user->getId());
				}

				$context = array(
					'journal' => $journal,
					'user' => $user
				);

				if (($sectionId = $request->getUserVar('sectionId'))) {
					$context['section'] = $sectionDao->getById($sectionId);
				}

				if (($issueId = $request->getUserVar('issueId'))) {
					$context['issue'] = $issueDao->getById($issueId, $journal->getId());
				}

				if (!$temporaryFile) {
					$templateMgr->assign('error', 'plugins.importexport.native.error.uploadFailed');
					return $templateMgr->display($this->getTemplatePath() . 'importError.tpl');
				}

				$doc =& $this->getDocument($temporaryFile->getFilePath());

				if (substr($this->getRootNodeName($doc), 0, 7) === 'article') {
					// Ensure the user has supplied enough valid information to
					// import articles within an appropriate context. If not,
					// prompt them for the.
					if (!isset($context['issue']) || !isset($context['section'])) {
						$issues = $issueDao->getIssues($journal->getId(), Handler::getRangeInfo($this->getRequest(), 'issues'));
						$templateMgr->assign('issues', $issues);
						$templateMgr->assign('sectionOptions', array('0' => __('author.submit.selectSection')) + $sectionDao->getSectionTitles($journal->getId(), false));
						$templateMgr->assign('temporaryFileId', $temporaryFile->getId());
						return $templateMgr->display($this->getTemplatePath() . 'articleContext.tpl');
					}
				}

				@set_time_limit(0);

				if ($this->handleImport($context, $doc, $errors, $issues, $articles, false)) {
					$templateMgr->assign('issues', $issues);
					$templateMgr->assign('articles', $articles);
					return $templateMgr->display($this->getTemplatePath() . 'importSuccess.tpl');
				} else {
					$templateMgr->assign('errors', $errors);
					return $templateMgr->display($this->getTemplatePath() . 'importError.tpl');
				}
				break;
			default:
				$templateMgr->display($this->getTemplatePath() . 'index.tpl');
		}
	}

	function exportIssue(&$journal, &$issue, $outputFile = null) {
		$this->import('NativeExportDom');
		$doc =& XMLCustomWriter::createDocument('issue', NATIVE_DTD_ID, NATIVE_DTD_URL);
		$issueNode =& NativeExportDom::generateIssueDom($doc, $journal, $issue);
		XMLCustomWriter::appendChild($doc, $issueNode);

		if (!empty($outputFile)) {
			if (($h = fopen($outputFile, 'wb'))===false) return false;
			fwrite($h, XMLCustomWriter::getXML($doc));
			fclose($h);
		} else {
			header("Content-Type: application/xml");
			header("Cache-Control: private");
			header("Content-Disposition: attachment; filename=\"issue-" . $issue->getId() . ".xml\"");
			XMLCustomWriter::printXML($doc);
		}
		return true;
	}

	function exportArticle(&$journal, &$issue, &$section, &$article, $outputFile = null) {
		$this->import('NativeExportDom');
		$doc =& XMLCustomWriter::createDocument('article', NATIVE_DTD_ID, NATIVE_DTD_URL);
		$articleNode =& NativeExportDom::generateArticleDom($doc, $journal, $issue, $section, $article);
		XMLCustomWriter::appendChild($doc, $articleNode);

		if (!empty($outputFile)) {
			if (($h = fopen($outputFile, 'w'))===false) return false;
			fwrite($h, XMLCustomWriter::getXML($doc));
			fclose($h);
		} else {
			header("Content-Type: application/xml");
			header("Cache-Control: private");
			header("Content-Disposition: attachment; filename=\"article-" . $article->getId() . ".xml\"");
			XMLCustomWriter::printXML($doc);
		}
		return true;
	}

	function exportIssues(&$journal, &$issues, $outputFile = null) {
		$this->import('NativeExportDom');
		$doc =& XMLCustomWriter::createDocument('issues', NATIVE_DTD_ID, NATIVE_DTD_URL);
		$issuesNode =& XMLCustomWriter::createElement($doc, 'issues');
		XMLCustomWriter::appendChild($doc, $issuesNode);

		foreach ($issues as $issue) {
			$issueNode =& NativeExportDom::generateIssueDom($doc, $journal, $issue);
			XMLCustomWriter::appendChild($issuesNode, $issueNode);
		}

		if (!empty($outputFile)) {
			if (($h = fopen($outputFile, 'w'))===false) return false;
			fwrite($h, XMLCustomWriter::getXML($doc));
			fclose($h);
		} else {
			header("Content-Type: application/xml");
			header("Cache-Control: private");
			header("Content-Disposition: attachment; filename=\"issues.xml\"");
			XMLCustomWriter::printXML($doc);
		}
		return true;
	}

	function exportArticles(&$results, $outputFile = null) {
		$this->import('NativeExportDom');
		$doc =& XMLCustomWriter::createDocument('articles', NATIVE_DTD_ID, NATIVE_DTD_URL);
		$articlesNode =& XMLCustomWriter::createElement($doc, 'articles');
		XMLCustomWriter::appendChild($doc, $articlesNode);

		foreach ($results as $result) {
			$article =& $result['publishedArticle'];
			$section =& $result['section'];
			$issue =& $result['issue'];
			$journal =& $result['journal'];
			$articleNode =& NativeExportDom::generateArticleDom($doc, $journal, $issue, $section, $article);
			XMLCustomWriter::appendChild($articlesNode, $articleNode);
		}

		if (!empty($outputFile)) {
			if (($h = fopen($outputFile, 'w'))===false) return false;
			fwrite($h, XMLCustomWriter::getXML($doc));
			fclose($h);
		} else {
			header("Content-Type: application/xml");
			header("Cache-Control: private");
			header("Content-Disposition: attachment; filename=\"articles.xml\"");
			XMLCustomWriter::printXML($doc);
		}
		return true;
	}

	function &getDocument($fileName) {
		$parser = new XMLParser();
		$returner =& $parser->parse($fileName);
		return $returner;
	}

	function getRootNodeName(&$doc) {
		return $doc->name;
	}

	function handleImport(&$context, &$doc, &$errors, &$issues, &$articles, $isCommandLine) {
		$errors = array();
		$issues = array();
		$articles = array();

		$user =& $context['user'];
		$journal =& $context['journal'];

		$rootNodeName = $this->getRootNodeName($doc);

		$this->import('NativeImportDom');

		switch ($rootNodeName) {
			case 'issues':
				return NativeImportDom::importIssues($journal, $doc->children, $issues, $errors, $user, $isCommandLine);
				break;
			case 'issue':
				$dependentItems = array();
				$result = NativeImportDom::importIssue($journal, $doc, $issue, $errors, $user, $isCommandLine, $dependentItems);
				if ($result) $issues = array($issue);
				return $result;
				break;
			case 'articles':
				$section =& $context['section'];
				$issue =& $context['issue'];
				return NativeImportDom::importArticles($journal, $doc->children, $issue, $section, $articles, $errors, $user, $isCommandLine);
				break;
			case 'article':
				$section =& $context['section'];
				$issue =& $context['issue'];
				$result = NativeImportDom::importArticle($journal, $doc, $issue, $section, $article, $errors, $user, $isCommandLine);
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

		AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON);

		$journalDao = DAORegistry::getDAO('JournalDAO');
		$issueDao = DAORegistry::getDAO('IssueDAO');
		$sectionDao = DAORegistry::getDAO('SectionDAO');
		$userDao = DAORegistry::getDAO('UserDAO');
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');

		$journal = $journalDao->getByPath($journalPath);

		if (!$journal) {
			if ($journalPath != '') {
				echo __('plugins.importexport.native.cliError') . "\n";
				echo __('plugins.importexport.native.error.unknownJournal', array('journalPath' => $journalPath)) . "\n\n";
			}
			$this->usage($scriptName);
			return;
		}

		$this->import('NativeImportDom');
		if ($xmlFile && NativeImportDom::isRelativePath($xmlFile)) {
			$xmlFile = PWD . '/' . $xmlFile;
		}

		switch ($command) {
			case 'import':
				$userName = array_shift($args);
				$user =& $userDao->getByUsername($userName);

				if (!$user) {
					if ($userName != '') {
						echo __('plugins.importexport.native.cliError') . "\n";
						echo __('plugins.importexport.native.error.unknownUser', array('userName' => $userName)) . "\n\n";
					}
					$this->usage($scriptName);
					return;
				}

				$doc =& $this->getDocument($xmlFile);

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
						$issue = $issueDao->getByBestId(($issueId = array_shift($args)), $journal->getId());
						if (!$issue) {
							echo __('plugins.importexport.native.cliError') . "\n";
							echo __('plugins.importexport.native.export.error.issueNotFound', array('issueId' => $issueId)) . "\n\n";
							return;
						}

						$context['issue'] =& $issue;

						switch (array_shift($args)) {
							case 'section_id':
								$section = $sectionDao->getById(($sectionIdentifier = array_shift($args)));
								break;
							case 'section_name':
								$section = $sectionDao->getByTitle(($sectionIdentifier = array_shift($args)), $journal->getId());
								break;
							case 'section_abbrev':
								$section = $sectionDao->getByAbbrev(($sectionIdentifier = array_shift($args)), $journal->getId());
								break;
							default:
								return $this->usage($scriptName);
						}

						if (!$section) {
							echo __('plugins.importexport.native.cliError') . "\n";
							echo __('plugins.importexport.native.export.error.sectionNotFound', array('sectionIdentifier' => $sectionIdentifier)) . "\n\n";
							return;
						}
						$context['section'] =& $section;
				}

				$result = $this->handleImport($context, $doc, $errors, $issues, $articles, true);
				if ($result) {
					echo __('plugins.importexport.native.import.success.description') . "\n\n";
					if (!empty($issues)) echo __('issue.issues') . ":\n";
					foreach ($issues as $issue) {
						echo "\t" . $issue->getIssueIdentification() . "\n";
					}

					if (!empty($articles)) echo __('article.articles') . ":\n";
					foreach ($articles as $article) {
						echo "\t" . $article->getLocalizedTitle() . "\n";
					}
				} else {
					$errorsTranslated = array();
					foreach ($errors as $error) {
						$errorsTranslated[] = __($error[0], $error[1]);
					}
					echo __('plugins.importexport.native.cliError') . "\n";
					foreach ($errorsTranslated as $errorTranslated) {
						echo "\t" . $errorTranslated . "\n";
					}
				}
				return;
				break;
			case 'export':
				if ($xmlFile != '') switch (array_shift($args)) {
					case 'article':
						$articleId = array_shift($args);
						$publishedArticle =& $publishedArticleDao->getPublishedArticleByBestArticleId($journal->getId(), $articleId);
						if ($publishedArticle == null) {
							echo __('plugins.importexport.native.cliError') . "\n";
							echo __('plugins.importexport.native.export.error.articleNotFound', array('articleId' => $articleId)) . "\n\n";
							return;
						}
						$issue = $issueDao->getById($publishedArticle->getIssueId(), $journal->getId());

						$sectionDao = DAORegistry::getDAO('SectionDAO');
						$section = $sectionDao->getById($publishedArticle->getSectionId());

						if (!$this->exportArticle($journal, $issue, $section, $publishedArticle, $xmlFile)) {
							echo __('plugins.importexport.native.cliError') . "\n";
							echo __('plugins.importexport.native.export.error.couldNotWrite', array('fileName' => $xmlFile)) . "\n\n";
						}
						return;
					case 'articles':
						$articleSearch = new ArticleSearch();
						$results = $articleSearch->formatResults($args);
						if (!$this->exportArticles($results, $xmlFile)) {
							echo __('plugins.importexport.native.cliError') . "\n";
							echo __('plugins.importexport.native.export.error.couldNotWrite', array('fileName' => $xmlFile)) . "\n\n";
						}
						return;
					case 'issue':
						$issueId = array_shift($args);
						$issue = $issueDao->getByBestId($issueId, $journal->getId());
						if ($issue == null) {
							echo __('plugins.importexport.native.cliError') . "\n";
							echo __('plugins.importexport.native.export.error.issueNotFound', array('issueId' => $issueId)) . "\n\n";
							return;
						}
						if (!$this->exportIssue($journal, $issue, $xmlFile)) {
							echo __('plugins.importexport.native.cliError') . "\n";
							echo __('plugins.importexport.native.export.error.couldNotWrite', array('fileName' => $xmlFile)) . "\n\n";
						}
						return;
					case 'issues':
						$issues = array();
						while (($issueId = array_shift($args))!==null) {
							$issue = $issueDao->getByBestId($issueId, $journal->getId());
							if ($issue == null) {
								echo __('plugins.importexport.native.cliError') . "\n";
								echo __('plugins.importexport.native.export.error.issueNotFound', array('issueId' => $issueId)) . "\n\n";
								return;
							}
							$issues[] =& $issue;
						}
						if (!$this->exportIssues($journal, $issues, $xmlFile)) {
							echo __('plugins.importexport.native.cliError') . "\n";
							echo __('plugins.importexport.native.export.error.couldNotWrite', array('fileName' => $xmlFile)) . "\n\n";
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
		echo __('plugins.importexport.native.cliUsage', array(
			'scriptName' => $scriptName,
			'pluginName' => $this->getName()
		)) . "\n";
	}
}

?>
