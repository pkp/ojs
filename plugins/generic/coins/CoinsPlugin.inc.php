<?php

/**
 * @file CoinsPlugin.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.coins
 * @class CoinsPlugin
 *
 * COinS plugin class
 *
 * $Id$
 */

import('classes.plugins.GenericPlugin');

class CoinsPlugin extends GenericPlugin {

	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		if (!Config::getVar('general', 'installed')) return false;
		$this->addLocaleData();
		if ($success) {
			HookRegistry::register('Templates::Article::Footer::PageFooter', array($this, 'insertFooter'));
		}
		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category, and should be suitable for part of a filename
	 * (ie short, no spaces, and no dependencies on cases being unique).
	 * @return String name of plugin
	 */
	function getName() {
		return 'CoinsPlugin';
	}

	function getDisplayName() {
		return Locale::translate('plugins.generic.coins.displayName');
	}

	function getDescription() {
		return Locale::translate('plugins.generic.coins.description');
	}

	/**
	 * Get the name of the settings file to be installed site-wide when
	 * OJS is installed.
	 * @return string
	 */
	function getInstallSitePluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * Display verbs for the management interface.
	 */
	function getManagementVerbs() {
		$verbs = array();
		if ($this->getEnabled()) {
			$verbs[] = array(
				'disable',
				Locale::translate('manager.plugins.disable')
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
	 * Determine whether or not this plugin is enabled.
	 */
	function getEnabled() {
		$journal =& Request::getJournal();
		if (!$journal) return false;
		return $this->getSetting($journal->getJournalId(), 'enabled');
	}

	/**
	 * Set the enabled/disabled state of this plugin
	 */
	function setEnabled($enabled) {
		$journal =& Request::getJournal();
		if ($journal) {
			$this->updateSetting($journal->getJournalId(), 'enabled', $enabled ? true : false);
			return true;
		}
		return false;
	}

	/**
	 * Insert COinS tag.
	 */  
	function insertFooter($hookName, $params) {
		if ($this->getEnabled()) {
			$smarty =& $params[1];
			$output =& $params[2];
			$templateMgr =& TemplateManager::getManager();

			$article = $templateMgr->get_template_vars('article');
			$journal = $templateMgr->get_template_vars('currentJournal');
			$issue = $templateMgr->get_template_vars('issue');

			$authors = $article->getAuthors();
			$firstAuthor =& $authors[0];

			$vars = array(
				array('ctx_ver', 'Z39.88-2004'),
				array('rft_id', Request::url(null, 'article', 'view', $article->getArticleId())),
				array('rft_val_fmt', 'info:ofi/fmt:kev:mtx:journal'),
				array('rft.genre', 'article'),
				array('rft.title', $journal->getJournalTitle()),
				array('rft.jtitle', $journal->getJournalTitle()),
				array('rft.atitle', $article->getArticleTitle()),
				array('rft.artnum', $article->getBestArticleId()),
				array('rft.date', date('Y-m-d', strtotime($article->getDatePublished()))),
				array('rft.stitle', $journal->getLocalizedSetting('abbreviation')),
				array('rft.volume', $issue->getVolume()),
				array('rft.issue', $issue->getNumber()),
				array('rft.aulast', $firstAuthor->getLastName()),
				array('rft.aufirst', $firstAuthor->getFirstName()),
				array('rft.auinit', $firstAuthor->getMiddleName())
			);

			foreach ($authors as $author) {
				$vars[] = array('rft.au', $author->getFullName());
			}

			if ($article->getPages()) $vars[] = array('rft.pages', $article->getPages());
			if ($journal->getSetting('printIssn')) $vars[] = array('rft.issn', $journal->getSetting('printIssn'));
			if ($journal->getSetting('onlineIssn')) $vars[] = array('rft.issn', $journal->getSetting('onlineIssn'));

			$title = '';
			foreach ($vars as $entries) {
				list($name, $value) = $entries;
				$title .= $name . '=' . urlencode($value) . '&';
			}
			$title = htmlentities(substr($title, 0, -1));

			$output .= "<span class=\"Z3988\" title=\"$title\"></span>\n";
		}
		return false;
	}

	/**
	 * Perform management functions
	 */
	function manage($verb, $args) {
		switch ($verb) {
			case 'enable':
				$this->setEnabled(true);
				break;
			case 'disable':
				$this->setEnabled(false);
				break;
			default:
				Request::redirect(null, 'manager');
		}
		return false;
	}
}
?>
