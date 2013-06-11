<?php

/**
 * @file controllers/grid/admin/journal/form/JournalSiteSettingsForm.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JournalSiteSettingsForm
 * @ingroup controllers_grid_admin_journal_form
 *
 * @brief Form for site administrator to edit basic journal settings.
 */

import('lib.pkp.controllers.grid.admin.context.form.ContextSiteSettingsForm');

class JournalSiteSettingsForm extends ContextSiteSettingsForm {
	/**
	 * Constructor.
	 * @param $contextId omit for a new journal
	 */
	function JournalSiteSettingsForm($contextId = null) {
		parent::ContextSiteSettingsForm('admin/journalSettings.tpl', $contextId);

		// Validation checks for this form
		$this->addCheck(new FormValidatorLocale($this, 'name', 'required', 'admin.journals.form.titleRequired'));
		$this->addCheck(new FormValidator($this, 'path', 'required', 'admin.journals.form.pathRequired'));
		$this->addCheck(new FormValidatorAlphaNum($this, 'path', 'required', 'admin.journals.form.pathAlphaNumeric'));
		$this->addCheck(new FormValidatorCustom($this, 'path', 'required', 'admin.journals.form.pathExists', create_function('$path,$form,$journalDao', 'return !$journalDao->existsByPath($path) || ($form->getData(\'oldPath\') != null && $form->getData(\'oldPath\') == $path);'), array($this, DAORegistry::getDAO('JournalDAO'))));
	}

	/**
	 * Initialize form data from current settings.
	 */
	function initData() {
		if (isset($this->contextId)) {
			$journalDao = DAORegistry::getDAO('JournalDAO');
			$journal = $journalDao->getById($this->contextId);

			parent::initData($journal);
			$this->setData('journalThumbnail', $journal->getSetting('journalThumbnail'));
		} else {
			parent::initData();
		}
	}

	/**
	 * Get a list of field names for which localized settings are used
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array_merge(
			parent::getLocaleFieldNames(),
			'journalThumbnail'
		);
	}
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		parent::readInputData();

		if ($this->contextId) {
			$journalDao = DAORegistry::getDAO('JournalDAO');
			$journal = $journalDao->getById($this->contextId);
			if ($journal) $this->setData('oldPath', $journal->getPath());
		}
	}

	/**
	 * Display the form.
	 */
	function fetch($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		import('lib.pkp.classes.linkAction.LinkAction');
		$templateMgr->assign(
			'uploadThumbnailLinkAction',
			new LinkAction(
				'uploadThumbnail',
				new AjaxModal(
					$router->url(
						$request, null, null, 'showThumbnailUploadForm'),
					__('common.upload'),
					'modal_add_file'
				),
				__('common.upload'),
				'add'
			)
		);
		$templateMgr->assign('thumbnailImage', $this->renderFileView($request));
		return parent::fetch($args, $request);
	}

	/**
	 * Save journal settings.
	 * @param $request PKPRequest
	 */
	function execute($request) {
		$journalDao = DAORegistry::getDAO('JournalDAO');

		if (isset($this->contextId)) {
			$journal = $journalDao->getById($this->contextId);
		}

		if (!isset($journal)) {
			$journal = $journalDao->newDataObject();
		}

		$journal->setPath($this->getData('path'));
		$journal->setEnabled($this->getData('enabled'));

		if ($journal->getId() != null) {
			$isNewJournal = false;
			$journalDao->updateObject($journal);
			$section = null;
		} else {
			$isNewJournal = true;
			$site = $request->getSite();

			// Give it a default primary locale
			$journal->setPrimaryLocale ($site->getPrimaryLocale());

			$journalId = $journalDao->insertObject($journal);
			$journalDao->resequence();

			$installedLocales =& $site->getInstalledLocales();

			// Install default genres
			$genreDao = DAORegistry::getDAO('GenreDAO');
			$genreDao->installDefaults($journalId, $installedLocales); /* @var $genreDao GenreDAO */

			// load the default user groups and stage assignments.
			$this->_loadDefaultUserGroups($journalId);

			// Put this user in the Manager group.
			$this->_assignManagerGroup($journalId);

			// Make the file directories for the journal
			import('lib.pkp.classes.file.FileManager');
			$fileManager = new FileManager();
			$fileManager->mkdir(Config::getVar('files', 'files_dir') . '/journals/' . $journalId);
			$fileManager->mkdir(Config::getVar('files', 'files_dir'). '/journals/' . $journalId . '/articles');
			$fileManager->mkdir(Config::getVar('files', 'files_dir'). '/journals/' . $journalId . '/issues');
			$fileManager->mkdir(Config::getVar('files', 'public_files_dir') . '/journals/' . $journalId);

			// Install default journal settings
			$journalSettingsDao = DAORegistry::getDAO('JournalSettingsDAO');
			$names = $this->getData('name');
			AppLocale::requireComponents(LOCALE_COMPONENT_APP_DEFAULT, LOCALE_COMPONENT_APP_COMMON);
			$journalSettingsDao->installSettings($journalId, 'registry/journalSettings.xml', array(
				'indexUrl' => $request->getIndexUrl(),
				'journalPath' => $this->getData('path'),
				'primaryLocale' => $site->getPrimaryLocale(),
				'journalName' => $names[$site->getPrimaryLocale()]
			));

			// Install the default RT versions.
			import('classes.rt.ojs.JournalRTAdmin');
			$journalRtAdmin = new JournalRTAdmin($journalId);
			$journalRtAdmin->restoreVersions(false);

			// Create a default "Articles" section
			$sectionDao = DAORegistry::getDAO('SectionDAO');
			$section = new Section();
			$section->setJournalId($journal->getId());
			$section->setTitle(__('section.default.title'), $journal->getPrimaryLocale());
			$section->setAbbrev(__('section.default.abbrev'), $journal->getPrimaryLocale());
			$section->setMetaIndexed(true);
			$section->setMetaReviewed(true);
			$section->setPolicy(__('section.default.policy'), $journal->getPrimaryLocale());
			$section->setEditorRestricted(false);
			$section->setHideTitle(false);
			$sectionDao->insertObject($section);
		}
		$journal->updateSetting('name', $this->getData('name'), 'string', true);
		$journal->updateSetting('description', $this->getData('description'), 'string', true);

		// Make sure all plugins are loaded for settings preload
		PluginRegistry::loadAllPlugins();

		HookRegistry::call('JournalSiteSettingsForm::execute', array($this, $journal, $section, $isNewJournal));
	}

	/**
	 * Render a template to show details about an uploaded file in the form
	 * and a link action to delete it.
	 * @param $request Request
	 * @return string
	 */
	function renderFileView($request) {
		$file = $this->getData('journalThumbnail');
		$locale = AppLocale::getLocale();

		// Check if the file is localized.
		if (!is_null($file) && key_exists($locale, $file)) {
			// We use the current localized file value.
			$file = $file[$locale];
		}

		// Only render the file view if we have a file.
		if (is_array($file)) {
			$templateMgr = TemplateManager::getManager($request);

			// Get the common alternate text for the image.
			$templateMgr->assign('commonAltText', __('admin.journals.thumbnail'));

			$templateMgr->assign('file', $file);
			$templateMgr->assign('deleteLinkAction', $this->_getDeleteFileLinkAction($request));

			return $templateMgr->fetch('controllers/tab/settings/formImageView.tpl');
		} else {
			return null;
		}
	}

	/**
	 * Delete an uploaded file.
	 * @return boolean
	 */
	function deleteFile($request) {
		$context = $request->getContext();
		$locale = AppLocale::getLocale();

		// Get the file.
		$file = $this->getData('journalThumbnail');

		// Check if the file is localized.
		if (key_exists($locale, $file)) {
			// We use the current localized file value.
			$file = $file[$locale];
		} else {
			$locale = null;
		}

		// Deletes the file and its settings.
		import('classes.file.PublicFileManager');
		$publicFileManager = new PublicFileManager();
		if ($publicFileManager->removeContextFile($context->getAssocType(), $context->getId(), $file['uploadName'])) {
			$settingsDao = $context->getSettingsDao();
			$settingsDao->deleteSetting($context->getId(), 'journalThumbnail', $locale);
			return true;
		} else {
			return false;
		}
	}


	//
	// Protected functions
	//
	/**
	 * Get the delete file link action.
	 * @param $request Request
	 * @return LinkAction
	 */
	function _getDeleteFileLinkAction($request) {
		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');

		return new LinkAction(
			'deleteFile-journalThumbnail',
			new RemoteActionConfirmationModal(
				__('common.confirmDelete'), null,
				$router->url(
					$request, null, null, 'deleteFile'
				),
				'modal_delete'
			),
			__('common.delete'),
			null
		);
	}

}

?>
