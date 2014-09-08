<?php

/**
 * @file plugins/viewableFiles/htmlArticleGalley/HtmlArticleGalleyPlugin.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class HtmlArticleGalleyPlugin
 * @ingroup plugins_viewableFiles_htmlArticleGalley
 *
 * @brief Class for HtmlArticleGalley plugin
 */

import('classes.plugins.ViewableFilePlugin');

class HtmlArticleGalleyPlugin extends ViewableFilePlugin {
	/**
	 * @see Plugin::register()
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
		return __('plugins.viewableFiles.htmlArticleGalley.displayName');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return __('plugins.viewableFiles.htmlArticleGalley.description');
	}

	/**
	 * Callback to insert CSS style sheets into the article header.
	 * @param string $hookName
	 * @param array $args
	 */
	function headerCallback($hookName, $args) {
		$templateMgr = $args[0];
		$template = $args[1];
		$request = Application::getRequest();
		$router = $request->getRouter();

		if ($template == 'article/article.tpl') {

			$galley = $templateMgr->get_template_vars('galley'); // set in ArticleHandler
			$fileId = $templateMgr->get_template_vars('fileId');

			if ($galley && $galley->getGalleyType() == $this->getName()) {
				if (!$fileId) {
					$file = $galley->getFirstGalleyFile('text/html');
					if ($file) {
						$fileId = $file->getFileId();
					} else {
						assert(false); // No HTML file in this HTML Article galley?
						return false; // return from the callback and continue.
					}
				}

				$journal = $request->getJournal();
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
	 * @see ViewableFilePlugin::displayArticleGalley
	 */
	function displayArticleGalley($templateMgr, $request, $params) {
		$journal = $request->getJournal();

		if (!$journal) return '';
		$fileId = (isset($params['fileId']) && is_numeric($params['fileId'])) ? (int) $params['fileId'] : null;

		$galley = $templateMgr->get_template_vars('galley'); // set in ArticleHandler
		$templateMgr->assign('htmlGalleyContents', $this->_getHTMLContents($request, $galley, $fileId));
		return parent::displayArticleGalley($templateMgr, $request, $params);
	}

	/**
	 * Return string containing the contents of the HTML file.
	 * This function performs any necessary filtering, like image URL replacement.
	 * @param $fileId int optional file id, otherwise the 'best' file will be chosen.
	 * @return string
	 */
	function _getHTMLContents($request, $galley, $fileId = null) {
		import('classes.file.ArticleFileManager');
		$fileManager = new ArticleFileManager($galley->getSubmissionId());
		if (!$fileId) {

			// Note: Some HTML file uploads may be stored with incorrect file_type settings
			// due to incorrect finfo or mime.magic entries.  As such, we examine the file extension
			// of the original file name for 'htm'. This will match .html, .htm, .xhtml, etc.
			// The file_type isn't important since the plugin includes the HTML content inline rather
			// than including a URL loaded in an iframe.
			$file = $galley->getFirstGalleyFile('htm');
			if ($file) {
				$fileId = $file->getFileId();
			} else {
				assert(false); // No HTML file in this HTML Article galley?
				return false;
			}
		}
		$contents = $fileManager->readFile($fileId);

		$journal = $request->getJournal();

		// Replace media file references
		$images = $this->_getImageFiles($galley, $fileId, $journal);

		foreach ($images as $image) {
			$imageUrl = $request->url(null, 'article', 'viewFile', array($galley->getSubmissionId(), $galley->getBestGalleyId($journal), $image->getFileId()));
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
		$issueDao = DAORegistry::getDAO('IssueDAO');
		$issue = $issueDao->getIssueByArticleId($galley->getSubmissionId());

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
		$request = Application::getRequest();
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
				$url = $request->getBaseUrl() . '/' . $publicFileManager->getJournalFilesPath($journal->getId()) . '/' . implode('/', $urlParts) . ($anchor?'#' . $anchor:'');
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
				if ($file->getFileType() != 'text/css' && preg_match('/\.css$/', $file->getOriginalFileName())) {
					$file->setFileType('text/css');
					$submissionFileDao->updateObject($file);
				}
				$styleFiles[] = $file;
			}
		}

		return $styleFiles;
	}
}

?>
