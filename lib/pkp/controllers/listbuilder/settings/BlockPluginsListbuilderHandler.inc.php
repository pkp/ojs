<?php
/**
 * @file controllers/listbuilder/settings/BlockPluginsListbuilderHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class BlockPluginsListbuilderHandler
 * @ingroup controllers_listbuilder_settings
 *
 * @brief Class for block plugins administration.
 */

import('lib.pkp.classes.controllers.listbuilder.MultipleListsListbuilderHandler');

class BlockPluginsListbuilderHandler extends MultipleListsListbuilderHandler {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER, ROLE_ID_SITE_ADMIN),
			array('fetch')
		);
	}

	/**
	 * @copydoc GridHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		$router = $request->getRouter();
		if (is_object($router->getContext($request))) {
			import('lib.pkp.classes.security.authorization.ContextAccessPolicy');
			$this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));
		} else {
			import('lib.pkp.classes.security.authorization.PKPSiteAccessPolicy');
			$this->addPolicy(new PKPSiteAccessPolicy($request, array(), $roleAssignments));
		}

		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * @copydoc ListbuilderHandler::initialize()
	 */
	function initialize($request) {
		parent::initialize($request);
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_MANAGER);

		// Basic configuration
		$this->setTitle('manager.setup.layout.blockManagement');
		$this->setSaveFieldName('blocks');

		// Name column
		$nameColumn = new ListbuilderGridColumn($this, 'name', 'common.name');

		// Add lists.
		$this->addList(new ListbuilderList('sidebarContext', 'manager.setup.layout.sidebar'));
		$this->addList(new ListbuilderList('unselected', 'manager.setup.layout.unselected'));

		import('lib.pkp.controllers.listbuilder.settings.BlockPluginsListbuilderGridCellProvider');
		$nameColumn->setCellProvider(new BlockPluginsListbuilderGridCellProvider());
		$this->addColumn($nameColumn);
	}


	//
	// Overridden template methods
	//
	/**
	 * @copydoc MultipleListsListbuilderHandler::setListsData()
	 */
	function setListsData($request, $filter) {
		$leftBlockPlugins = $disabledBlockPlugins = array();
		$plugins = PluginRegistry::loadCategory('blocks');
		foreach ($plugins as $key => $junk) {
			if (!$plugins[$key]->getEnabled() || $plugins[$key]->getBlockContext() == '') {
				if (count(array_intersect($plugins[$key]->getSupportedContexts(), array(BLOCK_CONTEXT_SIDEBAR))) > 0) $disabledBlockPlugins[$key] = $plugins[$key];
			} else switch ($plugins[$key]->getBlockContext()) {
				case BLOCK_CONTEXT_SIDEBAR:
					$leftBlockPlugins[$key] = $plugins[$key];
					break;
			}
		}

		$lists = $this->getLists();
		$lists['sidebarContext']->setData($leftBlockPlugins);
		$lists['unselected']->setData($disabledBlockPlugins);
	}
}

?>
