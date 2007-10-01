<?php

/**
 * @file CmsRssSettingsForm.inc.php
 *
 * Copyright (c) 2006-2007 Gunther Eysenbach, Juan Pablo Alperin
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.cmsRss
 * @class CmsRssSettingsForm
 *
 * Form for journal managers to add the RSS feeds to aggregate
 *
 */

import('form.Form');

class CmsRssSettingsForm extends Form {
	/** @var $journalId int */
	var $journalId;

	/** @var $plugin object */
	var $plugin;

	/** $var $errors string */
	var $errors;

	/**
	 * Constructor
	 * @param $journalId int
	 */
	function CmsRssSettingsForm(&$plugin, $journalId) {

		parent::Form($plugin->getTemplatePath() . 'settingsForm.tpl');

		$this->journalId = $journalId;
		$this->plugin =& $plugin;

		$this->addCheck(new FormValidatorArray($this, 'urls', 'required', 'plugins.generic.cmsrss.requiredFields', array('pageName', 'url')));
		$this->addCheck(new FormValidatorCustom($this, 'months', 'required', 'plugins.generic.cmsrss.monthsRequired', create_function('$months', 'return is_numeric($months) && $months >= 0;')));
	}

	/**
	 * Initialize form data from  the plugin settings to the form
	 */
	function initData() {
		$journalId = $this->journalId;
		$plugin =& $this->plugin;

		$templateMgr = &TemplateManager::getManager();
		$this->_data = array(
			'urls' => array()
			);

		$urls = $plugin->getSetting($journalId, 'urls');
		$months = $plugin->getSetting($journalId, 'months');
		if ( !($months > 0) ) $months = 3;
		$aggregate = $plugin->getSetting($journalId, 'aggregate');

		for ($i=0, $count=count($urls); $i < $count; $i++) {
			array_push(
				$this->_data['urls'],
				array(
					'urlId' => $urls[$i]['urlId'],
					'pageName' => $urls[$i]['pageName'],
					'url' => $urls[$i]['url']
				)
			);
		}
		$this->setData('months', $months);
		$this->setData('aggregate', $aggregate);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(
			array(
				'months',
				'aggregate',
				'urls',
				'deletedUrls'
			)
		);
	}

	/**
	 * Update the plugin settings
	 */
	function execute() {
		$plugin =& $this->plugin;
		$journalId = $this->journalId;

		//sort the URL's by page name
		$urls = $this->getData('urls');
		usort($urls, array(&$this, '_urlSort'));

		// Update urls
		$plugin->updateSetting($journalId, 'urls', $urls);
		$this->_data['urls'] = $urls;
		//update other settings
		$plugin->updateSetting($journalId, 'months', $this->getData('months'));
		$plugin->updateSetting($journalId, 'aggregate', $this->getData('aggregate'));

	}

	/**
	 * Internal helper function to sort an array by Page Name.
	 */
	function _urlSort( $a, $b) {
		$a = $a['pageName'];
		$b = $b['pageName'];

		return strcmp($a, $b);
	}

}

?>
