<?php

/**
 * @file pages/article/ArticleHandler.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleHandler
 * @ingroup pages_article
 *
 * @brief Handle requests for article functions.
 *
 */


import('classes.rt.ojs.RTDAO');
import('classes.rt.ojs.JournalRT');
import('classes.handler.Handler');
import('classes.rt.ojs.SharingRT');

class ArticleHandler extends Handler {
	/** journal associated with the request **/
	var $journal;

	/** issue associated with the request **/
	var $issue;

	/** article associated with the request **/
	var $article;

	/** galley associated with the request **/
	var $galley;

	/**
	 * Constructor
	 * @param $request Request
	 */
	function ArticleHandler($request) {
		parent::Handler($request);
	}

	/**
	 * @see PKPHandler::authorize()
	 * @param $request PKPRequest
	 * @param $args array
	 * @param $roleAssignments array
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.ContextRequiredPolicy');
		$this->addPolicy(new ContextRequiredPolicy($request));

		import('classes.security.authorization.OjsJournalMustPublishPolicy');
		$this->addPolicy(new OjsJournalMustPublishPolicy($request));

		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * @see PKPHandler::initialize()
	 */
	function initialize($request, $args) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? $args[1] : 0;

		$this->journal = $request->getContext();
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		if ($this->journal->getSetting('enablePublicArticleId')) {
			$publishedArticle = $publishedArticleDao->getPublishedArticleByBestArticleId((int) $this->journal->getId(), $articleId, true);
		} else {
			$publishedArticle = $publishedArticleDao->getPublishedArticleByArticleId((int) $articleId, (int) $this->journal->getId(), true);
		}

		$issueDao = DAORegistry::getDAO('IssueDAO');
		if (isset($publishedArticle)) {
			$issue = $issueDao->getById($publishedArticle->getIssueId(), $publishedArticle->getJournalId(), true);
			$this->issue = $issue;
			$this->article = $publishedArticle;
		} else {
			$articleDao = DAORegistry::getDAO('ArticleDAO');
			$article = $articleDao->getById((int) $articleId, $this->journal->getId(), true);
			$this->article = $article;
		}

		$galleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
		if ($this->journal->getSetting('enablePublicGalleyId')) {
			$galley = $galleyDao->getGalleyByBestGalleyId($galleyId, $this->article->getId());
		} else {
			$galley = $galleyDao->getGalley($galleyId, $this->article->getId());
		}
		$this->galley = $galley;
	}

	/**
	 * View Article.
	 * @param $args array
	 * @param $request Request
	 */
	function view($args, $request) {
		$router = $request->getRouter();
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? $args[1] : 0;
		$fileId = isset($args[2]) ? $args[2] : 0;

		if ($this->userCanViewGalley($request, $articleId, $galleyId)) {
			$journal = $this->journal;
			$issue = $this->issue;
			$article = $this->article;
			$this->setupTemplate($request);

			$rtDao = DAORegistry::getDAO('RTDAO');
			$journalRt = $rtDao->getJournalRTByJournal($journal);

			$sectionDao = DAORegistry::getDAO('SectionDAO');
			$section = $sectionDao->getById($article->getSectionId(), $journal->getId(), true);

			$version = null;
			if ($journalRt->getVersion()!=null && $journalRt->getDefineTerms()) {
				// Determine the "Define Terms" context ID.
				$version = $rtDao->getVersion($journalRt->getVersion(), $journalRt->getJournalId(), true);
				if ($version) foreach ($version->getContexts() as $context) {
					if ($context->getDefineTerms()) {
						$defineTermsContextId = $context->getContextId();
						break;
					}
				}
			}

			$commentDao = DAORegistry::getDAO('CommentDAO');
			$enableComments = $journal->getSetting('enableComments');

			if (($article->getEnableComments()) && ($enableComments == COMMENTS_AUTHENTICATED || $enableComments == COMMENTS_UNAUTHENTICATED || $enableComments == COMMENTS_ANONYMOUS)) {
				$comments == $commentDao->getRootCommentsBySubmissionId($article->getId());
			}

			$galleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
			if ($journal->getSetting('enablePublicGalleyId')) {
				$galley = $galleyDao->getGalleyByBestGalleyId($galleyId, $article->getId());
			} else {
				$galley = $galleyDao->getGalley($galleyId, $article->getId());
			}

			if ($galley) {
				if ($galley->getRemoteURL()) {
					$request->redirectUrl($galley->getRemoteURL());
				}
			}

			$templateMgr = TemplateManager::getManager($request);
			$templateMgr->addJavaScript('js/relatedItems.js');

			if (!$galley) {
				// Get the subscription status if displaying the abstract;
				// if access is open, we can display links to the full text.
				import('classes.issue.IssueAction');

				// The issue may not exist, if this is an editorial user
				// and scheduling hasn't been completed yet for the article.
				$issueAction = new IssueAction();
				if ($issue) {
					$templateMgr->assign('subscriptionRequired', $issueAction->subscriptionRequired($issue));
				}

				$templateMgr->assign('subscribedUser', $issueAction->subscribedUser($journal, isset($issue) ? $issue->getId() : null, isset($article) ? $article->getId() : null));
				$templateMgr->assign('subscribedDomain', $issueAction->subscribedDomain($journal, isset($issue) ? $issue->getId() : null, isset($article) ? $article->getId() : null));

				$templateMgr->assign('showGalleyLinks', $journal->getSetting('showGalleyLinks'));

				import('classes.payment.ojs.OJSPaymentManager');
				$paymentManager = new OJSPaymentManager($request);
				if ( $paymentManager->onlyPdfEnabled() ) {
					$templateMgr->assign('restrictOnlyPdf', true);
				}
				if ( $paymentManager->purchaseArticleEnabled() ) {
					$templateMgr->assign('purchaseArticleEnabled', true);
				}

				// Article cover page.
				$locale = AppLocale::getLocale();
				if (isset($article) && $article->getLocalizedFileName() && $article->getLocalizedShowCoverPage() && !$article->getLocalizedHideCoverPageAbstract()) {
					import('classes.file.PublicFileManager');
					$publicFileManager = new PublicFileManager();
					$coverPagePath = $request->getBaseUrl() . '/';
					$coverPagePath .= $publicFileManager->getJournalFilesPath($journal->getId()) . '/';
					$templateMgr->assign('coverPagePath', $coverPagePath);
					$templateMgr->assign('coverPageFileName', $article->getLocalizedFileName());
					$templateMgr->assign('width', $article->getLocalizedWidth());
					$templateMgr->assign('height', $article->getLocalizedHeight());
					$templateMgr->assign('coverPageAltText', $article->getLocalizedCoverPageAltText());
				}

				// References list.
				// FIXME: We only display the edited raw citations right now. We also want
				// to allow for generated citations to be displayed here (including a way for
				// the reader to choose any of the installed citation styles for output), see #5938.
				$citationDao = DAORegistry::getDAO('CitationDAO'); /* @var $citationDao CitationDAO */
				$citationFactory = $citationDao->getObjectsByAssocId(ASSOC_TYPE_ARTICLE, $article->getId());
				$templateMgr->assign('citationFactory', $citationFactory);
			}

			$templateMgr->assign('issue', $issue);
			$templateMgr->assign('article', $article);
			$templateMgr->assign('galley', $galley);
			$templateMgr->assign('section', $section);
			$templateMgr->assign('journalRt', $journalRt);
			$templateMgr->assign('version', $version);
			$templateMgr->assign('journal', $journal);
			$templateMgr->assign('articleId', $articleId);
			$templateMgr->assign('postingAllowed', (
				($article->getEnableComments()) && (
				$enableComments == COMMENTS_UNAUTHENTICATED ||
				(($enableComments == COMMENTS_AUTHENTICATED ||
				$enableComments == COMMENTS_ANONYMOUS) &&
				Validation::isLoggedIn()))
			));
			$templateMgr->assign('enableComments', $enableComments);
			$templateMgr->assign('postingLoginRequired', ($enableComments != COMMENTS_UNAUTHENTICATED && !Validation::isLoggedIn()));
			$templateMgr->assign('galleyId', $galleyId);
			$templateMgr->assign('fileId', $fileId);
			$templateMgr->assign('defineTermsContextId', isset($defineTermsContextId)?$defineTermsContextId:null);
			$templateMgr->assign('comments', isset($comments)?$comments:null);

			$templateMgr->assign('sharingEnabled', $journalRt->getSharingEnabled());

			if($journalRt->getSharingEnabled()) {
				$templateMgr->assign('sharingRequestURL', $request->getRequestURL());
				$templateMgr->assign('sharingArticleTitle', $article->getLocalizedTitle());
				$templateMgr->assign('sharingUserName', $journalRt->getSharingUserName());
				$templateMgr->assign('sharingButtonStyle', $journalRt->getSharingButtonStyle());
				$templateMgr->assign('sharingDropDownMenu', $journalRt->getSharingDropDownMenu());
				$templateMgr->assign('sharingBrand', $journalRt->getSharingBrand());
				$templateMgr->assign('sharingDropDown', $journalRt->getSharingDropDown());
				$templateMgr->assign('sharingLanguage', $journalRt->getSharingLanguage());
				$templateMgr->assign('sharingLogo', $journalRt->getSharingLogo());
				$templateMgr->assign('sharingLogoBackground', $journalRt->getSharingLogoBackground());
				$templateMgr->assign('sharingLogoColor', $journalRt->getSharingLogoColor());
				list($btnUrl, $btnWidth, $btnHeight) = SharingRT::sharingButtonImage($journalRt);
				$templateMgr->assign('sharingButtonUrl', $btnUrl);
				$templateMgr->assign('sharingButtonWidth', $btnWidth);
				$templateMgr->assign('sharingButtonHeight', $btnHeight);
			}

			$templateMgr->assign('articleSearchByOptions', array(
				'query' => 'search.allFields',
				'authors' => 'search.author',
				'title' => 'article.title',
				'abstract' => 'search.abstract',
				'indexTerms' => 'search.indexTerms',
				'galleyFullText' => 'search.fullText'
			));
			// consider public identifiers
			$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true);
			$templateMgr->assign('pubIdPlugins', $pubIdPlugins);

			// load Article galley plugins
			$galleyPlugins = PluginRegistry::loadCategory('articleGalleys', true);

			$templateMgr->display('article/article.tpl');
		}
	}

	/**
	 * View a file in a browser (inline)
	 * @param array $args
	 * @param PKPRequest $request
	 */
	function viewFile($args, $request, $hookName = 'ArticleHandler::viewFile', $inline = true) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? $args[1] : 0;
		$fileId = isset($args[2]) ? (int) $args[2] : 0;

		if ($this->userCanViewGalley($request, $articleId, $galleyId)) {
			if (!$fileId) {
				$submissionFile = $this->galley->getFirstGalleyFile(SUBMISSION_FILE_PROOF);
				if ($submissionFile) {
					$fileId = $submissionFile->getFileId();
				} else { // no proof files assigned to this galley!
					assert(false);
					return null;
				}
			}

			if (!HookRegistry::call($hookName, array($this->article, &$this->galley, &$fileId))) {
				import('classes.file.ArticleFileManager');
				$articleFileManager = new ArticleFileManager($articleId);
				$file = $articleFileManager->downloadFile($fileId, null, $inline);
			}
		}
	}

	/**
	 * download a file in a browser
	 * @param array $args
	 * @param PKPRequest $request
	 */
	function download($args, $request) {
		return $this->viewFile($args, $request, 'ArticleHandler::download', false);
	}

	/**
	 * Article interstitial page before a non-PDF, non-HTML galley is
	 * downloaded
	 * @param $args array
	 * @param $request Request
	 * @param $galley ArticleGalley
	 */
	function viewDownloadInterstitial($args, $request, $galley = null) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? $args[1] : 0;
		if ($this->userCanViewGalley($request, $articleId, $galleyId)) {
			$journal = $this->journal;
			$issue = $this->issue;
			$article = $this->article;
			$this->setupTemplate($request);

			if (!$galley) {
				$galleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
				if ($journal->getSetting('enablePublicGalleyId')) {
					$galley = $galleyDao->getGalleyByBestGalleyId($galleyId, $article->getId());
				} else {
					$galley = $galleyDao->getGalley($galleyId, $article->getId());
				}
			}

			if (!$galley) $request->redirect(null, null, 'view', $articleId);

			$templateMgr = TemplateManager::getManager($request);
			$templateMgr->assign('articleId', $articleId);
			$templateMgr->assign('galleyId', $galleyId);
			$templateMgr->assign('galley', $galley);
			$templateMgr->assign('article', $article);

			$templateMgr->display('article/interstitial.tpl');
		}
	}

	/**
	 * Determines whether a user can view this article galley or not.
	 * @param $request Request
	 * @param $articleId string
	 * @param $galleyId int or string
	 */
	function userCanViewGalley($request, $articleId, $galleyId = null) {

		import('classes.issue.IssueAction');
		$issueAction = new IssueAction();

		$router = $request->getRouter();
		$journal = $this->journal;
		$publishedArticle = $this->article;
		$issue = $this->issue;
		$journalId = $journal->getId();
		$user = $request->getUser();
		$userId = $user?$user->getId():0;

		// If this is an editorial user who can view unpublished/unscheduled
		// articles, bypass further validation. Likewise for its author.
		if (($publishedArticle) && $publishedArticle && $issueAction->allowedPrePublicationAccess($journal, $publishedArticle)) {
			return true;
		}

		// Make sure the reader has rights to view the article/issue.
		if ($issue && $issue->getPublished() && $publishedArticle->getStatus() == STATUS_PUBLISHED) {
			$subscriptionRequired = $issueAction->subscriptionRequired($issue);
			$isSubscribedDomain = $issueAction->subscribedDomain($journal, $issue->getId(), $publishedArticle->getId());

			// Check if login is required for viewing.
			if (!$isSubscribedDomain && !Validation::isLoggedIn() && $journal->getSetting('restrictArticleAccess') && isset($galleyId) && $galleyId) {
				Validation::redirectLogin();
			}

			// bypass all validation if subscription based on domain or ip is valid
			// or if the user is just requesting the abstract
			if ( (!$isSubscribedDomain && $subscriptionRequired) && (isset($galleyId) && $galleyId) ) {

				// Subscription Access
				$subscribedUser = $issueAction->subscribedUser($journal, $issue->getId(), $publishedArticle->getId());

				import('classes.payment.ojs.OJSPaymentManager');
				$paymentManager = new OJSPaymentManager($request);

				$purchasedIssue = false;
				if (!$subscribedUser && $paymentManager->purchaseIssueEnabled()) {
					$completedPaymentDao = DAORegistry::getDAO('OJSCompletedPaymentDAO');
					$purchasedIssue = $completedPaymentDao->hasPaidPurchaseIssue($userId, $issue->getId());
				}

				if (!(!$subscriptionRequired || $publishedArticle->getAccessStatus() == ARTICLE_ACCESS_OPEN || $subscribedUser || $purchasedIssue)) {

					if ( $paymentManager->purchaseArticleEnabled() || $paymentManager->membershipEnabled() ) {
						/* if only pdf files are being restricted, then approve all non-pdf galleys
						 * and continue checking if it is a pdf galley */
						if ( $paymentManager->onlyPdfEnabled() ) {

							if ($this->galley && !$this->galley->isPdfGalley() ) {
								$this->issue = $issue;
								$this->article = $publishedArticle;
								return true;
							}
						}

						if (!Validation::isLoggedIn()) {
							Validation::redirectLogin("payment.loginRequired.forArticle");
						}

						/* if the article has been paid for then forget about everything else
						 * and just let them access the article */
						$completedPaymentDao = DAORegistry::getDAO('OJSCompletedPaymentDAO');
						$dateEndMembership = $user->getSetting('dateEndMembership', 0);
						if ($completedPaymentDao->hasPaidPurchaseArticle($userId, $publishedArticle->getId())
							|| (!is_null($dateEndMembership) && $dateEndMembership > time())) {
							$this->issue = $issue;
							$this->article = $publishedArticle;
							return true;
						} else {
							$queuedPayment = $paymentManager->createQueuedPayment($journalId, PAYMENT_TYPE_PURCHASE_ARTICLE, $user->getId(), $publishedArticle->getId(), $journal->getSetting('purchaseArticleFee'));
							$queuedPaymentId = $paymentManager->queuePayment($queuedPayment);

							$paymentManager->displayPaymentForm($queuedPaymentId, $queuedPayment);
							exit;
						}
					}

					if (!isset($galleyId) || $galleyId) {
						if (!Validation::isLoggedIn()) {
							Validation::redirectLogin("reader.subscriptionRequiredLoginText");
						}
						$request->redirect(null, 'about', 'subscriptions');
					}
				}
			}
		} else {
			$request->redirect(null, 'search');
		}
		return true;
	}

	function setupTemplate($request) {
		parent::setupTemplate($request);
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_READER, LOCALE_COMPONENT_PKP_SUBMISSION);
	}
}

?>
