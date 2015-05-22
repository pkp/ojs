<?php

/**
 * @file plugins/generic/usageEvent/UsageEventPlugin.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
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
	// Implement methods from PKPPlugin.
	//
	/**
	* @see LazyLoadPlugin::register()
	*/
	function register($category, $path) {
		$success = parent::register($category, $path);

		if ($success) {
			// Register callbacks.
			HookRegistry::register('TemplateManager::display', array($this, 'getUsageEvent'));
			HookRegistry::register('ArticleHandler::viewFile', array($this, 'getUsageEvent'));
			HookRegistry::register('ArticleHandler::viewRemoteGalley', array($this, 'getUsageEvent'));
			HookRegistry::register('ArticleHandler::downloadFile', array($this, 'getUsageEvent'));
			HookRegistry::register('ArticleHandler::downloadSuppFile', array($this, 'getUsageEvent'));
			HookRegistry::register('IssueHandler::viewFile', array($this, 'getUsageEvent'));
			HookRegistry::register('FileManager::downloadFileFinished', array($this, 'getUsageEvent'));
		}

		return $success;
	}

	/**
	 * @see PKPPlugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.generic.usageEvent.displayName');
	}

	/**
	 * @see PKPPlugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.generic.usageEvent.description');
	}

	/**
	 * @see LazyLoadPlugin::getEnabled()
	 */
	function getEnabled() {
		return true;
	}

	/**
	* @see PKPPlugin::isSitePlugin()
	*/
	function isSitePlugin() {
		return true;
	}

	/**
	 * @see GenericPlugin::getManagementVerbs()
	 */
	function getManagementVerbs() {
		return array();
	}


	//
	// Public methods.
	//
	/**
	 * Get the unique site id.
	 * @return mixed string or null
	 */
	function getUniqueSiteId() {
		return $this->getSetting(0, 'uniqueSiteId');
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
	function _buildUsageEvent($hookName, $args) {
		// Finished downloading a file?
		if ($hookName == 'FileManager::downloadFileFinished') {
			// The usage event for this request is already build and
			// passed to any other registered hook.
			return null;
		}

		$application =& Application::getApplication();
		$request =& $application->getRequest();
		$router =& $request->getRouter(); /* @var $router PageRouter */
		$templateMgr =& $args[0]; /* @var $templateMgr TemplateManager */

		// We are just interested in page requests.
		if (!is_a($router, 'PageRouter')) return false;

		// Check whether we are in journal context.
		$journal =& $router->getContext($request);
		if (!$journal) return false;

		// Prepare request information.
		$downloadSuccess = false;
		$idParams = array();
		$canonicalUrlParams = array();
		switch ($hookName) {

			// Article abstract and HTML galley.
			case 'TemplateManager::display':
				$page = $router->getRequestedPage($request);
				$op = $router->getRequestedOp($request);

				// First check for a journal index page view.
				if (($page == 'index' || empty($page)) && $op == 'index') {
					$pubObject =& $templateMgr->get_template_vars('currentJournal');
					if (is_a($pubObject, 'Journal')) {
						$assocType = ASSOC_TYPE_JOURNAL;
						$canonicalUrlOp = '';
						$downloadSuccess = true;
						break;
					} else {
						return false;
					}
				}

				// We are interested in access to the article abstract/galley, issue view page.
				$wantedPages = array('article', 'issue');
				$wantedOps = array('view', 'articleView');

				if (!in_array($page, $wantedPages) || !in_array($op, $wantedOps)) return false;

				$issue =& $templateMgr->get_template_vars('issue');
				$galley =& $templateMgr->get_template_vars('galley'); /* @var $galley ArticleGalley */
				$article =& $templateMgr->get_template_vars('article');

				// If there is no published object, there is no usage event.
				if (!$issue && !$galley && !$article) return false;

				if ($galley) {
					if ($galley->isHTMLGalley()) {
						$pubObject =& $galley;
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
						$pubObject =& $article;
						$assocType = ASSOC_TYPE_ARTICLE;
						$canonicalUrlParams = array($pubObject->getBestArticleId($journal));
						$idParams = array('a' . $pubObject->getId());
					} else {
						$pubObject =& $issue;
						$assocType = ASSOC_TYPE_ISSUE;
						$canonicalUrlParams = array($pubObject->getBestIssueId($journal));
						$idParams = array('i' . $pubObject->getId());
					}
				}
				// The article, issue and HTML/remote galley pages do not download anything.
				$downloadSuccess = true;
				$canonicalUrlOp = 'view';
				break;

			case 'ArticleHandler::viewRemoteGalley':
				$article =& $args[0];
				$pubObject =& $args[1];
				$assocType = ASSOC_TYPE_GALLEY;
				$canonicalUrlParams = array($article->getBestArticleId(), $pubObject->getBestGalleyId($journal));
				$idParams = array('a' . $article->getId(), 'g' . $pubObject->getId());
				$downloadSuccess = true;
				$canonicalUrlOp = 'view';
				break;
			// Article galley (except for HTML and remote galley).
			case 'ArticleHandler::viewFile':
			case 'ArticleHandler::downloadFile':
				$pubObject =& $args[1];
				$assocType = ASSOC_TYPE_GALLEY;
				$canonicalUrlOp = 'download';
				$article =& $args[0];
				$canonicalUrlParams = array($article->getBestArticleId(), $pubObject->getBestGalleyId($journal));
				$idParams = array('a' . $article->getId(), 'g' . $pubObject->getId());
				break;

			// Supplementary file.
			case 'ArticleHandler::downloadSuppFile':
				$pubObject =& $args[1];
				$assocType = ASSOC_TYPE_SUPP_FILE;
				$canonicalUrlOp = 'downloadSuppFile';
				$article =& $args[0];
				$canonicalUrlParams = array($article->getBestArticleId(), $pubObject->getBestSuppFileId($journal));
				$idParams = array('a' . $article->getId(), 's' . $pubObject->getId());
				break;

			// Issue galley.
			case 'IssueHandler::viewFile':
				$pubObject =& $args[1];
				$assocType = ASSOC_TYPE_ISSUE_GALLEY;
				$canonicalUrlOp = 'download';
				$issue =& $args[0];
				$canonicalUrlParams = array($issue->getBestIssueId(), $pubObject->getBestGalleyId($journal));
				$idParams = array('i' . $issue->getId(), 'ig' . $pubObject->getId());
				break;

			default:
				// Why are we called from an unknown hook?
				assert(false);
		}

		// Timestamp.
		$time = Core::getCurrentDate();

		// Actual document size, MIME type.
		$htmlPageAssocTypes = array(ASSOC_TYPE_ARTICLE, ASSOC_TYPE_ISSUE, ASSOC_TYPE_JOURNAL);
		if (in_array($assocType, $htmlPageAssocTypes)) {
			// Article abstract or issue view page.
			$docSize = 0;
			$mimeType = 'text/html';
		} else {
			// Files.
			$docSize = (int)$pubObject->getFileSize();
			$mimeType = $pubObject->getFileType();
		}

		// Canonical URL.
		switch ($assocType) {
			case ASSOC_TYPE_ISSUE:
			case ASSOC_TYPE_ISSUE_GALLEY:
				$canonicalUrlPage = 'issue';
				break;
			case ASSOC_TYPE_ARTICLE:
			case ASSOC_TYPE_GALLEY:
			case ASSOC_TYPE_SUPP_FILE:
				$canonicalUrlPage = 'article';
				break;
			case ASSOC_TYPE_JOURNAL:
				$canonicalUrlPage = 'index';
				break;
		}

		$canonicalUrl = $router->url(
			$request, null, $canonicalUrlPage, $canonicalUrlOp, $canonicalUrlParams
		);

		// Make sure we log the server name and not aliases.
		$configBaseUrl = Config::getVar('general', 'base_url');
		$requestBaseUrl = $request->getBaseUrl();
		if ($requestBaseUrl !== $configBaseUrl) {
			// Make sure it's not an url override (no alias on that case).
			if (!in_array($requestBaseUrl, Config::getContextBaseUrls()) && 
					$requestBaseUrl !== Config::getVar('general', 'base_url[index]')) {
				// Alias found, replace it by base_url from config file.
				// Make sure we use the correct base url override value for the context, if any.
				$baseUrlReplacement = Config::getVar('general', 'base_url['.$journal->getPath().']');
				if (!$baseUrlReplacement) $baseUrlReplacement = $configBaseUrl;
				$canonicalUrl = str_replace($requestBaseUrl, $baseUrlReplacement, $canonicalUrl);
			}
		}

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
		if (!is_a($pubObject, 'IssueGalley') && !is_a($pubObject, 'Journal')) {
			$pubIdPlugins =& PluginRegistry::loadCategory('pubIds', true, $journal->getId());
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
		$user =& $request->getUser();
		$roles = array();
		if ($user) {
			$roleDao =& DAORegistry::getDAO('RoleDAO');
			$rolesByContext =& $roleDao->getByUserIdGroupedByContext($user->getId());
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
