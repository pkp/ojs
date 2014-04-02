<?php

/**
 * @file plugins/generic/lucene/LuceneFacetsBlockPlugin.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
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
	function LuceneFacetsBlockPlugin($parentPluginName) {
		$this->_parentPluginName = $parentPluginName;
		parent::BlockPlugin();
	}


	//
	// Implement template methods from PKPPlugin.
	//
	/**
	 * @see PKPPlugin::getHideManagement()
	 */
	function getHideManagement() {
		return true;
	}

	/**
	 * @see PKPPlugin::getName()
	 */
	function getName() {
		return 'LuceneFacetsBlockPlugin';
	}

	/**
	 * @see PKPPlugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.generic.lucene.faceting.displayName');
	}

	/**
	 * @see PKPPlugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.generic.lucene.faceting.description');
	}

	/**
	 * @see PKPPlugin::getPluginPath()
	 */
	function getPluginPath() {
		$plugin =& $this->_getLucenePlugin();
		return $plugin->getPluginPath();
	}

	/**
	 * @see PKPPlugin::getTemplatePath()
	 */
	function getTemplatePath() {
		$plugin =& $this->_getLucenePlugin();
		return $plugin->getTemplatePath();
	}

	/**
	 * @see PKPPlugin::getSeq()
	 */
	function getSeq() {
		// Identify the position of the faceting block.
		$seq = parent::getSeq();

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
	 * @see LazyLoadPlugin::getEnabled()
	 */
	function getEnabled() {
		$plugin =& $this->_getLucenePlugin();
		return $plugin->getEnabled();
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
			$blockContext = BLOCK_CONTEXT_LEFT_SIDEBAR;
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
	function getContents(&$templateMgr, $request = null) {
		// Get facets from the parent plug-in.
		$plugin =& $this->_getLucenePlugin();
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
	function &_getLucenePlugin() {
		$plugin =& PluginRegistry::getPlugin('generic', $this->_parentPluginName);
		return $plugin;
	}
}

?>
