<?php

/**
 * @file classes/core/Dispatcher.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Dispatcher
 * @ingroup core
 *
 * @brief Class dispatching HTTP requests to handlers.
 */


class Dispatcher {
	/** @var PKPApplication */
	var $_application;

	/** @var array an array of Router implementation class names */
	var $_routerNames = array();

	/** @var array an array of Router instances */
	var $_routerInstances = array();

	/** @var PKPRouter */
	var $_router;

	/** @var PKPRequest Used for a callback hack - NOT GENERALLY SET. */
	var $_requestCallbackHack;

	/**
	 * Get the application
	 * @return PKPApplication
	 */
	function &getApplication() {
		return $this->_application;
	}

	/**
	 * Set the application
	 * @param $application PKPApplication
	 */
	function setApplication($application) {
		$this->_application = $application;
	}

	/**
	 * Get the router names
	 * @return array an array of Router names
	 */
	function &getRouterNames() {
		return $this->_routerNames;
	}

	/**
	 * Add a router name.
	 *
	 * NB: Routers will be called in the order that they
	 * have been added to the dispatcher. The first router
	 * that supports the request will be called. The last
	 * router should always be a "catch-all" router that
	 * supports all types of requests.
	 *
	 * NB: Routers must be part of the core package
	 * to be accepted by this dispatcher implementation.
	 *
	 * @param $routerName string a class name of a router
	 *  to be given the chance to route the request.
	 *  NB: These are class names and not instantiated objects. We'll
	 *  use lazy instantiation to reduce the performance/memory impact
	 *  to a minimum.
	 * @param $shortcut string a shortcut name for the router
	 *  to be used for quick router instance retrieval.
	 */
	function addRouterName($routerName, $shortcut) {
		assert(is_array($this->_routerNames) && is_string($routerName));
		$this->_routerNames[$shortcut] = $routerName;
	}

	/**
	 * Determine the correct router for this request. Then
	 * let the router dispatch the request to the appropriate
	 * handler method.
	 * @param $request PKPRequest
	 */
	function dispatch($request) {
		// Make sure that we have at least one router configured
		$routerNames = $this->getRouterNames();
		assert(count($routerNames) > 0);

		// Go through all configured routers by priority
		// and find out whether one supports the incoming request
		foreach($routerNames as $shortcut => $routerCandidateName) {
			$routerCandidate =& $this->_instantiateRouter($routerCandidateName, $shortcut);

			// Does this router support the current request?
			if ($routerCandidate->supports($request)) {
				// Inject router and dispatcher into request
				$request->setRouter($routerCandidate);
				$request->setDispatcher($this);

				// We've found our router and can go on
				// to handle the request.
				$router =& $routerCandidate;
				$this->_router =& $router;
				break;
			}
		}

		// None of the router handles this request? This is a development-time
		// configuration error.
		if (is_null($router)) fatalError('None of the configured routers supports this request.');

		// Can we serve a cached response?
		if ($router->isCacheable($request)) {
			$this->_requestCallbackHack =& $request;
			if (Config::getVar('cache', 'web_cache')) {
				if ($this->_displayCached($router, $request)) exit(); // Success
				ob_start(array($this, '_cacheContent'));
			}
		} else {
			if (isset($_SERVER['HTTP_X_MOZ']) && $_SERVER['HTTP_X_MOZ'] == 'prefetch') {
				header('HTTP/1.0 403 Forbidden');
				echo '403: Forbidden<br><br>Pre-fetching not allowed.';
				exit;
			}
		}

		AppLocale::initialize($request);
		PluginRegistry::loadCategory('generic', true);

		$router->route($request);
	}

	/**
	 * Build a handler request URL into PKPApplication.
	 * @param $request PKPRequest the request to be routed
	 * @param $shortcut string the short name of the router that should be used to construct the URL
	 * @param $newContext mixed Optional contextual paths
	 * @param $handler string Optional name of the handler to invoke
	 * @param $op string Optional name of operation to invoke
	 * @param $path mixed Optional string or array of args to pass to handler
	 * @param $params array Optional set of name => value pairs to pass as user parameters
	 * @param $anchor string Optional name of anchor to add to URL
	 * @param $escape boolean Whether or not to escape ampersands for this URL; default false.
	 * @return string the URL
	 */
	function url($request, $shortcut, $newContext = null, $handler = null, $op = null, $path = null,
				$params = null, $anchor = null, $escape = false) {
		// Instantiate the requested router
		assert(isset($this->_routerNames[$shortcut]));
		$routerName = $this->_routerNames[$shortcut];
		$router =& $this->_instantiateRouter($routerName, $shortcut);

		return $router->url($request, $newContext, $handler, $op, $path, $params, $anchor, $escape);
	}

	//
	// Private helper methods
	//

	/**
	 * Instantiate a router
	 * @param $routerName string
	 * @param $shortcut string
	 */
	function &_instantiateRouter($routerName, $shortcut) {
		if (!isset($this->_routerInstances[$shortcut])) {
			// Routers must belong to the classes.core or lib.pkp.classes.core package
			// NB: This prevents code inclusion attacks.
			$allowedRouterPackages = array(
				'classes.core',
				'lib.pkp.classes.core'
			);

			// Instantiate the router
			$router =& instantiate($routerName, 'PKPRouter', $allowedRouterPackages);
			if (!is_object($router)) {
				fatalError('Cannot instantiate requested router. Routers must belong to the core package and be of type "PKPRouter".');
			}
			$router->setApplication($this->_application);
			$router->setDispatcher($this);

			// Save the router instance for later re-use
			$this->_routerInstances[$shortcut] =& $router;
		}

		return $this->_routerInstances[$shortcut];
	}

	/**
	 * Display the request contents from cache.
	 * @param $router PKPRouter
	 */
	function _displayCached($router, $request) {
		$filename = $router->getCacheFilename($request);
		if (!file_exists($filename)) return false;

		// Allow a caching proxy to work its magic if possible
		$ifModifiedSince = $request->getIfModifiedSince();
		if ($ifModifiedSince !== null && $ifModifiedSince >= filemtime($filename)) {
			header('HTTP/1.1 304 Not Modified');
			exit();
		}

		$fp = fopen($filename, 'r');
		$data = fread($fp, filesize($filename));
		fclose($fp);

		$i = strpos($data, ':');
		$time = substr($data, 0, $i);
		$contents = substr($data, $i+1);

		if (mktime() > $time + Config::getVar('cache', 'web_cache_hours') * 60 * 60) return false;

		header('Content-Type: text/html; charset=' . Config::getVar('i18n', 'client_charset'));

		echo $contents;
		return true;
	}

	/**
	 * Cache content as a local file.
	 * @param $contents string
	 * @return string
	 */
	function _cacheContent($contents) {
		assert(is_a($this->_router, 'PKPRouter'));
		if ($contents == '') return $contents; // Do not cache empties
		$filename = $this->_router->getCacheFilename($this->_requestCallbackHack);
		$fp = fopen($filename, 'w');
		if ($fp) {
			fwrite($fp, mktime() . ':' . $contents);
			fclose($fp);
		}
		return $contents;
	}

	/**
	 * Handle a 404 error (page not found).
	 */
	function handle404() {
		PKPRequest::_checkThis();

		header('HTTP/1.0 404 Not Found');
		fatalError('404 Not Found');
	}
}

?>
