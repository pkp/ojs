<?php

/**
 * @file plugins/generic/oas/OasPlugin.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OasPlugin
 * @ingroup plugins_generic_oas
 *
 * @brief OA-S plugin - turns OJS into a OA-S data provider.
 */


import('lib.pkp.classes.plugins.GenericPlugin');

// Our own and OA-S classification types.
define('OAS_PLUGIN_CLASSIFICATION_BOT', 'bot');
define('OAS_PLUGIN_CLASSIFICATION_ADMIN', 'administrative');

// Maximum time (in minutes) to stage usage events.
define('OAS_PLUGIN_MAX_STAGING_TIME', '15');

// Time interval (in minutes) between two SALT download attempts.
define('OAS_PLUGIN_SALT_URL', 'http://oas.sulb.uni-saarland.de/salt/salt_value.txt');
define('OAS_PLUGIN_SALT_DOWNLOAD_INTERVAL', '15');

class OasPlugin extends GenericPlugin {

	/** @var integer */
	var $_currentEventId;

	/** @var OasEventStagingDAO */
	var $_oasEventStagingDao;

	/** @var boolean */
	var $_optedOut = false;



	//
	// Implement template methods from PKPPlugin.
	//
	/**
	 * @see PKPPlugin::register()
	 */
	public function register($category, $path) {
		$success = parent::register($category, $path);
		if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) return $success;

		if($success && $this->getEnabled()) {
			// Register callbacks (application-level).
			HookRegistry::register('PluginRegistry::loadCategory', array($this, 'callbackLoadCategory'));
			HookRegistry::register('LoadHandler', array($this, 'callbackLoadHandler'));

			// Register callbacks (controller-level).
			HookRegistry::register('TemplateManager::display', array($this, 'startUsageEvent'));
			HookRegistry::register('ArticleHandler::viewFile', array($this, 'startUsageEvent'));
			HookRegistry::register('ArticleHandler::downloadFile', array($this, 'startUsageEvent'));
			HookRegistry::register('ArticleHandler::downloadSuppFile', array($this, 'startUsageEvent'));
			HookRegistry::register('IssueHandler::viewFile', array($this, 'startUsageEvent'));
			HookRegistry::register('FileManager::downloadFileFinished', array($this, 'endUsageEvent'));
		}
		return $success;
	}

	/**
	 * @see PKPPlugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.generic.oas.displayName');
	}

	/**
	 * @see PKPPlugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.generic.oas.description');
	}

	/**
	 * @see PKPPlugin::getInstallSchemaFile()
	 */
	function getInstallSchemaFile() {
		return $this->getPluginPath() . '/schema.xml';
	}

	/**
	 * @see PKPPlugin::isSitePlugin()
	 */
	function isSitePlugin() {
		return true;
	}

	/**
	 * @see PKPPlugin::getInstallSitePluginSettingsFile()
	 */
	function getInstallSitePluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * @see PKPPlugin::getTemplatePath()
	 */
	function getTemplatePath() {
		return parent::getTemplatePath() . 'templates/';
	}


	//
	// Implement template methods from GenericPlugin.
	//
	/**
	 * @see GenericPlugin::getManagementVerbs()
	 */
	function getManagementVerbs() {
		$verbs = parent::getManagementVerbs();
		if ($this->getEnabled()) {
			$verbs[] = array('settings', __('plugins.generic.oas.settings'));
		}
		return $verbs;
	}

 	/**
	 * @see PKPPlugin::manage()
	 */
	function manage($verb, $args, &$message, &$messageParams, &$pluginModalContent = null) {
		if (!parent::manage($verb, $args, $message, $messageParams)) return false;
		$request = $this->getRequest();

		switch ($verb) {
			case 'settings':
				// Prepare the template manager.
				$templateMgr = TemplateManager::getManager($request);
				$templateMgr->register_function('plugin_url', array($this, 'smartyPluginUrl'));

				// Instantiate the settings form.
				$this->import('classes.form.OasSettingsForm');
				$form = new OasSettingsForm($this);

				if ($request->getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						$request->redirect(null, 'manager', 'plugins', 'generic');
						return false;
					} else {
						$this->_setBreadCrumbs();
						$form->display($request);
					}
				} else {
					$this->_setBreadCrumbs();
					$form->initData();
					$form->display($request);
				}
				return true;

			default:
				// Unknown management verb
				assert(false);
				return false;
		}
	}


	//
	// Application level hook implementations.
	//
	/**
	 * @see PluginRegistry::loadCategory()
	 */
	function callbackLoadCategory($hookName, $args) {
		// Instantiate contributed plugins.
		$plugin = null;
		$category = $args[0];
		switch ($category) {
			case 'reports':
				$this->import('OasReportPlugin');
				$plugin = new OasReportPlugin();
				break;

			case 'blocks':
				$this->import('OasOptoutBlockPlugin');
				$plugin = new OasOptoutBlockPlugin($this->getName());
				break;
		}

		// Register contributed plugins (by reference).
		if ($plugin) {
			$seq = $plugin->getSeq();
			$plugins =& $args[1];
			if (!isset($plugins[$seq])) $plugins[$seq] = array();
			$plugins[$seq][$plugin->getPluginPath()] = $plugin;
		}

		return false;
	}

	/**
	 * @see PKPPageRouter::route()
	 */
	function callbackLoadHandler($hookName, $args) {
		// Check the page.
		$page = $args[0];
		if ($page !== 'oas') return;

		// Check the operation.
		$op = $args[1];
		if ($op != 'privacyInformation') return;

		// Looks as if our handler had been requested.
		define('HANDLER_CLASS', 'OasHandler');
		define('OAS_PLUGIN_NAME', $this->getName());
		$handlerFile =& $args[2];
		$handlerFile = $this->getPluginPath() . '/' . 'OasHandler.inc.php';
	}


	//
	// Controller level hook implementations.
	//
	/**
	 * Start preparing a usage event.
	 *
	 * @param $hookName string
	 * @param $args array
	 */
	function startUsageEvent($hookName, $args) {
		$request = $this->getRequest();

		// Check (and renew) the statistics opt-out.
		if ($request->getCookieVar('oas-opt-out')) {
			// Renew the Opt-Out cookie if present.
			$request->setCookieVar('oas-opt-out', true, time() + 60*60*24*365);
			$this->_optedOut = true;
			return false;
		}

		// Check whether we are in journal context.
		$journal = $request->getJournal();
		if (!$journal) return false;

		// Prepare request information.
		$downloadSuccess = false;
		switch ($hookName) {

			// Article abstract, HTML galley and remote galley.
			case 'TemplateManager::display':
				$page = $request->getRequestedPage();
				$op = $request->getRequestedOp();
				$templateMgr = $args[0];

				// Display the privacy information whenever we are on
				// an article or issue page (i.e. whenever usage info
				// may be collected).
				if ($page == 'article' || $page == 'issue') {
					$templateMgr->assign('oasDisplayPrivacyInfo', true);
				}

				// We are only interested in access to the article abstract/galley view page.
				if ($page != 'article' || !($op == 'view' || $op == 'articleView')) return false;

				$galley = $templateMgr->get_template_vars('galley'); /* @var $galley ArticleGalley */
				$article = $templateMgr->get_template_vars('article');
				if ($galley) {
					if ($galley->isHTMLGalley() || $galley->getRemoteURL()) {
						$pubObject = $galley;
						$assocType = ASSOC_TYPE_GALLEY;
						$canonicalUrlParams = array($article->getId(), $pubObject->getBestGalleyId($journal));
					} else {
						// This is an access to an intermediary galley page which we
						// do not count.
						return false;
					}
				} else {
					$pubObject = $article;
					$assocType = ASSOC_TYPE_ARTICLE;
					$canonicalUrlParams = array($pubObject->getBestArticleId($journal));
				}
				// The article and HTML/remote galley pages do not download anything.
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
				$canonicalUrlParams = array($article->getId(), $pubObject->getBestGalleyId($journal));
				break;

			// Supplementary file.
			case 'ArticleHandler::downloadSuppFile':
				$pubObject = $args[1];
				$assocType = ASSOC_TYPE_SUPP_FILE;
				$canonicalUrlOp = 'downloadSuppFile';
				$article = $args[0];
				$canonicalUrlParams = array($article->getId(), $pubObject->getBestSuppFileId($journal));
				break;

			// Issue galley.
			case 'IssueHandler::viewFile':
				$pubObject = $args[1];
				$assocType = ASSOC_TYPE_ISSUE_GALLEY;
				$canonicalUrlOp = 'download';
				$issue = $args[0];
				$canonicalUrlParams = array($issue->getId(), $pubObject->getBestGalleyId($journal));
				break;

			default:
				// Why are we called from an unknown hook?
				assert(false);
		}

		// Timestamp.
		$time = Core::getCurrentDate();

		// Actual document size, MIME type.
		$router = $request->getRouter();
		if ($assocType == ASSOC_TYPE_ARTICLE) {
			// Article abstract.
			$docSize = 0;
			$mimeType = 'text/html';
		} else {
			// Files.
			$docSize = $pubObject->getFileSize();
			$mimeType = $pubObject->getFileType();
		}

		// Canonical URL.
		if ($assocType == ASSOC_TYPE_ISSUE) {
			$canonicalUrlPage = 'issue';
		} else {
			$canonicalUrlPage = 'article';
		}
		$canonicalUrl = $router->url(
			$request, null, $canonicalUrlPage, $canonicalUrlOp, $canonicalUrlParams
		);

		// Public identifiers.
		$identifiers = array();
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
		$referrer = $_SERVER['HTTP_REFERER'];

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
				$classification = OAS_PLUGIN_CLASSIFICATION_ADMIN;
			}
		}
		if ($request->isBot()) {
			// The bot classification overwrites other classifications.
			$classification = OAS_PLUGIN_CLASSIFICATION_BOT;
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

		// Prefetch the DAO so that it is available even after download finishes.
		// (OJS will clear the registry before downloading files.)
		$this->import('OasEventStagingDAO');
		$this->_oasEventStagingDao = new OasEventStagingDAO();
		// We don't really have to register the DAO but let's do it for the sake of consistency.
		DAORegistry::registerDAO('OasContextObjectDAO', $this->_oasEventStagingDao);

		// Check whether we have outstanding maintenance jobs.
		$salt = $this->_doMaintenance();

		// Stage the usage event.
		$this->_currentEventId = $this->_oasEventStagingDao->stageUsageEvent($usageEvent, $salt);
	}

	/**
	 * Finalize a usage event.
	 *
	 * @param $hookName string
	 * @param $args array
	 */
	function endUsageEvent($hookName, $args) {
		if ($this->_optedOut) {
			// The user opted out so do not collect any statistics.
			return false;
		}

		// Check whether we got the event DAO (should be the case
		// if our event flow is correct).
		assert($this->_oasEventStagingDao);
		if (!$this->_oasEventStagingDao) return;

		// Check whether the download finished on the
		// end user's side. (This won't work 100%
		// reliably if the response is buffered by the
		// web server but at least it shouldn't produce
		// false negatives.)
		if (connection_aborted()) {
			$downloadSuccess = false;
		} else {
			$downloadSuccess = $args[0];
		}

		// Update the usage event.
		$this->_oasEventStagingDao->setDownloadSuccess($this->_currentEventId, $downloadSuccess);
		return false;
	}


	//
	// Private helper methods
	//
	/**
	 * Automatic plugin maintenance:
	 * 1) event table maintenance
	 * 2) salt management
	 *
	 * @return string the current or updated SALT value
	 */
	function _doMaintenance() {
		$currentTs = time();

		// Event table maintenance.
		$lastEventTableMaintenanceTs = $this->getSetting(0, 'lastEventTableMaintenanceTs');
		if (($currentTs - OAS_PLUGIN_MAX_STAGING_TIME * 60) > $lastEventTableMaintenanceTs) {
			$this->_oasEventStagingDao->clearExpiredEvents($currentTs - OAS_PLUGIN_MAX_STAGING_TIME * 60);
			$this->updateSetting(0, 'lastEventTableMaintenanceTs', $currentTs);
		}

		// Salt management.
		$salt = $this->getSetting(0, 'salt');
		$saltTs = $this->getSetting(0, 'saltTs');
		if (empty($salt) || $saltTs == 0 || date('YYYYMM', $saltTs) != date('YYYYMM', $currentTs)) {
			$lastSaltDownloadTs = $this->getSetting(0, 'lastSaltDownloadTs');
			if (($currentTs - OAS_PLUGIN_SALT_DOWNLOAD_INTERVAL * 60) > $lastSaltDownloadTs) {
				import('lib.pkp.classes.webservice.WebService');
				import('lib.pkp.classes.webservice.WebServiceRequest');
				$wsReq = new WebServiceRequest(OAS_PLUGIN_SALT_URL);
				$wsReq->setAccept('text/plain');
				$ws = new WebService();
				$ws->setAuthUsername($this->getSetting(0, 'saltApiUsername'));
				$ws->setAuthPassword($this->getSetting(0, 'saltApiPassword'));
				$saltScript = $ws->call($wsReq);
				$matches = null;
				String::regexp_match_get("/'([^']+)'/", $saltScript, $matches);
				$newSalt = null;
				if (isset($matches[1])) $newSalt = $matches[1];
				$this->updateSetting(0, 'lastSaltDownloadTs', $currentTs);
				if($ws->getLastResponseStatus() == '200' && !empty($newSalt) && $newSalt != $salt) {
					$this->updateSetting(0, 'saltTs', $currentTs);
					$this->updateSetting(0, 'salt', $newSalt);
					$salt = $newSalt;
				}
			}
		}

		return $salt;
	}

	/**
	 * Set the page's breadcrumbs, given the plugin's tree of items
	 * to append.
	 */
	function _setBreadcrumbs() {
		$request = $this->getRequest();
		$templateMgr = TemplateManager::getManager($request);
		$pageCrumbs = array(
			array(
				$request->url(null, 'user'),
				'navigation.user'
			),
			array(
				$request->url('index', 'admin'),
				'user.role.siteAdmin'
			),
			array(
				$request->url(null, 'manager', 'plugins'),
				'manager.plugins'
			)
		);
		$templateMgr->assign('pageHierarchy', $pageCrumbs);
	}
}

?>
