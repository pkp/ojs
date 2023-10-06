<?php

namespace APP\pages\orcid;

use APP\author\Author;
use APP\core\Application;
use APP\core\Request;
use APP\facades\Repo;
use APP\handler\Handler;
use APP\orcid\actions\AuthorizeUserData;
use APP\orcid\actions\VerifyAuthorWithOrcid;
use APP\orcid\OrcidManager;
use APP\template\TemplateManager;
use PKP\config\Config;
use PKP\core\Core;
use PKP\security\authorization\PKPSiteAccessPolicy;
use PKP\security\authorization\UserRequiredPolicy;
use PKP\session\SessionManager;

class OrcidHandler extends Handler
{
    protected const VERIFY_TEMPLATE_PATH = 'frontend/pages/orcidVerify.tpl';
    protected const ABOUT_TEMPLATE_PATH = 'frontend/pages/orcidAbout.tpl';
    public function authorize($request, &$args, $roleAssignments)
    {
        // Authorize all requests
        $this->addPolicy(new PKPSiteAccessPolicy(
            $request,
            ['verify', 'authorizeOrcid', 'about'],
            PKPSiteAccessPolicy::SITE_ACCESS_ALL_ROLES
        ));

        $op = $request->getRequestedOp();
        $targetOp = $request->getUserVar('targetOp');
        if ($op === 'authorize' && in_array($targetOp, ['profile', 'submit'])) {
            // ... but user must be logged in for authorize with profile or submit
            $this->addPolicy(new UserRequiredPolicy($request));
        }

        if (!Application::isInstalled()) {
            SessionManager::disable();
        }

        $this->setEnforceRestrictedSite(false);
        return parent::authorize($request, $args, $roleAssignments);
    }

    public function verify(array $args, Request $request): void
    {
        // If the application is set to sandbox mode, it will not reach out to external services
        if (Config::getVar('general', 'sandbox', false)) {
            error_log('Application is set to sandbox mode and will not interact with the ORCID service');
            return;
        }

        $templateMgr = TemplateManager::getManager($request);

        // Initialise template parameters
        $templateMgr->assign([
            'currentUrl' => $request->url(null, 'index'),
            'verifySuccess' => false,
            'authFailure' => false,
            'notPublished' => false,
            'sendSubmission' => false,
            'sendSubmissionSuccess' => false,
            'denied' => false,
        ]);

        // Get the author
        $author = $this->getAuthorToVerify($request);

        if ($author === null) {
            $this->handleNoAuthorWithToken($templateMgr);
        } elseif ($request->getUserVar('error') === 'access_denied') {
            // Handle access denied
            $this->handleUserDeniedAccess($author, $templateMgr, $request->getUserVar('error_description'));
        }

        (new VerifyAuthorWithOrcid($author, $request))->execute()->updateTemplateMgrVars($templateMgr);

        $templateMgr->display(self::VERIFY_TEMPLATE_PATH);
    }

    public function authorizeOrcid(array $args, Request $request): void
    {
        // If the application is set to sandbox mode, it will not reach out to external services
        if (Config::getVar('general', 'sandbox', false)) {
            error_log('Application is set to sandbox mode and will not interact with the ORCID service');
            return;
        }

        (new AuthorizeUserData($request))->execute();
    }

    public function about(array $args, Request $request): void
    {
        $context = $request->getContext();
        $templateMgr = TemplateManager::getManager($request);

        $templateMgr->assign('orcidIcon', OrcidManager::getIcon());
        $templateMgr->assign('isMemberApi', OrcidManager::isMemberApiEnabled($context));
        $templateMgr->display(self::ABOUT_TEMPLATE_PATH);

    }

    private function getAuthorToVerify(Request $request): ?Author
    {
        $publicationId = $request->getUserVar('state');
        $authors = Repo::author()
            ->getCollector()
            ->filterByPublicationIds([$publicationId])
            ->getMany();

        $authorToVerify = null;
        // Find the author entry, for which the ORCID verification was requested
        if ($request->getUserVar('token')) {
            foreach ($authors as $author) {
                if ($author->getData('orcidEmailToken') == $request->getUserVar('token')) {
                    $authorToVerify = $author;
                }
            }
        }

        return $authorToVerify;
    }

    private function handleNoAuthorWithToken(TemplateManager $templateMgr): void
    {
        OrcidManager::logError('OrcidHandler::verify = No author found with supplied token');
        $templateMgr->assign('verifySuccess', false);
    }

    private function handleUserDeniedAccess(Author $author, TemplateManager $templateMgr, string $errorDescription): void
    {
        // User denied access
        // Store the date time the author denied ORCID access to remember this
        $author->setData('orcidAccessDenied', Core::getCurrentDate());
        // remove all previously stored ORCID access token
        $author->setData('orcidAccessToken', null);
        $author->setData('orcidAccessScope', null);
        $author->setData('orcidRefreshToken', null);
        $author->setData('orcidAccessExpiresOn', null);
        $author->setData('orcidEmailToken', null);
        Repo::author()->dao->update($author);
        OrcidManager::logError('OrcidHandler::verify - ORCID access denied. Error description: ' . $errorDescription);
        $templateMgr->assign('denied', true);
    }
}
