<?php

/**
 * @file plugins/generic/mathjax/MathJaxPlugin.inc.php
 *
 * Copyright (c) 2017 Vasyl Ostrovskyi
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MathJaxPlugin
 * @ingroup plugins_generic_mathjax
 *
 * @brief Plugin to allow MathJax scripts to be added to OCS
 */

// $Id$


import('lib.pkp.classes.plugins.GenericPlugin');

class MathJaxPlugin extends GenericPlugin {
	/**
	 * Register the plugin, if enabled; note that this plugin
	 * @param $category string
	 * @param $path string
	 * @return boolean
	 */
	function register($category, $path) {
		if (parent::register($category, $path)) {
			if ($this->getEnabled()) {
				HookRegistry::register('TemplateManager::display', array(&$this, 'insertMJ'));
			}
			return true;
		}
		return false;
	}


//	function zatyk($hookName,$args){
//		return false;
//	}

	/**
	 * Hook callback function for TemplateManager::display
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 */
	function insertMJ($hookName, $args) {
		$templateManager =& $args[0];
		$MathJaxScript="</script>\n";
		$MathJaxScript .= "<script type=\"text/x-mathjax-config\">\n MathJax.Hub.Config({\n   tex2jax: {inlineMath: [['$','$'], ['\\\\(','\\\\)']]},\n   processEscapes: true\n }); \n</script>\n";
		$MathJaxScript .= '<script type="text/javascript" async src="https://cdn.mathjax.org/mathjax/latest/MathJax.js?config=TeX-MML-AM_CHTML">' ."\n";
		$templateManager->addJavaScript('mathjax', $MathJaxScript, 
			array(
				'inline' => true,
				'contexts' => array('frontend', 'backend')
			)
		);
		return false;
	}

	/**
	 * Get the name of the settings file to be installed on new context
	 * creation.
	 * @return string
	 */
//	function getContextSpecificPluginSettingsFile() {
//		return $this->getPluginPath() . '/settings.xml';
//	}

	/**
	 * Get the display name of this plugin
	 * @return string
	 */
	function getDisplayName() {
		return __('plugins.generic.mathjax.name');
	}

	/**
	 * Get the description of this plugin
	 * @return string
	 */
	function getDescription() {
		return __('plugins.generic.mathjax.description');
	}
}
?>