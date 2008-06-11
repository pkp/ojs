<?php

/**
 * @file ThesisPlugin.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.thesis
 * @class ThesisPlugin
 *
 * Thesis abstracts plugin class
 *
 * $Id$
 */

import('classes.plugins.GenericPlugin');

class ThesisPlugin extends GenericPlugin {

	/**
	 * Called as a plugin is registered to the registry
	 * @param @category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		$this->addLocaleData();
		if ($success && $this->getEnabled()) {
			$this->import('ThesisDAO');
			$thesisDao = &new ThesisDAO();
			$returner = &DAORegistry::registerDAO('ThesisDAO', $thesisDao);

			// Handler for public thesis abstract pages
			HookRegistry::register('LoadHandler', array($this, 'setupPublicHandler'));

			// Navigation bar link to thesis abstract page
			HookRegistry::register('Templates::Common::Header::Navbar::CurrentJournal', array($this, 'displayHeaderLink'));

			// Journal Manager link to thesis abstract management pages
			HookRegistry::register('Templates::Manager::Index::ManagementPages', array($this, 'displayManagerLink'));

			// Search results link to thesis abstract page
			HookRegistry::register('Templates::Search::SearchResults::PreResults', array($this, 'displaySearchLink'));
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
		return 'ThesisPlugin';
	}

	function getDisplayName() {
		return Locale::translate('plugins.generic.thesis.displayName');
	}

	function getDescription() {
		return Locale::translate('plugins.generic.thesis.description');
	}

	/**
	 * Get the filename of the ADODB schema for this plugin.
	 */
	function getInstallSchemaFile() {
		return $this->getPluginPath() . '/' . 'schema.xml';
	}

	/**
	 * Get the filename of the install data for this plugin.
	 */
	function getInstallDataFile() {
		return $this->getPluginPath() . '/' . 'data.xml';
	}

	/**
	 * Extend the {url ...} smarty to support thesis plugin.
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
			Request::url(null, 'manager', 'plugin', array('generic', $this->getName(), 'theses')),
			$this->getDisplayName(),
			true
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
				'theses',
				Locale::translate('plugins.generic.thesis.manager.theses')
			);
			$verbs[] = array(
				'settings',
				Locale::translate('plugins.generic.thesis.manager.settings')
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

	function setupPublicHandler($hookName, $params) {
		$page = &$params[0];
		if ($page == 'thesis') {
			define('HANDLER_CLASS', 'ThesisHandler');
			$handlerFile = &$params[2];
			$handlerFile = $this->getPluginPath() . '/' . 'ThesisHandler.inc.php';
		}
	}

	function displayHeaderLink($hookName, $params) {
		if ($this->getEnabled()) {
			$smarty = &$params[1];
			$output = &$params[2];
			$output .= '<li><a href="' . TemplateManager::smartyUrl(array('page'=>'thesis'), $smarty) . '">' . TemplateManager::smartyTranslate(array('key'=>'plugins.generic.thesis.headerLink'), $smarty) . '</a></li>';
		}
		return false;
	}

	function displayManagerLink($hookName, $params) {
		if ($this->getEnabled()) {
			$smarty = &$params[1];
			$output = &$params[2];
			$output .= '<li>&#187; <a href="' . $this->smartyPluginUrl(array('op'=>'plugin', 'path'=>'theses'), $smarty) . '">' . TemplateManager::smartyTranslate(array('key'=>'plugins.generic.thesis.manager.theses'), $smarty) . '</a></li>';
		}
		return false;
	}

	function displaySearchLink($hookName, $params) {
		if ($this->getEnabled()) {
			$smarty = &$params[1];
			$output = &$params[2];
			$currentJournal = $smarty->get_template_vars('currentJournal');
			if (!empty($currentJournal)) {
				$output .= '<a href="' . TemplateManager::smartyUrl(array('page'=>'thesis'), $smarty) . '" class="action">' . TemplateManager::smartyTranslate(array('key'=>'plugins.generic.thesis.searchLink'), $smarty) . '</a><br /><br />';
			}
		}
		return false;
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
					$this->import('ThesisSettingsForm');
					$form = &new ThesisSettingsForm($this, $journal->getJournalId());
					if (Request::getUserVar('save')) {
						$form->readInputData();
						if ($form->validate()) {
							$form->execute();
							Request::redirect(null, 'manager', 'plugin', array('generic', $this->getName(), 'theses'));
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
			case 'delete':
				if ($this->getEnabled()) {
					if (!empty($args)) {
						$thesisId = (int) $args[0];	
						$thesisDao = &DAORegistry::getDAO('ThesisDAO');

						// Ensure thesis is for this journal
						if ($thesisDao->getThesisJournalId($thesisId) == $journal->getJournalId()) {
							$thesisDao->deleteThesisById($thesisId);
						}
					}
					Request::redirect(null, 'manager', 'plugin', array('generic', $this->getName(), 'theses'));
				} else {
					Request::redirect(null, 'manager');
				}
				break;
			case 'create':
			case 'edit':
				if ($this->getEnabled()) {
					$thesisId = !isset($args) || empty($args) ? null : (int) $args[0];
					$thesisDao = &DAORegistry::getDAO('ThesisDAO');

					// Ensure thesis is valid and for this journal
					if (($thesisId != null && $thesisDao->getThesisJournalId($thesisId) == $journal->getJournalId()) || ($thesisId == null)) {
						$this->import('ThesisForm');

						if ($thesisId == null) {
							$templateMgr->assign('thesisTitle', 'plugins.generic.thesis.manager.createTitle');
						} else {
							$templateMgr->assign('thesisTitle', 'plugins.generic.thesis.manager.editTitle');	
						}

						$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
						$journalSettings = &$journalSettingsDao->getJournalSettings($journal->getJournalId());

						$thesisForm = &new ThesisForm($thesisId);
						$thesisForm->initData();
						$this->setBreadCrumbs(true);
						$templateMgr->assign('journalSettings', $journalSettings);
						$thesisForm->display();
					} else {
						Request::redirect(null, 'manager', 'plugin', array('generic', $this->getName(), 'theses'));
					}
				} else {
					Request::redirect(null, 'manager');
				}
				break;
			case 'update':
				if ($this->getEnabled()) {
					$this->import('ThesisForm');
					$thesisId = Request::getUserVar('thesisId') == null ? null : (int) Request::getUserVar('thesisId');
					$thesisDao = &DAORegistry::getDAO('ThesisDAO');

					if (($thesisId != null && $thesisDao->getThesisJournalId($thesisId) == $journal->getJournalId()) || $thesisId == null) {

						$thesisForm = &new ThesisForm($thesisId);
						$thesisForm->readInputData();

						if ($thesisForm->validate()) {
							$thesisForm->execute();

							if (Request::getUserVar('createAnother')) {
								Request::redirect(null, 'manager', 'plugin', array('generic', $this->getName(), 'create'));
							} else {
								Request::redirect(null, 'manager', 'plugin', array('generic', $this->getName(), 'theses'));
							}				
						} else {
							if ($thesisId == null) {
								$templateMgr->assign('thesisTitle', 'plugins.generic.thesis.manager.createTitle');
							} else {
								$templateMgr->assign('thesisTitle', 'plugins.generic.thesis.manager.editTitle');	
							}

							$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
							$journalSettings = &$journalSettingsDao->getJournalSettings($journal->getJournalId());

							$this->setBreadCrumbs(true);
							$templateMgr->assign('journalSettings', $journalSettings);
							$thesisForm->display();
						}		
					} else {
						Request::redirect(null, 'manager', 'plugin', array('generic', $this->getName(), 'theses'));
					}
				} else {
					Request::redirect(null, 'manager');
				}	
				break;
			default:
				if ($this->getEnabled()) {
					$this->import('Thesis');
					$searchField = null;
					$searchMatch = null;
					$search = Request::getUserVar('search');
					$dateFrom = Request::getUserDateVar('dateFrom', 1, 1);
					if ($dateFrom !== null) $dateFrom = date('Y-m-d H:i:s', $dateFrom);
					$dateTo = Request::getUserDateVar('dateTo', 32, 12, null, 23, 59, 59);
					if ($dateTo !== null) $dateTo = date('Y-m-d H:i:s', $dateTo);

					if (isset($search)) {
						$searchField = Request::getUserVar('searchField');
						$searchMatch = Request::getUserVar('searchMatch');
					}			

					$rangeInfo = &Handler::getRangeInfo('theses');
					$thesisDao = &DAORegistry::getDAO('ThesisDAO');
					$theses = &$thesisDao->getThesesByJournalId($journal->getJournalId(), $searchField, $search, $searchMatch, $dateFrom, $dateTo, null, $rangeInfo);

					$templateMgr->assign('theses', $theses);
					$this->setBreadCrumbs();

					// Set search parameters
					$duplicateParameters = array(
						'searchField', 'searchMatch', 'search',
						'dateFromMonth', 'dateFromDay', 'dateFromYear',
						'dateToMonth', 'dateToDay', 'dateToYear'
					);
					foreach ($duplicateParameters as $param)
						$templateMgr->assign($param, Request::getUserVar($param));

					$templateMgr->assign('dateFrom', $dateFrom);
					$templateMgr->assign('dateTo', $dateTo);
					$templateMgr->assign('yearOffsetPast', THESIS_APPROVED_YEAR_OFFSET_PAST);

					$fieldOptions = Array(
						THESIS_FIELD_FIRSTNAME => 'plugins.generic.thesis.manager.studentFirstName',
						THESIS_FIELD_LASTNAME => 'plugins.generic.thesis.manager.studentLastName',
						THESIS_FIELD_EMAIL => 'plugins.generic.thesis.manager.studentEmail',
						THESIS_FIELD_DEPARTMENT => 'plugins.generic.thesis.manager.department',
						THESIS_FIELD_UNIVERSITY => 'plugins.generic.thesis.manager.university',
						THESIS_FIELD_TITLE => 'plugins.generic.thesis.manager.title',
						THESIS_FIELD_ABSTRACT => 'plugins.generic.thesis.manager.abstract',
						THESIS_FIELD_SUBJECT => 'plugins.generic.thesis.manager.keyword'
					);
					$templateMgr->assign('fieldOptions', $fieldOptions);

					$templateMgr->display($this->getTemplatePath() . 'theses.tpl');
				} else {
					Request::redirect(null, 'manager');
				}
		}
		return $returner;
	}

}
?>
