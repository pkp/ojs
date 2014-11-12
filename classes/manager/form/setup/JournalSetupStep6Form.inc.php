<?php

/**
 * @file classes/manager/form/setup/JournalSetupStep6Form.inc.php
 *
 * @class JournalSetupStep6Form
 * @ingroup manager_form_setup
 *
 * @brief Form for Step 6 of journal setup.
 */

// $Id$


import('classes.manager.form.setup.JournalSetupForm');

class JournalSetupStep6Form extends JournalSetupForm {
	/**
	 * Constructor.
	 */
	function JournalSetupStep6Form() {
		parent::JournalSetupForm(
			6,
			array()
		);
	}

	/**
	 * Get the list of field names for which localized settings are used.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array();

	}

	/**
	 * Display the form.
	 */
	function display($request, $dispatcher) {
		$journal =& $request->getJournal();

		$allThemes =& PluginRegistry::loadCategory('themes');
		$journalThemes = array();
		foreach ($allThemes as $key => $junk) {
			$plugin =& $allThemes[$key]; // by ref
			$journalThemes[basename($plugin->getPluginPath())] =& $plugin;
			unset($plugin);
		}

		$templateMgr =& TemplateManager::getManager();

		# Special URL is used to reach Subi's brand file configuration page.
		$templateMgr->assign('subiURL',
			 ($_SERVER["HTTPS"] ? 'https://' : 'http://') . $_SERVER["SERVER_NAME"] . 
			'/subi/configure?configView=compact&entity=' . $journal->getPath());

 		$sessionManager = SessionManager::getManager();
		$session = $sessionManager->getUserSession();
		$user = $session->getUser();
		$templateMgr->assign('user', $user->getUserName());

		$templateMgr->assign('journalPath',$journal->getPath());

		// Make lists of the sidebar blocks available.
		$leftBlockPlugins = $disabledBlockPlugins = $rightBlockPlugins = array();
		$plugins =& PluginRegistry::loadCategory('blocks');
		foreach ($plugins as $key => $junk) {
			if (!$plugins[$key]->getEnabled() || $plugins[$key]->getBlockContext() == '') {
				if (count(array_intersect($plugins[$key]->getSupportedContexts(), array(BLOCK_CONTEXT_LEFT_SIDEBAR, BLOCK_CONTEXT_RIGHT_SIDEBAR))) > 0) $disabledBlockPlugins[] =& $plugins[$key];
			} else switch ($plugins[$key]->getBlockContext()) {
				case BLOCK_CONTEXT_LEFT_SIDEBAR:
					$leftBlockPlugins[] =& $plugins[$key];
					break;
				case BLOCK_CONTEXT_RIGHT_SIDEBAR:
					$rightBlockPlugins[] =& $plugins[$key];
					break;
			}
		}
		$templateMgr->assign(array(
			'disabledBlockPlugins' => &$disabledBlockPlugins,
			'leftBlockPlugins' => &$leftBlockPlugins,
			'rightBlockPlugins' => &$rightBlockPlugins
		));

		$templateMgr->setCacheability(CACHEABILITY_MUST_REVALIDATE);
		parent::display($request, $dispatcher);
	}

	function execute() {
		// Save the block plugin layout settings.
		$blockVars = array('blockSelectLeft', 'blockUnselected', 'blockSelectRight');
		foreach ($blockVars as $varName) {
			$$varName = array_map('urldecode', split(' ', Request::getUserVar($varName)));
		}

		$plugins =& PluginRegistry::loadCategory('blocks');
		foreach ($plugins as $key => $junk) {
			$plugin =& $plugins[$key]; // Ref hack
			$plugin->setEnabled(!in_array($plugin->getName(), $blockUnselected));
			if (in_array($plugin->getName(), $blockSelectLeft)) {
				$plugin->setBlockContext(BLOCK_CONTEXT_LEFT_SIDEBAR);
				$plugin->setSeq(array_search($key, $blockSelectLeft));
			}
			else if (in_array($plugin->getName(), $blockSelectRight)) {
				$plugin->setBlockContext(BLOCK_CONTEXT_RIGHT_SIDEBAR);
				$plugin->setSeq(array_search($key, $blockSelectRight));
			}
			unset($plugin);
		}

		return parent::execute();
	}
}

?>
