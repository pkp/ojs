<?php
/**
 * @file classes/components/form/FieldArchivingPn.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class FieldArchivingPn
 * @ingroup classes_controllers_form
 *
 * @brief An extension of the FieldOptions for the configuration setting which
 *  allows a user to enable/disable the PKP Preservation Network plugin, and
 *  access the settings, from a form-like view in the distribution settings.
 */
namespace APP\components\forms;

use PKP\components\forms\FieldOptions;

class FieldArchivingPn extends FieldOptions {
	/** @copydoc Field::$component */
	public $component = 'field-archiving-pn';

	/** @var string The message to show in a modal when the link is clicked.  */
	public $terms = '';

	/** @var string The message to show when the plugin is disabled. */
	public $disablePluginSuccess = '';

	/** @var string The message to show when the plugin was enabled.. */
	public $enablePluginSuccess = '';

	/** @var string The URL to enable the PLN plugin. */
	public $enablePluginUrl = '';

	/** @var string The URL to disable the PLN plugin. */
	public $disablePluginUrl = '';

	/** @var string The URL to load the PN plugin settings. */
	public $settingsUrl = '';

	/**
	 * @copydoc Field::getConfig()
	 */
	public function getConfig() {
		$config = parent::getConfig();
		$config['terms'] = $this->terms;
		$config['disablePluginSuccess'] = $this->disablePluginSuccess;
		$config['enablePluginSuccess'] = $this->enablePluginSuccess;
		$config['enablePluginUrl'] = $this->enablePluginUrl;
		$config['disablePluginUrl'] = $this->disablePluginUrl;
		$config['settingsUrl'] = $this->settingsUrl;

		return $config;
	}
}
