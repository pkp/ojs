<?php

/**
 * @file plugins/generic/usageEvent/UsageEventPlugin.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UsageEventPlugin
 * @ingroup plugins_generic_usageEvent
 *
 * @brief Provide usage event to other statistics plugins.
 */

import('lib.pkp.classes.plugins.GenericPlugin');

// Our own and OA-S classification types.
define('USAGE_EVENT_PLUGIN_CLASSIFICATION_BOT', 'bot');
define('USAGE_EVENT_PLUGIN_CLASSIFICATION_ADMIN', 'administrative');

class UsageEventPlugin extends GenericPlugin {

	//
	// Implement methods from Plugin.
	//
	/**
	 * @copydoc Plugin::register()
	 */
	function register($category, $path) {
		if (!parent::register($category, $path)) return false;

		// Register callbacks.
		HookRegistry::register('TemplateManager::display', array($this, 'getUsageEvent'));
		HookRegistry::register('ArticleHandler::viewFile', array($this, 'getUsageEvent'));
		HookRegistry::register('ArticleHandler::downloadFile', array($this, 'getUsageEvent'));
		HookRegistry::register('IssueHandler::viewFile', array($this, 'getUsageEvent'));
		HookRegistry::register('FileManager::downloadFileFinished', array($this, 'getUsageEvent'));
		return true;
	}

	/**
	 * @copydoc Plugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.generic.usageEvent.displayName');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.generic.usageEvent.description');
	}

	/**
	 * @copydoc Plugin::getCanDisable()
	 */
	function getCanDisable() {
		return false;
	}

	/**
	 * @copydoc Plugin::getEnabled()
	 */
	function getEnabled() {
		return true;
	}

	/**
	* @copydoc Plugin::isSitePlugin()
	*/
	function isSitePlugin() {
		return true;
	}


	//
	// Public methods.
	//
	/**
	 * Get the unique site id.
	 * @return mixed string or null
	 */
	function getUniqueSiteId() {
		return $this->getSetting(CONTEXT_SITE, 'uniqueSiteId');
	}


	//
	// Hook implementations.
	//
	/**
	 * Get usage event and pass it to the registered plugins, if any.
	 */
	function getUsageEvent($hookName, $args) {
		// Check if we have a registration to receive the usage event.
		$hooks = HookRegistry::getHooks();
		if (array_key_exists('UsageEventPlugin::getUsageEvent', $hooks)) {

			$usageEvent = $this->_buildUsageEvent($hookName, $args);
			HookRegistry::call('UsageEventPlugin::getUsageEvent', array_merge(array($hookName, $usageEvent), $args));
		}
		return false;
	}


	//
	// Private helper methods.
	//
	/**
	 * Build an usage event.
	 * @param $hookName string
	 * @param $args array
	 * @return array
	 */
	private function _buildUsageEvent($hookName, $args) {
		// Finished downloading a file?
		if ($hookName == 'FileManager::downloadFileFinished') {
			// The usage event for this request is already build and
			// passed to any other registered hook.
			return null;
		}

		$request = $this->getRequest();
		$router = $request->getRouter(); /* @var $router PageRouter */
		$templateMgr = $args[0]; /* @var $templateMgr TemplateManager */

		// We are just interested in page requests.
		if (!is_a($router, 'PageRouter')) return false;

		// Check whether we are in journal context.
		$journal = $router->getContext($request);
		if (!$journal) return false;

		// Prepare request information.
		$downloadSuccess = false;
		switch ($hookName) {

			// Article abstract, HTML galley and remote galley.
			case 'TemplateManager::display':
				// We are interested in access to the article abstract/galley and issue view page.
				$page = $router->getRequestedPage($request);
				$op = $router->getRequestedOp($request);
				$wantedPages = array('article', 'issue');
				$wantedOps = array('view', 'articleView');

				if (!in_array($page, $wantedPages) || !in_array($op, $wantedOps)) return false;

				$issue = $templateMgr->get_template_vars('issue');
				$galley = $templateMgr->get_template_vars('galley'); /* @var $galley ArticleGalley */
				$article = $templateMgr->get_template_vars('article');
				if ($galley && is_a($galley, 'ArticleGalley')) {
					if ($galley->isHTMLGalley() || $galley->getRemoteURL()) {
						$pubObject = $galley;
						$assocType = ASSOC_TYPE_GALLEY;
						$canonicalUrlParams = array($article->getBestArticleId(), $pubObject->getBestGalleyId($journal));
						$idParams = array('a' . $article->getId(), 'g' . $pubObject->getId());
					} else {
						// This is an access to an intermediary galley page which we
						// do not count.
						return false;
					}
				} else {
					if ($article) {
						$pubObject = $article;
						$assocType = ASSOC_TYPE_ARTICLE;
						$canonicalUrlParams = array($pubObject->getBestArticleId($journal));
						$idParams = array('a' . $pubObject->getId());
					} else {
						$pubObject = $issue;
						$assocType = ASSOC_TYPE_ISSUE;
						$canonicalUrlParams = array($pubObject->getBestIssueId($journal));
						$idParams = array('i' . $pubObject->getId());
					}
				}
				// The article, issue and HTML/remote galley pages do not download anything.
				$downloadSuccess = true;
				$canonicalUrlOp = 'view';
				break;

			// Article galley (except for HTML and remote galley).
			case 'ArticleHandler::viewFile':
			case 'ArticleHandler::downloadFile':
				$pubObject = $args[1];
				$assocType = ASSOC_TYPE_GALLEY;
				$canonicalUrlOp = 'download';
				$article = $args[0];
				$canonicalUrlParams = array($article->getBestArticleId(), $pubObject->getBestGalleyId($journal));
				$idParams = array('a' . $article->getId(), 'g' . $pubObject->getId());
				break;

			// Issue galley.
			case 'IssueHandler::viewFile':
				$pubObject = $args[1];
				$assocType = ASSOC_TYPE_ISSUE_GALLEY;
				$canonicalUrlOp = 'download';
				$issue = $args[0];
				$canonicalUrlParams = array($issue->getBestIssueId(), $pubObject->getBestGalleyId($journal));
				$idParams = array('i' . $issue->getId(), 'ig' . $pubObject->getId());
				break;

			default:
				$assocType = $pubObject = $canonicalUrlOp = $canonicalUrlParams = null; // Suppress scrutinizer warn
				// Why are we called from an unknown hook?
				assert(false);
		}

		// Timestamp.
		$time = Core::getCurrentDate();

		// Actual document size, MIME type.
		if ($assocType == ASSOC_TYPE_ARTICLE || $assocType == ASSOC_TYPE_ISSUE) {
			// Article abstract or issue view page.
			$docSize = 0;
			$mimeType = 'text/html';
		} else {
			// Files.
			$docSize = (int)$pubObject->getFileSize();
			$mimeType = $pubObject->getFileType();
		}

		// Canonical URL.
		if ($assocType == ASSOC_TYPE_ISSUE_GALLEY || $assocType == ASSOC_TYPE_ISSUE) {
			$canonicalUrlPage = 'issue';
		} else {
			$canonicalUrlPage = 'article';
		}
		$canonicalUrl = $router->url(
			$request, null, $canonicalUrlPage, $canonicalUrlOp, $canonicalUrlParams
		);

		// Public identifiers.
		// 1) A unique OJS-internal ID that will help us to easily attribute
		//    statistics to a specific publication object.
		array_unshift($idParams, 'j' . $journal->getId());
		$siteId = $this->getUniqueSiteId();
		if (empty($siteId)) {
			// Create a globally unique, persistent site ID
			// so that we can uniquely identify publication
			// objects from this site, even if the URL or any
			// other externally influenced information changes.
			$siteId = uniqid();
			$this->updateSetting(0, 'uniqueSiteId', $siteId);
		}
		array_unshift($idParams, $siteId);
		$ojsId = 'ojs:' . implode('-', $idParams);
		$identifiers = array('other::ojs' => $ojsId);

		// 2) Standardized public identifiers, e.g. DOI, URN, etc.
		if (!is_a($pubObject, 'IssueGalley')) {
			$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true, $journal->getId());
			if (is_array($pubIdPlugins)) {
				foreach ($pubIdPlugins as $pubIdPlugin) {
					if (!$pubIdPlugin->getEnabled()) continue;
					$pubId = $pubIdPlugin->getPubId($pubObject);
					if ($pubId) {
						$identifiers[$pubIdPlugin->getPubIdType()] = $pubId;
					}
				}
			}
		}

		// Service URI.
		$serviceUri = $router->url($request, $journal->getPath());

		// IP and Host.
		$ip = $request->getRemoteAddr();
		$host = null;
		if (isset($_SERVER['REMOTE_HOST'])) {
			// We do NOT actively look up the remote host to
			// avoid the performance penalty. We only set the remote
			// host if we get it "for free".
			$host = $_SERVER['REMOTE_HOST'];
		}

		// HTTP user agent.
		$userAgent = $request->getUserAgent();

		// HTTP referrer.
		$referrer = (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null);

		// User and roles.
		$user = $request->getUser();
		$roles = array();
		if ($user) {
			$roleDao = DAORegistry::getDAO('RoleDAO');
			$rolesByContext = $roleDao->getByUserIdGroupedByContext($user->getId());
			foreach (array(CONTEXT_SITE, $journal->getId()) as $context) {
				if(isset($rolesByContext[$context])) {
					foreach ($rolesByContext[$context] as $role) {
						$roles[] = $role->getRoleId();
					}
				}
			}
		}

		// Try a simple classification of the request.
		$classification = null;
		if (!empty($roles)) {
			// Access by editors, authors, etc.
			$internalRoles = array_diff($roles, array(ROLE_ID_READER));
			if (!empty($internalRoles)) {
				$classification = USAGE_EVENT_PLUGIN_CLASSIFICATION_ADMIN;
			}
		}
		if ($request->isBot()) {
			// The bot classification overwrites other classifications.
			$classification = USAGE_EVENT_PLUGIN_CLASSIFICATION_BOT;
		}
		// TODO: Classify LOCKSS or similar as 'internal' access.

		/*
		 * Comparison of our event log format with Apache log parameters...
		*
		* 1) default parameters:
		* %h: remote hostname or IP => $ip, $host
		* %l: remote logname (identd) => not supported, see $user, $roles instead
		* %u: remote user => not supported, see $user, $roles instead
		* %t: request time => $time
		* %r: query => derived objects: $pubObject, $assocType, $canonicalUrl, $identifiers, $serviceUri, $classification
		* %s: status => not supported (always 200 in our case)
		* %b: response size => $docSize
		*
		* 2) other common parameters
		* %O: bytes sent => not supported (cannot be reliably determined from within PHP)
		* %X: connection status => $downloadSuccess (not reliable!)
		* %{ContentType}o: => $mimeType
		* %{User-agent}i: => $userAgent
		* %{Referer}i: => $referrer
		*
		* Several items, e.g. time etc., may differ from what Apache
		* would actually log. But the differences do not matter for our use
		* cases.
		*/

		// Collect all information into an array.
		$usageEvent = compact(
			'time', 'pubObject', 'assocType', 'canonicalUrl', 'mimeType',
			'identifiers', 'docSize', 'downloadSuccess', 'serviceUri',
			'ip', 'host', 'user', 'roles', 'userAgent', 'referrer',
			'classification'
		);

		return $usageEvent;
	}

}

?>
