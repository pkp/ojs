<?php

/**
 * @file plugins/generic/lucene/LuceneFacetsBlockPlugin.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class LuceneFacetsBlockPlugin
 * @ingroup plugins_generic_lucene
 *
 * @brief Lucene plugin, faceting block component
 */

import('lib.pkp.classes.plugins.BlockPlugin');

class LuceneFacetsBlockPlugin extends BlockPlugin {

	/** @var string */
	var $_parentPluginName;


	/**
	 * Constructor
	 * @param $parentPluginName string
	 */
	function __construct($parentPluginName) {
		$this->_parentPluginName = $parentPluginName;
		parent::__construct();
	}


	//
	// Implement template methods from Plugin.
	//
	/**
	 * @see Plugin::getHideManagement()
	 */
	function getHideManagement() {
		return true;
	}

	/**
	 * @see Plugin::getName()
	 */
	function getName() {
		return 'LuceneFacetsBlockPlugin';
	}

	/**
	 * @see Plugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.generic.lucene');
	}

	/**
	 * @see Plugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.generic.lucene.description');
	}

	/**
	 * @see Plugin::getPluginPath()
	 */
	function getPluginPath() {
		$plugin = $this->_getLucenePlugin();
		return $plugin->getPluginPath();
	}

	/**
	 * @copydoc PKPPlugin::getTemplatePath
	 */
	function getTemplatePath($inCore = false) {
		$plugin = $this->_getLucenePlugin();
		return $plugin->getTemplatePath($inCore);
	}

	/**
	 * @copydoc BlockPlugin::getSeq()
	 */
	function getSeq($contextId = null) {
		// Identify the position of the faceting block.
		$seq = parent::getSeq($contextId);

		// If nothing has been configured then use the first
		// position. This is ok as we'll only display facets
		// in a search results context where they have a high
		// relevance by default.
		if (!is_numeric($seq)) $seq = 0;

		return $seq;
	}


	//
	// Implement template methods from LazyLoadPlugin
	//
	/**
	 * @copydoc LazyLoadPlugin::getEnabled()
	 */
	function getEnabled($contextId = null) {
		$plugin = $this->_getLucenePlugin();
		return $plugin->getEnabled($contextId);
	}


	//
	// Implement template methods from BlockPlugin
	//
	/**
	 * @see BlockPlugin::getBlockContext()
	 */
	function getBlockContext() {
		$blockContext = parent::getBlockContext();

		// Place the block on the left by default
		// where navigation will usually be expected
		// by the user.
		if (!in_array($blockContext, $this->getSupportedContexts())) {
			$blockContext = BLOCK_CONTEXT_SIDEBAR;
		}

		return $blockContext;
	}

	/**
	 * @see BlockPlugin::getBlockTemplateFilename()
	 */
	function getBlockTemplateFilename() {
		// Return the facets template.
		return 'facetsBlock.tpl';
	}

	/**
	 * @see BlockPlugin::getContents()
	 */
	function getContents($templateMgr, $request = null) {
		// Get facets from the parent plug-in.
		$plugin = $this->_getLucenePlugin();
		$facets = $plugin->getFacets();

		// Check whether we got any facets to display.
		$hasFacets = false;
		if (is_array($facets)) {
			foreach($facets as $facetCategory => $facetList) {
				if (count($facetList) > 0) {
					$hasFacets = true;
					break;
				}
			}
		}

		// Do not display the block if we got no facets.
		if (!$hasFacets) return '';

		$templateMgr->assign('facets', $facets);
		return parent::getContents($templateMgr, $request);
	}


	//
	// Private helper methods
	//
	/**
	 * Get the lucene plugin object
	 * @return LucenePlugin
	 */
	function _getLucenePlugin() {
		return PluginRegistry::getPlugin('generic', $this->_parentPluginName);
	}
}


