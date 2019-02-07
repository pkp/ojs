<?php

/**
 * @file pages/oai/OAIHandler.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OAIHandler
 * @ingroup pages_oai
 *
 * @brief Handle OAI protocol requests.
 */

define('SESSION_DISABLE_INIT', 1); // FIXME?

import('classes.oai.ojs.JournalOAI');
import('classes.handler.Handler');

use \Firebase\JWT\JWT;

class OAIHandler extends Handler {

	/**
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function index($args, $request) {
		$this->validate($request);

		PluginRegistry::loadCategory('oaiMetadataFormats', true);

		$oai = new JournalOAI(new OAIConfig($request->url(null, 'oai'), Config::getVar('oai', 'repository_id')));
		if (!$request->getJournal() && $request->getRequestedJournalPath() != 'index') {
			$dispatcher = $request->getDispatcher();
			return $dispatcher->handle404();
		}
		$oai->execute();
	}

	/**
	 * Validate the request
	 * @param $request PKPRequest
	 */
	function validate($request) {
		// Site validation checks not applicable
		//parent::validate();

		if (!Config::getVar('oai', 'oai')) {
			$request->redirect(null, 'index');
		}

		// Permit the use of the Authorization header and an API key for access to unpublished content (article URLs)
		if ($header = array_search('Authorization', array_flip(getallheaders()))) {
			list($bearer, $jwt) = explode(' ', $header);
			if (strcasecmp($bearer, 'Bearer')==0) {
				$apiToken = json_decode(JWT::decode($jwt, Config::getVar('security', 'api_key_secret', ''), array('HS256')));
				$this->setApiToken($apiToken);
			}
		}
	}

	/**
	 * @see PKPHandler::requireSSL()
	 */
	function requireSSL() {
		return false;
	}
}


