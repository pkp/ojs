<?php

/**
 * @file classes/plugins/ImportExportPlugin.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ImportExportPlugin
 * @ingroup plugins
 *
 * @brief Abstract class for import/export plugins
 */

import('lib.pkp.classes.plugins.PKPImportExportPlugin');

abstract class ImportExportPlugin extends PKPImportExportPlugin {
	/**
	 * Constructor
	 */
	function ImportExportPlugin() {
		parent::PKPImportExportPlugin();
	}

 	/**
	 * @see Plugin::manage()
	 */
	function manage($verb, $args, &$message, &$messageParams, &$pluginModalContent = null) {
		$request = $this->getRequest();
		if ($verb === 'importexport') {
			$request->redirect(null, 'manager', 'importexport', array('plugin', $this->getName()));
		}
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->register_function('plugin_url', array($this, 'smartyPluginUrl'));
		return false;
	}

	/**
	 * @copydoc PKPImportExportPlugin::smartyPluginUrl
	 */
	function smartyPluginUrl($params, $smarty) {
		$path = null;
		if (!empty($params['path'])) $path = $params['path'];
		if (!is_array($path)) $path = array($params['path']);

		// Check whether our path points to a management verb.
		$managementVerbs = array();
		foreach($this->getManagementVerbs() as $managementVerb) {
			$managementVerbs[] = $managementVerb[0];
		}
		if (count($path) == 1 && in_array($path[0], $managementVerbs)) {
			// Management verbs will be routed to the plugin's manage method.
			$params['op'] = 'plugin';
			return parent::smartyPluginUrl($params, $smarty);
		} else {
			// All other paths will be routed to the plugin's display method.
			$params['path'] = array_merge(array('plugin', $this->getName()), $path);
			return $smarty->smartyUrl($params, $smarty);
		}
	}
}

?>
