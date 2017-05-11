<?php
/**
 * @file controllers/listbuilder/admin/siteSetup/AdminBlockPluginsListbuilderHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AdminBlockPluginsListbuilderHandler
 * @ingroup controllers_listbuilder_settings
 *
 * @brief Class for managing block plugins displayed in the overall site context
 */

import('lib.pkp.controllers.listbuilder.settings.BlockPluginsListbuilderHandler');

class AdminBlockPluginsListbuilderHandler extends BlockPluginsListbuilderHandler {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			array(ROLE_ID_SITE_ADMIN),
			array('fetch')
		);
	}

	/**
	 * @copydoc GridHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.PKPSiteAccessPolicy');
		$this->addPolicy(new PKPSiteAccessPolicy($request, array(), $roleAssignments));

		return parent::authorize($request, $args, $roleAssignments);
	}

	//
	// Overridden template methods
	//
	/**
	 * @copydoc MultipleListsListbuilderHandler::setListsData()
	 */
	function setListsData($request, $filter) {
		$sidebarBlockPlugins = $disabledBlockPlugins = array();
		$plugins = PluginRegistry::loadCategory('blocks');
		foreach ($plugins as $key => $junk) {
			if (!$plugins[$key]->getEnabled(0) || $plugins[$key]->getBlockContext(0) == '') {
				if (count(array_intersect($plugins[$key]->getSupportedContexts(), array(BLOCK_CONTEXT_SIDEBAR))) > 0) $disabledBlockPlugins[$key] = $plugins[$key];
			} else {
				switch ($plugins[$key]->getBlockContext(0)) {
					case BLOCK_CONTEXT_SIDEBAR:
						$sidebarBlockPlugins[$key] = $plugins[$key];
						break;
				}
			}
		}

		$lists = $this->getLists();
		$lists['sidebarContext']->setData($sidebarBlockPlugins);
		$lists['unselected']->setData($disabledBlockPlugins);
	}
}

?>
