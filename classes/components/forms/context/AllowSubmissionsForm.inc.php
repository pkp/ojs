<?php
/**
 * @file classes/components/form/context/AllowSubmissionsForm.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class AllowSubmissionsForm
 * @ingroup classes_controllers_form
 *
 * @brief  A preset form for allowing new submissions.
 */
namespace APP\components\forms\context;
use \PKP\components\forms\FormComponent;
use \PKP\components\forms\FieldOptions;

define('FORM_ALLOW_SUBMISSIONS', 'allowSubmissions');

class AllowSubmissionsForm extends FormComponent {
	/** @copydoc FormComponent::$id */
	public $id = FORM_ALLOW_SUBMISSIONS;

	/** @copydoc FormComponent::$method */
	public $method = 'PUT';

	/**
	 * Constructor
	 *
	 * @param $action string URL to submit the form to
	 * @param $context Context Journal or Press to change settings for
	 */
	public function __construct($action, $context) {
		$this->action = $action;
		$this->successMessage = __('manager.setup.allowSubmissions.success');

		$this->addField(new FieldOptions('enableSubmissions', [
				'label' => __('manager.setup.allowSubmissions.enableSubmissions.label'),
				'description' => __('manager.setup.allowSubmissions.description'),
				'options' => [
					[
						'value' => true,
						'label' => __('manager.setup.allowSubmissions.enableSubmissions.label', ['lockssUrl' => $lockssUrl]),
					],
				],
				'value' => (bool) $context->getData('enableSubmissions'),
			]));
	}
}
