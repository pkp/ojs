<?php

namespace APP\orcid\actions;

use APP\author\Author;
use APP\core\Application;
use APP\core\Request;
use APP\facades\Repo;
use APP\orcid\OrcidManager;
use APP\template\TemplateManager;
use Carbon\Carbon;
use GuzzleHttp\Exception\GuzzleException;
use PKP\submission\PKPSubmission;

class VerifyAuthorWithOrcid
{
    public function __construct(
        private Author $author,
        private Request $request,
        private array $templateVarsToSet = []
    ) {
    }

    public function execute(): self
    {
        $context = $this->request->getContext();

        // Fetch the access token
        $oauthTokenUrl = OrcidManager::getApiPath($context) . OrcidManager::OAUTH_TOKEN_URL;

        $httpClient = Application::get()->getHttpClient();
        $headers = ['Accept' => 'application/json'];
        $postData = [
            'code' => $this->request->getUserVar('code'),
            'grant_type' => 'authorization_code',
            'client_id' => OrcidManager::getClientId($context),
            'client_secret' => OrcidManager::getClientSecret($context)
        ];

        OrcidManager::logInfo('POST ' . $oauthTokenUrl);
        OrcidManager::logInfo('Request headers: ' . var_export($headers, true));
        OrcidManager::logInfo('Request body: ' . http_build_query($postData));

        try {
            $response = $httpClient->request(
                'POST',
                $oauthTokenUrl,
                [
                    'headers' => $headers,
                    'form_params' => $postData,
                    'allow_redirects' => ['strict' => true],
                ],
            );

            if ($response->getStatusCode() !== 200) {
                OrcidManager::logError('VerifyAuthorWithOrcid::execute - unexpected response: ' . $response->getStatusCode());
                $this->addTemplateVar('authFailure', true);
            }
            $results = json_decode($response->getBody(), true);

            // Check for errors
            OrcidManager::logInfo('Response body: ' . print_r($results, true));
            if (($results['error'] ?? null) === 'invalid_grant') {
                OrcidManager::logError('Authorization code invalid, maybe already used');
                $this->addTemplateVar('authFailure', true);
            }
            if (isset($results['error'])) {
                OrcidManager::logError('Invalid ORCID response: ' . $results['error']);
                $this->addTemplateVar('authFailure', true);
            }

            // Check for duplicate ORCID for author
            $orcidUri = OrcidManager::getOrcidUrl($context) . $results['orcid'];
            if (!empty($this->author->getOrcid()) && $orcidUri !== $this->author->getOrcid()) {
                $this->addTemplateVar('duplicateOrcid', true);
            }
            $this->addTemplateVar('orcid', $orcidUri);

            $this->author->setOrcid($orcidUri);
            if (OrcidManager::isSandbox($context)) {
                $this->author->setData('orcidEmailToken', null);
            }
            $this->setOrcidAccessData($orcidUri, $results);
            Repo::author()->dao->update($this->author);

            // Send member submissions to ORCID
            if (OrcidManager::isMemberApiEnabled($context)) {
                $publicationId = $this->request->getUserVar('state');
                $publication = Repo::publication()->get($publicationId);

                if ($publication->getData('status') == PKPSubmission::STATUS_PUBLISHED) {
                    $this->addTemplateVar('sendSubmission', true);
                // TODO: Sort out sending
                //                    $sendResult = $this->plugin->sendSubmissionToOrcid($publication, $request);
                //                    if ($sendResult === true || (is_array($sendResult) && $sendResult[$response['orcid']])) {
                //                        $this->addTemplateVar('sendSubmissionSuccess', true);
                //                    }
                } else {
                    $this->addTemplateVar('submissionNotPublished', true);
                }
            }

            $this->addTemplateVar('verifySuccess', true);
            $this->addTemplateVar('orcidIcon', OrcidManager::getIcon());
        } catch (GuzzleException $exception) {
            OrcidManager::logError('Publication fail: ' . $exception->getMessage());
            $this->addTemplateVar('orcidAPIError', $exception->getMessage());
        }

        // TODO: I don't think this should be happening here
        $this->addTemplateVar('authFailure', true);
        return $this;
    }

    public function updateTemplateMgrVars(TemplateManager &$templateMgr): void
    {
        foreach ($this->templateVarsToSet as $key => $value) {
            $templateMgr->assign($key, $value);
        }
    }

    private function setOrcidAccessData(string $orcidUri, array $results): void
    {
        // Save the access token
        $orcidAccessExpiresOn = Carbon::now();
        // expires_in field from the response contains the lifetime in seconds of the token
        // See https://members.orcid.org/api/get-oauthtoken
        $orcidAccessExpiresOn->addSeconds($results['expires_in']);
        $this->author->setOrcid($orcidUri);
        // remove the access denied marker, because now the access was granted
        $this->author->setData('orcidAccessDenied', null);
        $this->author->setData('orcidAccessToken', $results['access_token']);
        $this->author->setData('orcidAccessScope', $results['scope']);
        $this->author->setData('orcidRefreshToken', $results['refresh_token']);
        $this->author->setData('orcidAccessExpiresOn', $orcidAccessExpiresOn->toDateTimeString());

    }

    private function addTemplateVar(string $key, mixed $value): void
    {
        $this->templateVarsToSet[$key] = $value;
    }
}
