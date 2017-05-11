<?php

/**
 * @file classes/core/PKPPageRouter.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPPageRouter
 * @ingroup core
 *
 * @brief Class mapping an HTTP request to a handler or context.
 */

define('ROUTER_DEFAULT_PAGE', './pages/index/index.php');
define('ROUTER_DEFAULT_OP', 'index');

import('lib.pkp.classes.core.PKPRouter');

class PKPPageRouter extends PKPRouter {
	/** @var array pages that don't need an installed system to be displayed */
	var $_installationPages = array('install', 'help', 'header', 'sidebar');

	//
	// Internal state cache variables
	// NB: Please do not access directly but
	// only via their respective getters/setters
	//
	/** @var string the requested page */
	var $_page;
	/** @var string the requested operation */
	var $_op;
	/** @var string index url */
	var $_indexUrl;
	/** @var string cache filename */
	var $_cacheFilename;

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * get the installation pages
	 * @return array
	 */
	function getInstallationPages() {
		return $this->_installationPages;
	}

	/**
	 * get the cacheable pages
	 * @return array
	 */
	function getCacheablePages() {
		// Can be overridden by sub-classes.
		return array();
	}

	/**
	 * Determine whether or not the request is cacheable.
	 * @param $request PKPRequest
	 * @param $testOnly boolean required for unit test to
	 *  bypass session check.
	 * @return boolean
	 */
	function isCacheable($request, $testOnly = false) {
		if (defined('SESSION_DISABLE_INIT') && !$testOnly) return false;
		if (!Config::getVar('general', 'installed')) return false;
		if (!empty($_POST) || Validation::isLoggedIn()) return false;

		if ($request->isPathInfoEnabled()) {
			if (!empty($_GET)) return false;
		} else {
			$application = $this->getApplication();
			$ok = array_merge($application->getContextList(), array('page', 'op', 'path'));
			if (!empty($_GET) && count(array_diff(array_keys($_GET), $ok)) != 0) {
				return false;
			}
		}

		if (in_array($this->getRequestedPage($request), $this->getCacheablePages())) return true;

		return false;
	}

	/**
	 * Get the page requested in the URL.
	 * @param $request PKPRequest the request to be routed
	 * @return String the page path (under the "pages" directory)
	 */
	function getRequestedPage($request) {
		if (!isset($this->_page)) {
			$this->_page = $this->_getRequestedUrlParts(array('Core', 'getPage'), $request);
		}
		return $this->_page;
	}

	/**
	 * Get the operation requested in the URL (assumed to exist in the requested page handler).
	 * @param $request PKPRequest the request to be routed
	 * @return string
	 */
	function getRequestedOp($request) {
		if (!isset($this->_op)) {
			$this->_op = $this->_getRequestedUrlParts(array('Core', 'getOp'), $request);
		}
		return $this->_op;
	}

	/**
	 * Get the arguments requested in the URL.
	 * @param $request PKPRequest the request to be routed
	 * @return array
	 */
	function getRequestedArgs($request) {
		return $this->_getRequestedUrlParts(array('Core', 'getArgs'), $request);
	}


	//
	// Implement template methods from PKPRouter
	//
	/**
	 * @copydoc PKPRouter::getCacheFilename()
	 */
	function getCacheFilename($request) {
		if (!isset($this->_cacheFilename)) {
			if ($request->isPathInfoEnabled()) {
				$id = isset($_SERVER['PATH_INFO'])?$_SERVER['PATH_INFO']:'index';
				$id .= '-' . AppLocale::getLocale();
			} else {
				$id = '';
				$application = $this->getApplication();
				foreach($application->getContextList() as $contextName) {
					$id .= $request->getUserVar($contextName) . '-';
				}
				$id .= $request->getUserVar('page') . '-' . $request->getUserVar('op') . '-' . $request->getUserVar('path') . '-' . AppLocale::getLocale();
			}
			$path = Core::getBaseDir();
			$this->_cacheFilename = $path . '/cache/wc-' . md5($id) . '.html';
		}
		return $this->_cacheFilename;
	}

	/**
	 * @copydoc PKPRouter::route()
	 */
	function route($request) {
		// Determine the requested page and operation
		$page = $this->getRequestedPage($request);
		$op = $this->getRequestedOp($request);

		// If the application has not yet been installed we only
		// allow installer pages to be displayed.
		if (!Config::getVar('general', 'installed')) {
			define('SESSION_DISABLE_INIT', 1);
			if (!in_array($page, $this->getInstallationPages())) {
				// A non-installation page was called although
				// the system is not yet installed. Redirect to
				// the installation page.
				$redirectMethod = array($request, 'redirect');

				// The correct redirection for the installer page
				// depends on the context depth of this application.
				$application = $this->getApplication();
				$contextDepth = $application->getContextDepth();
				// The context will be filled with all nulls
				$redirectArguments = array_pad(array('install'), - $contextDepth - 1, null);

				// Call request's redirect method
				call_user_func_array($redirectMethod, $redirectArguments);
			}
		}

		// Redirect requests from logged-out users to a context which is not
		// publicly enabled
		if (!defined('SESSION_DISABLE_INIT')) {
			$user = $request->getUser();
			$currentContext = $request->getContext();
			if ($currentContext && !$currentContext->getEnabled() && !is_a($user, 'User')) {
				$op = ROUTER_DEFAULT_OP;
				$page = ROUTER_DEFAULT_PAGE;
			}
		}

		// Determine the page index file. This file contains the
		// logic to resolve a page to a specific handler class.
		$sourceFile = sprintf('pages/%s/index.php', $page);

		// If a hook has been registered to handle this page, give it the
		// opportunity to load required resources and set HANDLER_CLASS.
		if (!HookRegistry::call('LoadHandler', array(&$page, &$op, &$sourceFile))) {
			if (file_exists($sourceFile)) require('./'.$sourceFile);
			elseif (file_exists(PKP_LIB_PATH . DIRECTORY_SEPARATOR . $sourceFile))
				require('.' . DIRECTORY_SEPARATOR . PKP_LIB_PATH . DIRECTORY_SEPARATOR . $sourceFile);
			elseif (empty($page)) require(ROUTER_DEFAULT_PAGE);
			else {
				$dispatcher = $this->getDispatcher();
				$dispatcher->handle404();
			}
		}

		if (!defined('SESSION_DISABLE_INIT')) {
			// Initialize session
			SessionManager::getManager();
		}

		// Call the selected handler's index operation if
		// no operation was defined in the request.
		if (empty($op)) $op = ROUTER_DEFAULT_OP;

		// Redirect to 404 if the operation doesn't exist
		// for the handler.
		$methods = array();
		if (defined('HANDLER_CLASS')) $methods = get_class_methods(HANDLER_CLASS);
		if (!in_array($op, $methods)) {
			$dispatcher = $this->getDispatcher();
			$dispatcher->handle404();
		}

		// Instantiate the handler class
		$handlerClass = HANDLER_CLASS;
		$handler = new $handlerClass($request);

		// Authorize and initialize the request but don't call the
		// validate() method on page handlers.
		// FIXME: We should call the validate() method for page
		// requests also (last param = true in the below method
		// call) once we've made sure that all validate() calls can
		// be removed from handler operations without damage (i.e.
		// they don't depend on actions being performed before the
		// call to validate().
		$args = $this->getRequestedArgs($request);
		$serviceEndpoint = array($handler, $op);
		$this->_authorizeInitializeAndCallRequest($serviceEndpoint, $request, $args, false);
	}

	/**
	 * @copydoc PKPRouter::url()
	 */
	function url($request, $newContext = null, $page = null, $op = null, $path = null,
				$params = null, $anchor = null, $escape = false) {
		$pathInfoEnabled = $request->isPathInfoEnabled();

		//
		// Base URL and Context
		//
		$newContext = $this->_urlCanonicalizeNewContext($newContext);
		$baseUrlAndContext = $this->_urlGetBaseAndContext($request, $newContext);
		$baseUrl = array_shift($baseUrlAndContext);
		$context = $baseUrlAndContext;

		//
		// Additional path info
		//
		if (empty($path)) {
			$additionalPath = array();
		} else {
			if (is_array($path)) {
				$additionalPath = array_map('rawurlencode', $path);
			} else {
				$additionalPath = array(rawurlencode($path));
			}

			// If path info is disabled then we have to
			// encode the path as query parameters.
			if (!$pathInfoEnabled) {
				$pathKey = $escape?'path%5B%5D=':'path[]=';
				foreach($additionalPath as $key => $pathElement) {
					$additionalPath[$key] = $pathKey.$pathElement;
				}
			}
		}

		//
		// Page and Operation
		//

		// Are we in a page request?
		$currentRequestIsAPageRequest = is_a($request->getRouter(), 'PKPPageRouter');

		// Determine the operation
		if ($op) {
			// If an operation has been explicitly set then use it.
			$op = rawurlencode($op);
		} else {
			// No operation has been explicitly set so let's determine a sensible
			// default operation.
			if (empty($newContext) && empty($page) && $currentRequestIsAPageRequest) {
				// If we remain in the existing context and on the existing page then
				// we will default to the current operation. We can only determine a
				// current operation if the current request is a page request.
				$op = $this->getRequestedOp($request);
			} else {
				// If a new context (or page) has been set then we'll default to the
				// index operation within the new context (or on the new page).
				if (empty($additionalPath)) {
					// If no additional path is set we can simply leave the operation
					// undefined which automatically defaults to the index operation
					// but gives shorter (=nicer) URLs.
					$op = null;
				} else {
					// If an additional path is set then we have to explicitly set the
					// index operation to disambiguate the path info.
					$op = 'index';
				}
			}
		}

		// Determine the page
		if ($page) {
			// If a page has been explicitly set then use it.
			$page = rawurlencode($page);
		} else {
			// No page has been explicitly set so let's determine a sensible default page.
			if (empty($newContext) && $currentRequestIsAPageRequest) {
				// If we remain in the existing context then we will default to the current
				// page. We can only determine a current page if the current request is a
				// page request.
				$page = $this->getRequestedPage($request);
			} else {
				// If a new context has been set then we'll default to the index page
				// within the new context.
				if (empty($op)) {
					// If no explicit operation is set we can simply leave the page
					// undefined which automatically defaults to the index page but gives
					// shorter (=nicer) URLs.
					$page = null;
				} else {
					// If an operation is set then we have to explicitly set the index
					// page to disambiguate the path info.
					$page = 'index';
				}
			}
		}

		//
		// Additional query parameters
		//
		$additionalParameters = $this->_urlGetAdditionalParameters($request, $params, $escape);

		//
		// Anchor
		//
		$anchor = (empty($anchor) ? '' : '#'.rawurlencode($anchor));

		//
		// Assemble URL
		//
		if ($pathInfoEnabled) {
			// If path info is enabled then context, page,
			// operation and additional path go into the
			// path info.
			$pathInfoArray = $context;
			if (!empty($page)) {
				$pathInfoArray[] = $page;
				if (!empty($op)) {
					$pathInfoArray[] = $op;
				}
			}
			$pathInfoArray = array_merge($pathInfoArray, $additionalPath);

			// Query parameters
			$queryParametersArray = $additionalParameters;
		} else {
			// If path info is disabled then context, page,
			// operation and additional path are encoded as
			// query parameters.
			$pathInfoArray = array();

			// Query parameters
			$queryParametersArray = $context;
			if (!empty($page)) {
				$queryParametersArray[] = "page=$page";
				if (!empty($op)) {
					$queryParametersArray[] = "op=$op";
				}
			}
			$queryParametersArray = array_merge($queryParametersArray, $additionalPath, $additionalParameters);
		}

		return $this->_urlFromParts($baseUrl, $pathInfoArray, $queryParametersArray, $anchor, $escape);
	}

	/**
	 * @copydoc PKPRouter::handleAuthorizationFailure()
	 */
	function handleAuthorizationFailure($request, $authorizationMessage) {
		// Redirect to the authorization denied page.
		if (!$request->getUser()) Validation::redirectLogin();
		$request->redirect(null, 'user', 'authorizationDenied', null, array('message' => $authorizationMessage));
	}

	/**
	 * Redirect to user home page (or the user group home page if the user has one user group).
	 * @param $request PKPRequest the request to be routed
	 */
	function redirectHome($request) {
		$request->redirectUrl($this->getHomeUrl($request));
	}

	/**
	 * Get the user's "home" page URL (e.g. where they are sent after login).
	 * @param $request PKPRequest the request to be routed
	 */
	function getHomeUrl($request) {
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$user = $request->getUser();
		$userId = $user->getId();

		if ($context = $this->getContext($request, 1)) {
			// The user is in the context, see if they have zero or one roles only
			$userGroups = $userGroupDao->getByUserId($userId, $context->getId());
			if($userGroups->getCount() <= 1) {
				$userGroup = $userGroups->next();
				if (!$userGroup || $userGroup->getRoleId() == ROLE_ID_READER) return $request->url(null, 'index');
			}
			return $request->url(null, 'submissions');
		} else {
			// The user is at the site context, check to see if they are
			// only registered in one place w/ one role
			$userGroups = $userGroupDao->getByUserId($userId, CONTEXT_ID_NONE);

			if($userGroups->getCount() == 1) {
				$contextDao = Application::getContextDAO();
				$userGroup = $userGroups->next();
				$context = $contextDao->getById($userGroup->getContextId());
				if (!isset($context)) $request->redirect('index', 'index');
				if ($userGroup->getRoleId() == ROLE_ID_READER) $request->redirect(null, 'index');
			}
			return $request->url('index', 'index');
		}
	}


	//
	// Private helper methods.
	//
	/**
	* Retrieve part of the current requested
	* url using the passed callback method.
	* @param $callback array Core method to retrieve
	* page, operation or arguments from url.
	* @param $request PKPRequest
	* @return array|string|null
	*/
	private function _getRequestedUrlParts($callback, &$request) {
		$url = null;
		assert(is_a($request->getRouter(), 'PKPPageRouter'));
		$isPathInfoEnabled = $request->isPathInfoEnabled();

		if ($isPathInfoEnabled) {
			if (isset($_SERVER['PATH_INFO'])) {
				$url = $_SERVER['PATH_INFO'];
			}
		} else {
			$url = $request->getCompleteUrl();
		}

		$userVars = $request->getUserVars();
		return call_user_func_array($callback, array($url, $isPathInfoEnabled, $userVars));
	}
}

?>
