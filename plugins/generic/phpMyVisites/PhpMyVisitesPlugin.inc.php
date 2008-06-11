<?php

/**
 * @file PhpMyVisitesPlugin.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.phpMyVisites
 * @class PhpMyVisitesPlugin
 *
 * phpMyVisites plugin class
 *
 * $Id$
 */

import('classes.plugins.GenericPlugin');

class PhpMyVisitesPlugin extends GenericPlugin {

	/**
	 * Called as a plugin is registered to the registry
	 * @param @category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		if (!Config::getVar('general', 'installed')) return false;
		$this->addLocaleData();
		if ($success) {
			// Insert phpmv page tag to common footer  
			HookRegistry::register('Templates::Common::Footer::PageFooter', array($this, 'insertFooter'));

			// Insert phpmv page tag to article footer
			HookRegistry::register('Templates::Article::Footer::PageFooter', array($this, 'insertFooter'));

			// Insert phpmv page tag to article interstitial footer
			HookRegistry::register('Templates::Article::Interstitial::PageFooter', array($this, 'insertFooter'));

			// Insert phpmv page tag to article pdf interstitial footer
			HookRegistry::register('Templates::Article::PdfInterstitial::PageFooter', array($this, 'insertFooter'));

			// Insert phpmv page tag to reading tools footer
			HookRegistry::register('Templates::Rt::Footer::PageFooter', array($this, 'insertFooter'));

			// Insert phpmv page tag to help footer
			HookRegistry::register('Templates::Help::Footer::PageFooter', array($this, 'insertFooter'));
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
		return 'PhpMyVisitesPlugin';
	}

	function getDisplayName() {
		return Locale::translate('plugins.generic.phpmv.displayName');
	}

	function getDescription() {
		return Locale::translate('plugins.generic.phpmv.description');
	}

	/**
	 * Extend the {url ...} smarty to support this plugin.
	 */
	function smartyPluginUrl($params, &$smarty) {
		$path = array($this->getCategory(), $this->getName());
		if (is_array($params['path'])) {
			$params['path'] = array_merge($path, $params['path']);
		} elseif (!empty($params['path'])) {
			$params['path'] = array_merge($path, array($params['path']));
		} else {
			$params['path'] = $path;
		}

		if (!empty($params['id'])) {
			$params['path'] = array_merge($params['path'], array($params['id']));
			unset($params['id']);
		}
		return $smarty->smartyUrl($params, $smarty);
	}

	/**
	 * Set the page's breadcrumbs, given the plugin's tree of items
	 * to append.
	 * @param $subclass boolean
	 */
	function setBreadcrumbs($isSubclass = false) {
		$templateMgr = &TemplateManager::getManager();
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
		if ($isSubclass) $pageCrumbs[] = array(
			Request::url(null, 'manager', 'plugins'),
			'manager.plugins'
		);

		$templateMgr->assign('pageHierarchy', $pageCrumbs);
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
			$verbs[] = array(
				'settings',
				Locale::translate('plugins.generic.phpmv.manager.settings')
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
		$journal = &Request::getJournal();
		if (!$journal) return false;
		return $this->getSetting($journal->getJournalId(), 'enabled');
	}

	/**
	 * Set the enabled/disabled state of this plugin
	 */
	function setEnabled($enabled) {
		$journal = &Request::getJournal();
		if ($journal) {
			$this->updateSetting($journal->getJournalId(), 'enabled', $enabled ? true : false);
			return true;
		}
		return false;
	}

	/**
	 * Insert phpmv page tag to footer
	 */  
	function insertFooter($hookName, $params) {
		if ($this->getEnabled()) {
			$smarty = &$params[1];
			$output = &$params[2];
			$templateMgr = &TemplateManager::getManager();
			$currentJournal = $templateMgr->get_template_vars('currentJournal');

			if (!empty($currentJournal)) {
				$journal = &Request::getJournal();
				$journalId = $journal->getJournalId();
				$phpmvSiteId = $this->getSetting($journalId, 'phpmvSiteId');
				$phpmvUrl = $this->getSetting($journalId, 'phpmvUrl');

				if (!empty($phpmvSiteId) && !empty($phpmvUrl)) {
					$templateMgr->assign('phpmvSiteId', $phpmvSiteId);
					$templateMgr->assign('phpmvUrl', $phpmvUrl);
					$output .= $templateMgr->fetch($this->getTemplatePath() . 'pageTag.tpl'); 
				}
			}
		}
		return false;
	}

	/**
	 * Perform management functions
	 */
	function manage($verb, $args) {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));
		$journal = &Request::getJournal();
		$returner = true;

		switch ($verb) {
			case 'enable':
				$this->setEnabled(true);
				$returner = false;
				break;
			case 'disable':
				$this->setEnabled(false);
				$returner = false;
				break;
			case 'settings':
				if ($this->getEnabled()) {
					$this->import('PhpMyVisitesSettingsForm');
					$form = &new PhpMyVisitesSettingsForm($this, $journal->getJournalId());
					if (Request::getUserVar('save')) {
						$form->readInputData();
						if ($form->validate()) {
							$form->execute();
							Request::redirect(null, 'manager', 'plugin');
						} else {
							$this->setBreadCrumbs(true);
							$form->display();
						}
					} else {
						$this->setBreadCrumbs(true);
						$form->initData();
						$form->display();
					}
				} else {
					Request::redirect(null, 'manager');
				}
				break;
			default:
				Request::redirect(null, 'manager');
		}
		return $returner;
	}
}
?>
