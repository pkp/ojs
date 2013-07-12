<?php

/**
 * @file plugins/articleGalleys/htmlArticleGalley/HtmlArticleGalleyPlugin.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class HtmlArticleGalleyPlugin
 * @ingroup plugins_articleGalleys_htmlArticleGalley
 *
 * @brief Class for HtmlArticleGalley plugin
 */

import('classes.plugins.ArticleGalleyPlugin');

class HtmlArticleGalleyPlugin extends ArticleGalleyPlugin {
	/**
	 * @see PKPPlugin::register()
	 */
	function register($category, $path) {
		if (parent::register($category, $path)) {
			if ($this->getEnabled()) {
				HookRegistry::register('TemplateManager::display', array($this, 'headerCallback'));
			}
			return true;
		}
		return false;
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
		return __('plugins.articleGalleys.htmlArticleGalley.displayName');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return __('plugins.articleGalleys.htmlArticleGalley.description');
	}

	/**
	 * Callback to insert CSS style sheets into the article header.
	 * @param string $hookName
	 * @param array $args
	 */
	function headerCallback($hookName, $args) {
		$templateMgr =& $args[0];
		$template =& $args[1];
		$request = Application::getRequest();
		$router = $request->getRouter();

		if ($template == 'article/article.tpl') {

			$galley = $templateMgr->get_template_vars('galley'); // set in ArticleHandler
			$fileId = $templateMgr->get_template_vars('fileId');

			if ($galley) {
				if (!$fileId) {
					$file = $galley->getFirstGalleyFile('text/html');
					if ($file) {
						$fileId = $file->getFileId();
					} else {
						assert(false); // No HTML file in this HTML Article galley?
						return false; // return from the callback and continue.
					}
				}

				$galleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
				$journal =& Request::getJournal();
				$styleFiles = $this->_getStyleFiles($galley, $fileId, $journal);
				if (is_array($styleFiles)) {
					foreach ($styleFiles as $file) {
						$styleUrl = $router->url($request, null, 'article', 'viewFile', array(
							$galley->getSubmissionId(),
							$galley->getBestGalleyId($journal),
							$file->getFileId()
						));

						$templateMgr->addStyleSheet($styleUrl);
					}
				}
			}
		}
	}

	/**
	 * @see ArticleGalleyPlugin::getArticleGalley
	 */
	function getArticleGalley(&$templateMgr, $request = null, $params) {
		$journal = $request->getJournal();
		$router = $request->getRouter();

		if (!$journal) return '';
		$fileId = (isset($params['fileId']) && is_numeric($params['fileId'])) ? (int) $fileId : null;

		$galley = $templateMgr->get_template_vars('galley'); // set in ArticleHandler
		$templateMgr->assign('htmlGalleyContents', $this->_getHTMLContents($galley, $fileId));
		return parent::getArticleGalley($templateMgr, $request, $params);
	}

	/**
	 * Return string containing the contents of the HTML file.
	 * This function performs any necessary filtering, like image URL replacement.
	 * @param $fileId int optional file id, otherwise the 'best' file will be chosen.
	 * @return string
	 */
	function _getHTMLContents($galley, $fileId = null) {
		import('classes.file.ArticleFileManager');
		$fileManager = new ArticleFileManager($galley->getSubmissionId());
		if (!$fileId) {
			$file = $galley->getFirstGalleyFile('text/html');
			if ($file) {
				$fileId = $file->getFileId();
			} else {
				assert(false); // No HTML file in this HTML Article galley?
				return false;
			}
		}
		$contents = $fileManager->readFile($fileId);

		$journal =& Request::getJournal();

		// Replace media file references
		$images = $this->_getImageFiles($galley, $fileId, $journal);

		foreach ($images as $image) {
			$imageUrl = Request::url(null, 'article', 'viewFile', array($galley->getSubmissionId(), $galley->getBestGalleyId($journal), $image->getFileId()));
			$pattern = preg_quote($image->getOriginalFileName());

			$contents = preg_replace(
					'/([Ss][Rr][Cc]|[Hh][Rr][Ee][Ff]|[Dd][Aa][Tt][Aa])\s*=\s*"([^"]*' . $pattern . ')"/',
					'\1="' . $imageUrl . '"',
					$contents
			);

			// Replacement for Flowplayer
			$contents = preg_replace(
					'/[Uu][Rr][Ll]\s*\:\s*\'(' . $pattern . ')\'/',
					'url:\'' . $imageUrl . '\'',
					$contents
			);

			// Replacement for other players (ested with odeo; yahoo and google player won't work w/ OJS URLs, might work for others)
			$contents = preg_replace(
					'/[Uu][Rr][Ll]=([^"]*' . $pattern . ')/',
					'url=' . $imageUrl ,
					$contents
			);

		}

		// Perform replacement for ojs://... URLs
		$contents = preg_replace_callback(
				'/(<[^<>]*")[Oo][Jj][Ss]:\/\/([^"]+)("[^<>]*>)/',
				array(&$this, '_handleOjsUrl'),
				$contents
		);

		// Perform variable replacement for journal, issue, site info
		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$issue =& $issueDao->getIssueByArticleId($galley->getSubmissionId());

		$journal =& Request::getJournal();
		$site =& Request::getSite();

		$paramArray = array(
				'issueTitle' => $issue?$issue->getIssueIdentification():__('editor.article.scheduleForPublication.toBeAssigned'),
				'journalTitle' => $journal->getLocalizedName(),
				'siteTitle' => $site->getLocalizedTitle(),
				'currentUrl' => Request::getRequestUrl()
		);

		foreach ($paramArray as $key => $value) {
			$contents = str_replace('{$' . $key . '}', $value, $contents);
		}

		return $contents;
	}

	function _handleOjsUrl($matchArray) {
		$url = $matchArray[2];
		$anchor = null;
		if (($i = strpos($url, '#')) !== false) {
			$anchor = substr($url, $i+1);
			$url = substr($url, 0, $i);
		}
		$urlParts = explode('/', $url);
		if (isset($urlParts[0])) switch(strtolower_codesafe($urlParts[0])) {
			case 'journal':
				$url = Request::url(
				isset($urlParts[1]) ?
				$urlParts[1] :
				Request::getRequestedJournalPath(),
				null,
				null,
				null,
				null,
				$anchor
				);
				break;
			case 'article':
				if (isset($urlParts[1])) {
					$url = Request::url(
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
					$url = Request::url(
							null,
							'issue',
							'view',
							$urlParts[1],
							null,
							$anchor
					);
				} else {
					$url = Request::url(
							null,
							'issue',
							'current',
							null,
							null,
							$anchor
					);
				}
				break;
			case 'suppfile':
				if (isset($urlParts[1]) && isset($urlParts[2])) {
					$url = Request::url(
							null,
							'article',
							'downloadSuppFile',
							array($urlParts[1], $urlParts[2]),
							null,
							$anchor
					);
				}
				break;
			case 'sitepublic':
				array_shift($urlParts);
				import ('classes.file.PublicFileManager');
				$publicFileManager = new PublicFileManager();
				$url = Request::getBaseUrl() . '/' . $publicFileManager->getSiteFilesPath() . '/' . implode('/', $urlParts) . ($anchor?'#' . $anchor:'');
				break;
			case 'public':
				array_shift($urlParts);
				$journal =& Request::getJournal();
				import ('classes.file.PublicFileManager');
				$publicFileManager = new PublicFileManager();
				$url = Request::getBaseUrl() . '/' . $publicFileManager->getJournalFilesPath($journal->getId()) . '/' . implode('/', $urlParts) . ($anchor?'#' . $anchor:'');
				break;
		}
		return $matchArray[1] . $url . $matchArray[3];
	}

	/**
	 * Retrieves the images associated with this HTML galley by looking at the submission_file genre.
	 * @return array SubmissionFiles
	 */
	function _getImageFiles($galley, $fileId, $journal) {
		$genreDao = DAORegistry::getDAO('GenreDAO');
		$imageGenres = $genreDao->getByCategory(GENRE_CATEGORY_ARTWORK, $journal->getId());
		$genreIds = array_keys($imageGenres->toAssociativeArray());

		$images = array();
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		$dependentFiles = $submissionFileDao->getLatestRevisionsByAssocId(ASSOC_TYPE_SUBMISSION_FILE, $fileId, $galley->getSubmissionId(), SUBMISSION_FILE_DEPENDENT);

		foreach ($dependentFiles as $file) {
			if (in_array($file->getGenreId(), $genreIds)) {
				$images[] = $file;
			}
		}

		return $images;
	}

	/**
	 * Retrieves the CSS/style files associated with this HTML galley by looking at the submission_file genre.
	 * @param ArticleGalley $galley
	 * @param Journal $journal
	 * @return array SubmissionFiles
	 */
	function _getStyleFiles($galley, $fileId, $journal) {
		$genreDao = DAORegistry::getDAO('GenreDAO');
		$styleGenre = $genreDao->getByType('STYLE', $journal->getId());

		$styleFiles = array();
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		$dependentFiles = $submissionFileDao->getLatestRevisionsByAssocId(ASSOC_TYPE_SUBMISSION_FILE, $fileId, $galley->getSubmissionId(), SUBMISSION_FILE_DEPENDENT);

		foreach ($dependentFiles as $file) {
			if ($file->getGenreId() == $styleGenre->getId()) {
				$styleFiles[] = $file;
			}
		}

		return $styleFiles;
	}
}

?>
