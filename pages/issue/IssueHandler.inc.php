<?php

/**
 * @file pages/issue/IssueHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IssueHandler
 * @ingroup pages_issue
 *
 * @brief Handle requests for issue functions.
 */

use APP\core\Application;
use APP\facades\Repo;

use APP\file\IssueFileManager;
use APP\handler\Handler;
use PKP\facades\Locale;
use APP\issue\Collector;
use APP\issue\IssueAction;

use APP\payment\ojs\OJSPaymentManager;
use APP\security\authorization\OjsIssueRequiredPolicy;
use APP\security\authorization\OjsJournalMustPublishPolicy;
use APP\template\TemplateManager;
use PKP\db\DAORegistry;
use PKP\security\authorization\ContextRequiredPolicy;
use PKP\submission\PKPSubmission;

class IssueHandler extends Handler
{
    /** @var IssueGalley retrieved issue galley */
    public $_galley = null;


    /**
     * @copydoc PKPHandler::authorize()
     */
    public function authorize($request, &$args, $roleAssignments)
    {
        $this->addPolicy(new ContextRequiredPolicy($request));
        $this->addPolicy(new OjsJournalMustPublishPolicy($request));

        // the 'archives' op does not need this policy so it is left out of the operations array.
        $this->addPolicy(new OjsIssueRequiredPolicy($request, $args, ['view', 'download']));

        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * @see PKPHandler::initialize()
     *
     * @param array $args Arguments list
     */
    public function initialize($request, $args = [])
    {
        // Get the issue galley
        $galleyId = $args[1] ?? 0;
        if ($galleyId) {
            $issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
            $galleyDao = DAORegistry::getDAO('IssueGalleyDAO'); /** @var IssueGalleyDAO $galleyDao */
            $journal = $request->getJournal();
            $galley = $galleyDao->getByBestId($galleyId, $issue->getId());

            // Invalid galley id, redirect to issue page
            if (!$galley) {
                $request->redirect(null, null, 'view', $issue->getId());
            }

            $this->setGalley($galley);
        }
    }

    /**
     * Display about index page.
     */
    public function index($args, $request)
    {
        $this->current($args, $request);
    }

    /**
     * Display current issue page.
     */
    public function current($args, $request)
    {
        $journal = $request->getJournal();
        $issue = Repo::issue()->getCurrent($journal->getId(), true);

        if ($issue != null) {
            $request->redirect(null, 'issue', 'view', $issue->getBestIssueId());
        }

        $this->setupTemplate($request);
        $templateMgr = TemplateManager::getManager($request);
        // consider public identifiers
        $pubIdPlugins = PluginRegistry::loadCategory('pubIds', true);
        $templateMgr->assign('pubIdPlugins', $pubIdPlugins);
        $templateMgr->display('frontend/pages/issue.tpl');
    }

    /**
     * View an issue.
     *
     * @param array $args
     * @param PKPRequest $request
     */
    public function view($args, $request)
    {
        $issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
        $this->setupTemplate($request);
        $templateMgr = TemplateManager::getManager($request);
        $journal = $request->getJournal();

        if (($galley = $this->getGalley()) && $this->userCanViewGalley($request)) {
            if (!HookRegistry::call('IssueHandler::view::galley', [&$request, &$issue, &$galley])) {
                $request->redirect(null, null, 'download', [$issue->getBestIssueId($journal), $galley->getBestGalleyId($journal)]);
            }
        } else {
            self::_setupIssueTemplate($request, $issue, $request->getUserVar('showToc') ? true : false);
            $templateMgr->assign('issueId', $issue->getBestIssueId());

            // consider public identifiers
            $pubIdPlugins = PluginRegistry::loadCategory('pubIds', true);
            $templateMgr->assign('pubIdPlugins', $pubIdPlugins);
            $templateMgr->display('frontend/pages/issue.tpl');
        }
    }

    /**
     * Display the issue archive listings
     *
     * @param array $args
     * @param PKPRequest $request
     */
    public function archive($args, $request)
    {
        $this->setupTemplate($request);
        $page = isset($args[0]) ? (int) $args[0] : 1;
        $templateMgr = TemplateManager::getManager($request);
        $context = $request->getContext();

        $count = $context->getData('itemsPerPage') ? $context->getData('itemsPerPage') : Config::getVar('interface', 'items_per_page');
        $offset = $page > 1 ? ($page - 1) * $count : 0;

        $collector = Repo::issue()->getCollector()
            ->limit($count)
            ->offset($offset)
            ->filterByContextIds([$context->getId()])
            ->orderBy(Collector::ORDERBY_SEQUENCE)
            ->filterByPublished(true);

        $issues = Repo::issue()->getMany($collector)->toArray();
        $total = Repo::issue()->getCount($collector->limit(null)->offset(null));

        $showingStart = $offset + 1;
        $showingEnd = min($offset + $count, $offset + count($issues));
        $nextPage = $total > $showingEnd ? $page + 1 : null;
        $prevPage = $showingStart > 1 ? $page - 1 : null;

        $templateMgr->assign([
            'issues' => $issues,
            'showingStart' => $showingStart,
            'showingEnd' => $showingEnd,
            'total' => $total,
            'nextPage' => $nextPage,
            'prevPage' => $prevPage,
        ]);

        $templateMgr->display('frontend/pages/issueArchive.tpl');
    }

    /**
     * Downloads an issue galley file
     *
     * @param array $args ($issueId, $galleyId)
     * @param Request $request
     */
    public function download($args, $request)
    {
        if ($this->userCanViewGalley($request)) {
            $issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
            $galley = $this->getGalley();

            if (!HookRegistry::call('IssueHandler::download', [&$issue, &$galley])) {
                $issueFileManager = new IssueFileManager($issue->getId());
                return $issueFileManager->downloadById($galley->getFileId(), $request->getUserVar('inline') ? true : false);
            }
        }
    }

    /**
     * Get the retrieved issue galley
     *
     * @return IssueGalley
     */
    public function getGalley()
    {
        return $this->_galley;
    }

    /**
     * Set a retrieved issue galley
     *
     * @param IssueGalley $galley
     */
    public function setGalley($galley)
    {
        $this->_galley = $galley;
    }

    /**
     * Determines whether or not a user can view an issue galley.
     *
     * @param Request $request
     */
    public function userCanViewGalley($request)
    {
        $issueAction = new IssueAction();

        $journal = $request->getJournal();
        $user = $request->getUser();
        $userId = $user ? $user->getId() : 0;
        $issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
        $galley = $this->getGalley();

        // If this is an editorial user who can view unpublished issue galleys,
        // bypass further validation
        if ($issueAction->allowedIssuePrePublicationAccess($journal, $user)) {
            return true;
        }

        // Ensure reader has rights to view the issue galley
        if ($issue->getPublished()) {
            $subscriptionRequired = $issueAction->subscriptionRequired($issue, $journal);
            $isSubscribedDomain = $issueAction->subscribedDomain($request, $journal, $issue->getId());

            // Check if login is required for viewing.
            if (!$isSubscribedDomain && !Validation::isLoggedIn() && $journal->getData('restrictArticleAccess')) {
                Validation::redirectLogin();
            }

            // If no domain/ip subscription, check if user has a valid subscription
            // or if the user has previously purchased the issue
            if (!$isSubscribedDomain && $subscriptionRequired) {
                // Check if user has a valid subscription
                $subscribedUser = $issueAction->subscribedUser($user, $journal, $issue->getId());
                if (!$subscribedUser) {
                    // Check if payments are enabled,
                    $paymentManager = Application::getPaymentManager($journal);

                    if ($paymentManager->purchaseIssueEnabled() || $paymentManager->membershipEnabled()) {
                        // If only pdf files are being restricted, then approve all non-pdf galleys
                        // and continue checking if it is a pdf galley
                        if ($paymentManager->onlyPdfEnabled() && !$galley->isPdfGalley()) {
                            return true;
                        }

                        if (!Validation::isLoggedIn()) {
                            Validation::redirectLogin('payment.loginRequired.forIssue');
                        }

                        // If the issue galley has been purchased, then allow reader access
                        $completedPaymentDao = DAORegistry::getDAO('OJSCompletedPaymentDAO'); /** @var OJSCompletedPaymentDAO $completedPaymentDao */
                        $dateEndMembership = $user->getSetting('dateEndMembership', 0);
                        if ($completedPaymentDao->hasPaidPurchaseIssue($userId, $issue->getId()) || (!is_null($dateEndMembership) && $dateEndMembership > time())) {
                            return true;
                        } else {
                            // Otherwise queue an issue purchase payment and display payment form
                            $queuedPayment = $paymentManager->createQueuedPayment($request, OJSPaymentManager::PAYMENT_TYPE_PURCHASE_ISSUE, $userId, $issue->getId(), $journal->getData('purchaseIssueFee'));
                            $paymentManager->queuePayment($queuedPayment);

                            $paymentForm = $paymentManager->getPaymentForm($queuedPayment);
                            $paymentForm->display($request);
                            exit;
                        }
                    }

                    if (!Validation::isLoggedIn()) {
                        Validation::redirectLogin('reader.subscriptionRequiredLoginText');
                    }
                    $request->redirect(null, 'about', 'subscriptions');
                }
            }
        } else {
            $request->redirect(null, 'index');
        }
        return true;
    }

    /**
     * Given an issue, set up the template with all the required variables for
     * frontend/objects/issue_toc.tpl to function properly (i.e. current issue
     * and view issue).
     *
     * @param object $issue The issue to display
     * @param bool $showToc iff false and a custom cover page exists,
     * 	the cover page will be displayed. Otherwise table of contents
     * 	will be displayed.
     */
    public static function _setupIssueTemplate($request, $issue, $showToc = false)
    {
        $journal = $request->getJournal();
        $user = $request->getUser();
        $templateMgr = TemplateManager::getManager($request);

        // Determine pre-publication access
        // FIXME: Do that. (Bug #8278)

        $templateMgr->assign([
            'issueIdentification' => $issue->getIssueIdentification(),
            'issueTitle' => $issue->getLocalizedTitle(),
            'issueSeries' => $issue->getIssueIdentification(['showTitle' => false]),
        ]);

        $locale = Locale::getLocale();

        $templateMgr->assign([
            'locale' => $locale,
        ]);

        $issueGalleyDao = DAORegistry::getDAO('IssueGalleyDAO'); /** @var IssueGalleyDAO $issueGalleyDao */

        $genreDao = DAORegistry::getDAO('GenreDAO'); /** @var GenreDAO $genreDao */
        $primaryGenres = $genreDao->getPrimaryByContextId($journal->getId())->toArray();
        $primaryGenreIds = array_map(function ($genre) {
            return $genre->getId();
        }, $primaryGenres);

        // Show scheduled submissions if this is a preview
        $allowedStatuses = [PKPSubmission::STATUS_PUBLISHED];
        if (!$issue->getPublished()) {
            $allowedStatuses[] = PKPSubmission::STATUS_SCHEDULED;
        }

        $collector = Repo::submission()->getCollector();
        $issueSubmissions = Repo::submission()->getMany(
            $collector
                ->filterByContextIds([$issue->getJournalId()])
                ->filterByIssueIds([$issue->getId()])
                ->filterByStatus($allowedStatuses)
                ->orderBy($collector::ORDERBY_SEQUENCE, $collector::ORDER_DIR_ASC)
        );
        $sections = Application::get()->getSectionDao()->getByIssueId($issue->getId());
        $issueSubmissionsInSection = [];
        foreach ($sections as $section) {
            $issueSubmissionsInSection[$section->getId()] = [
                'title' => $section->getHideTitle() ? null : $section->getLocalizedTitle(),
                'hideAuthor' => $section->getHideAuthor(),
                'articles' => [],
            ];
        }
        foreach ($issueSubmissions as $submission) {
            if (!$sectionId = $submission->getCurrentPublication()->getData('sectionId')) {
                continue;
            }
            $issueSubmissionsInSection[$sectionId]['articles'][] = $submission;
        }

        $templateMgr->assign([
            'issue' => $issue,
            'issueGalleys' => $issueGalleyDao->getByIssueId($issue->getId()),
            'publishedSubmissions' => $issueSubmissionsInSection,
            'primaryGenreIds' => $primaryGenreIds,
        ]);

        // Subscription Access
        $issueAction = new IssueAction();
        $subscriptionRequired = $issueAction->subscriptionRequired($issue, $journal);
        $subscribedUser = $issueAction->subscribedUser($user, $journal);
        $subscribedDomain = $issueAction->subscribedDomain($request, $journal);

        if ($subscriptionRequired && !$subscribedUser && !$subscribedDomain) {
            $templateMgr->assign('subscriptionExpiryPartial', true);

            // Partial subscription expiry for issue
            $partial = $issueAction->subscribedUser($user, $journal, $issue->getId());
            if (!$partial) {
                $issueAction->subscribedDomain($request, $journal, $issue->getId());
            }
            $templateMgr->assign('issueExpiryPartial', $partial);

            // Partial subscription expiry for articles
            $articleExpiryPartial = [];
            foreach ($issueSubmissions as $issueSubmission) {
                $partial = $issueAction->subscribedUser($user, $journal, $issue->getId(), $issueSubmission->getId());
                if (!$partial) {
                    $issueAction->subscribedDomain($request, $journal, $issue->getId(), $issueSubmission->getId());
                }
                $articleExpiryPartial[$issueSubmission->getId()] = $partial;
            }
            $templateMgr->assign('articleExpiryPartial', $articleExpiryPartial);
        }

        $completedPaymentDao = DAORegistry::getDAO('OJSCompletedPaymentDAO'); /** @var OJSCompletedPaymentDAO $completedPaymentDao */
        $templateMgr->assign([
            'hasAccess' => !$subscriptionRequired ||
                $issue->getAccessStatus() == \APP\issue\Issue::ISSUE_ACCESS_OPEN ||
                $subscribedUser || $subscribedDomain ||
                ($user && $completedPaymentDao->hasPaidPurchaseIssue($user->getId(), $issue->getId()))
        ]);

        $paymentManager = Application::getPaymentManager($journal);
        if ($paymentManager->onlyPdfEnabled()) {
            $templateMgr->assign('restrictOnlyPdf', true);
        }
        if ($paymentManager->purchaseArticleEnabled()) {
            $templateMgr->assign('purchaseArticleEnabled', true);
        }
    }
}
