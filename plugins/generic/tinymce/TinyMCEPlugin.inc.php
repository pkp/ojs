<?php

/**
 * @file plugins/generic/tinymce/TinyMCEPlugin.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TinyMCEPlugin
 * @ingroup plugins_generic_tinymce
 *
 * @brief TinyMCE WYSIWYG plugin for textareas - to allow cross-browser HTML editing
 */


import('lib.pkp.classes.plugins.GenericPlugin');

// Define TinyMCE paths with unix-style separators for inclusion in browser.
define('TINYMCE_INSTALL_PATH', 'lib/pkp/lib/tinymce');
define('TINYMCE_JS_PATH', TINYMCE_INSTALL_PATH . '/jscripts/tiny_mce');

class TinyMCEPlugin extends GenericPlugin {
	/**
	 * Register the plugin, if enabled; note that this plugin
	 * runs under both Journal and Site contexts.
	 * @param $category string
	 * @param $path string
	 * @return boolean
	 */
	function register($category, $path) {
		if (parent::register($category, $path)) {
			if ($this->isMCEInstalled() && $this->getEnabled()) {
				HookRegistry::register('TemplateManager::display',array($this, 'callback'));
			}
			return true;
		}
		return false;
	}

	/**
	 * Get the name of the settings file to be installed on new journal
	 * creation.
	 * @return string
	 */
	function getContextSpecificPluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
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
	 * Hook callback function for TemplateManager::display
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 */
	function callback($hookName, $args) {
		// Only pages requests interest us here
		$request =& $this->getRequest();
		if (!is_a($request->getRouter(), 'PKPPageRouter')) return null;

		$templateManager =& $args[0];

		$page = $request->getRequestedPage();
		$op = $request->getRequestedOp();

		$baseUrl = $templateManager->get_template_vars('baseUrl');
		$additionalHeadData = $templateManager->get_template_vars('additionalHeadData');
		$allLocales = AppLocale::getAllLocales();
		$localeList = array();
		foreach ($allLocales as $key => $locale) {
			$localeList[] = String::substr($key, 0, 2);
		}

		$tinymceScript = '
		<script src="'.$baseUrl.'/'.TINYMCE_JS_PATH.'/tiny_mce_gzip.js"></script>
		<script>
			tinyMCE_GZ.init({
				relative_urls : "false",
				plugins : "paste,jbimages,fullscreen",
				themes : "advanced",
				languages : "' . join(',', $localeList) . '",
				disk_cache : true
			});
		</script>
		<script>
			tinyMCE.init({
				entity_encoding : "raw",
				plugins : "paste,jbimages,fullscreen",
				mode: "specific_textareas",
				editor_selector: "richContent",
				language : "' . String::substr(AppLocale::getLocale(), 0, 2) . '",
				relative_urls : false,
				forced_root_block : false,
				paste_auto_cleanup_on_paste : true,
				apply_source_formatting : false,
				theme : "advanced",
				theme_advanced_buttons1 : "cut,copy,paste,|,bold,italic,underline,bullist,numlist,|,link,unlink,help,code,fullscreen,jbimages",
				theme_advanced_buttons2 : "",
				theme_advanced_buttons3 : "",
				init_instance_callback: $.pkp.controllers.SiteHandler.prototype.triggerTinyMCEInitialized,
				setup: $.pkp.controllers.SiteHandler.prototype.triggerTinyMCESetup
			});
		</script>';

		$templateManager->assign('additionalHeadData', $additionalHeadData."\n".$tinymceScript);
		return false;
	}

	/**
	 * Get the display name of this plugin
	 * @return string
	 */
	function getDisplayName() {
		return __('plugins.generic.tinymce.name');
	}

	/**
	 * Get the description of this plugin
	 * @return string
	 */
	function getDescription() {
		if ($this->isMCEInstalled()) return __('plugins.generic.tinymce.description');
		return __('plugins.generic.tinymce.descriptionDisabled', array('tinyMcePath' => TINYMCE_INSTALL_PATH));
	}

	/**
	 * Check whether or not the TinyMCE library is installed
	 * @return boolean
	 */
	function isMCEInstalled() {
		return file_exists(str_replace('/', DIRECTORY_SEPARATOR, TINYMCE_JS_PATH) . DIRECTORY_SEPARATOR. 'tiny_mce.js');
	}

	/**
	 * Get a list of available management verbs for this plugin
	 * @return array
	 */
	function getManagementVerbs() {
		$verbs = array();
		if ($this->isMCEInstalled()) $verbs = parent::getManagementVerbs();
		return $verbs;
	}
}

?>
