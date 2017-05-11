<?php
/**
 * @defgroup admin_form Site administration form
 */

/**
 * @file classes/admin/form/PKPSiteSettingsForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SiteSettingsForm
 * @ingroup admin_form
 *
 * @brief Form to edit site settings.
 */


define('SITE_MIN_PASSWORD_LENGTH', 4);
import('lib.pkp.classes.form.Form');

class PKPSiteSettingsForm extends Form {
	/** @var object Site settings DAO */
	var $siteSettingsDao;

	/**
	 * Constructor.
	 * @param $template string? Optional name of template file to use for form presentation
	 */
	function __construct($template = null) {
		parent::__construct($template?$template:'admin/settings.tpl');
		$this->siteSettingsDao = DAORegistry::getDAO('SiteSettingsDAO');

		// Validation checks for this form
		$this->addCheck(new FormValidatorLocale($this, 'title', 'required', 'admin.settings.form.titleRequired'));
		$this->addCheck(new FormValidatorLocale($this, 'contactName', 'required', 'admin.settings.form.contactNameRequired'));
		$this->addCheck(new FormValidatorLocaleEmail($this, 'contactEmail', 'required', 'admin.settings.form.contactEmailRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'minPasswordLength', 'required', 'admin.settings.form.minPasswordLengthRequired', create_function('$l', sprintf('return $l >= %d;', SITE_MIN_PASSWORD_LENGTH))));
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	/**
	 * Display the form.
	 */
	function display() {
		$site = Request::getSite();
		$publicFileManager = new PublicFileManager();
		$siteStyleFilename = $publicFileManager->getSiteFilesPath() . '/' . $site->getSiteStyleFilename();
		$templateMgr = TemplateManager::getManager();
		$templateMgr->assign(array(
			'showThumbnail' => $site->getSetting('showThumbnail'),
			'showTitle' => $site->getSetting('showTitle'),
			'showDescription' => $site->getSetting('showDescription'),
			'originalStyleFilename' => $site->getOriginalStyleFilename(),
			'pageHeaderTitleImage' => $site->getSetting('pageHeaderTitleImage'),
			'styleFilename' => $site->getSiteStyleFilename(),
			'publicFilesDir' => Request::getBasePath() . '/' . $publicFileManager->getSiteFilesPath(),
			'dateStyleFileUploaded' => file_exists($siteStyleFilename)?filemtime($siteStyleFilename):null,
			'siteStyleFileExists' => file_exists($siteStyleFilename),
		));
		return parent::display();
	}

	/**
	 * Initialize form data from current settings.
	 */
	function initData() {
		$siteDao = DAORegistry::getDAO('SiteDAO');
		$site = $siteDao->getSite();

		$data = array(
			'title' => $site->getSetting('title'), // Localized
			'intro' => $site->getSetting('intro'), // Localized
			'redirect' => $site->getRedirect(),
			'showThumbnail' => $site->getSetting('showThumbnail'),
			'showTitle' => $site->getSetting('showTitle'),
			'showDescription' => $site->getSetting('showDescription'),
			'about' => $site->getSetting('about'), // Localized
			'pageFooter' => $site->getSetting('pageFooter'), // Localized
			'contactName' => $site->getSetting('contactName'), // Localized
			'contactEmail' => $site->getSetting('contactEmail'), // Localized
			'minPasswordLength' => $site->getMinPasswordLength(),
			'pageHeaderTitleType' => $site->getSetting('pageHeaderTitleType'), // Localized
			'themePluginPath' => $site->getSetting('themePluginPath')
		);

		foreach ($data as $key => $value) {
			$this->setData($key, $value);
		}
	}

	function getLocaleFieldNames() {
		return array('title', 'pageHeaderTitleType', 'intro', 'about', 'contactName', 'contactEmail', 'pageFooter');
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(
			array('pageHeaderTitleType', 'title', 'intro', 'about', 'redirect', 'contactName', 'contactEmail', 'minPasswordLength', 'pageHeaderTitleImageAltText', 'showThumbnail', 'showTitle', 'showDescription', 'themePluginPath', 'pageFooter')
		);
	}

	/**
	 * Save site settings.
	 */
	function execute($request) {
		parent::execute();
		$siteDao = DAORegistry::getDAO('SiteDAO');
		$site = $siteDao->getSite();

		$site->setRedirect($this->getData('redirect'));
		$site->setMinPasswordLength($this->getData('minPasswordLength'));

		// Clear the template cache if theme has changed
		if ($this->getData('themePluginPath') != $site->getSetting('themePluginPath')) {
			$templateMgr = TemplateManager::getManager($request);
			$templateMgr->clearTemplateCache();
			$templateMgr->clearCssCache();
		}

		$siteSettingsDao = $this->siteSettingsDao;
		foreach ($this->getLocaleFieldNames() as $setting) {
			$siteSettingsDao->updateSetting($setting, $this->getData($setting), null, true);
		}

		$setting = $site->getSetting('pageHeaderTitleImage');
		if (!empty($setting)) {
			$imageAltText = $this->getData('pageHeaderTitleImageAltText');
			$locale = $this->getFormLocale();
			$setting[$locale]['altText'] = $imageAltText[$locale];
			$site->updateSetting('pageHeaderTitleImage', $setting, 'object', true);
		}

		$site->updateSetting('showThumbnail', $this->getData('showThumbnail'), 'bool');
		$site->updateSetting('showTitle', $this->getData('showTitle'), 'bool');
		$site->updateSetting('showDescription', $this->getData('showDescription'), 'bool');

		$siteDao->updateObject($site);

		return true;
	}

	/**
	 * Uploads custom site stylesheet.
	 */
	function uploadSiteStyleSheet() {
		import('classes.file.PublicFileManager');
		$publicFileManager = new PublicFileManager();
		$site = Request::getSite();
		if ($publicFileManager->uploadedFileExists('siteStyleSheet')) {
			$type = $publicFileManager->getUploadedFileType('siteStyleSheet');
			if ($type != 'text/plain' && $type != 'text/css') {
				return false;
			}

			$uploadName = $site->getSiteStyleFilename();
			if ($publicFileManager->uploadSiteFile('siteStyleSheet', $uploadName)) {
				$siteDao = DAORegistry::getDAO('SiteDAO');
				$site->setOriginalStyleFilename($publicFileManager->getUploadedFileName('siteStyleSheet'));
				$siteDao->updateObject($site);
			}
		}

		return true;
	}

	/**
	 * Uploads custom site logo.
	 */
	function uploadPageHeaderTitleImage($locale) {
		import('classes.file.PublicFileManager');
		$publicFileManager = new PublicFileManager();
		$site = Request::getSite();
		if ($publicFileManager->uploadedFileExists('pageHeaderTitleImage')) {
			$type = $publicFileManager->getUploadedFileType('pageHeaderTitleImage');
			$extension = $publicFileManager->getImageExtension($type);
			if (!$extension) return false;

			$uploadName = 'pageHeaderTitleImage_' . $locale . $extension;
			if ($publicFileManager->uploadSiteFile('pageHeaderTitleImage', $uploadName)) {
				$siteDao = DAORegistry::getDAO('SiteDAO');
				$setting = $site->getSetting('pageHeaderTitleImage');
				list($width, $height) = getimagesize($publicFileManager->getSiteFilesPath() . '/' . $uploadName);
				$setting[$locale] = array(
					'originalFilename' => $publicFileManager->getUploadedFileName('pageHeaderTitleImage'),
					'width' => $width,
					'height' => $height,
					'uploadName' => $uploadName,
					'dateUploaded' => Core::getCurrentDate()
				);
				$site->updateSetting('pageHeaderTitleImage', $setting, 'object', true);
			}
		}

		return true;
	}
}

?>
