<?php

/**
 * @file plugins/generic/customBlockManager/CustomBlockPlugin.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.CustomBlockPlugin
 * @class CustomBlockPlugin
 *
 * A generic sidebar block that can be customized through the CustomBlockManagerPlugin
 *
 */

import('lib.pkp.classes.plugins.BlockPlugin');

class CustomBlockPlugin extends BlockPlugin {
	var $blockName;

	/** @var $parentPluginName string Name of parent plugin */
	var $parentPluginName;

	/**
	 * Constructor
	 */
	function CustomBlockPlugin($blockName, $parentPluginName) {
		$this->blockName = $blockName;
		$this->parentPluginName = $parentPluginName;
		parent::BlockPlugin();
	}

	/**
	 * Get the management plugin
	 * @return object
	 */
	function &getManagerPlugin() {
		$plugin =& PluginRegistry::getPlugin('generic', $this->parentPluginName);
		return $plugin;
	}

	/**
	 * Get the symbolic name of the plugin.
	 * @return string
	 */
	function getName() {
		return $this->blockName;
	}

	/**
	 * Override the builtin to get the correct plugin path.
	 * @return string
	 */
	function getPluginPath() {
		$plugin =& $this->getManagerPlugin();
		return $plugin->getPluginPath();
	}

	/**
	 * Override the builtin to get the correct template path.
	 * @return string
	 */
	function getTemplatePath() {
		$plugin =& $this->getManagerPlugin();
		return $plugin->getTemplatePath();
	}

	/**
	 * Determine whether the plugin is enabled. Overrides parent so that
	 * the plugin will be displayed during install.
	 */
	function getEnabled() {
		if (!Config::getVar('general', 'installed')) return true;
		return parent::getEnabled();
	}

	function getManagementVerbs() {
		$verbs = parent::getManagementVerbs();
		if ($this->getEnabled()) {
			$verbs[] = array(
				'disable',
				__('manager.plugins.disable')
			);
			$verbs[] = array(
				'edit',
				__('plugins.generic.customBlock.edit')
			);
		} else {
			$verbs[] = array(
				'enable',
				__('manager.plugins.enable')
			);
		}
		return $verbs;
	}

	/**
	 * Perform management functions
	 */
	function manage($verb, $args) {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));

		$pageCrumbs = array(
			array(
				Request::url(null, 'user'),
				'navigation.user'
			),
			array(
				Request::url(null, 'manager'),
				'user.role.manager'
			)
		);

		$journal =& Request::getJournal();

		$this->import('CustomBlockEditForm');
		$form = new CustomBlockEditForm($this, $journal->getId());

		switch ($verb) {
			case 'enable':
				$this->setEnabled(true);
				return false;
			case 'disable':
				$this->setEnabled(false);
				return false;
			case 'edit':
				$pageCrumbs[] = array(
					Request::url(null, 'manager', 'plugins'),
					__('manager.plugins'),
					true
				);

				$templateMgr->assign('pageHierarchy', $pageCrumbs);
				$form->initData();
				$form->display();
				exit;

			case 'save':
				$form->readInputData();
				if ($form->validate()) {
					$form->save();
					$pageCrumbs[] = array(Request::url(null, 'manager', 'plugins'), 'manager.plugins');
					$templateMgr->assign(array(
						'currentUrl' => Request::url(null, null, null, array($this->getCategory(), $this->getName(), 'edit')),
						'pageTitleTranslated' => $this->getDisplayName(),
						'pageHierarchy' => $pageCrumbs,
						'message' => 'plugins.generic.customBlock.saved',
						'backLink' => Request::url(null, 'manager', 'plugins'),
						'backLinkLabel' => 'common.continue'
					));
					$templateMgr->display('common/message.tpl');
				} else {
					$form->addTinyMCE();
					$form->readInputData();
					$form->display();
				}
				exit;
		}
		return false;
	}

	/**
	 * Get the contents of the Block
	 * @param $templateMgr object
	 * @return string
	 */
	function getContents(&$templateMgr) {
		$journal =& Request::getJournal();
		if (!$journal) return '';

		$templateMgr->assign('customBlockContent', $this->getSetting($journal->getId(), 'blockContent'));
		return parent::getContents($templateMgr);

	}

	/**
	 * Get the block context. Overrides parent so that the plugin will be
	 * displayed during install.
	 * @return int
	 */
	function getBlockContext() {
		if (!Config::getVar('general', 'installed')) return BLOCK_CONTEXT_RIGHT_SIDEBAR;
		return parent::getBlockContext();
	}

	/**
	 * Determine the plugin sequence. Overrides parent so that
	 * the plugin will be displayed during install.
	 */
	function getSeq() {
		if (!Config::getVar('general', 'installed')) return 1;
		return parent::getSeq();
	}

	/**
	 * Get the display name of this plugin.
	 * @return String
	 */
	function getDisplayName() {
		return $this->blockName . ' ' . __('plugins.generic.customBlock.nameSuffix');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return __('plugins.generic.customBlock.description');
	}
}

?>
