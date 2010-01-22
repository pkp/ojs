<?php

/**
 * @file ArticleHandler.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleHandler
 * @ingroup pages_article
 *
 * @brief Handle requests for article functions.
 *
 */

// $Id$


import('rt.ojs.RTDAO');
import('rt.ojs.JournalRT');

class ArticleHandler extends Handler {

	/**
	 * View Article.
	 */
	function view($args) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? $args[1] : 0;

		list($journal, $issue, $article) = ArticleHandler::validate($articleId, $galleyId);

		$rtDao = &DAORegistry::getDAO('RTDAO');
		$journalRt = $rtDao->getJournalRTByJournal($journal);

		$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');
		if ($journal->getSetting('enablePublicGalleyId')) {
			$galley =& $galleyDao->getGalleyByBestGalleyId($galleyId, $article->getArticleId());
		} else {
			$galley =& $galleyDao->getGalley($galleyId, $article->getArticleId());
		}

		if (!$journalRt->getEnabled()) {
			if (!$galley || $galley->isHtmlGalley()) return ArticleHandler::viewArticle($args);
			else if ($galley->isPdfGalley()) return ArticleHandler::viewPDFInterstitial($args, $galley);
			else if ($galley->isInlineable()) {
				import('file.ArticleFileManager');
				$articleFileManager = &new ArticleFileManager($article->getArticleId());
				return $articleFileManager->viewFile($galley->getFileId());
			} else return ArticleHandler::viewDownloadInterstitial($args, $galley);
		}

		if (!$article) {
			Request::redirect(null, Request::getRequestedPage());
			return;
		}

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign_by_ref('article', $article);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign_by_ref('galley', $galley);

		$templateMgr->display('article/view.tpl');
	}

	/**
	 * Article interstitial page before PDF is shown
	 */
	function viewPDFInterstitial($args, $galley = null) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? $args[1] : 0;
		list($journal, $issue, $article) = ArticleHandler::validate($articleId, $galleyId);

		if (!$galley) {
			$galleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');
			if ($journal->getSetting('enablePublicGalleyId')) {
				$galley =& $galleyDao->getGalleyByBestGalleyId($galleyId, $article->getArticleId());
			} else {
				$galley =& $galleyDao->getGalley($galleyId, $article->getArticleId());
			}
		}

		if (!$galley) Request::redirect(null, null, 'view', $articleId);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign_by_ref('galley', $galley);
		$templateMgr->assign_by_ref('article', $article);

		$templateMgr->display('article/pdfInterstitial.tpl');
	}

	/**
	 * Article interstitial page before a non-PDF, non-HTML galley is
	 * downloaded
	 */
	function viewDownloadInterstitial($args, $galley = null) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? $args[1] : 0;
		list($journal, $issue, $article) = ArticleHandler::validate($articleId, $galleyId);

		if (!$galley) {
			$galleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');
			if ($journal->getSetting('enablePublicGalleyId')) {
				$galley =& $galleyDao->getGalleyByBestGalleyId($galleyId, $article->getArticleId());
			} else {
				$galley =& $galleyDao->getGalley($galleyId, $article->getArticleId());
			}
		}

		if (!$galley) Request::redirect(null, null, 'view', $articleId);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign_by_ref('galley', $galley);
		$templateMgr->assign_by_ref('article', $article);

		$templateMgr->display('article/interstitial.tpl');
	}

	/**
	 * Article view
	 */
	function viewArticle($args) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? $args[1] : 0;

		list($journal, $issue, $article) = ArticleHandler::validate($articleId, $galleyId);

		$rtDao = &DAORegistry::getDAO('RTDAO');
		$journalRt = $rtDao->getJournalRTByJournal($journal);

		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		$section = &$sectionDao->getSection($article->getSectionId());

		if ($journalRt->getVersion()!=null && $journalRt->getDefineTerms()) {
			// Determine the "Define Terms" context ID.
			$version = $rtDao->getVersion($journalRt->getVersion(), $journalRt->getJournalId());
			if ($version) foreach ($version->getContexts() as $context) {
				if ($context->getDefineTerms()) {
					$defineTermsContextId = $context->getContextId();
					break;
				}
			}
		}

		$commentDao = &DAORegistry::getDAO('CommentDAO');
		$enableComments = $journal->getSetting('enableComments');

		if (($article->getEnableComments()) && ($enableComments == COMMENTS_AUTHENTICATED || $enableComments == COMMENTS_UNAUTHENTICATED || $enableComments == COMMENTS_ANONYMOUS)) {
			$comments = &$commentDao->getRootCommentsByArticleId($article->getArticleId());
		}

		$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');
		if ($journal->getSetting('enablePublicGalleyId')) {
			$galley =& $galleyDao->getGalleyByBestGalleyId($galleyId, $article->getArticleId());
		} else {
			$galley =& $galleyDao->getGalley($galleyId, $article->getArticleId());
		}

		$templateMgr = &TemplateManager::getManager();

		if (!$galley) {
			// Get the subscription status if displaying the abstract;
			// if access is open, we can display links to the full text.
			import('issue.IssueAction');

			// The issue may not exist, if this is an editorial user
			// and scheduling hasn't been completed yet for the article.
			if ($issue) {
				$templateMgr->assign('subscriptionRequired', IssueAction::subscriptionRequired($issue));
			}

			$templateMgr->assign('subscribedUser', IssueAction::subscribedUser($journal, isset($issue) ? $issue->getIssueId() : null, isset($article) ? $article->getArticleId() : null));
			$templateMgr->assign('subscribedDomain', IssueAction::subscribedDomain($journal, isset($issue) ? $issue->getIssueId() : null, isset($article) ? $article->getArticleId() : null));

			$templateMgr->assign('showGalleyLinks', $journal->getSetting('showGalleyLinks'));

			import('payment.ojs.OJSPaymentManager');
			$paymentManager =& OJSPaymentManager::getManager();
			if ( $paymentManager->onlyPdfEnabled() ) {
				$templateMgr->assign('restrictOnlyPdf', true);
			}
			if ( $paymentManager->purchaseArticleEnabled() ) {
				$templateMgr->assign('purchaseArticleEnabled', true);
			}

			// Article cover page.
			$locale = Locale::getLocale();
			if (isset($article) && $article->getFileName($locale) && $article->getShowCoverPage($locale) && !$article->getHideCoverPageAbstract($locale)) {
				import('file.PublicFileManager');
				$publicFileManager = &new PublicFileManager();
				$coverPagePath = Request::getBaseUrl() . '/';
				$coverPagePath .= $publicFileManager->getJournalFilesPath($journal->getJournalId()) . '/';
				$templateMgr->assign('coverPagePath', $coverPagePath);
				$templateMgr->assign('coverPageFileName', $article->getFileName($locale));
				$templateMgr->assign('width', $article->getWidth($locale));
				$templateMgr->assign('height', $article->getHeight($locale));
				$templateMgr->assign('coverPageAltText', $article->getCoverPageAltText($locale));
			}

			// Increment the published article's abstract views count
			if (!Request::isBot()) {
				$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
				$publishedArticleDao->incrementViewsByArticleId($article->getArticleId());
			}
		} else {
			if (!Request::isBot()) {
				// Increment the galley's views count
				$galleyDao->incrementViews($galley->getGalleyId());
			}

			// Use the article's CSS file, if set.
			if ($galley->isHTMLGalley() && $styleFile =& $galley->getStyleFile()) {
				$templateMgr->addStyleSheet(Request::url(null, 'article', 'viewFile', array(
					$article->getArticleId(),
					$galley->getBestGalleyId($journal),
					$styleFile->getFileId()
				)));
			}
		}

		// Add font sizer js and css if not already in header
		$additionalHeadData = $templateMgr->get_template_vars('additionalHeadData');
		if (strpos(strtolower($additionalHeadData), 'sizer.js') === false) {
			$additionalHeadData .= $templateMgr->fetch('common/sizer.tpl');
			$templateMgr->assign('additionalHeadData', $additionalHeadData);
		}

		$templateMgr->assign_by_ref('issue', $issue);
		$templateMgr->assign_by_ref('article', $article);
		$templateMgr->assign_by_ref('galley', $galley);
		$templateMgr->assign_by_ref('section', $section);
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('postingAllowed', (
			($article->getEnableComments()) && (
			$enableComments == COMMENTS_UNAUTHENTICATED ||
			(($enableComments == COMMENTS_AUTHENTICATED ||
			$enableComments == COMMENTS_ANONYMOUS) &&
			Validation::isLoggedIn()))
		));
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign('defineTermsContextId', isset($defineTermsContextId)?$defineTermsContextId:null);
		$templateMgr->assign('comments', isset($comments)?$comments:null);
		$templateMgr->display('article/article.tpl');
	}

	/**
	 * Article Reading tools
	 */
	function viewRST($args) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? $args[1] : 0;

		list($journal, $issue, $article) = ArticleHandler::validate($articleId, $galleyId);

		$rtDao = &DAORegistry::getDAO('RTDAO');
		$journalRt = $rtDao->getJournalRTByJournal($journal);

		// The RST needs to know whether this galley is HTML or not. Fetch the galley.
		$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');
		if ($journal->getSetting('enablePublicGalleyId')) {
			$galley =& $galleyDao->getGalleyByBestGalleyId($galleyId, $article->getArticleId());
		} else {
			$galley =& $galleyDao->getGalley($galleyId, $article->getArticleId());
		}

		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		$section = &$sectionDao->getSection($article->getSectionId());

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign_by_ref('issue', $issue);
		$templateMgr->assign_by_ref('article', $article);
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign_by_ref('galley', $galley);
		$templateMgr->assign_by_ref('journal', $journal);
		$templateMgr->assign_by_ref('section', $section);

		$templateMgr->assign('articleSearchByOptions', array(
			'' => 'search.allFields',
			ARTICLE_SEARCH_AUTHOR => 'search.author',
			ARTICLE_SEARCH_TITLE => 'article.title',
			ARTICLE_SEARCH_ABSTRACT => 'search.abstract',
			ARTICLE_SEARCH_INDEX_TERMS => 'search.indexTerms',
			ARTICLE_SEARCH_GALLEY_FILE => 'search.fullText'
		));

		// Bring in comment constants.
		$commentDao = &DAORegistry::getDAO('CommentDAO');

		$enableComments = $journal->getSetting('enableComments');
		$templateMgr->assign('postingAllowed', (
			$article->getEnableComments() &&
			$enableComments != COMMENTS_DISABLED
		));

		$templateMgr->assign('postingDisabled', (
			($enableComments == COMMENTS_AUTHENTICATED ||
			$enableComments == COMMENTS_ANONYMOUS) &&
			!Validation::isLoggedIn())
		);

		$templateMgr->assign_by_ref('journalRt', $journalRt);
		if ($journalRt->getEnabled()) {
			$version = $rtDao->getVersion($journalRt->getVersion(), $journalRt->getJournalId());
			if ($version) {
				$templateMgr->assign_by_ref('version', $version);
			}
		}

		$templateMgr->display('rt/rt.tpl');
	}

	/**
	 * View a file (inlines file).
	 * @param $args array ($articleId, $galleyId, $fileId [optional])
	 */
	function viewFile($args) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? $args[1] : 0;
		$fileId = isset($args[2]) ? (int) $args[2] : 0;

		list($journal, $issue, $article) = ArticleHandler::validate($articleId, $galleyId);

		$galleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');
		if ($journal->getSetting('enablePublicGalleyId')) {
			$galley =& $galleyDao->getGalleyByBestGalleyId($galleyId, $article->getArticleId());
		} else {
			$galley =& $galleyDao->getGalley($galleyId, $article->getArticleId());
		}

		if (!$galley) Request::redirect(null, null, 'view', $articleId);

		if (!$fileId) {
			$galleyDao->incrementViews($galley->getGalleyId());
			$fileId = $galley->getFileId();
		} else {
			if (!$galley->isDependentFile($fileId)) {
				Request::redirect(null, null, 'view', $articleId);
			}
		}

		if (!HookRegistry::call('ArticleHandler::viewFile', array(&$article, &$galley, &$fileId))) {
			import('submission.common.Action');
			Action::viewFile($article->getArticleId(), $fileId);
		}
	}

	/**
	 * Downloads the document
	 */
	function download($args) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? $args[1] : 0;
		list($journal, $issue, $article) = ArticleHandler::validate($articleId, $galleyId);

		$galleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');
		if ($journal->getSetting('enablePublicGalleyId')) {
			$galley =& $galleyDao->getGalleyByBestGalleyId($galleyId, $article->getArticleId());
		} else {
			$galley =& $galleyDao->getGalley($galleyId, $article->getArticleId());
		}
		if ($galley) $galleyDao->incrementViews($galley->getGalleyId());

		if ($article && $galley && !HookRegistry::call('ArticleHandler::downloadFile', array(&$article, &$galley))) {
			import('file.ArticleFileManager');
			$articleFileManager = &new ArticleFileManager($article->getArticleId());
			$articleFileManager->downloadFile($galley->getFileId());
		}
	}

	function downloadSuppFile($args) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$suppId = isset($args[1]) ? $args[1] : 0;
		list($journal, $issue, $article) = ArticleHandler::validate($articleId);

		$suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
		if ($journal->getSetting('enablePublicSuppFileId')) {
			$suppFile = &$suppFileDao->getSuppFileByBestSuppFileId($article->getArticleId(), $suppId);
		} else {
			$suppFile = &$suppFileDao->getSuppFile((int) $suppId, $article->getArticleId());
		}

		if ($article && $suppFile) {
			import('file.ArticleFileManager');
			$articleFileManager = &new ArticleFileManager($article->getArticleId());
			if ($suppFile->isInlineable()) {
				$articleFileManager->viewFile($suppFile->getFileId());
			} else {
				$articleFileManager->downloadFile($suppFile->getFileId());
			}
		}
	}

	/**
	 * Validation
	 */
	function validate($articleId, $galleyId = null) {
		parent::validate(true);

		import('issue.IssueAction');

		$journal =& Request::getJournal();
		$journalId = $journal->getJournalId();
		$article = $publishedArticle = $issue = null;
		$user =& Request::getUser();
		$userId = $user?$user->getUserId():0;

		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		if ($journal->getSetting('enablePublicArticleId')) {
			$publishedArticle =& $publishedArticleDao->getPublishedArticleByBestArticleId($journalId, $articleId);
		} else {
			$publishedArticle =& $publishedArticleDao->getPublishedArticleByArticleId((int) $articleId, $journalId);
		}

		$issueDao =& DAORegistry::getDAO('IssueDAO');
		if (isset($publishedArticle)) {
			$issue = &$issueDao->getIssueByArticleId($publishedArticle->getArticleId(), $journalId);
		} else {
			$articleDao =& DAORegistry::getDAO('ArticleDAO');
			$article =& $articleDao->getArticle((int) $articleId, $journalId);
		}

		// If this is an editorial user who can view unpublished/unscheduled
		// articles, bypass further validation. Likewise for its author.
		if (($article || $publishedArticle) && (($article && IssueAction::allowedPrePublicationAccess($journal, $article) || ($publishedArticle && IssueAction::allowedPrePublicationAccess($journal, $publishedArticle))))) {
			return array($journal, $issue, $publishedArticle?$publishedArticle:$article);
		}

		// Make sure the reader has rights to view the article/issue.
		if ($issue && $issue->getPublished()) {
			$subscriptionRequired = IssueAction::subscriptionRequired($issue);
			$isSubscribedDomain = IssueAction::subscribedDomain($journal, $issue->getIssueId(), $articleId);

			// Check if login is required for viewing.
			if (!$isSubscribedDomain && !Validation::isLoggedIn() && $journal->getSetting('restrictArticleAccess') && isset($galleyId) && $galleyId != 0) {
				Validation::redirectLogin();
			}

			// bypass all validation if subscription based on domain or ip is valid
			// or if the user is just requesting the abstract
			if ( (!$isSubscribedDomain && $subscriptionRequired) &&
			     (isset($galleyId) && $galleyId!=0) ) {

				// Subscription Access
				$subscribedUser = IssueAction::subscribedUser($journal, $issue->getIssueId(), $articleId);

				if (!(!$subscriptionRequired || $publishedArticle->getAccessStatus() || $subscribedUser)) {
					// if payment information is enabled,
					import('payment.ojs.OJSPaymentManager');
					$paymentManager =& OJSPaymentManager::getManager();

					if ( $paymentManager->purchaseArticleEnabled() || $paymentManager->membershipEnabled() ) {
						/* if only pdf files are being restricted, then approve all non-pdf galleys
						 * and continue checking if it is a pdf galley */
						if ( $paymentManager->onlyPdfEnabled() ) {
							$galleyDAO =& DAORegistry::getDAO('ArticleGalleyDAO');
							if ($journal->getSetting('enablePublicGalleyId')) {
								$galley =& $galleyDAO->getGalley($galleyId, $articleId);
							} else {
								$galley =& $galleyDAO->getGalleyByBestGalleyId($galleyId, $articleId);
							}
							if ( $galley && !$galley->isPdfGalley() ) {
								return array($journal, $issue, $publishedArticle);
							}
						}

						if (!Validation::isLoggedIn()) {
							Validation::redirectLogin("payment.loginRequired.forArticle");
						}

						/* if the article has been paid for then forget about everything else
						 * and just let them access the article */
						$completedPaymentDAO =& DAORegistry::getDAO('OJSCompletedPaymentDAO');
						if ( $completedPaymentDAO->hasPaidPerViewArticle($userId, $articleId)
							|| (!is_null($user->getDateEndMembership()) && strtotime($user->getDateEndMembership()) > time()) ) {
							return array($journal, $issue, $publishedArticle);
						} else {
							$queuedPayment =& $paymentManager->createQueuedPayment($journalId, PAYMENT_TYPE_PURCHASE_ARTICLE, $user->getUserId(), $articleId, $journal->getSetting('purchaseArticleFee'));
							$queuedPaymentId = $paymentManager->queuePayment($queuedPayment);

							$templateMgr =& TemplateManager::getManager();
							$paymentManager->displayPaymentForm($queuedPaymentId, $queuedPayment);
							exit;
						}
					}

					if (!isset($galleyId) || $galleyId) {
						if (!Validation::isLoggedIn()) {
							Validation::redirectLogin("reader.subscriptionRequiredLoginText");
						}
						Request::redirect(null, 'about', 'subscriptions');
					}
				}
			}
		} else {
			Request::redirect(null, 'index');
		}
		return array($journal, $issue, $publishedArticle);
	}

}

?>
