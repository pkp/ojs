<?php

/**
 * @file pages/about/AboutSiteHandler.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AboutSiteHandler
 * @ingroup pages_about
 *
 * @brief Handle requests for site-wide about functions.
 */

import('classes.handler.Handler');

class AboutSiteHandler extends Handler {
	/**
	 * Constructor
	 */
	function AboutSiteHandler() {
		parent::Handler();
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON);
	}

	/**
	 * Display aboutThisPublishingSystem page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function aboutThisPublishingSystem($args, $request) {
		$versionDao = DAORegistry::getDAO('VersionDAO');
		$version = $versionDao->getCurrentVersion();

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('appVersion', $version->getVersionString(false));

		foreach (array(AppLocale::getLocale(), $primaryLocale = AppLocale::getPrimaryLocale(), 'en_US') as $locale) {
			$pubProcessFile = 'locale/'.$locale.'/pubprocesslarge.png';
			if (file_exists($pubProcessFile)) break;
		}
		$templateMgr->assign('pubProcessFile', $pubProcessFile);

		$templateMgr->display('about/aboutThisPublishingSystem.tpl');
	}
}

?>
