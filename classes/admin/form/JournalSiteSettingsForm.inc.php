<?php

/**
 * @file classes/manager/form/JournalSiteSettingsForm.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JournalSiteSettingsForm
 * @ingroup admin_form
 *
 * @brief Form for site administrator to edit basic journal settings.
 */

import('lib.pkp.classes.db.DBDataXMLParser');
import('lib.pkp.classes.form.Form');

class JournalSiteSettingsForm extends Form {

	/** The ID of the journal being edited */
	var $journalId;

	/**
	 * Constructor.
	 * @param $journalId omit for a new journal
	 */
	function JournalSiteSettingsForm($journalId = null) {
		parent::Form('admin/journalSettings.tpl');

		$this->journalId = isset($journalId) ? (int) $journalId : null;

		// Validation checks for this form
		$this->addCheck(new FormValidatorLocale($this, 'title', 'required', 'admin.journals.form.titleRequired'));
		$this->addCheck(new FormValidator($this, 'journalPath', 'required', 'admin.journals.form.pathRequired'));
		$this->addCheck(new FormValidatorAlphaNum($this, 'journalPath', 'required', 'admin.journals.form.pathAlphaNumeric'));
		$this->addCheck(new FormValidatorCustom($this, 'journalPath', 'required', 'admin.journals.form.pathExists', create_function('$path,$form,$journalDao', 'return !$journalDao->journalExistsByPath($path) || ($form->getData(\'oldPath\') != null && $form->getData(\'oldPath\') == $path);'), array(&$this, DAORegistry::getDAO('JournalDAO'))));
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('journalId', $this->journalId);
		$templateMgr->assign('helpTopicId', 'site.siteManagement');
		parent::display();
	}

	/**
	 * Initialize form data from current settings.
	 */
	function initData() {
		if (isset($this->journalId)) {
			$journalDao =& DAORegistry::getDAO('JournalDAO');
			$journal =& $journalDao->getById($this->journalId);

			if ($journal != null) {
				$this->_data = array(
					'title' => $journal->getSetting('title', null), // Localized
					'description' => $journal->getSetting('description', null), // Localized
					'journalPath' => $journal->getPath(),
					'enabled' => $journal->getEnabled()
				);

			} else {
				$this->journalId = null;
			}
		}
		if (!isset($this->journalId)) {
			$this->_data = array(
				'enabled' => 1
			);
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('title', 'description', 'journalPath', 'enabled'));
		$this->setData('enabled', (int)$this->getData('enabled'));

		if (isset($this->journalId)) {
			$journalDao =& DAORegistry::getDAO('JournalDAO');
			$journal =& $journalDao->getById($this->journalId);
			$this->setData('oldPath', $journal->getPath());
		}
	}

	/**
	 * Get a list of field names for which localized settings are used
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('title', 'description');
	}

	/**
	 * Save journal settings.
	 */
	function execute() {
		$site =& Request::getSite();
		$journalDao =& DAORegistry::getDAO('JournalDAO');

		if (isset($this->journalId)) {
			$journal =& $journalDao->getById($this->journalId);
		}

		if (!isset($journal)) {
			$journal = new Journal();
		}

		$journal->setPath($this->getData('journalPath'));
		$journal->setEnabled($this->getData('enabled'));

		if ($journal->getId() != null) {
			$isNewJournal = false;
			$journalDao->updateJournal($journal);
			$section = null;
		} else {
			$isNewJournal = true;

			// Give it a default primary locale
			$journal->setPrimaryLocale ($site->getPrimaryLocale());

			$journalId = $journalDao->insertJournal($journal);
			$journalDao->resequenceJournals();

			// Make the site administrator the journal manager of newly created journals
			$sessionManager =& SessionManager::getManager();
			$userSession =& $sessionManager->getUserSession();
			if ($userSession->getUserId() != null && $userSession->getUserId() != 0 && !empty($journalId)) {
				$role = new Role();
				$role->setJournalId($journalId);
				$role->setUserId($userSession->getUserId());
				$role->setRoleId(ROLE_ID_JOURNAL_MANAGER);

				$roleDao =& DAORegistry::getDAO('RoleDAO');
				$roleDao->insertRole($role);
			}

			// Make the file directories for the journal
			import('lib.pkp.classes.file.FileManager');
			$fileManager = new FileManager();
			$fileManager->mkdir(Config::getVar('files', 'files_dir') . '/journals/' . $journalId);
			$fileManager->mkdir(Config::getVar('files', 'files_dir'). '/journals/' . $journalId . '/articles');
			$fileManager->mkdir(Config::getVar('files', 'files_dir'). '/journals/' . $journalId . '/issues');
			$fileManager->mkdir(Config::getVar('files', 'public_files_dir') . '/journals/' . $journalId);

			// Install default journal settings
			$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
			$titles = $this->getData('title');
			AppLocale::requireComponents(LOCALE_COMPONENT_OJS_DEFAULT, LOCALE_COMPONENT_APPLICATION_COMMON);
			$journalSettingsDao->installSettings($journalId, 'registry/journalSettings.xml', array(
				'indexUrl' => Request::getIndexUrl(),
				'journalPath' => $this->getData('journalPath'),
				'primaryLocale' => $site->getPrimaryLocale(),
				'journalName' => $titles[$site->getPrimaryLocale()]
			));

			// Install the default RT versions.
			import('classes.rt.ojs.JournalRTAdmin');
			$journalRtAdmin = new JournalRTAdmin($journalId);
			$journalRtAdmin->restoreVersions(false);

			// Create a default "Articles" section
			$sectionDao =& DAORegistry::getDAO('SectionDAO');
			$section = new Section();
			$section->setJournalId($journal->getId());
			$section->setTitle(__('section.default.title'), $journal->getPrimaryLocale());
			$section->setAbbrev(__('section.default.abbrev'), $journal->getPrimaryLocale());
			$section->setMetaIndexed(true);
			$section->setMetaReviewed(true);
			$section->setPolicy(__('section.default.policy'), $journal->getPrimaryLocale());
			$section->setEditorRestricted(false);
			$section->setHideTitle(false);
			$sectionDao->insertSection($section);
		}
		$journal->updateSetting('supportedLocales', $site->getSupportedLocales());
		$journal->updateSetting('title', $this->getData('title'), 'string', true);
		$journal->updateSetting('description', $this->getData('description'), 'string', true);

		// Make sure all plugins are loaded for settings preload
		PluginRegistry::loadAllPlugins();

		HookRegistry::call('JournalSiteSettingsForm::execute', array(&$this, &$journal, &$section, &$isNewJournal));
	}

}

?>
