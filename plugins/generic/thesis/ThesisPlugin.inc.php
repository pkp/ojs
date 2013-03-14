<?php

/**
 * @file plugins/generic/thesis/ThesisPlugin.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ThesisPlugin
 * @ingroup plugins_generic_thesis
 *
 * @brief Thesis abstracts plugin class
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class ThesisPlugin extends GenericPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		if ($success && $this->getEnabled()) {
			$this->import('ThesisDAO');
			$thesisDao = new ThesisDAO($this->getName());
			$returner =& DAORegistry::registerDAO('ThesisDAO', $thesisDao);

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

	function getDisplayName() {
		return __('plugins.generic.thesis.displayName');
	}

	function getDescription() {
		return __('plugins.generic.thesis.description');
	}

	/**
	 * Get the filename of the ADODB schema for this plugin.
	 */
	function getInstallSchemaFile() {
		return $this->getPluginPath() . '/' . 'schema.xml';
	}

	function getInstallEmailTemplatesFile() {
		return ($this->getPluginPath() . DIRECTORY_SEPARATOR . 'emailTemplates.xml');
	}

	function getInstallEmailTemplateDataFile() {
		return ($this->getPluginPath() . '/locale/{$installedLocale}/emailTemplates.xml');
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
	 * Display verbs for the management interface.
	 */
	function getManagementVerbs() {
		$verbs = parent::getManagementVerbs();
		if ($this->getEnabled()) {
			$verbs[] = array('theses', __('plugins.generic.thesis.manager.theses'));
			$verbs[] = array('settings', __('plugins.generic.thesis.manager.settings'));
		}
		return $verbs;
	}

	function setupPublicHandler($hookName, $params) {
		$page =& $params[0];
		if ($page == 'thesis') {
			define('HANDLER_CLASS', 'ThesisHandler');
			define('THESIS_PLUGIN_NAME', $this->getName()); // Kludge
			$handlerFile =& $params[2];
			$handlerFile = $this->getPluginPath() . '/' . 'ThesisHandler.inc.php';
		}
	}

	function displayHeaderLink($hookName, $params) {
		if ($this->getEnabled()) {
			$smarty =& $params[1];
			$output =& $params[2];
			$templateMgr = TemplateManager::getManager($this->getRequest());
			$output .= '<li><a href="' . $templateMgr->smartyUrl(array('page'=>'thesis'), $smarty) . '" target="_parent">' . $templateMgr->smartyTranslate(array('key'=>'plugins.generic.thesis.headerLink'), $smarty) . '</a></li>';
		}
		return false;
	}

	function displayManagerLink($hookName, $params) {
		if ($this->getEnabled()) {
			$smarty =& $params[1];
			$output =& $params[2];
			$templateMgr = TemplateManager::getManager($this->getRequest());
			$output .= '<li>&#187; <a href="' . $this->smartyPluginUrl(array('op'=>'plugin', 'path'=>'theses'), $smarty) . '">' . $templateMgr->smartyTranslate(array('key'=>'plugins.generic.thesis.manager.theses'), $smarty) . '</a></li>';
		}
		return false;
	}

	function displaySearchLink($hookName, $params) {
		if ($this->getEnabled()) {
			$smarty =& $params[1];
			$output =& $params[2];
			$currentJournal = $smarty->get_template_vars('currentJournal');
			if (!empty($currentJournal)) {
				$templateMgr = TemplateManager::getManager($this->getRequest());
				$output .= '<a href="' . $templateMgr->smartyUrl(array('page'=>'thesis'), $smarty) . '" class="action">' . $templateMgr->smartyTranslate(array('key'=>'plugins.generic.thesis.searchLink'), $smarty) . '</a><br /><br />';
			}
		}
		return false;
	}

 	/**
	 * @see PKPPlugin::manage()
	 */
	function manage($verb, $args, &$message, &$messageParams, &$pluginModalContent = null) {
		if (!parent::manage($verb, $args, $message, $messageParams)) return false;

		AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON,  LOCALE_COMPONENT_PKP_MANAGER, LOCALE_COMPONENT_PKP_USER);
		$templateMgr =& TemplateManager::getManager($this->getRequest());
		$templateMgr->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));
		$request =& $this->getRequest();
		$journal =& $request->getJournal();

		switch ($verb) {
			case 'settings':
				$this->import('ThesisSettingsForm');
				$form = new ThesisSettingsForm($this, $journal->getId());
				if ($request->getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						$request->redirect(null, 'manager', 'plugin', array('generic', $this->getName(), 'theses'));
						return false;
					} else {
						$form->display();
					}
				} else {
					$form->initData();
					$form->display();
				}
				return true;
			case 'delete':
				if (!empty($args)) {
					$thesisId = (int) $args[0];
					$thesisDao = DAORegistry::getDAO('ThesisDAO');

					// Ensure thesis is for this journal
					if ($thesisDao->getThesisJournalId($thesisId) == $journal->getId()) {
						$thesisDao->deleteThesisById($thesisId);
					}
				}
				$request->redirect(null, 'manager', 'plugin', array('generic', $this->getName(), 'theses'));
				return true;
			case 'create':
			case 'edit':
				$thesisId = !isset($args) || empty($args) ? null : (int) $args[0];
				$thesisDao = DAORegistry::getDAO('ThesisDAO');

				// Ensure thesis is valid and for this journal
				if (($thesisId != null && $thesisDao->getThesisJournalId($thesisId) == $journal->getId()) || ($thesisId == null)) {
					$this->import('ThesisForm');

					if ($thesisId == null) {
						$templateMgr->assign('thesisTitle', 'plugins.generic.thesis.manager.createTitle');
					} else {
						$templateMgr->assign('thesisTitle', 'plugins.generic.thesis.manager.editTitle');
					}

					$journalSettingsDao = DAORegistry::getDAO('JournalSettingsDAO');
					$journalSettings =& $journalSettingsDao->getSettings($journal->getId());

					$thesisForm = new ThesisForm($this->getName(), $thesisId);
					$thesisForm->initData();
					$templateMgr->assign('journalSettings', $journalSettings);
					$thesisForm->display();
				} else {
					$request->redirect(null, 'manager', 'plugin', array('generic', $this->getName(), 'theses'));
				}
				return true;
			case 'update':
				$this->import('ThesisForm');
				$thesisId = $request->getUserVar('thesisId') == null ? null : (int) $request->getUserVar('thesisId');
				$thesisDao = DAORegistry::getDAO('ThesisDAO');

				if (($thesisId != null && $thesisDao->getThesisJournalId($thesisId) == $journal->getId()) || $thesisId == null) {

					$thesisForm = new ThesisForm($this->getName(), $thesisId);
					$thesisForm->readInputData();

					if ($thesisForm->validate()) {
						$thesisForm->execute();

						if ($request->getUserVar('createAnother')) {
							$request->redirect(null, 'manager', 'plugin', array('generic', $this->getName(), 'create'));
						} else {
							$request->redirect(null, 'manager', 'plugin', array('generic', $this->getName(), 'theses'));
						}
					} else {
						if ($thesisId == null) {
							$templateMgr->assign('thesisTitle', 'plugins.generic.thesis.manager.createTitle');
						} else {
							$templateMgr->assign('thesisTitle', 'plugins.generic.thesis.manager.editTitle');
						}

						$journalSettingsDao = DAORegistry::getDAO('JournalSettingsDAO');
						$journalSettings =& $journalSettingsDao->getSettings($journal->getId());

						$templateMgr->assign('journalSettings', $journalSettings);
						$thesisForm->display();
					}
				} else {
					$request->redirect(null, 'manager', 'plugin', array('generic', $this->getName(), 'theses'));
				}
				return true;
			default:
				$this->import('Thesis');
				$searchField = null;
				$searchMatch = null;
				$search = $request->getUserVar('search');
				$dateFrom = $request->getUserDateVar('dateFrom', 1, 1);
				if ($dateFrom !== null) $dateFrom = date('Y-m-d H:i:s', $dateFrom);
				$dateTo = $request->getUserDateVar('dateTo', 32, 12, null, 23, 59, 59);
				if ($dateTo !== null) $dateTo = date('Y-m-d H:i:s', $dateTo);

				if (!empty($search)) {
					$searchField = $request->getUserVar('searchField');
					$searchMatch = $request->getUserVar('searchMatch');
				}

				$rangeInfo =& Handler::getRangeInfo($this->getRequest(), 'theses');
				$thesisDao = DAORegistry::getDAO('ThesisDAO');
				$theses =& $thesisDao->getThesesByJournalId($journal->getId(), $searchField, $search, $searchMatch, $dateFrom, $dateTo, null, $rangeInfo);

				$templateMgr->assign('theses', $theses);

				// Set search parameters
				$duplicateParameters = array(
					'searchField', 'searchMatch', 'search',
					'dateFromMonth', 'dateFromDay', 'dateFromYear',
					'dateToMonth', 'dateToDay', 'dateToYear'
				);
				foreach ($duplicateParameters as $param)
					$templateMgr->assign($param, $request->getUserVar($param));

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
				return true;
		}
	}

}
?>
