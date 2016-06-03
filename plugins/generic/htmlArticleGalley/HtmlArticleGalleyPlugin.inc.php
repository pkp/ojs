<?php

/**
 * @file plugins/generic/htmlArticleGalley/HtmlArticleGalleyPlugin.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
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
	function register($category, $path) {
		if (parent::register($category, $path)) {
			if ($this->getEnabled()) {
				HookRegistry::register('ArticleHandler::view::galley', array($this, 'articleCallback'));
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
		return __('plugins.generic.htmlArticleGalley.displayName');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return __('plugins.generic.htmlArticleGalley.description');
	}

	/**
	 * Callback to insert CSS style sheets into the article header.
	 * @param string $hookName
	 * @param array $args
	 */
	function articleCallback($hookName, $args) {
		$request =& $args[0];
		$issue =& $args[1];
		$galley =& $args[2];
		$article =& $args[3];

		$templateMgr = TemplateManager::getManager($request);
		if ($galley && $galley->getFileType() == 'text/html') {
			$templateMgr->assign(array(
				'pluginTemplatePath' => $this->getTemplatePath(),
				'pluginUrl' => $request->getBaseUrl() . '/' . $this->getPluginPath(),
				'galleyFile' => $galley->getFile(),
				'issue' => $issue,
				'article' => $article,
				'galley' => $galley,
				'htmlGalleyContents' => $this->_getHTMLContents($request, $galley),
			));
			$templateMgr->display($this->getTemplatePath() . '/display.tpl');

			return true;
		}

		return false;

	/** FIXME STYLESHEETS
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
		} */
	}

	/**
	 * Return string containing the contents of the HTML file.
	 * This function performs any necessary filtering, like image URL replacement.
	 * @param $request PKPRequest
	 * @param $galley ArticleGalley
	 * @return string
	 */
	function _getHTMLContents($request, $galley) {
		import('lib.pkp.classes.file.SubmissionFileManager');
		$fileManager = new SubmissionFileManager($request->getContext()->getId(), $galley->getSubmissionId());
		$contents = $fileManager->readFile($galley->getFile()->getFileId());
print_r($galley->getFile()->getFileId());

		$journal = $request->getJournal();

		// Replace media file references
		$images = array(); /** FIXME GET IMAGES $this->_getImageFiles($galley, $fileId, $journal); */

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
