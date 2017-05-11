<?php

/**
 * @file lib/pkp/controllers/page/PageHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PageHandler
 * @ingroup controllers_page
 *
 * @brief Handler for requests for page components such as the header, tasks,
 *  usernav, and CSS.
 */

import('classes.handler.Handler');

class PageHandler extends Handler {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}


	//
	// Implement template methods from PKPHandler
	//
	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.PKPSiteAccessPolicy');
		$this->addPolicy(new PKPSiteAccessPolicy(
			$request,
			array('userNav', 'userNavBackend', 'tasks', 'css'),
			SITE_ACCESS_ALL_ROLES
		));
		if (!Config::getVar('general', 'installed')) define('SESSION_DISABLE_INIT', true);

		$this->setEnforceRestrictedSite(false);
		return parent::authorize($request, $args, $roleAssignments);
	}


	//
	// Public operations
	//
	/**
	 * Display the backend user-context menu.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function userNavBackend($args, $request) {
		$this->setupTemplate($request);
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_MANAGER); // Management menu items
		$templateMgr = TemplateManager::getManager($request);

		$this->setupHeader($args, $request);

		return $templateMgr->fetchJson('controllers/page/usernav.tpl');
	}

	/**
	 * Display the tasks component
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function tasks($args, $request) {
		$this->setupTemplate($request);
		$templateMgr = TemplateManager::getManager($request);
		return $templateMgr->fetchJson('controllers/page/tasks.tpl');
	}

	/**
	 * Get the compiled CSS
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function css($args, $request) {
		header('Content-Type: text/css');

		$templateManager = TemplateManager::getManager($request);

		$name = $request->getUserVar('name');
		if (empty($name)) {
			$name = 'pkp-lib';
		}
		switch ($name) {

			// The core app stylesheet
			case 'pkp-lib':
				$cachedFile = $templateManager->getCachedLessFilePath($name);
				if (!file_exists($cachedFile)) {
					$styles = $templateManager->compileLess($name, 'styles/index.less');
					if (!$templateManager->cacheLess($cachedFile, $styles)) {
						echo $styles;
						die;
					}
				}
				break;

			default:

				// Backwards compatibility. This hook is deprecated.
				if (HookRegistry::getHooks('PageHandler::displayCss')) {
					$result = '';
					$lastModified = null;
					HookRegistry::call('PageHandler::displayCss', array($request, &$name, &$result, &$lastModified));
					if ($lastModified) header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
					header('Content-Length: ' . strlen($result));
					echo $result;
					die;

				} else {
					$cachedFile = $templateManager->getCachedLessFilePath($name);
					if (!file_exists($cachedFile)) {

						// Process styles registered with the current theme
						$styles = '';
						$themes = PluginRegistry::loadCategory('themes', true);
						foreach($themes as $theme) {
							if ($theme->isActive()) {
								$style = $theme->getStyle($name);
								if (!empty($style)) {

									// Compile and cache the stylesheet
									$styles = $templateManager->compileLess(
										$name,
										$style['style'],
										array(
											'baseUrl'          => isset($style['baseUrl']) ? $style['baseUrl'] : null,
											'addLess'          => isset($style['addLess']) ? $style['addLess'] : null,
											'addLessVariables' => isset($style['addLessVariables']) ? $style['addLessVariables'] : null,
										)
									);
								}
								break;
							}
						}

						// If we still haven't found styles, fire off a hook
						// which allows other types of plugins to handle
						// requests
						if (!$styles) {
							HookRegistry::call(
								'PageHandler::getCompiledLess',
								array(
									'request'    => $request,
									'name'       => &$name,
									'styles'     => &$styles,
								)
							);
						}

						// Give up if there are still no styles
						if (!$styles) {
							die;
						}

						// Try to save the styles to a cached file. If we can't,
						// just print them out
						if (!$templateManager->cacheLess($cachedFile, $styles)) {
							echo $styles;
							die;
						}
					}
				}
				break;
		}

		// Deliver the cached file
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($cachedFile)).' GMT');
		header('Content-Length: ' . filesize($cachedFile));
		readfile($cachedFile);
		die;
	}

	/**
	 * Setup and assign variables for any templates that want the overall header
	 * context.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	private function setupHeader($args, $request) {

		$templateMgr = TemplateManager::getManager($request);

		$workingContexts = $this->getWorkingContexts($request);
		$context = $request->getContext();

		if ($workingContexts && $workingContexts->getCount() > 1) {
			$dispatcher = $request->getDispatcher();
			$contextsNameAndUrl = array();
			while ($workingContext = $workingContexts->next()) {
				$contextUrl = $dispatcher->url($request, ROUTE_PAGE, $workingContext->getPath(), 'submissions');
				$contextsNameAndUrl[$contextUrl] = $workingContext->getLocalizedName();
			}

			// Get the current context switcher value. We don´t need to worry about the
			// value when there is no current context, because then the switcher will not
			// be visible.
			$currentContextUrl = null;
			$currentContextName = null;
			if ($context) {
				$currentContextUrl = $dispatcher->url($request, ROUTE_PAGE, $context->getPath());
				$currentContextName = $context->getLocalizedName();
			}

			$templateMgr->assign(array(
				'currentContextUrl' => $currentContextUrl,
				'currentContextName' => $currentContextName,
				'contextsNameAndUrl' => $contextsNameAndUrl,
				'multipleContexts' => true
			));
		} else {
			$templateMgr->assign('noContextsConfigured', true);
			if (!$workingContexts) {
				$templateMgr->assign('notInstalled', true);
			}
		}
	}
}

?>
