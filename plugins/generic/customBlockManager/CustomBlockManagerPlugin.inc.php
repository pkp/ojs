<?php

/**
 * @file plugins/generic/customBlockManager/CustomBlockManagerPlugin.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.customBlockManager
 * @class CustomBlockManagerPlugin
 *
 * Plugin to let users add and delete sidebar blocks
 *
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class CustomBlockManagerPlugin extends GenericPlugin {
	function getDisplayName() {
		return __('plugins.generic.customBlockManager.displayName');
	}

	function getDescription() {
		return __('plugins.generic.customBlockManager.description');
	}

	function register($category, $path) {
		if (parent::register($category, $path)) {
			if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) return true;
			if ( $this->getEnabled() ) {
				HookRegistry::register('PluginRegistry::loadCategory', array(&$this, 'callbackLoadCategory'));
			}
			return true;
		}
		return false;
	}

	/**
	 * Register as a block plugin, even though this is a generic plugin.
	 * This will allow the plugin to behave as a block plugin, i.e. to
	 * have layout tasks performed on it.
	 * @param $hookName string
	 * @param $args array
	 */
	function callbackLoadCategory($hookName, $args) {
		$category =& $args[0];
		$plugins =& $args[1];
		switch ($category) {
			case 'blocks':
				$this->import('CustomBlockPlugin');

				$journal =& Request::getJournal();
				if (!$journal) return false;

				$blocks = $this->getSetting($journal->getId(), 'blocks');
				if (!is_array($blocks)) break;
				$i=0;
				foreach ($blocks as $block) {
					$blockPlugin = new CustomBlockPlugin($block, $this->getName());

					// default the block to being enabled
					if ($blockPlugin->getEnabled() !== false) {
						$blockPlugin->setEnabled(true);
					}
					// default the block to the right sidebar
					if (!is_numeric($blockPlugin->getBlockContext())) {
						$blockPlugin->setBlockContext(BLOCK_CONTEXT_RIGHT_SIDEBAR);
					}
					$plugins[$blockPlugin->getSeq()][$blockPlugin->getPluginPath() . $i] =& $blockPlugin;

					$i++;
					unset($blockPlugin);
				}
				break;
		}
		return false;
	}

	/**
	 * Display verbs for the management interface.
	 */
	function getManagementVerbs() {
		$verbs = array();
		if ($this->getEnabled()) {
			$verbs[] = array('settings', __('plugins.generic.customBlockManager.settings'));
		}
		return parent::getManagementVerbs($verbs);
	}

	/**
	 * Perform management functions
	 */
	function manage($verb, $args, &$message, &$messageParams) {
		if (!parent::manage($verb, $args, $message, $messageParams)) return false;
		switch ($verb) {
			case 'settings':
				$journal =& Request::getJournal();

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
					),
					array(
						Request::url(null, 'manager', 'plugins'),
						__('manager.plugins'),
						true
					)
				);
				$templateMgr->assign('pageHierarchy', $pageCrumbs);

				$this->import('SettingsForm');
				$form = new SettingsForm($this, $journal->getId());
				$form->readInputData();

				if (Request::getUserVar('addBlock')) {
					// Add a block
					$editData = true;
					$blocks = $form->getData('blocks');
					array_push($blocks, '');
					$form->_data['blocks'] = $blocks;

				} else if (($delBlock = Request::getUserVar('delBlock')) && count($delBlock) == 1) {
					// Delete an block
					$editData = true;
					list($delBlock) = array_keys($delBlock);
					$delBlock = (int) $delBlock;
					$blocks = $form->getData('blocks');
					if (isset($blocks[$delBlock]) && !empty($blocks[$delBlock])) {
						$deletedBlocks = explode(':', $form->getData('deletedBlocks'));
						array_push($deletedBlocks, $blocks[$delBlock]);
						$form->setData('deletedBlocks', join(':', $deletedBlocks));
					}
					array_splice($blocks, $delBlock, 1);
					$form->_data['blocks'] = $blocks;

				} else if ( Request::getUserVar('save') ) {
					$editData = true;
					$form->execute();
				} else {
					$form->initData();
				}

				if ( !isset($editData) && $form->validate()) {
					$form->execute();
					$form->display();
					exit;
				} else {
					$form->display();
					exit;
				}
				return true;
			default:
				// Unknown management verb
				assert(false);
				return false;
		}
	}
}

?>
