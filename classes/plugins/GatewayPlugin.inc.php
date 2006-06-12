<?php

/**
 * GatewayPlugin.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * Abstract class for gateway plugins
 *
 * $Id$
 */

class GatewayPlugin extends Plugin {
	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		// This should not be used as this is an abstract class
		return 'GatewayPlugin';
	}

	/**
	 * Get the display name of this plugin. This name is displayed on the
	 * Journal Manager's plugin management page, for example.
	 * @return String
	 */
	function getDisplayName() {
		// This name should never be displayed because child classes
		// will override this method.
		return 'Abstract Gateway Plugin';
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return 'This is the GatewayPlugin base class. Its functions can be overridden by subclasses to provide import/export functionality for various formats.';
	}

	/**
	 * Extend the {url ...} smarty to support gateway plugins.
	 */
	function smartyPluginUrl($params, &$smarty) {
		$path = array($this->getCategory(), $this->getName());
		if (is_array($params['path'])) {
			$params['path'] = array_merge($path, $params['path']);
		} elseif (!empty($params['path'])) {
			$params['path'] = array_merge($path, array($params['path']));
		} else {
			$params['path'] = $path;
		}
		return $smarty->smartyUrl($params, $smarty);
	}

	/**
	 * Display verbs for the management interface.
	 */
	function getManagementVerbs() {
		$verbs = array();
		if ($this->getEnabled()) {
			$verbs[] = array(
				'disable',
				Locale::translate('manager.plugins.disable')
			);
		} else {
			$verbs[] = array(
				'enable',
				Locale::translate('manager.plugins.enable')
			);
		}
		return $verbs;
	}

	/**
	 * Determine whether or not this plugin is enabled.
	 */
	function getEnabled() {
		$journal =& Request::getJournal();
		if (!$journal) return false;
		return $this->getSetting($journal->getJournalId(), 'enabled');
	}

	/**
	 * Set the enabled/disabled state of this plugin
	 */
	function setEnabled($enabled) {
		$journal =& Request::getJournal();
		if ($journal) {
			$this->updateSetting(
				$journal->getJournalId(),
				'enabled',
				$enabled?true:false
			);
			return true;
		}
		return false;
	}
	
	/**
	 * Perform management functions
	 */
	function manage($verb, $args) {
		$templateManager =& TemplateManager::getManager();
		$templateManager->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));
		switch ($verb) {
			case 'enable': $this->setEnabled(true); break;
			case 'disable': $this->setEnabled(false); break;
		}
		return false;
	}

	/**
	 * Handle fetch requests for this plugin.
	 */
	function fetch($args) {
		// Subclasses should override this function.
		return false;
	}
}
?>
