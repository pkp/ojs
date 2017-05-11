<?php

/**
 * @file classes/plugins/LazyLoadPlugin.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CachedPlugin
 * @ingroup plugins
 *
 * @brief Abstract class for plugins that optionally
 * support lazy load.
 */

import('lib.pkp.classes.plugins.Plugin');

abstract class LazyLoadPlugin extends Plugin {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}


	//
	// Override public methods from Plugin
	//
	/**
	 * @copydoc Plugin::register()
	 */
	function register($category, $path) {
		if (!parent::register($category, $path)) return false;
		$this->addLocaleData();
		return true;
	}


	//
	// Override protected methods from Plugin
	//
	/**
	 * @see Plugin::getName()
	 */
	function getName() {
		// Lazy load enabled plug-ins always use the plugin's class name
		// as plug-in name. Legacy plug-ins will override this method so
		// this implementation is backwards compatible.
		// NB: strtolower was required for PHP4 compatibility.
		return strtolower_codesafe(get_class($this));
	}


	//
	// Public methods required to support lazy load.
	//
	/**
	 * Determine whether or not this plugin is currently enabled.
	 * @return boolean
	 */
	function getEnabled() {
		return $this->getSetting($this->getCurrentContextId(), 'enabled');
	}

	/**
	 * Set whether or not this plugin is currently enabled.
	 * @param $enabled boolean
	 */
	function setEnabled($enabled) {
		$this->updateSetting($this->getCurrentContextId(), 'enabled', $enabled, 'bool');
	}

	/**
	 * @copydoc Plugin::getCanEnable()
	 */
	function getCanEnable() {
		return true;
	}

	/**
	 * @copydoc Plugin::getCanDisable()
	 */
	function getCanDisable() {
		return true;
	}

	/**
	 * Get the current context ID or the site-wide context ID (0) if no context
	 * can be found.
	 */
	function getCurrentContextId() {
		$context = PKPApplication::getRequest()->getContext();
		return is_null($context) ? 0 : $context->getId();
	}
}

?>
