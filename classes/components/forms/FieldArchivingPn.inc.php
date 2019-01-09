<?php
/**
 * @file classes/components/form/FieldArchivingPn.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FieldArchivingPn
 * @ingroup classes_controllers_form
 *
 * @brief An extension of the FieldOptions for the configuration setting which
 *  allows a user to enable/disable the PKP Preservation Network plugin, and
 *  access the settings, from a form-like view in the distribution settings.
 */
namespace APP\components\forms;
class FieldArchivingPn extends FieldOptions {
	/** @copydoc Field::$component */
	public $component = 'field-archiving-pn';

	/** @var string The message to show in a modal when the link is clicked.  */
	public $terms = '';

	/** @var string The URL to enable the PLN plugin. */
	public $enablePluginUrl = '';

	/** @var string The URL to disable the PLN plugin. */
	public $disablePluginUrl = '';

	/** @var string The URL to load the PN plugin settings. */
	public $settingsUrl = '';

	/** @var string A CSRF token for the plugin enable/disable requests */
	public $csrfToken = '';

	/**
	 * @copydoc Field::getConfig()
	 */
	public function getConfig() {
		$config = parent::getConfig();
		$config['terms'] = $this->terms;
		$config['enablePluginUrl'] = $this->enablePluginUrl;
		$config['disablePluginUrl'] = $this->disablePluginUrl;
		$config['settingsUrl'] = $this->settingsUrl;
		$config['csrfToken'] = $this->csrfToken;

		return $config;
	}
}
