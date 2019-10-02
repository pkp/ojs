<?php

/**
 * @file plugins/blocks/browse/BrowseBlockPlugin.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class BrowseBlockPlugin
 * @ingroup plugins_blocks_browse
 *
 * @brief Class for browse block plugin
 */

import('lib.pkp.classes.plugins.BlockPlugin');

class BrowseBlockPlugin extends BlockPlugin {
	/**
	 * Get the display name of this plugin.
	 * @return String
	 */
	function getDisplayName() {
		return __('plugins.block.browse.displayName');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return __('plugins.block.browse.description');
	}

	/**
	 * Get the HTML contents of the browse block.
	 * @param $templateMgr PKPTemplateManager
	 * @return string
	 */
	function getContents($templateMgr, $request = null) {
		$context = $request->getContext();
		if (!$context) {
			return '';
		}
		$categoryDao = DAORegistry::getDAO('CategoryDAO');
		$router = $request->getRouter();

		$requestedCategoryPath = null;
		$args = $router->getRequestedArgs($request);
		if ($router->getRequestedPage($request) . '/' . $router->getRequestedOp($request) == 'catalog/category') $requestedCategoryPath = reset($args);
		$templateMgr->assign(array(
			'browseBlockSelectedCategory' => $requestedCategoryPath,
			'browseCategoryFactory' => $categoryDao->getByContextId($context->getId()),
		));
		return parent::getContents($templateMgr);
	}
}
