<?php
/**
 * @file classes/components/form/context/SubmissionSettingsForm.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionSettingsForm
 * @ingroup classes_controllers_form
 *
 * @brief A preset form for configuring PPS submission settings
 * 
 */
namespace APP\components\forms\context;
use \PKP\components\forms\FormComponent;
use \PKP\components\forms\FieldSelect;
use \PKP\components\forms\FieldOptions;

define('FORM_SUBMISSION_SETTINGS', 'submissionSettings');

class SubmissionSettingsForm extends FormComponent {
	/** @copydoc FormComponent::$id */
	public $id = FORM_SUBMISSION_SETTINGS;

	/** @copydoc FormComponent::$method */
	public $method = 'PUT';

	/**
	 * Constructor
	 *
	 * @param $action string URL to submit the form to
	 * @param $locales array Supported locales
	 * @param $context Context to change settings for
	 */
	public function __construct($action, $locales, $context) {
		$this->action = $action;
		$this->successMessage = __('manager.setup.submissionSettings.success');
		$this->locales = $locales;

		$this->addField(new FieldOptions('enableOai', [
				'label' => __('manager.setup.enableOai'),
				'description' => __('manager.setup.enableOai.description'),
				'type' => 'radio',
				'options' => [
					['value' => true, 'label' => __('common.enable')],
					['value' => false, 'label' => __('common.disable')],
				],
				'value' => $context->getData('enableOai'),
			]));
	}
}