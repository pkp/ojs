<?php

/**
 * @file NavigationMenusPlugin.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.NavigationMenus
 * @class NavigationMenusPlugin
 * Navigation Menus plugin main class
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class NavigationMenusPlugin extends GenericPlugin {
	/**
	 * Get the plugin's display (human-readable) name.
	 * @return string
	 */
	function getDisplayName() {
		return __('plugins.generic.navigationMenus.displayName');
	}

	/**
	 * Get the plugin's display (human-readable) description.
	 * @return string
	 */
	function getDescription() {
		$description = __('plugins.generic.navigationMenus.description');
		if (!$this->isTinyMCEInstalled())
			$description .= __('plugins.generic.navigationMenus.requirement.tinymce');
		return $description;
	}

	/**
	 * Check whether or not the TinyMCE plugin is installed.
	 * @return boolean True iff TinyMCE is installed.
	 */
	function isTinyMCEInstalled() {
		$application = PKPApplication::getApplication();
		$products = $application->getEnabledProducts('plugins.generic');
		return (isset($products['tinymce']));
	}

	/**
	 * Register the plugin, attaching to hooks as necessary.
	 * @param $category string
	 * @param $path string
	 * @return boolean
	 */
	function register($category, $path) {
		if (parent::register($category, $path)) {
			if ($this->getEnabled()) {
				// Register the navigationMenu DAO.
				import('plugins.generic.navigationMenus.classes.NavigationMenusDAO');
				$navigationMenusDao = new NavigationMenusDAO();
				DAORegistry::registerDAO('NavigationMenusDAO', $navigationMenusDao);

				HookRegistry::register('Templates::Management::Settings::website', array($this, 'callbackShowWebsiteSettingsTabs'));

				// Intercept the LoadHandler hook to present
				// navigation menus when requested.
				HookRegistry::register('LoadHandler', array($this, 'callbackHandleContent'));

				// Register the components this plugin implements to
				// permit administration of navigation Menus.
				HookRegistry::register('LoadComponentHandler', array($this, 'setupGridHandler'));
			}
			return true;
		}
		return false;
	}

	/**
	 * Extend the website settings tabs to include static pages
	 * @param $hookName string The name of the invoked hook
	 * @param $args array Hook parameters
	 * @return boolean Hook handling status
	 */
	function callbackShowWebsiteSettingsTabs($hookName, $args) {
		$output =& $args[2];
		$request =& Registry::get('request');
		$dispatcher = $request->getDispatcher();

		// Add a new tab for navigation Menus
		$output .= '<li><a name="navigationMenus" href="' . $dispatcher->url($request, ROUTE_COMPONENT, null, 'plugins.generic.navigationMenus.controllers.grid.NavigationMenusGridHandler', 'index') . '">' . __('plugins.generic.navigationMenus.navigationMenus') . '</a></li>';

		// Permit other plugins to continue interacting with this hook
		return false;
	}

	/**
	 * Declare the handler function to process the actual page PATH
	 * @param $hookName string The name of the invoked hook
	 * @param $args array Hook parameters
	 * @return boolean Hook handling status
	 */
	function callbackHandleContent($hookName, $args) {
	    $request = $this->getRequest();
	    $templateMgr = TemplateManager::getManager($request);

	    $page =& $args[0];
	    $op =& $args[1];

	    $navigationMenusDao = DAORegistry::getDAO('NavigationMenusDAO');
	    if ($page == 'pages' && $op == 'preview') {
	        // This is a preview request; mock up a static page to display.
	        // The handler class ensures that only managers and administrators
	        // can do this.
	        $navigationMenu = $navigationMenusDao->newDataObject();
	        $navigationMenu->setContent((array) $request->getUserVar('content'), null);
	        $navigationMenu->setTitle((array) $request->getUserVar('title'), null);
	    } else {
	        // Construct a path to look for
	        $path = $page;
	        if ($op !== 'index') $path .= "/$op";
	        if ($ops = $request->getRequestedArgs()) $path .= '/' . implode('/', $ops);

	        // Look for a navigationMenus with the given path
	        $context = $request->getContext();
	        $navigationMenu = $navigationMenusDao->getByPath(
	            $context?$context->getId():CONTEXT_ID_NONE,
	            $path
	        );
	    }

	    // Check if this is a request for a static page or preview.
	    if ($navigationMenu) {
	        // Trick the handler into dealing with it normally
	        $page = 'pages';
	        $op = 'view';

	        // It is -- attach the navigationMenus handler.
	        define('HANDLER_CLASS', 'NavigationMenusHandler');
	        $this->import('NavigationMenusHandler');

	        // Allow the navigationMenus page handler to get the plugin object
	        NavigationMenusHandler::setPlugin($this);
	        NavigationMenusHandler::setPage($navigationMenu);
	        return true;
	    }
	    return false;
	}

	/**
	 * Permit requests to the navigationMenus grid handler
	 * @param $hookName string The name of the hook being invoked
	 * @param $args array The parameters to the invoked hook
	 */
	function setupGridHandler($hookName, $params) {
		$component =& $params[0];
		if ($component == 'plugins.generic.navigationMenus.controllers.grid.NavigationMenusGridHandler') {
			// Allow the navigation Menus grid handler to get the plugin object
			import($component);
			NavigationMenusGridHandler::setPlugin($this);
			return true;
		}
		return false;
	}

	/**
	 * @copydoc Plugin::getActions()
	 */
	function getActions($request, $actionArgs) {
		$dispatcher = $request->getDispatcher();
		import('lib.pkp.classes.linkAction.request.RedirectAction');
		return array_merge(
			$this->getEnabled()?array(
				new LinkAction(
					'settings',
					new RedirectAction($dispatcher->url(
						$request, ROUTE_PAGE,
						null, 'management', 'settings', 'website',
						array('uid' => uniqid()), // Force reload
						'navigationMenus' // Anchor for tab
					)),
					__('plugins.generic.navigationMenus.editAddContent'),
					null
				),
			):array(),
			parent::getActions($request, $actionArgs)
		);
	}

	/**
	 * Get the filename of the ADODB schema for this plugin.
	 * @return string Full path and filename to schema descriptor.
	 */
	function getInstallSchemaFile() {
		return $this->getPluginPath() . '/schema.xml';
	}

	/**
	 * @copydoc PKPPlugin::getTemplatePath
	 */
	function getTemplatePath($inCore = false) {
		return parent::getTemplatePath($inCore) . 'templates/';
	}

	/**
	 * Get the JavaScript URL for this plugin.
	 */
	function getJavaScriptURL($request) {
		return $request->getBaseUrl() . '/' . $this->getPluginPath() . '/js';
	}
}

?>
