<?php

/**
 * @file pages/article/ArticleHandler.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ArticleHandler
 *
 * @ingroup pages_article
 *
 * @brief Handle requests for article functions.
 *
 */

namespace APP\pages\article;

use APP\core\Application;
use APP\core\Services;
use APP\facades\Repo;
use APP\handler\Handler;
use APP\issue\IssueAction;
use APP\observers\events\UsageEvent;
use APP\payment\ojs\OJSCompletedPaymentDAO;
use APP\payment\ojs\OJSPaymentManager;
use APP\security\authorization\OjsJournalMustPublishPolicy;
use APP\submission\Submission;
use APP\template\TemplateManager;
use Firebase\JWT\JWT;
use PKP\citation\CitationDAO;
use PKP\config\Config;
use PKP\core\PKPApplication;
use PKP\db\DAORegistry;
use PKP\plugins\Hook;
use PKP\plugins\PluginRegistry;
use PKP\security\authorization\ContextRequiredPolicy;
use PKP\security\Validation;
use PKP\submission\Genre;
use PKP\submission\GenreDAO;
use PKP\submission\PKPSubmission;
use PKP\submissionFile\SubmissionFile;

class ArticleHandler extends Handler
{
    /** @var \APP\journal\Journal Context associated with the request */
    public $context;

    /** @var ?\APP\issue\Issue Issue associated with the request */
    public $issue;

    /** @var \APP\submission\Submission Submission associated with the request */
    public $article;

    /** @var \PKP\category\Category Category associated with the request */
    public $categories;

    /** @var \APP\publication\Publication Publication associated with the request */
    public $publication;

    /** @var \PKP\galley\Galley galley associated with the request */
    public $galley;

    /** @var int submissionFileId associated with the request */
    public $submissionFileId;


    /**
     * @copydoc PKPHandler::authorize()
     */
    public function authorize($request, &$args, $roleAssignments)
    {
        // Permit the use of the Authorization header and an API key for access to unpublished/subscription content
        if ($header = array_search('Authorization', array_flip(getallheaders()))) {
            [$bearer, $jwt] = explode(' ', $header);
            if (strcasecmp($bearer, 'Bearer') == 0 && !empty($jwt)) {
                $secret = Config::getVar('security', 'api_key_secret', '');
                if (!$secret) {
                    $templateMgr = TemplateManager::getManager($request);
                    $templateMgr->assign('message', 'api.500.apiSecretKeyMissing');
                    return $templateMgr->display('frontend/pages/message.tpl');
                }
                try {
                    $apiToken = JWT::decode($jwt, $secret, ['HS256']);
                    // Compatibility with old API keys
                    // https://github.com/pkp/pkp-lib/issues/6462
                    if (substr($apiToken, 0, 2) === '""') {
                        $apiToken = json_decode($apiToken);
                    }
                    $this->setApiToken($apiToken);
                } catch (\Exception $e) {
                    $templateMgr = TemplateManager::getManager($request);
                    $templateMgr->assign('message', 'api.400.invalidApiToken');
                    return $templateMgr->display('frontend/pages/message.tpl');
                }
            }
        }

        $this->addPolicy(new ContextRequiredPolicy($request));

        $this->addPolicy(new OjsJournalMustPublishPolicy($request));

        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * @see PKPHandler::initialize()
     *
     * @param \APP\core\Request $request
     * @param array $args Arguments list
     */
    public function initialize($request, $args = [])
    {
        $urlPath = empty($args) ? 0 : array_shift($args);

        // Get the submission that matches the requested urlPath
        $submission = ctype_digit((string) $urlPath)
            ? Repo::submission()->get((int) $urlPath, $request->getContext()->getId())
            : Repo::submission()->getByUrlPath($urlPath, $request->getContext()->getId());

        $user = $request->getUser();

        // Serve 404 if no submission available OR submission is unpublished and no user is logged in OR submission is unpublished and we have a user logged in but the user does not have access to preview
        if (!$submission || ($submission->getData('status') !== PKPSubmission::STATUS_PUBLISHED && !$user) || ($submission->getData('status') !== PKPSubmission::STATUS_PUBLISHED && $user && !Repo::submission()->canPreview($user, $submission))) {
            $request->getDispatcher()->handle404();
        }

        // If the urlPath does not match the urlPath of the current
        // publication, redirect to the current URL
        $currentUrlPath = $submission->getBestId();
        if ($currentUrlPath && $currentUrlPath != $urlPath) {
            $newArgs = array_merge([$currentUrlPath], $args);
            $request->redirect(null, $request->getRequestedPage(), $request->getRequestedOp(), $newArgs);
        }

        $this->article = $submission;
        // Get the requested publication or if none requested get the current publication
        $subPath = empty($args) ? 0 : array_shift($args);
        if ($subPath === 'version') {
            $publicationId = (int) array_shift($args);
            $galleyId = empty($args) ? 0 : array_shift($args);
            foreach ($this->article->getData('publications') as $publication) {
                if ($publication->getId() === $publicationId) {
                    $this->publication = $publication;
                }
            }
            if (!$this->publication) {
                $request->getDispatcher()->handle404();
            }
        } else {
            $this->publication = $this->article->getCurrentPublication();
            $galleyId = $subPath;
        }

        if ($this->publication->getData('status') !== PKPSubmission::STATUS_PUBLISHED && !Repo::submission()->canPreview($user, $submission)) {
            $request->getDispatcher()->handle404();
        }

        if ($galleyId && in_array($request->getRequestedOp(), ['view', 'download'])) {
            $galleys = $this->publication->getData('galleys');
            foreach ($galleys as $galley) {
                if ($galley->getBestGalleyId() == $galleyId) {
                    $this->galley = $galley;
                    break;

                // In some cases, a URL to a galley may use the ID when it should use
                // the urlPath. Redirect to the galley's correct URL.
                } elseif (ctype_digit($galleyId) && $galley->getId() == $galleyId) {
                    $request->redirect(null, $request->getRequestedPage(), $request->getRequestedOp(), [$submission->getBestId(), $galley->getBestGalleyId()]);
                }
            }
            // Redirect to the most recent version of the submission if the request
            // points to an outdated galley but doesn't use the specific versioned
            // URL. This can happen when a galley's urlPath is changed between versions.
            if (!$this->galley) {
                $publications = $submission->getPublishedPublications();
                foreach ($publications as $publication) {
                    foreach ($publication->getData('galleys') as $galley) {
                        if ($galley->getBestGalleyId() == $galleyId) {
                            $request->redirect(null, $request->getRequestedPage(), $request->getRequestedOp(), [$submission->getBestId()]);
                        }
                    }
                }
                $request->getDispatcher()->handle404();
            }

            // Store the file id if it exists
            if (!empty($args)) {
                $this->submissionFileId = array_shift($args);
            }
        }

        if ($this->publication->getData('issueId')) {
            // TODO: Previously fetched issue from cache. Reimplement when caching added.
            $issue = Repo::issue()->get($this->publication->getData('issueId'));
            $issue = $issue->getJournalId() == $submission->getData('contextId') ? $issue : null;
            $this->issue = $issue;
        }
    }

    /**
     * View Article. (Either article landing page or galley view.)
     *
     * @param array $args
     * @param \APP\core\Request $request
     */
    public function view($args, $request)
    {
        $context = $request->getContext();
        $user = $request->getUser();
        $issue = $this->issue;
        $article = $this->article;
        $publication = $this->publication;
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign([
            'issue' => $issue,
            'article' => $article,
            'publication' => $publication,
            'currentPublication' => $article->getCurrentPublication(),
            'galley' => $this->galley,
            'fileId' => $this->submissionFileId, // DEPRECATED in 3.4.0: https://github.com/pkp/pkp-lib/issues/6545
            'submissionFileId' => $this->submissionFileId,
        ]);
        $this->setupTemplate($request);

        // Get the earliest published publication
        $firstPublication = $article->getData('publications')->reduce(function ($a, $b) {
            return empty($a) || strtotime((string) $b->getData('datePublished')) < strtotime((string) $a->getData('datePublished')) ? $b : $a;
        }, 0);
        $templateMgr->assign([
            'firstPublication' => $firstPublication,
        ]);

        $templateMgr->assign([
            'ccLicenseBadge' => Application::get()->getCCLicenseBadge($publication->getData('licenseUrl')),
            'publication' => $publication,
            'section' => Repo::section()->get($publication->getData('sectionId')),
        ]);

        if ($this->galley && !$this->userCanViewGalley($request, $article->getId(), $this->galley->getId())) {
            fatalError('Cannot view galley.');
        }

        $templateMgr->assign([
            'categories' => Repo::category()->getCollector()
                ->filterByPublicationIds([$publication->getId()])
                ->getMany()
                ->toArray()
        ]);

        // Get galleys sorted into primary and supplementary groups
        $galleys = $publication->getData('galleys');

        $primaryGalleys = [];
        $supplementaryGalleys = [];
        if ($galleys) {
            $genreDao = DAORegistry::getDAO('GenreDAO'); /** @var GenreDAO $genreDao */
            $primaryGenres = $genreDao->getPrimaryByContextId($context->getId())->toArray();
            $primaryGenreIds = array_map(function ($genre) {
                return $genre->getId();
            }, $primaryGenres);
            $supplementaryGenres = $genreDao->getBySupplementaryAndContextId(true, $context->getId())->toArray();
            $supplementaryGenreIds = array_map(function ($genre) {
                return $genre->getId();
            }, $supplementaryGenres);

            foreach ($galleys as $galley) {
                $remoteUrl = $galley->getRemoteURL();
                $file = Repo::submissionFile()->get((int) $galley->getData('submissionFileId'));
                if (!$remoteUrl && !$file) {
                    continue;
                }
                if ($remoteUrl || in_array($file->getGenreId(), $primaryGenreIds)) {
                    $primaryGalleys[] = $galley;
                } elseif (in_array($file->getGenreId(), $supplementaryGenreIds)) {
                    $supplementaryGalleys[] = $galley;
                }
            }
        }
        $templateMgr->assign([
            'primaryGalleys' => $primaryGalleys,
            'supplementaryGalleys' => $supplementaryGalleys,
            'userGroupsById' => Repo::userGroup()->getCollector()->filterByPublicationIds([$this->publication->getId()])->getMany()->toArray()
        ]);

        // Citations
        if ($publication->getData('citationsRaw')) {
            $citationDao = DAORegistry::getDAO('CitationDAO'); /** @var CitationDAO $citationDao */
            $parsedCitations = $citationDao->getByPublicationId($publication->getId());
            $templateMgr->assign([
                'parsedCitations' => $parsedCitations->toArray(),
            ]);
        }

        // Assign deprecated values to the template manager for
        // compatibility with older themes
        $templateMgr->assign([
            'licenseTerms' => $context->getLocalizedData('licenseTerms'),
            'licenseUrl' => $publication->getData('licenseUrl'),
            'copyrightHolder' => $publication->getLocalizedData('copyrightHolder'),
            'copyrightYear' => $publication->getData('copyrightYear'),
            'pubIdPlugins' => PluginRegistry::loadCategory('pubIds', true),
            'keywords' => $publication->getData('keywords'),
        ]);

        // Fetch and assign the galley to the template
        if ($this->galley && $this->galley->getRemoteURL()) {
            $request->redirectUrl($this->galley->getRemoteURL());
        }

        if (empty($this->galley)) {
            // No galley: Prepare the article landing page.

            // Ask robots not to index outdated versions and point to the canonical url for the latest version
            if ($publication->getId() !== $article->getCurrentPublication()->getId()) {
                $templateMgr->addHeader('noindex', '<meta name="robots" content="noindex">');
                $url = $request->getDispatcher()->url($request, PKPApplication::ROUTE_PAGE, null, 'article', 'view', $article->getBestId());
                $templateMgr->addHeader('canonical', '<link rel="canonical" href="' . $url . '">');
            }

            // Get the subscription status if displaying the abstract;
            // if access is open, we can display links to the full text.

            // The issue may not exist, if this is an editorial user
            // and scheduling hasn't been completed yet for the article.
            $issueAction = new IssueAction();
            $subscriptionRequired = false;
            if ($issue) {
                $subscriptionRequired = $issueAction->subscriptionRequired($issue, $context);
            }

            $subscribedUser = $issueAction->subscribedUser($user, $context, isset($issue) ? $issue->getId() : null, isset($article) ? $article->getId() : null);
            $subscribedDomain = $issueAction->subscribedDomain($request, $context, isset($issue) ? $issue->getId() : null, isset($article) ? $article->getId() : null);

            $completedPaymentDao = DAORegistry::getDAO('OJSCompletedPaymentDAO'); /** @var OJSCompletedPaymentDAO $completedPaymentDao */
            $templateMgr->assign(
                'hasAccess',
                !$subscriptionRequired ||
                $publication->getData('accessStatus') == Submission::ARTICLE_ACCESS_OPEN ||
                $subscribedUser || $subscribedDomain ||
                ($user && $issue && $completedPaymentDao->hasPaidPurchaseIssue($user->getId(), $issue->getId())) ||
                ($user && $completedPaymentDao->hasPaidPurchaseArticle($user->getId(), $article->getId()))
            );

            $paymentManager = Application::get()->getPaymentManager($context);
            if ($paymentManager->onlyPdfEnabled()) {
                $templateMgr->assign('restrictOnlyPdf', true);
            }
            if ($paymentManager->purchaseArticleEnabled()) {
                $templateMgr->assign('purchaseArticleEnabled', true);
            }

            if (!Hook::call('ArticleHandler::view', [&$request, &$issue, &$article, $publication])) {
                $templateMgr->display('frontend/pages/article.tpl');
                event(new UsageEvent(Application::ASSOC_TYPE_SUBMISSION, $context, $article, null, null, $this->issue));
                return;
            }
        } else {
            // Ask robots not to index outdated versions
            if ($publication->getId() !== $article->getCurrentPublication()->getId()) {
                $templateMgr->addHeader('noindex', '<meta name="robots" content="noindex">');
            }

            // Galley: Prepare the galley file download.
            if (!Hook::call('ArticleHandler::view::galley', [&$request, &$issue, &$this->galley, &$article, $publication])) {
                if ($this->publication->getId() !== $this->article->getCurrentPublication()->getId()) {
                    $redirectPath = [
                        $article->getBestId(),
                        'version',
                        $publication->getId(),
                        $this->galley->getBestGalleyId()
                    ];
                } else {
                    $redirectPath = [
                        $article->getBestId(),
                        $this->galley->getBestGalleyId()
                    ];
                }
                $request->redirect(null, null, 'download', $redirectPath);
            }
        }
    }

    /**
     * Download an article file
     * For deprecated OJS 2.x URLs; see https://github.com/pkp/pkp-lib/issues/1541
     *
     * @param array $args
     * @param \APP\core\Request $request
     */
    public function viewFile($args, $request)
    {
        $articleId = $args[0] ?? 0;
        $galleyId = $args[1] ?? 0;
        $submissionFileId = isset($args[2]) ? (int) $args[2] : 0;
        header('HTTP/1.1 301 Moved Permanently');
        $request->redirect(null, null, 'download', [$articleId, $galleyId, $submissionFileId]);
    }

    /**
     * Download a supplementary file.
     * For deprecated OJS 2.x URLs; see https://github.com/pkp/pkp-lib/issues/1541
     *
     * @param array $args
     * @param \APP\core\Request $request
     */
    public function downloadSuppFile($args, $request)
    {
        $articleId = $args[0] ?? 0;
        $article = Repo::submission()->get($articleId);
        if (!$article) {
            $dispatcher = $request->getDispatcher();
            $dispatcher->handle404();
        }
        $suppId = $args[1] ?? 0;

        $submissionFiles = Repo::submissionFile()
            ->getCollector()
            ->filterBySubmissionIds([$article->getId()])
            ->getMany();

        foreach ($submissionFiles as $submissionFile) {
            if ($submissionFile->getData('old-supp-id') == $suppId) {
                $articleGalleys = Repo::galley()->getCollector()
                    ->filterByPublicationIds([$article->getCurrentPublication()->getId()])
                    ->getMany();

                foreach ($articleGalleys as $articleGalley) {
                    $galleyFile = Repo::submissionFile()->get($articleGalley->getData('submissionFileId'));
                    if ($galleyFile && $galleyFile->getData('submissionFileId') == $submissionFile->getId()) {
                        header('HTTP/1.1 301 Moved Permanently');
                        $request->redirect(null, null, 'download', [$articleId, $articleGalley->getId(), $submissionFile->getId()]);
                    }
                }
            }
        }
        $dispatcher = $request->getDispatcher();
        $dispatcher->handle404();
    }

    /**
     * Download an article file
     *
     * @param array $args
     * @param \APP\core\Request $request
     */
    public function download($args, $request)
    {
        if (!isset($this->galley)) {
            $request->getDispatcher()->handle404();
        }
        if ($this->galley->getRemoteURL()) {
            $request->redirectUrl($this->galley->getRemoteURL());
        } elseif ($this->userCanViewGalley($request, $this->article->getId(), $this->galley->getId())) {
            if (!$this->submissionFileId) {
                $this->submissionFileId = $this->galley->getData('submissionFileId');
            }

            // If no file ID could be determined, treat it as a 404.
            if (!$this->submissionFileId) {
                $request->getDispatcher()->handle404();
            }

            // If the file ID is not the galley's file ID, ensure it is a dependent file, or else 404.
            if ($this->submissionFileId != $this->galley->getData('submissionFileId')) {
                $dependentFileIds = Repo::submissionFile()
                    ->getCollector()
                    ->filterByAssoc(
                        Application::ASSOC_TYPE_SUBMISSION_FILE,
                        [$this->galley->getData('submissionFileId')]
                    )
                    ->filterByFileStages([SubmissionFile::SUBMISSION_FILE_DEPENDENT])
                    ->includeDependentFiles()
                    ->getIds()
                    ->toArray();

                if (!in_array($this->submissionFileId, $dependentFileIds)) {
                    $request->getDispatcher()->handle404();
                }
            }

            if (!Hook::call('ArticleHandler::download', [$this->article, &$this->galley, &$this->submissionFileId])) {
                $submissionFile = Repo::submissionFile()->get($this->submissionFileId);

                if (!Services::get('file')->fs->has($submissionFile->getData('path'))) {
                    $request->getDispatcher()->handle404();
                }

                $filename = Services::get('file')->formatFilename($submissionFile->getData('path'), $submissionFile->getLocalizedData('name'));

                // if the file is a galley file (i.e. not a dependent file e.g. CSS or images), fire an usage event.
                if ($this->galley->getData('submissionFileId') == $this->submissionFileId) {
                    $assocType = Application::ASSOC_TYPE_SUBMISSION_FILE;
                    /** @var GenreDAO */
                    $genreDao = DAORegistry::getDAO('GenreDAO');
                    $genre = $genreDao->getById($submissionFile->getData('genreId'));
                    // TO-DO: is this correct ?
                    if ($genre->getCategory() != Genre::GENRE_CATEGORY_DOCUMENT || $genre->getSupplementary() || $genre->getDependent()) {
                        $assocType = Application::ASSOC_TYPE_SUBMISSION_FILE_COUNTER_OTHER;
                    }
                    event(new UsageEvent($assocType, $request->getContext(), $this->article, $this->galley, $submissionFile, $this->issue));
                }
                $returner = true;
                Hook::call('FileManager::downloadFileFinished', [&$returner]);
                Services::get('file')->download($submissionFile->getData('fileId'), $filename);
            }
        } else {
            header('HTTP/1.0 403 Forbidden');
            echo '403 Forbidden<br>';
        }
    }

    /**
     * Determines whether a user can view this article galley or not.
     *
     * @param \APP\core\Request $request
     * @param string $articleId
     * @param int|string $galleyId
     */
    public function userCanViewGalley($request, $articleId, $galleyId = null)
    {
        $issueAction = new IssueAction();

        $context = $request->getContext();
        $submission = $this->article;
        $issue = $this->issue;
        $contextId = $context->getId();
        $user = $request->getUser();
        $userId = $user ? $user->getId() : 0;

        // If this is an editorial user who can view unpublished/unscheduled
        // articles, bypass further validation. Likewise for its author.
        if ($submission && $user && Repo::submission()->canPreview($user, $submission)) {
            return true;
        }

        // Make sure the reader has rights to view the article/issue.
        if ($issue && $issue->getPublished() && $submission->getStatus() == PKPSubmission::STATUS_PUBLISHED) {
            $subscriptionRequired = $issueAction->subscriptionRequired($issue, $context);
            $isSubscribedDomain = $issueAction->subscribedDomain($request, $context, $issue->getId(), $submission->getId());

            // Check if login is required for viewing.
            if (!$isSubscribedDomain && !Validation::isLoggedIn() && $context->getData('restrictArticleAccess') && isset($galleyId) && $galleyId) {
                Validation::redirectLogin();
            }

            // bypass all validation if subscription based on domain or ip is valid
            // or if the user is just requesting the abstract
            if ((!$isSubscribedDomain && $subscriptionRequired) && (isset($galleyId) && $galleyId)) {
                // Subscription Access
                $subscribedUser = $issueAction->subscribedUser($user, $context, $issue->getId(), $submission->getId());

                $paymentManager = Application::get()->getPaymentManager($context);

                $purchasedIssue = false;
                if (!$subscribedUser && $paymentManager->purchaseIssueEnabled()) {
                    $completedPaymentDao = DAORegistry::getDAO('OJSCompletedPaymentDAO'); /** @var OJSCompletedPaymentDAO $completedPaymentDao */
                    $purchasedIssue = $completedPaymentDao->hasPaidPurchaseIssue($userId, $issue->getId());
                }

                if (!(!$subscriptionRequired || $submission->getCurrentPublication()->getData('accessStatus') == Submission::ARTICLE_ACCESS_OPEN || $subscribedUser || $purchasedIssue)) {
                    if ($paymentManager->purchaseArticleEnabled() || $paymentManager->membershipEnabled()) {
                        /* if only pdf files are being restricted, then approve all non-pdf galleys
                         * and continue checking if it is a pdf galley */
                        if ($paymentManager->onlyPdfEnabled()) {
                            if ($this->galley && !$this->galley->isPdfGalley()) {
                                $this->issue = $issue;
                                $this->article = $submission;
                                return true;
                            }
                        }

                        if (!Validation::isLoggedIn()) {
                            Validation::redirectLogin('payment.loginRequired.forArticle');
                        }

                        /* if the article has been paid for then forget about everything else
                         * and just let them access the article */
                        $completedPaymentDao = DAORegistry::getDAO('OJSCompletedPaymentDAO'); /** @var OJSCompletedPaymentDAO $completedPaymentDao */
                        $dateEndMembership = $user->getData('dateEndMembership', 0);
                        if ($completedPaymentDao->hasPaidPurchaseArticle($userId, $submission->getId())
                            || (!is_null($dateEndMembership) && $dateEndMembership > time())) {
                            $this->issue = $issue;
                            $this->article = $submission;
                            return true;
                        } elseif ($paymentManager->purchaseArticleEnabled()) {
                            $queuedPayment = $paymentManager->createQueuedPayment($request, OJSPaymentManager::PAYMENT_TYPE_PURCHASE_ARTICLE, $user->getId(), $submission->getId(), $context->getData('purchaseArticleFee'));
                            $paymentManager->queuePayment($queuedPayment);

                            $paymentForm = $paymentManager->getPaymentForm($queuedPayment);
                            $paymentForm->display($request);
                            exit;
                        }
                    }

                    if (!isset($galleyId) || $galleyId) {
                        if (!Validation::isLoggedIn()) {
                            Validation::redirectLogin('reader.subscriptionRequiredLoginText');
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
}
