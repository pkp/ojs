<?php

/**
 * @file plugins/generic/booksForReview/BooksForReviewPlugin.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class BookForReviewPlugin
 * @ingroup plugins_generic_booksForReview
 *
 * @brief Books for review plugin class
 */

import('lib.pkp.classes.plugins.GenericPlugin');

define('BFR_MODE_FULL',		0x01);
define('BFR_MODE_METADATA',	0x02);


class BooksForReviewPlugin extends GenericPlugin {

	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		$this->addLocaleData();
		if ($success && $this->getEnabled()) {
			$this->import('classes.BookForReviewDAO');
			$this->import('classes.BookForReviewAuthorDAO');

			// PHP4 Requires explicit instantiation-by-reference
			if (checkPhpVersion('5.0.0')) {
				$bfrAuthorDao = new BookForReviewAuthorDAO($this->getName());
			} else {
				$bfrAuthorDao =& new BookForReviewAuthorDAO($this->getName());
			}
			$returner =& DAORegistry::registerDAO('BookForReviewAuthorDAO', $bfrAuthorDao);

			// PHP4 Requires explicit instantiation-by-reference
			if (checkPhpVersion('5.0.0')) {
				$bfrDao = new BookForReviewDAO($this->getName());
			} else {
				$bfrDao =& new BookForReviewDAO($this->getName());
			}
			$returner =& DAORegistry::registerDAO('BookForReviewDAO', $bfrDao);

			$journal =& Request::getJournal();
			if ($journal) {
				$mode = $this->getSetting($journal->getId(), 'mode');
				$coverPageIssue = $this->getSetting($journal->getId(), 'coverPageIssue');
				$coverPageAbstract = $this->getSetting($journal->getId(), 'coverPageAbstract');
			}

			// Handler for editor books for review pages
			HookRegistry::register('LoadHandler', array($this, 'setupEditorHandler'));

			// Editor link to books for review pages
			HookRegistry::register('Templates::Editor::Index::AdditionalItems', array($this, 'displayEditorHomeLink'));

			// Editor link to book for review metadata in submission view
			HookRegistry::register('Templates::Submission::Metadata::Metadata::AdditionalEditItems', array($this, 'displayEditorMetadataLink'));

			// Append book metadata to book review article
			HookRegistry::register('Templates::Article::Header::Metadata', array($this, 'displayBookMetadata'));

			// Enable TinyMCE for book for review text fields
			HookRegistry::register('TinyMCEPlugin::getEnableFields', array($this, 'enableTinyMCE'));

			// Ensure book for review user assignments are transferred when merging users
			HookRegistry::register('UserAction::mergeUsers', array($this, 'mergeBooksForReviewAuthors'));

			// If using book for review cover page as article cover page
			// then include cover page handlers for issue toc and article abstract views
			if ($coverPageIssue) {
				HookRegistry::register('Templates::Issue::Issue::ArticleCoverImage', array($this, 'displayArticleCoverPageIssue'));
			}

			if ($coverPageAbstract) {
				HookRegistry::register('Templates::Article::Article::ArticleCoverImage', array($this, 'displayArticleCoverPageAbstract'));
			}

			// If publishing books available for review and managing book reviewers
			// then include additional links, pages, and handlers
			if ($mode == BFR_MODE_FULL) {
				// Handler for public books for review pages
				HookRegistry::register('LoadHandler', array($this, 'setupPublicHandler'));

				// Navigation bar link to books for review page
				HookRegistry::register('Templates::Common::Header::Navbar::CurrentJournal', array($this, 'displayHeaderLink'));

				// Handler for author books for review pages
				HookRegistry::register('LoadHandler', array($this, 'setupAuthorHandler'));

				// Display author's books for review during submission
				HookRegistry::register('Author::SubmitHandler::saveSubmit', array($this, 'saveSubmitHandler'));
				HookRegistry::register('Templates::Author::Submit::Step5::AdditionalItems', array($this, 'displayAuthorBooksForReview'));

				// Author link to books for review pages
				HookRegistry::register('Templates::Author::Index::AdditionalItems', array($this, 'displayAuthorHomeLink'));
			}
		}
		return $success;
	}

	function getDisplayName() {
		return __('plugins.generic.booksForReview.displayName');
	}

	function getDescription() {
		return __('plugins.generic.booksForReview.description');
	}

	/**
	 * Get the filename of the ADODB schema for this plugin.
	 */
	function getInstallSchemaFile() {
		return $this->getPluginPath() . '/xml/schema.xml';
	}

	/**
	 * Get the filename of the email keys for this plugin.
	 */
	function getInstallEmailTemplatesFile() {
		return $this->getPluginPath() . '/xml/emailTemplates.xml';
	}

	/**
	 * Get the filename of the email locale data for this plugin.
	 */
	function getInstallEmailTemplateDataFile() {
		return $this->getPluginPath() . '/locale/{$installedLocale}/emailTemplates.xml';
	}

	/**
	 * Get the template path for this plugin.
	 */
	function getTemplatePath() {
		return parent::getTemplatePath() . 'templates/';
	}

	/**
	 * Get the handler path for this plugin.
	 */
	function getHandlerPath() {
		return $this->getPluginPath() . '/pages/';
	}

	/**
	 * Get the stylesheet for this plugin.
	 */
	function getStyleSheet() {
		return $this->getPluginPath() . '/styles/booksForReview.css';
	}

	/**
	 * Set the page's breadcrumbs, given the plugin's tree of items
	 * to append.
	 * @param $subclass boolean
	 */
	function setBreadcrumbs($isSubclass = false) {
		$templateMgr =& TemplateManager::getManager();
		$pageCrumbs = array(
			array(
				Request::url(null, 'user'),
				'navigation.user'
			),
			array(
				Request::url(null, 'manager'),
				'user.role.manager'
			)
		);
		if ($isSubclass) $pageCrumbs[] = array(
			Request::url(null, 'manager', 'plugin', array('generic', $this->getName(), 'booksForReview')),
			$this->getDisplayName(),
			true
		);

		$templateMgr->assign('pageHierarchy', $pageCrumbs);
	}

	/**
	 * Allow author to specify book for review during article submission.
	 */
	function saveSubmitHandler($hookName, $params) {
		$article =& $params[1];
		$journal =& Request::getJournal();
		$user =& Request::getUser();

		if ($journal && $user) {
			$journalId = $journal->getId();
			$userId = $user->getId();
			$bookId = Request::getUserVar('bookForReviewId') == null ? null : (int) Request::getUserVar('bookForReviewId');

			if ($bookId) {
				$bfrDao =& DAORegistry::getDAO('BookForReviewDAO');

				// Ensure book for review is for this journal
				if ($bfrDao->getBookForReviewJournalId($bookId) == $journalId) {
					$book =& $bfrDao->getBookForReview($bookId);
					$authorId = $book->getUserId();

					// Ensure book for review is assigned to author
					if ($authorId == $userId) {
						$status = $book->getStatus();
						$this->import('classes.BookForReview');

						// Ensure book for review is assigned or mailed
						if ($status == BFR_STATUS_ASSIGNED || $status == BFR_STATUS_MAILED) {
							$book->setStatus(BFR_STATUS_SUBMITTED);
							$book->setDateSubmitted(date('Y-m-d H:i:s', time()));
							$book->setArticleId($article->getId());
							$bfrDao->updateObject($book);
						}
					}
				}
			}
		}
	}

	/**
	 * Enable editor book for review management.
	 */
	function setupEditorHandler($hookName, $params) {
		$page =& $params[0];

		if ($page == 'editor') {
			$op =& $params[1];

			if ($op) {
				$editorPages = array(
					'createBookForReview',
					'editBookForReview',
					'updateBookForReview',
					'deleteBookForReview',
					'booksForReview',
					'booksForReviewSettings',
					'selectBookForReviewAuthor',
					'selectBookForReviewSubmission',
					'assignBookForReviewAuthor',
					'assignBookForReviewSubmission',
					'denyBookForReviewAuthor',
					'notifyBookForReviewMailed',
					'removeBookForReviewAuthor',
					'removeBookForReviewCoverPage'
				);

				if (in_array($op, $editorPages)) {
					define('HANDLER_CLASS', 'BooksForReviewEditorHandler');
					define('BOOKS_FOR_REVIEW_PLUGIN_NAME', $this->getName());
					AppLocale::requireComponents(LOCALE_COMPONENT_APPLICATION_COMMON, LOCALE_COMPONENT_PKP_USER, LOCALE_COMPONENT_OJS_EDITOR);
					$handlerFile =& $params[2];
					$handlerFile = $this->getHandlerPath() . 'BooksForReviewEditorHandler.inc.php';
				}
			}
		}
	}

	/**
	 * Enable author book for review management.
	 */
	function setupAuthorHandler($hookName, $params) {
		$page =& $params[0];
		if ($page == 'author') {
			$op =& $params[1];

			if ($op) {
				$authorPages = array(
					'booksForReview',
					'requestBookForReview'
				);

				if (in_array($op, $authorPages)) {
					define('HANDLER_CLASS', 'BooksForReviewAuthorHandler');
					define('BOOKS_FOR_REVIEW_PLUGIN_NAME', $this->getName());
					AppLocale::requireComponents(LOCALE_COMPONENT_APPLICATION_COMMON, LOCALE_COMPONENT_PKP_USER, LOCALE_COMPONENT_OJS_AUTHOR);
					$handlerFile =& $params[2];
					$handlerFile = $this->getHandlerPath() . 'BooksForReviewAuthorHandler.inc.php';
				}
			}
		}
	}

	/**
	 * Enable public book for review pages.
	 */
	function setupPublicHandler($hookName, $params) {
		$page =& $params[0];
		if ($page == 'booksForReview') {
			$op =& $params[1];

			if ($op) {
				$publicPages = array(
					'index',
					'viewBookForReview'
				);

				if (in_array($op, $publicPages)) {
					define('HANDLER_CLASS', 'BooksForReviewHandler');
					define('BOOKS_FOR_REVIEW_PLUGIN_NAME', $this->getName());
					AppLocale::requireComponents(LOCALE_COMPONENT_APPLICATION_COMMON);
					$handlerFile =& $params[2];
					$handlerFile = $this->getHandlerPath() . 'BooksForReviewHandler.inc.php';
				}
			}
		}
	}

	/**
	 * Enable TinyMCE support for book for review text fields.
	 */
	function enableTinyMCE($hookName, $params) {
		$fields =& $params[1];
		$page = Request::getRequestedPage();
		$op = Request::getRequestedOp();
		if ($page == 'editor' && ($op == 'createBookForReview' || $op == 'editBookForReview' || $op == 'updateBookForReview')) {
			$fields[] = 'description';
			$fields[] = 'notes';
		} elseif ($page == 'editor' && $op == 'booksForReviewSettings') {
			$fields[] = 'additionalInformation';
		}
		return false;
	}

	/**
	 * Transfer book for review user assignments when merging users.
	 */
	function mergeBooksForReviewAuthors($hookName, $params) {
		$oldUserId =& $params[0];
		$newUserId =& $params[1];

		$journal =& Request::getJournal();

		$bfrDao =& DAORegistry::getDAO('BookForReviewDAO');
		$oldUserBooksForReview =& $bfrDao->getBooksForReviewByAuthor($journal->getId(), $oldUserId);

		while ($bookForReview =& $oldUserBooksForReview->next()) {
			$bookForReview->setUserId($newUserId);
			$bfrDao->updateObject($bookForReview);
			unset($bookForReview);
		}

		return false;
	}

	/**
	 * Display an author's books for review during submission step 5.
	 */
	function displayAuthorBooksForReview($hookName, $params) {
		if ($this->getEnabled()) {
			$smarty =& $params[1];
			$output =& $params[2];

			$journal =& Request::getJournal();
			$user =& Request::getUser();

			if ($journal && $user) {
				$bfrDao =& DAORegistry::getDAO('BookForReviewDAO');
				$rangeInfo =& Handler::getRangeInfo('booksForReview');
				$booksForReview =& $bfrDao->getBooksForReviewAssignedByAuthor($journal->getId(), $user->getId(), $rangeInfo);

				if (!$booksForReview->wasEmpty()) {
					$smarty->assign('booksForReview', $booksForReview);
					$output .= $smarty->fetch($this->getTemplatePath() . 'author' . '/' . 'submissionBooksForReview.tpl');
				}
			}
		}
		return false;
	}

	/**
	 * Display book for review cover page in issue toc.
	 */
	function displayArticleCoverPageIssue($hookName, $params) {
		if ($this->getEnabled()) {
			$smarty =& $params[1];
			$output =& $params[2];

			$journal =& Request::getJournal();

			if ($journal) {
				$journalId = $journal->getId();
			} else {
				return false;
			}

			$article =& $smarty->get_template_vars('article');
			if ($article) {
				$articleId = $article->getId();
			} else {
				return false;
			}

			$bfrDao =& DAORegistry::getDAO('BookForReviewDAO');
			$book =& $bfrDao->getSubmittedBookForReviewByArticle($journalId, $articleId);

			if ($book) {
				$smarty->assign('book', $book);
				$output .= $smarty->fetch($this->getTemplatePath() . 'coverPageIssue.tpl');
			} else {
				return false;
			}
		}
		return false;
	}

	/**
	 * Display book for review cover page in article abstract.
	 */
	function displayArticleCoverPageAbstract($hookName, $params) {
		if ($this->getEnabled()) {
			$smarty =& $params[1];
			$output =& $params[2];

			$journal =& Request::getJournal();

			if ($journal) {
				$journalId = $journal->getId();
			} else {
				return false;
			}

			$article =& $smarty->get_template_vars('article');
			if ($article) {
				$articleId = $article->getId();
			} else {
				return false;
			}

			$bfrDao =& DAORegistry::getDAO('BookForReviewDAO');
			$book =& $bfrDao->getSubmittedBookForReviewByArticle($journalId, $articleId);

			if ($book) {
				import('classes.file.PublicFileManager');
				$publicFileManager = new PublicFileManager();
				$baseCoverPagePath = Request::getBaseUrl() . '/';
				$baseCoverPagePath .= $publicFileManager->getJournalFilesPath($journalId) . '/';
				$smarty->assign('baseCoverPagePath', $baseCoverPagePath);
				$smarty->assign('locale', AppLocale::getLocale());
				$smarty->assign('book', $book);
				$output .= $smarty->fetch($this->getTemplatePath() . 'coverPageAbstract.tpl');
			} else {
				return false;
			}
		}
		return false;
	}

	/**
	 * Append book for review metadata to article metadata.
	 */
	function displayBookMetadata($hookName, $params) {
		if ($this->getEnabled()) {
			$smarty =& $params[1];
			$output =& $params[2];

			$journal =& Request::getJournal();

			if ($journal) {
				$journalId = $journal->getId();
			} else {
				return false;
			}

			$article =& $smarty->get_template_vars('article');
			if ($article) {
				$articleId = $article->getId();
			} else {
				return false;
			}

			$bfrDao =& DAORegistry::getDAO('BookForReviewDAO');
			$book =& $bfrDao->getSubmittedBookForReviewByArticle($journalId, $articleId);

			if ($book) {
				$smarty->assign('book', $book);
				$citation = trim(trim($smarty->fetch($this->getTemplatePath() . 'citation.tpl')));
				$smarty->assign('citation', $citation);
				$output .= $smarty->fetch($this->getTemplatePath() . 'metadata.tpl');
			} else {
				return false;
			}
		}
		return false;
	}

	/**
	 * Display books for review link in header menu bar.
	 */
	function displayHeaderLink($hookName, $params) {
		if ($this->getEnabled()) {
			$smarty =& $params[1];
			$output =& $params[2];
			$templateMgr = TemplateManager::getManager();
			$output .= '<li><a href="' . $templateMgr->smartyUrl(array('page'=>'booksForReview'), $smarty) . '" target="_parent">' . $templateMgr->smartyTranslate(array('key'=>'plugins.generic.booksForReview.headerLink'), $smarty) . '</a></li>';
		}
		return false;
	}

	/**
	 * Display books for review management link in editor home.
	 */
	function displayEditorHomeLink($hookName, $params) {
		if ($this->getEnabled()) {
			$smarty =& $params[1];
			$output =& $params[2];
			$templateMgr = TemplateManager::getManager();
			$output .= '<h3>' . $templateMgr->smartyTranslate(array('key'=>'plugins.generic.booksForReview.editor.booksForReview'), $smarty) . '</h3><ul class="plain"><li>&#187; <a href="' . $templateMgr->smartyUrl(array('op'=>'booksForReview'), $smarty) . '">' . $templateMgr->smartyTranslate(array('key'=>'plugins.generic.booksForReview.editor.booksForReview'), $smarty) . '</a></li></ul>';
		}
		return false;
	}

	/**
	 * Display book for review metadata link in submission summary page.
	 */
	function displayEditorMetadataLink($hookName, $params) {
		if ($this->getEnabled()) {
			$smarty =& $params[1];
			$output =& $params[2];
			$submission =& $smarty->get_template_vars("submission");

			if ($submission) {
				$articleId = $submission->getId();
				$journal =& Request::getJournal();
				$journalId = $journal->getId();
				$bfrDao =& DAORegistry::getDAO('BookForReviewDAO');
				$bookId = $bfrDao->getSubmittedBookForReviewIdByArticle($journalId, $articleId);
				if ($bookId) {
					$templateMgr = TemplateManager::getManager();
					$output = '<p><a href="' . $templateMgr->smartyUrl(array('page'=>'editor', 'op'=>'editBookForReview', 'path'=>$bookId), $smarty) . '" class="action">' . $templateMgr->smartyTranslate(array('key'=>'plugins.generic.booksForReview.editor.editBookForReviewMetadata'), $smarty) . '</a></p>';
				}
			}
		}
		return false;
	}

	/**
	 * Display books for review links in author home.
	 */
	function displayAuthorHomeLink($hookName, $params) {
		if ($this->getEnabled()) {
			$smarty =& $params[1];
			$output =& $params[2];
			$templateMgr = TemplateManager::getManager();
			$output .= '<br /><div class="separator"></div><h3>' . $templateMgr->smartyTranslate(array('key'=>'plugins.generic.booksForReview.author.booksForReview'), $smarty) . '</h3><ul class="plain"><li>&#187; <a href="' . $templateMgr->smartyUrl(array('page'=>'author', 'op'=>'booksForReview'), $smarty) . '">' . $templateMgr->smartyTranslate(array('key'=>'plugins.generic.booksForReview.author.myBooksForReview'), $smarty) . '</a></li><li>&#187; <a href="' . $templateMgr->smartyUrl(array('page'=>'booksForReview'), $smarty) . '">' . $templateMgr->smartyTranslate(array('key'=>'plugins.generic.booksForReview.author.availableBooksForReview'), $smarty) . '</a></li></ul><br />';
		}
		return false;
	}
}
?>
