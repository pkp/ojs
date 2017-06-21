<?php

/**
 * @file plugins/generic/coins/CoinsPlugin.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CoinsPlugin
 * @ingroup plugins_generic_coins
 *
 * @brief COinS plugin class
 */


import('lib.pkp.classes.plugins.GenericPlugin');

class CoinsPlugin extends GenericPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) return true;
		if ($success && $this->getEnabled()) {
			HookRegistry::register('Templates::Article::Footer::PageFooter', array($this, 'insertFooter'));
			HookRegistry::register('Templates::Issue::Issue::Article', array($this, 'insertFooter'));
		}
		return $success;
	}

	function getDisplayName() {
		return __('plugins.generic.coins.displayName');
	}

	function getDescription() {
		return __('plugins.generic.coins.description');
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
	 * Insert COinS tag.
	 */
	function insertFooter($hookName, $params) {
		if ($this->getEnabled()) {
			$smarty = $params[1];
			$output =& $params[2];
			$request = $this->getRequest();
			$templateMgr = TemplateManager::getManager($request);

			$article = $templateMgr->get_template_vars('article');
			$journal = $templateMgr->get_template_vars('currentJournal');
			$issue = $templateMgr->get_template_vars('issue');

			if (!$issue || !$journal || !$article) return false;

			$vars = array(
				array('ctx_ver', 'Z39.88-2004'),
				array('rft_id', $request->url(null, 'article', 'view', $article->getId())),
				array('rft_val_fmt', 'info:ofi/fmt:kev:mtx:journal'),
				array('rft.genre', 'article'),
				array('rft.title', $journal->getLocalizedName()),
				array('rft.jtitle', $journal->getLocalizedName()),
				array('rft.atitle', $article->getLocalizedTitle()),
				array('rft.artnum', $article->getBestArticleId()),
				array('rft.stitle', $journal->getLocalizedSetting('abbreviation')),
				array('rft.volume', $issue->getShowVolume()?$issue->getVolume():''),
				array('rft.issue', $issue->getShowNumber()?$issue->getNumber():''),
			);

			$authors = $article->getAuthors();
			if ($firstAuthor = array_shift($authors)) {
				$vars = array_merge($vars, array(
					array('rft.aulast', $firstAuthor->getLastName()),
					array('rft.aufirst', $firstAuthor->getFirstName()),
					array('rft.auinit', $firstAuthor->getMiddleName()),
				));
			}

			$datePublished = $article->getDatePublished();
			if (!$datePublished) $datePublished = $issue->getDatePublished();
			if ($datePublished) {
				$vars[] = array('rft.date', date('Y-m-d', strtotime($datePublished)));
			}

			foreach ($authors as $author) {
				$vars[] = array('rft.au', $author->getFullName());
			}

			if ($doi = $article->getStoredPubId('doi')) $vars[] = array('rft_id', 'info:doi/' . $doi);
			if ($article->getPages()) $vars[] = array('rft.pages', $article->getPages());
			if ($journal->getSetting('printIssn')) $vars[] = array('rft.issn', $journal->getSetting('printIssn'));
			if ($journal->getSetting('onlineIssn')) $vars[] = array('rft.eissn', $journal->getSetting('onlineIssn'));

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
}
?>
