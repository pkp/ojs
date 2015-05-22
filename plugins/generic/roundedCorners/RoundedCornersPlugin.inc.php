<?php

/**
 * @file plugins/generic/roundedCorners/RoundedCornersPlugin.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2007-2009 Juan Pablo Alperin, Gunther Eysenbach
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RoundedCornersPlugin
 * @ingroup plugins_generic_roundedCorners
 *
 * @brief Rounded Corners plugin class
 *
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class RoundedCornersPlugin extends GenericPlugin {
	function getDisplayName() {
		return __('plugins.generic.roundedcorners.displayName');
	}

	function getDescription() {
		return __('plugins.generic.roundedcorners.description');
	}

	function register($category, $path) {
		if (parent::register($category, $path)) {
			if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) return true;
			if ( $this->getEnabled() ) {
				HookRegistry::register('TemplateManager::display', array(&$this, 'templateManagerCallback'));
			}
			return true;
		}
		return false;
	}

	function templateManagerCallback($hookName, $args) {
		$templateMgr =& $args[0]; //TemplateManager::getManager();
		$baseUrl = $templateMgr->get_template_vars('baseUrl');
		$roundedCornerCssUrl = $baseUrl . '/plugins/generic/roundedCorners/roundedcorners.css';
		$templateMgr->addStyleSheet($roundedCornerCssUrl);
		$templateMgr->register_outputfilter(array('RoundedCornersPlugin', 'roundOutputFilter'));
	}

	/**
	 * Do the work of adding in the <span> blocks
	 */
	function roundOutputFilter($output, &$smarty) {
		$top = '<span class="rtop"><span class="r1"></span><span class="r2"></span><span class="r3"></span><span class="r4"></span></span><div class="roundedCorner">';
		$bottom = '</div><span class="rbottom"><span class="r4"></span><span class="r3"></span><span class="r2"></span><span class="r1"></span></span>';
		$newOutput = $output;

		$matches = RoundedCornersPlugin::_getDivs($newOutput, 'block');
		if (count($matches) > 0) {
			foreach ($matches as $match) {
				if (preg_match('/<div[^>]+class\=\"block\"[^>]*>(\s*)(<\/div>[^<]*)$/', $match) > 0 ) continue;

				$newBlock = preg_replace('/(<div[^>]+class\=\"block)(\"[^>]*>)/is', "\\1 alreadyRounded\\2$top", $match, PREG_OFFSET_CAPTURE);
				$newBlock = preg_replace('/([^>]*)(<\/div>[^<]*)$/', "\\1$bottom\\2", $newBlock);

				$newOutput = str_replace($match, $newBlock, $newOutput);
			}
		}

		return $newOutput;
	}

	/**
	 * look for the opening and closing divs with a particular $class in the $subject
	 * Have to count opening and closing divs since regexes are not so good matching opening and closing tags
	 */
	function _getDivs($subject, $class) {
		preg_match_all("/<div[^>]+class\=\"$class\"[^>]*>/is", $subject, $matches, PREG_OFFSET_CAPTURE);

		$matches = $matches[0];
		for ($i=0; $i<count($matches); $i++) {
			$openDivs = 0;
			$closedDivs = 0;
			$divClosePosition = 0;
			$divPosition = array();
			preg_match_all("/<\/?div[^>]*>/is", $subject, $divPosition, PREG_OFFSET_CAPTURE, $matches[$i][1]);
			$divPosition = $divPosition[0];
			for ($i2=0; $i2<count($divPosition); $i2++) {
				if (eregi("\/", $divPosition[$i2][0])) {
					$closedDivs++;
				} else {
					$openDivs++;
				}

				if($closedDivs > $openDivs-1) {
					$divClosePosition = $divPosition[$i2][1];
					$divLength = $divClosePosition+6 - $matches[$i][1];
					$divs[$i] = substr($subject, $matches[$i][1], $divLength);
					break;
				}
			}
		}
		return $divs;
	}
}

?>
