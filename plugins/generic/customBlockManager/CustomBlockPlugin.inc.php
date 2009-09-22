<?php
/**
 * @file CustomBlockPlugin.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.CustomBlockPlugin
 * @class CustomBlockPlugin
 *
 * A generic sidebar block that can be customized through the CustomBlockManagerPlugin
 * 
 */

import('plugins.BlockPlugin');

class CustomBlockPlugin extends BlockPlugin {
	var $blockName; 
	
	function CustomBlockPlugin($blockName) {
		$this->blockName = $blockName;
		return parent::BlockPlugin();
	}
	
	/**
	 * Get the management plugin
	 * @return object
	 */
	function &getManagerPlugin() {
		$plugin =& PluginRegistry::getPlugin('generic', 'CustomBlockManagerPlugin');
		return $plugin;
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
				Locale::translate('manager.plugins.disable')
			);
			$verbs[] = array(
				'edit',
				Locale::translate('plugins.generic.customBlock.edit')
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
	 * Perform management functions
	 */
	function manage($verb, $args) {
		$returner = true;

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
		$form = new CustomBlockEditForm($this, $journal->getJournalId());
		
		switch ($verb) {
			case 'enable':
				$this->setEnabled(true);
				break;
			case 'disable':
				$this->setEnabled(false);
				break;
			case 'edit':
				$pageCrumbs[] = array(
					Request::url(null, 'manager', 'plugins'),
					Locale::translate('manager.plugins'),
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
					exit;
				} else {
					$form->addTinyMCE();
					$form->readInputData();
					$form->display();
					exit;
				}
			}
			$returner = false;	
	}

	/**
	 * Get the contents of the Block 
	 * @param $templateMgr object
	 * @return string
	 */
	function getContents(&$templateMgr) {
		$journal =& Request::getJournal();
		if (!$journal) return '';

		$templateMgr->assign('customBlockContent', $this->getSetting($journal->getJournalId(), 'blockContent'));
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
	 * Get the supported contexts (e.g. BLOCK_CONTEXT_...) for this block.
	 * @return array
	 */
	function getSupportedContexts() {
		return array(BLOCK_CONTEXT_LEFT_SIDEBAR, BLOCK_CONTEXT_RIGHT_SIDEBAR);
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
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return $this->blockName . 'CustomBlockPlugin';
	}

	/**
	 * Get the display name of this plugin.
	 * @return String
	 */
	function getDisplayName() {
		return $this->blockName . ' ' . Locale::translate('plugins.generic.customBlock.nameSuffix');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return Locale::translate('plugins.generic.customBlock.description');
	}
}

?>
