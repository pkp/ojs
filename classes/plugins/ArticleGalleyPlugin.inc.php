<?php

/**
 * @file classes/plugins/ArticleGalleyPlugin.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleGalleyPlugin
 * @ingroup plugins
 *
 * @brief Abstract class for article galley plugins
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class ArticleGalleyPlugin extends GenericPlugin {

	/*
	 * Override public methods from PKPPlugin
	 */
	/**
	 * @see PKPPlugin::register()
	 */
	function register($category, $path) {
		if (parent::register($category, $path)) {
			if ($this->getEnabled()) {
				HookRegistry::register('Templates::Galley::displayGalley', array($this, 'callback'));
			}
			return true;
		}
		return false;
	}

	/**
	 * Get the filename of the template. (Default behavior may
	 * be overridden through some combination of this function and the
	 * getArticleGalley function.)
	 * Returning null from this function results in an empty display.
	 *
	 * @return string
	 */
	function getTemplateFilename() {
		return 'display.tpl';
	}

	/**
	 * Display this galley in some manner.
	 *
	 * @param $templateMgr object
	 * @param $request PKPRequest (Optional for legacy plugins)
	 * @return string
	 */
	function getArticleGalley($templateMgr, $request = null, $params) {
		$templateFilename = $this->getTemplateFilename();
		if ($templateFilename === null) return '';
		return $templateMgr->fetch($this->getTemplatePath() . $templateFilename);
	}

	/**
	 * Callback that renders the galley.
	 *
	 * @param $hookName string
	 * @param $args array
	 * @return string
	 */
	function callback($hookName, $args) {
		$params =& $args[0];
		$templateMgr =& $args[1];
		$output =& $args[2];

		$galley = $templateMgr->get_template_vars('galley'); // set in ArticleHandler
		if ($galley && $galley->getGalleyType() == $this->getName()) {
			$output .= $this->getArticleGalley($templateMgr, $this->getRequest(), $params);
		}

		return false;
	}
}
?>
