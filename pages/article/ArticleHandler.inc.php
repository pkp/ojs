<?php

/**
 * @file pages/article/ArticleHandler.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleHandler
 * @ingroup pages_article
 *
 * @brief Handle requests for article functions.
 *
 */

import('classes.handler.Handler');

class ArticleHandler extends Handler {
	
	/** @var journal object: journal associated with the request **/
	var $journal;

	/** @var issue object: issue associated with the request **/
	var $issue;

	/** @var article object: article associated with the request **/
	var $article;

	/** @var galley object: galley associated with the request **/
	var $galley;
	
	/** @var int: type of the requested object (article, galley or file) **/
	var $type;
	
	/** @var int: article ID associated with the request **/
	var $articleId;
	
	/** @var int: submission(article) revision ID associated with the request **/
	var $submissionRevision;
	
	/** @var int: the highest metadata revision ID of a submission **/
	var $latestSubmissionRevision;
	
	/** @var int: galley ID associated with the request **/
	var $galleyId;
	
	/** @var int: file ID associated with the request **/
	var $fileId;
	
	/** @var int: file revision ID associated with the request **/
	var $fileRevision;
	
	/** @var boolean: true if current submission is an old revision **/
	var $isPreviousRevision = false;
	
	/** @var array: precedent revisions of the current submission **/
	var $previousRevisions;
	
	/** @var string: localized title of the latest submission **/
	var $latestTitle;
	
	
	/**
	 * Constructor
	 * @param $request Request
	 */
	function ArticleHandler() {
		parent::Handler();
	}

	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.ContextRequiredPolicy');
		$this->addPolicy(new ContextRequiredPolicy($request));

		import('classes.security.authorization.OjsJournalMustPublishPolicy');
		$this->addPolicy(new OjsJournalMustPublishPolicy($request));

		return parent::authorize($request, $args, $roleAssignments);
	}
	
	/**
	 * Initialize the parameters of the current request
	 * @param $request Request
	 * @param $args array
	 */
	function _initializeArguments($request, $args) {

		if (!in_array($args[0], array('article', 'galley', 'file'))) {
			$this->articleId = isset($args[0]) ? $args[0] : 0;
			
			/* Redirect from old style URLs to the new URL pattern:
				1) /article/view/[articleId]  ->  /article/view/article/[articleId]
				2) /article/view/[articleId]/[galleyId] ->  /article/view/galley/[articleId]/[galleyId]
			*/
			switch(count($args)) {
				case 1: // article
					$request->redirect(null, 'article', 'view', array('article', $this->articleId));
					break;
				case 2: // galley
					$this->galleyId = isset($args[1]) ? $args[1] : 0;
					$request->redirect(null, 'article', 'view', array('galley', $this->articleId, $this->galleyId));
					break;
				default:
					assert(false);
			}
		} else {
			$this->type = $args[0];
			$this->articleId = isset($args[1]) ? $args[1] : 0;
			
			$articleDao = DAORegistry::getDAO('ArticleDAO');
			$this->latestSubmissionRevision = $articleDao->getLatestRevisionId($this->articleId, $this->journal->getId());

			switch($this->type) {
				case 'article':
					$this->submissionRevision = isset($args[2]) ? $args[2] : 0;
					break;
				case 'galley':
					$this->galleyId = isset($args[2]) ? $args[2] : 0;
					$this->submissionRevision = isset($args[3]) ? $args[3] : $this->latestSubmissionRevision;
					break;
				case 'file':
					$this->galleyId = isset($args[2]) ? $args[2] : 0;
					$this->submissionRevision = isset($args[3]) ? $args[3] : $this->latestSubmissionRevision;
					$this->fileId = isset($args[4]) ? $args[4] : 0;
					$this->fileRevision = isset($args[5]) ? $args[5] : 0;
					break;
				default:
					assert(false);
			}
			
			if ($this->submissionRevision &&($this->submissionRevision < $this->latestSubmissionRevision)) {
				$this->isPreviousRevision = true;
			} else {
				$this->previousRevisions = $articleDao->getSubmissionRevisions($this->articleId, $this->journal->getId(), false, true);
			}
		}
	}

	/**
	 * @see PKPHandler::initialize()
	 */
	function initialize($request, $args) {
		
		$this->journal = $request->getContext(); 
		$this->_initializeArguments($request, $args);
		
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		if ($this->journal->getSetting('enablePublicArticleId')) {
			$publishedArticle = $publishedArticleDao->getPublishedArticleByBestArticleId((int) $this->journal->getId(), $this->articleId, true);
		} else {
			$publishedArticle = $publishedArticleDao->getPublishedArticleByArticleId((int) $this->articleId, (int) $this->journal->getId(), true);
		}

		$this->latestTitle = $publishedArticleDao->getLocalizedTitleByVersion($publishedArticle->getId(), $this->latestSubmissionRevision);

		if ($this->submissionRevision) {
			$articleDao = DAORegistry::getDAO('ArticleDAO');
			if (!$articleDao->revisionIdExists($this->articleId, $this->submissionRevision, $this->journal->getId())) {
				$this->submissionRevision = $this->latestSubmissionRevision;
			}
			$publishedArticle = $publishedArticleDao->getPublishedArticleByArticleId((int) $publishedArticle->getId(), (int) $this->journal->getId(), false, $this->submissionRevision);
		}

		$issueDao = DAORegistry::getDAO('IssueDAO');
		if (isset($publishedArticle)) {
			$issue = $issueDao->getById($publishedArticle->getIssueId(), $publishedArticle->getJournalId(), true);
			$this->issue = $issue;
			$this->article = $publishedArticle;
		} else {
			$articleDao = DAORegistry::getDAO('ArticleDAO');
			$article = $articleDao->getById((int) $this->articleId, $this->journal->getId(), true);
			$this->article = $article;
		}

		$galleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
		if ($this->journal->getSetting('enablePublicGalleyId')) {
			$this->galley = $galleyDao->getByBestGalleyId($this->galleyId, $this->articleId);
		}

		if (!$this->galley) {
			$this->galley = $galleyDao->getById($this->galleyId, $this->articleId);
		}
	}

	/**
	 * View Article.
	 * @param $args array
	 * @param $request Request
	 */
	function view($args, $request) {

		if ($this->userCanViewGalley($request, $this->articleId, $this->galleyId)) {
			$journal = $this->journal;
			$issue = $this->issue;
			$this->setupTemplate($request);

			$sectionDao = DAORegistry::getDAO('SectionDAO');
			$section = $sectionDao->getById($this->article->getSectionId(), $journal->getId(), true);

			if (isset($this->galley)) {
				if ($this->galley->getRemoteURL()) {
					$request->redirectUrl($this->galley->getRemoteURL());
				}
			}

			$templateMgr = TemplateManager::getManager($request);
			$templateMgr->addJavaScript('js/relatedItems.js');

			if (!$this->galley) {
				// Get the subscription status if displaying the abstract;
				// if access is open, we can display links to the full text.
				import('classes.issue.IssueAction');

				// The issue may not exist, if this is an editorial user
				// and scheduling hasn't been completed yet for the article.
				$issueAction = new IssueAction();
				$subscriptionRequired = false;
				if ($issue) {
					$subscriptionRequired = $issueAction->subscriptionRequired($issue);
				}

				$subscribedUser = $issueAction->subscribedUser($journal, isset($issue) ? $issue->getId() : null, isset($this->article) ? $this->articleId : null);
				$subscribedDomain = $issueAction->subscribedDomain($journal, isset($issue) ? $issue->getId() : null, isset($this->article) ? $this->articleId : null);

				$templateMgr->assign('showGalleyLinks', !$subscriptionRequired || $journal->getSetting('showGalleyLinks'));
				$templateMgr->assign('hasAccess', !$subscriptionRequired || (isset($this->article) && $this->article->getAccessStatus() == ARTICLE_ACCESS_OPEN) || $subscribedUser || $subscribedDomain);

				import('classes.payment.ojs.OJSPaymentManager');
				$paymentManager = new OJSPaymentManager($request);
				if ( $paymentManager->onlyPdfEnabled() ) {
					$templateMgr->assign('restrictOnlyPdf', true);
				}
				if ( $paymentManager->purchaseArticleEnabled() ) {
					$templateMgr->assign('purchaseArticleEnabled', true);
				}

				// Article cover page.
				if (isset($this->article) && $this->article->getLocalizedFileName() && $this->article->getLocalizedShowCoverPage() && !$this->article->getLocalizedHideCoverPageAbstract()) {
					import('classes.file.PublicFileManager');
					$publicFileManager = new PublicFileManager();
					$coverPagePath = $request->getBaseUrl() . '/';
					$coverPagePath .= $publicFileManager->getJournalFilesPath($journal->getId()) . '/';
					$templateMgr->assign('coverPagePath', $coverPagePath);
					$templateMgr->assign('coverPageFileName', $this->article->getLocalizedFileName());
					$templateMgr->assign('width', $this->article->getLocalizedWidth());
					$templateMgr->assign('height', $this->article->getLocalizedHeight());
					$templateMgr->assign('coverPageAltText', $this->article->getLocalizedCoverPageAltText());
				}

				// References list.
				// FIXME: We only display the edited raw citations right now. We also want
				// to allow for generated citations to be displayed here (including a way for
				// the reader to choose any of the installed citation styles for output), see #5938.
				$citationDao = DAORegistry::getDAO('CitationDAO'); /* @var $citationDao CitationDAO */
				$citationFactory = $citationDao->getObjectsByAssocId(ASSOC_TYPE_ARTICLE, $this->articleId);
				$templateMgr->assign('citationFactory', $citationFactory);

				// Keywords
				$submissionKeywordDao = DAORegistry::getDAO('SubmissionKeywordDAO');
				$templateMgr->assign('keywords', $submissionKeywordDao->getKeywords($this->articleId, array(AppLocale::getLocale()), $this->submissionRevision > 0 ? $this->submissionRevision : $this->latestSubmissionRevision));
			}

			$templateMgr->assign('issue', $issue);
			$templateMgr->assign('article', $this->article);
			$templateMgr->assign('galley', $this->galley);
			$templateMgr->assign('section', $section);
			$templateMgr->assign('journal', $journal);
			$templateMgr->assign('defineTermsContextId', isset($defineTermsContextId)?$defineTermsContextId:null);
			
			$templateMgr->assign('ccLicenseBadge', Application::getCCLicenseBadge($this->article->getLicenseURL()));
			
			// Copyright and license info
			if ($journal->getSetting('includeCopyrightStatement') && $journal->getLocalizedSetting('copyrightNotice')) {
				$templateMgr->assign('copyright', $journal->getLocalizedSetting('copyrightNotice'));
				$templateMgr->assign('copyrightHolder', $journal->getLocalizedSetting('copyrightHolder'));
				$templateMgr->assign('copyrightYear', $journal->getSetting('copyrightYear'));
			}
			if ($journal->getSetting('includeLicense') && $this->article->getLicenseURL()) {
				$templateMgr->assign('licenseUrl', $this->article->getLicenseURL());
				$templateMgr->assign('ccLicenseBadge', Application::getCCLicenseBadge($this->article->getLicenseURL()));
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
			
			$templateMgr->assign('submissionRevision', $this->submissionRevision);

			if ($this->isPreviousRevision) {
				$templateMgr->assign('isPreviousRevision', true);
				$templateMgr->assign('latestTitle', $this->latestTitle);
			} else if (count($this->previousRevisions) > 0) {
				$templateMgr->assign('previousRevisions', $this->previousRevisions);
			}
			
			$templateMgr->assign('hideSubmissionRevisions', $this->article->getHideSubmissionRevisions());
			
			// load Article galley plugins
			PluginRegistry::loadCategory('viewableFiles', true);

			if (!HookRegistry::call('ArticleHandler::view::galley', array(&$request, &$issue, &$this->galley, &$this->article))) {
				return $templateMgr->display('frontend/pages/article.tpl');
			}
		}
	}

	/**
	 * Download an article file
	 * @param array $args
	 * @param PKPRequest $request
	 */
	function download($args, $request) {

		if ($this->userCanViewGalley($request, $this->articleId, $this->galleyId)) {
			if (!$this->fileId) {
				$submissionFile = $this->galley->getFirstGalleyFile();
				if ($submissionFile) {
					$fileId = $submissionFile->getFileId();
					// The file manager expects the real article id.  Extract it from the submission file.
					$this->articleId = $submissionFile->getSubmissionId();
				} else { // no proof files assigned to this galley!
					assert(false);
					return null;
				}
			}

			if (!HookRegistry::call('ArticleHandler::download', array($this->article, &$this->galley, &$this->fileId))) {
				import('lib.pkp.classes.file.SubmissionFileManager');
				$submissionFileManager = new SubmissionFileManager($this->article->getContextId(), $this->articleId);
				$submissionFileManager->downloadFile($this->fileId, $this->fileRevision, $request->getUserVar('inline')?true:false);
			}
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

		$journal = $this->journal;
		$publishedArticle = $this->article;
		$issue = $this->issue;
		$journalId = $journal->getId();
		$user = $request->getUser();
		$userId = $user?$user->getId():0;

		// If this is an editorial user who can view unpublished/unscheduled
		// articles, bypass further validation. Likewise for its author.
		if ($publishedArticle && $issueAction->allowedPrePublicationAccess($journal, $publishedArticle)) {
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
