<?php

/**
 * @file plugins/generic/htmlArticleGalley/HtmlArticleGalleyPlugin.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class HtmlArticleGalleyPlugin
 * @ingroup plugins_generic_htmlArticleGalley
 *
 * @brief Class for HtmlArticleGalley plugin
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class HtmlArticleGalleyPlugin extends GenericPlugin {
	/**
	 * @see Plugin::register()
	 */
	function register($category, $path, $mainContextId = null) {
		if (!parent::register($category, $path, $mainContextId)) return false;
		if ($this->getEnabled($mainContextId)) {
			HookRegistry::register('ArticleHandler::view::galley', array($this, 'articleViewCallback'), HOOK_SEQUENCE_LATE);
			HookRegistry::register('ArticleHandler::download', array($this, 'articleDownloadCallback'), HOOK_SEQUENCE_LATE);
		}
		return true;
	}

	/**
	 * Install default settings on journal creation.
	 * @return string
	 */
	function getContextSpecificPluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * Get the display name of this plugin.
	 * @return String
	 */
	function getDisplayName() {
		return __('plugins.generic.htmlArticleGalley.displayName');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return __('plugins.generic.htmlArticleGalley.description');
	}

	/**
	 * Present the article wrapper page.
	 * @param string $hookName
	 * @param array $args
	 */
	function articleViewCallback($hookName, $args) {
		$request =& $args[0];
		$issue =& $args[1];
		$galley =& $args[2];
		$article =& $args[3];

		if (!$galley) {
			return false;
		}

		$submissionFile = $galley->getFile();
		if ($submissionFile->getData('mimetype') === 'text/html') {
			foreach ($article->getData('publications') as $publication) {
				if ($publication->getId() === $galley->getData('publicationId')) {
					$galleyPublication = $publication;
					break;
				}
			}
			$templateMgr = TemplateManager::getManager($request);
			$templateMgr->assign(array(
				'issue' => $issue,
				'article' => $article,
				'galley' => $galley,
				'isLatestPublication' => $article->getData('currentPublicationId') === $galley->getData('publicationId'),
				'galleyPublication' => $galleyPublication,
			));
			$templateMgr->display($this->getTemplateResource('display.tpl'));

			return true;
		}

		return false;
	}

	/**
	 * Present rewritten article HTML.
	 * @param string $hookName
	 * @param array $args
	 */
	function articleDownloadCallback($hookName, $args) {
		$article =& $args[0];
		$galley =& $args[1];
		$fileId =& $args[2];
		$request = Application::get()->getRequest();

		if (!$galley) {
			return false;
		}

		$submissionFile = $galley->getFile();
		if ($galley->getData('submissionFileId') == $fileId && $submissionFile->getData('mimetype') === 'text/html' && $galley->getData('submissionFileId') == $submissionFile->getId()) {
			if (!HookRegistry::call('HtmlArticleGalleyPlugin::articleDownload', array($article,  &$galley, &$fileId))) {
				echo $this->_getHTMLContents($request, $galley);
				$returner = true;
				HookRegistry::call('HtmlArticleGalleyPlugin::articleDownloadFinished', array(&$returner));
			}
			return true;
		}

		return false;
	}

	/**
	 * Return string containing the contents of the HTML file.
	 * This function performs any necessary filtering, like image URL replacement.
	 * @param $request PKPRequest
	 * @param $galley ArticleGalley
	 * @return string
	 */
	protected function _getHTMLContents($request, $galley) {
		$submissionFile = $galley->getFile();
		$submissionId = $submissionFile->getData('submissionId');
		$contents = Services::get('file')->fs->read($submissionFile->getData('path'));

		// Replace media file references
		import('lib.pkp.classes.submission.SubmissionFile'); // Constants
		$embeddableFilesIterator = Services::get('submissionFile')->getMany([
			'assocTypes' => [ASSOC_TYPE_SUBMISSION_FILE],
			'assocIds' => [$submissionFile->getId()],
			'fileStages' => [SUBMISSION_FILE_DEPENDENT],
			'includeDependentFiles' => true,
		]);
		$embeddableFiles = iterator_to_array($embeddableFilesIterator);

		$referredArticle = null;
		$submissionDao = DAORegistry::getDAO('SubmissionDAO');
		foreach ($embeddableFiles as $embeddableFile) {
			$params = array();

			if ($embeddableFile->getData('mimetype') == 'text/plain' || $embeddableFile->getData('mimetype') == 'text/css') {
				$params['inline'] ='true';
			}

			// Ensure that the $referredArticle object refers to the article we want
			if (!$referredArticle || $referredArticle->getId() != $submissionId) {
				$referredArticle = $submissionDao->getById($submissionId);
			}
			$fileUrl = $request->url(null, 'article', 'download', array($referredArticle->getBestId(), $galley->getBestGalleyId(), $embeddableFile->getId()), $params);
			$pattern = preg_quote(rawurlencode($embeddableFile->getLocalizedData('name')));

			$contents = preg_replace(
				'/([Ss][Rr][Cc]|[Hh][Rr][Ee][Ff]|[Dd][Aa][Tt][Aa])\s*=\s*"([^"]*' . $pattern . ')"/',
				'\1="' . $fileUrl . '"',
				$contents
			);
			if ($contents === null) error_log('PREG error in ' . __FILE__ . ' line ' . __LINE__ . ': ' . preg_last_error());

			// Replacement for Flowplayer
			$contents = preg_replace(
				'/[Uu][Rr][Ll]\s*\:\s*\'(' . $pattern . ')\'/',
				'url:\'' . $fileUrl . '\'',
				$contents
			);
			if ($contents === null) error_log('PREG error in ' . __FILE__ . ' line ' . __LINE__ . ': ' . preg_last_error());

			// Replacement for other players (ested with odeo; yahoo and google player won't work w/ OJS URLs, might work for others)
			$contents = preg_replace(
				'/[Uu][Rr][Ll]=([^"]*' . $pattern . ')/',
				'url=' . $fileUrl ,
				$contents
			);
			if ($contents === null) error_log('PREG error in ' . __FILE__ . ' line ' . __LINE__ . ': ' . preg_last_error());
		}

		// Perform replacement for ojs://... URLs
		$contents = preg_replace_callback(
			'/(<[^<>]*")[Oo][Jj][Ss]:\/\/([^"]+)("[^<>]*>)/',
			array($this, '_handleOjsUrl'),
			$contents
		);
		if ($contents === null) error_log('PREG error in ' . __FILE__ . ' line ' . __LINE__ . ': ' . preg_last_error());

		$templateMgr = TemplateManager::getManager($request);
		$contents = $templateMgr->loadHtmlGalleyStyles($contents, $embeddableFiles);

		// Perform variable replacement for journal, issue, site info
		$issueDao = DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
		$issue = $issueDao->getBySubmissionId($submissionId);

		$journal = $request->getJournal();
		$site = $request->getSite();

		$paramArray = array(
			'issueTitle' => $issue?$issue->getIssueIdentification():__('editor.article.scheduleForPublication.toBeAssigned'),
			'journalTitle' => $journal->getLocalizedName(),
			'siteTitle' => $site->getLocalizedTitle(),
			'currentUrl' => $request->getRequestUrl()
		);

		foreach ($paramArray as $key => $value) {
			$contents = str_replace('{$' . $key . '}', $value, $contents);
		}

		return $contents;
	}

	function _handleOjsUrl($matchArray) {
		$request = Application::get()->getRequest();
		$url = $matchArray[2];
		$anchor = null;
		if (($i = strpos($url, '#')) !== false) {
			$anchor = substr($url, $i+1);
			$url = substr($url, 0, $i);
		}
		$urlParts = explode('/', $url);
		if (isset($urlParts[0])) switch(strtolower_codesafe($urlParts[0])) {
			case 'journal':
				$url = $request->url(
				isset($urlParts[1]) ?
				$urlParts[1] :
				$request->getRequestedJournalPath(),
				null,
				null,
				null,
				null,
				$anchor
				);
				break;
			case 'article':
				if (isset($urlParts[1])) {
					$url = $request->url(
							null,
							'article',
							'view',
							$urlParts[1],
							null,
							$anchor
					);
				}
				break;
			case 'issue':
				if (isset($urlParts[1])) {
					$url = $request->url(
							null,
							'issue',
							'view',
							$urlParts[1],
							null,
							$anchor
					);
				} else {
					$url = $request->url(
							null,
							'issue',
							'current',
							null,
							null,
							$anchor
					);
				}
				break;
			case 'sitepublic':
				array_shift($urlParts);
				import ('classes.file.PublicFileManager');
				$publicFileManager = new PublicFileManager();
				$url = $request->getBaseUrl() . '/' . $publicFileManager->getSiteFilesPath() . '/' . implode('/', $urlParts) . ($anchor?'#' . $anchor:'');
				break;
			case 'public':
				array_shift($urlParts);
				$journal = $request->getJournal();
				import ('classes.file.PublicFileManager');
				$publicFileManager = new PublicFileManager();
				$url = $request->getBaseUrl() . '/' . $publicFileManager->getContextFilesPath($journal->getId()) . '/' . implode('/', $urlParts) . ($anchor?'#' . $anchor:'');
				break;
		}
		return $matchArray[1] . $url . $matchArray[3];
	}
}
