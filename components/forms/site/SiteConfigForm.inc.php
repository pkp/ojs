<?php
/**
 * @file controllers/form/site/SiteConfigForm.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SiteConfigForm
 * @ingroup classes_controllers_form
 *
 * @brief A preset form for the site config settings.
 */
import('lib.pkp.components.forms.FormComponent');

define('FORM_SITE_CONFIG', 'siteConfig');

class SiteConfigForm extends FormComponent {
	/** @copydoc FormComponent::$id */
	public $id = FORM_SITE_CONFIG;

	/** @copydoc FormComponent::$method */
	public $method = 'PUT';

	/**
	 * Constructor
	 *
	 * @param $action string URL to submit the form to
	 * @param $locales array Supported locales
	 * @param $site Site
	 */
	public function __construct($action, $locales, $site) {
		$this->action = $action;
		$this->successMessage = __('admin.settings.config.success');
		$this->locales = $locales;

		$contexts = \ServicesContainer::instance()->get('context')
			->getContexts(['isEnabled' => true]);

		$this->addField(new FieldText('title', [
			'label' => __('admin.settings.siteTitle'),
			'isRequired' => true,
			'isMultilingual' => true,
			'value' => $site->getData('title'),
		]));

		if (!empty($contexts)) {
			$options = [['value' => '', 'label' => '']];
			foreach ($contexts as $context) {
				$options[] = [
					'value' => $context->getId(),
					'label' => $context->getLocalizedData('name'),
				];
			}
			$this->addField(new FieldSelect('redirect', [
				'label' => __('admin.settings.journalRedirect'),
				'description' => __('admin.settings.journalRedirectInstructions'),
				'options' => $options,
				'value' => $site->getData('redirect'),
			]));
		}

		$this->addField(new FieldText('minPasswordLength', [
			'label' => __('admin.settings.minPasswordLength'),
			'isRequired' => true,
			'size' => 'small',
			'value' => $site->getData('minPasswordLength'),
		]));
	}
}
