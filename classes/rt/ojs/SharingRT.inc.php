<?php

/**
 * @defgroup rt_ojs
 */

/**
 * @file classes/rt/ojs/SharingRT.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SharingRT
 * @ingroup rt_ojs
 *
 * @brief OJS-specific AddThis end-user interface.
 */

import('classes.i18n.AppLocale');

class SharingRT {
	function getLanguages() {
		return array (
			'zh' => 'Chinese',
			'da' => 'Danish',
			'nl' => 'Dutch',
			'en' => 'English',
			'fi' => 'Finnish',
			'fr' => 'French',
			'de' => 'German',
			'he' => 'Hebrew',
			'it' => 'Italian',
			'ja' => 'Japanese',
			'ko' => 'Korean',
			'no' => 'Norwegian',
			'pl' => 'Polish',
			'pt' => 'Portugese',
			'ru' => 'Russian',
			'es' => 'Spanish',
			'sv' => 'Swedish'
		);
	}

	function getBtnStyles() {
		return array(
			'share' => array (
				'img' => 'lg-share-%lang%.gif',
				'w' => 125,
				'h' => 16
			),
			'bookmark' => array (
				'img' => 'lg-bookmark-en.gif',
				'w' => 125,
				'h' => 16
			),
			'addthis' => array (
				'img' => 'lg-addthis-en.gif',
				'w' => 125,
				'h' => 16
			),
			'share-small' => array (
				'img' => 'sm-share-%lang%.gif',
				'w' => 83,
				'h' => 16
			),
			'bookmark-small' => array (
				'img' => 'sm-bookmark-en.gif',
				'w' => 83,
				'h' => 16
			),
			'plus' => array (
				'img' => 'sm-plus.gif',
				'w' => 16,
				'h' => 16
			)
			/* Add your own style here, like this:
			 , 'custom' => array('img'=>'http://example.com/button.gif', 'w'=>16, 'h'=>16) */
		);
	}

	/**
	 * Generate the information for the HTML tag for the sharing button
	 * @return array(url, width, height)
	 */
	function sharingButtonImage($journalRt) {
		$btnStyle = $journalRt->getSharingButtonStyle();
		if ($journalRt->getSharingLanguage() != 'en') {
			if ($btnStyle == 'bookmark' || $btnStyle == 'addthis' || $btnStyle == 'bookmark-sm') {
				$btnStyle = 'share';
			}
		}
		$btnStyles = SharingRT::getBtnStyles();
		if (!isset ($btnStyles[$btnStyle])) {
			$btnStyle = 'share';
		}
		$btnRecord = $btnStyles[$btnStyle];
		$btnUrl = (strpos(trim($btnRecord['img']), 'http://') !== 0 ? "http://s7.addthis.com/static/btn/" : "") . $btnRecord['img'];
		$btnUrl = str_replace('%lang%', SharingRT::sharingLocale($journalRt->getSharingLanguage()), $btnUrl);
		$btnWidth = $btnRecord['w'];
		$btnHeight = $btnRecord['h'];
		return array (
			$btnUrl,
			$btnWidth,
			$btnHeight
		);
	}

	/**
	 * determine the correct language for the sharing button. Attempt to use the user's local
	 * setting if it is one that AddThis supports. If not, use the language the administrator
	 * has chosen.
	 *
	 * @return string
	 * @param $default string
	 */
	function sharingLocale($default) {
		// getLocale() returns a string like 'en_US'.
		$locale = AppLocale::getLocale();
		$lang = substr($locale, 0, 2);
		$languages = SharingRT::getLanguages();
		if (isset ($languages[$lang])) {
			return $lang;
		}
		return $default;
	}
}

?>
