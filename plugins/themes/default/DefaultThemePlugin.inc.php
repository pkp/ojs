<?php

/**
 * @file plugins/themes/default/DefaultThemePlugin.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DefaultThemePlugin
 * @ingroup plugins_themes_default
 *
 * @brief Default theme
 */

import('lib.pkp.classes.plugins.ThemePlugin');

class DefaultThemePlugin extends ThemePlugin {
	/**
	 * @copydoc ThemePlugin::isActive()
	 */
	public function isActive() {
		if (defined('SESSION_DISABLE_INIT')) return true;
		return parent::isActive();
	}

	/**
	 * Initialize the theme's styles, scripts and hooks. This is run on the
	 * currently active theme and it's parent themes.
	 *
	 * @return null
	 */
	public function init() {

		// Register theme options
		$this->addOption('typography', 'FieldOptions', [
			'type' => 'radio',
			'label' => __('plugins.themes.default.option.typography.label'),
			'description' => __('plugins.themes.default.option.typography.description'),
			'options' => [
				[
					'value' => 'notoSans',
					'label' => __('plugins.themes.default.option.typography.notoSans'),
				],
				[
					'value' => 'notoSerif',
					'label' => __('plugins.themes.default.option.typography.notoSerif'),
				],
				[
					'value' => 'notoSerif_notoSans',
					'label' => __('plugins.themes.default.option.typography.notoSerif_notoSans'),
				],
				[
					'value' => 'notoSans_notoSerif',
					'label' => __('plugins.themes.default.option.typography.notoSans_notoSerif'),
				],
				[
					'value' => 'lato',
					'label' => __('plugins.themes.default.option.typography.lato'),
				],
				[
					'value' => 'lora',
					'label' => __('plugins.themes.default.option.typography.lora'),
				],
				[
					'value' => 'lora_openSans',
					'label' => __('plugins.themes.default.option.typography.lora_openSans'),
				],
			],
			'default' => 'notoSans',
		]);

		$this->addOption('baseColour', 'FieldColor', [
			'label' => __('plugins.themes.default.option.colour.label'),
			'description' => __('plugins.themes.default.option.colour.description'),
			'default' => '#1E6292',
		]);

		$this->addOption('showDescriptionInJournalIndex', 'FieldOptions', [
			'label' => __('plugins.themes.default.option.showDescriptionInJournalIndex.label'),
				'options' => [
				[
					'value' => true,
			  		'label' => __('plugins.themes.default.option.showDescriptionInJournalIndex.option'),
				],
		  	],
		  	'default' => false,
		]);

		// Load primary stylesheet
		$this->addStyle('stylesheet', 'styles/index.less');

		// Store additional LESS variables to process based on options
		$additionalLessVariables = array();

		// Load fonts from Google Font CDN
		// To load extended latin or other character sets, see:
		// https://www.google.com/fonts#UsePlace:use/Collection:Noto+Sans
		if (Config::getVar('general', 'enable_cdn')) {

			if ($this->getOption('typography') === 'notoSerif') {
				$this->addStyle(
					'fontNotoSerif',
					'//fonts.googleapis.com/css?family=Noto+Serif:400,400i,700,700i',
					array('baseUrl' => '')
				);
				$additionalLessVariables[] = '@font: "Noto Serif", -apple-system, BlinkMacSystemFont, "Segoe UI", "Roboto", "Oxygen-Sans", "Ubuntu", "Cantarell", "Helvetica Neue", sans-serif;';

			} elseif (strpos($this->getOption('typography'), 'notoSerif') !== false) {
				$this->addStyle(
					'fontNotoSansNotoSerif',
					'//fonts.googleapis.com/css?family=Noto+Sans:400,400i,700,700i|Noto+Serif:400,400i,700,700i',
					array('baseUrl' => '')
				);

				// Update LESS font variables
				if ($this->getOption('typography') == 'notoSerif_notoSans') {
					$additionalLessVariables[] = '@font-heading: "Noto Serif", serif;';
				} elseif ($this->getOption('typography') == 'notoSans_notoSerif') {
					$additionalLessVariables[] = '@font: "Noto Serif", serif;@font-heading: "Noto Sans", serif;';
				}

			} elseif ($this->getOption('typography') == 'lato') {
				$this->addStyle(
					'fontLato',
					'//fonts.googleapis.com/css?family=Lato:400,400i,900,900i',
					array('baseUrl' => '')
				);
				$additionalLessVariables[] = '@font: Lato, sans-serif;';

			} elseif ($this->getOption('typography') == 'lora') {
				$this->addStyle(
					'fontLora',
					'//fonts.googleapis.com/css?family=Lora:400,400i,700,700i',
					array('baseUrl' => '')
				);
				$additionalLessVariables[] = '@font: Lora, serif;';

			} elseif ($this->getOption('typography') == 'lora_openSans') {
				$this->addStyle(
					'fontLoraOpenSans',
					'//fonts.googleapis.com/css?family=Lora:400,400i,700,700i|Open+Sans:400,400i,700,700i',
					array('baseUrl' => '')
				);
				$additionalLessVariables[] = '@font: "Open Sans", sans-serif;@font-heading: Lora, serif;';

			} else {
				$this->addStyle(
					'fontNotoSans',
					'//fonts.googleapis.com/css?family=Noto+Sans:400,400italic,700,700italic',
					array('baseUrl' => '')
				);
			}
		}

		// Update colour based on theme option
		if ($this->getOption('baseColour') !== '#1E6292') {
			$additionalLessVariables[] = '@bg-base:' . $this->getOption('baseColour') . ';';
			if (!$this->isColourDark($this->getOption('baseColour'))) {
				$additionalLessVariables[] = '@text-bg-base:rgba(0,0,0,0.84);';
			}
		}

		// Pass additional LESS variables based on options
		if (!empty($additionalLessVariables)) {
			$this->modifyStyle('stylesheet', array('addLessVariables' => join($additionalLessVariables)));
		}

		$request = Application::get()->getRequest();

		// Load icon font FontAwesome - http://fontawesome.io/
		if (Config::getVar('general', 'enable_cdn')) {
			$url = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css';
		} else {
			$url = $request->getBaseUrl() . '/lib/pkp/styles/fontawesome/fontawesome.css';
		}
		$this->addStyle(
			'fontAwesome',
			$url,
			array('baseUrl' => '')
		);

		// Load jQuery from a CDN or, if CDNs are disabled, from a local copy.
		$min = Config::getVar('general', 'enable_minified') ? '.min' : '';
		if (Config::getVar('general', 'enable_cdn')) {
			$jquery = '//ajax.googleapis.com/ajax/libs/jquery/' . CDN_JQUERY_VERSION . '/jquery' . $min . '.js';
			$jqueryUI = '//ajax.googleapis.com/ajax/libs/jqueryui/' . CDN_JQUERY_UI_VERSION . '/jquery-ui' . $min . '.js';
		} else {
			// Use OJS's built-in jQuery files
			$jquery = $request->getBaseUrl() . '/lib/pkp/lib/vendor/components/jquery/jquery' . $min . '.js';
			$jqueryUI = $request->getBaseUrl() . '/lib/pkp/lib/vendor/components/jqueryui/jquery-ui' . $min . '.js';
		}
		// Use an empty `baseUrl` argument to prevent the theme from looking for
		// the files within the theme directory
		$this->addScript('jQuery', $jquery, array('baseUrl' => ''));
		$this->addScript('jQueryUI', $jqueryUI, array('baseUrl' => ''));
		$this->addScript('jQueryTagIt', $request->getBaseUrl() . '/lib/pkp/js/lib/jquery/plugins/jquery.tag-it.js', array('baseUrl' => ''));

		// Load Bootsrap's dropdown
		$this->addScript('popper', 'js/lib/popper/popper.js');
		$this->addScript('bsUtil', 'js/lib/bootstrap/util.js');
		$this->addScript('bsDropdown', 'js/lib/bootstrap/dropdown.js');

		// Load custom JavaScript for this theme
		$this->addScript('default', 'js/main.js');

		// Add navigation menu areas for this theme
		$this->addMenuArea(array('primary', 'user'));
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
	 * Get the display name of this plugin
	 * @return string
	 */
	function getDisplayName() {
		return __('plugins.themes.default.name');
	}

	/**
	 * Get the description of this plugin
	 * @return string
	 */
	function getDescription() {
		return __('plugins.themes.default.description');
	}
}
