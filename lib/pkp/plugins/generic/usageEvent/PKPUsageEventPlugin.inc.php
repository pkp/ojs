<?php

/**
 * @file plugins/generic/usageEvent/PKPUsageEventPlugin.inc.php
 *
 * Copyright (c) 2013-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPUsageEventPlugin
 * @ingroup plugins_generic_usageEvent
 *
 * @brief Base class for usage event plugin. Provide usage events to
 * other statistics plugins.
 */


import('lib.pkp.classes.plugins.GenericPlugin');

// User classification types.
define('USAGE_EVENT_PLUGIN_CLASSIFICATION_BOT', 'bot');
define('USAGE_EVENT_PLUGIN_CLASSIFICATION_ADMIN', 'administrative');

abstract class PKPUsageEventPlugin extends GenericPlugin {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	//
	// Implement methods from PKPPlugin.
	//
	/**
	* @copydoc LazyLoadPlugin::register()
	*/
	function register($category, $path) {
		$success = parent::register($category, $path);

		if ($success) {
			$eventHooks = $this->getEventHooks();
			foreach ($eventHooks as $hook) {
				HookRegistry::register($hook, array($this, 'getUsageEvent'));
			}
		}

		return $success;
	}

	/**
	 * @copydoc LazyLoadPlugin::getName()
	 */
	function getName() {
		return 'usageeventplugin';
	}

	/**
	 * @copydoc Plugin::getInstallSitePluginSettingsFile()
	 */
	function getInstallSitePluginSettingsFile() {
		return PKP_LIB_PATH . DIRECTORY_SEPARATOR . $this->getPluginPath() . DIRECTORY_SEPARATOR . 'settings.xml';
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
	 * @copydoc LazyLoadPlugin::getEnabled()
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

			$usageEvent = $this->buildUsageEvent($hookName, $args);
			HookRegistry::call('UsageEventPlugin::getUsageEvent', array_merge(array($hookName, $usageEvent), $args));
		}
		return false;
	}


	//
	// Protected methods.
	//
	/**
	 * Get all hooks that must be used to
	 * generate usage events.
	 * @return array
	 */
	protected function getEventHooks() {
		return array(
			'TemplateManager::display',
			'FileManager::downloadFileFinished'
		);
	}

	/**
	 * Get all hooks that define the
	 * finished file download.
	 * @return array
	 */
	protected function getDownloadFinishedEventHooks() {
		return array(
			'FileManager::downloadFileFinished'
		);
	}

	/**
	 * Build an usage event.
	 * @param $hookName string
	 * @param $args array
	 * @return array
	 */
	protected function buildUsageEvent($hookName, $args) {
		// Finished downloading a file?
		if (in_array($hookName, $this->getDownloadFinishedEventHooks())) {
			// The usage event for this request is already build and
			// passed to any other registered hook.
			return null;
		}

		$application = Application::getApplication();
		$request = $application->getRequest();
		$router = $request->getRouter(); /* @var $router PageRouter */
		$templateMgr = $args[0]; /* @var $templateMgr TemplateManager */

		// We are just interested in page requests.
		if (!is_a($router, 'PageRouter')) return false;

		// Check whether we are in journal context.
		$context = $router->getContext($request);
		if (!$context) return false;

		// Prepare request information.
		list($pubObject, $downloadSuccess, $assocType, $idParams, $canonicalUrlPage, $canonicalUrlOp, $canonicalUrlParams) =
			$this->getUsageEventData($hookName, $args, $request, $router, $templateMgr, $context);

		if (!$pubObject) return false;

		// Timestamp.
		$time = Core::getCurrentDate();

		// Actual document size, MIME type.
		$htmlPageAssocTypes = $this->getHtmlPageAssocTypes();
		if (in_array($assocType, $htmlPageAssocTypes)) {
			// HTML pages with no file downloads.
			$docSize = 0;
			$mimeType = 'text/html';
		} else {
			// Files.
			$docSize = (int)$pubObject->getFileSize();
			$mimeType = $pubObject->getFileType();
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
				$baseUrlReplacement = Config::getVar('general', 'base_url['.$context->getPath().']');
				if (!$baseUrlReplacement) $baseUrlReplacement = $configBaseUrl;
				$canonicalUrl = str_replace($requestBaseUrl, $baseUrlReplacement, $canonicalUrl);
			}
		}

		// Public identifiers.
		// 1) A unique system internal ID that will help us to easily attribute
		//    statistics to a specific publication object.
		array_unshift($idParams, 'c' . $context->getId());
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
		$applicationName = $application->getName();
		$applicationId = $applicationName . ':' . implode('-', $idParams);
		$idKey = 'other::' . $applicationName;
		$identifiers = array($idKey => $applicationId);

		// 2) Standardized public identifiers, e.g. DOI, URN, etc.
		if ($this->isPubIdObjectType($pubObject)) {
			$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true, $context->getId());
			if (is_array($pubIdPlugins)) {
				foreach ($pubIdPlugins as $pubIdPlugin) {
					if (!$pubIdPlugin->getEnabled()) continue;
					$pubId = $pubObject->getStoredPubId($pubIdPlugin->getPubIdType());
					if ($pubId) {
						$identifiers[$pubIdPlugin->getPubIdType()] = $pubId;
					}
				}
			}
		}

		// Service URI.
		$serviceUri = $router->url($request, $context->getPath());

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
			$roleDao = DAORegistry::getDAO('RoleDAO'); /* @var $roleDao PKPRoleDAO */
			$rolesByContext = $roleDao->getByUserIdGroupedByContext($user->getId());
			foreach (array(CONTEXT_SITE, $context->getId()) as $workingContext) {
				if(isset($rolesByContext[$workingContext])) {
					foreach ($rolesByContext[$workingContext] as $roleId => $role) {
						$roles[] = $roleId;
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

	/**
	* Get usage event details based on the passed hook.
	* Subclasses should extend to implement application specifics.
	* @param $hookName string
	* @param $hookArgs array
	* @param $request PKPRequest
	* @param $router PageRouter
	* @param $templateMgr PKPTemplateManager
	* @param $context Context
	* @return array With the following data:
	* DataObject the published object, boolean download success, integer used published object assoc type,
	* string used published object id foreign keys lookup (all parent associated objects id,
	* preceeded with a single letter to identify the object), string canonical url page,
	* string canonical url operation, array with canonical url parameters.
	* @see PKPUsageEventPlugin::buildUsageEvent()
	*/
	protected function getUsageEventData($hookName, $hookArgs, $request, $router, $templateMgr, $context) {
		$nullVar = null;
		$pubObject = $nullVar;
		$downloadSuccess = false;
		$canonicalUrlPage = $canonicalUrlOp = $assocType = null;
		$canonicalUrlParams = $idParams = array();

		if ($hookName == 'TemplateManager::display') {
			$page = $router->getRequestedPage($request);
			$op = $router->getRequestedOp($request);

			// First check for a context index page view.
			if (($page == 'index' || empty($page)) && $op == 'index') {
				$pubObject = $templateMgr->get_template_vars('currentContext');
				if (is_a($pubObject, 'Context')) {
					$assocType = Application::getContextAssocType();
					$canonicalUrlOp = '';
					$canonicalUrlPage = 'index';
					$downloadSuccess = true;
				}
			}
		}

		return array($pubObject, $downloadSuccess, $assocType, $idParams, $canonicalUrlPage, $canonicalUrlOp, $canonicalUrlParams);
	}

	//
	// Abstract protected methods.
	//
	/**
	 * Get all assoc types that have their usage event
	 * produced by html page access.
	 * @return array
	 */
	abstract protected function getHtmlPageAssocTypes();

	/**
	 * Whether or not the passed object is of a type that can have
	 * different public identifiers, like DOI, URN, etc.
	 * @param $pubObject DataObject
	 * @return boolean
	 */
	abstract protected function isPubIdObjectType($pubObject);

}

?>
