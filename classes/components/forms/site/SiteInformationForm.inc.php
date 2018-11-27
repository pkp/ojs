<?php
/**
 * @file controllers/form/site/SiteInformationForm.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SiteInformationForm
 * @ingroup classes_controllers_form
 *
 * @brief A preset form for the site information settings.
 */
namespace APP\components\forms\site;
use \PKP\components\forms\FormComponent;
use \PKP\components\forms\FieldRichTextarea;
use \PKP\components\forms\FieldText;
use \PKP\components\forms\FieldTextarea;

define('FORM_SITE_INFO', 'siteInfo');

class SiteInformationForm extends FormComponent {
	/** @copydoc FormComponent::$id */
	public $id = FORM_SITE_INFO;

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
		$this->successMessage = __('admin.settings.info.success');
		$this->locales = $locales;

		$this->addField(new FieldTextarea('about', [
				'label' => __('admin.settings.about'),
				'isMultilingual' => true,
				'value' => $site->getData('about'),
			]))
			->addField(new FieldText('contactName', [
				'label' => __('admin.settings.contactName'),
				'isRequired' => true,
				'isMultilingual' => true,
				'value' => $site->getData('contactName'),
			]))
			->addField(new FieldText('contactEmail', [
				'label' => __('admin.settings.contactEmail'),
				'isRequired' => true,
				'isMultilingual' => true,
				'value' => $site->getData('contactEmail'),
			]))
			->addField(new FieldRichTextarea('privacyStatement', [
				'label' => __('manager.setup.privacyStatement'),
				'description' => __('manager.setup.privacyStatement.description'),
				'isMultilingual' => true,
				'value' => $site->getData('privacyStatement'),
			]));
	}
}
