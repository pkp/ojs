<?php

/**
 * @file classes/plugins/BlockPlugin.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class BlockPlugin
 * @ingroup plugins
 *
 * @brief Abstract class for block plugins
 */

// $Id$


define('BLOCK_CONTEXT_LEFT_SIDEBAR',		0x00000001);
define('BLOCK_CONTEXT_RIGHT_SIDEBAR', 		0x00000002);
define('BLOCK_CONTEXT_HOMEPAGE',		0x00000003);

class BlockPlugin extends Plugin {
	function register($category, $path) {
		$success = parent::register($category, $path);
		if ($success && $this->getEnabled()) {
			$contextMap =& $this->getContextMap();
			$blockContext = $this->getBlockContext();
			if (isset($contextMap[$blockContext])) {
				$hookName = $contextMap[$blockContext];
				HookRegistry::register($hookName, array(&$this, 'callback'));
			}
		}
		return $success;
	}

	/**
	 * Get the block context (e.g. BLOCK_CONTEXT_...) for this block.
	 * @return int
	 */
	function getBlockContext() {
		$journal =& Request::getJournal();
		$journalId = ($journal?$journal->getJournalId():0);
		return $this->getSetting($journalId, 'context');
	}

	/**
	 * Set the block context (e.g. BLOCK_CONTEXT_...) for this block.
	 * @param context int
	 */
	function setBlockContext($context) {
		$journal =& Request::getJournal();
		$journalId = ($journal?$journal->getJournalId():0);
		return $this->updateSetting($journalId, 'context', $context, 'int');
	}

	/**
	 * Get the supported contexts (e.g. BLOCK_CONTEXT_...) for this block.
	 * @return array
	 */
	function getSupportedContexts() {
		fatalError('ABSTRACT METHOD');
	}

	/**
	 * Determine whether or not this plugin is currently enabled.
	 * @return boolean
	 */
	function getEnabled() {
		$journal =& Request::getJournal();
		$journalId = ($journal?$journal->getJournalId():0);
		return $this->getSetting($journalId, 'enabled');
	}

	/**
	 * Set whether or not this plugin is currently enabled.
	 * @param $enabled boolean
	 */
	function setEnabled($enabled) {
		$journal =& Request::getJournal();
		$journalId = ($journal?$journal->getJournalId():0);
		return $this->updateSetting($journalId, 'enabled', $enabled, 'bool');
	}

	/**
	 * Get the sequence information for this plugin.
	 * Higher numbers move plugins down the page compared to other blocks.
	 * @return int
	 */
	function getSeq() {
		$journal =& Request::getJournal();
		$journalId = ($journal?$journal->getJournalId():0);
		return $this->getSetting($journalId, 'seq');
	}

	/**
	 * Set the sequence information for this plugin.
	 * Higher numbers move plugins down the page compared to other blocks.
	 * @param i int
	 */
	function setSeq($i) {
		$journal =& Request::getJournal();
		$journalId = ($journal?$journal->getJournalId():0);
		return $this->updateSetting($journalId, 'seq', $i, 'int');
	}

	/**
	 * Get an associative array linking block context to hook name.
	 * @return array
	 */
	function &getContextMap() {
		static $contextMap = array(
			BLOCK_CONTEXT_LEFT_SIDEBAR => 'Templates::Common::LeftSidebar',
			BLOCK_CONTEXT_RIGHT_SIDEBAR => 'Templates::Common::RightSidebar',
			BLOCK_CONTEXT_HOMEPAGE => 'Templates::Index::journal'
		);
		HookRegistry::call('BlockPlugin::getContextMap', array(&$this, &$contextMap));
		return $contextMap;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		// This should not be used as this is an abstract class
		return 'BlockPlugin';
	}

	/**
	 * Get the display name of this plugin.
	 * @return String
	 */
	function getDisplayName() {
		// This name should never be displayed because child classes
		// will override this method.
		return 'Abstract Block Plugin';
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return 'This is the BlockPlugin base class. Its functions can be overridden by subclasses to provide support for UI blocks.';
	}

	/**
	 * Get the filename of the template block. (Default behavior may
	 * be overridden through some combination of this function and the
	 * getContents function.)
	 * Returning null from this function results in an empty display.
	 * @return string
	 */
	function getBlockTemplateFilename() {
		return 'block.tpl';
	}

	/**
	 * Get the HTML contents for this block.
	 * @param $templateMgr object
	 * @return string
	 */
	function getContents(&$templateMgr) {
		$blockTemplateFilename = $this->getBlockTemplateFilename();
		if ($blockTemplateFilename === null) return '';
		return $templateMgr->fetch($this->getTemplatePath() . '/' . $blockTemplateFilename);
	}

	function callback($hookName, &$args) {
		$params =& $args[0];
		$smarty =& $args[1];
		$output =& $args[2];
		$output .= $this->getContents($smarty);
	}
}

?>
