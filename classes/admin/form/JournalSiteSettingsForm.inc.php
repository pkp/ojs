<?php

/**
 * @file classes/manager/form/JournalSiteSettingsForm.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JournalSiteSettingsForm
 * @ingroup admin_form
 *
 * @brief Form for site administrator to edit basic journal settings.
 */

// $Id$


import('db.DBDataXMLParser');
import('form.Form');

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
		$this->addCheck(new FormValidator($this, 'path', 'required', 'admin.journals.form.pathRequired'));
		$this->addCheck(new FormValidatorAlphaNum($this, 'path', 'required', 'admin.journals.form.pathAlphaNumeric'));
		$this->addCheck(new FormValidatorCustom($this, 'path', 'required', 'admin.journals.form.pathExists', create_function('$path,$form,$journalDao', 'return !$journalDao->journalExistsByPath($path) || ($form->getData(\'oldPath\') != null && $form->getData(\'oldPath\') == $path);'), array(&$this, DAORegistry::getDAO('JournalDAO'))));
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('journalId', $this->journalId);
		$templateMgr->assign('helpTopicId', 'site.siteManagement');
		parent::display();
	}

	/**
	 * Initialize form data from current settings.
	 */
	function initData() {
		if (isset($this->journalId)) {
			$journalDao = &DAORegistry::getDAO('JournalDAO');
			$journal = &$journalDao->getJournal($this->journalId);

			if ($journal != null) {
				$this->_data = array(
					'title' => $journal->getSetting('title', null), // Localized
					'description' => $journal->getSetting('description', null), // Localized
					'path' => $journal->getPath(),
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
		$this->readUserVars(array('title', 'description', 'path', 'enabled'));
		$this->setData('enabled', (int)$this->getData('enabled'));

		if (isset($this->journalId)) {
			$journalDao = &DAORegistry::getDAO('JournalDAO');
			$journal = &$journalDao->getJournal($this->journalId);
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
		$journalDao = &DAORegistry::getDAO('JournalDAO');

		if (isset($this->journalId)) {
			$journal = &$journalDao->getJournal($this->journalId);
		}

		if (!isset($journal)) {
			$journal = &new Journal();
		}

		$journal->setPath($this->getData('path'));
		$journal->setEnabled($this->getData('enabled'));

		if ($journal->getJournalId() != null) {
			$isNewJournal = false;
			$journalDao->updateJournal($journal);
			$section = null;
		} else {
			$isNewJournal = true;
			$site =& Request::getSite();

			// Give it a default primary locale
			$journal->setPrimaryLocale ($site->getPrimaryLocale());

			$journalId = $journalDao->insertJournal($journal);
			$journalDao->resequenceJournals();

			// Make the site administrator the journal manager of newly created journals
			$sessionManager = &SessionManager::getManager();
			$userSession = &$sessionManager->getUserSession();
			if ($userSession->getUserId() != null && $userSession->getUserId() != 0 && !empty($journalId)) {
				$role = &new Role();
				$role->setJournalId($journalId);
				$role->setUserId($userSession->getUserId());
				$role->setRoleId(ROLE_ID_JOURNAL_MANAGER);

				$roleDao = &DAORegistry::getDAO('RoleDAO');
				$roleDao->insertRole($role);
			}

			// Make the file directories for the journal
			import('file.FileManager');
			FileManager::mkdir(Config::getVar('files', 'files_dir') . '/journals/' . $journalId);
			FileManager::mkdir(Config::getVar('files', 'files_dir'). '/journals/' . $journalId . '/articles');
			FileManager::mkdir(Config::getVar('files', 'files_dir'). '/journals/' . $journalId . '/issues');
			FileManager::mkdir(Config::getVar('files', 'public_files_dir') . '/journals/' . $journalId);

			// Install default journal settings
			$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
			$titles = $this->getData('title');
			$journalSettingsDao->installSettings($journalId, 'registry/journalSettings.xml', array(
				'indexUrl' => Request::getIndexUrl(),
				'journalPath' => $this->getData('path'),
				'primaryLocale' => $site->getPrimaryLocale(),
				'journalName' => $titles[$site->getPrimaryLocale()]
			));

			// Install the default RT versions.
			import('rt.ojs.JournalRTAdmin');
			$journalRtAdmin = &new JournalRTAdmin($journalId);
			$journalRtAdmin->restoreVersions(false);

			// Create a default "Articles" section
			$sectionDao = &DAORegistry::getDAO('SectionDAO');
			$section = &new Section();
			$section->setJournalId($journal->getJournalId());
			$section->setTitle(Locale::translate('section.default.title'), $journal->getPrimaryLocale());
			$section->setAbbrev(Locale::translate('section.default.abbrev'), $journal->getPrimaryLocale());
			$section->setMetaIndexed(true);
			$section->setMetaReviewed(true);
			$section->setPolicy(Locale::translate('section.default.policy'), $journal->getPrimaryLocale());
			$section->setEditorRestricted(false);
			$section->setHideTitle(false);
			$sectionDao->insertSection($section);

		}
		$journal->updateSetting('title', $this->getData('title'), 'string', true);
		$journal->updateSetting('description', $this->getData('description'), 'string', true);
		HookRegistry::call('JournalSiteSettingsForm::execute', array(&$this, &$journal, &$section, &$isNewJournal));
	}

}

?>
