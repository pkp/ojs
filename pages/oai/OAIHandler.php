<?php

/**
 * @file pages/oai/OAIHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OAIHandler
 * @ingroup pages_oai
 *
 * @brief Handle OAI protocol requests.
 */

use APP\handler\Handler;
use APP\oai\ojs\JournalOAI;
use Firebase\JWT\JWT;
use PKP\oai\OAIConfig;
use PKP\session\SessionManager;

// Disable initializing the session
SessionManager::disable();

class OAIHandler extends Handler
{
    /**
     * @param array $args
     * @param PKPRequest $request
     */
    public function index($args, $request)
    {
        $this->validate(null, $request);

        PluginRegistry::loadCategory('oaiMetadataFormats', true);

        $oai = new JournalOAI(new OAIConfig($request->url(null, 'oai'), Config::getVar('oai', 'repository_id')));
        if (!$request->getJournal() && $request->getRequestedJournalPath() != 'index') {
            $dispatcher = $request->getDispatcher();
            return $dispatcher->handle404();
        }
        $oai->execute();
    }

    /**
     * @copydoc PKPHandler::validate()
     *
     * @param null|mixed $requiredContexts
     * @param null|mixed $request
     */
    public function validate($requiredContexts = null, $request = null)
    {
        // Site validation checks not applicable
        //parent::validate($requiredContexts, $request);

        if (!Config::getVar('oai', 'oai')) {
            $request->redirect(null, 'index');
        }

        // Permit the use of the Authorization header and an API key for access to unpublished content (article URLs)
        if ($header = array_search('Authorization', array_flip(getallheaders()))) {
            [$bearer, $jwt] = explode(' ', $header);
            if (strcasecmp($bearer, 'Bearer') == 0) {
                $apiToken = JWT::decode($jwt, Config::getVar('security', 'api_key_secret', ''), ['HS256']);
                // Compatibility with old API keys
                // https://github.com/pkp/pkp-lib/issues/6462
                if (substr($apiToken, 0, 2) === '""') {
                    $apiToken = json_decode($apiToken);
                }
                $this->setApiToken($apiToken);
            }
        }
    }

    /**
     * @see PKPHandler::requireSSL()
     */
    public function requireSSL()
    {
        return false;
    }
}
