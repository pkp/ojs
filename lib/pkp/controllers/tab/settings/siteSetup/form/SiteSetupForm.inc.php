<?php

/**
 * @file controllers/tab/settings/siteSetup/form/SiteSetupForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SiteSetupForm
 * @ingroup admin_form
 * @see PKPSiteSettingsForm
 *
 * @brief Form to edit site settings.
 */


import('lib.pkp.classes.admin.form.PKPSiteSettingsForm');

class SiteSetupForm extends PKPSiteSettingsForm {
	/**
	 * Constructor.
	 * @param $template string? Optional name of template file to use for form presentation
	 */
	function __construct($template = null) {
		parent::__construct($template?$template:'controllers/tab/settings/siteSetup/form/siteSetupForm.tpl');

		$themes = PluginRegistry::getPlugins('themes');
		if (is_null($themes)) {
			PluginRegistry::loadCategory('themes', true);
		}

		AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON);
	}

	//
	// Extended methods from Form.
	//
	/**
	 * @see Form::fetch()
	 * @param $request PKPRequest
	 * @param $params array
	 */
	function fetch($request, $params = null) {
		$site = $request->getSite();
		$publicFileManager = new PublicFileManager();
		$contextDao = Application::getContextDAO();
		$contexts = $contextDao->getNames();
		$siteStyleFilename = $publicFileManager->getSiteFilesPath() . '/' . $site->getSiteStyleFilename();

		$cssSettingName = 'siteStyleSheet';
		$imageSettingName = 'pageHeaderTitleImage';

		// Get link actions.
		$uploadCssLinkAction = $this->_getFileUploadLinkAction($cssSettingName, 'css', $request);
		$uploadImageLinkAction = $this->_getFileUploadLinkAction($imageSettingName, 'image', $request);

		// Get the files view.
		$cssView = $this->renderFileView($cssSettingName, $request);
		$imageView = $this->renderFileView($imageSettingName, $request);

		$application = Application::getApplication();
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'locale' => AppLocale::getLocale(),
			'siteStyleFileExists' => file_exists($siteStyleFilename),
			'uploadCssLinkAction' => $uploadCssLinkAction,
			'uploadImageLinkAction' => $uploadImageLinkAction,
			'cssView' => $cssView,
			'imageView' => $imageView,
			'redirectOptions' => $contexts,
			'pageHeaderTitleImage' => $site->getSetting($imageSettingName),
			'availableMetricTypes' => $application->getMetricTypes(true),
		));

		$themePlugins = PluginRegistry::getPlugins('themes');
		$enabledThemes = array();
		$activeThemeOptions = array();
		foreach ($themePlugins as $themePlugin) {
			$enabledThemes[basename($themePlugin->getPluginPath())] = $themePlugin->getDisplayName();
			if ($themePlugin->isActive()) {
				$activeThemeOptions = $themePlugin->getOptionsConfig();
				$activeThemeOptionsValues = $themePlugin->getOptionValues();
				foreach ($activeThemeOptions as $name => $option) {
					$activeThemeOptions[$name]['value'] = isset($activeThemeOptionsValues[$name]) ? $activeThemeOptionsValues[$name] : '';
				}
			}
		}
		$templateMgr->assign(array(
			'enabledThemes' => $enabledThemes,
			'activeThemeOptions' => $activeThemeOptions,
		));

		return parent::fetch($request);
	}


	//
	// Extend method from PKPSiteSettingsForm
	//
	/**
	 * @see PKPSiteSettingsForm::initData()
	 * @param $request PKPRequest
	 */
	function initData($request) {
		$site = $request->getSite();
		$publicFileManager = $publicFileManager = new PublicFileManager();
		$siteStyleFilename = $publicFileManager->getSiteFilesPath() . '/' . $site->getSiteStyleFilename();

		// Get the files settings that can be uploaded within this form.

		// FIXME Change the way we get the style sheet setting when
		// it's implemented in site settings table, like pageHeaderTitleImage.
		$siteStyleSheet = null;
		if (file_exists($siteStyleFilename)) {
			$siteStyleSheet = array(
				'name' => $site->getOriginalStyleFilename(),
				'uploadName' => $site->getSiteStyleFilename(),
				'dateUploaded' => filemtime($siteStyleFilename),
			);
		}

		$pageHeaderTitleImage = $site->getSetting('pageHeaderTitleImage');

		$this->setData('siteStyleSheet', $siteStyleSheet);
		$this->setData('pageHeaderTitleImage', $pageHeaderTitleImage);
		$this->setData('themePluginPath', $site->getSetting('themePluginPath'));
		$this->setData('defaultMetricType', $site->getSetting('defaultMetricType'));

		parent::initData();
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(
			array('pageHeaderTitleType', 'title', 'about', 'redirect', 'contactName',
				'contactEmail', 'minPasswordLength', 'themePluginPath', 'defaultMetricType','pageFooter',)
		);
	}

	/**
	 * Save site settings.
	 */
	function execute($request) {
		parent::execute($request);
		$siteDao = DAORegistry::getDAO('SiteDAO');
		$site = $siteDao->getSite();

		$site->setRedirect($this->getData('redirect'));
		$site->setMinPasswordLength($this->getData('minPasswordLength'));

		$siteSettingsDao = $this->siteSettingsDao;
		foreach ($this->getLocaleFieldNames() as $setting) {
			$siteSettingsDao->updateSetting($setting, $this->getData($setting), null, true);
		}

		$siteSettingsDao->updateSetting('defaultMetricType', $this->getData('defaultMetricType'));

		// Activate the selected theme plugin
		$selectedThemePluginPath = $this->getData('themePluginPath');
		$site->updateSetting('themePluginPath', $selectedThemePluginPath);

		$siteDao->updateObject($site);

		// Save block plugins context positions.
		import('lib.pkp.classes.controllers.listbuilder.ListbuilderHandler');
		ListbuilderHandler::unpack($request, $request->getUserVar('blocks'), array($this, 'deleteEntry'), array($this, 'insertEntry'), array($this, 'updateEntry'));

		return true;
	}

	//
	// Public methods.
	//
	/**
	 * Render a template to show details about an uploaded file in the form
	 * and a link action to delete it.
	 * @param $fileSettingName string The uploaded file setting name.
	 * @param $request Request
	 * @return string
	 */
	function renderFileView($fileSettingName, $request) {
		$file = $this->getData($fileSettingName);
		$locale = AppLocale::getLocale();

		// Check if the file is localized.
		if (!is_null($file) && key_exists($locale, $file)) {
			// We use the current localized file value.
			$file = $file[$locale];
		}

		// Only render the file view if we have a file.
		if (is_array($file)) {
			$templateMgr = TemplateManager::getManager($request);
			$deleteLinkAction = $this->_getDeleteFileLinkAction($fileSettingName, $request);

			// Get the right template to render the view.
			if ($fileSettingName == 'pageHeaderTitleImage') {
				$template = 'controllers/tab/settings/formImageView.tpl';

				// Get the common alternate text for the image.
				$localeKey = 'admin.settings.homeHeaderImage.altText';
				$commonAltText = __($localeKey);
				$templateMgr->assign('commonAltText', $commonAltText);
			} else {
				$template = 'controllers/tab/settings/formFileView.tpl';
			}

			$publicFileManager = $publicFileManager = new PublicFileManager();
			$templateMgr->assign(array(
				'publicFilesDir' => $request->getBasePath() . '/' . $publicFileManager->getSiteFilesPath(),
				'file' => $file,
				'deleteLinkAction' => $deleteLinkAction,
				'fileSettingName' => $fileSettingName,
			));
			return $templateMgr->fetch($template);
		} else {
			return null;
		}
	}

	/**
	 * Delete an uploaded file.
	 * @param $fileSettingName string
	 * @return boolean
	 */
	function deleteFile($fileSettingName, $request) {
		$locale = AppLocale::getLocale();

		// Get the file.
		$file = $this->getData($fileSettingName);

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
		if ($publicFileManager->removeSiteFile($file['uploadName'])) {
			$settingsDao = DAORegistry::getDAO('SiteSettingsDAO');
			$settingsDao->deleteSetting($fileSettingName, $locale);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Overriden method from ListbuilderHandler.
	 * @param $request Request
	 * @param $rowId mixed
	 * @param $newRowId array
	 */
	function updateEntry($request, $rowId, $newRowId) {
		$plugins =& PluginRegistry::loadCategory('blocks');
		$plugin =& $plugins[$rowId]; // Ref hack
		switch ($newRowId['listId']) {
			case 'unselected':
				$plugin->setEnabled(false, 0);
				break;
			case 'sidebarContext':
				$plugin->setEnabled(true, 0);
				$plugin->setBlockContext(BLOCK_CONTEXT_SIDEBAR, 0);
				$plugin->setSeq((int) $newRowId['sequence'], 0);
				break;
			default:
				assert(false);
		}
	}

	/**
	 * Avoid warnings when Listbuilder::unpack tries to call this method.
	 */
	function deleteEntry() {
		return false;
	}

	/**
	 * Avoid warnings when Listbuilder::unpack tries to call this method.
	 */
	function insertEntry() {
		return false;
	}


	//
	// Private helper methods.
	//
	/**
	 * Get a link action for file upload.
	 * @param $settingName string
	 * @param $fileType string The uploaded file type.
	 * @param $request Request
	 * @return LinkAction
	 */
	function _getFileUploadLinkAction($settingName, $fileType, $request) {
		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.AjaxModal');

		$ajaxModal = new AjaxModal(
			$router->url(
				$request, null, null, 'showFileUploadForm', null, array(
					'fileSettingName' => $settingName,
					'fileType' => $fileType
				)
			)
		);
		import('lib.pkp.classes.linkAction.LinkAction');
		$linkAction = new LinkAction(
			'uploadFile-' . $settingName,
			$ajaxModal,
			__('common.upload'),
			null
		);

		return $linkAction;
	}

	/**
	 * Get the delete file link action.
	 * @param $setttingName string File setting name.
	 * @param $request Request
	 * @return LinkAction
	 */
	function _getDeleteFileLinkAction($settingName, $request) {
		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');

		$confirmationModal = new RemoteActionConfirmationModal(
			$request->getSession(),
			__('common.confirmDelete'), null,
			$router->url(
				$request, null, null, 'deleteFile', null, array(
					'fileSettingName' => $settingName,
					'tab' => 'siteSetup'
				)
			)
		);
		$linkAction = new LinkAction(
			'deleteFile-' . $settingName,
			$confirmationModal,
			__('common.delete'),
			null
		);

		return $linkAction;
	}

	/**
	 * Handle any additional form validation checks.
	 * (See SettingsTabHandler)
	 * @return boolean
	 */
	function addValidationChecks() {
		return true;
	}
}

?>
